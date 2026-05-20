<?php
$pageTitle = 'Routed Requests';
include __DIR__ . '/../partials/layout_head.php';
?>

<div class="ps-page-header">
    <h2>Routed Requests</h2>
    <p>Approved requests that have been routed to the appropriate offices.</p>
</div>

<?php
$rsModel = new RequestSlip($conn);
$srModel = new ServiceRequest($conn);
$notifModel = new Notification($conn);

// Handle final routing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['route_request_id'])) {
    $reqId = intval($_POST['route_request_id']);
    $srModel->updateStatus($reqId, 'routed', 'Endorsed and routed to appropriate office.');
    $req = $srModel->getById($reqId);
    $notifModel->create($req['user_id'], 'Request Routed',
        'Your request ' . $req['request_number'] . ' has been officially routed to the appropriate office. Expect follow-up from the assigned office.');
    $success = "Request has been officially routed.";
}

$approvedSlips = array_filter($rsModel->getAll(), fn($s) => $s['status'] === 'approved');
?>

<?php if(isset($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="ps-card">
    <h6 class="fw-bold text-ps-green mb-3">Approved Slips — Ready for Routing</h6>
    <?php if (empty($approvedSlips)): ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2zm13 2.383-4.708 2.825L15 11.105V5.383zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741zM1 11.105l4.708-2.897L1 5.383v5.722z"/>
            </svg>
            <p>No approved slips ready for routing yet.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="ps-table">
                <thead>
                    <tr>
                        <th>Slip #</th>
                        <th>Request #</th>
                        <th>Activity</th>
                        <th>Organizer</th>
                        <th>Qty</th>
                        <th>Endorsement Office</th>
                        <th>Approved Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($approvedSlips as $slip): 
                        $req = $srModel->getById($slip['request_id']);
                    ?>
                    <tr>
                        <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($slip['slip_number']) ?></td>
                        <td class="small"><?= htmlspecialchars($slip['request_number']) ?></td>
                        <td><?= htmlspecialchars($slip['activity_name']) ?></td>
                        <td class="small"><?= htmlspecialchars($slip['prep_firstname'] . ' ' . $slip['prep_lastname']) ?></td>
                        <td><?= number_format($slip['quantity_approved']) ?></td>
                        <td class="small fw-semibold"><?= htmlspecialchars($slip['endorsement_office'] ?? '—') ?></td>
                        <td class="small text-muted"><?= $slip['approved_at'] ? date('M d, Y', strtotime($slip['approved_at'])) : '—' ?></td>
                        <td>
                            <?php if ($req && $req['status'] === 'approved'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="route_request_id" value="<?= $slip['request_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-ps-primary"
                                    onclick="return confirm('Confirm routing to <?= addslashes($slip['endorsement_office'] ?? 'office') ?>?')">
                                    Confirm Route
                                </button>
                            </form>
                            <?php else: ?>
                                <span class="ps-badge ps-badge-routed">Routed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Routing History -->
<?php
$routedRequests = $srModel->getByStatus('routed');
if (!empty($routedRequests)):
?>
<div class="ps-card mt-4">
    <h6 class="fw-bold text-ps-green mb-3">Routing History</h6>
    <div class="table-responsive">
        <table class="ps-table">
            <thead>
                <tr>
                    <th>Request #</th>
                    <th>Activity</th>
                    <th>Organizer</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($routedRequests as $req): ?>
                <tr>
                    <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($req['request_number']) ?></td>
                    <td><?= htmlspecialchars($req['activity_name']) ?></td>
                    <td class="small"><?= htmlspecialchars($req['firstname'] . ' ' . $req['lastname']) ?></td>
                    <td class="small"><?= htmlspecialchars($req['target_location']) ?></td>
                    <td><span class="ps-badge ps-badge-routed">Routed</span></td>
                    <td class="small text-muted"><?= date('M d, Y', strtotime($req['updated_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
