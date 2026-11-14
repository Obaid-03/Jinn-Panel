<?php
/** @var string|null $token null = the link isn't valid (any more) */
/** @var array<string,mixed>|null $row the account */
/** @var string|null $error */
?>
<div class="auth-card">
    <?php if ($token === null): ?>
    <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-full bg-warning-soft text-warning-text"><?= icon('clock', 'h-5 w-5') ?></div>
    <h1 class="text-xl font-semibold tracking-tight text-fg">This link no longer works</h1>
    <p class="mt-2 text-sm text-muted">Password links work once, and only for a limited time. Ask for a new one.</p>
    <a href="/login/forgot" class="btn btn-primary btn-block mt-6">Send a new link</a>
    <p class="mt-4 text-center text-sm"><a href="/login" class="link-muted">Back to sign in</a></p>
    <?php else: ?>
    <h1 class="text-xl font-semibold tracking-tight text-fg"><?= ($row['purpose'] ?? '') === 'welcome' ? 'Choose your password' : 'Choose a new password' ?></h1>
    <p class="mt-1 text-sm text-muted">For <span class="font-medium text-fg"><?= e((string) $row['username']) ?></span>. Other signed-in sessions will be signed out.</p>
    <?php if (!empty($error)): ?>
    <div class="mt-5"><?= Ui::alert($error, 'danger') ?></div>
    <?php endif; ?>
    <form method="post" action="/login/reset" class="mt-6 space-y-4">
        <?= Csrf::field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <input type="text" name="username" value="<?= e((string) $row['username']) ?>" autocomplete="username" class="sr-only" tabindex="-1" aria-label="Username" readonly>
        <?= Ui::field('new_password', 'New password', ['type' => 'password', 'required' => true, 'generate' => true, 'hint' => 'At least ' . Passwords::MIN_LENGTH . ' characters, not a common password.', 'attrs' => ['minlength' => Passwords::MIN_LENGTH, 'autocomplete' => 'new-password', 'autofocus' => true]]) ?>
        <?= Ui::field('new_password_confirm', 'New password again', ['type' => 'password', 'required' => true, 'attrs' => ['minlength' => Passwords::MIN_LENGTH, 'autocomplete' => 'new-password']]) ?>
        <button type="submit" class="btn btn-primary btn-block py-2.5">Save password</button>
    </form>
    <?php endif; ?>
</div>
