<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= url($user['avatar']) ?>" class="rounded-circle mb-3" width="100" height="100" style="object-fit:cover;">
                <?php else: ?>
                    <i class="fa-solid fa-circle-user fa-5x text-secondary mb-3"></i>
                <?php endif; ?>
                <h5 class="mb-0"><?= e($user['name']) ?></h5>
                <div class="text-muted small"><?= e($user['position'] ?? '') ?></div>
                <span class="badge bg-success mt-2 text-capitalize"><?= e($user['status']) ?></span>
                <hr>
                <form method="post" action="<?= url('/profile') ?>" enctype="multipart/form-data" class="text-start">
                    <?= csrf_field() ?>
                    <label class="form-label small">Change photo</label>
                    <input type="file" name="avatar" class="form-control form-control-sm mb-2" accept="image/*">
                    <input type="hidden" name="name" value="<?= e($user['name']) ?>">
                    <input type="hidden" name="phone" value="<?= e($user['phone'] ?? '') ?>">
                    <input type="hidden" name="position" value="<?= e($user['position'] ?? '') ?>">
                    <button class="btn btn-sm btn-outline-primary w-100">Upload Photo</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Edit Profile</div>
            <div class="card-body">
                <form method="post" action="<?= url('/profile') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" class="form-control" value="<?= e($user['position'] ?? '') ?>">
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm mt-3">Save Changes</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header fw-semibold">Change Password</div>
            <div class="card-body">
                <form method="post" action="<?= url('/profile/password') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="8">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="new_password_confirmation" class="form-control" required minlength="8">
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm mt-3">Change Password</button>
                </form>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header fw-semibold">Account Info</div>
            <div class="card-body small">
                <div class="row">
                    <div class="col-sm-6"><strong>Created:</strong> <?= format_datetime($user['created_at']) ?></div>
                    <div class="col-sm-6"><strong>Last login:</strong> <?= format_datetime($user['last_login_at'] ?? null) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
