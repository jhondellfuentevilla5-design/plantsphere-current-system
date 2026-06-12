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
        $filingDate = $_POST['filing_date'] ?? date('Y-m-d');
        $plantingSite = trim($_POST['planting_site'] ?? $slip['target_location']);
        $endorsedQty  = intval($_POST['endorsed_qty'] ?? $slip['quantity_approved']);

        $conn->prepare("UPDATE request_slips SET endorsement_ref_number = ?, filing_date = ?, endorsed_planting_site = ?, endorsed_quantity = ? WHERE id = ?")
             ->execute([$refNum ?: null, $filingDate, $plantingSite, $endorsedQty, $id]);

        $srModel->updateStatus($slip['request_id'], 'approved', 'Approved by MAO. Endorsement letter generated.');

        $req = $srModel->getById($slip['request_id']);

        // Notify organizer
        $notifModel->create($req['user_id'], 'Request Approved — Endorsement Letter Ready',
            'Your request ' . $req['request_number'] . ' has been approved by the MAO. Endorsement letter routed to ' . $endorsementOffice . '.');

        // Notify Department Head
        $deptHeads = (new User($conn))->getAllByRole('department_head');
        foreach ($deptHeads as $dh) {
            $notifModel->create($dh['id'], 'Approved Request for Finalization',
                'Request ' . $req['request_number'] . ' approved by MAO. Endorsement Ref: ' . ($refNum ?: 'N/A') . '. Please review and finalize.');
        }

        $success = "approved";
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
    <?php if ($success === 'approved'): ?>
    <div class="alert alert-success d-flex align-items-start gap-3 mb-4">
        <i class="bi bi-check-circle-fill mt-1" style="font-size:1.3rem;"></i>
        <div>
            <div class="fw-bold">Request Slip Approved — Endorsement Letter Generated</div>
            <div class="small mt-1">The endorsement letter has been prepared and the Department Head has been notified for final approval.</div>
            <div class="mt-2">
                <a href="index.php?action=view_endorsement&slip_id=<?= $id ?>" target="_blank"
                   class="btn btn-sm btn-success">
                    <i class="bi bi-file-earmark-text me-1"></i>View Endorsement Letter
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
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
    <h6 class="fw-bold text-ps-green mb-3">
        <i class="bi bi-file-earmark-text me-2"></i>MAO Decision & Endorsement Letter
    </h6>
    <p class="small text-muted mb-3">
        Fill in the endorsement details below. Upon approval, an endorsement letter will be generated and forwarded to the Department Head.
    </p>
    <form method="POST">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Endorsement Reference Number</label>
                <?php
                // Auto-generate ref number: ENDR-YYYY-XXXX
                $year = date('Y');
                $countStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM request_slips WHERE endorsement_ref_number IS NOT NULL AND YEAR(approved_at) = ?");
                $countStmt->execute([$year]);
                $seq = str_pad(($countStmt->fetch()['cnt'] + 1), 4, '0', STR_PAD_LEFT);
                $autoRef = 'ENDR-' . $year . '-' . $seq;
                ?>
                <input type="text" name="endorsement_ref_number" class="form-control"
                    value="<?= htmlspecialchars($autoRef) ?>" readonly
                    style="background:#f0faf0; font-weight:600; color:#2d5a27;">
                <div class="form-text"><i class="bi bi-info-circle me-1"></i>Auto-generated reference number.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Filing Date <span class="text-danger">*</span></label>
                <input type="date" name="filing_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-8">
                <label class="form-label">Planting Site <span class="text-danger">*</span></label>
                <input type="text" name="planting_site" class="form-control"
                    value="<?= htmlspecialchars($slip['target_location']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Endorsed Seedling Quantity <span class="text-danger">*</span></label>
                <input type="number" name="endorsed_qty" class="form-control"
                    value="<?= $slip['quantity_approved'] ?>" min="1" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Endorsement Office <span class="text-danger">*</span></label>
            <input type="text" name="endorsement_office" class="form-control"
                placeholder="e.g. DENR Regional Office, PENRO, CENRO, LGU Agriculture Office" required>
            <div class="form-text">Specify the office this endorsement will be routed to.</div>
        </div>
        <div class="mb-4">
            <label class="form-label">Remarks / Notes</label>
            <textarea name="remarks" class="form-control" rows="2"
                placeholder="Add conditions, remarks, or additional notes..."></textarea>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if ($slip['status'] === 'prepared'): ?>
            <button type="submit" name="action_taken" value="review" class="btn btn-outline-secondary">
                <i class="bi bi-eye me-1"></i>Mark as Reviewed
            </button>
            <?php endif; ?>
            <button type="submit" name="action_taken" value="approve" class="btn btn-success"
                onclick="return validateApprove()">
                <i class="bi bi-check-circle me-1"></i>Approve & Generate Endorsement Letter
            </button>
            <button type="submit" name="action_taken" value="reject" class="btn btn-danger"
                onclick="return confirm('Are you sure you want to reject this request slip?')">
                <i class="bi bi-x-circle me-1"></i>Reject
            </button>
            <a href="index.php?action=review_slips" class="btn btn-outline-secondary">Back</a>
        </div>
    </form>
</div>
<?php else: ?>
<div class="ps-card">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <div class="small text-muted mb-1">Status</div>
            <span class="ps-badge ps-badge-<?= $slip['status'] ?>"><?= ucfirst($slip['status']) ?></span>
        </div>
        <?php if ($slip['status'] === 'approved'): ?>
        <a href="index.php?action=view_endorsement&slip_id=<?= $id ?>" target="_blank"
           class="btn btn-ps-primary btn-sm">
            <i class="bi bi-file-earmark-text me-1"></i>View Endorsement Letter
        </a>
        <?php endif; ?>
    </div>
    <div class="row g-3 small">
        <?php if ($slip['mao_remarks']): ?>
        <div class="col-12"><span class="text-muted">Remarks:</span> <?= htmlspecialchars($slip['mao_remarks']) ?></div>
        <?php endif; ?>
        <?php if ($slip['endorsement_office']): ?>
        <div class="col-md-6"><span class="text-muted">Routed to:</span> <strong><?= htmlspecialchars($slip['endorsement_office']) ?></strong></div>
        <?php endif; ?>
        <?php if ($slip['endorsement_ref_number']): ?>
        <div class="col-md-6"><span class="text-muted">Ref #:</span> <?= htmlspecialchars($slip['endorsement_ref_number']) ?></div>
        <?php endif; ?>
        <?php if ($slip['approved_at']): ?>
        <div class="col-12"><span class="text-muted">Date:</span> <?= date('F d, Y g:i A', strtotime($slip['approved_at'])) ?></div>
        <?php endif; ?>
    </div>
    <div class="mt-3">
        <a href="index.php?action=review_slips" class="btn btn-outline-secondary btn-sm">Back to Slips</a>
    </div>
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
