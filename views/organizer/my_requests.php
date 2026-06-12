<?php
$pageTitle = 'My Requests';
include __DIR__ . '/../partials/layout_head.php';
?>

<div class="ps-page-header d-flex justify-content-between align-items-start">
    <div>
        <h2>My Proposals</h2>
        <p>Track the status of all your submitted proposal letters.</p>
    </div>
    <a href="index.php?action=submit_request" class="btn btn-ps-primary">+ New Proposal</a>
</div>

<?php
$srModel = new ServiceRequest($conn);
$requests = $srModel->getByUser($_SESSION['user']['id']);
?>

<div class="ps-card">
    <?php if (empty($requests)): ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
            </svg>
            <p>You haven't submitted any requests yet.</p>
            <a href="index.php?action=submit_request" class="btn btn-ps-primary btn-sm">Submit Your First Request</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="ps-table">
                <thead>
                    <tr>
                        <th>Request #</th>
                        <th>Activity Name</th>
                        <th>Location</th>
                        <th>Target Date</th>
                        <th>Seedling Type</th>
                        <th>Qty</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Remarks</th>
                        <th>Letter</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                    <tr>
                        <td class="fw-semibold small text-ps-green"><?= htmlspecialchars($req['request_number']) ?></td>
                        <td><?= htmlspecialchars($req['activity_name']) ?></td>
                        <td class="small"><?= htmlspecialchars($req['target_location']) ?></td>
                        <td class="small"><?= date('M d, Y', strtotime($req['target_date'])) ?></td>
                        <td class="small"><?= htmlspecialchars($req['seedling_type']) ?></td>
                        <td><?= number_format($req['quantity_requested']) ?></td>
                        <td><span class="ps-badge ps-badge-<?= $req['status'] ?>"><?= str_replace('_', ' ', $req['status']) ?></span></td>
                        <td class="small text-muted"><?= date('M d, Y', strtotime($req['created_at'])) ?></td>
                        <td class="small text-muted"><?= $req['remarks'] ? htmlspecialchars($req['remarks']) : '—' ?></td>
                        <td>
                            <?php if (!empty($req['request_letter'])): ?>
                                <a href="<?= htmlspecialchars($req['request_letter']) ?>" target="_blank"
                                   class="btn btn-sm btn-outline-secondary" title="View uploaded letter">
                                    <i class="bi bi-file-earmark-arrow-down me-1"></i>
                                    <?= strtoupper(pathinfo($req['request_letter'], PATHINFO_EXTENSION)) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($req['status'] === 'barangay_approved'): ?>
                                <a href="index.php?action=formal_request&id=<?= $req['id'] ?>"
                                   class="btn btn-sm btn-ps-primary">
                                    <i class="bi bi-file-earmark-plus me-1"></i>Generate Formal Request
                                </a>
                            <?php elseif ($req['status'] === 'pending'): ?>
                                <span class="small text-muted">Awaiting Barangay Validation</span>
                            <?php elseif ($req['status'] === 'formal_request_submitted'): ?>
                                <span class="small text-success"><i class="bi bi-check-circle me-1"></i>Formal Request Submitted</span>
                            <?php elseif ($req['status'] === 'released'): ?>
                                <a href="index.php?action=view_release_photos&request_id=<?= $req['id'] ?>"
                                   class="btn btn-sm btn-ps-primary">
                                    <i class="bi bi-images me-1"></i>View Seedlings
                                </a>
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

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
