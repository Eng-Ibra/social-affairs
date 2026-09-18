<?php
$listFields = array_values(array_filter($module['fields'], fn ($f) => !empty($f['list'])));
$filterFields = array_values(array_filter($module['fields'], fn ($f) => !empty($f['filterable'])));
$canCreate = can($module['key'], 'create');
$canEdit = can($module['key'], 'edit');
$canDelete = can($module['key'], 'delete');
$canExport = can($module['key'], 'export');
$canImport = can($module['key'], 'import');
$canViewAll = can($module['key'], 'view_all') || can($module['key'], 'manage');

function sas_sort_link(string $field, string $label, string $module, string $sort, string $dir, array $qs): string {
    $newDir = ($sort === $field && $dir === 'ASC') ? 'desc' : 'asc';
    $qs = array_merge($qs, ['sort' => $field, 'dir' => $newDir]);
    $icon = $sort === $field ? ($dir === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort text-muted';
    return '<a class="text-decoration-none text-reset" href="' . url('/m/' . $module . '?' . http_build_query($qs)) . '">' . e($label) . ' <i class="fa-solid ' . $icon . ' small"></i></a>';
}
$qsBase = $_GET;
unset($qsBase['sort'], $qsBase['dir'], $qsBase['page']);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="mb-0"><i class="fa-solid <?= $module['icon'] ?> me-2"></i><?= e($module['label']) ?></h4>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (!empty($module['has_map'])): ?>
            <a href="<?= url('/map?module=' . $module['key']) ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-map-location-dot"></i> Map</a>
        <?php endif; ?>
        <?php if ($canImport): ?>
            <a href="<?= url('/m/' . $module['key'] . '/import/template') ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-file-arrow-down"></i> Template</a>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal"><i class="fa-solid fa-file-import"></i> Import</button>
        <?php endif; ?>
        <?php if ($canExport): ?>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="fa-solid fa-file-export"></i> Export</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= url('/m/' . $module['key'] . '/export/csv?' . http_build_query($_GET)) ?>">CSV</a></li>
                    <li><a class="dropdown-item" href="<?= url('/m/' . $module['key'] . '/export/xlsx?' . http_build_query($_GET)) ?>">Excel (.xlsx)</a></li>
                </ul>
            </div>
        <?php endif; ?>
        <?php if ($canDelete && ($module['soft_deletes'] ?? true)): ?>
            <a href="<?= url('/m/' . $module['key'] . ($showTrashed ? '' : '?trashed=1')) ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid <?= $showTrashed ? 'fa-arrow-left' : 'fa-box-archive' ?>"></i> <?= $showTrashed ? 'Back to Active' : 'View Archived' ?>
            </a>
        <?php endif; ?>
        <?php if ($canCreate): ?>
            <a href="<?= url('/m/' . $module['key'] . '/create') ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus"></i> Add <?= e($module['label_singular']) ?></a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Search</label>
                <input type="text" name="q" class="form-control form-control-sm" value="<?= e($q) ?>" placeholder="Search <?= e(strtolower($module['label'])) ?>…">
            </div>
            <?php foreach ($filterFields as $f): ?>
            <div class="col-md-2">
                <label class="form-label small mb-1"><?= e($f['label']) ?></label>
                <?php if ($f['type'] === 'relation'): ?>
                    <?php $opts = (new \App\Controllers\CrudController())->relationOptions($f); ?>
                    <select name="<?= $f['name'] ?>" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($opts as $o): ?>
                            <option value="<?= $o['id'] ?>" <?= (string)($_GET[$f['name']] ?? '') === (string)$o['id'] ? 'selected' : '' ?>><?= e($o['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <select name="<?= $f['name'] ?>" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($f['options'] ?? [] as $val => $label): ?>
                            <option value="<?= e($val) ?>" <?= (string)($_GET[$f['name']] ?? '') === (string)$val ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="fa-solid fa-filter"></i> Apply</button>
            </div>
            <div class="col-md-2">
                <a href="<?= url('/m/' . $module['key']) ?>" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (!$canViewAll): ?>
    <div class="alert alert-info small py-2"><i class="fa-solid fa-circle-info me-1"></i> You are viewing records you created<?php if (in_array('section_id', array_column($module['fields'],'name'))): ?> or that belong to your section<?php endif; ?>.</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <?php foreach ($listFields as $f): ?>
                        <th><?= sas_sort_link($f['name'], $f['label'], $module['key'], $sort, $dir, $qsBase) ?></th>
                    <?php endforeach; ?>
                    <?php if (in_array($module['key'], ['projects','activities'])): ?><th>Status</th><?php endif; ?>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="<?= count($listFields) + 2 ?>" class="text-center text-muted py-4">
                    <i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i> No records found.
                </td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($listFields as $f): ?>
                        <td>
                        <?php
                        $val = $f['type'] === 'relation' ? ($row[$f['name'] . '_label'] ?? '-') : ($row[$f['name']] ?? '-');
                        if (!empty($f['sensitive']) && !$canViewAll) {
                            echo '<span class="text-muted">•••• masked</span>';
                        } elseif ($f['type'] === 'select' && isset($f['options'][$val])) {
                            echo e($f['options'][$val]);
                        } elseif ($f['type'] === 'date') {
                            echo format_date($val);
                        } else {
                            echo e((string) $val);
                        }
                        ?>
                        </td>
                    <?php endforeach; ?>
                    <?php if (in_array($module['key'], ['projects','activities'])): ?>
                        <td>
                        <?php $st = $row['_metrics']['status'] ?? $row['status']; $badgeMap = ['upcoming'=>'secondary','active'=>'primary','near_deadline'=>'warning','overdue'=>'danger','completed'=>'success','pending'=>'secondary','in_progress'=>'primary']; ?>
                        <span class="badge bg-<?= $badgeMap[$st] ?? 'secondary' ?> text-capitalize"><?= str_replace('_',' ',$st) ?></span>
                        </td>
                    <?php endif; ?>
                    <td class="text-end text-nowrap">
                        <?php if ($showTrashed): ?>
                            <?php if ($canDelete): ?>
                            <form action="<?= url('/m/' . $module['key'] . '/' . $row['id'] . '/restore') ?>" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-success" title="Restore"><i class="fa-solid fa-rotate-left"></i> Restore</button>
                            </form>
                            <?php endif; ?>
                        <?php else: ?>
                        <a href="<?= url('/m/' . $module['key'] . '/' . $row['id']) ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="fa-solid fa-eye"></i></a>
                        <?php if ($canEdit): ?>
                        <a href="<?= url('/m/' . $module['key'] . '/' . $row['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                        <form action="<?= url('/m/' . $module['key'] . '/' . $row['id'] . '/delete') ?>" method="post" class="d-inline" data-confirm="Archive this record? It can be restored by an administrator.">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" title="Archive"><i class="fa-solid fa-box-archive"></i></button>
                        </form>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($lastPage > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <span class="text-muted small">Showing page <?= $page ?> of <?= $lastPage ?> (<?= number_format($total) ?> total)</span>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($p = 1; $p <= $lastPage; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('/m/' . $module['key'] . '?' . http_build_query(array_merge($_GET, ['page' => $p]))) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php if ($canImport): ?>
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?= url('/m/' . $module['key'] . '/import') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Import <?= e($module['label']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p class="small text-muted">Upload a CSV or Excel (.xlsx) file matching the downloadable template. Rows with validation errors will be skipped and reported.</p>
            <input type="file" name="import_file" class="form-control" accept=".csv,.xlsx" required>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary">Import</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
