<?php
/** @var list<array<string,mixed>> $accounts with package_name, reseller_username, used_bytes, over_disk, over_bandwidth, domain_count */
/** @var array<string,mixed> $me */
/** @var list<array<string,mixed>> $packages packages the viewer may give */
$admin = $me['role'] === 'admin';
$packages ??= [];
View::script('accounts');
$selectable = count(array_filter($accounts, fn($a) => (int) $a['id'] !== (int) $me['id']));
View::slot('desc', e($admin ? 'Every login on this server: administrators, resellers and hosting accounts.' : 'The hosting accounts you created.'));
View::slot('actions', '<a href="/whm/accounts/create" class="btn btn-primary">' . icon('user-plus') . 'Create account</a>');
$roleBadge = fn(string $r) => match ($r) {
    'admin' => Ui::badge('Admin', 'accent'),
    'reseller' => Ui::badge('Reseller', 'info'),
    default => '',
};
?>
<?php if ($selectable): ?>
<form method="post" action="/whm/accounts/bulk" id="bulk-form" class="card mb-4 flex flex-wrap items-end gap-3 p-4" data-bulk data-confirm="Apply this to the selected accounts?" data-confirm-action="Apply">
    <?= Csrf::field() ?>
    <p class="w-full text-sm text-muted sm:w-auto sm:self-center"><span class="font-medium text-fg" data-bulk-count>0</span> selected</p>
    <?= Ui::select('op', 'With the selected', ['' => 'Choose an action…', 'package' => 'Change package', 'suspend' => 'Suspend', 'unsuspend' => 'Unsuspend', 'feature' => 'Switch a feature'], '', ['class' => 'select-sm', 'attrs' => ['data-bulk-op' => true]]) ?>
    <div data-bulk-for="package">
        <?php
        $po = [];
        foreach ($packages as $p) {
            $po[(string) $p['id']] = $p['name'];
        }
        echo Ui::select('package_id', 'Package', $po, null, ['class' => 'select-sm']);
        ?>
    </div>
    <div class="flex flex-wrap items-end gap-3" data-bulk-for="feature">
        <?php
        $fo = [];
        foreach (Features::LIST as $k => [$label]) {
            $fo[$k] = $label;
        }
        echo Ui::select('feature', 'Feature', $fo, null, ['class' => 'select-sm']);
        echo Ui::select('feature_value', 'Set to', ['1' => 'On', '0' => 'Off', '' => 'As the package'], '1', ['class' => 'select-sm']);
        ?>
    </div>
    <button type="submit" class="btn btn-primary btn-sm" data-bulk-apply>Apply</button>
</form>
<?php endif; ?>

<section class="card" aria-labelledby="accounts-title">
    <div class="card-header">
        <h2 id="accounts-title" class="card-title"><?= e(Ui::plural(count($accounts), 'account')) ?></h2>
        <?php if (count($accounts) > 6): ?>
        <input type="search" class="input input-sm w-full sm:w-64" placeholder="Filter by name, email, package…" aria-label="Filter accounts" data-filter="#accounts-table">
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table class="table" id="accounts-table">
            <thead>
                <tr>
                    <?php if ($selectable): ?><th scope="col" class="w-10"><input type="checkbox" data-bulk-all aria-label="Select all accounts"></th><?php endif; ?>
                    <th scope="col">Account</th>
                    <th scope="col" class="hidden md:table-cell">Package</th>
                    <?php if ($admin): ?><th scope="col" class="hidden lg:table-cell">Reseller</th><?php endif; ?>
                    <th scope="col" class="hidden sm:table-cell">Usage</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-right"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $a): $id = (int) $a['id']; $self = $id === (int) $me['id']; ?>
                <tr>
                    <?php if ($selectable): ?>
                    <td><?php if (!$self): ?><input type="checkbox" name="ids[]" value="<?= $id ?>" form="bulk-form" data-bulk-item aria-label="Select <?= e($a['username']) ?>"><?php endif; ?></td>
                    <?php endif; ?>
                    <td class="max-w-[18rem]">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-surface-3 text-xs font-semibold uppercase text-muted" aria-hidden="true"><?= e(substr((string) $a['username'], 0, 2)) ?></span>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <?php if ($self): ?>
                                    <span class="truncate font-medium text-fg"><?= e($a['username']) ?></span> <?= Ui::badge('You', 'neutral') ?>
                                    <?php else: ?>
                                    <a href="/whm/accounts/<?= $id ?>" class="truncate font-medium text-fg hover:text-accent-text"><?= e($a['username']) ?></a>
                                    <?php endif; ?>
                                    <?= $roleBadge((string) $a['role']) ?>
                                </div>
                                <p class="truncate text-xs text-muted"><?= e($a['email']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="hidden text-muted md:table-cell"><?= e($a['package_name'] ?? '—') ?></td>
                    <?php if ($admin): ?><td class="hidden text-muted lg:table-cell"><?= e($a['reseller_username'] ?? '—') ?></td><?php endif; ?>
                    <td class="hidden whitespace-nowrap sm:table-cell">
                        <?php if ($a['role'] === 'user'): ?>
                        <span class="tabular-nums text-fg"><?= e(fmt_bytes((int) ($a['used_bytes'] ?? 0))) ?></span>
                        <span class="text-muted">· <?= e(Ui::plural((int) $a['domain_count'], 'domain')) ?></span>
                        <?php if (!empty($a['over_disk']) || !empty($a['over_bandwidth'])): ?>
                        <div class="mt-1"><?= Ui::badge(!empty($a['over_disk']) ? 'Over disk quota' : 'Bandwidth used up', 'danger') ?></div>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="text-subtle">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= Ui::status((string) $a['status']) ?></td>
                    <td>
                        <?php if (!$self): ?>
                        <div class="flex items-center justify-end gap-1">
                            <?php if ($a['role'] === 'user' && $a['status'] === 'active'): ?>
                            <?= Ui::actionForm("/whm/accounts/$id/login-as", 'login', 'Log in as ' . $a['username']) ?>
                            <?php endif; ?>
                            <a href="/whm/accounts/<?= $id ?>" class="btn btn-secondary btn-sm">Manage</a>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$accounts): ?>
                <?= Ui::emptyRow(($admin ? 6 : 5) + ($selectable ? 1 : 0), 'No accounts yet', 'Create a hosting account for a customer, or a reseller who creates their own.', 'users', '<a href="/whm/accounts/create" class="btn btn-primary">' . icon('user-plus') . 'Create account</a>') ?>
                <?php else: ?>
                <tr data-filter-none data-filter-skip hidden><td colspan="<?= ($admin ? 6 : 5) + ($selectable ? 1 : 0) ?>" class="py-8 text-center text-sm text-muted">No account matches that filter.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
