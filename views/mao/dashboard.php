<?php
$pageTitle = 'Dashboard';
include __DIR__ . '/../partials/layout_head.php';

$rsModel      = new RequestSlip($conn);
$srModel      = new ServiceRequest($conn);
$allSlips     = $rsModel->getAll();
$pendingSlips = $rsModel->getPending();
$allRequests  = $srModel->getAll();
$approved     = count(array_filter($allRequests, fn($r) => $r['status'] === 'approved'));
$routed       = count(array_filter($allRequests, fn($r) => $r['status'] === 'routed'));
?>

<div class="ps-page-header">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['user']['firstname']) ?>!</h2>
    <p>Review and approve request slips, then route to the appropriate office.</p>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-yellow">
            <div class="stat-value"><?= count($pendingSlips) ?></div>
            <div class="stat-label">Slips for Review</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card">
            <div class="stat-value"><?= count($allSlips) ?></div>
            <div class="stat-label">Total Slips</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-blue">
            <div class="stat-value"><?= $approved ?></div>
            <div class="stat-label">Approved Requests</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-teal">
            <div class="stat-value"><?= $routed ?></div>
            <div class="stat-label">Routed to Office</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Quick Actions</h6>
            <div class="d-grid gap-2">
                <a href="index.php?action=review_slips" class="btn btn-ps-primary">
                    Review Request Slips
                    <?php if (count($pendingSlips) > 0): ?>
                        <span class="badge bg-danger ms-1"><?= count($pendingSlips) ?></span>
                    <?php endif; ?>
                </a>
                <a href="index.php?action=route_request" class="btn btn-outline-secondary">Routed Requests</a>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="ps-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-ps-green">Request Slips Awaiting Review</h6>
                <a href="index.php?action=review_slips" class="small text-ps-green text-decoration-none">View all →</a>
            </div>
            <?php if (empty($pendingSlips)): ?>
                <div class="empty-state">
                    <i class="bi bi-check-circle" style="font-size:2.5rem;opacity:0.25;"></i>
                    <p class="mt-2">No request slips pending review.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="ps-table">
                        <thead><tr><th>Slip #</th><th>Request #</th><th>Activity</th><th>Prepared By</th><th>Qty</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($pendingSlips, 0, 6) as $slip): ?>
                            <tr>
                                <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($slip['slip_number']) ?></td>
                                <td class="small"><?= htmlspecialchars($slip['request_number']) ?></td>
                                <td><?= htmlspecialchars($slip['activity_name']) ?></td>
                                <td class="small"><?= htmlspecialchars($slip['prep_firstname'] . ' ' . $slip['prep_lastname']) ?></td>
                                <td><?= number_format($slip['quantity_approved']) ?></td>
                                <td><span class="ps-badge ps-badge-<?= $slip['status'] ?>"><?= ucfirst($slip['status']) ?></span></td>
                                <td><a href="index.php?action=approve_slip&id=<?= $slip['id'] ?>" class="btn btn-sm btn-ps-primary">Review</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
