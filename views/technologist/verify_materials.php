<?php
$pageTitle = 'Planting Materials';
include __DIR__ . '/../partials/layout_head.php';
?>

<div class="ps-page-header">
    <h2>Planting Materials Inventory</h2>
    <p>Verify availability of planting materials and manage inventory records.</p>
</div>

<?php
$pmModel = new PlantingMaterial($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_material'])) {
        if ($pmModel->create($_POST)) {
            $success = "New planting material added successfully.";
        } else {
            $error = "Failed to add material.";
        }
    } elseif (isset($_POST['update_material'])) {
        if ($pmModel->update(intval($_POST['material_id']), $_POST)) {
            $success = "Material updated successfully.";
        } else {
            $error = "Failed to update material.";
        }
    }
}

$materials = $pmModel->getAll();
?>

<?php if(isset($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if(isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Add Material Form -->
    <div class="col-md-4">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Add New Material</h6>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Material Name <span class="text-danger">*</span></label>
                    <input type="text" name="material_name" class="form-control" placeholder="e.g. Narra Seedlings" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="material_type" class="form-select" required>
                        <option value="" disabled selected>Select type</option>
                        <option>Hardwood Tree</option>
                        <option>Fruit Tree</option>
                        <option>Bamboo</option>
                        <option>Legume Tree</option>
                        <option>Multipurpose Tree</option>
                        <option>Shade Tree</option>
                        <option>Mangrove</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="row mb-3">
                    <div class="col-7">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control" min="0" required>
                    </div>
                    <div class="col-5">
                        <label class="form-label">Unit</label>
                        <select name="unit" class="form-select">
                            <option>packs</option>
                            <option>bundles</option>
                            <option>seedlings</option>
                            <option>pieces</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Brief description..."></textarea>
                </div>
                <button type="submit" name="add_material" value="1" class="btn btn-ps-primary full">Add Material</button>
            </form>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="col-md-8">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Current Inventory</h6>
            <div class="table-responsive">
                <table class="ps-table">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th>Type</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Stock</th>
                            <th>Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materials as $m): 
                            $badge = $m['quantity'] > 200 ? 'ps-badge-approved' : ($m['quantity'] > 50 ? 'ps-badge-pending' : 'ps-badge-rejected');
                            $label = $m['quantity'] > 200 ? 'Good' : ($m['quantity'] > 50 ? 'Low' : 'Critical');
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($m['material_name']) ?></td>
                            <td class="small"><?= htmlspecialchars($m['material_type']) ?></td>
                            <td class="fw-bold"><?= number_format($m['quantity']) ?></td>
                            <td class="small"><?= htmlspecialchars($m['unit']) ?></td>
                            <td><span class="ps-badge <?= $badge ?>"><?= $label ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" 
                                    onclick="openEdit(<?= $m['id'] ?>, '<?= addslashes($m['material_name']) ?>', '<?= addslashes($m['material_type']) ?>', <?= $m['quantity'] ?>, '<?= $m['unit'] ?>', '<?= addslashes($m['description'] ?? '') ?>')">
                                    Edit
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Edit Material</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="material_id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Material Name</label>
                        <input type="text" name="material_name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <input type="text" name="material_type" id="edit_type" class="form-control" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-7">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" id="edit_qty" class="form-control" min="0" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" id="edit_unit" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_desc" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_material" value="1" class="btn btn-ps-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEdit(id, name, type, qty, unit, desc) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_type').value = type;
    document.getElementById('edit_qty').value = qty;
    document.getElementById('edit_unit').value = unit;
    document.getElementById('edit_desc').value = desc;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
