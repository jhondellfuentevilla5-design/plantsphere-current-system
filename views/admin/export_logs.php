<?php
$pageTitle = 'Export / DLP Logs';
include __DIR__ . '/../partials/layout_head.php';

$secLog = new SecurityLog($conn);
$logs   = $secLog->getExportLogs(200);
?>

<div class="ps-page-header">
    <h2>Export & DLP Logs</h2>
    <p>Data Loss Prevention — log of all export attempts and blocked actions.</p>
</div>

<div class="ps-card">
    <?php if (empty($logs)): ?>
        <div class="empty-state"><p>No export attempts logged yet.</p></div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="ps-table">
            <thead>
                <tr><th>#</th><th>User</th><th>Role</th><th>Action</th><th>Blocked</th><th>IP</th><th>Time</th></tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="small text-muted"><?= $log['id'] ?></td>
                    <td class="small fw-semibold"><?= htmlspecialchars($log['firstname'] . ' ' . $log['lastname']) ?></td>
                    <td class="small"><?= ucwords(str_replace('_',' ',$log['role'])) ?></td>
                    <td class="small"><?= htmlspecialchars($log['action']) ?></td>
                    <td>
                        <span class="ps-badge <?= $log['blocked'] ? 'ps-badge-rejected' : 'ps-badge-approved' ?>">
                            <?= $log['blocked'] ? 'Blocked' : 'Allowed' ?>
                        </span>
                    </td>
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
