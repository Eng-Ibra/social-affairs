<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-ranking-star me-2"></i>Priority Analysis</h4>
    <a href="<?= url('/ai-assistant') ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-robot"></i> AI Needs Assistant</a>
</div>

<div class="row g-3 mb-1">
    <div class="col-md-4"><div class="stat-card"><div class="stat-value"><?= number_format($totalAffected) ?></div><div class="text-muted small">Total People Affected (reported)</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-value"><?= number_format($unresolvedCount) ?></div><div class="text-muted small">Unresolved Needs</div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-value"><?= number_format($totalAssessments) ?></div><div class="text-muted small">Total Needs Assessments</div></div></div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header fw-semibold">Priority Ranking (Automatically Calculated)</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead><tr><th>#</th><th>Need Category</th><th>Affected</th><th>Locations</th><th>Reports</th><th>Score</th><th>Level</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$priorities): ?><tr><td colspan="8" class="text-center text-muted py-4">No needs assessments recorded yet.</td></tr><?php endif; ?>
                    <?php foreach ($priorities as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($p['category_name']) ?></td>
                            <td><?= number_format($p['total_affected']) ?></td>
                            <td><?= $p['locations_count'] ?></td>
                            <td><?= $p['reports_count'] ?></td>
                            <td><?= number_format($p['computed_score'], 1) ?></td>
                            <td>
                                <?php $lvl = $p['effective_level']; ?>
                                <span class="badge bg-<?= $lvl === 'high' ? 'danger' : ($lvl === 'medium' ? 'warning' : 'secondary') ?> text-capitalize"><?= $lvl ?></span>
                                <?php if ($p['override_level']): ?><i class="fa-solid fa-user-pen text-muted ms-1" title="Manually overridden"></i><?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($canOverride): ?>
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#overrideModal<?= $p['id'] ?>"><i class="fa-solid fa-pen"></i></button>
                                <div class="modal fade" id="overrideModal<?= $p['id'] ?>">
                                    <div class="modal-dialog"><div class="modal-content">
                                    <form method="post" action="<?= url('/priorities/' . $p['id'] . '/override') ?>">
                                        <?= csrf_field() ?>
                                        <div class="modal-header"><h6 class="modal-title">Override Priority: <?= e($p['category_name']) ?></h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <div class="modal-body">
                                            <label class="form-label small">New Level</label>
                                            <select name="level" class="form-select mb-2" required>
                                                <option value="high" <?= $lvl==='high'?'selected':'' ?>>High</option>
                                                <option value="medium" <?= $lvl==='medium'?'selected':'' ?>>Medium</option>
                                                <option value="low" <?= $lvl==='low'?'selected':'' ?>>Low</option>
                                            </select>
                                            <label class="form-label small">Justification (required, logged for audit)</label>
                                            <textarea name="reason" class="form-control" rows="2" required></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button class="btn btn-sm btn-primary">Save Override</button>
                                        </div>
                                    </form>
                                    </div></div>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Needs by Location Type</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($needsByLocationType as $n): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-capitalize"><?= str_replace('_',' ',e($n['label'])) ?></span>
                    <span><?= $n['total'] ?> reports · <?= number_format($n['affected']) ?> affected</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="card">
            <div class="card-header fw-semibold">Needs by Sector</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($needsBySector as $n): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?= e($n['label']) ?></span>
                    <span><?= $n['total'] ?> reports · <?= number_format($n['affected']) ?> affected</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
