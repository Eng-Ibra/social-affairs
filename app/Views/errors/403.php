<div class="card">
  <div class="card-body text-center py-5">
    <i class="fa-solid fa-lock fa-3x text-danger mb-3"></i>
    <h4>Access Denied</h4>
    <p class="text-muted">Your role does not have permission to <strong><?= e($capability ?? '') ?></strong> the <strong><?= e($module ?? '') ?></strong> module.</p>
    <a href="<?= url('/dashboard') ?>" class="btn btn-primary btn-sm">Back to Dashboard</a>
  </div>
</div>
