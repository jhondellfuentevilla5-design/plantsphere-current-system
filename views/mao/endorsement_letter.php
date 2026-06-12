<?php
$pageTitle = 'Endorsement Letter';
include __DIR__ . '/../partials/layout_head.php';

$rsModel = new RequestSlip($conn);
$srModel = new ServiceRequest($conn);

$slipId = intval($_GET['slip_id'] ?? 0);
$slip   = $rsModel->getById($slipId);

if (!$slip || $slip['status'] !== 'approved') {
    echo '<div class="alert alert-warning">Endorsement letter not available. Slip must be approved first.</div>';
    include __DIR__ . '/../partials/layout_foot.php';
    exit;
}

$request = $srModel->getById($slip['request_id']);

// MAO user info
$maoUser = $_SESSION['user'];
?>

<style>
@media print {
    .ps-sidebar, .ps-topbar, .no-print { display: none !important; }
    .ps-main-content { margin: 0 !important; padding: 0 !important; }
    .endorsement-wrap { box-shadow: none !important; border: none !important; }
}

.endorsement-wrap {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
    border: 1px solid #d8e8d5;
    border-radius: 12px;
    padding: 48px 56px;
    font-family: 'Times New Roman', Times, serif;
    font-size: 0.95rem;
    line-height: 1.8;
    color: #1a1a1a;
}
.endorsement-header {
    text-align: center;
    margin-bottom: 32px;
    border-bottom: 2px solid #1a3d16;
    padding-bottom: 18px;
}
.endorsement-header .republic {
    font-size: 0.8rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #555;
    margin-bottom: 4px;
}
.endorsement-header .office {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1a3d16;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.endorsement-header .sub-office {
    font-size: 0.85rem;
    color: #444;
}
.endorsement-ref {
    display: flex;
    justify-content: space-between;
    margin-bottom: 28px;
    font-size: 0.88rem;
}
.endorsement-body p { margin-bottom: 14px; text-align: justify; }
.endorsement-body .indent { text-indent: 40px; }
.endorsement-highlight {
    font-weight: 700;
    text-decoration: underline;
}
.endorsement-table {
    width: 100%;
    border-collapse: collapse;
    margin: 18px 0;
    font-size: 0.88rem;
}
.endorsement-table th {
    background: #f0faf0;
    border: 1px solid #b7dfb7;
    padding: 8px 12px;
    text-align: left;
    font-weight: 700;
}
.endorsement-table td {
    border: 1px solid #d8e8d5;
    padding: 8px 12px;
}
.endorsement-sig {
    margin-top: 48px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}
.sig-block { text-align: center; }
.sig-name {
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.9rem;
    border-top: 1.5px solid #1a1a1a;
    padding-top: 6px;
    margin-top: 48px;
    display: inline-block;
    min-width: 220px;
}
.sig-title { font-size: 0.82rem; color: #444; }
</style>

<!-- Action buttons -->
<div class="d-flex gap-2 mb-4 no-print">
    <button onclick="window.print()" class="btn btn-ps-primary">
        <i class="bi bi-printer me-2"></i>Print Endorsement Letter
    </button>
    <a href="index.php?action=approve_slip&id=<?= $slipId ?>" class="btn btn-outline-secondary">
        ← Back to Slip
    </a>
</div>

<!-- Endorsement Letter -->
<div class="endorsement-wrap">

    <!-- Header -->
    <div class="endorsement-header">
        <div class="republic">Republic of the Philippines</div>
        <div class="republic">City of Davao</div>
        <div class="office">City Agriculturist's Office</div>
        <div class="sub-office">Office of the Municipal Agricultural Officer</div>
        <div class="sub-office">Toril District</div>
    </div>

    <!-- Reference & Date -->
    <div class="endorsement-ref">
        <div>
            <strong>Ref. No.:</strong>
            <?= htmlspecialchars($slip['endorsement_ref_number'] ?? 'N/A') ?>
        </div>
        <div>
            <strong>Date:</strong>
            <?= date('F d, Y', strtotime($slip['filing_date'] ?? $slip['approved_at'])) ?>
        </div>
    </div>

    <!-- Addressee -->
    <div class="endorsement-body">
        <p>
            <strong><?= htmlspecialchars($slip['endorsement_office'] ?? 'Concerned Office') ?></strong><br>
            Toril District, Davao City
        </p>

        <p class="indent">
            <strong>Subject: Endorsement of Tree Planting Activity Request</strong>
        </p>

        <p class="indent">
            This is to formally endorse the tree planting activity request submitted by
            <span class="endorsement-highlight"><?= htmlspecialchars($request['firstname'] . ' ' . $request['lastname']) ?></span>
            for the activity titled
            <span class="endorsement-highlight">"<?= htmlspecialchars($request['activity_name']) ?>"</span>,
            which has been reviewed and approved by this office.
        </p>

        <p class="indent">
            The details of the endorsed request are as follows:
        </p>

        <!-- Details Table -->
        <table class="endorsement-table">
            <tr>
                <th style="width:40%">Particulars</th>
                <th>Details</th>
            </tr>
            <tr>
                <td>Request Number</td>
                <td><?= htmlspecialchars($request['request_number']) ?></td>
            </tr>
            <tr>
                <td>Proponent / Organizer</td>
                <td><?= htmlspecialchars($request['firstname'] . ' ' . $request['lastname']) ?></td>
            </tr>
            <tr>
                <td>Activity Title</td>
                <td><?= htmlspecialchars($request['activity_name']) ?></td>
            </tr>
            <tr>
                <td>Planting Site / Location</td>
                <td><?= htmlspecialchars($slip['endorsed_planting_site'] ?? $request['target_location']) ?></td>
            </tr>
            <tr>
                <td>Scheduled Date</td>
                <td><?= date('F d, Y', strtotime($request['target_date'])) ?></td>
            </tr>
            <tr>
                <td>Seedling / Material Type</td>
                <td><?= htmlspecialchars($request['seedling_type']) ?></td>
            </tr>
            <tr>
                <td>Endorsed Quantity</td>
                <td><strong><?= number_format($slip['endorsed_quantity'] ?? $slip['quantity_approved']) ?> seedlings/packs</strong></td>
            </tr>
            <tr>
                <td>Number of Participants</td>
                <td><?= number_format($request['number_of_participants']) ?></td>
            </tr>
            <tr>
                <td>Request Slip Number</td>
                <td><?= htmlspecialchars($slip['slip_number']) ?></td>
            </tr>
        </table>

        <p class="indent">
            This endorsement is issued to facilitate the timely release of the requested seedling materials
            and to ensure the continuity of the tree planting program in the Toril District.
            All necessary validations including RSBSA verification and site validation have been completed.
        </p>

        <p class="indent">
            Your favorable action on this endorsement is earnestly requested.
        </p>

        <?php if (!empty($slip['mao_remarks'])): ?>
        <p class="indent">
            <em>Additional Remarks: <?= htmlspecialchars($slip['mao_remarks']) ?></em>
        </p>
        <?php endif; ?>

        <p style="margin-top: 24px;">Thank you.</p>

        <!-- Signature -->
        <div class="endorsement-sig">
            <div class="sig-block">
                <div class="sig-name"><?= htmlspecialchars($maoUser['firstname'] . ' ' . $maoUser['lastname']) ?></div>
                <div class="sig-title">Municipal Agriculture Officer</div>
                <div class="sig-title">City Agriculturist's Office, Toril District</div>
            </div>
            <div class="sig-block">
                <div class="sig-name">&nbsp;</div>
                <div class="sig-title">Received by / Authorized Representative</div>
                <div class="sig-title">Date: ________________</div>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
