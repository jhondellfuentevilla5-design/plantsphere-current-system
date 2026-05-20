<?php
$pageTitle = 'Activity Logs';
include __DIR__ . '/../partials/layout_head.php';

$secLog  = new SecurityLog($conn);
$module  = $_GET['module'] ?? 'all';
$logs    = $secLog->getActivityLogs(300, null, $module === 'all' ? null : $module);

$modules = ['all','auth','admin','organizer','affairs_worker','technologist','mao'];
?>

<div class="ps-page-header">
    <h2>Activity Logs</h2>
    <p>Complete audit trail of all user actions across the system.</p>
</div>

<div class="ps-card">
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <?php foreach ($modules as $m): ?>
            <a href="index.php?action=admin_activity_logs&module=<?= $m ?>"
               class="btn btn-sm <?= $module === $m ? 'btn-ps-primary' : 'btn-outline-secondary' ?>">
                <?= ucfirst(str_replace('_',' ',$m)) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($logs)): ?>
        <div class="empty-state"><p>No activity logs found.</p></div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="ps-table">
            <thead>
                <tr><th>#</th><th>User</th><th>Role</th><th>Action</th><th>Module</th><th>Description</th><th>IP</th><th>Time</th></tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="small text-muted"><?= $log['id'] ?></td>
                    <td class="small fw-semibold"><?= htmlspecialchars($log['firstname'] . ' ' . $log['lastname']) ?></td>
                    <td class="small"><?= ucwords(str_replace('_',' ',$log['role'])) ?></td>
                    <td><span class="ps-badge ps-badge-under_review"><?= htmlspecialchars($log['action']) ?></span></td>
                    <td class="small"><?= htmlspecialchars($log['module']) ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($log['description']) ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($log['ip_address']) ?></td>
                    <td class="small text-muted"><?= date('M d, Y g:i A', strtotime($log['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
