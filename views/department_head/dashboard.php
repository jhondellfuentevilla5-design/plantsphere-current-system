<?php
$pageTitle = 'Dashboard';
include __DIR__ . '/../partials/layout_head.php';

$rsModel = new RequestSlip($conn);
$srModel = new ServiceRequest($conn);

// Slips awaiting dept head finalization
$stmt = $conn->prepare("
    SELECT rs.*, sr.request_number, sr.activity_name, sr.target_location, sr.target_date,
           sr.seedling_type, sr.quantity_requested,
           preparer.firstname AS prep_firstname, preparer.lastname AS prep_lastname,
           req_user.firstname AS req_firstname, req_user.lastname AS req_lastname
    FROM request_slips rs
    JOIN service_requests sr ON rs.request_id = sr.id
    JOIN users preparer ON rs.prepared_by = preparer.id
    JOIN users req_user ON sr.user_id = req_user.id
    WHERE rs.status = 'approved' AND (rs.finalized_status = 'pending' OR rs.finalized_status IS NULL)
    ORDER BY rs.approved_at DESC
");
$stmt->execute();
$pendingFinalization = $stmt->fetchAll();

$stmt2 = $conn->prepare("SELECT COUNT(*) as c FROM request_slips WHERE finalized_status = 'finalized'");
$stmt2->execute();
$totalFinalized = (int)$stmt2->fetch()['c'];

$stmt3 = $conn->prepare("SELECT COUNT(*) as c FROM request_slips WHERE finalized_status = 'rejected'");
$stmt3->execute();
$totalRejected = (int)$stmt3->fetch()['c'];
?>

<div class="ps-page-header">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['user']['firstname']) ?>!</h2>
    <p>Finalize approved service requests and issue endorsement decisions.</p>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card accent-yellow">
            <div class="stat-value"><?= count($pendingFinalization) ?></div>
            <div class="stat-label">Awaiting Finalization</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="ps-stat-card">
            <div class="stat-value"><?= $totalFinalized ?></div>
            <div class="stat-label">Finalized</div>
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
            <div class="stat-value"><?= $totalFinalized + $totalRejected ?></div>
            <div class="stat-label">Total Processed</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Quick Actions</h6>
            <div class="d-grid gap-2">
                <a href="index.php?action=depthead_finalize" class="btn btn-ps-primary">
                    <i class="bi bi-check2-square me-2"></i>Finalize Requests
                    <?php if (count($pendingFinalization) > 0): ?>
                        <span class="badge bg-danger ms-1"><?= count($pendingFinalization) ?></span>
                    <?php endif; ?>
                </a>
                <a href="index.php?action=depthead_history" class="btn btn-outline-secondary">Finalization History</a>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="ps-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-ps-green">Requests Awaiting Finalization</h6>
                <a href="index.php?action=depthead_finalize" class="small text-ps-green text-decoration-none">View all →</a>
            </div>
            <?php if (empty($pendingFinalization)): ?>
                <div class="empty-state">
                    <i class="bi bi-check-circle" style="font-size:2.5rem;opacity:0.25;"></i>
                    <p class="mt-2">No requests pending finalization.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="ps-table">
                        <thead><tr><th>Slip #</th><th>Request #</th><th>Activity</th><th>Organizer</th><th>Qty</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($pendingFinalization, 0, 6) as $slip): ?>
                            <tr>
                                <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($slip['slip_number']) ?></td>
                                <td class="small"><?= htmlspecialchars($slip['request_number']) ?></td>
                                <td><?= htmlspecialchars($slip['activity_name']) ?></td>
                                <td class="small"><?= htmlspecialchars($slip['req_firstname'] . ' ' . $slip['req_lastname']) ?></td>
                                <td><?= number_format($slip['quantity_approved']) ?></td>
                                <td><a href="index.php?action=depthead_finalize&id=<?= $slip['id'] ?>" class="btn btn-sm btn-ps-primary">Finalize</a></td>
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
