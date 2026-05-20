<?php
$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../partials/layout_head.php';

$userModel  = new User($conn);
$secLog     = new SecurityLog($conn);
$userStats  = $userModel->getStats();
$secStats   = $secLog->getStats();
$recentLogs = $secLog->getLoginLogs(8);
$recentAct  = $secLog->getActivityLogs(8);
$activeSessions = $secLog->countActiveSessions();
?>

<div class="ps-page-header">
    <h2>System Administration</h2>
    <p>Monitor security events, manage users, and oversee system activity.</p>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card">
            <div class="stat-value"><?= array_sum($userStats['byRole']) ?></div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-red">
            <div class="stat-value"><?= $secStats['failed_logins_24h'] ?></div>
            <div class="stat-label">Failed Logins (24h)</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-yellow">
            <div class="stat-value"><?= $userStats['locked'] ?></div>
            <div class="stat-label">Locked Accounts</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-teal">
            <div class="stat-value"><?= $secStats['activities_24h'] ?></div>
            <div class="stat-label">Activities (24h)</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-blue">
            <div class="stat-value"><?= $secStats['success_logins_24h'] ?></div>
            <div class="stat-label">Successful Logins (24h)</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-red">
            <div class="stat-value"><?= $secStats['blocked_exports'] ?></div>
            <div class="stat-label">Blocked Exports</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-yellow">
            <div class="stat-value"><?= $userStats['inactive'] ?></div>
            <div class="stat-label">Inactive Accounts</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card">
            <div class="stat-value"><?= $userStats['byRole']['admin'] ?? 0 ?></div>
            <div class="stat-label">Admin Accounts</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card" style="border-left-color:#198754;">
            <div class="stat-value" style="color:#198754;"><?= $activeSessions ?></div>
            <div class="stat-label">Active Sessions Now</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Quick Actions -->
    <div class="col-md-3">
        <div class="ps-card mb-4">
            <h6 class="fw-bold text-ps-green mb-3">Quick Actions</h6>
            <div class="d-grid gap-2">
                <a href="index.php?action=admin_users"      class="btn btn-ps-primary">Manage Users</a>
                <a href="index.php?action=admin_login_logs" class="btn btn-outline-secondary">Login Logs</a>
                <a href="index.php?action=admin_activity_logs" class="btn btn-outline-secondary">Activity Logs</a>
                <a href="index.php?action=admin_export_logs"   class="btn btn-outline-secondary">Export Logs</a>
            </div>
        </div>

        <!-- Users by Role -->
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Users by Role</h6>
            <?php
            $roleLabels = [
                'admin'                     => 'Admin',
                'community_organizer'       => 'Community Organizer',
                'community_affairs_worker'  => 'Affairs Worker',
                'agricultural_technologist' => 'Agri Technologist',
                'mao'                       => 'MAO',
            ];
            foreach ($roleLabels as $key => $label):
                $count = $userStats['byRole'][$key] ?? 0;
            ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small"><?= $label ?></span>
                <span class="ps-badge ps-badge-verified"><?= $count ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Session Logs -->
    <div class="col-md-9">
        <div class="ps-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-ps-green">Recent Session Events</h6>
                <a href="index.php?action=admin_login_logs" class="small text-ps-green text-decoration-none">View all →</a>
            </div>
            <div class="table-responsive">
                <table class="ps-table">
                    <thead><tr><th>Event</th><th>User</th><th>Email</th><th>IP Address</th><th>Time</th></tr></thead>
                    <tbody>
                        <?php
                        $evtConfig = [
                            'success' => ['label'=>'Login',  'badge'=>'ps-badge-approved',    'icon'=>'bi-box-arrow-in-right'],
                            'logout'  => ['label'=>'Logout', 'badge'=>'ps-badge-under_review','icon'=>'bi-box-arrow-left'],
                            'failed'  => ['label'=>'Failed', 'badge'=>'ps-badge-pending',     'icon'=>'bi-x-circle'],
                            'locked'  => ['label'=>'Locked', 'badge'=>'ps-badge-rejected',    'icon'=>'bi-lock'],
                        ];
                        foreach ($recentLogs as $log):
                            $cfg = $evtConfig[$log['status']] ?? ['label'=>ucfirst($log['status']),'badge'=>'ps-badge-pending','icon'=>'bi-circle'];
                        ?>
                        <tr>
                            <td>
                                <span class="d-flex align-items-center gap-1">
                                    <i class="bi <?= $cfg['icon'] ?> small"></i>
                                    <span class="ps-badge <?= $cfg['badge'] ?>"><?= $cfg['label'] ?></span>
                                </span>
                            </td>
                            <td class="small fw-semibold">
                                <?= $log['firstname'] ? htmlspecialchars($log['firstname'] . ' ' . $log['lastname']) : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td class="small"><?= htmlspecialchars($log['email']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($log['ip_address']) ?></td>
                            <td class="small text-muted"><?= date('M d, g:i A', strtotime($log['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentLogs)): ?>
                        <tr><td colspan="5" class="text-center text-muted small py-3">No session logs yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="ps-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-ps-green">Recent Activity</h6>
                <a href="index.php?action=admin_activity_logs" class="small text-ps-green text-decoration-none">View all →</a>
            </div>
            <div class="table-responsive">
                <table class="ps-table">
                    <thead><tr><th>User</th><th>Action</th><th>Module</th><th>Description</th><th>Time</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentAct as $act): ?>
                        <tr>
                            <td class="small fw-semibold"><?= htmlspecialchars($act['firstname'] . ' ' . $act['lastname']) ?></td>
                            <td><span class="ps-badge ps-badge-under_review"><?= htmlspecialchars($act['action']) ?></span></td>
                            <td class="small"><?= htmlspecialchars($act['module']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars(substr($act['description'], 0, 60)) ?>...</td>
                            <td class="small text-muted"><?= date('M d, g:i A', strtotime($act['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentAct)): ?>
                        <tr><td colspan="5" class="text-center text-muted small py-3">No activity logs yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
