<?php
$pageTitle = 'Review Slips';
include __DIR__ . '/../partials/layout_head.php';
?>

<div class="ps-page-header">
    <h2>Request Slips</h2>
    <p>Review all request slips prepared by the Agricultural Technologist.</p>
</div>

<?php
$rsModel = new RequestSlip($conn);
$allSlips = $rsModel->getAll();
$filterStatus = $_GET['status'] ?? 'all';
if ($filterStatus !== 'all') {
    $allSlips = array_filter($allSlips, fn($s) => $s['status'] === $filterStatus);
}
?>

<div class="ps-card">
    <!-- Filter Tabs -->
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <?php foreach (['all','prepared','reviewed','approved','rejected'] as $s): ?>
            <a href="index.php?action=review_slips&status=<?= $s ?>" 
               class="btn btn-sm <?= $filterStatus === $s ? 'btn-ps-primary' : 'btn-outline-secondary' ?>">
                <?= ucfirst($s) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($allSlips)): ?>
        <div class="empty-state"><p>No request slips found.</p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="ps-table">
                <thead>
                    <tr>
                        <th>Slip #</th>
                        <th>Request #</th>
                        <th>Activity</th>
                        <th>Location</th>
                        <th>Prepared By</th>
                        <th>Qty Approved</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allSlips as $slip): ?>
                    <tr>
                        <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($slip['slip_number']) ?></td>
                        <td class="small"><?= htmlspecialchars($slip['request_number']) ?></td>
                        <td><?= htmlspecialchars($slip['activity_name']) ?></td>
                        <td class="small"><?= htmlspecialchars($slip['target_location'] ?? '—') ?></td>
                        <td class="small"><?= htmlspecialchars($slip['prep_firstname'] . ' ' . $slip['prep_lastname']) ?></td>
                        <td class="fw-bold"><?= number_format($slip['quantity_approved']) ?></td>
                        <td><span class="ps-badge ps-badge-<?= $slip['status'] ?>"><?= ucfirst($slip['status']) ?></span></td>
                        <td class="small text-muted"><?= date('M d, Y', strtotime($slip['created_at'])) ?></td>
                        <td>
                            <a href="index.php?action=approve_slip&id=<?= $slip['id'] ?>" 
                               class="btn btn-sm <?= in_array($slip['status'], ['prepared','reviewed']) ? 'btn-ps-primary' : 'btn-outline-secondary' ?>">
                                <?= in_array($slip['status'], ['prepared','reviewed']) ? 'Review' : 'View' ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
