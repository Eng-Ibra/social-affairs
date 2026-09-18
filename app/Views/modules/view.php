<?php
$canEdit = can($module['key'], 'edit');
$canDelete = can($module['key'], 'delete');
$canViewAll = can($module['key'], 'view_all') || can($module['key'], 'manage');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid <?= $module['icon'] ?> me-2"></i><?= e($title) ?></h4>
    <div class="d-flex gap-2">
        <a href="<?= url('/m/' . $module['key']) ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <?php if ($canEdit): ?><a href="<?= url('/m/' . $module['key'] . '/' . $record['id'] . '/edit') ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-pen"></i> Edit</a><?php endif; ?>
        <?php if ($canDelete): ?>
        <form action="<?= url('/m/' . $module['key'] . '/' . $record['id'] . '/delete') ?>" method="post" data-confirm="Archive this record?">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-box-archive"></i> Archive</button>
        </form>
        <?php endif; ?>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="fa-solid fa-print"></i> Print</button>
    </div>
</div>

<?php if ($metrics): ?>
<div class="card mb-3">
    <div class="card-body">
        <div class="row text-center g-3">
            <?php if ($module['key'] === 'projects'): ?>
                <div class="col"><div class="fs-4 fw-bold"><?= $metrics['days_elapsed'] ?></div><div class="text-muted small">Days Elapsed</div></div>
                <div class="col"><div class="fs-4 fw-bold"><?= $metrics['days_remaining'] ?></div><div class="text-muted small">Days Remaining</div></div>
                <div class="col"><div class="fs-4 fw-bold"><?= $metrics['total_days'] ?></div><div class="text-muted small">Total Days</div></div>
                <div class="col"><div class="fs-4 fw-bold"><?= $metrics['time_progress_percent'] ?>%</div><div class="text-muted small">Time Progress</div></div>
                <div class="col">
                    <?php $badgeMap = ['upcoming'=>'secondary','active'=>'primary','near_deadline'=>'warning','overdue'=>'danger','completed'=>'success']; ?>
                    <span class="badge bg-<?= $badgeMap[$metrics['status']] ?? 'secondary' ?> fs-6 text-capitalize"><?= str_replace('_',' ',$metrics['status']) ?></span>
                    <div class="text-muted small mt-1">Status</div>
                </div>
            <?php else: ?>
                <div class="col"><div class="fs-5 fw-bold"><?= e($metrics['remaining_label']) ?></div><div class="text-muted small">Timeline</div></div>
                <div class="col">
                    <?php $badgeMap = ['pending'=>'secondary','in_progress'=>'primary','completed'=>'success','overdue'=>'danger']; ?>
                    <span class="badge bg-<?= $badgeMap[$metrics['status']] ?? 'secondary' ?> fs-6 text-capitalize"><?= str_replace('_',' ',$metrics['status']) ?></span>
                    <div class="text-muted small mt-1">Status</div>
                </div>
            <?php endif; ?>
        </div>
        <div class="progress mt-3" style="height:8px;">
            <div class="progress-bar" role="progressbar" style="width: <?= $module['key'] === 'projects' ? $metrics['time_progress_percent'] : ($record['completion_percent'] ?? 0) ?>%;"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="row g-3">
        <?php foreach ($module['fields'] as $f):
            $name = $f['name'];
            if (($f['type'] ?? '') === 'file') continue;
            $val = $f['type'] === 'relation' ? ($record[$name . '_label'] ?? '—') : ($record[$name] ?? '—');
        ?>
            <div class="col-md-4">
                <div class="text-muted small text-uppercase" style="font-size:.72rem;letter-spacing:.04em;"><?= e($f['label']) ?></div>
                <div>
                <?php
                if (!empty($f['sensitive']) && !$canViewAll) {
                    echo '<span class="text-muted">•••• masked (insufficient permission)</span>';
                } elseif ($f['type'] === 'select' && isset($f['options'][$val])) {
                    echo e($f['options'][$val]);
                } elseif ($f['type'] === 'date') {
                    echo format_date($val);
                } elseif ($f['type'] === 'polymorphic_location') {
                    echo e(\App\Core\LocationResolver::resolve($record['location_type'] ?? null, $val ?: null) ?? '—');
                } elseif ($f['type'] === 'textarea') {
                    echo nl2br(e((string) $val));
                } else {
                    echo e((string) $val) !== '' ? e((string) $val) : '—';
                }
                ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

        <?php foreach ($module['fields'] as $f): if (($f['type'] ?? '') === 'file' && !empty($record[$f['name']])): ?>
            <hr>
            <a href="<?= url($record[$f['name']]) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-paperclip"></i> <?= e($f['label']) ?></a>
        <?php endif; endforeach; ?>
    </div>
    <div class="card-footer small text-muted">
        Created <?= format_datetime($record['created_at']) ?><?= !empty($record['updated_at']) && $record['updated_at'] !== $record['created_at'] ? ' · Updated ' . format_datetime($record['updated_at']) : '' ?>
    </div>
</div>
