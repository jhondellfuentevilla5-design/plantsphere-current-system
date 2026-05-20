<?php
$pageTitle = 'Dashboard';
include __DIR__ . '/../partials/layout_head.php';

$srModel     = new ServiceRequest($conn);
$pmModel     = new PlantingMaterial($conn);
$allRequests = $srModel->getAll();
$pending     = array_filter($allRequests, fn($r) => $r['status'] === 'pending');
$underReview = array_filter($allRequests, fn($r) => $r['status'] === 'under_review');
$totalMaterials = $pmModel->getTotalStock();
?>

<div class="ps-page-header">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['user']['firstname']) ?>!</h2>
    <p>Review incoming requests, explain seedling materials, and refer to appropriate officers.</p>
</div>

<?php if(isset($success)): ?>
    <div class="alert alert-success py-2 small mb-3"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-yellow">
            <div class="stat-value"><?= count($pending) ?></div>
            <div class="stat-label">Pending Requests</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-blue">
            <div class="stat-value"><?= count($underReview) ?></div>
            <div class="stat-label">Under Review</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card">
            <div class="stat-value"><?= count($allRequests) ?></div>
            <div class="stat-label">Total Requests</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-teal">
            <div class="stat-value"><?= number_format($totalMaterials) ?></div>
            <div class="stat-label">Seedlings Available</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Quick Actions</h6>
            <div class="d-grid gap-2">
                <a href="index.php?action=view_requests" class="btn btn-ps-primary">View All Requests</a>
                <a href="index.php?action=seedling_info" class="btn btn-outline-secondary">Seedling Materials</a>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="ps-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-ps-green">Pending Requests</h6>
                <a href="index.php?action=view_requests" class="small text-ps-green text-decoration-none">View all →</a>
            </div>
            <?php if (empty($pending)): ?>
                <div class="empty-state">
                    <i class="bi bi-check-circle" style="font-size:2.5rem;opacity:0.25;"></i>
                    <p class="mt-2">No pending requests at the moment.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="ps-table">
                        <thead><tr><th>Request #</th><th>Organizer</th><th>Activity</th><th>Seedling</th><th>Date</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($pending, 0, 5) as $req): ?>
                            <tr>
                                <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($req['request_number']) ?></td>
                                <td class="small"><?= htmlspecialchars($req['firstname'] . ' ' . $req['lastname']) ?></td>
                                <td><?= htmlspecialchars($req['activity_name']) ?></td>
                                <td class="small"><?= htmlspecialchars($req['seedling_type']) ?></td>
                                <td class="small"><?= date('M d, Y', strtotime($req['target_date'])) ?></td>
                                <td><a href="index.php?action=refer_request&id=<?= $req['id'] ?>" class="btn btn-sm btn-ps-primary">Review</a></td>
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
