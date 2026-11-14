<?php
/** @var array<string,mixed> $me */
/** @var array<string,mixed>|null $pkg */
/** @var array<string,int> $usage */
/** @var list<array<string,mixed>> $domains up to 6, newest first, with ['ssl'] */
$name = trim((string) ($me['full_name'] ?? '')) ?: (string) $me['username'];
View::slot('desc', e('Welcome back, ' . $name . '. Here is your hosting at a glance.'));
View::slot('actions', '<a href="/cpanel/domains#add-domain" class="btn btn-primary">' . icon('plus') . 'Add domain</a>');
$sslTone = fn(string $st) => $st === 'ok' ? 'success' : ($st === 'no_dns' ? 'danger' : 'warning');
$can = Features::current();
$tiles = [
    ['/cpanel/email', 'mail', 'Email accounts', Ui::plural((int) $usage['email_accounts'], 'mailbox', 'mailboxes')],
    ['/cpanel/databases', 'database', 'MySQL databases', Ui::plural((int) $usage['databases'], 'database')],
    ['/cpanel/files', 'folder', 'File Manager', 'Upload, edit, zip and unzip'],
    ['/cpanel/ftp', 'folder-up', 'FTP / SFTP', Ui::plural((int) $usage['ftp_accounts'], 'login')],
    ['/cpanel/dns', 'server', 'DNS zones', 'Records for your domains'],
    ['/cpanel/cron', 'clock', 'Cron jobs', 'Scheduled scripts and URLs'],
    ['/cpanel/cache', 'bolt', 'Cache', 'Page and object cache'],
    ['/cpanel/backups', 'archive', 'Backups', 'Download and restore'],
];
$tileFeature = ['/cpanel/files' => 'file_manager', '/cpanel/cron' => 'cron', '/cpanel/backups' => 'backups'];
$tiles = array_values(array_filter($tiles, fn($t) => !isset($tileFeature[$t[0]]) || $can[$tileFeature[$t[0]]]));
?>
<div class="stack">
    <?php include __DIR__ . '/../partials/quota_bars.php'; ?>

    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2" aria-labelledby="sites-title">
            <?= Ui::cardHeader('Your sites', '', '<a href="/cpanel/domains" class="btn btn-ghost btn-sm">All domains' . icon('chevron-right') . '</a>', id: 'sites-title') ?>
            <?php if (!$domains): ?>
            <?= Ui::empty('No websites yet', 'Add a domain to get a website, a DNS zone and a certificate. Point the domain at this server and it goes live.', 'globe', '<a href="/cpanel/domains#add-domain" class="btn btn-primary">' . icon('plus') . 'Add your first domain</a>') ?>
            <?php else: ?>
            <ul class="divide-y divide-line">
                <?php foreach ($domains as $d): ?>
                <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3">
                    <div class="min-w-0 flex-1">
                        <a href="/cpanel/domains/<?= (int) $d['id'] ?>" class="font-medium text-fg hover:text-accent-text"><?= e($d['domain_name']) ?></a>
                        <p class="text-xs text-muted"><?= e($d['php_version'] === 'default' ? 'PHP default' : 'PHP ' . $d['php_version']) ?> · panel at <span class="font-mono"><?= e($d['domain_name']) ?>/jpanel</span></p>
                    </div>
                    <span title="<?= e($d['ssl']['detail']) ?>"><?= Ui::badge((string) $d['ssl']['label'], $sslTone((string) $d['ssl']['state']), true) ?></span>
                    <a href="https://<?= e($d['domain_name']) ?>/" target="_blank" rel="noopener" class="btn btn-ghost btn-sm"><?= icon('external') ?>Visit</a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </section>

        <section class="card" aria-labelledby="help-title">
            <?= Ui::cardHeader('Connection details', id: 'help-title') ?>
            <dl class="dl card-body">
                <div><dt>Server</dt><dd><?= Ui::copyable(Config::SERVER_HOSTNAME) ?></dd></div>
                <div><dt>IP address</dt><dd><?= Ui::copyable(Config::SERVER_IP) ?></dd></div>
                <div><dt>SFTP</dt><dd><code><?= e(Config::SERVER_HOSTNAME) ?></code>, port <code><?= (int) Config::SFTP_PUBLIC_PORT ?></code></dd></div>
                <div><dt>Your username</dt><dd><code><?= e($me['username']) ?></code></dd></div>
            </dl>
        </section>
    </div>

    <nav aria-label="Tools" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($tiles as [$href, $ic, $label, $sub]): ?>
        <a href="<?= e($href) ?>" class="card card-link flex items-center gap-4 p-4">
            <span class="stat-icon"><?= icon($ic) ?></span>
            <span class="min-w-0"><span class="block font-medium text-fg"><?= e($label) ?></span><span class="block truncate text-sm text-muted"><?= e($sub) ?></span></span>
        </a>
        <?php endforeach; ?>
    </nav>
</div>
