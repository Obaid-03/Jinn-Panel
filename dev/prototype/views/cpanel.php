<?php
$days = [];
for ($i = 29; $i >= 0; $i--) {
    $days[] = date('j M', strtotime('2026-11-14') - $i * 86400);
}
$visitors = stats_series(31, 30, 560, 0.28, 0.35);
$total = array_sum($visitors);
$sites = [
    ['acme.test', 1, 'ok', 'Let\'s Encrypt', stats_series(41, 14, 420, 0.3, 0.3), '12.4K', '248K'],
    ['shop.acme.test', 2, 'ok', 'Let\'s Encrypt', stats_series(42, 14, 160, 0.45, 0.9), '5.1K', '142K'],
    ['acme-staging.example', 3, 'warn', 'DNS not pointing here', stats_series(43, 14, 8, 0.6), '214', '3.9K'],
];
$actions = '<nav class="segmented" aria-label="Time range"><a href="#">7 days</a><a href="#" aria-current="true">30 days</a><a href="#">90 days</a></nav>'
    . '<a href="#" class="btn btn-primary">' . icon('plus') . 'Add domain</a>';
ob_start();
?>
<div class="stack">
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <section class="panel xl:col-span-2" aria-labelledby="visitors-title">
            <div class="flex flex-wrap items-start justify-between gap-x-8 gap-y-4 px-5 pt-5">
                <div>
                    <h2 id="visitors-title" class="text-[13px] font-medium text-muted">Visitors to your sites, last 30 days</h2>
                    <p class="mt-2 flex items-baseline gap-3">
                        <span class="figure"><?= e(Chart::compact($total)) ?></span>
                        <span class="text-[13px] font-medium delta-up"><?= icon('chevron-right', 'inline h-3.5 w-3.5 -rotate-90 align-[-2px]') ?>8% on the 30 days before</span>
                    </p>
                </div>
                <dl class="flex gap-8 text-[13px]">
                    <div><dt class="text-muted">Requests</dt><dd class="figure-sm mt-1.5">394K</dd></div>
                    <div><dt class="text-muted">Data sent</dt><dd class="figure-sm mt-1.5">38.4 GB</dd></div>
                    <div><dt class="text-muted">Errors</dt><dd class="figure-sm mt-1.5">0.4%</dd></div>
                </dl>
            </div>
            <div class="px-5 pb-4 pt-8">
                <?= Chart::line([['name' => 'Visitors', 'values' => $visitors, 'series' => 's1']], $days, 'Visitors per day, last 30 days', ['height' => 240, 'area' => true, 'xEvery' => 7]) ?>
                <?= Chart::table([['name' => 'Visitors', 'values' => $visitors]], $days, ['first' => 'Day']) ?>
            </div>
        </section>

        <section class="panel" aria-labelledby="plan-title">
            <div class="card-header"><div><h2 id="plan-title" class="card-title">Your plan</h2><p class="card-desc">Business, measured an hour ago</p></div></div>
            <div class="space-y-6 px-5 pb-5">
                <div class="flex items-center gap-4">
                    <?= Chart::ring(68, 'Disk used', ['size' => 76, 'center' => '68%']) ?>
                    <div class="min-w-0 text-[13px]">
                        <p class="font-medium text-fg">Disk: 3.4 GB of 5 GB</p>
                        <p class="text-muted">Files 2.1 GB, databases 0.6 GB, mail 0.7 GB</p>
                        <p class="mt-1 flex items-center gap-1.5 text-warning-text"><?= icon('clock', 'h-3.5 w-3.5') ?>At this rate, full around 24 Dec</p>
                    </div>
                </div>
                <div>
                    <div class="mb-1.5 flex items-baseline justify-between text-[13px]"><span class="text-muted">Bandwidth this month</span><span class="font-medium tabular-nums text-fg">41 GB of 50 GB</span></div>
                    <?= Ui::progress(82, 'Bandwidth this month', 'warning') ?>
                    <p class="mt-1.5 text-[12px] text-subtle">Runs out around 26 Nov; your sites would pause until 1 Dec.</p>
                </div>
                <ul class="grid grid-cols-2 gap-x-6 gap-y-4 border-t border-line pt-5">
                    <?php foreach ([['Domains', 3, 5], ['Databases', 2, 10], ['Mailboxes', 7, 25], ['SFTP logins', 1, 3]] as [$l, $u, $m]): ?>
                    <li><div class="mb-1.5 flex items-baseline justify-between text-[13px]"><span class="text-muted"><?= e($l) ?></span><span class="tabular-nums text-fg"><?= $u ?> / <?= $m ?></span></div><?= Ui::progress($u / $m * 100, $l) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    </div>

    <section class="panel" aria-labelledby="sites-title">
        <div class="card-header"><div><h2 id="sites-title" class="card-title">Your sites</h2><p class="card-desc">Visitors per day over the last 14 days</p></div><a href="#" class="btn btn-ghost btn-sm">All domains</a></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th scope="col">Site</th><th scope="col" class="w-48">Last 14 days</th><th scope="col" class="hidden text-right md:table-cell">Visitors</th><th scope="col" class="hidden text-right md:table-cell">Requests</th><th scope="col" class="hidden sm:table-cell">Certificate</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                <?php foreach ($sites as [$name, $id, $st, $ssl, $trend, $vis, $req]): ?>
                <tr>
                    <td>
                        <a href="#" class="font-medium text-fg hover:text-accent-text"><?= e($name) ?></a>
                        <p class="font-mono text-[12px] text-subtle">/var/www/<?= e($name) ?>/public</p>
                    </td>
                    <td><div class="w-40"><?= Chart::sparkline($trend, 'Visitors per day to ' . $name, ['area' => true]) ?></div></td>
                    <td class="hidden text-right md:table-cell"><?= e($vis) ?></td>
                    <td class="hidden text-right text-muted md:table-cell"><?= e($req) ?></td>
                    <td class="hidden sm:table-cell"><?= Ui::badge($ssl, $st === 'ok' ? 'success' : 'warning', true) ?></td>
                    <td class="text-right"><a href="#" class="btn btn-secondary btn-sm">Stats</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php
$content = ob_get_clean();
$title = 'Good afternoon, Acme Ltd';
$desc = 'Three sites on the Business plan. Your control panel is also at acme.test/jpanel.';
$side = 'cpanel';
$active = '/__proto/cpanel';
include __DIR__ . '/_shell.php';
