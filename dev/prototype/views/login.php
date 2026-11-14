<?php
$failed = isset($_GET['failed']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<title>Sign in · server.example.com</title>
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<link rel="stylesheet" href="/__proto/assets/proto.css">
<script src="/assets/js/theme.js"></script>
</head>
<body class="min-h-screen bg-surface">
<div class="grid min-h-screen lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
    <section class="relative hidden flex-col justify-between overflow-hidden bg-[#15113a] px-12 py-10 text-white lg:flex" aria-label="This server">
        <div class="flex items-center gap-2.5">
            <svg class="h-8 w-8" viewBox="0 0 140 140" aria-hidden="true"><rect width="140" height="140" rx="32" fill="#fff" fill-opacity=".12"/><path d="M86 35v57c0 17.5-11 28.5-29.5 28.5-12 0-20.8-5-26.3-14.2l12-11c3.3 5.5 7.7 8.8 13.7 8.8 8.2 0 12.6-4.9 12.6-16.4V35H86Z" fill="#fff"/><rect x="96" y="31" width="13" height="13" rx="3" fill="#a996ff"/></svg>
            <span class="text-[15px] font-semibold">JinnPanel</span>
        </div>
        <div>
            <p class="text-[13px] text-white/60">You are signing in to</p>
            <p class="mt-3 break-all font-mono text-[clamp(2.2rem,4.4vw,3.6rem)] font-medium leading-[1.05] tracking-[-0.03em]">server<span class="text-[#a996ff]">.</span>example<span class="text-[#a996ff]">.</span>com</p>
            <p class="mt-6 max-w-md text-[15px] leading-relaxed text-white/70">The control panel for the websites, mailboxes and databases hosted on this server.</p>
        </div>
        <p class="text-[13px] text-white/50">Trouble signing in? Your hosting provider can reset your password.</p>
    </section>

    <main class="flex flex-col px-6 py-8 sm:px-10">
        <div class="flex items-center gap-2.5 lg:hidden">
            <svg class="h-7 w-7" viewBox="0 0 140 140" aria-hidden="true"><defs><linearGradient id="jp-l" x1="0" y1="0" x2="140" y2="140" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#6366f1"/><stop offset="1" stop-color="#7c3aed"/></linearGradient></defs><rect width="140" height="140" rx="32" fill="url(#jp-l)"/><path d="M86 35v57c0 17.5-11 28.5-29.5 28.5-12 0-20.8-5-26.3-14.2l12-11c3.3 5.5 7.7 8.8 13.7 8.8 8.2 0 12.6-4.9 12.6-16.4V35H86Z" fill="#fff"/></svg>
            <span class="min-w-0 truncate font-mono text-[13px] text-muted">server.example.com</span>
        </div>
        <div class="my-auto w-full max-w-[22rem] self-center py-10">
            <h1 class="text-[1.625rem] font-semibold tracking-[-0.015em] text-fg">Sign in</h1>
            <p class="mt-1.5 text-[14px] text-muted">With your username or email address.</p>
            <?php if ($failed): ?>
            <div class="mt-6"><?= Ui::alert('That username and password don\'t match. Check for caps lock, or reset your password.', 'danger') ?></div>
            <?php endif; ?>
            <form method="post" action="#" class="mt-8 space-y-5" data-auth>
                <?= Ui::field('username', 'Username or email', ['required' => true, 'value' => $failed ? 'acme' : null, 'attrs' => ['autocomplete' => 'username', 'autocapitalize' => 'off', 'spellcheck' => 'false', 'autofocus' => !$failed]]) ?>
                <div class="field">
                    <div class="mb-1.5 flex items-baseline justify-between"><label for="pw" class="text-[13px] font-medium text-fg">Password</label><a href="#" class="text-[13px] link">Forgot it?</a></div>
                    <div class="relative">
                        <input id="pw" name="password" type="password" required autocomplete="current-password" class="input pr-11" aria-describedby="pw-caps" <?= $failed ? 'autofocus aria-invalid="true"' : '' ?> data-password>
                        <button type="button" class="absolute right-1 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-[6px] text-subtle hover:bg-surface-3 hover:text-fg" aria-label="Show password" aria-pressed="false" data-reveal><?= icon('eye', 'h-4 w-4') ?></button>
                    </div>
                    <p id="pw-caps" class="field-error flex items-center gap-1.5" hidden data-caps><?= icon('alert', 'h-3.5 w-3.5') ?>Caps lock is on</p>
                </div>
                <button type="submit" class="btn btn-primary btn-block h-10 text-[14px]">Sign in</button>
            </form>
            <p class="mt-8 text-[13px] text-subtle">Two-factor codes are asked for on the next step, if you turned them on.</p>
        </div>
        <p class="text-center text-[12px] text-subtle lg:hidden">Trouble signing in? Your hosting provider can reset your password.</p>
    </main>
</div>
<script src="/__proto/assets/auth.js" defer></script>
</body>
</html>
