<?php
$pageTitle = 'Stakeholder Importance';
include __DIR__ . '/../partials/layout_head.php';

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guidance_id'])) {
    $guidanceId = intval($_POST['guidance_id']);
    $requestId  = intval($_POST['request_id']);
    $stakeholders = $_POST['stakeholder_name'] ?? [];
    $scores       = $_POST['importance_score'] ?? [];
    $roles        = $_POST['role_description'] ?? [];
    $tasks        = $_POST['assigned_task'] ?? [];
    $date         = $_POST['assessment_date'];

    $inserted = 0;
    foreach ($stakeholders as $i => $name) {
        if (empty(trim($name))) continue;
        $stmt = $conn->prepare("INSERT INTO task_assignment_logs (guidance_id, request_id, assessed_by, stakeholder_name, importance_score, role_description, assigned_task, assessment_date) VALUES (?,?,?,?,?,?,?,?)");
        if ($stmt->execute([$guidanceId, $requestId, $_SESSION['user']['id'], trim($name), intval($scores[$i] ?? 5), trim($roles[$i] ?? ''), trim($tasks[$i] ?? ''), $date])) {
            $inserted++;
        }
    }
    if ($inserted > 0) {
        $success = "$inserted stakeholder(s) recorded in Task Assignment Log.";
    } else {
        $error = "No valid stakeholders entered.";
    }
}

// Get completed guidance logs not yet assessed
$stmt = $conn->prepare("
    SELECT gl.*, sr.request_number, sr.activity_name, sr.target_location
    FROM guidance_logs gl
    JOIN service_requests sr ON gl.request_id = sr.id
    WHERE gl.completion_status = 'completed'
    AND gl.id NOT IN (SELECT DISTINCT guidance_id FROM task_assignment_logs)
    ORDER BY gl.created_at DESC
");
$stmt->execute();
$pendingGuidance = $stmt->fetchAll();

// All task assignment logs
$stmt2 = $conn->prepare("
    SELECT tal.*, sr.request_number, sr.activity_name, u.firstname, u.lastname
    FROM task_assignment_logs tal
    JOIN service_requests sr ON tal.request_id = sr.id
    JOIN users u ON tal.assessed_by = u.id
    ORDER BY tal.assessment_date DESC
");
$stmt2->execute();
$allLogs = $stmt2->fetchAll();
?>

<div class="ps-page-header">
    <h2>Stakeholder Importance — Task Assignment Log</h2>
    <p>Process 15 — Calculate stakeholder importance and assign tasks after instructional delivery.</p>
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
    <div class="col-md-5">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">
                <i class="bi bi-people me-2"></i>Record Stakeholder Importance
            </h6>
            <?php if (empty($pendingGuidance)): ?>
                <div class="empty-state"><p class="small">No completed guidance logs pending assessment.</p></div>
            <?php else: ?>
            <form method="POST" id="stakeholderForm">
                <div class="mb-3">
                    <label class="form-label">Select Guidance Log <span class="text-danger">*</span></label>
                    <select name="guidance_id" class="form-select" required onchange="fillRequestId(this)">
                        <option value="" disabled selected>Choose a guidance log</option>
                        <?php foreach ($pendingGuidance as $g): ?>
                        <option value="<?= $g['id'] ?>" data-request="<?= $g['request_id'] ?>">
                            <?= htmlspecialchars($g['request_number']) ?> — <?= htmlspecialchars($g['activity_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="request_id" id="requestIdField">

                <div class="mb-3">
                    <label class="form-label">Assessment Date <span class="text-danger">*</span></label>
                    <input type="date" name="assessment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div id="stakeholderRows">
                    <label class="form-label fw-semibold">Stakeholders</label>
                    <div class="stakeholder-row mb-2 p-3 border rounded" id="row-0">
                        <div class="row g-2 mb-2">
                            <div class="col-8">
                                <input type="text" name="stakeholder_name[]" class="form-control form-control-sm" placeholder="Stakeholder Name *" required>
                            </div>
                            <div class="col-4">
                                <input type="number" name="importance_score[]" class="form-control form-control-sm" placeholder="Score (1-10)" min="1" max="10" value="5" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <input type="text" name="role_description[]" class="form-control form-control-sm" placeholder="Role / Position">
                        </div>
                        <div>
                            <textarea name="assigned_task[]" class="form-control form-control-sm" rows="2" placeholder="Assigned Task"></textarea>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="addRow()">
                    <i class="bi bi-plus-circle me-1"></i>Add Stakeholder
                </button>
                <br>
                <button type="submit" class="btn btn-ps-primary">
                    <i class="bi bi-save me-1"></i>Save Task Assignment Log
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-7">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Task Assignment Log History</h6>
            <?php if (empty($allLogs)): ?>
                <div class="empty-state"><p>No task assignment logs yet.</p></div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="ps-table">
                    <thead>
                        <tr>
                            <th>Request #</th>
                            <th>Activity</th>
                            <th>Stakeholder</th>
                            <th>Score</th>
                            <th>Assigned Task</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allLogs as $log): ?>
                        <tr>
                            <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($log['request_number']) ?></td>
                            <td class="small"><?= htmlspecialchars($log['activity_name']) ?></td>
                            <td class="small"><?= htmlspecialchars($log['stakeholder_name']) ?></td>
                            <td>
                                <span class="fw-bold <?= $log['importance_score'] >= 7 ? 'text-danger' : ($log['importance_score'] >= 4 ? 'text-warning' : 'text-muted') ?>">
                                    <?= $log['importance_score'] ?>/10
                                </span>
                            </td>
                            <td class="small"><?= htmlspecialchars(substr($log['assigned_task'] ?? '—', 0, 50)) ?></td>
                            <td class="small"><?= date('M d, Y', strtotime($log['assessment_date'])) ?></td>
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
let rowCount = 1;
function addRow() {
    const container = document.getElementById('stakeholderRows');
    const div = document.createElement('div');
    div.className = 'stakeholder-row mb-2 p-3 border rounded';
    div.id = 'row-' + rowCount;
    div.innerHTML = `
        <div class="d-flex justify-content-end mb-1">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.stakeholder-row').remove()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-8">
                <input type="text" name="stakeholder_name[]" class="form-control form-control-sm" placeholder="Stakeholder Name *" required>
            </div>
            <div class="col-4">
                <input type="number" name="importance_score[]" class="form-control form-control-sm" placeholder="Score (1-10)" min="1" max="10" value="5" required>
            </div>
        </div>
        <div class="mb-2">
            <input type="text" name="role_description[]" class="form-control form-control-sm" placeholder="Role / Position">
        </div>
        <div>
            <textarea name="assigned_task[]" class="form-control form-control-sm" rows="2" placeholder="Assigned Task"></textarea>
        </div>`;
    container.appendChild(div);
    rowCount++;
}

function fillRequestId(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('requestIdField').value = opt.dataset.request || '';
}
</script>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
