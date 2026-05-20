<?php
$pageTitle = 'Session Logs';
include __DIR__ . '/../partials/layout_head.php';

$secLog = new SecurityLog($conn);
$filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$logs = $secLog->getSessionTimeline(500);

// Apply filter
if ($filter !== 'all') {
    $logs = array_filter($logs, fn($l) => $l['status'] === $filter);
}

// Apply search
if ($search !== '') {
    $logs = array_filter($logs, function($l) use ($search) {
        $name = strtolower(($l['firstname'] ?? '') . ' ' . ($l['lastname'] ?? ''));
        return str_contains(strtolower($l['email']), strtolower($search))
            || str_contains($name, strtolower($search))
            || str_contains($l['ip_address'], $search);
    });
}

// Count per status
$allLogs = $secLog->getSessionTimeline(500);
$counts = [
    'all'     => count($allLogs),
    'success' => count(array_filter($allLogs, fn($l) => $l['status'] === 'success')),
    'logout'  => count(array_filter($allLogs, fn($l) => $l['status'] === 'logout')),
    'failed'  => count(array_filter($allLogs, fn($l) => $l['status'] === 'failed')),
    'locked'  => count(array_filter($allLogs, fn($l) => $l['status'] === 'locked')),
];

$statusConfig = [
    'success' => ['label' => 'Logged In',    'badge' => 'ps-badge-approved',    'icon' => 'bi-box-arrow-in-right', 'color' => '#198754'],
    'logout'  => ['label' => 'Logged Out',   'badge' => 'ps-badge-under_review','icon' => 'bi-box-arrow-left',     'color' => '#0d6efd'],
    'failed'  => ['label' => 'Failed',       'badge' => 'ps-badge-pending',     'icon' => 'bi-x-circle',           'color' => '#f0a500'],
    'locked'  => ['label' => 'Locked',       'badge' => 'ps-badge-rejected',    'icon' => 'bi-lock',               'color' => '#dc3545'],
];
?>

<div class="ps-page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h2>Session Logs</h2>
        <p>Real-time audit of all user login and logout sessions.</p>
    </div>
    <a href="index.php?action=admin_login_logs" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
    </a>
</div>

<!-- Summary stat cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-blue">
            <div class="stat-value"><?= $counts['all'] ?></div>
            <div class="stat-label">Total Events</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card">
            <div class="stat-value" style="color:#198754;"><?= $counts['success'] ?></div>
            <div class="stat-label">Login Sessions</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-blue">
            <div class="stat-value"><?= $counts['logout'] ?></div>
            <div class="stat-label">Logout Sessions</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-red">
            <div class="stat-value"><?= $counts['failed'] + $counts['locked'] ?></div>
            <div class="stat-label">Failed / Locked</div>
        </div>
    </div>
</div>

<div class="ps-card">

    <!-- Filters + Search -->
    <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
        <!-- Status tabs -->
        <div class="d-flex gap-2 flex-wrap">
            <?php foreach (['all','success','logout','failed','locked'] as $s):
                $label = match($s) {
                    'all'     => 'All',
                    'success' => 'Login',
                    'logout'  => 'Logout',
                    'failed'  => 'Failed',
                    'locked'  => 'Locked',
                };
            ?>
            <a href="index.php?action=admin_login_logs&status=<?= $s ?>&search=<?= urlencode($search) ?>"
               class="btn btn-sm <?= $filter === $s ? 'btn-ps-primary' : 'btn-outline-secondary' ?>">
                <?php if ($s !== 'all' && isset($statusConfig[$s])): ?>
                    <i class="bi <?= $statusConfig[$s]['icon'] ?> me-1"></i>
                <?php endif; ?>
                <?= $label ?>
                <span class="ms-1 opacity-75">(<?= $counts[$s] ?? 0 ?>)</span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Search -->
        <form method="GET" action="index.php" class="ms-auto d-flex gap-2">
            <input type="hidden" name="action" value="admin_login_logs">
            <input type="hidden" name="status" value="<?= htmlspecialchars($filter) ?>">
            <div class="input-group input-group-sm" style="width:240px;">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" name="search" class="form-control border-start-0"
                       placeholder="Search email, name, IP..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn btn-sm btn-ps-primary">Search</button>
            <?php if ($search): ?>
                <a href="index.php?action=admin_login_logs&status=<?= $filter ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($logs)): ?>
        <div class="empty-state">
            <i class="bi bi-journal-x" style="font-size:2.5rem;opacity:0.25;"></i>
            <p class="mt-2">No session logs found.</p>
        </div>
    <?php else: ?>

    <!-- Timeline table -->
    <div class="table-responsive">
        <table class="ps-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Event</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>IP Address</th>
                    <th>Browser / Device</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log):
                    $cfg = $statusConfig[$log['status']] ?? ['label' => ucfirst($log['status']), 'badge' => 'ps-badge-pending', 'icon' => 'bi-circle', 'color' => '#6c757d'];
                    $name = $log['firstname'] ? htmlspecialchars($log['firstname'] . ' ' . $log['lastname']) : null;
                    $role = $log['role'] ?? null;

                    // Parse browser from user agent
                    $ua = $log['user_agent'] ?? '';
                    $browser = 'Unknown';
                    if (str_contains($ua, 'Chrome') && !str_contains($ua, 'Edg'))  $browser = 'Chrome';
                    elseif (str_contains($ua, 'Firefox'))  $browser = 'Firefox';
                    elseif (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) $browser = 'Safari';
                    elseif (str_contains($ua, 'Edg'))      $browser = 'Edge';
                    elseif (str_contains($ua, 'Opera'))    $browser = 'Opera';

                    $device = str_contains($ua, 'Mobile') ? 'Mobile' : 'Desktop';
                ?>
                <tr>
                    <td class="small text-muted"><?= $log['id'] ?></td>

                    <!-- Event badge with icon -->
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="
                                width:30px;height:30px;border-radius:50%;
                                background:<?= $cfg['color'] ?>18;
                                display:flex;align-items:center;justify-content:center;
                                flex-shrink:0;">
                                <i class="bi <?= $cfg['icon'] ?>" style="color:<?= $cfg['color'] ?>;font-size:0.85rem;"></i>
                            </div>
                            <span class="ps-badge <?= $cfg['badge'] ?>"><?= $cfg['label'] ?></span>
                        </div>
                    </td>

                    <!-- User -->
                    <td>
                        <?php if ($name): ?>
                            <div class="d-flex align-items-center gap-2">
                                <div style="
                                    width:28px;height:28px;border-radius:50%;
                                    background:var(--ps-green-pale);
                                    display:flex;align-items:center;justify-content:center;
                                    font-size:0.7rem;font-weight:700;color:var(--ps-green);
                                    flex-shrink:0;">
                                    <?= strtoupper(substr($log['firstname'],0,1) . substr($log['lastname'],0,1)) ?>
                                </div>
                                <span class="small fw-semibold"><?= $name ?></span>
                            </div>
                        <?php else: ?>
                            <span class="small text-muted">—</span>
                        <?php endif; ?>
                    </td>

                    <!-- Email -->
                    <td class="small"><?= htmlspecialchars($log['email']) ?></td>

                    <!-- Role -->
                    <td class="small text-muted">
                        <?= $role ? ucwords(str_replace('_', ' ', $role)) : '—' ?>
                    </td>

                    <!-- IP -->
                    <td>
                        <span class="small fw-semibold text-muted">
                            <i class="bi bi-geo-alt me-1 opacity-50"></i><?= htmlspecialchars($log['ip_address']) ?>
                        </span>
                    </td>

                    <!-- Browser -->
                    <td>
                        <div class="d-flex align-items-center gap-1">
                            <i class="bi <?= $device === 'Mobile' ? 'bi-phone' : 'bi-laptop' ?> text-muted small"></i>
                            <span class="small text-muted"><?= $browser ?> · <?= $device ?></span>
                        </div>
                    </td>

                    <!-- Time -->
                    <td>
                        <div class="small fw-semibold"><?= date('M d, Y', strtotime($log['created_at'])) ?></div>
                        <div class="small text-muted"><?= date('g:i:s A', strtotime($log['created_at'])) ?></div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3 small text-muted">
        Showing <?= count($logs) ?> event(s)
        <?= $filter !== 'all' ? '· filtered by <strong>' . htmlspecialchars($filter) . '</strong>' : '' ?>
        <?= $search ? '· search: <strong>' . htmlspecialchars($search) . '</strong>' : '' ?>
    </div>

    <?php endif; ?>
</div>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
