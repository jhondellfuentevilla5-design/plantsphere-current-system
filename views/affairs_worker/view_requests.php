<?php
$pageTitle = 'View Requests';
include __DIR__ . '/../partials/layout_head.php';
?>

<div class="ps-page-header">
    <h2>All Service Requests</h2>
    <p>Review and manage incoming tree planting requests approved by the Barangay Captain.</p>
</div>

<?php
$srModel = new ServiceRequest($conn);
$requests = $srModel->getAll();

// Filter
$filterStatus = $_GET['status'] ?? 'formal_request_submitted';
if ($filterStatus !== 'all') {
    $requests = array_filter($requests, fn($r) => $r['status'] === $filterStatus);
}
?>

<div class="ps-card">
    <!-- Filter Tabs -->
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <?php
        $statuses = ['formal_request_submitted','barangay_approved','under_review','for_validation','validated','approved','rejected','all'];
        $labels   = [
            'formal_request_submitted' => 'For Review',
            'barangay_approved'        => 'Barangay Approved',
            'under_review'             => 'Under Review',
            'for_validation'           => 'For Validation',
            'validated'                => 'Validated',
            'approved'                 => 'Approved',
            'rejected'                 => 'Rejected',
            'all'                      => 'All',
        ];
        foreach ($statuses as $s):
            $active = $filterStatus === $s ? 'btn-ps-primary' : 'btn-outline-secondary';
        ?>
            <a href="index.php?action=view_requests&status=<?= $s ?>" class="btn btn-sm <?= $active ?>">
                <?= $labels[$s] ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($requests)): ?>
        <div class="empty-state">
            <p>No requests found for this filter.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="ps-table">
                <thead>
                    <tr>
                        <th>Request #</th>
                        <th>Organizer</th>
                        <th>Activity</th>
                        <th>Location</th>
                        <th>Seedling</th>
                        <th>Qty</th>
                        <th>Target Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                    <tr>
                        <td class="small fw-semibold text-ps-green"><?= htmlspecialchars($req['request_number']) ?></td>
                        <td class="small"><?= htmlspecialchars($req['firstname'] . ' ' . $req['lastname']) ?></td>
                        <td><?= htmlspecialchars($req['activity_name']) ?></td>
                        <td class="small"><?= htmlspecialchars($req['target_location']) ?></td>
                        <td class="small"><?= htmlspecialchars($req['seedling_type']) ?></td>
                        <td><?= number_format($req['quantity_requested']) ?></td>
                        <td class="small"><?= date('M d, Y', strtotime($req['target_date'])) ?></td>
                        <td><span class="ps-badge ps-badge-<?= $req['status'] ?>"><?= str_replace('_', ' ', $req['status']) ?></span></td>
                        <td>
                            <?php if ($req['status'] === 'formal_request_submitted'): ?>
                                <a href="index.php?action=refer_request&id=<?= $req['id'] ?>" class="btn btn-sm btn-ps-primary">Review</a>
                            <?php else: ?>
                                <a href="index.php?action=refer_request&id=<?= $req['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
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
