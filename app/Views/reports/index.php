<h4 class="mb-3"><i class="fa-solid fa-file-lines me-2"></i>Reports</h4>
<div class="row g-3">
<?php foreach ($modules as $key => $m): ?>
    <div class="col-md-4 col-lg-3">
        <a href="<?= url('/reports/' . $key) ?>" class="text-decoration-none">
            <div class="card h-100 text-center py-3">
                <i class="fa-solid <?= $m['icon'] ?> fa-2x text-primary mb-2"></i>
                <div class="fw-semibold"><?= e($m['label']) ?></div>
                <div class="text-muted small">Report</div>
            </div>
        </a>
    </div>
<?php endforeach; ?>
</div>
