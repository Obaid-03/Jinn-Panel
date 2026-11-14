<?php
$rows = stats_accounts();
$sort = in_array($_GET['sort'] ?? '', ['name', 'disk', 'traffic', 'created'], true) ? $_GET['sort'] : 'traffic';
usort($rows, fn($a, $b) => match ($sort) {
    'name' => strcmp($a['username'], $b['username']),
    'disk' => ($b['disk_limit_mb'] > 0 ? $b['disk_used_mb'] / $b['disk_limit_mb'] : 0) <=> ($a['disk_limit_mb'] > 0 ? $a['disk_used_mb'] / $a['disk_limit_mb'] : 0),
    'created' => strcmp($b['created'], $a['created']),
    default => array_sum($b['traffic']) <=> array_sum($a['traffic']),
});
$th = function (string $key, string $label, string $cls = '') use ($sort): string {
    $on = $sort === $key;
    return '<th scope="col" class="' . $cls . '"' . ($on ? ' aria-sort="' . ($key === 'name' ? 'ascending' : 'descending') . '"' : '') . '>'
        . '<a href="?sort=' . $key . '" class="th-sort">' . e($label)
        . ($on ? icon('chevron-down', 'h-3.5 w-3.5' . ($key === 'name' ? ' rotate-180' : '')) : '') . '</a></th>';
};
$actions = '<a href="#" class="btn btn-primary">' . icon('user-plus') . 'Create account</a>';
ob_start();
?>
<section class="panel">
    <form class="flex flex-wrap items-center gap-3 px-5 py-4" role="search" aria-label="Filter accounts">
        <label class="relative min-w-[14rem] flex-1">
            <span class="sr-only">Search accounts</span>
            <?= icon('search', 'pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-subtle') ?>
            <input type="search" class="input input-sm h-9 pl-9" placeholder="Name, email or domain">
        </label>
        <nav class="segmented" aria-label="Status">
            <a href="#" aria-current="true">All <span class="text-subtle">12</span></a><a href="#">Active <span class="text-subtle">11</span></a><a href="#">Suspended <span class="text-subtle">1</span></a><a href="#">Over limits <span class="text-subtle">1</span></a>
        </nav>
        <label class="sr-only" for="f-pkg">Package</label>
        <select id="f-pkg" class="select select-sm h-9 w-auto"><option>All packages</option><option>Starter</option><option>Business</option><option>Unlimited</option></select>
        <label class="sr-only" for="f-res">Reseller</label>
        <select id="f-res" class="select select-sm h-9 w-auto"><option>Any reseller</option><option>resell</option><option>No reseller</option></select>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col" class="hidden w-10 sm:table-cell"><input type="checkbox" aria-label="Select all accounts on this page"></th>
                    <?= $th('name', 'Account') ?>
                    <th scope="col" class="hidden lg:table-cell">Package</th>
                    <?= $th('disk', 'Disk', 'hidden sm:table-cell') ?>
                    <?= $th('traffic', 'Visitors, 14 days', 'hidden md:table-cell') ?>
                    <th scope="col" class="hidden text-right xl:table-cell">Domains</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="hidden sm:table-cell"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $a):
                $limit = (int) $a['disk_limit_mb'];
                $pct = $limit > 0 ? $a['disk_used_mb'] / $limit * 100 : 0;
                $tone = $pct >= 100 ? 'danger' : ($pct >= 90 ? 'warning' : 'accent'); ?>
            <tr>
                <td class="hidden sm:table-cell"><input type="checkbox" aria-label="Select <?= e($a['username']) ?>"></td>
                <td class="max-w-[16rem]">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-surface-3 text-[12px] font-semibold uppercase text-muted" aria-hidden="true"><?= e(substr($a['username'], 0, 2)) ?></span>
                        <div class="min-w-0">
                            <a href="#" class="block truncate font-medium text-fg hover:text-accent-text"><?= e($a['username']) ?></a>
                            <p class="truncate text-[12.5px] text-muted"><?= e($a['full_name']) ?><?= $a['reseller'] ? ', via ' . e($a['reseller']) : '' ?></p>
                        </div>
                    </div>
                </td>
                <td class="hidden text-muted lg:table-cell"><?= e($a['package']) ?></td>
                <td class="hidden min-w-[9rem] sm:table-cell">
                    <div class="mb-1 flex items-baseline justify-between gap-2 text-[12.5px]"><span class="text-fg"><?= e(Chart::bytes($a['disk_used_mb'] * 1048576)) ?></span><span class="text-subtle"><?= $limit > 0 ? round($pct) . '%' : 'no limit' ?></span></div>
                    <?= $limit > 0 ? Ui::progress($pct, 'Disk of ' . $a['username'], $tone) : '<div class="progress" aria-hidden="true"></div>' ?>
                </td>
                <td class="hidden md:table-cell">
                    <div class="flex items-center gap-3"><div class="w-28"><?= Chart::sparkline($a['traffic'], 'Visitors per day to ' . $a['username'] . "'s sites") ?></div><span class="text-[12.5px] text-muted"><?= e(Chart::compact(array_sum($a['traffic']))) ?></span></div>
                </td>
                <td class="hidden text-right xl:table-cell"><?= (int) $a['domains'] ?></td>
                <td><?= $a['status'] === 'active' ? ($pct >= 100 ? Ui::badge('Over disk', 'danger', true) : Ui::badge('Active', 'success', true)) : Ui::badge('Suspended', 'warning', true) ?></td>
                <td class="hidden text-right sm:table-cell"><a href="#" class="btn btn-ghost btn-sm">Manage</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3 text-[13px] text-muted">
        <span>Showing <span class="tabular-nums text-fg">1–12</span> of <span class="tabular-nums text-fg">42</span> accounts</span>
        <nav class="flex items-center gap-1" aria-label="Pages">
            <a href="#" class="btn btn-ghost btn-sm" aria-disabled="true">Previous</a>
            <a href="#" class="btn btn-secondary btn-sm" aria-current="page">1</a><a href="#" class="btn btn-ghost btn-sm">2</a><a href="#" class="btn btn-ghost btn-sm">3</a><a href="#" class="btn btn-ghost btn-sm">4</a>
            <a href="#" class="btn btn-ghost btn-sm">Next</a>
        </nav>
    </div>
</section>
<?php
$content = ob_get_clean();
$title = 'Accounts';
$desc = '42 accounts: 38 hosting, 3 resellers, 1 administrator.';
$side = 'whm';
$active = '/__proto/accounts';
include __DIR__ . '/_shell.php';
