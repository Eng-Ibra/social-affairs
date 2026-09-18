<h4 class="mb-3"><i class="fa-solid fa-magnifying-glass me-2"></i>Search Results for "<?= e($q) ?>"</h4>

<?php if ($q === ''): ?>
    <div class="alert alert-info">Enter a search term in the top bar.</div>
<?php elseif (!$results): ?>
    <div class="alert alert-secondary">No matches found across any module.</div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($results as $key => $data): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header fw-semibold"><i class="fa-solid <?= $data['module']['icon'] ?> me-2"></i><?= e($data['module']['label']) ?></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($data['rows'] as $row): ?>
                    <a href="<?= url('/m/' . $key . '/' . $row['id']) ?>" class="list-group-item list-group-item-action"><?= e($row['title']) ?></a>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endforeach; ?>
</div>
