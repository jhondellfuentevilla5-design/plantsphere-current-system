<?php
$pageTitle = 'Dashboard';
include __DIR__ . '/../partials/layout_head.php';

$releaseModel = new SeedRelease($conn);
$srModel      = new ServiceRequest($conn);

$pendingReleases = $releaseModel->getPendingReleases();
$allReleases     = $releaseModel->getAll();
$totalReleased   = $releaseModel->getTotalReleased();
?>

<div class="ps-page-header">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['user']['firstname']) ?>!</h2>
    <p>Manage seed pack releases for finalized tree planting requests.</p>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-yellow">
            <div class="stat-value"><?= count($pendingReleases) ?></div>
            <div class="stat-label">Pending Releases</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card">
            <div class="stat-value"><?= count($allReleases) ?></div>
            <div class="stat-label">Total Released</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-teal">
            <div class="stat-value"><?= number_format($totalReleased) ?></div>
            <div class="stat-label">Seed Packs Issued</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-blue">
            <div class="stat-value"><?= (new PlantingMaterial($conn))->getTotalStock() ?></div>
            <div class="stat-label">Current Stock</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Quick Actions</h6>
            <div class="d-grid gap-2">
                <a href="index.php?action=nursery_release" class="btn btn-ps-primary">
                    <i class="bi bi-box-seam me-2"></i>Release Seed Packs
                    <?php if (count($pendingReleases) > 0): ?>
                        <span class="badge bg-danger ms-1"><?= count($pendingReleases) ?></span>
                    <?php endif; ?>
                </a>
                <a href="index.php?action=nursery_history" class="btn btn-outline-secondary">Release History</a>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="ps-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-ps-green">Pending Seed Pack Releases</h6>
                <a href="index.php?action=nursery_release" class="small text-ps-green text-decoration-none">View all →</a>
            </div>
            <?php if (empty($pendingReleases)): ?>
                <div class="empty-state">
                    <i class="bi bi-check-circle" style="font-size:2.5rem;opacity:0.25;"></i>
                    <p class="mt-2">No pending releases at the moment.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="ps-table">
                        <thead><tr><th>Slip #</th><th>Request #</th><th>Activity</th><th>Organizer</th><th>Qty Approved</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($pendingReleases, 0, 6) as $slip): ?>
                            <tr>
                                <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($slip['slip_number']) ?></td>
                                <td class="small"><?= htmlspecialchars($slip['request_number']) ?></td>
                                <td><?= htmlspecialchars($slip['activity_name']) ?></td>
                                <td class="small"><?= htmlspecialchars($slip['firstname'] . ' ' . $slip['lastname']) ?></td>
                                <td class="fw-bold"><?= number_format($slip['quantity_approved']) ?></td>
                                <td><a href="index.php?action=nursery_release&id=<?= $slip['id'] ?>" class="btn btn-sm btn-ps-primary">Release</a></td>
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
