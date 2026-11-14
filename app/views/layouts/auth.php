<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<title><?= e($title ?? 'Sign in') ?> · <?= e(Config::APP_NAME) ?></title>
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<link rel="stylesheet" href="/assets/css/app.css">
<script src="/assets/js/theme.js"></script>
</head>
<body class="min-h-screen">
<main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-12">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-gradient-to-b from-accent/10 to-transparent" aria-hidden="true"></div>
    <div class="relative w-full <?= View::slotGet('wide') !== '' ? 'max-w-xl' : 'max-w-sm' ?>">
        <div class="mb-8 flex items-center justify-center gap-2.5">
            <svg class="h-9 w-9" viewBox="0 0 140 140" aria-hidden="true"><defs><linearGradient id="jp-mark" x1="0" y1="0" x2="140" y2="140" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#6366f1"/><stop offset="1" stop-color="#7c3aed"/></linearGradient></defs><rect width="140" height="140" rx="32" fill="url(#jp-mark)"/><path d="M86 35v57c0 17.5-11 28.5-29.5 28.5-12 0-20.8-5-26.3-14.2l12-11c3.3 5.5 7.7 8.8 13.7 8.8 8.2 0 12.6-4.9 12.6-16.4V35H86Z" fill="#fff"/><rect x="96" y="31" width="13" height="13" rx="3" fill="#fff" fill-opacity=".55"/></svg>
            <span class="text-lg font-semibold tracking-tight text-fg"><?= e(Config::APP_NAME) ?></span>
        </div>
        <?php foreach (Flash::pull() as $f): ?>
        <div class="mb-4"><?= Ui::alert($f['message'], $f['type'] === 'error' ? 'danger' : 'success') ?></div>
        <?php endforeach; ?>
        <?= $content ?>
        <p class="mt-6 text-center text-xs text-subtle"><?= e(Config::SERVER_HOSTNAME) ?></p>
    </div>
</main>
<script src="/assets/js/app.js" defer></script>
<?php foreach (View::scripts() as $s): ?>
<script src="/assets/js/pages/<?= e($s) ?>.js" defer></script>
<?php endforeach; ?>
</body>
</html>
