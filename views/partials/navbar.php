<?php
$role = $_SESSION['user']['role'];
$roleLabels = [
    'community_organizer'       => 'Community Organizer',
    'community_affairs_worker'  => 'Community Affairs Worker',
    'agricultural_technologist' => 'Agricultural Technologist',
    'mao'                       => 'Municipal Agriculture Office',
];
$roleLabel = $roleLabels[$role] ?? 'User';

$notifModel = new Notification($conn);
$unreadCount = $notifModel->countUnread($_SESSION['user']['id']);

// Detect current action for active highlight
$currentAction = $_GET['action'] ?? 'dashboard';

// Helper: returns CSS classes for nav link
function navLink($action, $current) {
    $base = 'nav-link px-3 py-1 rounded-2 fw-500';
    if ($action === $current || ($action === 'dashboard' && $current === 'dashboard')) {
        return $base . ' nav-active';
    }
    return $base . ' text-white';
}
?>

<style>
.ps-navbar { background: #2d5a27; padding: 0.5rem 0; }

.nav-active {
    background: rgba(255,255,255,0.18) !important;
    color: #fff !important;
    border-bottom: 2.5px solid #a8d5a2;
}
.navbar-nav .nav-link {
    color: rgba(255,255,255,0.82) !important;
    font-size: 0.875rem;
    transition: background 0.18s, color 0.18s;
    border-bottom: 2.5px solid transparent;
}
.navbar-nav .nav-link:hover {
    background: rgba(255,255,255,0.12);
    color: #fff !important;
    border-radius: 6px;
}
.navbar-nav .nav-active {
    color: #fff !important;
}
</style>

<nav class="navbar navbar-expand-lg ps-navbar shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold text-white d-flex align-items-center gap-2" href="index.php?action=dashboard">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8.416.223a.5.5 0 0 0-.832 0l-3 4.5A.5.5 0 0 0 5 5.5h.382l-2.486 3.73a.5.5 0 0 0 .416.77h1.124l-2.14 3.21a.5.5 0 0 0 .416.77h11.416a.5.5 0 0 0 .416-.77l-2.14-3.21h1.124a.5.5 0 0 0 .416-.77L10.618 5.5H11a.5.5 0 0 0 .416-.77l-3-4.5z"/>
            </svg>
            PlantSphere
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto gap-1">
                <?php if ($role === 'community_organizer'): ?>
                    <li class="nav-item"><a class="<?= navLink('dashboard', $currentAction) ?>" href="index.php?action=dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="<?= navLink('submit_request', $currentAction) ?>" href="index.php?action=submit_request">Submit Request</a></li>
                    <li class="nav-item"><a class="<?= navLink('my_requests', $currentAction) ?>" href="index.php?action=my_requests">My Requests</a></li>
                    <li class="nav-item"><a class="<?= navLink('rsbsa_form', $currentAction) ?>" href="index.php?action=rsbsa_form">RSBSA Registration</a></li>

                <?php elseif ($role === 'community_affairs_worker'): ?>
                    <li class="nav-item"><a class="<?= navLink('dashboard', $currentAction) ?>" href="index.php?action=dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="<?= navLink('view_requests', $currentAction) ?>" href="index.php?action=view_requests">View Requests</a></li>
                    <li class="nav-item"><a class="<?= navLink('seedling_info', $currentAction) ?>" href="index.php?action=seedling_info">Seedling Materials</a></li>

                <?php elseif ($role === 'agricultural_technologist'): ?>
                    <li class="nav-item"><a class="<?= navLink('dashboard', $currentAction) ?>" href="index.php?action=dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="<?= navLink('check_rsbsa', $currentAction) ?>" href="index.php?action=check_rsbsa">RSBSA Registry</a></li>
                    <li class="nav-item"><a class="<?= navLink('verify_materials', $currentAction) ?>" href="index.php?action=verify_materials">Planting Materials</a></li>
                    <li class="nav-item"><a class="<?= navLink('site_validation', $currentAction) ?>" href="index.php?action=site_validation">Site Validation</a></li>
                    <li class="nav-item"><a class="<?= navLink('prepare_slip', $currentAction) ?>" href="index.php?action=prepare_slip">Request Slips</a></li>

                <?php elseif ($role === 'mao'): ?>
                    <li class="nav-item"><a class="<?= navLink('dashboard', $currentAction) ?>" href="index.php?action=dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="<?= navLink('review_slips', $currentAction) ?>" href="index.php?action=review_slips">Review Slips</a></li>
                    <li class="nav-item"><a class="<?= navLink('route_request', $currentAction) ?>" href="index.php?action=route_request">Routed Requests</a></li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <!-- Notifications -->
                <div class="position-relative">
                    <button class="btn btn-sm btn-outline-light position-relative" id="notifBtn" onclick="toggleNotif()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917z"/>
                        </svg>
                        <?php if ($unreadCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </button>
                    <div id="notifDropdown" class="notif-dropdown d-none shadow-lg">
                        <div class="notif-header d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <strong class="small">Notifications</strong>
                            <a href="index.php?action=mark_notif_read" class="small text-muted text-decoration-none">Mark all read</a>
                        </div>
                        <?php
                        $notifs = $notifModel->getByUser($_SESSION['user']['id'], 5);
                        if (empty($notifs)): ?>
                            <div class="px-3 py-3 text-muted small text-center">No notifications</div>
                        <?php else: foreach ($notifs as $n): ?>
                            <div class="notif-item px-3 py-2 border-bottom <?= $n['is_read'] ? '' : 'notif-unread' ?>">
                                <div class="small fw-semibold"><?= htmlspecialchars($n['title']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($n['message']) ?></div>
                                <div class="x-small text-muted mt-1"><?= date('M d, Y g:i A', strtotime($n['created_at'])) ?></div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

                <!-- User info -->
                <div class="text-white small">
                    <div class="fw-semibold"><?= htmlspecialchars($_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['lastname']) ?></div>
                    <div class="opacity-75" style="font-size:0.72rem;"><?= $roleLabel ?></div>
                </div>

                <a href="index.php?action=logout" class="btn btn-sm btn-outline-light">Logout</a>
            </div>
        </div>
    </div>
</nav>

<script>
function toggleNotif() {
    document.getElementById('notifDropdown').classList.toggle('d-none');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('#notifBtn') && !e.target.closest('#notifDropdown')) {
        document.getElementById('notifDropdown')?.classList.add('d-none');
    }
});
</script>
