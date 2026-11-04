<?php
declare(strict_types=1);

$info = [
    'php_version' => PHP_VERSION,
    'php_version_id' => PHP_VERSION_ID,
    'lab_status' => getenv('LAB_PHP_STATUS') ?: 'unknown',
    'sapi' => PHP_SAPI,
    'worker_pid' => getmypid(),
    'memory_limit' => ini_get('memory_limit'),
    'upload_progress_enabled' => (bool) ini_get('session.upload_progress.enabled'),
    'timestamp' => gmdate('c'),
];

$wantsJson = (isset($_GET['format']) && $_GET['format'] === 'json')
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

if ($wantsJson) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>PHP Lab Environment — <?= htmlspecialchars($info['php_version']) ?></title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 680px; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #d0d7de; padding: 0.5rem 0.75rem; text-align: left; }
        th { background: #f6f8fa; width: 40%; }
        .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.85rem; }
        .unfixed { background: #ffebe9; color: #cf222e; }
        .fixed { background: #dafbe1; color: #1a7f37; }
    </style>
</head>
<body>
    <h1>PHP Container Info</h1>
    <table>
        <tr><th>PHP Version</th><td><code><?= htmlspecialchars($info['php_version']) ?></code></td></tr>
        <tr>
            <th>Status Patch</th>
            <td>
                <span class="badge <?= htmlspecialchars($info['lab_status']) ?>">
                    <?= htmlspecialchars(strtoupper($info['lab_status'])) ?>
                </span>
            </td>
        </tr>
        <tr><th>SAPI</th><td><code><?= htmlspecialchars($info['sapi']) ?></code></td></tr>
        <tr><th>Worker PID</th><td><code><?= (int) $info['worker_pid'] ?></code></td></tr>
        <tr><th>Memory Limit</th><td><code><?= htmlspecialchars((string) $info['memory_limit']) ?></code></td></tr>
        <tr><th>Session Upload Progress</th><td><code><?= $info['upload_progress_enabled'] ? 'On' : 'Off' ?></code></td></tr>
    </table>
    <p>Tambahkan file pengujian Anda di direktori <code>src/</code> pada host (otomatis ter-mount ke <code>/var/www/html/</code> di seluruh container).</p>
</body>
</html>
