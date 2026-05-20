<?php
$pageTitle = 'Review Request';
include __DIR__ . '/../partials/layout_head.php';
?>

<?php
$srModel = new ServiceRequest($conn);
$pmModel = new PlantingMaterial($conn);
$notifModel = new Notification($conn);
$id = intval($_GET['id'] ?? 0);
$request = $srModel->getById($id);

if (!$request) {
    echo '<div class="alert alert-danger">Request not found.</div>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_taken = $_POST['action_taken'];
    $remarks = trim($_POST['remarks'] ?? '');

    if ($action_taken === 'refer') {
        $srModel->updateStatus($id, 'under_review', $remarks, $_SESSION['user']['id']);
        // Notify agricultural technologists
        $techs = (new User($conn))->getAllByRole('agricultural_technologist');
        foreach ($techs as $t) {
            $notifModel->create($t['id'], 'Request Referred for Review',
                'Request ' . $request['request_number'] . ' has been referred for RSBSA check and site validation.');
        }
        // Notify organizer
        $notifModel->create($request['user_id'], 'Request Under Review',
            'Your request ' . $request['request_number'] . ' is now under review by the Agricultural Technologist.');
        $success = "Request referred to Agricultural Technologist successfully.";
        $request = $srModel->getById($id);
    } elseif ($action_taken === 'reject') {
        $srModel->updateStatus($id, 'rejected', $remarks);
        $notifModel->create($request['user_id'], 'Request Rejected',
            'Your request ' . $request['request_number'] . ' was rejected. Reason: ' . $remarks);
        $success = "Request has been rejected.";
        $request = $srModel->getById($id);
    }
}

$materials = $pmModel->getAll();
$matchedMaterial = null;
foreach ($materials as $m) {
    if (stripos($m['material_name'], $request['seedling_type']) !== false ||
        stripos($request['seedling_type'], $m['material_name']) !== false) {
        $matchedMaterial = $m;
        break;
    }
}
?>

<div class="ps-page-header">
    <h2>Review Request</h2>
    <p>Explain available seedling materials and refer to the appropriate officer.</p>
</div>

<?php if(isset($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- Request Details -->
<div class="ps-card mb-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h6 class="fw-bold text-ps-green mb-1"><?= htmlspecialchars($request['activity_name']) ?></h6>
            <div class="small text-muted"><?= htmlspecialchars($request['request_number']) ?></div>
        </div>
        <span class="ps-badge ps-badge-<?= $request['status'] ?>"><?= str_replace('_', ' ', $request['status']) ?></span>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="small text-muted">Submitted By</div>
            <div class="fw-semibold"><?= htmlspecialchars($request['firstname'] . ' ' . $request['lastname']) ?></div>
            <div class="small text-muted"><?= htmlspecialchars($request['email']) ?></div>
        </div>
        <div class="col-md-6">
            <div class="small text-muted">Target Location</div>
            <div><?= htmlspecialchars($request['target_location']) ?></div>
        </div>
        <div class="col-md-4">
            <div class="small text-muted">Target Date</div>
            <div><?= date('F d, Y', strtotime($request['target_date'])) ?></div>
        </div>
        <div class="col-md-4">
            <div class="small text-muted">Participants</div>
            <div><?= number_format($request['number_of_participants']) ?></div>
        </div>
        <div class="col-md-4">
            <div class="small text-muted">Submitted</div>
            <div><?= date('M d, Y', strtotime($request['created_at'])) ?></div>
        </div>
        <div class="col-12">
            <div class="small text-muted">Purpose</div>
            <div><?= nl2br(htmlspecialchars($request['purpose'])) ?></div>
        </div>
        <?php if (!empty($request['request_letter'])): 
            $letterPath = $request['request_letter'];
            $letterExt  = strtolower(pathinfo($letterPath, PATHINFO_EXTENSION));
            $letterName = basename($letterPath);
            $isPdf      = $letterExt === 'pdf';
        ?>
        <div class="col-12">
            <div class="small text-muted fw-semibold mb-2">
                <i class="bi bi-paperclip me-1"></i>Attached Request Letter
            </div>

            <!-- File info bar -->
            <div class="letter-bar">
                <div class="letter-bar-icon <?= $isPdf ? 'pdf' : 'doc' ?>">
                    <i class="bi <?= $isPdf ? 'bi-file-earmark-pdf' : 'bi-file-earmark-word' ?>"></i>
                </div>
                <div class="letter-bar-info">
                    <div class="letter-bar-name"><?= htmlspecialchars($letterName) ?></div>
                    <div class="letter-bar-type"><?= strtoupper($letterExt) ?> Document</div>
                </div>
                <div class="letter-bar-actions">
                    <?php if ($isPdf): ?>
                        <button type="button" class="btn btn-sm btn-ps-primary"
                                onclick="toggleLetterViewer()">
                            <i class="bi bi-eye me-1"></i>View Letter
                        </button>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars($letterPath) ?>" download
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-download me-1"></i>Download
                    </a>
                    <?php if (!$isPdf): ?>
                        <a href="https://docs.google.com/viewer?url=<?= urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/' . $letterPath) ?>"
                           target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Open in Viewer
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isPdf): ?>
            <!-- Inline PDF viewer (collapsible) -->
            <div class="letter-viewer d-none" id="letterViewer">
                <div class="letter-viewer-toolbar">
                    <span class="small fw-semibold text-muted">
                        <i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                        <?= htmlspecialchars($letterName) ?>
                    </span>
                    <div class="d-flex gap-2">
                        <a href="<?= htmlspecialchars($letterPath) ?>" target="_blank"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrows-fullscreen me-1"></i>Full Screen
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="toggleLetterViewer()">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <iframe src="<?= htmlspecialchars($letterPath) ?>#toolbar=1&navpanes=0"
                        class="letter-viewer-frame"
                        id="letterFrame"
                        title="Request Letter">
                    <p class="p-3 text-muted small">
                        Your browser cannot display PDFs inline.
                        <a href="<?= htmlspecialchars($letterPath) ?>">Download the file</a> instead.
                    </p>
                </iframe>
            </div>
            <?php else: ?>
            <!-- DOC/DOCX notice -->
            <div class="letter-doc-notice">
                <i class="bi bi-info-circle text-primary me-2"></i>
                <span class="small text-muted">
                    Word documents cannot be previewed directly in the browser.
                    Use <strong>Download</strong> to open in Microsoft Word, or
                    <strong>Open in Viewer</strong> to view via Google Docs.
                </span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Seedling Availability Info -->
<div class="ps-card mb-4">
    <h6 class="fw-bold text-ps-green mb-3">Seedling Material Availability</h6>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="small text-muted">Requested Seedling Type</div>
            <div class="fw-semibold"><?= htmlspecialchars($request['seedling_type']) ?></div>
            <div class="small text-muted mt-1">Quantity Requested: <strong><?= number_format($request['quantity_requested']) ?></strong></div>
        </div>
        <div class="col-md-6">
            <?php if ($matchedMaterial): ?>
                <div class="alert alert-success py-2 mb-0">
                    <div class="small fw-semibold">Available in Inventory</div>
                    <div class="small"><?= htmlspecialchars($matchedMaterial['material_name']) ?>: 
                        <strong><?= number_format($matchedMaterial['quantity']) ?> <?= $matchedMaterial['unit'] ?></strong>
                    </div>
                    <?php if ($matchedMaterial['quantity'] >= $request['quantity_requested']): ?>
                        <div class="small text-success mt-1">✓ Sufficient stock available</div>
                    <?php else: ?>
                        <div class="small text-warning mt-1">⚠ Insufficient stock — only <?= $matchedMaterial['quantity'] ?> available</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning py-2 mb-0">
                    <div class="small">Requested seedling type not found in current inventory. Verify with Agricultural Technologist.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Action Form -->
<?php if (in_array($request['status'], ['pending', 'barangay_approved'])): ?>
<div class="ps-card">
    <h6 class="fw-bold text-ps-green mb-3">Take Action</h6>
    <?php if ($request['status'] === 'barangay_approved'): ?>
    <div class="alert alert-success py-2 small mb-3">
        <i class="bi bi-check-circle-fill me-1"></i>
        This proposal has been <strong>approved by the Barangay Captain</strong> and is ready for stock verification and referral.
    </div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Remarks / Notes</label>
            <textarea name="remarks" class="form-control" rows="3" placeholder="Add any notes or remarks about this request..."></textarea>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" name="action_taken" value="refer" class="btn btn-ps-primary">
                Refer to Agricultural Technologist
            </button>
            <button type="submit" name="action_taken" value="reject" class="btn btn-danger"
                onclick="return confirm('Are you sure you want to reject this request?')">
                Reject Request
            </button>
            <a href="index.php?action=view_requests" class="btn btn-outline-secondary">Back</a>
        </div>
    </form>
</div>
<?php else: ?>
<div class="ps-card">
    <div class="alert alert-info py-2 mb-0 small">
        This request has already been processed. Status: <strong><?= str_replace('_', ' ', $request['status']) ?></strong>
        <?php if ($request['remarks']): ?>
            <br>Remarks: <?= htmlspecialchars($request['remarks']) ?>
        <?php endif; ?>
    </div>
    <div class="mt-3">
        <a href="index.php?action=view_requests" class="btn btn-outline-secondary btn-sm">Back to Requests</a>
    </div>
</div>
<?php endif; ?>

<style>
/* ── File info bar ── */
.letter-bar {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    background: #f8faf8;
    border: 1.5px solid #d8e8d5;
    border-radius: 10px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}
.letter-bar-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}
.letter-bar-icon.pdf  { background: #fff0f0; color: #dc3545; }
.letter-bar-icon.doc  { background: #e8f0ff; color: #0d6efd; }
.letter-bar-name { font-size: 0.875rem; font-weight: 600; color: #1a2e1a; word-break: break-all; }
.letter-bar-type { font-size: 0.75rem; color: #6c757d; }
.letter-bar-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-left: auto; }

/* ── Inline PDF viewer ── */
.letter-viewer {
    border: 1.5px solid #d8e8d5;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 4px;
}
.letter-viewer-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    background: #f0f5f0;
    border-bottom: 1px solid #d8e8d5;
    gap: 10px;
    flex-wrap: wrap;
}
.letter-viewer-frame {
    width: 100%;
    height: 680px;
    border: none;
    display: block;
    background: #fff;
}

/* ── DOC notice ── */
.letter-doc-notice {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 10px 14px;
    background: #f0f5ff;
    border: 1px solid #c8d8ff;
    border-radius: 8px;
    margin-top: 4px;
}
</style>

<script>
function toggleLetterViewer() {
    const viewer = document.getElementById('letterViewer');
    const isHidden = viewer.classList.contains('d-none');
    viewer.classList.toggle('d-none', !isHidden);

    // Scroll to viewer when opening
    if (isHidden) {
        setTimeout(() => viewer.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
    }
}
</script>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
