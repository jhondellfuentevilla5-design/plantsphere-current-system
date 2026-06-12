<?php
$pageTitle = 'Review Proposals';
include __DIR__ . '/../partials/layout_head.php';

$barangayModel = new BarangayApproval($conn);
$srModel       = new ServiceRequest($conn);
$notifModel    = new Notification($conn);

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approval_id'])) {
    $approvalId = intval($_POST['approval_id']);
    $action_taken = $_POST['action_taken'];
    $remarks = trim($_POST['remarks'] ?? '');

    // Get the approval record to find request_id
    $stmt = $conn->prepare("SELECT * FROM barangay_approvals WHERE id = ?");
    $stmt->execute([$approvalId]);
    $approval = $stmt->fetch();

    if ($approval) {
        if ($action_taken === 'approve') {
            $barangayModel->updateStatus($approvalId, 'approved', $remarks);
            $srModel->updateStatus($approval['request_id'], 'barangay_approved', 'Proposal approved by Barangay Captain.');
            $req = $srModel->getById($approval['request_id']);
            $notifModel->create($req['user_id'], 'Proposal Approved by Barangay Captain',
                'Your proposal for ' . $req['request_number'] . ' has been approved. It will now be forwarded for processing.');
            // Notify affairs workers
            $affairsWorkers = (new User($conn))->getAllByRole('community_affairs_worker');
            foreach ($affairsWorkers as $aw) {
                $notifModel->create($aw['id'], 'New Approved Proposal',
                    'Request ' . $req['request_number'] . ' has been approved by the Barangay Captain.');
            }
            $success = "Proposal approved and forwarded to Community Affairs Worker.";
        } elseif ($action_taken === 'reject') {
            $barangayModel->updateStatus($approvalId, 'rejected', $remarks);
            $srModel->updateStatus($approval['request_id'], 'rejected', 'Rejected by Barangay Captain: ' . $remarks);
            $req = $srModel->getById($approval['request_id']);
            $notifModel->create($req['user_id'], 'Proposal Rejected',
                'Your proposal ' . $req['request_number'] . ' was rejected by the Barangay Captain. Reason: ' . $remarks);
            $success = "Proposal has been rejected.";
        }
    }
}

$pending = $barangayModel->getPendingForCaptain($_SESSION['user']['id']);
$filter  = $_GET['filter'] ?? 'pending';
$all     = $barangayModel->getAllForCaptain($_SESSION['user']['id']);
$display = $filter === 'all' ? $all : array_filter($all, fn($r) => $r['status'] === $filter);
?>

<div class="ps-page-header">
    <h2>Proposal Review</h2>
    <p>Validate proposal letters submitted by community organizers.</p>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<div class="ps-card">
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <?php foreach (['pending','approved','rejected','all'] as $f): ?>
            <a href="index.php?action=captain_proposals&filter=<?= $f ?>"
               class="btn btn-sm <?= $filter === $f ? 'btn-ps-primary' : 'btn-outline-secondary' ?>">
                <?= ucfirst($f) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($display)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox" style="font-size:2.5rem;opacity:0.25;"></i>
            <p class="mt-2">No proposals found.</p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="ps-table">
            <thead><tr><th>Request #</th><th>Organizer</th><th>Activity</th><th>Location</th><th>Date</th><th>Letter</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($display as $p): ?>
                <tr>
                    <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($p['request_number']) ?></td>
                    <td class="small"><?= htmlspecialchars($p['firstname'] . ' ' . $p['lastname']) ?></td>
                    <td><?= htmlspecialchars($p['activity_name']) ?></td>
                    <td class="small"><?= htmlspecialchars($p['target_location']) ?></td>
                    <td class="small"><?= date('M d, Y', strtotime($p['target_date'])) ?></td>
                    <td>
                        <?php if (!empty($p['request_letter'])): ?>
                            <?php
                                $letterPath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/' . ltrim($p['request_letter'], '/');
                                $ext = strtolower(pathinfo($p['request_letter'], PATHINFO_EXTENSION));
                            ?>
                            <div class="d-flex gap-1">
                                <?php if ($ext === 'pdf'): ?>
                                    <button type="button" class="btn btn-sm btn-ps-primary"
                                        onclick="previewLetter('<?= htmlspecialchars($letterPath) ?>', '<?= htmlspecialchars(addslashes($p['activity_name'])) ?>')">
                                        <i class="bi bi-eye me-1"></i>Preview
                                    </button>
                                <?php endif; ?>
                                <a href="<?= htmlspecialchars($letterPath) ?>" target="_blank"
                                   class="btn btn-sm btn-outline-secondary" title="Open in new tab">
                                    <i class="bi bi-file-earmark-arrow-down me-1"></i><?= strtoupper($ext) ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="ps-badge ps-badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                    <td>
                        <?php if ($p['status'] === 'pending'): ?>
                        <?php
                            // Build absolute letter path
                            $lp = !empty($p['request_letter'])
                                ? rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/' . ltrim($p['request_letter'], '/')
                                : '';
                            $lp = htmlspecialchars($lp);
                        ?>
                        <button type="button" class="btn btn-sm btn-ps-primary"
                            onclick="openReview(
                                <?= $p['id'] ?>,
                                '<?= htmlspecialchars(addslashes($p['activity_name'])) ?>',
                                '<?= addslashes($lp) ?>',
                                '<?= htmlspecialchars(addslashes($p['firstname'] . ' ' . $p['lastname'])) ?>',
                                '<?= htmlspecialchars(addslashes($p['target_location'])) ?>',
                                '<?= date('M d, Y', strtotime($p['target_date'])) ?>',
                                '<?= htmlspecialchars(addslashes($p['seedling_type'] ?? '')) ?>',
                                '<?= number_format($p['quantity_requested'] ?? 0) ?>',
                                '<?= htmlspecialchars(addslashes(mb_substr($p['purpose'] ?? '', 0, 300))) ?>'
                            )">
                            <i class="bi bi-eye me-1"></i>Review
                        </button>
                        <?php else: ?>
                            <span class="small text-muted"><?= $p['remarks'] ? htmlspecialchars(substr($p['remarks'],0,30)).'...' : '—' ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="bi bi-envelope-check me-2"></i>Review Proposal</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="approval_id" id="reviewApprovalId">

                    <!-- Proposal Details Summary -->
                    <div class="p-3 rounded mb-3" style="background:#f0faf0; border:1px solid #b7dfb7;">
                        <div class="row g-2 small">
                            <div class="col-12">
                                <span class="text-muted">Activity:</span>
                                <strong id="reviewActivityName" class="ms-1"></strong>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted">Organizer:</span>
                                <span id="reviewOrganizer" class="ms-1"></span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted">Location:</span>
                                <span id="reviewLocation" class="ms-1"></span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">Date:</span>
                                <span id="reviewDate" class="ms-1"></span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">Seedling:</span>
                                <span id="reviewSeedling" class="ms-1"></span>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">Quantity:</span>
                                <span id="reviewQty" class="ms-1 fw-bold text-ps-green"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Purpose -->
                    <div id="reviewPurposeWrap" class="mb-3 d-none">
                        <label class="form-label fw-semibold small">Purpose / Justification</label>
                        <div id="reviewPurpose" class="p-2 rounded small"
                             style="background:#f8faf8; border:1px solid #d8e8d5; line-height:1.7; max-height:120px; overflow-y:auto;"></div>
                    </div>

                    <!-- Letter preview -->
                    <div id="letterPreviewWrap" class="mb-3 d-none">
                        <label class="form-label fw-semibold">Uploaded Request Letter</label>
                        <div class="border rounded overflow-hidden" style="height:380px;">
                            <iframe id="letterIframe" src="" width="100%" height="100%"
                                    style="border:none;" title="Request Letter"></iframe>
                        </div>
                        <div class="mt-1">
                            <a id="letterDownloadLink" href="#" target="_blank"
                               class="small text-ps-green text-decoration-none">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Open in new tab
                            </a>
                        </div>
                    </div>
                    <div id="noLetterMsg" class="alert alert-info d-none py-2 small">
                        <i class="bi bi-info-circle me-1"></i>
                        This proposal was submitted via form (no letter attachment). Review the details above.
                    </div>

                    <label class="form-label">Remarks / Notes</label>
                    <textarea name="remarks" class="form-control" rows="2"
                        placeholder="Add your remarks or reason for decision..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="action_taken" value="reject" class="btn btn-danger"
                        onclick="return confirm('Reject this proposal?')">
                        <i class="bi bi-x-circle me-1"></i>Reject
                    </button>
                    <button type="submit" name="action_taken" value="approve" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- PDF Preview Modal (standalone) -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold" id="previewModalTitle">Request Letter</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" style="height:80vh;">
                <iframe id="previewIframe" src="" width="100%" height="100%"
                        style="border:none;" title="Letter Preview"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
function openReview(id, name, letterPath, organizer, location, date, seedling, qty, purpose) {
    document.getElementById('reviewApprovalId').value = id;
    document.getElementById('reviewActivityName').textContent  = name;
    document.getElementById('reviewOrganizer').textContent     = organizer;
    document.getElementById('reviewLocation').textContent      = location;
    document.getElementById('reviewDate').textContent          = date;
    document.getElementById('reviewSeedling').textContent      = seedling || '—';
    document.getElementById('reviewQty').textContent           = qty || '—';

    // Purpose
    const purposeWrap = document.getElementById('reviewPurposeWrap');
    const purposeEl   = document.getElementById('reviewPurpose');
    if (purpose && purpose.trim() && purpose !== '[See attached request letter]') {
        purposeEl.textContent = purpose;
        purposeWrap.classList.remove('d-none');
    } else {
        purposeWrap.classList.add('d-none');
    }

    // Letter
    const wrap     = document.getElementById('letterPreviewWrap');
    const noLetter = document.getElementById('noLetterMsg');
    const iframe   = document.getElementById('letterIframe');
    const dlLink   = document.getElementById('letterDownloadLink');

    if (letterPath) {
        iframe.src  = letterPath;
        dlLink.href = letterPath;
        wrap.classList.remove('d-none');
        noLetter.classList.add('d-none');
    } else {
        iframe.src = '';
        wrap.classList.add('d-none');
        noLetter.classList.remove('d-none');
    }

    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}

function previewLetter(path, name) {
    document.getElementById('previewIframe').src = path;
    document.getElementById('previewModalTitle').textContent = name;
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}
</script>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
