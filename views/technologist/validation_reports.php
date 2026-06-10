<?php
$pageTitle = 'Validation Reports';
include __DIR__ . '/../partials/layout_head.php';

$vrModel = new ValidationReport($conn);
$reports = $vrModel->getAll();
?>

<div class="ps-page-header">
    <h2>Validation Reports</h2>
    <p>All submitted site validation reports with uploaded site photos.</p>
</div>

<div class="ps-card">
    <?php if (empty($reports)): ?>
        <div class="empty-state">
            <i class="bi bi-geo-alt" style="font-size:2.5rem;opacity:0.25;"></i>
            <p class="mt-2">No validation reports submitted yet.</p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="ps-table">
            <thead>
                <tr>
                    <th>Request #</th>
                    <th>Activity</th>
                    <th>Site Location</th>
                    <th>Validated By</th>
                    <th>Date</th>
                    <th>Soil</th>
                    <th>Seed Packs</th>
                    <th>Photos</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $r):
                    $photos = [];
                    if (!empty($r['site_photos'])) {
                        $decoded = json_decode($r['site_photos'], true);
                        if (is_array($decoded)) $photos = $decoded;
                    }
                ?>
                <tr>
                    <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($r['request_number']) ?></td>
                    <td class="small"><?= htmlspecialchars($r['activity_name']) ?></td>
                    <td class="small"><?= htmlspecialchars($r['site_location']) ?></td>
                    <td class="small"><?= htmlspecialchars($r['firstname'] . ' ' . $r['lastname']) ?></td>
                    <td class="small"><?= date('M d, Y', strtotime($r['validation_date'])) ?></td>
                    <td class="small"><?= htmlspecialchars(explode(' - ', $r['soil_condition'])[0]) ?></td>
                    <td class="fw-bold text-ps-green"><?= number_format($r['seed_packs_counted']) ?></td>
                    <td>
                        <?php if (!empty($photos)): ?>
                            <span class="d-flex align-items-center gap-1">
                                <!-- Show first photo as tiny thumbnail -->
                                <img src="<?= htmlspecialchars($photos[0]) ?>"
                                     style="width:32px;height:32px;object-fit:cover;border-radius:5px;border:1px solid #d8e8d5;"
                                     alt="Site photo">
                                <span class="ps-badge ps-badge-approved">
                                    <?= count($photos) ?> photo<?= count($photos) > 1 ? 's' : '' ?>
                                </span>
                            </span>
                        <?php else: ?>
                            <span class="text-muted small">No photos</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="index.php?action=view_validation&request_id=<?= $r['request_id'] ?>"
                           class="btn btn-sm btn-ps-primary">
                            <i class="bi bi-eye me-1"></i>View Report
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
