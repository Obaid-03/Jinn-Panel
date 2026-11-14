<?php
/** @var array{services:array<string,bool>, hostname:string, ip:string} $server */
/** @var list<string> $errors */
/** @var array<string,string> $old */
View::slot('wide', '1');
$down = in_array(false, $server['services'], true);
?>
<div class="auth-card">
    <h1 class="text-xl font-semibold tracking-tight text-fg">Welcome to <?= e(Config::APP_NAME) ?></h1>
    <p class="mt-1 text-sm text-muted">One-time setup: create the administrator account for this server.</p>

    <div class="mt-6">
        <p class="section-title mb-2">Services</p>
        <ul class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            <?php foreach ($server['services'] as $name => $up): ?>
            <li class="flex items-center justify-between gap-2 rounded-lg border border-line bg-surface-2 px-3 py-2 text-sm">
                <span class="text-fg"><?= e($name) ?></span>
                <span class="<?= $up ? 'text-success' : 'text-danger' ?>" title="<?= $up ? 'Answering' : 'Not answering' ?>"><span class="dot" aria-hidden="true"></span><span class="sr-only"><?= $up ? 'answering' : 'not answering' ?></span></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($down): ?>
        <div class="mt-3"><?= Ui::alert('One or more services aren\'t answering yet. You can still finish setup; check <code>systemctl status</code> for the ones marked red before relying on them.', 'warning', true) ?></div>
        <?php endif; ?>
    </div>

    <?php if ($errors): ?>
    <div class="mt-5"><?= Ui::alert('<ul class="list-inside list-disc space-y-0.5">' . implode('', array_map(fn($er) => '<li>' . e($er) . '</li>', $errors)) . '</ul>', 'danger', true) ?></div>
    <?php endif; ?>

    <form method="post" action="/setup" class="mt-6 space-y-4">
        <?= Csrf::field() ?>
        <?= Ui::field('setup_token', 'Setup token', ['required' => true, 'mono' => true, 'placeholder' => 'printed at the end of install.sh', 'hint' => 'Also on the server in /root/.jinnpanel/setup_token.', 'attrs' => ['autocomplete' => 'off', 'spellcheck' => 'false', 'autofocus' => true]]) ?>
        <div class="grid gap-4 sm:grid-cols-2">
            <?= Ui::field('username', 'Admin username', ['required' => true, 'value' => $old['username'] ?? '', 'mono' => true, 'attrs' => ['autocomplete' => 'username', 'autocapitalize' => 'off']]) ?>
            <?= Ui::field('full_name', 'Full name', ['optional' => true, 'value' => $old['full_name'] ?? '']) ?>
        </div>
        <?= Ui::field('email', 'Admin email', ['type' => 'email', 'required' => true, 'value' => $old['email'] ?? '', 'hint' => 'For password resets and server alerts.']) ?>
        <div class="grid gap-4 sm:grid-cols-2">
            <?= Ui::field('password', 'Password', ['type' => 'password', 'required' => true, 'attrs' => ['minlength' => Passwords::MIN_LENGTH, 'autocomplete' => 'new-password']]) ?>
            <?= Ui::field('password_confirm', 'Confirm password', ['type' => 'password', 'required' => true, 'attrs' => ['minlength' => Passwords::MIN_LENGTH, 'autocomplete' => 'new-password']]) ?>
        </div>
        <p class="text-xs text-muted">At least <?= Passwords::MIN_LENGTH ?> characters. This account controls every domain, mailbox and server setting.</p>
        <button type="submit" class="btn btn-primary btn-block py-2.5">Create admin account and finish setup</button>
    </form>

    <p class="mt-6 border-t border-line pt-4 text-xs text-muted">
        Server <code class="text-fg"><?= e($server['hostname']) ?></code> · <code class="text-fg"><?= e($server['ip']) ?></code>
    </p>
</div>
