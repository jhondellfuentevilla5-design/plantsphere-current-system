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
    <h2>RSBSA Registry</h2>
    <p>Verify registrations, assist unregistered farmers, and process requests.</p>
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
        <!-- NO RSBSA — show assisted registration inline -->
        <div class="rsbsa-assist-banner">
            <div class="rsbsa-assist-icon">
                <i class="bi bi-person-exclamation"></i>
            </div>
            <div class="rsbsa-assist-body">
                <div class="rsbsa-assist-title">No RSBSA Registration Found</div>
                <div class="rsbsa-assist-desc">
                    <strong><?= htmlspecialchars($focusRequest['firstname'] . ' ' . $focusRequest['lastname']) ?></strong>
                    has not submitted an RSBSA registration. You can register them on their behalf below.
                </div>
            </div>
        </div>

        <!-- Assisted registration form for this specific organizer -->
        <div class="assist-form-wrap mt-3" id="assistFormFocus">
            <div class="assist-form-header">
                <i class="bi bi-person-plus-fill me-2"></i>
                Assisted RSBSA Registration — <?= htmlspecialchars($focusRequest['firstname'] . ' ' . $focusRequest['lastname']) ?>
            </div>
            <div class="assist-form-body">
                <form method="POST">
                    <input type="hidden" name="assisted_register" value="1">
                    <input type="hidden" name="target_user_id" value="<?= $focusRequest['user_id'] ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($focusRequest['firstname']) ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($focusRequest['lastname']) ?>" disabled>
                        </div>
                    </div>

                    <?php include __DIR__ . '/rsbsa_fields.php'; ?>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-ps-primary">
                            <i class="bi bi-person-check me-1"></i>Register & Verify
                        </button>
                    </div>
                    <div class="form-text mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Registration will be automatically verified since you are registering on their behalf.
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="row g-4">

    <!-- ── Left: Assisted registration for any organizer ── -->
    <div class="col-md-4">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-1">
                <i class="bi bi-person-plus me-2"></i>Assisted Registration
            </h6>
            <p class="small text-muted mb-3">
                Register an organizer or farmer who is present but not yet in the system.
            </p>

            <form method="POST" id="assistedForm">
                <input type="hidden" name="assisted_register" value="1">

                <div class="mb-3">
                    <label class="form-label">Select Organizer <span class="text-danger">*</span></label>
                    <select name="target_user_id" class="form-select" required
                            onchange="checkExistingRsbsa(this.value)">
                        <option value="" disabled selected>Choose organizer</option>
                        <?php foreach ($organizers as $org):
                            $hasRsbsa = $rsbsaModel->getByUser($org['id']);
                        ?>
                        <option value="<?= $org['id'] ?>"
                                data-name="<?= htmlspecialchars($org['firstname'] . ' ' . $org['lastname']) ?>"
                                data-has="<?= $hasRsbsa ? '1' : '0' ?>"
                                data-status="<?= $hasRsbsa ? $hasRsbsa['status'] : '' ?>">
                            <?= htmlspecialchars($org['firstname'] . ' ' . $org['lastname']) ?>
                            <?= $hasRsbsa ? ' (' . ucfirst($hasRsbsa['status']) . ')' : ' — No RSBSA' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <!-- Status indicator -->
                    <div id="rsbsaStatusHint" class="mt-2 d-none"></div>
                </div>

                <!-- Fields shown after selecting an unregistered organizer -->
                <div id="assistedFields" class="d-none">
                    <?php include __DIR__ . '/rsbsa_fields.php'; ?>
                    <button type="submit" class="btn btn-ps-primary w-100 mt-3">
                        <i class="bi bi-person-check me-1"></i>Register & Verify
                    </button>
                    <div class="form-text mt-2">
                        <i class="bi bi-shield-check me-1"></i>
                        Registration will be automatically verified.
                    </div>
                </div>
            </form>
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
function checkExistingRsbsa(userId) {
    if (!userId) return;
    const sel    = document.querySelector('[name="target_user_id"]');
    const opt    = sel.options[sel.selectedIndex];
    const has    = opt.dataset.has === '1';
    const status = opt.dataset.status;
    const name   = opt.dataset.name;
    const hint   = document.getElementById('rsbsaStatusHint');
    const fields = document.getElementById('assistedFields');

    hint.classList.remove('d-none');

    if (has) {
        hint.innerHTML = `
            <div class="alert alert-${status === 'verified' ? 'success' : status === 'pending' ? 'warning' : 'danger'} py-2 small mb-0">
                <i class="bi bi-info-circle me-1"></i>
                <strong>${name}</strong> already has an RSBSA registration
                with status: <strong>${status.charAt(0).toUpperCase() + status.slice(1)}</strong>.
                No new registration needed.
            </div>`;
        fields.classList.add('d-none');
    } else {
        hint.innerHTML = `
            <div class="alert alert-warning py-2 small mb-0">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>${name}</strong> has no RSBSA registration.
                Fill in the details below to register them.
            </div>`;
        fields.classList.remove('d-none');
    }
}
</script>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
