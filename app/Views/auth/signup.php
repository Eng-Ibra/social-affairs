<?php $errors = $_SESSION['_errors'] ?? []; unset($_SESSION['_errors']); ?>
<form method="post" action="<?= url('/signup') ?>">
    <?= csrf_field() ?>
    <div class="row g-2">
        <div class="col-12">
            <label class="form-label">Full name <span class="required-mark">*</span></label>
            <input type="text" name="name" class="form-control" value="<?= e(old('name')) ?>" required>
            <?php foreach ($errors['name'] ?? [] as $err): ?><div class="text-danger small"><?= e($err) ?></div><?php endforeach; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label">Email <span class="required-mark">*</span></label>
            <input type="email" name="email" class="form-control" value="<?= e(old('email')) ?>" required>
            <?php foreach ($errors['email'] ?? [] as $err): ?><div class="text-danger small"><?= e($err) ?></div><?php endforeach; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= e(old('phone')) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select">
                <option value="">-- Select --</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Position</label>
            <input type="text" name="position" class="form-control" value="<?= e(old('position')) ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Requested Role <span class="required-mark">*</span></label>
            <select name="role_id" class="form-select" required>
                <option value="">-- Select --</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php foreach ($errors['role_id'] ?? [] as $err): ?><div class="text-danger small"><?= e($err) ?></div><?php endforeach; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label">Password <span class="required-mark">*</span></label>
            <input type="password" name="password" class="form-control" required minlength="8">
            <?php foreach ($errors['password'] ?? [] as $err): ?><div class="text-danger small"><?= e($err) ?></div><?php endforeach; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label">Confirm Password <span class="required-mark">*</span></label>
            <input type="password" name="password_confirmation" class="form-control" required minlength="8">
        </div>
    </div>
    <div class="alert alert-warning small mt-3 mb-3">
        <i class="fa-solid fa-circle-info me-1"></i> Your account will require administrator approval before you can log in.
    </div>
    <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-user-plus me-1"></i> Create Account</button>
    <div class="text-center mt-3 small"><a href="<?= url('/login') ?>">Already have an account? Login</a></div>
</form>
