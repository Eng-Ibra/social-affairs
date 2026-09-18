<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fa-solid fa-robot me-2"></i>AI Needs Assessment Assistant</h4>
    <span class="text-muted small">Generated <?= e($a['generated_at']) ?></span>
</div>

<div class="alert alert-secondary small">
    <i class="fa-solid fa-shield-halved me-1"></i>
    This assistant only reports figures directly computed from recorded needs assessments — it never invents statistics.
    Sections marked <span class="badge bg-primary">Data</span> are direct database aggregates; sections marked
    <span class="badge bg-info text-dark">Recommendation</span> are generated suggestions for your review.
</div>

<div class="card mb-3">
    <div class="card-header fw-semibold"><span class="badge bg-info text-dark me-2">Recommendation</span>Management Summary</div>
    <div class="card-body">
        <p class="mb-0"><?= e($a['summary']) ?></p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold"><span class="badge bg-primary me-2">Data</span>Most Common Needs (by report count)</div>
            <ul class="list-group list-group-flush">
                <?php if (!$a['most_common']): ?><li class="list-group-item text-muted small">No data yet.</li><?php endif; ?>
                <?php foreach ($a['most_common'] as $n): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?= e($n['category']) ?></span>
                    <span class="text-muted small"><?= $n['reports'] ?> reports · <?= number_format($n['affected']) ?> affected</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold"><span class="badge bg-primary me-2">Data</span>Most Urgent Needs (avg. urgency ≥ 3.5/5)</div>
            <ul class="list-group list-group-flush">
                <?php if (!$a['most_urgent']): ?><li class="list-group-item text-muted small">No urgent needs recorded.</li><?php endif; ?>
                <?php foreach ($a['most_urgent'] as $n): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?= e($n['category']) ?></span>
                    <span class="text-muted small">Urgency <?= number_format($n['avg_urgency'], 1) ?>/5 · Severity <?= number_format($n['avg_severity'], 1) ?>/5</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold"><span class="badge bg-primary me-2">Data</span>Most Affected Locations</div>
            <ul class="list-group list-group-flush">
                <?php if (!$a['most_affected_locations']): ?><li class="list-group-item text-muted small">No data yet.</li><?php endif; ?>
                <?php foreach ($a['most_affected_locations'] as $l): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?= e($l['label']) ?> <span class="text-muted small">(<?= e($l['type']) ?>)</span></span>
                    <span class="text-muted small"><?= number_format($l['affected']) ?> affected</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold"><span class="badge bg-primary me-2">Data</span>Vulnerable Groups on Record</div>
            <ul class="list-group list-group-flush">
                <?php if (!$a['vulnerable_groups']): ?><li class="list-group-item text-muted small">No vulnerability data recorded.</li><?php endif; ?>
                <?php foreach ($a['vulnerable_groups'] as $v): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?= e($v['label']) ?></span><span class="text-muted small"><?= $v['total'] ?> records</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold"><span class="badge bg-primary me-2">Data</span>Trends (last 30 vs. prior 30 days)</div>
            <ul class="list-group list-group-flush">
                <?php if (!$a['trends']): ?><li class="list-group-item text-muted small">Not enough historical data yet.</li><?php endif; ?>
                <?php foreach ($a['trends'] as $t): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= e($t['category']) ?></span>
                    <span class="small">
                        <?= $t['current_30d'] ?> vs <?= $t['previous_30d'] ?>
                        <?php $icon = ['increasing'=>'fa-arrow-trend-up text-danger','decreasing'=>'fa-arrow-trend-down text-success','stable'=>'fa-minus text-muted'][$t['direction']]; ?>
                        <i class="fa-solid <?= $icon ?> ms-1"></i>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold"><span class="badge bg-primary me-2">Data</span>Reported Service Gaps (most recent)</div>
            <ul class="list-group list-group-flush">
                <?php if (!$a['service_gaps']): ?><li class="list-group-item text-muted small">No service gaps reported yet.</li><?php endif; ?>
                <?php foreach ($a['service_gaps'] as $g): ?>
                <li class="list-group-item">
                    <div class="fw-semibold small"><?= e($g['category']) ?> — <?= format_date($g['assessment_date']) ?></div>
                    <div class="small text-muted"><?= e($g['service_gaps']) ?></div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold"><span class="badge bg-info text-dark me-2">Recommendation</span>Suggested Interventions</div>
            <ul class="list-group list-group-flush">
                <?php if (!$a['interventions']): ?><li class="list-group-item text-muted small">No recommended interventions recorded yet.</li><?php endif; ?>
                <?php foreach ($a['interventions'] as $i): ?>
                <li class="list-group-item">
                    <div class="fw-semibold small"><?= e($i['category']) ?></div>
                    <div class="small text-muted"><?= e($i['recommended_intervention']) ?></div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold"><span class="badge bg-info text-dark me-2">Recommendation</span>Potentially Relevant Organizations</div>
            <div class="card-body">
                <?php if (!$a['relevant_organizations']): ?><p class="text-muted small mb-0">No organizations matched current priority sectors yet — register organizations with sector/area info to enable this.</p><?php endif; ?>
                <?php foreach ($a['relevant_organizations'] as $category => $orgs): ?>
                    <div class="mb-2">
                        <div class="fw-semibold small"><?= e($category) ?></div>
                        <?php foreach ($orgs as $o): ?>
                            <span class="badge bg-secondary me-1 mb-1"><?= e($o['org_name']) ?><?= $o['acronym'] ? ' (' . e($o['acronym']) . ')' : '' ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
