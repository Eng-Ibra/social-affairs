<p class="text-muted small">Enter your account email and we will send you a password reset link.</p>
<form method="post" action="<?= url('/forgot-password') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="form-control" required autofocus>
    </div>
    <button class="btn btn-primary w-100" type="submit">Send Reset Link</button>
    <div class="text-center mt-3 small"><a href="<?= url('/login') ?>">Back to login</a></div>
</form>
