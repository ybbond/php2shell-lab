<?php
declare(strict_types=1);

/**
 * CVE-2026-109618 — Simulasi Edukatif: OOM bailout (zend_bailout) meninggalkan
 * state internal request yang terinisialisasi sebagian tanpa rollback utuh.
 *
 * CATATAN KELAS: ini adalah SIMULASI. Perilaku "unfixed" vs "fixed" diemulasi
 * dari env LAB_PHP_STATUS (diset build Docker), supaya efeknya bisa dibandingkan
 * berdampingan pada PHP versi apa pun di lab ini.
 *
 * Alur (?action=oom):
 *   1. Request menandai "transaksi" internal sebagai in_flight (state file per worker PID).
 *   2. Alokasi memori berulang sampai Zend Memory Manager memicu OOM (zend_bailout).
 *   3. Fatal error ditangkap lewat shutdown handler (OOM fatal tidak bisa di-catch);
 *      raw fatal error disembunyikan dari browser (tetap tercatat di log container).
 *   4. Pada versi FIXED, cleanup shutdown menandai state sebagai rolled_back.
 *      Pada versi UNFIXED, record parsial tertinggal (tidak ada rollback).
 *   5. Halaman hasil menampilkan pesan fatal + verdict state → bukti visual demo.
 *
 * ?action=status — cek state worker yang sama kapan pun; ?action=reset — mulai ulang.
 */

$action        = $_GET['action'] ?? 'status';
$stateDir      = '/tmp/php_sessions';
$pid           = getmypid();
$stateFile     = sprintf('%s/lab_oom_state_%d.json', $stateDir, $pid);
$isFixed       = (getenv('LAB_PHP_STATUS') ?: 'unknown') === 'fixed';
$isOomRequest  = ($action === 'oom');

function ensure_state_dir(string $dir): void
{
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

function write_state(string $stateFile, array $state): void
{
    @file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT));
}

function oom_state_verdict(array $state): array
{
    $phase = $state['phase'] ?? 'clean';
    return match ($phase) {
        'in_flight' => [
            'verdict' => 'VULNERABLE PATTERN',
            'detail'  => sprintf(
                "State tertinggal 'in_flight' pada chunk ke-%d — terinisialisasi sebagian, tidak di-rollback setelah zend_bailout.",
                (int) ($state['chunks_done'] ?? 0)
            ),
            'css'     => 'unfixed',
        ],
        'rolled_back' => [
            'verdict' => 'SAFE (rolled back)',
            'detail'  => 'Cleanup shutdown mengembalikan state ke kondisi bersih setelah OOM bailout.',
            'css'     => 'fixed',
        ],
        default => [
            'verdict' => 'CLEAN',
            'detail'  => 'Belum ada request OOM yang pernah dilayani worker ini.',
            'css'     => 'fixed',
        ],
    };
}

function render_page(string $title, array $rows, array $state, string $extra = ''): void
{
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 760px; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #d0d7de; padding: 0.5rem 0.75rem; text-align: left; vertical-align: top; }
        th { background: #f6f8fa; width: 30%; }
        .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.85rem; }
        .unfixed { background: #ffebe9; color: #cf222e; }
        .fixed { background: #dafbe1; color: #1a7f37; }
        pre { background: #f6f8fa; padding: 0.75rem; border-radius: 6px; overflow-x: auto; }
        a.button { display: inline-block; margin: 0.25rem 0.5rem 0.25rem 0; padding: 0.4rem 0.8rem; border: 1px solid #d0d7de; border-radius: 6px; text-decoration: none; }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($title) ?></h1>
    <table>
<?php foreach ($rows as $k => $v): ?>
        <tr><th><?= htmlspecialchars((string) $k) ?></th><td><?= $v ?></td></tr>
<?php endforeach; ?>
    </table>
<?php if ($extra !== ''): ?>
    <h3><?= htmlspecialchars($extra) ?></h3>
<?php endif; ?>
    <pre><?= htmlspecialchars(json_encode($state, JSON_PRETTY_PRINT) ?: '(kosong)') ?></pre>
    <p>
        <a class="button" href="?action=oom">💥 Picu OOM lagi</a>
        <a class="button" href="?action=status">🔍 Cek state worker</a>
        <a class="button" href="?action=reset">♻️ Reset</a>
    </p>
</body>
</html>
    <?php
}

/**
 * Shutdown handler: zend_bailout (longjmp) tidak bisa di-catch — handler ini
 * satu-satunya tempat yang tetap berjalan setelah OOM fatal. Di sinilah
 * perbedaan rollback fixed vs unfixed dimodelkan, dan halaman hasil dirender.
 */
register_shutdown_function(function () use ($stateFile, $isFixed, $isOomRequest, $stateDir): void {
    ensure_state_dir($stateDir);

    $raw = @file_get_contents($stateFile);
    $state = is_string($raw) ? (json_decode($raw, true) ?: []) : [];

    $bailoutMidInit = (($state['phase'] ?? null) === 'in_flight');
    if ($bailoutMidInit) {
        // Titik rollback: fixed membersihkan state, unfixed membiarkannya tertinggal.
        $state['phase'] = $isFixed ? 'rolled_back' : 'in_flight';
        $state['rolled_back'] = $isFixed;
        write_state($stateFile, $state);
    }

    if (!$isOomRequest) {
        return; // request status/reset biasa: cukup update state di atas
    }

    // Halaman hasil OOM (memori sudah dibebaskan sebelum shutdown functions berjalan).
    $err   = error_get_last();
    $fatal = ($err['type'] ?? 0) === E_ERROR
        ? (string) $err['message']
        : 'OOM fatal tidak terdeteksi (alokasi mungkin terlalu kecil untuk memory_limit saat ini).';
    $v = oom_state_verdict($state);

    header('Content-Type: text/html; charset=utf-8');
    render_page('CVE-2026-109618 — Hasil OOM Bailout', [
        'PHP Version'   => '<code>' . htmlspecialchars(PHP_VERSION) . '</code>',
        'Status Patch'  => '<span class="badge ' . ($isFixed ? 'fixed' : 'unfixed') . '">' . ($isFixed ? 'FIXED' : 'UNFIXED') . '</span>',
        'Worker PID'    => '<code>' . getmypid() . '</code>',
        'Memory Limit'  => '<code>' . htmlspecialchars((string) ini_get('memory_limit')) . '</code>',
        'Fatal (zend_bailout)' => '<code>' . htmlspecialchars($fatal) . '</code>',
        'Verdict'       => '<span class="badge ' . $v['css'] . '">' . htmlspecialchars($v['verdict']) . '</span>',
        'Detail'        => htmlspecialchars($v['detail']),
    ], $state, 'Isi state internal worker');
});

if ($action === 'reset') {
    @unlink($stateFile);
    header('Location: ?action=status');
    exit;
}

if ($action === 'oom') {
    // Sembunyikan raw fatal error dari browser; pesannya ditampilkan rapi di
    // halaman hasil oleh shutdown handler (tetap tercatat di log container).
    ini_set('display_errors', '0');

    // 1. Tandai transaksi internal dimulai (simulasi inisialisasi struktur request)
    ensure_state_dir($stateDir);
    write_state($stateFile, [
        'phase'       => 'in_flight',
        'op'          => 'oom_probe',
        'chunks_done' => 0,
        'started'     => gmdate('c'),
    ]);

    // 2. Alokasikan memori sampai Zend MM memicu OOM fatal (zend_bailout)
    $chunk = 128 * 1024; // 128 KB per langkah; memory_limit = 2M akan terlampaui cepat
    $buf = '';
    $i = 0;
    while (true) {
        $buf .= str_repeat('A', $chunk);
        $i++;
        write_state($stateFile, [
            'phase'       => 'in_flight',
            'op'          => 'oom_probe',
            'chunks_done' => $i,
            'started'     => gmdate('c'),
        ]);
    }
    // Tidak pernah sampai sini: zend_bailout → shutdown handler merender halaman hasil
}

// ---- action=status ----
ensure_state_dir($stateDir);
$raw   = @file_get_contents($stateFile);
$state = is_string($raw) ? (json_decode($raw, true) ?: []) : [];
$v     = oom_state_verdict($state);

header('Content-Type: text/html; charset=utf-8');
render_page('CVE-2026-109618 — OOM Bailout State', [
    'PHP Version'  => '<code>' . htmlspecialchars(PHP_VERSION) . '</code>',
    'Status Patch' => '<span class="badge ' . ($isFixed ? 'fixed' : 'unfixed') . '">' . ($isFixed ? 'FIXED' : 'UNFIXED') . '</span>',
    'Worker PID'   => '<code>' . (int) $pid . '</code>',
    'Memory Limit' => '<code>' . htmlspecialchars((string) ini_get('memory_limit')) . '</code>',
    'State File'   => '<code>' . htmlspecialchars($stateFile) . '</code>',
    'Verdict'      => '<span class="badge ' . $v['css'] . '">' . htmlspecialchars($v['verdict']) . '</span>',
    'Detail'       => htmlspecialchars($v['detail']),
], $state, 'Isi state internal worker');
