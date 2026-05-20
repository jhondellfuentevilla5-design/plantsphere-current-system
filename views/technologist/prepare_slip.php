<?php
$pageTitle = 'Request Slips';
include __DIR__ . '/../partials/layout_head.php';
?>

<div class="ps-page-header">
    <h2>Request Slips</h2>
    <p>Prepare and manage request slips for validated tree planting requests.</p>
</div>

<?php
$srModel = new ServiceRequest($conn);
$vrModel = new ValidationReport($conn);
$rsModel = new RequestSlip($conn);
$notifModel = new Notification($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_slip'])) {
    $data = [
        'request_id'         => intval($_POST['request_id']),
        'validation_id'      => intval($_POST['validation_id']),
        'prepared_by'        => $_SESSION['user']['id'],
        'materials_requested'=> trim($_POST['materials_requested']),
        'quantity_approved'  => intval($_POST['quantity_approved']),
    ];
    $slipId = $rsModel->create($data);
    if ($slipId) {
        // Notify MAO
        $maoUsers = (new User($conn))->getAllByRole('mao');
        foreach ($maoUsers as $mao) {
            $notifModel->create($mao['id'], 'New Request Slip for Review',
                'A new request slip has been prepared and is awaiting your review and approval.');
        }
        $success = "Request slip prepared and sent to MAO for review.";
    } else {
        $error = "Failed to create request slip.";
    }
}

$validated = $srModel->getByStatus('validated');
$allSlips = $rsModel->getAll();
?>

<?php if(isset($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if(isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Prepare New Slip -->
    <div class="col-md-5">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Prepare New Request Slip</h6>
            <?php if (empty($validated)): ?>
                <div class="empty-state">
                    <p class="small">No validated requests available. Complete site validation first.</p>
                </div>
            <?php else: ?>
            <form method="POST" id="slipForm">
                <div class="mb-3">
                    <label class="form-label">Select Validated Request <span class="text-danger">*</span></label>
                    <select name="request_id" id="requestSelect" class="form-select" required onchange="loadValidation(this.value)">
                        <option value="" disabled selected>Choose a request</option>
                        <?php foreach ($validated as $req): ?>
                            <?php $existingSlip = $rsModel->getByRequest($req['id']); ?>
                            <?php if (!$existingSlip): ?>
                            <option value="<?= $req['id'] ?>" 
                                data-seedling="<?= htmlspecialchars($req['seedling_type']) ?>"
                                data-qty="<?= $req['quantity_requested'] ?>">
                                <?= htmlspecialchars($req['request_number']) ?> - <?= htmlspecialchars($req['activity_name']) ?>
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="validation_id" id="validationId">
                <div id="slipDetails" class="d-none">
                    <div class="mb-3">
                        <label class="form-label">Materials Requested <span class="text-danger">*</span></label>
                        <textarea name="materials_requested" id="materialsField" class="form-control" rows="3" required
                            placeholder="List the materials being requested..."></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Quantity Approved <span class="text-danger">*</span></label>
                        <input type="number" name="quantity_approved" id="qtyField" class="form-control" min="1" required>
                    </div>
                    <button type="submit" name="create_slip" value="1" class="btn btn-ps-primary full">
                        Prepare & Submit Slip
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Existing Slips -->
    <div class="col-md-7">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">All Request Slips</h6>
            <?php if (empty($allSlips)): ?>
                <div class="empty-state"><p>No request slips prepared yet.</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="ps-table">
                        <thead>
                            <tr>
                                <th>Slip #</th>
                                <th>Request #</th>
                                <th>Activity</th>
                                <th>Qty</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allSlips as $slip): ?>
                            <tr>
                                <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($slip['slip_number']) ?></td>
                                <td class="small"><?= htmlspecialchars($slip['request_number']) ?></td>
                                <td class="small"><?= htmlspecialchars($slip['activity_name']) ?></td>
                                <td><?= number_format($slip['quantity_approved']) ?></td>
                                <td><span class="ps-badge ps-badge-<?= $slip['status'] ?>"><?= ucfirst($slip['status']) ?></span></td>
                                <td class="small text-muted"><?= date('M d, Y', strtotime($slip['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Fetch validation data when request is selected
function loadValidation(requestId) {
    if (!requestId) return;
    const sel = document.getElementById('requestSelect');
    const opt = sel.options[sel.selectedIndex];
    const seedling = opt.dataset.seedling;
    const qty = opt.dataset.qty;

    document.getElementById('materialsField').value = seedling;
    document.getElementById('qtyField').value = qty;
    document.getElementById('slipDetails').classList.remove('d-none');

    // Fetch validation ID via AJAX
    fetch('api/get_validation_id.php?request_id=' + requestId)
        .then(r => r.json())
        .then(data => {
            if (data.validation_id) {
                document.getElementById('validationId').value = data.validation_id;
            }
        }).catch(() => {});
}
</script>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
