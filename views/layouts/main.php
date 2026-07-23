<?php
/** @var string $content */
/** @var array $flashes */
if (!isset($settings)) {
    $settings = (new Setting())->all();
}
$gymName = $settings['gym_name'] ?? APP_NAME;
$currentUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? $gymName) ?> | <?= e($gymName) ?></title>
    <meta name="description" content="<?= e($settings['gym_tagline'] ?? 'Modern fitness center') ?>">
    <meta name="csrf-token" content="<?= e(Security::csrfToken()) ?>">

    <link rel="icon" type="image/png" href="<?= asset('images/logo/logo.png') ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>

<?php require BASE_PATH . '/views/partials/navbar.php'; ?>

<?php if (!empty($flashes)): ?>
<div class="container mt-4">
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<main><?= $content ?></main>

<?php require BASE_PATH . '/views/partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/main.js') ?>"></script>
<script src="<?= asset('js/password-toggle.js') ?>"></script>
<?php if (!empty($extraScripts)): foreach ($extraScripts as $script): ?>
<script src="<?= str_starts_with($script, 'http') ? e($script) : asset($script) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
