<?php
$pageTitle = 'Dashboard';
include __DIR__ . '/../partials/layout_head.php';

$srModel    = new ServiceRequest($conn);
$rsbsaModel = new RsbsaRegistry($conn);
$requests   = $srModel->getByUser($_SESSION['user']['id']);
$rsbsa      = $rsbsaModel->getByUser($_SESSION['user']['id']);

$total    = count($requests);
$pending  = count(array_filter($requests, fn($r) => $r['status'] === 'pending'));
$approved = count(array_filter($requests, fn($r) => $r['status'] === 'approved'));
$rejected = count(array_filter($requests, fn($r) => $r['status'] === 'rejected'));
?>

<div class="ps-page-header">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['user']['firstname']) ?>!</h2>
    <p>Submit and track your tree planting proposal letters.</p>
</div>

<?php if(isset($success)): ?>
    <div class="alert alert-success py-2 small mb-3"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card">
            <div class="stat-value"><?= $total ?></div>
            <div class="stat-label">Total Requests</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-yellow">
            <div class="stat-value"><?= $pending ?></div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-blue">
            <div class="stat-value"><?= $approved ?></div>
            <div class="stat-label">Approved</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-red">
            <div class="stat-value"><?= $rejected ?></div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Quick Actions -->
    <div class="col-md-4">
        <div class="ps-card h-100">
            <h6 class="fw-bold mb-3 text-ps-green">Quick Actions</h6>
            <div class="d-grid gap-2">
                <a href="index.php?action=submit_request" class="btn btn-ps-primary">
                    <i class="bi bi-file-earmark-plus me-2"></i>Submit Proposal Letter
                </a>
                <a href="index.php?action=my_requests" class="btn btn-outline-secondary">
                    <i class="bi bi-clipboard-list me-2"></i>My Proposals
                </a>
                <a href="index.php?action=rsbsa_form" class="btn btn-outline-secondary">
                    <?= $rsbsa ? 'View RSBSA Registration' : 'Register for RSBSA' ?>
                </a>
                <a href="index.php?action=nursery_survival" class="btn btn-outline-secondary">
                    <i class="bi bi-tree me-2"></i>Survival Monitoring
                </a>
            </div>
            <hr class="section-divider">
            <h6 class="fw-bold mb-2 text-ps-green">RSBSA Status</h6>
            <?php if ($rsbsa): ?>
                <div class="d-flex align-items-center gap-2">
                    <span class="ps-badge ps-badge-<?= $rsbsa['status'] ?>"><?= ucfirst($rsbsa['status']) ?></span>
                    <span class="small text-muted"><?= htmlspecialchars($rsbsa['rsbsa_number']) ?></span>
                </div>
            <?php else: ?>
                <p class="small text-muted mb-0">Not yet registered. <a href="index.php?action=rsbsa_form">Register now</a></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Requests -->
    <div class="col-md-8">
        <div class="ps-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-ps-green">Recent Requests</h6>
                <a href="index.php?action=my_requests" class="small text-ps-green text-decoration-none">View all →</a>
            </div>
            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <i class="bi bi-clipboard-x" style="font-size:2.5rem;opacity:0.25;"></i>
                    <p class="mt-2">No requests yet. Submit your first tree planting request!</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="ps-table">
                        <thead><tr><th>Request #</th><th>Activity</th><th>Target Date</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($requests, 0, 5) as $req): ?>
                            <tr>
                                <td class="fw-semibold small text-ps-green"><?= htmlspecialchars($req['request_number']) ?></td>
                                <td><?= htmlspecialchars($req['activity_name']) ?></td>
                                <td class="small"><?= date('M d, Y', strtotime($req['target_date'])) ?></td>
                                <td><span class="ps-badge ps-badge-<?= $req['status'] ?>"><?= str_replace('_', ' ', $req['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Process Flow -->
<div class="ps-card mt-4">
    <h6 class="fw-bold mb-3 text-ps-green">How Your Request is Processed</h6>
    <div class="ps-steps">
        <?php
        $steps = ['Submit Proposal','Barangay Validation','Affairs Worker Review','RSBSA Check','Site Validation','Request Slip','MAO Approval','Seed Release'];
        foreach ($steps as $i => $s):
        ?>
        <div class="ps-step <?= $i === 0 ? 'done' : '' ?>">
            <div class="ps-step-circle"><?= $i+1 ?></div>
            <div class="ps-step-label"><?= $s ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
