<h4 class="mb-3"><i class="fa-solid fa-shield-halved me-2"></i>Audit Log</h4>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1">Module</label>
                <select name="module" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach ($modules as $m): ?><option value="<?= e($m) ?>" <?= ($_GET['module'] ?? '')===$m?'selected':'' ?>><?= e($m) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach ($actions as $a): ?><option value="<?= e($a) ?>" <?= ($_GET['action'] ?? '')===$a?'selected':'' ?>><?= e($a) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label small mb-1">From</label><input type="date" name="from" class="form-control form-control-sm" value="<?= e($_GET['from'] ?? '') ?>"></div>
            <div class="col-md-2"><label class="form-label small mb-1">To</label><input type="date" name="to" class="form-control form-control-sm" value="<?= e($_GET['to'] ?? '') ?>"></div>
            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Filter</button></div>
            <div class="col-md-2"><a href="<?= url('/audit-logs') ?>" class="btn btn-sm btn-outline-secondary w-100">Reset</a></div>
        </form>
    </div>
</div>

<div class="card">
<div class="table-responsive">
<table class="table table-sm table-hover mb-0">
<thead><tr><th>Date/Time</th><th>User</th><th>Action</th><th>Module</th><th>Record</th><th>Description</th><th>IP</th></tr></thead>
<tbody>
<?php if (!$logs): ?><tr><td colspan="7" class="text-center text-muted py-4">No audit entries found.</td></tr><?php endif; ?>
<?php foreach ($logs as $log): ?>
<tr>
    <td class="small text-nowrap"><?= format_datetime($log['created_at']) ?></td>
    <td class="small"><?= e($log['user_name'] ?? 'System') ?></td>
    <td><span class="badge bg-secondary text-capitalize"><?= str_replace('_',' ',e($log['action'])) ?></span></td>
    <td class="small text-capitalize"><?= str_replace('_',' ',e($log['module'])) ?></td>
    <td class="small">#<?= e((string)($log['record_id'] ?? '-')) ?></td>
    <td class="small"><?= e($log['description'] ?? '') ?></td>
    <td class="small text-muted"><?= e($log['ip_address'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php if ($lastPage > 1): ?>
<div class="card-footer d-flex justify-content-between align-items-center">
    <span class="text-muted small">Page <?= $page ?> of <?= $lastPage ?> (<?= number_format($total) ?> entries)</span>
    <nav><ul class="pagination pagination-sm mb-0">
        <?php for ($p = max(1,$page-2); $p <= min($lastPage,$page+2); $p++): ?>
            <li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="<?= url('/audit-logs?' . http_build_query(array_merge($_GET,['page'=>$p]))) ?>"><?= $p ?></a></li>
        <?php endfor; ?>
    </ul></nav>
</div>
<?php endif; ?>
</div>
