<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($title) ? e($title) . ' — ' : '' ?><?= e(config('app.name')) ?></title>
<link rel="stylesheet" href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/fontawesome/css/all.min.css') ?>">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
<div class="app-shell">
<?php $u = current_user(); ?>
<aside class="sidebar" id="appSidebar">
    <div class="brand"><i class="fa-solid fa-people-roof me-2"></i><?= e(config('app.name')) ?></div>
    <nav class="nav flex-column py-2">
        <a class="nav-link <?= active_nav('dashboard') ?>" href="<?= url('/dashboard') ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>

        <div class="nav-section-title">Registrations</div>
        <?php if (can('sections','view')): ?><a class="nav-link <?= active_nav('m/sections') ?>" href="<?= url('/m/sections') ?>"><i class="fa-solid fa-sitemap"></i> Sections</a><?php endif; ?>
        <?php if (can('camps','view')): ?><a class="nav-link <?= active_nav('m/camps') ?>" href="<?= url('/m/camps') ?>"><i class="fa-solid fa-campground"></i> Camps</a><?php endif; ?>
        <?php if (can('villages','view')): ?><a class="nav-link <?= active_nav('m/villages') ?>" href="<?= url('/m/villages') ?>"><i class="fa-solid fa-house-chimney"></i> Villages</a><?php endif; ?>
        <?php if (can('host_communities','view')): ?><a class="nav-link <?= active_nav('m/host_communities') ?>" href="<?= url('/m/host_communities') ?>"><i class="fa-solid fa-people-group"></i> Host Communities</a><?php endif; ?>
        <?php if (can('refugees','view')): ?><a class="nav-link <?= active_nav('m/refugees') ?>" href="<?= url('/m/refugees') ?>"><i class="fa-solid fa-person-walking-luggage"></i> Refugees</a><?php endif; ?>
        <?php if (can('pwd','view')): ?><a class="nav-link <?= active_nav('m/pwd') ?>" href="<?= url('/m/pwd') ?>"><i class="fa-solid fa-wheelchair"></i> Persons with Disabilities</a><?php endif; ?>
        <?php if (can('beneficiaries','view')): ?><a class="nav-link <?= active_nav('m/beneficiaries') ?>" href="<?= url('/m/beneficiaries') ?>"><i class="fa-solid fa-hand-holding-heart"></i> Beneficiaries</a><?php endif; ?>
        <?php if (can('organizations','view')): ?><a class="nav-link <?= active_nav('m/organizations') ?>" href="<?= url('/m/organizations') ?>"><i class="fa-solid fa-building-shield"></i> Organizations</a><?php endif; ?>
        <?php if (can('schools','view')): ?><a class="nav-link <?= active_nav('m/schools') ?>" href="<?= url('/m/schools') ?>"><i class="fa-solid fa-school"></i> Schools</a><?php endif; ?>
        <?php if (can('health_facilities','view')): ?><a class="nav-link <?= active_nav('m/health_facilities') ?>" href="<?= url('/m/health_facilities') ?>"><i class="fa-solid fa-hospital"></i> Health Facilities</a><?php endif; ?>

        <div class="nav-section-title">Operations</div>
        <?php if (can('projects','view')): ?><a class="nav-link <?= active_nav('m/projects') ?>" href="<?= url('/m/projects') ?>"><i class="fa-solid fa-diagram-project"></i> Projects</a><?php endif; ?>
        <?php if (can('activities','view')): ?><a class="nav-link <?= active_nav('m/activities') ?>" href="<?= url('/m/activities') ?>"><i class="fa-solid fa-list-check"></i> Activities</a><?php endif; ?>
        <?php if (can('needs_assessments','view')): ?><a class="nav-link <?= active_nav('m/needs_assessments') ?>" href="<?= url('/m/needs_assessments') ?>"><i class="fa-solid fa-clipboard-question"></i> Needs Assessments</a><?php endif; ?>
        <?php if (can('priorities','view')): ?><a class="nav-link <?= active_nav('priorities') ?>" href="<?= url('/priorities') ?>"><i class="fa-solid fa-ranking-star"></i> Priorities</a><?php endif; ?>
        <a class="nav-link <?= active_nav('ai-assistant') ?>" href="<?= url('/ai-assistant') ?>"><i class="fa-solid fa-robot"></i> AI Needs Assistant</a>
        <?php if (can('complaints','view')): ?><a class="nav-link <?= active_nav('m/complaints') ?>" href="<?= url('/m/complaints') ?>"><i class="fa-solid fa-comment-dots"></i> Complaints</a><?php endif; ?>
        <?php if (can('events','view')): ?><a class="nav-link <?= active_nav('m/events') ?>" href="<?= url('/m/events') ?>"><i class="fa-solid fa-calendar-days"></i> Events</a><?php endif; ?>
        <a class="nav-link <?= active_nav('calendar') ?>" href="<?= url('/calendar') ?>"><i class="fa-solid fa-calendar-check"></i> Calendar</a>
        <a class="nav-link <?= active_nav('map') ?>" href="<?= url('/map') ?>"><i class="fa-solid fa-map-location-dot"></i> Map</a>

        <div class="nav-section-title">Insights</div>
        <?php if (can('reports','view')): ?><a class="nav-link <?= active_nav('reports') ?>" href="<?= url('/reports') ?>"><i class="fa-solid fa-file-lines"></i> Reports</a><?php endif; ?>
        <?php if (can('audit_logs','view')): ?><a class="nav-link <?= active_nav('audit-logs') ?>" href="<?= url('/audit-logs') ?>"><i class="fa-solid fa-shield-halved"></i> Audit Log</a><?php endif; ?>

        <?php if (can('users','view') || can('roles','manage') || can('settings','manage')): ?>
        <div class="nav-section-title">Administration</div>
        <?php if (can('users','view')): ?><a class="nav-link <?= active_nav('users') ?>" href="<?= url('/users') ?>"><i class="fa-solid fa-users-gear"></i> Users</a><?php endif; ?>
        <?php if (can('roles','manage')): ?><a class="nav-link <?= active_nav('roles') ?>" href="<?= url('/roles') ?>"><i class="fa-solid fa-user-shield"></i> Roles &amp; Permissions</a><?php endif; ?>
        <?php if (can('settings','manage')): ?><a class="nav-link <?= active_nav('settings') ?>" href="<?= url('/settings') ?>"><i class="fa-solid fa-gears"></i> Settings</a><?php endif; ?>
        <?php if (can('settings','manage')): ?><a class="nav-link <?= active_nav('backup') ?>" href="<?= url('/backup') ?>"><i class="fa-solid fa-database"></i> Backup</a><?php endif; ?>
        <?php endif; ?>
    </nav>
</aside>

<div class="main-content">
    <header class="topbar">
        <button class="btn btn-sm btn-outline-secondary" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
        <form action="<?= url('/search') ?>" method="get" class="flex-grow-1" style="max-width:420px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-surface-alt"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="search" name="q" class="form-control" placeholder="Search camps, villages, beneficiaries, organizations…" value="<?= e($_GET['q'] ?? '') ?>">
            </div>
        </form>
        <div class="ms-auto d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary" id="themeToggle" title="Toggle theme"><i class="fa-solid fa-moon" id="themeIcon"></i></button>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary position-relative" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-bell"></i>
                    <?php $unread = \App\Core\Notification::unreadCount($u); ?>
                    <?php if ($unread > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $unread ?></span><?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-2" style="width:340px; max-height:400px; overflow-y:auto;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="small">Notifications</strong>
                        <a href="<?= url('/notifications/read-all') ?>" class="small">Mark all read</a>
                    </div>
                    <?php $notifs = \App\Core\Notification::recentFor($u); ?>
                    <?php if (!$notifs): ?><div class="text-muted small p-2">No notifications yet.</div><?php endif; ?>
                    <?php foreach ($notifs as $n): ?>
                        <a href="<?= url('/notifications/' . $n['id'] . '/open') ?>" class="dropdown-item small border-bottom py-2 <?= $n['is_read'] ? '' : 'fw-semibold' ?>">
                            <?= e($n['title']) ?>
                            <div class="text-muted fw-normal" style="white-space:normal;"><?= e($n['message']) ?></div>
                            <div class="text-muted fw-normal" style="font-size:.72rem;"><?= format_datetime($n['created_at']) ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-circle-user"></i> <?= e($u['name']) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted"><?= e($u['position'] ?? '') ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= url('/profile') ?>"><i class="fa-solid fa-id-card me-2"></i>My Profile</a></li>
                    <li><a class="dropdown-item" href="<?= url('/logout') ?>"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <div class="page-body">
        <?php foreach (get_flashes() as $type => $messages): ?>
            <?php foreach ($messages as $msg): ?>
                <div class="alert alert-<?= $type === 'error' ? 'danger' : e($type) ?> alert-dismissible fade show" data-autohide>
                    <?= e($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php $content(); ?>
    </div>
    <footer class="text-center text-muted small py-3 no-print">
        &copy; <?= date('Y') ?> <?= e(config('app.name')) ?> — Local Government Social Affairs Department
    </footer>
</div>
</div>
<script>window.SAS_CSRF = "<?= csrf_token() ?>";</script>
<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<?php if (!empty($extraScripts)) echo $extraScripts; ?>
</body>
</html>
