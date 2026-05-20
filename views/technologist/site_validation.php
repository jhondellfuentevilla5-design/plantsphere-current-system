<?php
$pageTitle = 'Site Validation';
include __DIR__ . '/../partials/layout_head.php';

$srModel       = new ServiceRequest($conn);
$forValidation = $srModel->getByStatus('for_validation');
$requestId     = intval($_GET['request_id'] ?? 0);
$selectedRequest = $requestId ? $srModel->getById($requestId) : null;
?>

<div class="ps-page-header">
    <h2>Site Validation</h2>
    <p>Conduct site validation and prepare the validation report with seed pack count.</p>
</div>

<!-- API response alert -->
<div id="apiAlert" class="d-none mb-3"></div>

<div class="row g-4">

    <!-- ── Left: Requests list ── -->
    <div class="col-md-4">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Requests for Validation</h6>
            <?php if (empty($forValidation)): ?>
                <div class="empty-state">
                    <i class="bi bi-clock" style="font-size:2rem;opacity:0.2;"></i>
                    <p class="small mt-2">No requests pending site validation.</p>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2" id="requestList">
                    <?php foreach ($forValidation as $req): ?>
                    <div class="request-item p-3 rounded border <?= $requestId == $req['id'] ? 'border-success bg-ps-pale' : '' ?>"
                         style="cursor:pointer;"
                         onclick="loadRequest(<?= $req['id'] ?>)">
                        <div class="small fw-semibold text-ps-green"><?= htmlspecialchars($req['request_number']) ?></div>
                        <div class="small"><?= htmlspecialchars($req['activity_name']) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($req['firstname'] . ' ' . $req['lastname']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Right: Validation form ── -->
    <div class="col-md-8">

        <!-- Request details card (loaded via API) -->
        <div id="requestDetailsCard" class="ps-card mb-3 <?= $selectedRequest ? '' : 'd-none' ?>">
            <h6 class="fw-bold text-ps-green mb-2">Request Details</h6>
            <div class="row g-2 small" id="requestDetailsBody">
                <?php if ($selectedRequest): ?>
                <div class="col-6"><span class="text-muted">Activity:</span> <span id="det-activity"><?= htmlspecialchars($selectedRequest['activity_name']) ?></span></div>
                <div class="col-6"><span class="text-muted">Organizer:</span> <span id="det-organizer"><?= htmlspecialchars($selectedRequest['firstname'] . ' ' . $selectedRequest['lastname']) ?></span></div>
                <div class="col-6"><span class="text-muted">Location:</span> <span id="det-location"><?= htmlspecialchars($selectedRequest['target_location']) ?></span></div>
                <div class="col-6"><span class="text-muted">Target Date:</span> <span id="det-date"><?= date('M d, Y', strtotime($selectedRequest['target_date'])) ?></span></div>
                <div class="col-6"><span class="text-muted">Seedling:</span> <span id="det-seedling"><?= htmlspecialchars($selectedRequest['seedling_type']) ?></span></div>
                <div class="col-6"><span class="text-muted">Qty Requested:</span> <span id="det-qty"><?= number_format($selectedRequest['quantity_requested']) ?></span></div>
                <?php else: ?>
                <div class="col-6"><span class="text-muted">Activity:</span> <span id="det-activity"></span></div>
                <div class="col-6"><span class="text-muted">Organizer:</span> <span id="det-organizer"></span></div>
                <div class="col-6"><span class="text-muted">Location:</span> <span id="det-location"></span></div>
                <div class="col-6"><span class="text-muted">Target Date:</span> <span id="det-date"></span></div>
                <div class="col-6"><span class="text-muted">Seedling:</span> <span id="det-seedling"></span></div>
                <div class="col-6"><span class="text-muted">Qty Requested:</span> <span id="det-qty"></span></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Validation form -->
        <div id="validationFormCard" class="ps-card <?= $selectedRequest ? '' : 'd-none' ?>">
            <h6 class="fw-bold text-ps-green mb-3">Validation Report Form</h6>
            <form id="validationForm">
                <input type="hidden" name="request_id" id="formRequestId"
                       value="<?= $requestId ?>">

                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="form-label">Site Location <span class="text-danger">*</span></label>
                        <input type="text" name="site_location" id="site_location" class="form-control"
                               value="<?= htmlspecialchars($selectedRequest['target_location'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Site Area (ha) <span class="text-danger">*</span></label>
                        <input type="number" name="site_area" class="form-control" step="0.01" min="0.01" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Validation Date <span class="text-danger">*</span></label>
                        <input type="date" name="validation_date" class="form-control"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Scheduled Planting Date <span class="text-danger">*</span></label>
                        <input type="date" name="schedule_date" id="schedule_date" class="form-control"
                               value="<?= $selectedRequest['target_date'] ?? '' ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Soil Condition <span class="text-danger">*</span></label>
                        <select name="soil_condition" class="form-select" required>
                            <option value="" disabled selected>Select condition</option>
                            <option>Excellent - Highly suitable</option>
                            <option>Good - Suitable</option>
                            <option>Fair - Moderately suitable</option>
                            <option>Poor - Needs improvement</option>
                            <option>Not suitable</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Site Accessibility <span class="text-danger">*</span></label>
                        <select name="accessibility" class="form-select" required>
                            <option value="" disabled selected>Select accessibility</option>
                            <option>Easily accessible</option>
                            <option>Accessible with vehicle</option>
                            <option>Accessible on foot only</option>
                            <option>Difficult to access</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Recommended Species <span class="text-danger">*</span></label>
                    <input type="text" name="recommended_species" id="recommended_species"
                           class="form-control" required>
                </div>

                <hr class="section-divider">
                <h6 class="fw-bold text-ps-green mb-3">Seed Pack Count</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Seed Packs Counted <span class="text-danger">*</span></label>
                        <input type="number" name="seed_packs_counted" class="form-control" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Available Seedlings <span class="text-danger">*</span></label>
                        <input type="number" name="available_seedlings" class="form-control" min="0" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Site Findings <span class="text-danger">*</span></label>
                    <textarea name="findings" class="form-control" rows="3" required
                              placeholder="Describe site conditions, observations, and findings..."></textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label">Recommendation <span class="text-danger">*</span></label>
                    <textarea name="recommendation" class="form-control" rows="3" required
                              placeholder="Provide recommendations for the tree planting activity..."></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-ps-primary" id="submitBtn">
                        <i class="bi bi-send me-1"></i>
                        <span id="submitBtnText">Submit Validation Report</span>
                    </button>
                    <a href="index.php?action=site_validation" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Empty state -->
        <div id="emptyState" class="ps-card <?= $selectedRequest ? 'd-none' : '' ?>">
            <div class="empty-state">
                <i class="bi bi-geo-alt" style="font-size:2.5rem;opacity:0.2;"></i>
                <p class="mt-2">Select a request from the list to begin site validation.</p>
            </div>
        </div>

    </div>
</div>

<script>
// ── Load request details via API ──────────────────────────
function loadRequest(requestId) {
    // Highlight selected
    document.querySelectorAll('.request-item').forEach(el => {
        el.classList.remove('border-success', 'bg-ps-pale');
    });
    event.currentTarget.classList.add('border-success', 'bg-ps-pale');

    // Show loading state
    document.getElementById('requestDetailsCard').classList.remove('d-none');
    document.getElementById('validationFormCard').classList.remove('d-none');
    document.getElementById('emptyState').classList.add('d-none');

    // Call API
    fetch('api/get_request_details.php?request_id=' + requestId, {
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            showAlert('danger', data.error || 'Failed to load request details.');
            return;
        }
        const req = data.request;

        // Populate details card
        document.getElementById('det-activity').textContent  = req.activity_name;
        document.getElementById('det-organizer').textContent = req.organizer;
        document.getElementById('det-location').textContent  = req.target_location;
        document.getElementById('det-date').textContent      = req.target_date;
        document.getElementById('det-seedling').textContent  = req.seedling_type;
        document.getElementById('det-qty').textContent       = req.quantity_requested.toLocaleString();

        // Pre-fill form fields
        document.getElementById('formRequestId').value        = req.id;
        document.getElementById('site_location').value        = req.target_location;
        document.getElementById('schedule_date').value        = req.target_date;
        document.getElementById('recommended_species').value  = req.seedling_type;
    })
    .catch(() => showAlert('danger', 'Network error. Please try again.'));
}

// ── Submit validation via API ─────────────────────────────
document.getElementById('validationForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const btn     = document.getElementById('submitBtn');
    const btnText = document.getElementById('submitBtnText');
    btn.disabled  = true;
    btnText.textContent = 'Submitting...';

    const formData = new FormData(this);
    const payload  = {};
    formData.forEach((val, key) => payload[key] = val);

    fetch('api/submit_validation.php', {
        method:      'POST',
        credentials: 'same-origin',
        headers:     { 'Content-Type': 'application/json' },
        body:        JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert('success',
                '<i class="bi bi-check-circle-fill me-2"></i>' +
                'Validation report submitted for <strong>' + data.request_number + '</strong>. ' +
                'Request moved to validated status.'
            );
            // Remove the request from the list
            document.querySelectorAll('.request-item').forEach(el => {
                if (el.querySelector('.text-ps-green') &&
                    el.querySelector('.text-ps-green').textContent.includes(data.request_number)) {
                    el.remove();
                }
            });
            // Reset form
            document.getElementById('validationForm').reset();
            document.getElementById('requestDetailsCard').classList.add('d-none');
            document.getElementById('validationFormCard').classList.add('d-none');
            document.getElementById('emptyState').classList.remove('d-none');
        } else {
            showAlert('danger', '<i class="bi bi-exclamation-circle-fill me-2"></i>' + data.error);
        }
    })
    .catch(() => showAlert('danger', 'Network error. Please try again.'))
    .finally(() => {
        btn.disabled = false;
        btnText.textContent = 'Submit Validation Report';
    });
});

// ── Alert helper ──────────────────────────────────────────
function showAlert(type, message) {
    const el = document.getElementById('apiAlert');
    el.className = 'alert alert-' + type + ' d-flex align-items-center gap-2';
    el.innerHTML = message;
    el.classList.remove('d-none');
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    if (type === 'success') {
        setTimeout(() => el.classList.add('d-none'), 6000);
    }
}

// ── Auto-load if request_id in URL ────────────────────────
<?php if ($requestId): ?>
loadRequest(<?= $requestId ?>);
<?php endif; ?>
</script>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
