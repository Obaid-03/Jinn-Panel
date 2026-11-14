<?php
/** @var string|null $error */
/** @var bool $recovery show the recovery-code form first */
$recovery ??= false;
View::script('two-factor');
?>
<div class="auth-card">
    <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-full bg-accent-soft text-accent-text"><?= icon('shield', 'h-5 w-5') ?></div>
    <h1 class="text-xl font-semibold tracking-tight text-fg">Two-factor sign-in</h1>

    <?php if (!empty($error)): ?>
    <div class="mt-5"><?= Ui::alert($error, 'danger') ?></div>
    <?php endif; ?>

    <form method="post" action="/login/2fa" class="mt-5 space-y-4<?= $recovery ? ' js-hide' : '' ?>" data-tf="code">
        <?= Csrf::field() ?>
        <p class="text-sm text-muted">Enter the 6-digit code from your authenticator app.</p>
        <?= Ui::field('code', 'Authentication code', ['required' => !$recovery, 'class' => 'py-2.5 text-center font-mono text-lg tracking-[0.4em]', 'attrs' => ['autofocus' => !$recovery, 'inputmode' => 'numeric', 'autocomplete' => 'one-time-code', 'pattern' => '[0-9 ]{6,7}', 'maxlength' => 7]]) ?>
        <button type="submit" class="btn btn-primary btn-block py-2.5">Verify</button>
    </form>

    <form method="post" action="/login/2fa" class="mt-5 space-y-4<?= $recovery ? '' : ' js-hide' ?>" data-tf="recovery">
        <?= Csrf::field() ?>
        <p class="text-sm text-muted">Lost your phone? Enter one of the recovery codes you saved when you turned on two-factor. Each works once.</p>
        <?= Ui::field('recovery_code', 'Recovery code', ['required' => $recovery, 'mono' => true, 'placeholder' => 'xxxx-xxxx-xxxx', 'class' => 'py-2.5 text-center tracking-wider', 'attrs' => ['autocomplete' => 'off', 'autocapitalize' => 'off', 'spellcheck' => 'false', 'autofocus' => $recovery]]) ?>
        <button type="submit" class="btn btn-primary btn-block py-2.5">Sign in with recovery code</button>
    </form>

    <div class="mt-6 flex items-center justify-between text-sm">
        <a href="/login" class="link-muted">Start over</a>
        <button type="button" class="link hidden [.js_&]:inline" data-tf-switch><?= $recovery ? 'Use the authenticator app' : 'Use a recovery code' ?></button>
    </div>
    <noscript><p class="mt-4 text-xs text-muted">Without JavaScript both forms are shown: use either.</p></noscript>
</div>
