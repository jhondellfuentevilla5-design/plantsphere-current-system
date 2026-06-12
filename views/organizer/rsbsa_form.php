<?php
$pageTitle = 'RSBSA Verification';
include __DIR__ . '/../partials/layout_head.php';

$rsbsaModel = new RsbsaRegistry($conn);
$existing   = $rsbsaModel->getByUser($_SESSION['user']['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing) {
    $rsbsa_number = trim($_POST['rsbsa_number'] ?? '');
    $barangay     = trim($_POST['barangay'] ?? '');
    $municipality = trim($_POST['municipality'] ?? '');
    $province     = trim($_POST['province'] ?? '');

    if (empty($rsbsa_number) || empty($barangay) || empty($municipality) || empty($province)) {
        $error = 'Please fill in all required fields.';
    } else {
        $data = [
            'user_id'           => $_SESSION['user']['id'],
            'rsbsa_number'      => $rsbsa_number,
            'barangay'          => $barangay,
            'municipality'      => $municipality,
            'province'          => $province,
            'farm_size'         => floatval($_POST['farm_size'] ?? 0),
            'crop_type'         => trim($_POST['crop_type'] ?? 'Mixed Farming'),
            'registration_date' => date('Y-m-d'),
        ];

        if ($rsbsaModel->register($data)) {
            $success  = "Your RSBSA number has been submitted for verification by the Agricultural Technologist.";
            $existing = $rsbsaModel->getByUser($_SESSION['user']['id']);
        } else {
            $error = "Submission failed. This RSBSA number may already be linked to another account.";
        }
    }
}
?>

<div class="ps-page-header">
    <h2>RSBSA Verification</h2>
    <p>Registry System for Basic Sectors in Agriculture — submit your existing RSBSA number for verification.</p>
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

<?php if ($existing): ?>

<!-- ── View existing submission ── -->
<div class="ps-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h6 class="fw-bold text-ps-green mb-1">Your RSBSA Submission</h6>
            <div class="small text-muted">Submitted for verification to the Agricultural Technologist</div>
        </div>
        <span class="ps-badge ps-badge-<?= $existing['status'] ?> fs-6">
            <?php if ($existing['status'] === 'verified'): ?>
                <i class="bi bi-patch-check-fill me-1"></i>
            <?php elseif ($existing['status'] === 'pending'): ?>
                <i class="bi bi-hourglass-split me-1"></i>
            <?php else: ?>
                <i class="bi bi-x-circle-fill me-1"></i>
            <?php endif; ?>
            <?= ucfirst($existing['status']) ?>
        </span>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="small text-muted mb-1">RSBSA Number</div>
            <div class="fw-bold text-ps-green" style="font-size:1.1rem;"><?= htmlspecialchars($existing['rsbsa_number']) ?></div>
        </div>
        <div class="col-md-4">
            <div class="small text-muted mb-1">Name</div>
            <div class="fw-semibold"><?= htmlspecialchars($_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['lastname']) ?></div>
        </div>
        <div class="col-md-4">
            <div class="small text-muted mb-1">Date Submitted</div>
            <div><?= date('F d, Y', strtotime($existing['registration_date'])) ?></div>
        </div>
        <div class="col-md-4">
            <div class="small text-muted mb-1">Barangay</div>
            <div><?= htmlspecialchars($existing['barangay']) ?></div>
        </div>
        <div class="col-md-4">
            <div class="small text-muted mb-1">Municipality</div>
            <div><?= htmlspecialchars($existing['municipality']) ?></div>
        </div>
        <div class="col-md-4">
            <div class="small text-muted mb-1">Province</div>
            <div><?= htmlspecialchars($existing['province']) ?></div>
        </div>
    </div>

    <hr class="section-divider">

    <?php if ($existing['status'] === 'pending'): ?>
        <div class="d-flex align-items-start gap-3 p-3 rounded" style="background:#fff8e1;border:1px solid #ffe082;">
            <i class="bi bi-hourglass-split text-warning mt-1" style="font-size:1.2rem;"></i>
            <div>
                <div class="fw-semibold small" style="color:#856404;">Awaiting Verification</div>
                <div class="small text-muted">The Agricultural Technologist will verify your RSBSA number against the official registry. You will be notified once verified.</div>
            </div>
        </div>
    <?php elseif ($existing['status'] === 'verified'): ?>
        <div class="d-flex align-items-start gap-3 p-3 rounded" style="background:#f0faf0;border:1px solid #b7dfb7;">
            <i class="bi bi-patch-check-fill text-success mt-1" style="font-size:1.2rem;"></i>
            <div>
                <div class="fw-semibold small text-success">RSBSA Verified</div>
                <div class="small text-muted">Your RSBSA number has been verified. You are eligible to submit tree planting requests.</div>
            </div>
        </div>
    <?php elseif ($existing['status'] === 'rejected'): ?>
        <div class="d-flex align-items-start gap-3 p-3 rounded" style="background:#fdf2f2;border:1px solid #f5c6cb;">
            <i class="bi bi-x-circle-fill text-danger mt-1" style="font-size:1.2rem;"></i>
            <div>
                <div class="fw-semibold small text-danger">RSBSA Not Verified</div>
                <div class="small text-muted">Your RSBSA number could not be verified. Please contact your local agriculture office to confirm your registration, then resubmit.</div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php else: ?>

<!-- ── Submit RSBSA Number ── -->
<div class="row g-4">
    <div class="col-md-7">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-1">Submit Your RSBSA Number</h6>
            <p class="small text-muted mb-4">
                If you are already registered in the RSBSA (Registry System for Basic Sectors in Agriculture),
                enter your RSBSA number below. The Agricultural Technologist will verify it against the official registry.
            </p>

            <form method="POST" action="index.php?action=rsbsa_form">
                <div class="mb-3">
                    <label class="form-label">RSBSA Number <span class="text-danger">*</span></label>
                    <input type="text" name="rsbsa_number" class="form-control form-control-lg"
                           placeholder="e.g. 01-001-001-000001"
                           value="<?= htmlspecialchars($_POST['rsbsa_number'] ?? '') ?>"
                           required autofocus>
                    <div class="form-text">
                        <i class="bi bi-info-circle me-1"></i>
                        Your RSBSA number is found on your registration certificate from the local agriculture office.
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Barangay <span class="text-danger">*</span></label>
                        <input type="text" name="barangay" class="form-control"
                               placeholder="e.g. Toril"
                               value="<?= htmlspecialchars($_POST['barangay'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Municipality / City <span class="text-danger">*</span></label>
                        <input type="text" name="municipality" class="form-control"
                               placeholder="e.g. Davao City"
                               value="<?= htmlspecialchars($_POST['municipality'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Province <span class="text-danger">*</span></label>
                        <input type="text" name="province" class="form-control"
                               placeholder="e.g. Davao del Sur"
                               value="<?= htmlspecialchars($_POST['province'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Farm Size (hectares)</label>
                        <input type="number" name="farm_size" class="form-control"
                               step="0.01" min="0" placeholder="e.g. 1.50"
                               value="<?= htmlspecialchars($_POST['farm_size'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Primary Crop / Intervention Type</label>
                        <select name="crop_type" class="form-select">
                            <option value="Mixed Farming" <?= ($_POST['crop_type'] ?? '') === 'Mixed Farming' ? 'selected' : '' ?>>Mixed Farming</option>
                            <option value="Rice" <?= ($_POST['crop_type'] ?? '') === 'Rice' ? 'selected' : '' ?>>Rice</option>
                            <option value="Corn" <?= ($_POST['crop_type'] ?? '') === 'Corn' ? 'selected' : '' ?>>Corn</option>
                            <option value="Vegetables" <?= ($_POST['crop_type'] ?? '') === 'Vegetables' ? 'selected' : '' ?>>Vegetables</option>
                            <option value="Fruit Trees" <?= ($_POST['crop_type'] ?? '') === 'Fruit Trees' ? 'selected' : '' ?>>Fruit Trees</option>
                            <option value="Agroforestry" <?= ($_POST['crop_type'] ?? '') === 'Agroforestry' ? 'selected' : '' ?>>Agroforestry</option>
                            <option value="Aquaculture" <?= ($_POST['crop_type'] ?? '') === 'Aquaculture' ? 'selected' : '' ?>>Aquaculture</option>
                            <option value="Other" <?= ($_POST['crop_type'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-ps-primary">
                        <i class="bi bi-send me-2"></i>Submit for Verification
                    </button>
                    <a href="index.php?action=dashboard" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Info panel -->
    <div class="col-md-5">
        <div class="ps-card h-100" style="background:var(--ps-green-pale);">
            <h6 class="fw-bold text-ps-green mb-3">
                <i class="bi bi-question-circle me-2"></i>What is RSBSA?
            </h6>
            <p class="small text-muted mb-3">
                The <strong>Registry System for Basic Sectors in Agriculture (RSBSA)</strong> is a database of farmers, fisherfolk, and other agricultural workers maintained by the Department of Agriculture.
            </p>
            <div class="small text-muted mb-2"><strong>How to verify your RSBSA number:</strong></div>
            <ul class="small text-muted ps-3" style="line-height:2;">
                <li>Contact your local Municipal Agriculture Office (MAO)</li>
                <li>Check your RSBSA registration certificate</li>
                <li>Visit the nearest DA satellite office</li>
            </ul>
            <hr class="section-divider">
            <div class="small text-muted">
                <i class="bi bi-shield-check text-ps-green me-1"></i>
                Your RSBSA number will be verified by the Agricultural Technologist before processing your request.
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
