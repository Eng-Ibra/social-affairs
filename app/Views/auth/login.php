<form method="post" action="<?= url('/login') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="form-control" value="<?= e(old('email')) ?>" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-right-to-bracket me-1"></i> Login</button>
    <div class="d-flex justify-content-between mt-3 small">
        <a href="<?= url('/forgot-password') ?>">Forgot password?</a>
        <a href="<?= url('/signup') ?>">Create an account</a>
    </div>
</form>
