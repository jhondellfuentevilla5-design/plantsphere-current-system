<!-- Shared RSBSA form fields — included in check_rsbsa.php -->
<div class="mb-3">
    <label class="form-label">RSBSA Number <span class="text-danger">*</span></label>
    <input type="text" name="rsbsa_number" class="form-control"
           placeholder="e.g. 01-001-001-000001" required>
    <div class="form-text">Provided by the local agriculture office.</div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <label class="form-label">Barangay <span class="text-danger">*</span></label>
        <input type="text" name="barangay" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Municipality <span class="text-danger">*</span></label>
        <input type="text" name="municipality" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Province <span class="text-danger">*</span></label>
        <input type="text" name="province" class="form-control" required>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label">Farm Size (hectares) <span class="text-danger">*</span></label>
        <input type="number" name="farm_size" class="form-control"
               step="0.01" min="0.01" placeholder="e.g. 1.50" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Primary Crop Type <span class="text-danger">*</span></label>
        <select name="crop_type" class="form-select" required>
            <option value="" disabled selected>Select crop type</option>
            <option>Rice</option>
            <option>Corn</option>
            <option>Vegetables</option>
            <option>Fruit Trees</option>
            <option>Root Crops</option>
            <option>Coconut</option>
            <option>Sugarcane</option>
            <option>Mixed Farming</option>
            <option>Agroforestry</option>
            <option>Other</option>
        </select>
    </div>
</div>

<div class="mb-0">
    <label class="form-label">Registration Date <span class="text-danger">*</span></label>
    <input type="date" name="registration_date" class="form-control"
           value="<?= date('Y-m-d') ?>" required>
</div>
