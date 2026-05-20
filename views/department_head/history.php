<?php
$pageTitle = 'Finalization History';
include __DIR__ . '/../partials/layout_head.php';

$filter = $_GET['status'] ?? 'all';
$stmt = $conn->prepare("
    SELECT rs.*, sr.request_number, sr.activity_name, sr.target_location,
           req_user.firstname AS req_firstname, req_user.lastname AS req_lastname,
           dh.firstname AS dh_firstname, dh.lastname AS dh_lastname
    FROM request_slips rs
    JOIN service_requests sr ON rs.request_id = sr.id
    JOIN users req_user ON sr.user_id = req_user.id
    LEFT JOIN users dh ON rs.dept_head_id = dh.id
    WHERE rs.finalized_status IN ('finalized','rejected')
    ORDER BY rs.finalized_at DESC
");
$stmt->execute();
$all = $stmt->fetchAll();
$display = $filter === 'all' ? $all : array_filter($all, fn($r) => $r['finalized_status'] === $filter);
?>

<div class="ps-page-header">
    <h2>Finalization History</h2>
    <p>All finalization decisions made by the Department Head.</p>
</div>

<div class="ps-card">
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <?php foreach (['all','finalized','rejected'] as $f): ?>
            <a href="index.php?action=depthead_history&status=<?= $f ?>"
               class="btn btn-sm <?= $filter === $f ? 'btn-ps-primary' : 'btn-outline-secondary' ?>">
                <?= ucfirst($f) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($display)): ?>
        <div class="empty-state"><p>No records found.</p></div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="ps-table">
            <thead><tr><th>Slip #</th><th>Request #</th><th>Activity</th><th>Organizer</th><th>Status</th><th>Remarks</th><th>Finalized</th></tr></thead>
            <tbody>
                <?php foreach ($display as $r): ?>
                <tr>
                    <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($r['slip_number']) ?></td>
                    <td class="small"><?= htmlspecialchars($r['request_number']) ?></td>
                    <td><?= htmlspecialchars($r['activity_name']) ?></td>
                    <td class="small"><?= htmlspecialchars($r['req_firstname'] . ' ' . $r['req_lastname']) ?></td>
                    <td>
                        <span class="ps-badge <?= $r['finalized_status'] === 'finalized' ? 'ps-badge-approved' : 'ps-badge-rejected' ?>">
                            <?= ucfirst($r['finalized_status']) ?>
                        </span>
                    </td>
                    <td class="small text-muted"><?= $r['dept_head_remarks'] ? htmlspecialchars(substr($r['dept_head_remarks'],0,50)) : '—' ?></td>
                    <td class="small text-muted"><?= $r['finalized_at'] ? date('M d, Y', strtotime($r['finalized_at'])) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
