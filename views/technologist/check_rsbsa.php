<?php
$pageTitle = 'RSBSA Registry';
include __DIR__ . '/../partials/layout_head.php';

$rsbsaModel = new RsbsaRegistry($conn);
$srModel    = new ServiceRequest($conn);
$notifModel = new Notification($conn);
$userModel  = new User($conn);

// ── Handle: Assisted RSBSA registration ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assisted_register'])) {
    $targetUserId = intval($_POST['target_user_id']);
    $existing     = $rsbsaModel->getByUser($targetUserId);

    if ($existing) {
        $error = "This user already has an RSBSA registration on file.";
    } else {
        $data = array_merge($_POST, ['user_id' => $targetUserId]);
        if ($rsbsaModel->registerAssisted($data)) {
            // Auto-verify since technologist is registering directly
            $newReg = $rsbsaModel->getByUser($targetUserId);
            if ($newReg) {
                $rsbsaModel->updateStatus($newReg['id'], 'verified');
            }
            // Notify the organizer
            $notifModel->create($targetUserId, 'RSBSA Registration Completed',
                'Your RSBSA registration has been completed and verified by the Agricultural Technologist.');
            $success = "RSBSA registration completed and verified for the organizer.";
        } else {
            $error = "Registration failed. RSBSA number may already exist.";
        }
    }
}

// ── Handle: RSBSA status update ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rsbsa_id']) && !isset($_POST['assisted_register'])) {
    $rsbsaId   = intval($_POST['rsbsa_id']);
    $newStatus = $_POST['rsbsa_status'];
    $rsbsaModel->updateStatus($rsbsaId, $newStatus);
    $success = "RSBSA status updated to: " . ucfirst($newStatus);
}

// ── Handle: Proceed to site validation ───────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['proceed'])) {
    $reqId = intval($_POST['request_id']);
    $req   = $srModel->getById($reqId);
    $rsbsa = $rsbsaModel->checkByUser($req['user_id']);
    if ($rsbsa) {
        $srModel->updateStatus($reqId, 'for_validation', 'RSBSA verified. Proceeding to site validation.');
        $notifModel->create($req['user_id'], 'RSBSA Verified',
            'Your RSBSA registration has been verified. Your request ' . $req['request_number'] . ' is now scheduled for site validation.');
        $success = "Request moved to site validation stage.";
    } else {
        $error = "Organizer does not have a verified RSBSA registration.";
    }
}

$requestId    = intval($_GET['request_id'] ?? 0);
$focusRequest = $requestId ? $srModel->getById($requestId) : null;
$focusRsbsa   = $focusRequest ? $rsbsaModel->getByUser($focusRequest['user_id']) : null;
$allRsbsa     = $rsbsaModel->getAll();
$filter       = $_GET['filter'] ?? 'all';

// Get all organizers for the assisted registration dropdown
$organizers = $userModel->getAllByRole('community_organizer');
?>

<div class="ps-page-header">
    <h2>RSBSA Verification</h2>
    <p>Verify RSBSA numbers submitted by organizers against the official registry.</p>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- ── Focused request check ── -->
<?php if ($focusRequest): ?>
<div class="ps-card mb-4">
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <h6 class="fw-bold text-ps-green mb-1">
                <i class="bi bi-clipboard2-check me-2"></i>Request: <?= htmlspecialchars($focusRequest['request_number']) ?>
            </h6>
            <div class="small text-muted"><?= htmlspecialchars($focusRequest['activity_name']) ?></div>
        </div>
        <span class="ps-badge ps-badge-<?= $focusRequest['status'] ?>"><?= str_replace('_',' ',$focusRequest['status']) ?></span>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="small text-muted">Organizer</div>
            <div class="fw-semibold"><?= htmlspecialchars($focusRequest['firstname'] . ' ' . $focusRequest['lastname']) ?></div>
            <div class="small text-muted"><?= htmlspecialchars($focusRequest['email']) ?></div>
        </div>
        <div class="col-md-6">
            <div class="small text-muted">Target Location</div>
            <div><?= htmlspecialchars($focusRequest['target_location']) ?></div>
        </div>
    </div>

    <?php if ($focusRsbsa): ?>
        <!-- Has RSBSA -->
        <div class="alert alert-<?= $focusRsbsa['status'] === 'verified' ? 'success' : ($focusRsbsa['status'] === 'pending' ? 'warning' : 'danger') ?> py-2 mb-3">
            <div class="fw-semibold small">RSBSA Status: <?= ucfirst($focusRsbsa['status']) ?></div>
            <div class="small">
                RSBSA #: <strong><?= htmlspecialchars($focusRsbsa['rsbsa_number']) ?></strong> &nbsp;|&nbsp;
                <?= htmlspecialchars($focusRsbsa['barangay']) ?>, <?= htmlspecialchars($focusRsbsa['municipality']) ?>
            </div>
        </div>

        <?php if ($focusRsbsa['status'] === 'pending'): ?>
        <form method="POST" class="mb-3">
            <input type="hidden" name="rsbsa_id" value="<?= $focusRsbsa['id'] ?>">
            <div class="d-flex gap-2">
                <button type="submit" name="rsbsa_status" value="verified" class="btn btn-success btn-sm">
                    <i class="bi bi-check-circle me-1"></i>Verify RSBSA
                </button>
                <button type="submit" name="rsbsa_status" value="rejected" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-x-circle me-1"></i>Reject
                </button>
            </div>
        </form>
        <?php endif; ?>

        <?php if ($focusRsbsa['status'] === 'verified' && $focusRequest['status'] === 'under_review'): ?>
        <form method="POST">
            <input type="hidden" name="request_id" value="<?= $focusRequest['id'] ?>">
            <button type="submit" name="proceed" value="1" class="btn btn-ps-primary btn-sm">
                <i class="bi bi-arrow-right-circle me-1"></i>Proceed to Site Validation
            </button>
        </form>
        <?php endif; ?>

    <?php else: ?>
        <!-- NO RSBSA — organizer has not submitted yet -->
        <div class="d-flex align-items-start gap-3 p-3 rounded" style="background:#fff8e1;border:1.5px solid #ffe082;">
            <i class="bi bi-person-exclamation text-warning mt-1" style="font-size:1.3rem;"></i>
            <div>
                <div class="fw-semibold small" style="color:#856404;">No RSBSA Number Submitted</div>
                <div class="small text-muted mt-1">
                    <strong><?= htmlspecialchars($focusRequest['firstname'] . ' ' . $focusRequest['lastname']) ?></strong>
                    has not yet submitted their RSBSA number. They need to go to
                    <strong>RSBSA Verification</strong> in their dashboard and enter their RSBSA number first.
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="row g-4">

    <!-- ── Left: Summary stats ── -->
    <div class="col-md-4">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">
                <i class="bi bi-bar-chart me-2"></i>Registry Summary
            </h6>
            <?php
            $allStats   = $rsbsaModel->getAll();
            $pendingCnt  = count(array_filter($allStats, fn($r) => $r['status'] === 'pending'));
            $verifiedCnt = count(array_filter($allStats, fn($r) => $r['status'] === 'verified'));
            $rejectedCnt = count(array_filter($allStats, fn($r) => $r['status'] === 'rejected'));
            ?>
            <div class="d-flex flex-column gap-2">
                <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:#fff8e1;border:1px solid #ffe082;">
                    <span class="small fw-semibold" style="color:#856404;"><i class="bi bi-hourglass-split me-1"></i>Pending Verification</span>
                    <span class="fw-bold" style="color:#856404;"><?= $pendingCnt ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:#f0faf0;border:1px solid #b7dfb7;">
                    <span class="small fw-semibold text-success"><i class="bi bi-patch-check-fill me-1"></i>Verified</span>
                    <span class="fw-bold text-success"><?= $verifiedCnt ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:#fdf2f2;border:1px solid #f5c6cb;">
                    <span class="small fw-semibold text-danger"><i class="bi bi-x-circle-fill me-1"></i>Rejected</span>
                    <span class="fw-bold text-danger"><?= $rejectedCnt ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:#f8faf8;border:1px solid #d8e8d5;">
                    <span class="small fw-semibold text-muted"><i class="bi bi-people me-1"></i>Total Submissions</span>
                    <span class="fw-bold"><?= count($allStats) ?></span>
                </div>
            </div>
            <hr class="section-divider">
            <div class="small text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Organizers submit their existing RSBSA numbers. Verify each one against the official DA registry before processing their requests.
            </div>
        </div>
    </div>

    <!-- ── Right: Registry table ── -->
    <div class="col-md-8">
        <div class="ps-card">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h6 class="fw-bold mb-0 text-ps-green">
                    <i class="bi bi-table me-2"></i>RSBSA Registry
                </h6>
                <div class="d-flex gap-2 flex-wrap">
                    <?php foreach (['all','pending','verified','rejected'] as $s): ?>
                        <a href="index.php?action=check_rsbsa&filter=<?= $s ?><?= $requestId ? '&request_id='.$requestId : '' ?>"
                           class="btn btn-sm <?= $filter === $s ? 'btn-ps-primary' : 'btn-outline-secondary' ?>">
                            <?= ucfirst($s) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php
            $filtered = $filter === 'all'
                ? $allRsbsa
                : array_filter($allRsbsa, fn($r) => $r['status'] === $filter);
            ?>

            <?php if (empty($filtered)): ?>
                <div class="empty-state">
                    <i class="bi bi-person-x" style="font-size:2.5rem;opacity:0.2;"></i>
                    <p class="mt-2">No RSBSA registrations found.</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="ps-table">
                    <thead>
                        <tr>
                            <th>RSBSA #</th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Farm</th>
                            <th>Crop</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtered as $r): ?>
                        <tr>
                            <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($r['rsbsa_number']) ?></td>
                            <td>
                                <div class="small fw-semibold"><?= htmlspecialchars($r['firstname'] . ' ' . $r['lastname']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($r['email']) ?></div>
                            </td>
                            <td class="small"><?= htmlspecialchars($r['barangay']) ?>, <?= htmlspecialchars($r['municipality']) ?></td>
                            <td class="small"><?= number_format($r['farm_size'], 2) ?> ha</td>
                            <td class="small"><?= htmlspecialchars($r['crop_type']) ?></td>
                            <td><span class="ps-badge ps-badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                            <td>
                                <?php if ($r['status'] === 'pending'): ?>
                                <form method="POST" class="d-flex gap-1">
                                    <input type="hidden" name="rsbsa_id" value="<?= $r['id'] ?>">
                                    <button type="submit" name="rsbsa_status" value="verified"
                                            class="btn btn-sm btn-success" title="Verify">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <button type="submit" name="rsbsa_status" value="rejected"
                                            class="btn btn-sm btn-outline-danger" title="Reject">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span class="small text-muted">—</span>
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

<style>
/* Assist banner */
.rsbsa-assist-banner {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 14px 16px;
    background: #fff8e1;
    border: 1.5px solid #ffe082;
    border-radius: 10px;
    margin-bottom: 4px;
}
.rsbsa-assist-icon {
    width: 40px; height: 40px;
    background: #fff3cd;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; color: #f0a500;
    flex-shrink: 0;
}
.rsbsa-assist-title { font-size: 0.9rem; font-weight: 700; color: #856404; margin-bottom: 3px; }
.rsbsa-assist-desc  { font-size: 0.82rem; color: #6c757d; line-height: 1.5; }

/* Assist form */
.assist-form-wrap {
    border: 1.5px solid #d8e8d5;
    border-radius: 10px;
    overflow: hidden;
}
.assist-form-header {
    background: var(--ps-green-pale);
    padding: 10px 16px;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--ps-green);
    border-bottom: 1px solid #d8e8d5;
}
.assist-form-body { padding: 20px; }
</style>

<script>
// No JS needed — pure server-side verification
</script>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
