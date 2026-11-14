<?php
/** @var array<string,mixed>|null $pkg null = create */
/** @var int $accounts accounts using it */
$edit = $pkg !== null;
View::breadcrumbs([['Packages', '/whm/packages'], [$edit ? (string) $pkg['name'] : 'Create package']]);
View::slot('desc', e($edit
    ? 'Changes apply to every account on this package. Lowering a limit deletes nothing; accounts over it just can\'t add more.'
    : 'A set of limits for hosting accounts. Disk and bandwidth are enforced; counts limit what can be created.'));
$v = fn(string $k, int $default) => $edit ? (string) (int) $pkg[$k] : (string) $default;
$num = fn(int $min) => ['min' => $min, 'inputmode' => 'numeric', 'class' => 'tabular-nums'];
?>
<form method="post" action="<?= $edit ? '/whm/packages/' . (int) $pkg['id'] : '/whm/packages' ?>" class="max-w-3xl">
    <?= Csrf::field() ?>
    <div class="card">
        <?php if ($edit && $accounts > 0): ?>
        <div class="card-body border-b border-line pb-4"><?= Ui::alert('Used by ' . Ui::plural($accounts, 'account') . '. Saving changes their limits right away (hard disk quotas within the hour).', 'info') ?></div>
        <?php endif; ?>
        <div class="card-body space-y-6">
            <?= Ui::field('name', 'Package name', ['required' => true, 'value' => $edit ? (string) $pkg['name'] : null, 'placeholder' => 'Business', 'wrap' => 'max-w-sm']) ?>

            <div>
                <h2 class="section-title mb-3">Resources</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <?= Ui::field('disk_quota_mb', 'Disk space', ['type' => 'number', 'value' => $v('disk_quota_mb', 1024), 'suffix' => 'MB', 'hint' => 'Files, databases and mail together. 1024 MB = 1 GB.', 'attrs' => $num(1)]) ?>
                    <?= Ui::field('bandwidth_mb', 'Bandwidth per month', ['type' => 'number', 'value' => $v('bandwidth_mb', 10240), 'suffix' => 'MB', 'hint' => 'Over it, the account\'s sites answer 509 until next month.', 'attrs' => $num(1)]) ?>
                </div>
            </div>

            <div>
                <h2 class="section-title mb-3">Limits</h2>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <?= Ui::field('max_domains', 'Domains', ['type' => 'number', 'value' => $v('max_domains', 1), 'attrs' => $num(1)]) ?>
                    <?= Ui::field('max_databases', 'Databases', ['type' => 'number', 'value' => $v('max_databases', 1), 'attrs' => $num(1)]) ?>
                    <?= Ui::field('max_email_accounts', 'Mailboxes', ['type' => 'number', 'value' => $v('max_email_accounts', 5), 'attrs' => $num(0)]) ?>
                    <?= Ui::field('max_ftp_accounts', 'SFTP logins', ['type' => 'number', 'value' => $v('max_ftp_accounts', 1), 'attrs' => $num(0)]) ?>
                </div>
            </div>

            <fieldset>
                <legend class="section-title float-left mb-1 w-full">Features</legend>
                <p class="mb-4 text-sm text-muted">What accounts on this package may use. Each account can be switched differently on its own page in Accounts.</p>
                <?php $pf = Features::forPackage($pkg); ?>
                <div class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    <?php foreach (Features::LIST as $key => [$label, $what]): ?>
                    <?= Ui::checkbox('features[' . $key . ']', $label, $pf[$key], ['hint' => $what]) ?>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <?php if (Auth::isAdmin()): ?>
            <div>
                <h2 class="section-title mb-3">Resellers</h2>
                <?= Ui::field('max_accounts', 'Accounts a reseller on this package may create', ['type' => 'number', 'value' => $v('max_accounts', 0), 'hint' => '0 = none. Only matters when the package is given to a reseller.', 'wrap' => 'max-w-sm', 'attrs' => $num(0)]) ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="/whm/packages" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary"><?= icon($edit ? 'save' : 'plus') ?><?= $edit ? 'Save package' : 'Create package' ?></button>
        </div>
    </div>
</form>
