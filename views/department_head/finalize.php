<?php
$pageTitle = 'Finalize Service Requests';
include __DIR__ . '/../partials/layout_head.php';

$notifModel = new Notification($conn);
$srModel    = new ServiceRequest($conn);

// Handle finalization
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['slip_id'])) {
    $slipId      = intval($_POST['slip_id']);
    $action_taken = $_POST['action_taken'];
    $remarks     = trim($_POST['remarks'] ?? '');

    $stmt = $conn->prepare("SELECT rs.*, sr.user_id, sr.request_number FROM request_slips rs JOIN service_requests sr ON rs.request_id = sr.id WHERE rs.id = ?");
    $stmt->execute([$slipId]);
    $slip = $stmt->fetch();

    if ($slip) {
        if ($action_taken === 'finalize') {
            $conn->prepare("UPDATE request_slips SET finalized_status = 'finalized', dept_head_id = ?, dept_head_remarks = ?, finalized_at = NOW() WHERE id = ?")->execute([$_SESSION['user']['id'], $remarks, $slipId]);
            $srModel->updateStatus($slip['request_id'], 'finalized', 'Finalized by Department Head.');
            $notifModel->create($slip['user_id'], 'Service Request Finalized',
                'Your request ' . $slip['request_number'] . ' has been finalized by the Department Head. Seed packs will be released soon.');
            // Notify nursery
            $nurseryUsers = (new User($conn))->getAllByRole('nursery');
            foreach ($nurseryUsers as $n) {
                $notifModel->create($n['id'], 'New Release Order',
                    'Request ' . $slip['request_number'] . ' is finalized and ready for seed pack release.');
            }
            $success = "Request finalized. Nursery has been notified for seed pack release.";
        } elseif ($action_taken === 'reject') {
            $conn->prepare("UPDATE request_slips SET finalized_status = 'rejected', dept_head_id = ?, dept_head_remarks = ?, finalized_at = NOW() WHERE id = ?")->execute([$_SESSION['user']['id'], $remarks, $slipId]);
            $srModel->updateStatus($slip['request_id'], 'rejected', 'Rejected by Department Head: ' . $remarks);
            $notifModel->create($slip['user_id'], 'Service Request Rejected by Department Head',
                'Your request ' . $slip['request_number'] . ' was rejected. Reason: ' . $remarks);
            $success = "Request has been rejected.";
        }
    }
}

// Load slips
$focusId = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare("
    SELECT rs.*, sr.request_number, sr.activity_name, sr.target_location, sr.target_date,
           sr.seedling_type, sr.quantity_requested, sr.purpose,
           preparer.firstname AS prep_firstname, preparer.lastname AS prep_lastname,
           req_user.firstname AS req_firstname, req_user.lastname AS req_lastname,
           vr.site_location, vr.validation_date, vr.seed_packs_counted, vr.available_seedlings,
           vr.findings, vr.recommendation
    FROM request_slips rs
    JOIN service_requests sr ON rs.request_id = sr.id
    JOIN users preparer ON rs.prepared_by = preparer.id
    JOIN users req_user ON sr.user_id = req_user.id
    JOIN validation_reports vr ON rs.validation_id = vr.id
    WHERE rs.status = 'approved' AND (rs.finalized_status = 'pending' OR rs.finalized_status IS NULL)
    ORDER BY rs.approved_at DESC
");
$stmt->execute();
$slips = $stmt->fetchAll();
$focusSlip = $focusId ? array_values(array_filter($slips, fn($s) => $s['id'] == $focusId))[0] ?? null : null;
?>

<div class="ps-page-header">
    <h2>Finalize Service Requests</h2>
    <p>Review approved request slips and issue final approval or rejection.</p>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- List -->
    <div class="col-md-4">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Pending Finalization</h6>
            <?php if (empty($slips)): ?>
                <div class="empty-state"><p class="small">No requests pending.</p></div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($slips as $s): ?>
                    <a href="index.php?action=depthead_finalize&id=<?= $s['id'] ?>" class="text-decoration-none">
                        <div class="p-3 rounded border <?= $focusId == $s['id'] ? 'border-success bg-ps-pale' : '' ?>">
                            <div class="small fw-semibold text-ps-green"><?= htmlspecialchars($s['slip_number']) ?></div>
                            <div class="small"><?= htmlspecialchars($s['activity_name']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($s['req_firstname'] . ' ' . $s['req_lastname']) ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detail -->
    <div class="col-md-8">
        <?php if ($focusSlip): ?>
        <div class="ps-card mb-3">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h6 class="fw-bold text-ps-green mb-1"><?= htmlspecialchars($focusSlip['slip_number']) ?></h6>
                    <div class="small text-muted">Request: <?= htmlspecialchars($focusSlip['request_number']) ?></div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="ps-badge ps-badge-approved">Approved by MAO</span>
                    <!-- View Endorsement Letter -->
                    <a href="index.php?action=view_endorsement&slip_id=<?= $focusSlip['id'] ?>"
                       target="_blank" class="btn btn-sm btn-ps-primary">
                        <i class="bi bi-file-earmark-text me-1"></i>View Endorsement Letter
                    </a>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6"><div class="small text-muted">Activity</div><div><?= htmlspecialchars($focusSlip['activity_name']) ?></div></div>
                <div class="col-md-6"><div class="small text-muted">Organizer</div><div><?= htmlspecialchars($focusSlip['req_firstname'] . ' ' . $focusSlip['req_lastname']) ?></div></div>
                <div class="col-md-6"><div class="small text-muted">Location</div><div><?= htmlspecialchars($focusSlip['target_location']) ?></div></div>
                <div class="col-md-6"><div class="small text-muted">Target Date</div><div><?= date('F d, Y', strtotime($focusSlip['target_date'])) ?></div></div>
                <div class="col-md-4"><div class="small text-muted">Seedling Type</div><div><?= htmlspecialchars($focusSlip['seedling_type']) ?></div></div>
                <div class="col-md-4"><div class="small text-muted">Qty Approved</div><div class="fw-bold text-ps-green"><?= number_format($focusSlip['quantity_approved']) ?></div></div>
                <div class="col-md-4"><div class="small text-muted">Seed Packs Counted</div><div class="fw-bold"><?= number_format($focusSlip['seed_packs_counted']) ?></div></div>

                <!-- Endorsement details from MAO -->
                <?php if (!empty($focusSlip['endorsement_ref_number'])): ?>
                <div class="col-md-6">
                    <div class="small text-muted">Endorsement Ref #</div>
                    <div class="fw-semibold text-ps-green"><?= htmlspecialchars($focusSlip['endorsement_ref_number']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($focusSlip['filing_date'])): ?>
                <div class="col-md-6">
                    <div class="small text-muted">Filing Date</div>
                    <div><?= date('F d, Y', strtotime($focusSlip['filing_date'])) ?></div>
                </div>
                <?php endif; ?>
                <div class="col-12">
                    <div class="small text-muted">Endorsement Office</div>
                    <div class="fw-semibold"><?= htmlspecialchars($focusSlip['endorsement_office'] ?? '—') ?></div>
                </div>
                <div class="col-12">
                    <div class="small text-muted">Validation Findings</div>
                    <div class="small"><?= nl2br(htmlspecialchars($focusSlip['findings'])) ?></div>
                </div>
            </div>
        </div>

        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Department Head Decision</h6>

            <!-- Embedded endorsement letter preview -->
            <div class="mb-4">
                <label class="form-label fw-semibold small">
                    <i class="bi bi-file-earmark-text text-ps-green me-1"></i>
                    Endorsement Letter Preview
                </label>
                <div style="border:1.5px solid #d8e8d5; border-radius:10px; overflow:hidden; height:340px;">
                    <iframe src="index.php?action=view_endorsement&slip_id=<?= $focusSlip['id'] ?>&embed=1"
                            width="100%" height="100%" style="border:none;"
                            title="Endorsement Letter"></iframe>
                </div>
                <div class="mt-1 d-flex justify-content-end">
                    <a href="index.php?action=view_endorsement&slip_id=<?= $focusSlip['id'] ?>"
                       target="_blank" class="small text-ps-green text-decoration-none">
                        <i class="bi bi-arrows-fullscreen me-1"></i>Open full screen
                    </a>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="slip_id" value="<?= $focusSlip['id'] ?>">
                <div class="mb-4">
                    <label class="form-label">Remarks / Evaluation Notes</label>
                    <textarea name="remarks" class="form-control" rows="3"
                        placeholder="Add evaluation remarks or conditions..."></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="action_taken" value="finalize" class="btn btn-success">
                        <i class="bi bi-check2-circle me-1"></i>Finalize & Approve
                    </button>
                    <button type="submit" name="action_taken" value="reject" class="btn btn-danger"
                        onclick="return confirm('Reject this request?')">
                        <i class="bi bi-x-circle me-1"></i>Reject
                    </button>
                    <a href="index.php?action=depthead_finalize" class="btn btn-outline-secondary">Back</a>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="ps-card">
            <div class="empty-state">
                <i class="bi bi-arrow-left-circle" style="font-size:2.5rem;opacity:0.25;"></i>
                <p class="mt-2">Select a request from the list to finalize.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
