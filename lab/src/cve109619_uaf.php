<?php
declare(strict_types=1);

/**
 * CVE-2026-109619 — Simulasi Edukatif: Use-After-Free pada state worker/global.
 *
 * CATATAN KELAS: ini adalah SIMULASI. Perilaku "unfixed" vs "fixed" diemulasi
 * dari env LAB_PHP_STATUS (diset build Docker). Karena MaxRequestWorkers = 1,
 * request berikutnya selalu masuk ke PID worker yang sama — persis kondisi
 * yang dibutuhkan agar state usang tingkat proses bisa terbaca kembali.
 *
 * Alur dua request pada worker yang sama:
 *   Request A (?action=stage):
 *     - Alokasikan record "payload" di memori request.
 *     - Saat request berakhir, payload dibebaskan (free).
 *       - UNFIXED: pointer di registry tingkat worker TIDAK dihapus (dangling).
 *       - FIXED  : pointer ikut dibersihkan (tidak ada referensi usang).
 *   Request B (?action=read) di PID yang sama:
 *     - UNFIXED: membaca record yang sudah freed → USE AFTER FREE.
 *     - FIXED  : pointer tidak ada → SAFE.
 */

$action = $_GET['action'] ?? 'read';

$stateDir  = '/tmp/php_sessions';
$pid       = getmypid();
$regFile   = sprintf('%s/lab_worker_%d.json', $stateDir, $pid);
$isFixed   = (getenv('LAB_PHP_STATUS') ?: 'unknown') === 'fixed';

function ensure_state_dir(string $dir): void
{
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

if ($action === 'reset') {
    @unlink($regFile);
    header('Location: ?action=read');
    exit;
}

if ($action === 'stage') {
    ensure_state_dir($stateDir);

    $recordId = sprintf('rec_%s', bin2hex(random_bytes(4)));
    $payload  = str_repeat('CAFEF00D', 8); // isi "memori" yang akan dibebaskan

    // Simulasi: record dialokasikan lalu di-free di akhir request.
    // Registry tingkat worker menyimpan referensi ke record tersebut.
    $registry = [
        'pid'        => $pid,
        'ref'        => $recordId,
        'dangling'   => !$isFixed, // unfixed: pointer usang ditinggalkan
        'record'     => [
            'id'      => $recordId,
            'state'   => 'freed',   // memori record sudah dibebaskan
            'payload' => $payload,
            'staged'  => gmdate('c'),
        ],
    ];
    @file_put_contents($regFile, json_encode($registry, JSON_PRETTY_PRINT));

    header('Location: ?action=read');
    exit;
}

// ---- action=read ----
$raw   = @file_get_contents($regFile);
$reg   = is_string($raw) ? (json_decode($raw, true) ?: []) : [];
$found = ($reg['pid'] ?? null) === $pid && isset($reg['ref']);

$uaf = $found
    && (bool) ($reg['dangling'] ?? false)
    && (($reg['record']['state'] ?? '') === 'freed');

if ($uaf) {
    $verdict = 'USE AFTER FREE';
    $detail  = sprintf(
        'Worker PID %d masih menyimpan pointer ke %s yang SUDAH dibebaskan request sebelumnya. Request ini membaca %d byte data usang.',
        (int) $pid,
        (string) $reg['ref'],
        strlen((string) $reg['record']['payload'])
    );
    $css = 'unfixed';
} elseif ($found) {
    $verdict = 'SAFE';
    $detail  = 'Registry worker bersih — tidak ada pointer yang menunjuk memori yang sudah dibebaskan.';
    $css     = 'fixed';
} else {
    $verdict = 'CLEAN';
    $detail  = 'Belum ada record yang di-stage di worker ini. Buka ?action=stage terlebih dahulu.';
    $css     = 'fixed';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>CVE-2026-109619 — Worker State UAF (<?= htmlspecialchars(PHP_VERSION) ?>)</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #d0d7de; padding: 0.5rem 0.75rem; text-align: left; }
        th { background: #f6f8fa; width: 38%; }
        .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.85rem; }
        .unfixed { background: #ffebe9; color: #cf222e; }
        .fixed { background: #dafbe1; color: #1a7f37; }
        pre { background: #f6f8fa; padding: 0.75rem; border-radius: 6px; overflow-x: auto; }
        a.button { display: inline-block; margin: 0.25rem 0.5rem 0.25rem 0; padding: 0.4rem 0.8rem; border: 1px solid #d0d7de; border-radius: 6px; text-decoration: none; }
    </style>
</head>
<body>
    <h1>CVE-2026-109619 — Simulasi Worker State UAF</h1>
    <table>
        <tr><th>PHP Version</th><td><code><?= htmlspecialchars(PHP_VERSION) ?></code></td></tr>
        <tr><th>Status Patch</th><td><span class="badge <?= $isFixed ? 'fixed' : 'unfixed' ?>"><?= $isFixed ? 'FIXED' : 'UNFIXED' ?></span></td></tr>
        <tr><th>Worker PID</th><td><code><?= (int) $pid ?></code></td></tr>
        <tr><th>Registry File</th><td><code><?= htmlspecialchars($regFile) ?></code></td></tr>
        <tr><th>Verdict</th><td><span class="badge <?= $css ?>"><?= htmlspecialchars($verdict) ?></span></td></tr>
        <tr><th>Detail</th><td><?= htmlspecialchars($detail) ?></td></tr>
    </table>
    <h3>Isi registry worker</h3>
    <pre><?= htmlspecialchars(json_encode($reg, JSON_PRETTY_PRINT) ?: '(kosong)') ?></pre>
    <p>
        <a class="button" href="?action=stage">📦 Stage & free record (request A)</a>
        <a class="button" href="?action=read">🔍 Baca ulang di worker yang sama (request B)</a>
        <a class="button" href="?action=reset">♻️ Reset</a>
    </p>
</body>
</html>
