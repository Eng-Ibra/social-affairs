<?php $listFields = array_values(array_filter($module['fields'], fn ($f) => !empty($f['list']))); ?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h4 class="mb-0"><i class="fa-solid <?= $module['icon'] ?> me-2"></i><?= e($module['label']) ?> Report</h4>
    <div class="d-flex gap-2">
        <a href="<?= url('/reports') ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <a href="<?= url('/reports/' . $module['key'] . '?' . http_build_query(array_merge($_GET, ['export' => 1]))) ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-file-excel"></i> Export</a>
        <button onclick="window.print()" class="btn btn-sm btn-primary"><i class="fa-solid fa-print"></i> Print</button>
    </div>
</div>

<div class="card mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small mb-1">Search</label><input type="text" name="q" class="form-control form-control-sm" value="<?= e($_GET['q'] ?? '') ?>"></div>
            <div class="col-md-2"><label class="form-label small mb-1">From</label><input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($_GET['date_from'] ?? '') ?>"></div>
            <div class="col-md-2"><label class="form-label small mb-1">To</label><input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($_GET['date_to'] ?? '') ?>"></div>
            <?php foreach (array_filter($module['fields'], fn($f)=>!empty($f['filterable'])) as $f): ?>
                <?php if ($f['type'] === 'select'): ?>
                <div class="col-md-2">
                    <label class="form-label small mb-1"><?= e($f['label']) ?></label>
                    <select name="<?= $f['name'] ?>" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($f['options'] as $val=>$label): ?><option value="<?= e($val) ?>" <?= ($_GET[$f['name']]??'')===$val?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Apply</button></div>
        </form>
    </div>
</div>

<div class="text-center mb-2 d-none d-print-block">
    <h5><?= e(config('app.name')) ?></h5>
    <div><?= e($module['label']) ?> Report — Generated <?= date('d M Y H:i') ?></div>
</div>

<div class="card">
    <div class="card-header fw-semibold">Total Records: <?= count($rows) ?></div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
            <thead><tr><?php foreach ($listFields as $f): ?><th><?= e($f['label']) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                <?php foreach ($listFields as $f):
                    $val = $f['type'] === 'relation' ? ($row[$f['name'] . '_label'] ?? '-') : ($row[$f['name']] ?? '-');
                    if ($f['type'] === 'select' && isset($f['options'][$val])) $val = $f['options'][$val];
                    if ($f['type'] === 'date') $val = format_date($val);
                ?>
                    <td><?= e((string) $val) ?></td>
                <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
