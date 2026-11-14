<?php
/**
 * Shared app shell for both the server side (WHM) and the hosting account
 * side (cPanel). Expects:
 * $nav           list<array{label:?string, items:list<array{href:string,label:string,icon:string}>}>
 * $quickActions  list<array{href:string,label:string,icon:string}>  extra entries for the Ctrl-K menu
 * $roleLabel     string  shown under the user name
 * $securityHref  string  the login security page
 * $content       string  rendered page HTML
 * $title         string  page title
 * Views add to it with View::slot('actions'|'desc', ...), View::breadcrumbs(), View::script().
 */
$me = Auth::user();
$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$quickActions ??= [];

// The current section: exact match, or the longest nav link this page sits under.
$activeHref = null;
$activeGroup = null;
foreach ($nav as $group) {
    foreach ($group['items'] as $item) {
        $h = $item['href'];
        $hit = $path === $h || (substr_count($h, '/') > 1 && str_starts_with($path, $h . '/'));
        if ($hit && ($activeHref === null || strlen($h) > strlen($activeHref))) {
            $activeHref = $h;
            $activeGroup = $group['label'];
        }
    }
}

$crumbs = View::crumbs();
if ($crumbs === null) {
    $crumbs = [];
    if ($activeGroup !== null) {
        $crumbs[] = [$activeGroup];
    }
    $crumbs[] = [$title ?? ''];
}

// Ctrl-K menu: every page in the sidebar plus the quick actions.
$palette = [];
foreach ($nav as $group) {
    foreach ($group['items'] as $item) {
        $palette[] = ['label' => $item['label'], 'href' => $item['href'], 'group' => $group['label'] ?? 'Pages'];
    }
}
foreach ($quickActions as $qa) {
    $palette[] = ['label' => $qa['label'], 'href' => $qa['href'], 'group' => 'Actions'];
}
$palette[] = ['label' => 'Login security', 'href' => $securityHref, 'group' => 'Account'];

$initials = strtoupper(substr((string) ($me['username'] ?? '?'), 0, 2));
$flashes = Flash::pull();
$impersonator = $_SESSION['impersonator'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<title><?= e($title ?? '') ?> · <?= e(Config::APP_NAME) ?></title>
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<link rel="stylesheet" href="/assets/css/app.css">
<script src="/assets/js/theme.js"></script>
</head>
<body class="min-h-screen">
<a href="#main" class="skip-link">Skip to content</a>

<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-line bg-surface lg:translate-x-0" aria-label="Main">
    <div class="flex h-14 shrink-0 items-center gap-2.5 px-5">
        <a href="<?= e($nav[0]['items'][0]['href'] ?? '/') ?>" class="flex items-center gap-2.5 rounded-lg">
            <svg class="h-7 w-7" viewBox="0 0 140 140" aria-hidden="true"><defs><linearGradient id="jp-mark" x1="0" y1="0" x2="140" y2="140" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#6366f1"/><stop offset="1" stop-color="#7c3aed"/></linearGradient></defs><rect width="140" height="140" rx="32" fill="url(#jp-mark)"/><path d="M86 35v57c0 17.5-11 28.5-29.5 28.5-12 0-20.8-5-26.3-14.2l12-11c3.3 5.5 7.7 8.8 13.7 8.8 8.2 0 12.6-4.9 12.6-16.4V35H86Z" fill="#fff"/><rect x="96" y="31" width="13" height="13" rx="3" fill="#fff" fill-opacity=".55"/></svg>
            <span class="font-semibold tracking-tight text-fg"><?= e(Config::APP_NAME) ?></span>
        </a>
        <button type="button" class="btn btn-icon ml-auto lg:hidden" data-sidebar-close aria-label="Close menu"><?= icon('close', 'h-5 w-5') ?></button>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 pb-4">
        <?php foreach ($nav as $group): ?>
            <?php if (!empty($group['label'])): ?><p class="nav-section"><?= e($group['label']) ?></p><?php else: ?><div class="pt-2"></div><?php endif; ?>
            <ul class="space-y-0.5">
            <?php foreach ($group['items'] as $item): ?>
                <li><a href="<?= e($item['href']) ?>" class="nav-link"<?= $item['href'] === $activeHref ? ' aria-current="page"' : '' ?>><?= icon($item['icon']) ?><span><?= e($item['label']) ?></span></a></li>
            <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </nav>
    <div class="border-t border-line px-5 py-3 text-xs text-subtle">
        <span class="flex items-center gap-2"><?= icon('server', 'h-3.5 w-3.5') ?><span class="truncate"><?= e(Config::SERVER_HOSTNAME) ?></span></span>
    </div>
</aside>
<div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-black/40 lg:hidden" data-sidebar-close></div>

<div class="flex min-h-screen min-w-0 flex-col lg:pl-64">
    <header class="sticky top-0 z-20 flex h-14 shrink-0 items-center gap-3 border-b border-line bg-surface/85 px-4 backdrop-blur sm:px-6">
        <button type="button" class="btn btn-icon -ml-2 lg:hidden" id="sidebar-toggle" aria-controls="sidebar" aria-expanded="false" aria-label="Open menu"><?= icon('menu', 'h-5 w-5') ?></button>

        <nav aria-label="Breadcrumb" class="min-w-0 flex-1">
            <ol class="flex min-w-0 items-center gap-1.5 text-sm">
                <?php foreach ($crumbs as $i => $c): $last = $i === count($crumbs) - 1; ?>
                <li class="flex min-w-0 items-center gap-1.5<?= $last ? '' : ' hidden sm:flex' ?>">
                    <?php if ($i > 0): ?><span class="<?= $i === 1 ? 'hidden text-subtle sm:inline' : 'text-subtle' ?>" aria-hidden="true"><?= icon('chevron-right', 'h-3.5 w-3.5') ?></span><?php endif; ?>
                    <?php if (!$last && !empty($c[1])): ?>
                    <a href="<?= e($c[1]) ?>" class="truncate text-muted hover:text-fg"><?= e($c[0]) ?></a>
                    <?php else: ?>
                    <span class="truncate <?= $last ? 'font-medium text-fg' : 'text-muted' ?>"<?= $last ? ' aria-current="page"' : '' ?>><?= e($c[0]) ?></span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ol>
        </nav>

        <button type="button" class="hidden items-center gap-2 rounded-lg border border-line bg-surface-2 py-1.5 pl-2.5 pr-1.5 text-sm text-subtle transition-colors hover:border-line-strong hover:text-muted md:flex" data-palette-open>
            <?= icon('search', 'h-4 w-4') ?><span class="pr-6">Search…</span><kbd class="kbd">Ctrl K</kbd>
        </button>
        <button type="button" class="btn btn-icon md:hidden" data-palette-open aria-label="Search"><?= icon('search', 'h-5 w-5') ?></button>

        <button type="button" class="btn btn-icon" data-theme-toggle aria-label="Theme" title="Theme">
            <span data-theme-icon="light"><?= icon('sun', 'h-5 w-5') ?></span>
            <span data-theme-icon="dark" hidden><?= icon('moon', 'h-5 w-5') ?></span>
            <span data-theme-icon="system" hidden><?= icon('monitor', 'h-5 w-5') ?></span>
        </button>

        <details class="menu" data-menu>
            <summary class="flex cursor-pointer items-center gap-2 rounded-lg p-1 hover:bg-surface-3" aria-label="Account menu">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-accent-soft text-xs font-semibold text-accent-text"><?= e($initials) ?></span>
                <?= icon('chevron-down', 'hidden h-4 w-4 text-subtle sm:block') ?>
            </summary>
            <div class="menu-panel">
                <div class="px-2.5 pb-2 pt-1.5">
                    <p class="truncate text-sm font-medium text-fg"><?= e($me['username'] ?? '') ?></p>
                    <p class="truncate text-xs text-muted"><?= e($roleLabel) ?><?= !empty($me['email']) ? ' · ' . e($me['email']) : '' ?></p>
                </div>
                <div class="menu-sep"></div>
                <a href="<?= e($securityHref) ?>" class="menu-item"><?= icon('shield') ?>Login security</a>
                <div class="menu-sep"></div>
                <form method="post" action="/logout">
                    <?= Csrf::field() ?>
                    <button type="submit" class="menu-item"><?= icon('logout') ?>Sign out</button>
                </form>
            </div>
        </details>
    </header>

    <?php if ($impersonator): ?>
    <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 border-b border-warning/30 bg-warning-soft px-4 py-2 text-sm text-warning-text">
        <span><?= icon('eye', 'mr-1 inline h-4 w-4 align-[-3px]') ?>You are viewing <strong><?= e($me['username'] ?? '') ?></strong>'s panel as <strong><?= e((string) ($impersonator['username'] ?? '')) ?></strong>. Changes you make are real.</span>
        <form method="post" action="/impersonate/stop">
            <?= Csrf::field() ?>
            <button class="btn btn-secondary btn-sm"><?= icon('arrow-left') ?>Back to <?= e((string) ($impersonator['username'] ?? 'admin')) ?></button>
        </form>
    </div>
    <?php endif; ?>

    <main id="main" class="flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8" tabindex="-1">
        <div class="page">
            <?php $actions = View::slotGet('actions'); $desc = View::slotGet('desc'); ?>
            <?php if (View::slotGet('header') !== 'none'): ?>
            <div class="page-header">
                <div class="min-w-0">
                    <h1 class="page-title"><?= e($title ?? '') ?></h1>
                    <?php if ($desc !== ''): ?><p class="page-desc"><?= $desc ?></p><?php endif; ?>
                </div>
                <?php if ($actions !== ''): ?><div class="flex flex-wrap items-center gap-2"><?= $actions ?></div><?php endif; ?>
            </div>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </main>
</div>

<div id="toasts" class="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex flex-col items-end gap-2 sm:left-auto sm:right-6 sm:w-96">
    <?php foreach ($flashes as $f): $err = $f['type'] === 'error'; ?>
    <div class="toast" role="<?= $err ? 'alert' : 'status' ?>" data-toast<?= $err ? '' : ' data-autohide' ?>>
        <?= icon($err ? 'x-circle' : 'check-circle', $err ? 'text-danger' : 'text-success') ?>
        <p class="min-w-0 flex-1 break-words"><?= e($f['message']) ?></p>
        <button type="button" class="-m-1 rounded p-1 text-subtle hover:text-fg" data-toast-close aria-label="Dismiss"><?= icon('close', 'h-4 w-4') ?></button>
    </div>
    <?php endforeach; ?>
</div>

<dialog id="jp-dialog" class="dialog" aria-labelledby="jp-dialog-title">
    <form method="dialog">
        <div class="dialog-body">
            <h2 id="jp-dialog-title" class="dialog-title"></h2>
            <p class="dialog-text" data-dialog-text></p>
            <div class="mt-4" data-dialog-input hidden>
                <label for="jp-dialog-value" class="label" data-dialog-label></label>
                <input id="jp-dialog-value" class="input font-mono" autocomplete="off" spellcheck="false">
            </div>
        </div>
        <div class="dialog-footer">
            <button value="cancel" class="btn btn-secondary">Cancel</button>
            <button value="ok" class="btn btn-primary" data-dialog-ok>OK</button>
        </div>
    </form>
</dialog>

<dialog id="jp-palette" class="dialog max-w-lg self-start sm:mt-[12vh]" aria-label="Go to">
    <div class="flex items-center gap-3 border-b border-line px-4">
        <?= icon('search', 'h-4 w-4 text-subtle') ?>
        <input type="text" class="w-full bg-transparent py-3.5 text-sm text-fg placeholder:text-subtle focus:outline-none" placeholder="Go to a page or action…" aria-label="Search pages and actions" data-palette-input autocomplete="off" role="combobox" aria-expanded="true" aria-controls="jp-palette-list">
        <kbd class="kbd">Esc</kbd>
    </div>
    <ul id="jp-palette-list" class="max-h-80 overflow-y-auto p-2" role="listbox" data-palette-list></ul>
    <p class="hidden px-4 py-8 text-center text-sm text-muted" data-palette-empty>Nothing matches.</p>
</dialog>

<script type="application/json" id="jp-palette-data"><?= json_encode($palette, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="/assets/js/app.js" defer></script>
<?php foreach (View::scripts() as $s): ?>
<script src="/assets/js/pages/<?= e($s) ?>.js" defer></script>
<?php endforeach; ?>
</body>
</html>
