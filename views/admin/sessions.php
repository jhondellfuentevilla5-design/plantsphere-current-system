<?php
$pageTitle = 'User Sessions';
include __DIR__ . '/../partials/layout_head.php';

$secLog  = new SecurityLog($conn);
$filter  = $_GET['filter'] ?? 'all';
$search  = trim($_GET['search'] ?? '');

$sessions = $secLog->getUserSessions(500);
$active   = $secLog->countActiveSessions();
$total    = count($sessions);

// Filter
if ($filter === 'active') {
    $sessions = array_filter($sessions, fn($s) => $s['is_active'] == 1);
} elseif ($filter === 'ended') {
    $sessions = array_filter($sessions, fn($s) => $s['is_active'] == 0);
}

// Search
if ($search !== '') {
    $sessions = array_filter($sessions, function($s) use ($search) {
        $name = strtolower(($s['firstname'] ?? '') . ' ' . ($s['lastname'] ?? ''));
        return str_contains(strtolower($s['email']), strtolower($search))
            || str_contains($name, strtolower($search))
            || str_contains($s['ip_address'], $search);
    });
}

// Helper: format seconds to human duration
function formatDuration($secs) {
    $secs = (int)$secs;
    if ($secs < 60)   return $secs . 's';
    if ($secs < 3600) return floor($secs/60) . 'm ' . ($secs%60) . 's';
    $h = floor($secs/3600);
    $m = floor(($secs%3600)/60);
    return $h . 'h ' . $m . 'm';
}
?>

<div class="ps-page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h2>User Sessions</h2>
        <p>Track every login session — start time, duration, IP address, and logout method.</p>
    </div>
    <a href="index.php?action=admin_sessions" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
    </a>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-blue">
            <div class="stat-value"><?= $total ?></div>
            <div class="stat-label">Total Sessions</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card">
            <div class="stat-value" style="color:#198754;"><?= $active ?></div>
            <div class="stat-label">Currently Active</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-yellow">
            <div class="stat-value"><?= $total - $active ?></div>
            <div class="stat-label">Ended Sessions</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <?php
        // Average duration of ended sessions
        $ended = array_filter($secLog->getUserSessions(500), fn($s) => $s['is_active'] == 0 && $s['duration_seconds'] > 0);
        $avgSec = count($ended) > 0 ? array_sum(array_column($ended, 'duration_seconds')) / count($ended) : 0;
        ?>
        <div class="ps-stat-card accent-teal">
            <div class="stat-value" style="font-size:1.4rem;"><?= formatDuration($avgSec) ?></div>
            <div class="stat-label">Avg Session Duration</div>
        </div>
    </div>
</div>

<div class="ps-card">

    <!-- Filters + Search -->
    <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
        <div class="d-flex gap-2">
            <?php foreach (['all' => 'All', 'active' => 'Active', 'ended' => 'Ended'] as $val => $label): ?>
            <a href="index.php?action=admin_sessions&filter=<?= $val ?>&search=<?= urlencode($search) ?>"
               class="btn btn-sm <?= $filter === $val ? 'btn-ps-primary' : 'btn-outline-secondary' ?>">
                <?php if ($val === 'active'): ?>
                    <span class="me-1" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#198754;"></span>
                <?php elseif ($val === 'ended'): ?>
                    <span class="me-1" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#6c757d;"></span>
                <?php endif; ?>
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>

        <form method="GET" action="index.php" class="ms-auto d-flex gap-2">
            <input type="hidden" name="action" value="admin_sessions">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <div class="input-group input-group-sm" style="width:240px;">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" name="search" class="form-control border-start-0"
                       placeholder="Search name, email, IP..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn btn-sm btn-ps-primary">Search</button>
            <?php if ($search): ?>
                <a href="index.php?action=admin_sessions&filter=<?= $filter ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($sessions)): ?>
        <div class="empty-state">
            <i class="bi bi-clock-history" style="font-size:2.5rem;opacity:0.2;"></i>
            <p class="mt-2">No sessions found.</p>
        </div>
    <?php else: ?>

    <div class="table-responsive">
        <table class="ps-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Login Time</th>
                    <th>Last Activity</th>
                    <th>Duration</th>
                    <th>Expires / Ended</th>
                    <th>IP Address</th>
                    <th>Browser</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $s):
                    $isActive = $s['is_active'] == 1 && strtotime($s['expires_at']) > time();
                    $duration = (int)$s['duration_seconds'];

                    // Parse browser
                    $ua = $s['user_agent'] ?? '';
                    $browser = 'Unknown';
                    if (str_contains($ua, 'Chrome') && !str_contains($ua, 'Edg'))  $browser = 'Chrome';
                    elseif (str_contains($ua, 'Firefox'))  $browser = 'Firefox';
                    elseif (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) $browser = 'Safari';
                    elseif (str_contains($ua, 'Edg'))      $browser = 'Edge';
                    elseif (str_contains($ua, 'Opera'))    $browser = 'Opera';
                    $device = str_contains($ua, 'Mobile') ? 'Mobile' : 'Desktop';
                ?>
                <tr>
                    <td class="small text-muted"><?= $s['id'] ?></td>

                    <!-- User -->
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="sess-avatar">
                                <?= strtoupper(substr($s['firstname'],0,1) . substr($s['lastname'],0,1)) ?>
                            </div>
                            <div>
                                <div class="small fw-semibold"><?= htmlspecialchars($s['firstname'] . ' ' . $s['lastname']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($s['email']) ?></div>
                            </div>
                        </div>
                    </td>

                    <!-- Role -->
                    <td class="small text-muted"><?= ucwords(str_replace('_',' ',$s['role'])) ?></td>

                    <!-- Status -->
                    <td>
                        <?php if ($isActive): ?>
                            <span class="d-flex align-items-center gap-1">
                                <span class="sess-dot active"></span>
                                <span class="ps-badge ps-badge-approved">Active</span>
                            </span>
                        <?php else: ?>
                            <span class="d-flex align-items-center gap-1">
                                <span class="sess-dot ended"></span>
                                <span class="ps-badge ps-badge-pending">Ended</span>
                            </span>
                        <?php endif; ?>
                    </td>

                    <!-- Login time -->
                    <td>
                        <div class="small fw-semibold"><?= date('M d, Y', strtotime($s['created_at'])) ?></div>
                        <div class="small text-muted"><?= date('g:i:s A', strtotime($s['created_at'])) ?></div>
                    </td>

                    <!-- Last activity -->
                    <td>
                        <div class="small"><?= date('M d, Y', strtotime($s['last_activity'])) ?></div>
                        <div class="small text-muted"><?= date('g:i:s A', strtotime($s['last_activity'])) ?></div>
                    </td>

                    <!-- Duration -->
                    <td>
                        <span class="sess-duration <?= $isActive ? 'active' : '' ?>">
                            <i class="bi bi-stopwatch me-1"></i>
                            <?= $duration > 0 ? formatDuration($duration) : '—' ?>
                            <?php if ($isActive): ?>
                                <span class="sess-live-badge">LIVE</span>
                            <?php endif; ?>
                        </span>
                    </td>

                    <!-- Expires / Ended -->
                    <td>
                        <div class="small"><?= date('M d, Y', strtotime($s['expires_at'])) ?></div>
                        <div class="small text-muted"><?= date('g:i:s A', strtotime($s['expires_at'])) ?></div>
                    </td>

                    <!-- IP -->
                    <td class="small text-muted">
                        <i class="bi bi-geo-alt me-1 opacity-50"></i><?= htmlspecialchars($s['ip_address']) ?>
                    </td>

                    <!-- Browser -->
                    <td>
                        <div class="d-flex align-items-center gap-1">
                            <i class="bi <?= $device === 'Mobile' ? 'bi-phone' : 'bi-laptop' ?> text-muted small"></i>
                            <span class="small text-muted"><?= $browser ?></span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3 small text-muted">
        Showing <?= count($sessions) ?> session(s)
        <?= $filter !== 'all' ? '· <strong>' . htmlspecialchars($filter) . '</strong>' : '' ?>
        <?= $search ? '· search: <strong>' . htmlspecialchars($search) . '</strong>' : '' ?>
    </div>

    <?php endif; ?>
</div>

<style>
.sess-avatar {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: var(--ps-green-pale);
    color: var(--ps-green);
    font-size: 0.72rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.sess-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
}
.sess-dot.active { background: #198754; box-shadow: 0 0 0 3px rgba(25,135,84,0.2); }
.sess-dot.ended  { background: #6c757d; }

.sess-duration {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--ps-muted);
    display: flex;
    align-items: center;
    gap: 4px;
}
.sess-duration.active { color: #198754; }

.sess-live-badge {
    font-size: 0.6rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    background: #198754;
    color: #fff;
    padding: 1px 5px;
    border-radius: 4px;
    animation: pulse-badge 1.5s ease-in-out infinite;
}
@keyframes pulse-badge {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.5; }
}
</style>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
