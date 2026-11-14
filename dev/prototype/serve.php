<?php
declare(strict_types=1);

/**
 * Serves the design prototype inside the preview server (dev/preview/router.php):
 *   /__proto                 index of the screens
 *   /__proto/<screen>        a screen (views/<screen>.php)
 *   /__proto/assets/<file>   proto.css, charts.js, fonts
 * Sample data only; nothing here touches the app.
 */
$uri = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (str_starts_with($uri, '/__proto/assets/')) {
    $rel = substr($uri, strlen('/__proto/assets/'));
    $file = realpath(__DIR__ . '/public/' . $rel);
    $base = realpath(__DIR__ . '/public');
    if ($file === false || !str_starts_with($file, $base . '/') || !is_file($file)) {
        http_response_code(404);
        return;
    }
    $types = ['css' => 'text/css', 'js' => 'text/javascript', 'woff2' => 'font/woff2', 'txt' => 'text/plain'];
    header('Content-Type: ' . ($types[pathinfo($file, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
    header('Cache-Control: no-cache');
    readfile($file);
    return;
}

require_once __DIR__ . '/../preview/bootstrap.php';
require_once __DIR__ . '/Chart.php';
require_once __DIR__ . '/../preview/fixtures/_sample_stats.php';
View::reset();

$screens = [
    'login' => ['Sign in', 'Split layout: the server name as the left panel, the form on the right. Show/hide password and a caps-lock warning.'],
    'whm' => ['Admin dashboard', 'Traffic by site with the spike called out, server vitals, busiest sites, service uptime, accounts near their limits.'],
    'cpanel' => ['Customer dashboard', 'Visitors over 30 days, plan usage with a forecast, the sites with a trend each.'],
    'accounts' => ['Accounts list', 'Sorting, filters, a 14-day traffic line per row, disk meter, pagination.'],
];
$screen = trim(substr($uri, strlen('/__proto')), '/');
$screen = $screen === 'whm/analytics' ? 'whm' : $screen;

header('Content-Type: text/html; charset=utf-8');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'");

if ($screen === '' || !isset($screens[$screen])) {
    if ($screen !== '') {
        http_response_code(404);
    }
    ?><!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Prototype · JinnPanel</title>
    <link rel="stylesheet" href="/__proto/assets/proto.css"><script src="/assets/js/theme.js"></script>
    <body class="px-4 py-12"><main class="mx-auto max-w-2xl">
    <h1 class="page-title">Plan 1 v3 prototype</h1>
    <p class="page-desc">The proposed direction on four screens, with sample data. Nothing here changes the panel. Compare with the current pages in the <a class="link" href="/__preview">preview</a>.</p>
    <ul class="mt-8 divide-y divide-line rounded-[12px] border border-line bg-surface">
    <?php foreach ($screens as $k => [$name, $what]): ?>
        <li class="px-5 py-4"><a class="text-[15px] font-semibold text-fg hover:text-accent-text" href="/__proto/<?= e($k) ?>"><?= e($name) ?></a><p class="mt-0.5 text-[13px] text-muted"><?= e($what) ?></p></li>
    <?php endforeach; ?>
    </ul></main></body></html><?php
    return;
}

Auth::as($screen === 'cpanel' ? 'user' : ($screen === 'login' ? 'guest' : 'admin'));
include __DIR__ . '/views/' . $screen . '.php';
