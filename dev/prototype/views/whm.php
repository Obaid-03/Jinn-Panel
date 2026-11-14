<?php
$range = in_array($_GET['range'] ?? '', ['24h', '7d', '30d'], true) ? $_GET['range'] : '24h';
$t = stats_traffic($range);
$totals = array_map('array_sum', $t['stacks']);
$total = array_sum($totals);
$prevTotal = $total / 1.12;
$spikeLabel = $t['labels'][$t['spike']];
$spikeSite = $t['stacks'][$t['spike']][0];
$usual = array_sum(array_column($t['stacks'], 0)) / count($t['stacks']);
$rangeName = ['24h' => '24 hours', '7d' => '7 days', '30d' => '30 days'][$range];
$siteTotals = [];
foreach ($t['series'] as $k => $s) {
    if ($s['series'] !== 'so') {
        $siteTotals[] = [$s['name'], array_sum(array_column($t['stacks'], $k))];
    }
}
$siteTotals = array_merge($siteTotals, [['lumen.example', 1.4e4], ['juno.example', 9.1e3], ['harbour.example', 6.2e3]]);
usort($siteTotals, fn($a, $b) => $b[1] <=> $a[1]);

$actions = '<nav class="segmented" aria-label="Time range">';
foreach (['24h' => '24 hours', '7d' => '7 days', '30d' => '30 days'] as $k => $l) {
    $actions .= '<a href="?range=' . $k . '"' . ($k === $range ? ' aria-current="true"' : '') . '>' . $l . '</a>';
}
$actions .= '</nav><a href="/__proto/accounts" class="btn btn-primary">' . icon('user-plus') . 'Create account</a>';

$cpu = stats_series(7, 48, 0.6, 0.5);
$mem = stats_series(8, 48, 4.6, 0.08);
$bw = stats_series(9, 14, 120, 0.3, 0.4);
$services = [
    ['Web server', 'frankenphp', array_fill(0, 48, 'up'), '100%'],
    ['Mail', 'stalwart', array_merge(array_fill(0, 30, 'up'), ['slow', 'slow'], array_fill(0, 16, 'up')), '100%'],
    ['Databases', 'mariadb', array_fill(0, 48, 'up'), '100%'],
    ['DNS', 'knot', array_fill(0, 48, 'up'), '100%'],
    ['SFTP', 'sftpgo', array_merge(array_fill(0, 40, 'up'), ['down', 'down', 'down'], array_fill(0, 5, 'up')), '93.8%'],
];
ob_start();
?>
<div class="stack">
    <section class="panel" aria-labelledby="traffic-title">
        <div class="flex flex-wrap items-start justify-between gap-x-8 gap-y-3 px-5 pt-5">
            <div>
                <h2 id="traffic-title" class="text-[13px] font-medium text-muted">Requests, all sites, last <?= e($rangeName) ?></h2>
                <p class="mt-2 flex items-baseline gap-3">
                    <span class="figure"><?= e(Chart::compact($total)) ?></span>
                    <span class="text-[13px] font-medium delta-up"><?= icon('chevron-right', 'inline h-3.5 w-3.5 -rotate-90 align-[-2px]') ?>12% on the previous <?= e($rangeName) ?></span>
                </p>
            </div>
            <?= Chart::legend($t['series']) ?>
        </div>
        <div class="px-5 pb-2 pt-8">
            <?= Chart::columns($t['series'], $t['stacks'], $t['labels'], 'Requests per ' . ($range === '30d' ? 'day' : ($range === '7d' ? '4 hours' : 'hour')) . ' by site', [
                'height' => 260,
                'annotate' => [$t['spike'], 'shop.acme.test ' . number_format($spikeSite / $usual, 1) . '× usual'],
            ]) ?>
            <?= Chart::table(array_map(fn($s, $k) => ['name' => $s['name'], 'values' => array_column($t['stacks'], $k)], $t['series'], array_keys($t['series'])), $t['labels']) ?>
        </div>
        <div class="panel-row grid-cols-1 border-t border-line sm:grid-cols-2 xl:grid-cols-4">
            <div class="flex items-end justify-between gap-4 px-5 py-4">
                <div><p class="text-[13px] text-muted">CPU load</p><p class="mt-1.5 flex items-baseline gap-1.5"><span class="figure-sm">0.62</span><span class="text-[12.5px] text-subtle">of 4 cores</span></p></div>
                <div class="w-28"><?= Chart::sparkline($cpu, 'CPU load over 12 hours') ?></div>
            </div>
            <div class="flex items-end justify-between gap-4 px-5 py-4">
                <div><p class="text-[13px] text-muted">Memory</p><p class="mt-1.5 flex items-baseline gap-1.5"><span class="figure-sm">4.8 GB</span><span class="text-[12.5px] text-subtle">of 8 GB</span></p></div>
                <div class="w-28"><?= Chart::sparkline($mem, 'Memory in use over 12 hours') ?></div>
            </div>
            <div class="flex items-center justify-between gap-4 px-5 py-4">
                <div><p class="text-[13px] text-muted">Disk</p><p class="mt-1.5 flex items-baseline gap-1.5"><span class="figure-sm">82 GB</span><span class="text-[12.5px] text-subtle">of 200 GB</span></p></div>
                <?= Chart::ring(41, 'Disk used', ['size' => 52]) ?>
            </div>
            <div class="flex items-end justify-between gap-4 px-5 py-4">
                <div><p class="text-[13px] text-muted">Bandwidth this month</p><p class="mt-1.5 flex items-baseline gap-1.5"><span class="figure-sm">1.9 TB</span><span class="text-[12.5px] text-subtle">sent</span></p></div>
                <div class="w-28"><?= Chart::sparkline($bw, 'Bandwidth per day this month', ['area' => true]) ?></div>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="panel" aria-labelledby="busiest-title">
            <div class="card-header"><div><h2 id="busiest-title" class="card-title">Busiest sites</h2><p class="card-desc">Requests in the last <?= e($rangeName) ?></p></div><a href="/__proto/whm/analytics" class="btn btn-ghost btn-sm">Analytics</a></div>
            <div class="card-body"><?= Chart::ranked(array_map(fn($r) => [$r[0], (float) $r[1], Chart::compact($r[1])], array_slice($siteTotals, 0, 7)), 'Busiest sites by requests') ?></div>
        </section>

        <section class="panel" aria-labelledby="services-title">
            <div class="card-header"><div><h2 id="services-title" class="card-title">Services</h2><p class="card-desc">Checked every 30 minutes, last 24 hours</p></div></div>
            <ul class="card-body space-y-4">
                <?php foreach ($services as [$name, $unit, $checks, $up]): $bad = in_array('down', $checks, true); ?>
                <li>
                    <div class="mb-1.5 flex items-baseline justify-between gap-3 text-[13px]">
                        <span class="text-fg"><?= e($name) ?> <span class="ml-1 font-mono text-[11.5px] text-subtle"><?= e($unit) ?></span></span>
                        <span class="flex items-center gap-1.5 tabular-nums <?= $bad ? 'text-danger-text' : 'text-muted' ?>"><?= $bad ? icon('x-circle', 'h-3.5 w-3.5') : icon('check-circle', 'h-3.5 w-3.5 text-success') ?><?= e($up) ?> up</span>
                    </div>
                    <?= Chart::uptime($checks, $name . ': ' . $up . ' up in the last 24 hours' . ($bad ? ', down from 20:00 to 21:30' : '')) ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="panel" aria-labelledby="limits-title">
            <div class="card-header"><div><h2 id="limits-title" class="card-title">Nearing their limits</h2><p class="card-desc">At the current rate</p></div><a href="/__proto/accounts" class="btn btn-ghost btn-sm">Accounts</a></div>
            <ul class="card-body space-y-5">
                <?php foreach ([
                    ['bakery', 'Disk', 1105, 1024, 'Over since 9 Nov', 'danger'],
                    ['acme', 'Bandwidth', 41.2, 50, 'Runs out around 24 Nov', 'warning'],
                    ['clinicnorth', 'Disk', 4.3, 5, 'Full around 2 Dec', 'warning'],
                    ['dunmore', 'Mailboxes', 23, 25, '2 left', 'accent'],
                ] as [$u, $what, $used, $limit, $note, $tone]): $pct = min(100, $used / $limit * 100); ?>
                <li>
                    <div class="mb-1.5 flex items-baseline justify-between gap-3 text-[13px]">
                        <a href="/__proto/accounts" class="font-medium text-fg hover:text-accent-text"><?= e($u) ?></a>
                        <span class="text-muted"><?= e($what) ?> <span class="tabular-nums"><?= round($used / $limit * 100) ?>%</span></span>
                    </div>
                    <?= Ui::progress($pct, $u . ' ' . $what, $tone) ?>
                    <p class="mt-1 text-[12px] <?= $tone === 'danger' ? 'text-danger-text' : 'text-subtle' ?>"><?= e($note) ?></p>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="panel lg:col-span-2" aria-labelledby="attention-title">
            <div class="card-header"><div><h2 id="attention-title" class="card-title">Needs attention</h2></div><a href="/__proto/alerts" class="btn btn-ghost btn-sm">All alerts</a></div>
            <ul class="divide-y divide-line border-t border-line">
                <?php foreach ([
                    ['x-circle', 'text-danger', 'SFTP stopped answering for 90 minutes', 'Back since 21:30. Check: systemctl status sftpgo'],
                    ['alert', 'text-warning', 'bakery is over its disk quota', 'Uploads, new databases, mailboxes and domains are refused until it frees space.'],
                    ['alert', 'text-warning', 'Certificate for shop.acme.test expires on 20 Nov', 'Renewal normally happens 30 days ahead - the domain may no longer point here.'],
                ] as [$ic, $tone, $title, $detail]): ?>
                <li class="flex items-start gap-3 px-5 py-3.5">
                    <?= icon($ic, "mt-0.5 h-[18px] w-[18px] shrink-0 $tone") ?>
                    <div class="min-w-0 flex-1"><p class="text-[13.5px] font-medium text-fg"><?= e($title) ?></p><p class="mt-0.5 text-[13px] text-muted"><?= e($detail) ?></p></div>
                    <a href="/__proto/alerts" class="btn btn-secondary btn-sm shrink-0">Open</a>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <section class="panel" aria-labelledby="recent-title">
            <div class="card-header"><div><h2 id="recent-title" class="card-title">Recent activity</h2></div><a href="/__proto/activity" class="btn btn-ghost btn-sm">Log</a></div>
            <ol class="space-y-3.5 border-t border-line px-5 py-4">
                <?php foreach ([
                    ['2 min ago', 'acme', 'added the domain shop.acme.test'],
                    ['1 h ago', 'admin', 'suspended bakery'],
                    ['3 h ago', 'resell', 'created the account lumen'],
                    ['Yesterday', 'admin', 'changed the Business package'],
                ] as [$when, $who, $what]): ?>
                <li class="grid grid-cols-[5.5rem_1fr] gap-2 text-[13px]"><span class="text-subtle"><?= e($when) ?></span><span><span class="font-medium text-fg"><?= e($who) ?></span> <span class="text-muted"><?= e($what) ?></span></span></li>
                <?php endforeach; ?>
            </ol>
        </section>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'Overview';
$desc = 'All services are answering. 14 hosting accounts and 37 domains on server.example.com.';
$side = 'whm';
$active = '/__proto/whm';
include __DIR__ . '/_shell.php';
