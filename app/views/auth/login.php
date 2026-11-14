<?php /** @var string|null $error */ ?>
<div class="auth-card">
    <h1 class="text-xl font-semibold tracking-tight text-fg">Sign in</h1>
    <p class="mt-1 text-sm text-muted">to your hosting control panel.</p>

    <?php if (!empty($error)): ?>
    <div class="mt-5"><?= Ui::alert($error, 'danger') ?></div>
    <?php endif; ?>

    <form method="post" action="/login" class="mt-6 space-y-4">
        <?= Csrf::field() ?>
        <?= Ui::field('username', 'Username or email', ['required' => true, 'attrs' => ['autofocus' => true, 'autocomplete' => 'username', 'autocapitalize' => 'off', 'spellcheck' => 'false']]) ?>
        <div>
            <div class="mb-1.5 flex items-baseline justify-between">
                <label for="login-password" class="text-sm font-medium text-fg">Password</label>
                <a href="/login/forgot" class="text-sm link">Forgot it?</a>
            </div>
            <input type="password" name="password" id="login-password" required autocomplete="current-password" class="input">
        </div>
        <button type="submit" class="btn btn-primary btn-block py-2.5">Sign in</button>
    </form>
</div>
