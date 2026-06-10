<?php
$pageTitle = 'Approve Request Slip';
include __DIR__ . '/../partials/layout_head.php';
?>

<?php
$rsModel = new RequestSlip($conn);
$srModel = new ServiceRequest($conn);
$notifModel = new Notification($conn);
$id = intval($_GET['id'] ?? 0);
$slip = $rsModel->getById($id);

if (!$slip) {
    echo '<div class="alert alert-danger">Request slip not found.</div>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_taken = $_POST['action_taken'];
    $remarks = trim($_POST['remarks'] ?? '');
    $endorsementOffice = trim($_POST['endorsement_office'] ?? '');

    if ($action_taken === 'approve') {
        $rsModel->updateStatus($id, 'approved', $remarks, $_SESSION['user']['id'], $endorsementOffice);
        // Save endorsement ref and filing date
        $refNum = trim($_POST['endorsement_ref_number'] ?? '');
        $filingDate = $_POST['filing_date'] ?? null;
        if ($refNum || $filingDate) {
            $conn->prepare("UPDATE request_slips SET endorsement_ref_number = ?, filing_date = ? WHERE id = ?")->execute([$refNum ?: null, $filingDate ?: null, $id]);
        }
        $srModel->updateStatus($slip['request_id'], 'approved', 'Approved by MAO.');
        // Notify organizer
        $req = $srModel->getById($slip['request_id']);
        $notifModel->create($req['user_id'], 'Request Approved!',
            'Your request ' . $req['request_number'] . ' has been approved by the MAO. It will be routed to ' . $endorsementOffice . '.');
        $success = "Request slip approved and will be routed to: " . $endorsementOffice;
        $slip = $rsModel->getById($id);
    } elseif ($action_taken === 'reject') {
        $rsModel->updateStatus($id, 'rejected', $remarks, $_SESSION['user']['id']);
        $srModel->updateStatus($slip['request_id'], 'rejected', 'Rejected by MAO: ' . $remarks);
        $req = $srModel->getById($slip['request_id']);
        $notifModel->create($req['user_id'], 'Request Rejected',
            'Your request ' . $req['request_number'] . ' was rejected by the MAO. Reason: ' . $remarks);
        $success = "Request slip has been rejected.";
        $slip = $rsModel->getById($id);
    } elseif ($action_taken === 'review') {
        $rsModel->review($id);
        $success = "Slip marked as reviewed.";
        $slip = $rsModel->getById($id);
    }
}
?>

<div class="ps-page-header">
    <h2>Request Slip Review</h2>
    <p>Review, approve, or reject the request slip and route to appropriate office.</p>
</div>

<?php if(isset($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- Slip Header -->
<div class="ps-card mb-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h5 class="fw-bold text-ps-green mb-1"><?= htmlspecialchars($slip['slip_number']) ?></h5>
            <div class="small text-muted">Request: <?= htmlspecialchars($slip['request_number']) ?></div>
        </div>
        <span class="ps-badge ps-badge-<?= $slip['status'] ?> fs-6"><?= ucfirst($slip['status']) ?></span>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="small text-muted">Activity Name</div>
            <div class="fw-semibold"><?= htmlspecialchars($slip['activity_name']) ?></div>
        </div>
        <div class="col-md-6">
            <div class="small text-muted">Requesting Organizer</div>
            <div class="fw-semibold"><?= htmlspecialchars($slip['req_firstname'] . ' ' . $slip['req_lastname']) ?></div>
        </div>
        <div class="col-md-6">
            <div class="small text-muted">Target Location</div>
            <div><?= htmlspecialchars($slip['target_location']) ?></div>
        </div>
        <div class="col-md-6">
            <div class="small text-muted">Target Date</div>
            <div><?= date('F d, Y', strtotime($slip['target_date'])) ?></div>
        </div>
        <div class="col-md-6">
            <div class="small text-muted">Seedling Type Requested</div>
            <div><?= htmlspecialchars($slip['seedling_type']) ?></div>
        </div>
        <div class="col-md-6">
            <div class="small text-muted">Original Quantity Requested</div>
            <div><?= number_format($slip['quantity_requested']) ?></div>
        </div>
    </div>
</div>

<!-- Validation Summary -->
<div class="ps-card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-ps-green mb-0">Site Validation Summary</h6>
        <a href="index.php?action=view_validation&request_id=<?= $slip['request_id'] ?>"
           target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-camera me-1"></i>View Site Photos
        </a>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="small text-muted">Validated Site</div>
            <div><?= htmlspecialchars($slip['site_location']) ?></div>
        </div>
        <div class="col-md-6">
            <div class="small text-muted">Validation Date</div>
            <div><?= date('F d, Y', strtotime($slip['validation_date'])) ?></div>
        </div>
        <div class="col-md-4">
            <div class="small text-muted">Seed Packs Counted</div>
            <div class="fw-bold text-ps-green"><?= number_format($slip['seed_packs_counted']) ?></div>
        </div>
        <div class="col-md-4">
            <div class="small text-muted">Available Seedlings</div>
            <div class="fw-bold text-ps-green"><?= number_format($slip['available_seedlings']) ?></div>
        </div>
        <div class="col-md-4">
            <div class="small text-muted">Quantity in Slip</div>
            <div class="fw-bold text-ps-green"><?= number_format($slip['quantity_approved']) ?></div>
        </div>
    </div>
    <div class="mt-3">
        <div class="small text-muted">Materials Requested</div>
        <div class="p-2 bg-ps-pale rounded mt-1 small"><?= nl2br(htmlspecialchars($slip['materials_requested'])) ?></div>
    </div>
    <div class="mt-2">
        <div class="small text-muted">Prepared By</div>
        <div><?= htmlspecialchars($slip['prep_firstname'] . ' ' . $slip['prep_lastname']) ?></div>
    </div>
</div>

<!-- Action Form -->
<?php if (in_array($slip['status'], ['prepared', 'reviewed'])): ?>
<div class="ps-card">
    <h6 class="fw-bold text-ps-green mb-3">MAO Decision</h6>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Remarks / Notes</label>
            <textarea name="remarks" class="form-control" rows="3" 
                placeholder="Add your remarks, conditions, or reasons for decision..."></textarea>
        </div>
        <div class="mb-4" id="endorseField">
            <label class="form-label">Endorsement Office <span class="text-danger" id="endorseRequired">*</span></label>
            <input type="text" name="endorsement_office" class="form-control" 
                placeholder="e.g. DENR Regional Office, PENRO, CENRO, LGU Agriculture Office">
            <div class="form-text">Required when approving. Specify the office to route this request to.</div>
        </div>
        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label">Endorsement Reference Number</label>
                <input type="text" name="endorsement_ref_number" class="form-control"
                    placeholder="e.g. ENDR-2026-001">
            </div>
            <div class="col-md-6">
                <label class="form-label">Filing Date</label>
                <input type="date" name="filing_date" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if ($slip['status'] === 'prepared'): ?>
            <button type="submit" name="action_taken" value="review" class="btn btn-outline-secondary">
                Mark as Reviewed
            </button>
            <?php endif; ?>
            <button type="submit" name="action_taken" value="approve" class="btn btn-success"
                onclick="return validateApprove()">
                Approve & Route
            </button>
            <button type="submit" name="action_taken" value="reject" class="btn btn-danger"
                onclick="return confirm('Are you sure you want to reject this request slip?')">
                Reject
            </button>
            <a href="index.php?action=review_slips" class="btn btn-outline-secondary">Back</a>
        </div>
    </form>
</div>
<?php else: ?>
<div class="ps-card">
    <div class="alert alert-info py-2 mb-3 small">
        This slip has been <strong><?= $slip['status'] ?></strong>.
        <?php if ($slip['mao_remarks']): ?>
            <br>Remarks: <?= htmlspecialchars($slip['mao_remarks']) ?>
        <?php endif; ?>
        <?php if ($slip['endorsement_office']): ?>
            <br>Routed to: <strong><?= htmlspecialchars($slip['endorsement_office']) ?></strong>
        <?php endif; ?>
        <?php if ($slip['approved_at']): ?>
            <br>Date: <?= date('F d, Y g:i A', strtotime($slip['approved_at'])) ?>
        <?php endif; ?>
    </div>
    <a href="index.php?action=review_slips" class="btn btn-outline-secondary btn-sm">Back to Slips</a>
</div>
<?php endif; ?>

<script>
function validateApprove() {
    const office = document.querySelector('[name="endorsement_office"]').value.trim();
    if (!office) {
        alert('Please specify the endorsement office before approving.');
        return false;
    }
    return confirm('Approve this request slip and route to: ' + office + '?');
}
</script>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
