<?php
$role         = $_SESSION['user']['role'];
$currentAction = $_GET['action'] ?? 'dashboard';
$notifModel   = new Notification($conn);
$unreadCount  = $notifModel->countUnread($_SESSION['user']['id']);

$roleLabels = [
    'community_organizer'       => 'Community Organizer',
    'community_affairs_worker'  => 'Community Affairs Worker',
    'agricultural_technologist' => 'Agricultural Technologist',
    'mao'                       => 'Municipal Agriculture Office',
    'admin'                     => 'System Administrator',
    'barangay_captain'          => 'Barangay Captain',
    'department_head'           => 'Department Head',
    'nursery'                   => 'Nursery Staff',
];
$roleLabel = $roleLabels[$role] ?? 'User';

// Nav items per role
$navItems = [];
if ($role === 'community_organizer') {
    $navItems = [
        ['action' => 'dashboard',        'label' => 'Dashboard',           'icon' => 'bi-speedometer2'],
        ['action' => 'submit_request',   'label' => 'Submit Proposal',     'icon' => 'bi-file-earmark-plus'],
        ['action' => 'my_requests',      'label' => 'My Proposals',        'icon' => 'bi-clipboard-list'],
        ['action' => 'rsbsa_form',       'label' => 'RSBSA Registration',  'icon' => 'bi-person-vcard'],
        ['action' => 'nursery_survival', 'label' => 'Survival Monitoring', 'icon' => 'bi-tree'],
    ];
} elseif ($role === 'community_affairs_worker') {
    $navItems = [
        ['action' => 'dashboard',     'label' => 'Dashboard',          'icon' => 'bi-speedometer2'],
        ['action' => 'view_requests', 'label' => 'View Requests',       'icon' => 'bi-inbox'],
        ['action' => 'seedling_info', 'label' => 'Seedling Materials',  'icon' => 'bi-tree'],
    ];
} elseif ($role === 'agricultural_technologist') {
    $navItems = [
        ['action' => 'dashboard',            'label' => 'Dashboard',              'icon' => 'bi-speedometer2'],
        ['action' => 'check_rsbsa',          'label' => 'RSBSA Registry',         'icon' => 'bi-person-check'],
        ['action' => 'verify_materials',     'label' => 'Planting Materials',     'icon' => 'bi-box-seam'],
        ['action' => 'site_validation',      'label' => 'Site Validation',        'icon' => 'bi-geo-alt'],
        ['action' => 'prepare_slip',         'label' => 'Request Slips',          'icon' => 'bi-file-earmark-text'],
        ['action' => 'nursery_guidance_log', 'label' => 'Guidance Log',           'icon' => 'bi-journal-check'],
    ];
} elseif ($role === 'mao') {
    $navItems = [
        ['action' => 'dashboard',     'label' => 'Dashboard',         'icon' => 'bi-speedometer2'],
        ['action' => 'review_slips',  'label' => 'Review Slips',      'icon' => 'bi-file-earmark-check'],
        ['action' => 'route_request', 'label' => 'Routed Requests',   'icon' => 'bi-send'],
    ];
} elseif ($role === 'admin') {
    $navItems = [
        ['action' => 'dashboard',            'label' => 'Dashboard',       'icon' => 'bi-speedometer2'],
        ['action' => 'admin_users',          'label' => 'Manage Users',    'icon' => 'bi-people'],
        ['action' => 'admin_sessions',       'label' => 'User Sessions',   'icon' => 'bi-clock-history'],
        ['action' => 'admin_login_logs',     'label' => 'Session Logs',    'icon' => 'bi-shield-exclamation'],
        ['action' => 'admin_activity_logs',  'label' => 'Activity Logs',   'icon' => 'bi-journal-text'],
        ['action' => 'admin_export_logs',    'label' => 'Export / DLP',    'icon' => 'bi-lock'],
    ];
} elseif ($role === 'barangay_captain') {
    $navItems = [
        ['action' => 'dashboard',          'label' => 'Dashboard',         'icon' => 'bi-speedometer2'],
        ['action' => 'captain_proposals',  'label' => 'Review Proposals',  'icon' => 'bi-envelope-check'],
        ['action' => 'captain_history',    'label' => 'Approval History',  'icon' => 'bi-clock-history'],
    ];
} elseif ($role === 'department_head') {
    $navItems = [
        ['action' => 'dashboard',          'label' => 'Dashboard',         'icon' => 'bi-speedometer2'],
        ['action' => 'depthead_finalize',  'label' => 'Finalize Requests', 'icon' => 'bi-check2-square'],
        ['action' => 'depthead_history',   'label' => 'History',           'icon' => 'bi-journal-check'],
    ];
} elseif ($role === 'nursery') {
    $navItems = [
        ['action' => 'dashboard',        'label' => 'Dashboard',          'icon' => 'bi-speedometer2'],
        ['action' => 'nursery_release',  'label' => 'Release Seed Packs', 'icon' => 'bi-box-seam'],
        ['action' => 'nursery_history',  'label' => 'Release History',    'icon' => 'bi-clock-history'],
    ];
}
?>

<!-- ══ SIDEBAR ══ -->
<aside class="ps-sidebar" id="psSidebar">

    <!-- Brand -->
    <div class="ps-sidebar-brand">
        <div class="ps-sidebar-brand-icon">
            <i class="bi bi-tree-fill"></i>
        </div>
        <div class="ps-sidebar-brand-text">
            <span class="brand-main">Plant</span><span class="brand-accent">Sphere</span>
        </div>
        <button class="ps-sidebar-toggle d-lg-none" id="sidebarClose" aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- User card -->
    <div class="ps-sidebar-user">
        <div class="ps-sidebar-avatar">
            <?= strtoupper(substr($_SESSION['user']['firstname'], 0, 1) . substr($_SESSION['user']['lastname'], 0, 1)) ?>
        </div>
        <div class="ps-sidebar-user-info">
            <div class="ps-sidebar-user-name"><?= htmlspecialchars($_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['lastname']) ?></div>
            <div class="ps-sidebar-user-role"><?= $roleLabel ?></div>
        </div>
    </div>

    <!-- Nav -->
    <nav class="ps-sidebar-nav">
        <div class="ps-sidebar-nav-label">Menu</div>
        <?php foreach ($navItems as $item): 
            $isActive = $currentAction === $item['action'];
        ?>
        <a href="index.php?action=<?= $item['action'] ?>"
           class="ps-sidebar-nav-item <?= $isActive ? 'active' : '' ?>">
            <i class="bi <?= $item['icon'] ?>"></i>
            <span><?= $item['label'] ?></span>
            <?php if ($item['action'] === 'review_slips' && isset($pendingSlipsCount) && $pendingSlipsCount > 0): ?>
                <span class="ps-sidebar-badge"><?= $pendingSlipsCount ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- Bottom: notifications + logout -->
    <div class="ps-sidebar-bottom">
        <!-- Notifications -->
        <div class="ps-sidebar-notif-wrap">
            <button class="ps-sidebar-nav-item w-100 border-0 bg-transparent text-start"
                    id="sidebarNotifBtn" onclick="toggleSidebarNotif()" style="cursor:pointer;">
                <i class="bi bi-bell"></i>
                <span>Notifications</span>
                <?php if ($unreadCount > 0): ?>
                    <span class="ps-sidebar-badge"><?= $unreadCount ?></span>
                <?php endif; ?>
            </button>
            <div class="ps-sidebar-notif-dropdown d-none" id="sidebarNotifDropdown">
                <div class="ps-notif-header">
                    <span class="fw-semibold small">Notifications</span>
                    <a href="index.php?action=mark_notif_read" class="small text-muted text-decoration-none">Mark all read</a>
                </div>
                <?php
                $notifs = $notifModel->getByUser($_SESSION['user']['id'], 5);
                if (empty($notifs)): ?>
                    <div class="px-3 py-3 text-muted small text-center">No notifications</div>
                <?php else: foreach ($notifs as $n): ?>
                    <div class="ps-notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
                        <div class="small fw-semibold"><?= htmlspecialchars($n['title']) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($n['message']) ?></div>
                        <div class="x-small text-muted mt-1"><?= date('M d, g:i A', strtotime($n['created_at'])) ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <a href="index.php?action=logout" class="ps-sidebar-nav-item ps-sidebar-logout">
            <i class="bi bi-box-arrow-left"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

<!-- Mobile overlay -->
<div class="ps-sidebar-overlay d-none" id="sidebarOverlay" onclick="closeSidebar()"></div>
