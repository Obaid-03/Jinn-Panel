<?php
/** @var array<string,mixed> $me */
/** @var array<string,mixed>|null $stats ServerStatus::stats() (admins) */
/** @var list<array<string,mixed>> $alerts AlertService::collect() (admins) */
/** @var list<array<string,mixed>> $recent */
$admin = ($me['role'] ?? '') === 'admin';
View::slot('desc', e($admin ? 'The server at a glance: accounts, health and what changed recently.' : 'Your hosting accounts at a glance.'));
View::slot('actions', '<a href="/whm/accounts/create" class="btn btn-primary">' . icon('user-plus') . 'Create account</a>');
$levelIcon = ['danger' => ['x-circle', 'text-danger'], 'warning' => ['alert', 'text-warning'], 'info' => ['info', 'text-info']];
$fmtUptime = function (int $s): string {
    $d = intdiv($s, 86400);
    $h = intdiv($s % 86400, 3600);
    return $d > 0 ? "$d d $h h" : $h . ' h ' . intdiv($s % 3600, 60) . ' min';
};
?>
<div class="stack">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 <?= $admin ? 'xl:grid-cols-4' : 'xl:grid-cols-3' ?>">
        <?php if ($admin): ?>
        <?= Ui::stat('Resellers', (int) $resellerCount, 'users', '', '/whm/accounts') ?>
        <?php endif; ?>
        <?= Ui::stat('Hosting accounts', (int) $userCount, 'user', '', '/whm/accounts') ?>
        <?= Ui::stat('Domains hosted', (int) $domainCount, 'globe') ?>
        <?= Ui::stat('Suspended', (int) $suspendedCount, 'pause', $suspendedCount ? 'Sites offline, logins off' : 'None') ?>
    </div>

    <?php if ($admin): ?>
    <section class="card" aria-labelledby="attention-title">
        <?= Ui::cardHeader('Needs attention', '', '<a href="/whm/alerts" class="btn btn-ghost btn-sm">All alerts' . icon('chevron-right') . '</a>', id: 'attention-title') ?>
        <?php if (!$alerts): ?>
        <div class="flex items-center gap-3 px-5 py-4 text-sm">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-success-soft text-success-text"><?= icon('check', 'h-4 w-4') ?></span>
            <span><span class="font-medium text-fg">All clear.</span> <span class="text-muted">Services answer, backups ran, no account is over its limits.</span></span>
        </div>
        <?php else: ?>
        <ul class="divide-y divide-line">
            <?php foreach (array_slice($alerts, 0, 5) as $a): [$ic, $tone] = $levelIcon[$a['level']] ?? $levelIcon['info']; ?>
            <li class="flex items-start gap-3 px-5 py-3">
                <?= icon($ic, "mt-0.5 h-5 w-5 shrink-0 $tone") ?>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-fg"><?= e($a['title']) ?></p>
                    <?php if ($a['detail'] !== ''): ?><p class="mt-0.5 text-sm text-muted"><?= e($a['detail']) ?></p><?php endif; ?>
                </div>
                <?php if (!empty($a['href'])): ?><a href="<?= e($a['href']) ?>" class="btn btn-secondary btn-sm shrink-0">Open</a><?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if (count($alerts) > 5): ?>
        <div class="card-footer justify-start text-sm text-muted"><?= count($alerts) - 5 ?> more on the <a href="/whm/alerts" class="link">Alerts</a> page.</div>
        <?php endif; ?>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-2">
        <?php if ($stats): ?>
        <section class="card" aria-labelledby="server-title">
            <?= Ui::cardHeader('Server', Config::SERVER_HOSTNAME . ' · up ' . $fmtUptime((int) $stats['uptime']), id: 'server-title') ?>
            <div class="card-body space-y-5">
                <?php
                $diskPct = $stats['disk_total'] > 0 ? $stats['disk_used'] / $stats['disk_total'] * 100 : 0;
                $memPct = $stats['mem_total'] > 0 ? $stats['mem_used'] / $stats['mem_total'] * 100 : 0;
                $loadPct = $stats['load'][0] / max(1, $stats['cpus']) * 100;
                echo Ui::meter('Disk (/)', fmt_bytes((int) $stats['disk_used']) . ' of ' . fmt_bytes((int) $stats['disk_total']), $diskPct, Ui::usageTone($diskPct));
                echo Ui::meter('Memory', fmt_bytes((int) $stats['mem_used']) . ' of ' . fmt_bytes((int) $stats['mem_total']), $memPct, Ui::usageTone($memPct));
                echo Ui::meter('Load (1 min)', number_format((float) $stats['load'][0], 2) . ' on ' . $stats['cpus'] . ' CPU' . ($stats['cpus'] === 1 ? '' : 's'), $loadPct, Ui::usageTone($loadPct),
                    '5 min ' . number_format((float) $stats['load'][1], 2) . ' · 15 min ' . number_format((float) $stats['load'][2], 2));
                ?>
            </div>
        </section>

        <section class="card" aria-labelledby="services-title">
            <?= Ui::cardHeader('Services', 'Checked by connecting to each one just now.', id: 'services-title') ?>
            <ul class="divide-y divide-line">
                <?php
                $what = ['mariadb' => 'Databases', 'frankenphp' => 'Web server and panel', 'stalwart' => 'Mail', 'sftpgo' => 'SFTP', 'knot' => 'DNS'];
                foreach ($stats['services'] as $svc => $status): ?>
                <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                    <span><span class="font-medium text-fg"><?= e($what[$svc] ?? $svc) ?></span> <span class="ml-1 font-mono text-xs text-subtle"><?= e($svc) ?></span></span>
                    <?= Ui::status($status === 'active' ? 'running' : 'inactive', $status === 'active' ? 'Running' : 'Not answering') ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>

        <section class="card <?= $stats ? 'lg:col-span-2' : 'lg:col-span-2' ?>" aria-labelledby="recent-title">
            <?= Ui::cardHeader('Recent activity', '', '<a href="/whm/activity" class="btn btn-ghost btn-sm">Activity log' . icon('chevron-right') . '</a>', id: 'recent-title') ?>
            <?php if (!$recent): ?>
            <?= Ui::empty('Nothing yet', 'Sign-ins and every change made in the panel show up here.', 'activity') ?>
            <?php else: ?>
            <div class="table-wrap">
                <table class="table table-tight">
                    <thead><tr><th scope="col">When</th><th scope="col">Who</th><th scope="col">What</th><th scope="col" class="hidden md:table-cell">Detail</th></tr></thead>
                    <tbody>
                        <?php foreach ($recent as $r): ?>
                        <tr>
                            <td class="whitespace-nowrap text-muted" title="<?= e((string) $r['created_at']) ?>"><?= e(fmt_ago((string) $r['created_at'])) ?></td>
                            <td class="whitespace-nowrap"><?= e($r['actor_name'] ?? 'system') ?></td>
                            <td><span class="chip"><?= e($r['action']) ?></span><?= !empty($r['target_name']) && $r['target_name'] !== $r['actor_name'] ? ' <span class="text-muted">→ ' . e($r['target_name']) . '</span>' : '' ?></td>
                            <td class="hidden max-w-md truncate text-muted md:table-cell" title="<?= e((string) $r['detail']) ?>"><?= e((string) $r['detail']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <?php if ($logServices): ?>
    <section class="card" aria-labelledby="logs-title">
        <div class="card-header">
            <div>
                <h2 id="logs-title" class="card-title">Service logs</h2>
                <p class="card-desc">The last lines of each service's journal, refreshed by the background worker every 5 seconds.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form method="get" action="/whm">
                    <label for="log-pick" class="sr-only">Service</label>
                    <select id="log-pick" name="log" class="select select-sm w-56" data-autosubmit>
                        <?php foreach ($logServices as $svc): ?>
                        <option value="<?= e($svc) ?>" <?= $selectedLog === $svc ? 'selected' : '' ?>><?= e($svc) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <form method="post" action="/whm/logs/pull">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="service" value="<?= e($selectedLog) ?>">
                    <button class="btn btn-secondary btn-sm"><?= icon('download') ?>Last 1000 lines</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <pre class="log" tabindex="0" aria-label="Log of <?= e($selectedLog) ?>"><?= e($logContent) ?></pre>
        </div>
    </section>
    <?php endif; ?>
</div>
