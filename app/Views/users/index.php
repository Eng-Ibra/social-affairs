<?php $canApprove = can('users','approve'); $canEdit = can('users','edit'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-users-gear me-2"></i>User Management</h4>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label small mb-1">Search</label><input type="text" name="q" class="form-control form-control-sm" value="<?= e($q) ?>"></div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['pending','approved','rejected','suspended'] as $s): ?>
                        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Filter</button></div>
            <div class="col-md-2"><a href="<?= url('/users') ?>" class="btn btn-sm btn-outline-secondary w-100">Reset</a></div>
        </form>
    </div>
</div>

<div class="card">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Department/Section</th><th>Status</th><th>Last Login</th><th class="text-end">Actions</th></tr></thead>
<tbody>
<?php if (!$users): ?><tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr><?php endif; ?>
<?php foreach ($users as $u): ?>
<tr>
    <td><?= e($u['name']) ?></td>
    <td><?= e($u['email']) ?></td>
    <td><?= e($u['role_name']) ?></td>
    <td><?= e($u['department_name'] ?? '-') ?> <?= $u['section_name'] ? '/ ' . e($u['section_name']) : '' ?></td>
    <td>
        <?php $badgeMap = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','suspended'=>'secondary']; ?>
        <span class="badge bg-<?= $badgeMap[$u['status']] ?> text-capitalize"><?= $u['status'] ?></span>
    </td>
    <td class="small text-muted"><?= format_datetime($u['last_login_at']) ?></td>
    <td class="text-end text-nowrap">
        <?php if ($canApprove && $u['status'] === 'pending'): ?>
            <form method="post" action="<?= url('/users/' . $u['id'] . '/approve') ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i> Approve</button></form>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $u['id'] ?>"><i class="fa-solid fa-xmark"></i> Reject</button>
            <div class="modal fade" id="rejectModal<?= $u['id'] ?>"><div class="modal-dialog"><div class="modal-content">
                <form method="post" action="<?= url('/users/' . $u['id'] . '/reject') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-header"><h6 class="modal-title">Reject <?= e($u['name']) ?></h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><textarea name="reason" class="form-control" placeholder="Reason for rejection" required></textarea></div>
                    <div class="modal-footer"><button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-sm btn-danger">Reject</button></div>
                </form>
            </div></div></div>
        <?php endif; ?>
        <?php if ($canEdit && $u['status'] !== 'pending'): ?>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal<?= $u['id'] ?>"><i class="fa-solid fa-pen"></i></button>
            <div class="modal fade" id="editModal<?= $u['id'] ?>"><div class="modal-dialog"><div class="modal-content">
                <form method="post" action="<?= url('/users/' . $u['id'] . '/role') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-header"><h6 class="modal-title">Edit <?= e($u['name']) ?></h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <label class="form-label small">Role</label>
                        <select name="role_id" class="form-select mb-2">
                            <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>" <?= $r['id']==$u['role_id']?'selected':'' ?>><?= e($r['name']) ?></option><?php endforeach; ?>
                        </select>
                        <label class="form-label small">Section</label>
                        <select name="section_id" class="form-select">
                            <option value="">-- None --</option>
                            <?php foreach ($sections as $s): ?><option value="<?= $s['id'] ?>" <?= $s['id']==$u['section_id']?'selected':'' ?>><?= e($s['section_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-sm btn-primary">Save</button>
                    </div>
                </form>
            </div></div></div>
            <?php if ($u['status'] === 'suspended'): ?>
                <form method="post" action="<?= url('/users/' . $u['id'] . '/reactivate') ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-outline-success"><i class="fa-solid fa-rotate-left"></i></button></form>
            <?php elseif ($u['status'] === 'approved' && $u['id'] != current_user()['id']): ?>
                <form method="post" action="<?= url('/users/' . $u['id'] . '/suspend') ?>" class="d-inline" data-confirm="Suspend this account?"><?= csrf_field() ?><button class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-ban"></i></button></form>
            <?php endif; ?>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
