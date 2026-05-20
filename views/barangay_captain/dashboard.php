<?php
$pageTitle = 'Dashboard';
include __DIR__ . '/../partials/layout_head.php';

$barangayModel = new BarangayApproval($conn);
$pending       = $barangayModel->getPendingForCaptain($_SESSION['user']['id']);
$all           = $barangayModel->getAllForCaptain($_SESSION['user']['id']);
$totalPending  = count($pending);
$totalApproved = count(array_filter($all, fn($r) => $r['status'] === 'approved'));
$totalRejected = count(array_filter($all, fn($r) => $r['status'] === 'rejected'));
?>

<div class="ps-page-header">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['user']['firstname']) ?>!</h2>
    <p>Review and validate proposal letters from community organizers.</p>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-yellow">
            <div class="stat-value"><?= $totalPending ?></div>
            <div class="stat-label">Pending Proposals</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card">
            <div class="stat-value"><?= $totalApproved ?></div>
            <div class="stat-label">Approved</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-red">
            <div class="stat-value"><?= $totalRejected ?></div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-blue">
            <div class="stat-value"><?= count($all) ?></div>
            <div class="stat-label">Total Received</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Quick Actions</h6>
            <div class="d-grid gap-2">
                <a href="index.php?action=captain_proposals" class="btn btn-ps-primary">
                    <i class="bi bi-envelope-check me-2"></i>Review Proposals
                    <?php if ($totalPending > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $totalPending ?></span>
                    <?php endif; ?>
                </a>
                <a href="index.php?action=captain_history" class="btn btn-outline-secondary">Approval History</a>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="ps-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-ps-green">Pending Proposals</h6>
                <a href="index.php?action=captain_proposals" class="small text-ps-green text-decoration-none">View all →</a>
            </div>
            <?php if (empty($pending)): ?>
                <div class="empty-state">
                    <i class="bi bi-check-circle" style="font-size:2.5rem;opacity:0.25;"></i>
                    <p class="mt-2">No pending proposals at the moment.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="ps-table">
                        <thead><tr><th>Request #</th><th>Organizer</th><th>Activity</th><th>Location</th><th>Date</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($pending, 0, 5) as $p): ?>
                            <tr>
                                <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($p['request_number']) ?></td>
                                <td class="small"><?= htmlspecialchars($p['firstname'] . ' ' . $p['lastname']) ?></td>
                                <td><?= htmlspecialchars($p['activity_name']) ?></td>
                                <td class="small"><?= htmlspecialchars($p['target_location']) ?></td>
                                <td class="small"><?= date('M d, Y', strtotime($p['target_date'])) ?></td>
                                <td><a href="index.php?action=captain_review&id=<?= $p['id'] ?>" class="btn btn-sm btn-ps-primary">Review</a></td>
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
