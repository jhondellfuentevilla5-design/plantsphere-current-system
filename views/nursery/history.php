<?php
$pageTitle = 'Release History';
include __DIR__ . '/../partials/layout_head.php';

$releaseModel = new SeedRelease($conn);
$releases = $releaseModel->getAll();
$total = $releaseModel->getTotalReleased();
?>

<div class="ps-page-header d-flex justify-content-between align-items-start">
    <div>
        <h2>Release History</h2>
        <p>All seed pack releases recorded by the Nursery.</p>
    </div>
    <div class="ps-stat-card" style="min-width:160px;">
        <div class="stat-value"><?= number_format($total) ?></div>
        <div class="stat-label">Total Packs Released</div>
    </div>
</div>

<div class="ps-card">
    <?php if (empty($releases)): ?>
        <div class="empty-state">
            <i class="bi bi-box" style="font-size:2.5rem;opacity:0.25;"></i>
            <p class="mt-2">No releases recorded yet.</p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="ps-table">
            <thead>
                <tr><th>Request #</th><th>Activity</th><th>Qty Released</th><th>Recipient</th><th>Release Date</th><th>Released By</th><th>Remarks</th></tr>
            </thead>
            <tbody>
                <?php foreach ($releases as $r): ?>
                <tr>
                    <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($r['request_number']) ?></td>
                    <td><?= htmlspecialchars($r['activity_name']) ?></td>
                    <td class="fw-bold"><?= number_format($r['quantity_released']) ?></td>
                    <td class="small"><?= htmlspecialchars($r['recipient_name']) ?></td>
                    <td class="small"><?= date('M d, Y', strtotime($r['release_date'])) ?></td>
                    <td class="small"><?= htmlspecialchars($r['firstname'] . ' ' . $r['lastname']) ?></td>
                    <td class="small text-muted"><?= $r['remarks'] ? htmlspecialchars(substr($r['remarks'],0,40)) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
