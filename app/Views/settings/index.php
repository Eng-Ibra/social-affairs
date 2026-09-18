<h4 class="mb-3"><i class="fa-solid fa-gears me-2"></i>Settings</h4>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header fw-semibold">System Settings</div>
            <form method="post" action="<?= url('/settings') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="mb-2"><label class="form-label small">System Name</label><input type="text" name="system_name" class="form-control" value="<?= e($settings['system_name'] ?? '') ?>"></div>
                    <div class="mb-2"><label class="form-label small">Organization Name</label><input type="text" name="organization_name" class="form-control" value="<?= e($settings['organization_name'] ?? '') ?>"></div>
                    <div class="mb-2"><label class="form-label small">District</label><input type="text" name="district_name" class="form-control" value="<?= e($settings['district_name'] ?? '') ?>"></div>
                    <div class="row">
                        <div class="col-md-6 mb-2"><label class="form-label small">Contact Email</label><input type="email" name="contact_email" class="form-control" value="<?= e($settings['contact_email'] ?? '') ?>"></div>
                        <div class="col-md-6 mb-2"><label class="form-label small">Contact Phone</label><input type="text" name="contact_phone" class="form-control" value="<?= e($settings['contact_phone'] ?? '') ?>"></div>
                    </div>
                    <div class="mb-2"><label class="form-label small">Logo</label><input type="file" name="logo" class="form-control" accept="image/*">
                        <?php if (!empty($settings['logo_path'])): ?><div class="form-text">Current: <?= e($settings['logo_path']) ?></div><?php endif; ?>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6 mb-2"><label class="form-label small">Project Deadline Warning (days)</label><input type="number" name="project_deadline_warning_days" class="form-control" value="<?= e($settings['project_deadline_warning_days'] ?? 7) ?>"></div>
                        <div class="col-md-6 mb-2"><label class="form-label small">Activity Deadline Warning (days)</label><input type="number" name="activity_deadline_warning_days" class="form-control" value="<?= e($settings['activity_deadline_warning_days'] ?? 2) ?>"></div>
                        <div class="col-md-6 mb-2"><label class="form-label small">Max Login Attempts</label><input type="number" name="max_login_attempts" class="form-control" value="<?= e($settings['max_login_attempts'] ?? 5) ?>"></div>
                        <div class="col-md-6 mb-2"><label class="form-label small">Lockout Duration (minutes)</label><input type="number" name="login_lockout_minutes" class="form-control" value="<?= e($settings['login_lockout_minutes'] ?? 15) ?>"></div>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-primary btn-sm">Save Settings</button></div>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header fw-semibold">Categories</div>
            <div class="card-body">
                <ul class="nav nav-pills mb-3 flex-wrap">
                    <?php foreach ($categoryTypes as $type => $label): ?>
                        <li class="nav-item"><button class="nav-link <?= $type === array_key_first($categoryTypes) ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#cat_<?= $type ?>"><?= e($label) ?></button></li>
                    <?php endforeach; ?>
                </ul>
                <div class="tab-content">
                <?php foreach ($categoryTypes as $type => $label): ?>
                    <div class="tab-pane fade <?= $type === array_key_first($categoryTypes) ? 'show active' : '' ?>" id="cat_<?= $type ?>">
                        <form method="post" action="<?= url('/settings/categories') ?>" class="input-group input-group-sm mb-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="type" value="<?= $type ?>">
                            <input type="text" name="name" class="form-control" placeholder="Add new <?= strtolower(rtrim($label,'s')) ?>…" required>
                            <button class="btn btn-outline-primary">Add</button>
                        </form>
                        <ul class="list-group list-group-flush" style="max-height:220px; overflow-y:auto;">
                            <?php foreach ($categories[$type] as $c): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-1">
                                    <span class="<?= $c['status'] === 'inactive' ? 'text-muted text-decoration-line-through' : '' ?>"><?= e($c['name']) ?></span>
                                    <span>
                                        <form method="post" action="<?= url('/settings/categories/' . $c['id'] . '/toggle') ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-link p-0 me-2"><?= $c['status']==='active' ? 'Deactivate' : 'Activate' ?></button></form>
                                        <form method="post" action="<?= url('/settings/categories/' . $c['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete this category?"><?= csrf_field() ?><button class="btn btn-sm btn-link text-danger p-0">Delete</button></form>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
