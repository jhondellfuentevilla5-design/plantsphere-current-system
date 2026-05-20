<?php
$pageTitle = 'Seedling Materials';
include __DIR__ . '/../partials/layout_head.php';
?>

<div class="ps-page-header">
    <h2>Available Seedling Materials</h2>
    <p>Current inventory of planting materials available for distribution.</p>
</div>

<?php
$pmModel = new PlantingMaterial($conn);
$materials = $pmModel->getAll();
?>

<div class="row g-3 mb-4">
    <?php foreach ($materials as $m): 
        $stockClass = $m['quantity'] > 200 ? 'text-success' : ($m['quantity'] > 50 ? 'text-warning' : 'text-danger');
        $stockLabel = $m['quantity'] > 200 ? 'In Stock' : ($m['quantity'] > 50 ? 'Low Stock' : 'Critical');
    ?>
    <div class="col-md-4 col-lg-3">
        <div class="ps-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="fw-semibold"><?= htmlspecialchars($m['material_name']) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($m['material_type']) ?></div>
                </div>
                <span class="small fw-bold <?= $stockClass ?>"><?= $stockLabel ?></span>
            </div>
            <div class="mt-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-muted">Available</span>
                    <span class="fw-bold text-ps-green"><?= number_format($m['quantity']) ?> <?= htmlspecialchars($m['unit']) ?></span>
                </div>
                <div class="progress mt-2" style="height:6px;">
                    <?php $pct = min(100, ($m['quantity'] / 1000) * 100); ?>
                    <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                </div>
            </div>
            <?php if ($m['description']): ?>
                <p class="small text-muted mt-2 mb-0"><?= htmlspecialchars($m['description']) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Summary Table -->
<div class="ps-card">
    <h6 class="fw-bold text-ps-green mb-3">Inventory Summary</h6>
    <div class="table-responsive">
        <table class="ps-table">
            <thead>
                <tr>
                    <th>Material Name</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Stock Status</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materials as $m): 
                    $badge = $m['quantity'] > 200 ? 'ps-badge-approved' : ($m['quantity'] > 50 ? 'ps-badge-pending' : 'ps-badge-rejected');
                    $label = $m['quantity'] > 200 ? 'In Stock' : ($m['quantity'] > 50 ? 'Low Stock' : 'Critical');
                ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($m['material_name']) ?></td>
                    <td><?= htmlspecialchars($m['material_type']) ?></td>
                    <td class="fw-bold"><?= number_format($m['quantity']) ?></td>
                    <td><?= htmlspecialchars($m['unit']) ?></td>
                    <td><span class="ps-badge <?= $badge ?>"><?= $label ?></span></td>
                    <td class="small text-muted"><?= htmlspecialchars($m['description'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
