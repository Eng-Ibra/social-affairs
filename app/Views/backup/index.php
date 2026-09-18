<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-database me-2"></i>Backup &amp; Data Management</h4>
    <form method="post" action="<?= url('/backup') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-primary"><i class="fa-solid fa-download"></i> Create Backup Now</button></form>
</div>

<div class="alert alert-info small">
    <i class="fa-solid fa-circle-info me-1"></i>
    Backups are full logical SQL dumps saved to <code>storage/backups/</code>. Schedule <code>cron/recalculate.php</code>
    and a periodic backup (e.g. via Windows Task Scheduler or cron) for production use. Database credentials are never exposed in the frontend.
</div>

<div class="card">
    <div class="card-header fw-semibold">Backup History</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Filename</th><th>Size</th><th>Created</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            <?php if (!$backups): ?><tr><td colspan="4" class="text-center text-muted py-4">No backups yet. Click "Create Backup Now" to generate one.</td></tr><?php endif; ?>
            <?php foreach ($backups as $b): ?>
                <tr>
                    <td><?= e($b['name']) ?></td>
                    <td><?= number_format($b['size'] / 1024, 1) ?> KB</td>
                    <td><?= e($b['created_at']) ?></td>
                    <td class="text-end"><a href="<?= url('/backup/' . $b['name'] . '/download') ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-download"></i> Download</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
