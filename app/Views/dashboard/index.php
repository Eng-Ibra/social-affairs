<div class="row g-3 mb-1">
    <?php
    $cards = [
        ['label' => 'Camps', 'key' => 'camps', 'icon' => 'fa-campground', 'color' => '#0d6efd', 'link' => '/m/camps'],
        ['label' => 'Villages', 'key' => 'villages', 'icon' => 'fa-house-chimney', 'color' => '#198754', 'link' => '/m/villages'],
        ['label' => 'Host Communities', 'key' => 'host_communities', 'icon' => 'fa-people-group', 'color' => '#6f42c1', 'link' => '/m/host_communities'],
        ['label' => 'Organizations', 'key' => 'organizations', 'icon' => 'fa-building-shield', 'color' => '#fd7e14', 'link' => '/m/organizations'],
        ['label' => 'Projects', 'key' => 'projects', 'icon' => 'fa-diagram-project', 'color' => '#0dcaf0', 'link' => '/m/projects'],
        ['label' => 'Activities', 'key' => 'activities', 'icon' => 'fa-list-check', 'color' => '#20c997', 'link' => '/m/activities'],
        ['label' => 'Complaints', 'key' => 'complaints', 'icon' => 'fa-comment-dots', 'color' => '#dc3545', 'link' => '/m/complaints'],
        ['label' => 'Events', 'key' => 'events', 'icon' => 'fa-calendar-days', 'color' => '#6610f2', 'link' => '/m/events'],
        ['label' => 'Beneficiaries', 'key' => 'beneficiaries', 'icon' => 'fa-hand-holding-heart', 'color' => '#d63384', 'link' => '/m/beneficiaries'],
        ['label' => 'Refugees', 'key' => 'refugees', 'icon' => 'fa-person-walking-luggage', 'color' => '#0a58ca', 'link' => '/m/refugees'],
        ['label' => 'Persons with Disabilities', 'key' => 'pwd', 'icon' => 'fa-wheelchair', 'color' => '#795548', 'link' => '/m/pwd'],
        ['label' => 'Schools', 'key' => 'schools', 'icon' => 'fa-school', 'color' => '#0891b2', 'link' => '/m/schools'],
        ['label' => 'Health Facilities', 'key' => 'health_facilities', 'icon' => 'fa-hospital', 'color' => '#be123c', 'link' => '/m/health_facilities'],
        ['label' => 'High Priority Needs', 'key' => 'priorities_high', 'icon' => 'fa-ranking-star', 'color' => '#b91c1c', 'link' => '/priorities'],
    ];
    ?>
    <?php foreach ($cards as $c): ?>
    <div class="col-6 col-md-4 col-xl-3">
        <a href="<?= url($c['link']) ?>" class="text-decoration-none">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:<?= $c['color'] ?>22; color:<?= $c['color'] ?>;"><i class="fa-solid <?= $c['icon'] ?>"></i></div>
            <div>
                <div class="stat-value"><?= number_format($counts[$c['key']]) ?></div>
                <div class="text-muted small"><?= e($c['label']) ?></div>
            </div>
        </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-header fw-semibold">Needs by Sector</div>
            <div class="card-body"><canvas id="chartNeeds" height="220"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-header fw-semibold">Complaint Status</div>
            <div class="card-body"><canvas id="chartComplaints" height="220"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-header fw-semibold">Project Status</div>
            <div class="card-body"><canvas id="chartProjects" height="200"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-header fw-semibold">Activity Status</div>
            <div class="card-body"><canvas id="chartActivities" height="200"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-header fw-semibold">Population by Gender</div>
            <div class="card-body"><canvas id="chartGender" height="200"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-header fw-semibold">Beneficiaries by Vulnerability</div>
            <div class="card-body"><canvas id="chartBeneficiary" height="220"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold d-flex justify-content-between">Top Priority Needs <a href="<?= url('/priorities') ?>" class="small">View all</a></div>
            <div class="list-group list-group-flush">
                <?php if (!$topPriorities): ?><div class="list-group-item text-muted small">No assessments recorded yet.</div><?php endif; ?>
                <?php foreach ($topPriorities as $i => $p): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-secondary me-2">#<?= $i + 1 ?></span>
                        <?= e($p['category_name']) ?>
                        <div class="text-muted small">Affected: <?= number_format($p['total_affected']) ?> · Locations: <?= $p['locations_count'] ?></div>
                    </div>
                    <?php $lvl = $p['override_level'] ?: $p['computed_level']; ?>
                    <span class="badge bg-<?= $lvl === 'high' ? 'danger' : ($lvl === 'medium' ? 'warning' : 'secondary') ?> text-capitalize"><?= $lvl ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script><script>' . '
const isDark = document.documentElement.getAttribute("data-theme") === "dark";
const gridColor = isDark ? "rgba(255,255,255,.08)" : "rgba(0,0,0,.06)";
const textColor = isDark ? "#c9d3e6" : "#495166";
Chart.defaults.color = textColor;
Chart.defaults.borderColor = gridColor;
const palette = ["#0d6efd","#198754","#fd7e14","#6f42c1","#dc3545","#0dcaf0","#d63384","#20c997","#6610f2","#795548"];

new Chart(document.getElementById("chartNeeds"), {
  type: "bar",
  data: { labels: ' . json_encode(array_column($needsBySector, 'label')) . ', datasets: [{ label: "Reports", data: ' . json_encode(array_map('intval', array_column($needsBySector, 'total'))) . ', backgroundColor: "#0d6efd" }] },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
new Chart(document.getElementById("chartComplaints"), {
  type: "doughnut",
  data: { labels: ' . json_encode(array_map('ucfirst', array_column($complaintStatus, 'label'))) . ', datasets: [{ data: ' . json_encode(array_map('intval', array_column($complaintStatus, 'total'))) . ', backgroundColor: palette }] }
});
new Chart(document.getElementById("chartProjects"), {
  type: "pie",
  data: { labels: ' . json_encode(array_map('ucfirst', array_column($projectStatus, 'label'))) . ', datasets: [{ data: ' . json_encode(array_map('intval', array_column($projectStatus, 'total'))) . ', backgroundColor: palette }] }
});
new Chart(document.getElementById("chartActivities"), {
  type: "pie",
  data: { labels: ' . json_encode(array_map('ucfirst', array_column($activityStatus, 'label'))) . ', datasets: [{ data: ' . json_encode(array_map('intval', array_column($activityStatus, 'total'))) . ', backgroundColor: palette }] }
});
new Chart(document.getElementById("chartGender"), {
  type: "doughnut",
  data: { labels: ["Male","Female"], datasets: [{ data: [' . (int)($populationByGender['male'] ?? 0) . ',' . (int)($populationByGender['female'] ?? 0) . '], backgroundColor: ["#0d6efd","#d63384"] }] }
});
new Chart(document.getElementById("chartBeneficiary"), {
  type: "bar",
  data: { labels: ' . json_encode(array_column($beneficiaryByVulnerability, 'label')) . ', datasets: [{ label: "Beneficiaries", data: ' . json_encode(array_map('intval', array_column($beneficiaryByVulnerability, 'total'))) . ', backgroundColor: "#198754" }] },
  options: { indexAxis: "y", plugins: { legend: { display: false } } }
});
' . '</script>';
?>
