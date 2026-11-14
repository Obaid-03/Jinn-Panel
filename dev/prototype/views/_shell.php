<?php
/**
 * Prototype app shell. Expects: $title, $side ('whm'|'cpanel'), $active (href),
 * $content (html), $actions (html, optional), $desc (text, optional).
 */
$nav = $side === 'whm' ? [
    [null, [['/__proto/whm', 'Dashboard', 'grid'], ['/__proto/whm/analytics', 'Analytics', 'chart']]],
    ['Accounts', [['/__proto/accounts', 'Accounts', 'users'], ['/__proto/packages', 'Packages', 'box'], ['/__proto/migrations', 'cPanel migration', 'transfer'], ['/__proto/activity', 'Activity log', 'activity']]],
    ['Server', [['/__proto/alerts', 'Alerts', 'bell'], ['/__proto/backups', 'Backups', 'archive'], ['/__proto/dns', 'DNS zones', 'globe'], ['/__proto/notifications', 'Notifications', 'mail']]],
    ['Configuration', [['/__proto/mail', 'Mail', 'mail'], ['/__proto/php', 'PHP', 'code'], ['/__proto/database', 'Database', 'database'], ['/__proto/tuning', 'Server tweaks', 'sliders']]],
] : [
    [null, [['/__proto/cpanel', 'Dashboard', 'grid']]],
    ['Websites', [['/__proto/cpanel/domains', 'Domains', 'globe'], ['/__proto/cpanel/files', 'Files', 'folder'], ['/__proto/cpanel/dns', 'DNS', 'server'], ['/__proto/cpanel/cache', 'Cache', 'bolt'], ['/__proto/cpanel/cron', 'Cron jobs', 'clock']]],
    ['Email and data', [['/__proto/cpanel/email', 'Email', 'mail'], ['/__proto/cpanel/databases', 'Databases', 'database'], ['/__proto/cpanel/ftp', 'SFTP', 'folder-up'], ['/__proto/cpanel/backups', 'Backups', 'archive']]],
];
$who = $side === 'whm' ? ['AD', 'admin', 'Administrator'] : ['AC', 'Acme Ltd', 'acme'];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<title><?= e($title) ?> · JinnPanel prototype</title>
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<link rel="stylesheet" href="/__proto/assets/proto.css">
<script src="/assets/js/theme.js"></script>
</head>
<body class="min-h-screen">
<a href="#main" class="skip-link">Skip to content</a>
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-60 -translate-x-full flex-col bg-canvas lg:translate-x-0" aria-label="Main">
    <div class="flex h-16 shrink-0 items-center gap-2.5 px-5">
        <svg class="h-7 w-7 shrink-0" viewBox="0 0 140 140" aria-hidden="true"><defs><linearGradient id="jp-m" x1="0" y1="0" x2="140" y2="140" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#6366f1"/><stop offset="1" stop-color="#7c3aed"/></linearGradient></defs><rect width="140" height="140" rx="32" fill="url(#jp-m)"/><path d="M86 35v57c0 17.5-11 28.5-29.5 28.5-12 0-20.8-5-26.3-14.2l12-11c3.3 5.5 7.7 8.8 13.7 8.8 8.2 0 12.6-4.9 12.6-16.4V35H86Z" fill="#fff"/><rect x="96" y="31" width="13" height="13" rx="3" fill="#fff" fill-opacity=".55"/></svg>
        <div class="min-w-0 leading-tight">
            <p class="text-[14px] font-semibold text-fg">JinnPanel</p>
            <p class="truncate font-mono text-[11.5px] text-subtle">server.example.com</p>
        </div>
        <button type="button" class="btn btn-icon ml-auto lg:hidden" data-sidebar-close aria-label="Close menu"><?= icon('close') ?></button>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 pb-6">
        <?php foreach ($nav as [$section, $items]): ?>
        <?php if ($section): ?><p class="nav-section"><?= e($section) ?></p><?php else: ?><div class="pt-1"></div><?php endif; ?>
        <ul class="space-y-0.5">
            <?php foreach ($items as [$href, $label, $ic]): ?>
            <li><a href="<?= e($href) ?>" class="nav-link"<?= $href === $active ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e($label) ?></span></a></li>
            <?php endforeach; ?>
        </ul>
        <?php endforeach; ?>
    </nav>
</aside>
<div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-black/40 lg:hidden" data-sidebar-close></div>

<div class="flex min-h-screen min-w-0 flex-col lg:pl-60">
    <header class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 bg-canvas/90 px-4 backdrop-blur sm:px-6 lg:pl-2 lg:pr-8">
        <button type="button" class="btn btn-icon -ml-1 lg:hidden" id="sidebar-toggle" aria-controls="sidebar" aria-expanded="false" aria-label="Open menu"><?= icon('menu') ?></button>
        <button type="button" class="flex h-9 min-w-0 max-w-md flex-1 items-center gap-2 rounded-[9px] border border-line bg-surface px-3 text-left text-[13.5px] text-subtle transition-colors hover:border-line-strong" data-palette-open>
            <?= icon('search', 'h-4 w-4 shrink-0') ?><span class="truncate">Find an account, domain or page</span><kbd class="kbd ml-auto hidden sm:inline-flex">Ctrl K</kbd>
        </button>
        <div class="ml-auto flex items-center gap-1">
            <a href="/__proto/alerts" class="btn btn-icon relative" aria-label="Alerts: 3 need attention" title="3 alerts need attention">
                <?= icon('bell') ?><span class="absolute right-1 top-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-danger px-1 text-[10.5px] font-semibold leading-none text-white">3</span>
            </a>
            <button type="button" class="btn btn-icon" data-theme-toggle aria-label="Theme">
                <span data-theme-icon="light"><?= icon('sun') ?></span><span data-theme-icon="dark" hidden><?= icon('moon') ?></span><span data-theme-icon="system" hidden><?= icon('monitor') ?></span>
            </button>
            <button type="button" class="ml-1 flex items-center gap-2 rounded-[9px] p-1 pr-2 hover:bg-surface-3" aria-label="Account menu">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-accent text-[12px] font-semibold text-accent-fg"><?= e($who[0]) ?></span>
                <span class="hidden text-left leading-tight md:block"><span class="block text-[13px] font-medium text-fg"><?= e($who[1]) ?></span><span class="block text-[11.5px] text-subtle"><?= e($who[2]) ?></span></span>
            </button>
        </div>
    </header>

    <main id="main" class="flex-1 px-4 pb-12 pt-2 sm:px-6 lg:pl-2 lg:pr-8" tabindex="-1">
        <div class="page">
            <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="page-title"><?= e($title) ?></h1>
                    <?php if (!empty($desc)): ?><p class="page-desc"><?= e($desc) ?></p><?php endif; ?>
                </div>
                <?php if (!empty($actions)): ?><div class="flex flex-wrap items-center gap-2"><?= $actions ?></div><?php endif; ?>
            </div>
            <?= $content ?>
        </div>
    </main>
</div>
<script src="/assets/js/app.js" defer></script>
<script src="/assets/js/pages/charts.js" defer></script>
</body>
</html>
