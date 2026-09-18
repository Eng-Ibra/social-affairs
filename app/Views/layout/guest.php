<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Login') ?> — <?= e(config('app.name')) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
<div class="d-flex align-items-center justify-content-center" style="min-height:100vh; background: linear-gradient(135deg,#14213d,#0d6efd);">
    <div class="card shadow-lg border-0" style="max-width:440px; width:100%;">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="mb-2"><i class="fa-solid fa-people-roof fa-2x text-primary"></i></div>
                <h5 class="fw-bold mb-0"><?= e(config('app.name')) ?></h5>
                <div class="text-muted small">Local Government Social Affairs Department</div>
            </div>
            <?php foreach (get_flashes() as $type => $messages): ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="alert alert-<?= $type === 'error' ? 'danger' : e($type) ?> py-2 small"><?= e($msg) ?></div>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php if (!empty($_SESSION['_debug_reset_link'])): ?>
                <div class="alert alert-info small">
                    Debug mode — reset link:<br>
                    <a href="<?= e($_SESSION['_debug_reset_link']) ?>"><?= e($_SESSION['_debug_reset_link']) ?></a>
                </div>
                <?php unset($_SESSION['_debug_reset_link']); ?>
            <?php endif; ?>
            <?php $content(); ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
