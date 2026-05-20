<?php
$pageTitle = 'Approval History';
include __DIR__ . '/../partials/layout_head.php';

$barangayModel = new BarangayApproval($conn);
$all    = $barangayModel->getAllForCaptain($_SESSION['user']['id']);
$filter = $_GET['status'] ?? 'all';
$display = $filter === 'all' ? $all : array_filter($all, fn($r) => $r['status'] === $filter);
?>

<div class="ps-page-header">
    <h2>Approval History</h2>
    <p>All proposal decisions you have made.</p>
</div>

<div class="ps-card">
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <?php foreach (['all','approved','rejected','pending'] as $f): ?>
            <a href="index.php?action=captain_history&status=<?= $f ?>"
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
            <thead><tr><th>Request #</th><th>Organizer</th><th>Activity</th><th>Location</th><th>Date</th><th>Status</th><th>Remarks</th><th>Decided</th></tr></thead>
            <tbody>
                <?php foreach ($display as $r): ?>
                <tr>
                    <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($r['request_number']) ?></td>
                    <td class="small"><?= htmlspecialchars($r['firstname'] . ' ' . $r['lastname']) ?></td>
                    <td><?= htmlspecialchars($r['activity_name']) ?></td>
                    <td class="small"><?= htmlspecialchars($r['target_location']) ?></td>
                    <td class="small"><?= date('M d, Y', strtotime($r['target_date'])) ?></td>
                    <td><span class="ps-badge ps-badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td class="small text-muted"><?= $r['remarks'] ? htmlspecialchars(substr($r['remarks'],0,40)) : '—' ?></td>
                    <td class="small text-muted"><?= $r['approved_at'] ? date('M d, Y', strtotime($r['approved_at'])) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
