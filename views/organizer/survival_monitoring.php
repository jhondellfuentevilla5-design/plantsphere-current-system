<?php
$pageTitle = 'Survival Monitoring';
include __DIR__ . '/../partials/layout_head.php';

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $planted  = intval($_POST['seedlings_planted']);
    $survived = intval($_POST['seedlings_survived']);

    if ($survived > $planted) {
        $error = "Survived count cannot exceed planted count.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO survival_analytics
                (request_id, monitored_by, monitoring_date, seedlings_planted, seedlings_survived, observations, service_rating, next_monitoring)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            intval($_POST['request_id']),
            $_SESSION['user']['id'],
            $_POST['monitoring_date'],
            $planted,
            $survived,
            trim($_POST['observations'] ?? ''),
            intval($_POST['service_rating']) ?: null,
            $_POST['next_monitoring'] ?: null,
        ]);
        $success = $result ? "Survival monitoring record saved." : "Failed to save record.";
    }
}

// Released requests belonging to this organizer only
$stmt = $conn->prepare("
    SELECT sr.id, sr.request_number, sr.activity_name, sr.target_location,
           u.firstname, u.lastname
    FROM service_requests sr
    JOIN users u ON sr.user_id = u.id
    WHERE sr.status IN ('released','routed')
      AND sr.user_id = ?
    ORDER BY sr.updated_at DESC
");
$stmt->execute([$_SESSION['user']['id']]);
$releasedRequests = $stmt->fetchAll();

// Monitoring records for this organizer's requests only
$stmt2 = $conn->prepare("
    SELECT sa.*, sr.request_number, sr.activity_name,
           ROUND((sa.seedlings_survived / sa.seedlings_planted) * 100, 1) AS survival_rate,
           u.firstname, u.lastname
    FROM survival_analytics sa
    JOIN service_requests sr ON sa.request_id = sr.id
    JOIN users u ON sa.monitored_by = u.id
    WHERE sr.user_id = ?
    ORDER BY sa.monitoring_date DESC
");
$stmt2->execute([$_SESSION['user']['id']]);
$records = $stmt2->fetchAll();
?>

<div class="ps-page-header">
    <h2>Seedling Survival Monitoring</h2>
    <p>Monitor and record the survival of seedlings from your approved planting activities.</p>
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
    <!-- Form -->
    <div class="col-md-5">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">
                <i class="bi bi-tree me-2"></i>Record Monitoring
            </h6>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Select Request <span class="text-danger">*</span></label>
                    <select name="request_id" class="form-select" required>
                        <option value="" disabled selected>Choose a request</option>
                        <?php foreach ($releasedRequests as $r): ?>
                        <option value="<?= $r['id'] ?>">
                            <?= htmlspecialchars($r['request_number']) ?> — <?= htmlspecialchars($r['activity_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Monitoring Date <span class="text-danger">*</span></label>
                    <input type="date" name="monitoring_date" class="form-control"
                           value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Seedlings Planted <span class="text-danger">*</span></label>
                        <input type="number" name="seedlings_planted" class="form-control"
                               min="1" id="plantedInput" oninput="calcRate()" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Seedlings Survived <span class="text-danger">*</span></label>
                        <input type="number" name="seedlings_survived" class="form-control"
                               min="0" id="survivedInput" oninput="calcRate()" required>
                    </div>
                </div>

                <!-- Live survival rate display -->
                <div class="mb-3 p-3 rounded" style="background:#f0faf0;border:1px solid #b7dfb7;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small fw-semibold text-ps-green">Survival Rate</span>
                        <span class="fw-bold text-ps-green" id="survivalRateDisplay">—</span>
                    </div>
                    <div class="progress mt-2" style="height:6px;">
                        <div class="progress-bar bg-success" id="survivalBar" style="width:0%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Service Selection Rating</label>
                    <div class="d-flex gap-2" id="starRating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label class="star-label" style="cursor:pointer;font-size:1.5rem;color:#dee2e6;"
                               onclick="setRating(<?= $i ?>)">★</label>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="service_rating" id="serviceRating">
                    <div class="form-text">Rate the overall service quality (1–5 stars)</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observations</label>
                    <textarea name="observations" class="form-control" rows="3"
                        placeholder="Describe conditions, issues, or notable observations..."></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">Next Monitoring Date</label>
                    <input type="date" name="next_monitoring" class="form-control"
                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                </div>

                <button type="submit" class="btn btn-ps-primary w-100">
                    <i class="bi bi-save me-1"></i>Save Monitoring Record
                </button>
            </form>
        </div>
    </div>

    <!-- Records -->
    <div class="col-md-7">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Survival Analytics Records</h6>
            <?php if (empty($records)): ?>
                <div class="empty-state">
                    <i class="bi bi-bar-chart" style="font-size:2.5rem;opacity:0.25;"></i>
                    <p class="mt-2">No monitoring records yet.</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="ps-table">
                    <thead><tr><th>Request #</th><th>Activity</th><th>Date</th><th>Planted</th><th>Survived</th><th>Rate</th><th>Rating</th></tr></thead>
                    <tbody>
                        <?php foreach ($records as $r): ?>
                        <tr>
                            <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($r['request_number']) ?></td>
                            <td class="small"><?= htmlspecialchars($r['activity_name']) ?></td>
                            <td class="small"><?= date('M d, Y', strtotime($r['monitoring_date'])) ?></td>
                            <td><?= number_format($r['seedlings_planted']) ?></td>
                            <td><?= number_format($r['seedlings_survived']) ?></td>
                            <td>
                                <span class="fw-bold <?= $r['survival_rate'] >= 75 ? 'text-success' : ($r['survival_rate'] >= 50 ? 'text-warning' : 'text-danger') ?>">
                                    <?= $r['survival_rate'] ?>%
                                </span>
                            </td>
                            <td>
                                <?php if ($r['service_rating']): ?>
                                    <span style="color:#f0a500;">
                                        <?= str_repeat('★', $r['service_rating']) ?><?= str_repeat('☆', 5 - $r['service_rating']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
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

<script>
function calcRate() {
    const planted  = parseInt(document.getElementById('plantedInput').value) || 0;
    const survived = parseInt(document.getElementById('survivedInput').value) || 0;
    const display  = document.getElementById('survivalRateDisplay');
    const bar      = document.getElementById('survivalBar');

    if (planted > 0) {
        const rate = Math.min(100, ((survived / planted) * 100)).toFixed(1);
        display.textContent = rate + '%';
        bar.style.width = rate + '%';
        bar.className = 'progress-bar ' + (rate >= 75 ? 'bg-success' : rate >= 50 ? 'bg-warning' : 'bg-danger');
    } else {
        display.textContent = '—';
        bar.style.width = '0%';
    }
}

function setRating(val) {
    document.getElementById('serviceRating').value = val;
    const stars = document.querySelectorAll('.star-label');
    stars.forEach((s, i) => {
        s.style.color = i < val ? '#f0a500' : '#dee2e6';
    });
}
</script>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
