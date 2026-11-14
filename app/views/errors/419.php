<main class="max-w-md text-center">
    <p class="font-mono text-sm font-semibold text-warning-text">419</p>
    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-fg">This form expired</h1>
    <p class="mt-2 text-muted">For your safety, forms only work for a while and only from the page they came from. Nothing was changed. Open the form again and resend it.</p>
    <div class="mt-6 flex justify-center gap-3">
        <?php if (!empty($back)): ?><a href="<?= e($back) ?>" class="btn btn-secondary"><?= icon('arrow-left') ?>Back to the form</a><?php endif; ?>
        <a href="/" class="btn btn-primary"><?= icon('home') ?>Dashboard</a>
    </div>
</main>
