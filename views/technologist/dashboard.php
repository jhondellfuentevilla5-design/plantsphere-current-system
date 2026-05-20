<?php
$pageTitle = 'Dashboard';
include __DIR__ . '/../partials/layout_head.php';

$srModel     = new ServiceRequest($conn);
$rsbsaModel  = new RsbsaRegistry($conn);
$pmModel     = new PlantingMaterial($conn);
$allRequests = $srModel->getAll();
$forReview   = array_filter($allRequests, fn($r) => $r['status'] === 'under_review');
$forValidation = array_filter($allRequests, fn($r) => $r['status'] === 'for_validation');
$rsbsaPending  = array_filter($rsbsaModel->getAll(), fn($r) => $r['status'] === 'pending');
$totalMaterials = $pmModel->getTotalStock();
$actionable = array_merge(array_values($forReview), array_values($forValidation));
?>

<div class="ps-page-header">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['user']['firstname']) ?>!</h2>
    <p>Manage RSBSA verification, site validations, and request slips.</p>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-yellow">
            <div class="stat-value"><?= count($forReview) ?></div>
            <div class="stat-label">Awaiting RSBSA Check</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-blue">
            <div class="stat-value"><?= count($forValidation) ?></div>
            <div class="stat-label">For Site Validation</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card">
            <div class="stat-value"><?= count($rsbsaPending) ?></div>
            <div class="stat-label">RSBSA Pending</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-teal">
            <div class="stat-value"><?= number_format($totalMaterials) ?></div>
            <div class="stat-label">Seedlings in Stock</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Quick Actions</h6>
            <div class="d-grid gap-2">
                <a href="index.php?action=check_rsbsa" class="btn btn-ps-primary">Check RSBSA</a>
                <a href="index.php?action=verify_materials" class="btn btn-outline-secondary">Verify Materials</a>
                <a href="index.php?action=site_validation" class="btn btn-outline-secondary">Site Validation</a>
                <a href="index.php?action=prepare_slip" class="btn btn-outline-secondary">Prepare Slip</a>
                <a href="index.php?action=nursery_guidance_log" class="btn btn-outline-secondary">Guidance Log</a>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="ps-card">
            <h6 class="fw-bold mb-3 text-ps-green">Requests Awaiting Action</h6>
            <?php if (empty($actionable)): ?>
                <div class="empty-state">
                    <i class="bi bi-check-circle" style="font-size:2.5rem;opacity:0.25;"></i>
                    <p class="mt-2">No requests require action at this time.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="ps-table">
                        <thead><tr><th>Request #</th><th>Organizer</th><th>Activity</th><th>Status</th><th>Next Step</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($actionable, 0, 8) as $req): ?>
                            <tr>
                                <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($req['request_number']) ?></td>
                                <td class="small"><?= htmlspecialchars($req['firstname'] . ' ' . $req['lastname']) ?></td>
                                <td><?= htmlspecialchars($req['activity_name']) ?></td>
                                <td><span class="ps-badge ps-badge-<?= $req['status'] ?>"><?= str_replace('_', ' ', $req['status']) ?></span></td>
                                <td>
                                    <?php if ($req['status'] === 'under_review'): ?>
                                        <a href="index.php?action=check_rsbsa&request_id=<?= $req['id'] ?>" class="btn btn-sm btn-ps-primary">Check RSBSA</a>
                                    <?php elseif ($req['status'] === 'for_validation'): ?>
                                        <a href="index.php?action=site_validation&request_id=<?= $req['id'] ?>" class="btn btn-sm btn-ps-primary">Validate Site</a>
                                    <?php endif; ?>
                                </td>
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
