<?php
$pageTitle = 'RSBSA Registration';
include __DIR__ . '/../partials/layout_head.php';
?>

<div class="ps-page-header">
    <h2>RSBSA Registration</h2>
    <p>Registry System for Basic Sectors in Agriculture (RSBSA) - Fill out this form to register.</p>
</div>

<?php
$rsbsaModel = new RsbsaRegistry($conn);
$existing = $rsbsaModel->getByUser($_SESSION['user']['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing) {
    $data = array_merge($_POST, ['user_id' => $_SESSION['user']['id']]);
    if ($rsbsaModel->register($data)) {
        $success = "RSBSA registration submitted successfully! Awaiting verification.";
        $existing = $rsbsaModel->getByUser($_SESSION['user']['id']);
    } else {
        $error = "Registration failed. RSBSA number may already exist.";
    }
}
?>

<?php if(isset($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if(isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($existing): ?>
<!-- View existing registration -->
<div class="ps-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-ps-green mb-0">Your RSBSA Registration</h6>
        <span class="ps-badge ps-badge-<?= $existing['status'] ?>"><?= ucfirst($existing['status']) ?></span>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="small text-muted">RSBSA Number</div>
            <div class="fw-semibold"><?= htmlspecialchars($existing['rsbsa_number']) ?></div>
        </div>
        <div class="col-md-6">
            <div class="small text-muted">Registration Date</div>
            <div class="fw-semibold"><?= date('F d, Y', strtotime($existing['registration_date'])) ?></div>
        </div>
        <div class="col-md-4">
            <div class="small text-muted">Barangay</div>
            <div><?= htmlspecialchars($existing['barangay']) ?></div>
        </div>
        <div class="col-md-4">
            <div class="small text-muted">Municipality</div>
            <div><?= htmlspecialchars($existing['municipality']) ?></div>
        </div>
        <div class="col-md-4">
            <div class="small text-muted">Province</div>
            <div><?= htmlspecialchars($existing['province']) ?></div>
        </div>
        <div class="col-md-6">
            <div class="small text-muted">Farm Size (hectares)</div>
            <div><?= number_format($existing['farm_size'], 2) ?> ha</div>
        </div>
        <div class="col-md-6">
            <div class="small text-muted">Primary Crop Type</div>
            <div><?= htmlspecialchars($existing['crop_type']) ?></div>
        </div>
    </div>
    <?php if ($existing['status'] === 'pending'): ?>
        <div class="alert alert-warning mt-3 py-2 small mb-0">
            Your registration is pending verification by the Agricultural Technologist.
        </div>
    <?php elseif ($existing['status'] === 'verified'): ?>
        <div class="alert alert-success mt-3 py-2 small mb-0">
            Your RSBSA registration has been verified. You are eligible to submit tree planting requests.
        </div>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- Registration Form -->
<div class="ps-card">
    <form method="POST" action="index.php?action=rsbsa_form">
        <h6 class="fw-bold text-ps-green mb-3">Personal Information</h6>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">First Name</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['user']['firstname']) ?>" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label">Last Name</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['user']['lastname']) ?>" disabled>
            </div>
        </div>

        <hr class="section-divider">
        <h6 class="fw-bold text-ps-green mb-3">Farm / Location Details</h6>
        <div class="mb-3">
            <label class="form-label">RSBSA Number <span class="text-danger">*</span></label>
            <input type="text" name="rsbsa_number" class="form-control" placeholder="e.g. 01-001-001-000001" required>
            <div class="form-text">Provided by your local agriculture office.</div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Barangay <span class="text-danger">*</span></label>
                <input type="text" name="barangay" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Municipality <span class="text-danger">*</span></label>
                <input type="text" name="municipality" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Province <span class="text-danger">*</span></label>
                <input type="text" name="province" class="form-control" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Farm Size (hectares) <span class="text-danger">*</span></label>
                <input type="number" name="farm_size" class="form-control" step="0.01" min="0.01" placeholder="e.g. 1.50" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Primary Crop Type <span class="text-danger">*</span></label>
                <select name="crop_type" class="form-select" required>
                    <option value="" disabled selected>Select crop type</option>
                    <option>Rice</option>
                    <option>Corn</option>
                    <option>Vegetables</option>
                    <option>Fruit Trees</option>
                    <option>Root Crops</option>
                    <option>Coconut</option>
                    <option>Sugarcane</option>
                    <option>Mixed Farming</option>
                    <option>Agroforestry</option>
                    <option>Other</option>
                </select>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">Registration Date <span class="text-danger">*</span></label>
            <input type="date" name="registration_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-ps-primary">Submit RSBSA Form</button>
            <a href="index.php?action=dashboard" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
