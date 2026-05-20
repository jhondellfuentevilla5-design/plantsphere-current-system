<?php
$pageTitle = 'Release Seed Packs';
include __DIR__ . '/../partials/layout_head.php';

$releaseModel = new SeedRelease($conn);
$pmModel      = new PlantingMaterial($conn);
$notifModel   = new Notification($conn);
$srModel      = new ServiceRequest($conn);

// Handle release
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['slip_id'])) {
    $slipId          = intval($_POST['slip_id']);
    $requestId       = intval($_POST['request_id']);
    $qtyReleased     = intval($_POST['quantity_released']);
    $recipientName   = trim($_POST['recipient_name']);
    $releaseDate     = $_POST['release_date'];
    $remarks         = trim($_POST['remarks'] ?? '');

    // Get slip details
    $stmt = $conn->prepare("SELECT rs.*, sr.user_id, sr.request_number, sr.seedling_type FROM request_slips rs JOIN service_requests sr ON rs.request_id = sr.id WHERE rs.id = ?");
    $stmt->execute([$slipId]);
    $slip = $stmt->fetch();

    if ($slip && $qtyReleased > 0) {
        $releaseId = $releaseModel->create([
            'request_id'       => $requestId,
            'slip_id'          => $slipId,
            'released_by'      => $_SESSION['user']['id'],
            'quantity_released'=> $qtyReleased,
            'release_date'     => $releaseDate,
            'recipient_name'   => $recipientName,
            'remarks'          => $remarks,
        ]);

        if ($releaseId) {
            // Deduct from inventory
            $materials = $pmModel->getAll();
            foreach ($materials as $m) {
                if (stripos($m['material_name'], $slip['seedling_type']) !== false ||
                    stripos($slip['seedling_type'], $m['material_name']) !== false) {
                    $pmModel->deductStock($m['id'], $qtyReleased);
                    break;
                }
            }

            // Update service request status
            $srModel->updateStatus($requestId, 'released', 'Seed packs released by Nursery.');
            $conn->prepare("UPDATE service_requests SET quantity_released = ?, released_at = NOW(), released_by = ? WHERE id = ?")->execute([$qtyReleased, $_SESSION['user']['id'], $requestId]);

            // Notify organizer
            $notifModel->create($slip['user_id'], 'Seed Packs Released',
                'Your request ' . $slip['request_number'] . ' — ' . $qtyReleased . ' seed packs have been released to ' . $recipientName . '.');

            $success = "$qtyReleased seed packs released successfully to $recipientName.";
        } else {
            $error = "Failed to record release.";
        }
    } else {
        $error = "Invalid quantity or slip.";
    }
}

$focusId       = intval($_GET['id'] ?? 0);
$pendingSlips  = $releaseModel->getPendingReleases();
$focusSlip     = $focusId ? array_values(array_filter($pendingSlips, fn($s) => $s['id'] == $focusId))[0] ?? null : null;
?>

<div class="ps-page-header">
    <h2>Seed Pack Release</h2>
    <p>Process and record seed pack releases for finalized requests.</p>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- List -->
    <div class="col-md-4">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Ready for Release</h6>
            <?php if (empty($pendingSlips)): ?>
                <div class="empty-state"><p class="small">No pending releases.</p></div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($pendingSlips as $s): ?>
                    <a href="index.php?action=nursery_release&id=<?= $s['id'] ?>" class="text-decoration-none">
                        <div class="p-3 rounded border <?= $focusId == $s['id'] ? 'border-success bg-ps-pale' : '' ?>">
                            <div class="small fw-semibold text-ps-green"><?= htmlspecialchars($s['slip_number']) ?></div>
                            <div class="small"><?= htmlspecialchars($s['activity_name']) ?></div>
                            <div class="small text-muted"><?= number_format($s['quantity_approved']) ?> packs</div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Release Form -->
    <div class="col-md-8">
        <?php if ($focusSlip): ?>
        <div class="ps-card mb-3">
            <h6 class="fw-bold text-ps-green mb-3">Request Details</h6>
            <div class="row g-3 small">
                <div class="col-6"><span class="text-muted">Slip #:</span> <strong><?= htmlspecialchars($focusSlip['slip_number']) ?></strong></div>
                <div class="col-6"><span class="text-muted">Request #:</span> <?= htmlspecialchars($focusSlip['request_number']) ?></div>
                <div class="col-6"><span class="text-muted">Activity:</span> <?= htmlspecialchars($focusSlip['activity_name']) ?></div>
                <div class="col-6"><span class="text-muted">Location:</span> <?= htmlspecialchars($focusSlip['target_location']) ?></div>
                <div class="col-6"><span class="text-muted">Seedling Type:</span> <?= htmlspecialchars($focusSlip['seedling_type']) ?></div>
                <div class="col-6"><span class="text-muted">Qty Approved:</span> <strong class="text-ps-green"><?= number_format($focusSlip['quantity_approved']) ?></strong></div>
            </div>
        </div>

        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">
                <i class="bi bi-box-seam me-2"></i>Record Release
            </h6>
            <form method="POST">
                <input type="hidden" name="slip_id" value="<?= $focusSlip['id'] ?>">
                <input type="hidden" name="request_id" value="<?= $focusSlip['request_id'] ?>">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Quantity to Release <span class="text-danger">*</span></label>
                        <input type="number" name="quantity_released" class="form-control"
                               min="1" max="<?= $focusSlip['quantity_approved'] ?>"
                               value="<?= $focusSlip['quantity_approved'] ?>" required>
                        <div class="form-text">Max: <?= number_format($focusSlip['quantity_approved']) ?> packs</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Release Date <span class="text-danger">*</span></label>
                        <input type="date" name="release_date" class="form-control"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Recipient Name <span class="text-danger">*</span></label>
                    <input type="text" name="recipient_name" class="form-control"
                           placeholder="Name of person receiving the seed packs" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2"
                        placeholder="Additional notes about the release..."></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-ps-primary">
                        <i class="bi bi-box-arrow-up me-1"></i>Confirm Release
                    </button>
                    <a href="index.php?action=nursery_release" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="ps-card">
            <div class="empty-state">
                <i class="bi bi-arrow-left-circle" style="font-size:2.5rem;opacity:0.25;"></i>
                <p class="mt-2">Select a request from the list to process the release.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
