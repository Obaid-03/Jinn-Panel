<?php
/** @var bool $sent */
/** @var string|null $error */
?>
<div class="auth-card">
    <?php if ($sent): ?>
    <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-full bg-success-soft text-success-text"><?= icon('mail', 'h-5 w-5') ?></div>
    <h1 class="text-xl font-semibold tracking-tight text-fg">Check your email</h1>
    <p class="mt-2 text-sm text-muted">If an account matches, we've sent a link to its email address. It works once, for the next hour.</p>
    <p class="mt-3 text-sm text-muted">Nothing after a few minutes? Check the spam folder, or ask your hosting provider to reset the password for you.</p>
    <a href="/login" class="btn btn-secondary btn-block mt-6"><?= icon('arrow-left') ?>Back to sign in</a>
    <?php else: ?>
    <h1 class="text-xl font-semibold tracking-tight text-fg">Reset your password</h1>
    <p class="mt-1 text-sm text-muted">We'll email you a link to choose a new one.</p>
    <?php if (!empty($error)): ?>
    <div class="mt-5"><?= Ui::alert($error, 'danger') ?></div>
    <?php endif; ?>
    <form method="post" action="/login/forgot" class="mt-6 space-y-4">
        <?= Csrf::field() ?>
        <?= Ui::field('username', 'Username or email', ['required' => true, 'attrs' => ['autofocus' => true, 'autocomplete' => 'username', 'autocapitalize' => 'off', 'spellcheck' => 'false']]) ?>
        <button type="submit" class="btn btn-primary btn-block py-2.5">Email me a link</button>
    </form>
    <p class="mt-6 text-center text-sm"><a href="/login" class="link-muted">Back to sign in</a></p>
    <?php endif; ?>
</div>
