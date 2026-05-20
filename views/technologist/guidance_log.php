<?php
$pageTitle = 'Guidance Log';
include __DIR__ . '/../partials/layout_head.php';

$notifModel = new Notification($conn);
$srModel    = new ServiceRequest($conn);

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['release_id'])) {
    $stmt = $conn->prepare("
        INSERT INTO guidance_logs (request_id, release_id, logged_by, delivery_date, attendance_count, completion_status, guidance_notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $result = $stmt->execute([
        intval($_POST['request_id']),
        intval($_POST['release_id']),
        $_SESSION['user']['id'],
        $_POST['delivery_date'],
        intval($_POST['attendance_count']),
        $_POST['completion_status'],
        trim($_POST['guidance_notes'] ?? ''),
    ]);
    if ($result) {
        // Update request status
        $srModel->updateStatus(intval($_POST['request_id']), 'routed', 'Instructional delivery verified.');
        $req = $srModel->getById(intval($_POST['request_id']));
        $notifModel->create($req['user_id'], 'Delivery Verified',
            'Instructional delivery for your request ' . $req['request_number'] . ' has been verified.');
        $success = "Guidance log recorded successfully.";
    } else {
        $error = "Failed to save guidance log.";
    }
}

// Get released requests that need guidance log
$stmt = $conn->prepare("
    SELECT sr2.*, sr.request_number, sr.activity_name, sr.target_location, sr.target_date,
           u.firstname, u.lastname
    FROM seed_releases sr2
    JOIN service_requests sr ON sr2.request_id = sr.id
    JOIN users u ON sr.user_id = u.id
    WHERE sr.status = 'released'
    AND sr2.request_id NOT IN (SELECT DISTINCT request_id FROM guidance_logs)
    ORDER BY sr2.release_date DESC
");
$stmt->execute();
$pendingGuidance = $stmt->fetchAll();

// All guidance logs
$stmt2 = $conn->prepare("
    SELECT gl.*, sr.request_number, sr.activity_name,
           u.firstname, u.lastname
    FROM guidance_logs gl
    JOIN service_requests sr ON gl.request_id = sr.id
    JOIN users u ON gl.logged_by = u.id
    ORDER BY gl.created_at DESC
");
$stmt2->execute();
$allLogs = $stmt2->fetchAll();
?>

<div class="ps-page-header">
    <h2>Guidance Log — Instructional Delivery</h2>
    <p>Verify and record instructional delivery after seed pack release. (Process 15 & 16)</p>
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
    <!-- Log form -->
    <div class="col-md-5">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">
                <i class="bi bi-journal-check me-2"></i>Record Delivery Verification
            </h6>
            <?php if (empty($pendingGuidance)): ?>
                <div class="empty-state"><p class="small">No released requests pending guidance log.</p></div>
            <?php else: ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Select Released Request <span class="text-danger">*</span></label>
                    <select name="release_id" id="releaseSelect" class="form-select" required
                            onchange="fillRequestId(this)">
                        <option value="" disabled selected>Choose a release</option>
                        <?php foreach ($pendingGuidance as $r): ?>
                        <option value="<?= $r['id'] ?>" data-request="<?= $r['request_id'] ?>">
                            <?= htmlspecialchars($r['request_number']) ?> — <?= htmlspecialchars($r['activity_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="request_id" id="requestIdField">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Delivery Date <span class="text-danger">*</span></label>
                        <input type="date" name="delivery_date" class="form-control"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Attendance Count <span class="text-danger">*</span></label>
                        <input type="number" name="attendance_count" class="form-control" min="0" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Completion Status <span class="text-danger">*</span></label>
                    <select name="completion_status" class="form-select" required>
                        <option value="completed">Completed</option>
                        <option value="partial">Partial</option>
                        <option value="not_delivered">Not Delivered</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label">Guidance Notes</label>
                    <textarea name="guidance_notes" class="form-control" rows="3"
                        placeholder="Notes about the instructional delivery..."></textarea>
                </div>

                <button type="submit" class="btn btn-ps-primary w-100">
                    <i class="bi bi-journal-plus me-1"></i>Save Guidance Log
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Log history -->
    <div class="col-md-7">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Guidance Log History</h6>
            <?php if (empty($allLogs)): ?>
                <div class="empty-state"><p>No guidance logs yet.</p></div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="ps-table">
                    <thead><tr><th>Request #</th><th>Activity</th><th>Delivery Date</th><th>Attendance</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($allLogs as $log): ?>
                        <tr>
                            <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($log['request_number']) ?></td>
                            <td class="small"><?= htmlspecialchars($log['activity_name']) ?></td>
                            <td class="small"><?= date('M d, Y', strtotime($log['delivery_date'])) ?></td>
                            <td><?= number_format($log['attendance_count']) ?></td>
                            <td>
                                <span class="ps-badge <?= $log['completion_status'] === 'completed' ? 'ps-badge-approved' : ($log['completion_status'] === 'partial' ? 'ps-badge-pending' : 'ps-badge-rejected') ?>">
                                    <?= ucfirst(str_replace('_',' ',$log['completion_status'])) ?>
                                </span>
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

<script>
function fillRequestId(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('requestIdField').value = opt.dataset.request || '';
}
</script>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
