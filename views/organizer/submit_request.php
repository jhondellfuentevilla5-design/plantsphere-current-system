<?php
$pageTitle = 'Submit Request';
include __DIR__ . '/../partials/layout_head.php';

$pmModel   = new PlantingMaterial($conn);
$materials = $pmModel->getAll();

// ── Handle submission ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $mode          = $_POST['submission_mode'] ?? 'form'; // 'form' or 'upload'
    $uploadedPath  = null;
    $uploadError   = null;

    // ── File upload handling ──────────────────────────────
    if ($mode === 'upload') {
        if (empty($_FILES['request_letter']['name'])) {
            $uploadError = 'Please select a file to upload.';
        } else {
            $file     = $_FILES['request_letter'];
            $allowed  = ['pdf','doc','docx'];
            $maxSize  = 5 * 1024 * 1024; // 5 MB
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mimeMap  = [
                'pdf'  => 'application/pdf',
                'doc'  => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $uploadError = 'Upload error. Please try again.';
            } elseif (!in_array($ext, $allowed)) {
                $uploadError = 'Only PDF, DOC, and DOCX files are allowed.';
            } elseif ($file['size'] > $maxSize) {
                $uploadError = 'File size must not exceed 5 MB.';
            } elseif (!isset($mimeMap[$ext]) || $file['type'] !== $mimeMap[$ext]) {
                // Re-check MIME via finfo for security
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $realMime = $finfo->file($file['tmp_name']);
                $validMimes = array_values($mimeMap);
                if (!in_array($realMime, $validMimes)) {
                    $uploadError = 'Invalid file type detected.';
                }
            }

            if (!$uploadError) {
                $safeName     = time() . '_' . $_SESSION['user']['id'] . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
                $destination  = 'uploads/request_letters/' . $safeName;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $uploadedPath = $destination;
                } else {
                    $uploadError = 'Failed to save file. Check folder permissions.';
                }
            }
        }
    }

    // ── Validate required fields ──────────────────────────
    $formError = null;
    if (!$uploadError) {
        $required = ['activity_name','target_location','target_date','number_of_participants','seedling_type','quantity_requested'];
        foreach ($required as $field) {
            if (empty(trim($_POST[$field] ?? ''))) {
                $formError = 'Please fill in all required fields.';
                break;
            }
        }
        // Purpose required only in form mode
        if ($mode === 'form' && empty(trim($_POST['purpose'] ?? ''))) {
            $formError = 'Please provide the purpose / justification.';
        }
    }

    if (!$uploadError && !$formError) {
        $srModel = new ServiceRequest($conn);
        $data = [
            'user_id'              => $_SESSION['user']['id'],
            'activity_name'        => trim($_POST['activity_name']),
            'target_location'      => trim($_POST['target_location']),
            'target_date'          => $_POST['target_date'],
            'number_of_participants' => intval($_POST['number_of_participants']),
            'seedling_type'        => trim($_POST['seedling_type']),
            'quantity_requested'   => intval($_POST['quantity_requested']),
            'purpose'              => $mode === 'upload'
                                        ? '[See attached request letter]'
                                        : trim($_POST['purpose']),
            'request_letter'       => $uploadedPath,
            'proponent_name'       => trim($_POST['proponent_name'] ?? ''),
            'association_name'     => trim($_POST['association_name'] ?? ''),
            'recipient_name'       => trim($_POST['recipient_name'] ?? ''),
            'recipient_position'   => trim($_POST['recipient_position'] ?? ''),
            'activity_time'        => $_POST['activity_time'] ?? null,
        ];

        $id = $srModel->create($data);
        if ($id) {
            // Notify affairs workers
            $affairsWorkers = (new User($conn))->getAllByRole('community_affairs_worker');
            $notifModel     = new Notification($conn);
            $senderName     = htmlspecialchars($_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['lastname']);
            foreach ($affairsWorkers as $aw) {
                $notifModel->create($aw['id'], 'New Request Submitted',
                    "$senderName submitted a new tree planting request.");
            }
            // Notify Barangay Captains for proposal validation
            $captains = (new User($conn))->getAllByRole('barangay_captain');
            $barangayModel = new BarangayApproval($conn);
            foreach ($captains as $cap) {
                $barangayModel->create($id, $cap['id']);
                $notifModel->create($cap['id'], 'New Proposal for Validation',
                    "$senderName submitted a proposal letter requiring your validation.");
            }
            $success = "Request submitted successfully! Reference: " . $srModel->getRequestNumber($id);
        } else {
            $error = "Failed to submit request. Please try again.";
        }
    } else {
        $error = $uploadError ?? $formError;
    }
}

// Preserve POST values on error
$old = $_POST ?? [];
$activeMode = $old['submission_mode'] ?? 'form';
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill"></i>
        <div><?= htmlspecialchars($success) ?> <a href="index.php?action=my_requests" class="alert-link">View my requests →</a></div>
    </div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-circle-fill"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
<?php endif; ?>

<div class="ps-page-header">
    <h2>Submit Proposal Letter</h2>
    <p>Create your tree planting proposal. It will be sent to the Barangay Captain for validation before processing.</p>
</div>

<form method="POST" action="index.php?action=submit_request"
      enctype="multipart/form-data" id="requestForm">

    <input type="hidden" name="submission_mode" id="submissionMode" value="<?= htmlspecialchars($activeMode) ?>">

    <!-- ── Mode toggle ── -->
    <div class="ps-card mb-4">
        <p class="small text-muted mb-3">Choose how you want to submit your request:</p>
        <div class="d-flex gap-3 flex-wrap">

            <label class="mode-card <?= $activeMode === 'form' ? 'active' : '' ?>" id="modeCardForm" onclick="switchMode('form')">
                <div class="mode-card-icon"><i class="bi bi-pencil-square"></i></div>
                <div>
                    <div class="mode-card-title">Fill Out Form</div>
                    <div class="mode-card-desc">Type your request details directly into the form fields.</div>
                </div>
            </label>

            <label class="mode-card <?= $activeMode === 'upload' ? 'active' : '' ?>" id="modeCardUpload" onclick="switchMode('upload')">
                <div class="mode-card-icon"><i class="bi bi-file-earmark-arrow-up"></i></div>
                <div>
                    <div class="mode-card-title">Upload Request Letter</div>
                    <div class="mode-card-desc">Upload a prepared PDF, DOC, or DOCX request letter.</div>
                </div>
            </label>

        </div>
    </div>

    <!-- ── Shared fields (always required) ── -->
    <div class="ps-card mb-4">
        <h6 class="fw-bold text-ps-green mb-3">
            <i class="bi bi-person me-2"></i>Proponent Information
        </h6>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Proponent Name <span class="text-danger">*</span></label>
                <input type="text" name="proponent_name" class="form-control"
                       placeholder="Full name of proponent"
                       value="<?= htmlspecialchars($old['proponent_name'] ?? ($_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['lastname'])) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Association / Organization Name</label>
                <input type="text" name="association_name" class="form-control"
                       placeholder="e.g. Barangay Farmers Association"
                       value="<?= htmlspecialchars($old['association_name'] ?? '') ?>">
            </div>
        </div>
        <div class="row mb-0">
            <div class="col-md-6">
                <label class="form-label">Recipient Name <span class="text-danger">*</span></label>
                <input type="text" name="recipient_name" class="form-control"
                       placeholder="Name of recipient officer"
                       value="<?= htmlspecialchars($old['recipient_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Recipient Position / Title <span class="text-danger">*</span></label>
                <input type="text" name="recipient_position" class="form-control"
                       placeholder="e.g. Municipal Agriculture Officer"
                       value="<?= htmlspecialchars($old['recipient_position'] ?? '') ?>" required>
            </div>
        </div>
    </div>

    <!-- ── Activity details ── -->
    <div class="ps-card mb-4">
        <h6 class="fw-bold text-ps-green mb-3">
            <i class="bi bi-info-circle me-2"></i>Activity Details
        </h6>

        <div class="mb-3">
            <label class="form-label">Activity Name / Title <span class="text-danger">*</span></label>
            <input type="text" name="activity_name" class="form-control"
                   placeholder="e.g. Barangay Reforestation Drive 2026"
                   value="<?= htmlspecialchars($old['activity_name'] ?? '') ?>" required>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Target Location / Site <span class="text-danger">*</span></label>
                <input type="text" name="target_location" class="form-control"
                       placeholder="Barangay, Municipality, Province"
                       value="<?= htmlspecialchars($old['target_location'] ?? '') ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Target Date <span class="text-danger">*</span></label>
                <input type="date" name="target_date" class="form-control"
                       min="<?= date('Y-m-d') ?>"
                       value="<?= htmlspecialchars($old['target_date'] ?? '') ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Activity Time</label>
                <input type="time" name="activity_time" class="form-control"
                       value="<?= htmlspecialchars($old['activity_time'] ?? '08:00') ?>">
            </div>
        </div>

        <div class="mb-0">
            <label class="form-label">Number of Participants <span class="text-danger">*</span></label>
            <input type="number" name="number_of_participants" class="form-control"
                   min="1" placeholder="e.g. 50"
                   value="<?= htmlspecialchars($old['number_of_participants'] ?? '') ?>" required>
        </div>
    </div>

    <!-- ── Seedling section (always required) ── -->
    <div class="ps-card mb-4">
        <h6 class="fw-bold text-ps-green mb-3">
            <i class="bi bi-tree me-2"></i>Seedling / Material Request
        </h6>
        <div class="row mb-0">
            <div class="col-md-6 mb-3">
                <label class="form-label">Seedling Type Requested <span class="text-danger">*</span></label>
                <select name="seedling_type" class="form-select" required>
                    <option value="" disabled <?= empty($old['seedling_type']) ? 'selected' : '' ?>>Select seedling type</option>
                    <?php
                    $interventionList = [
                        'Banana Lakatan','Avocado','Marang','Durian','Lanzones',
                        'Rambutan','Jackfruit','Corn','Coffee','Cacao',
                        'Vegetable Seeds'
                    ];
                    // Build lookup from DB materials for quantity display
                    $matLookup = [];
                    foreach ($materials as $m) {
                        $matLookup[$m['material_name']] = $m;
                    }
                    foreach ($interventionList as $iName):
                        $mat = $matLookup[$iName] ?? null;
                        $qtyLabel = ($mat && $mat['quantity'] > 0)
                            ? ' (' . number_format($mat['quantity']) . ' ' . htmlspecialchars($mat['unit']) . ' available)'
                            : '';
                    ?>
                        <option value="<?= htmlspecialchars($iName) ?>"
                            <?= ($old['seedling_type'] ?? '') === $iName ? 'selected' : '' ?>>
                            <?= htmlspecialchars($iName) ?><?= $qtyLabel ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="Other" <?= ($old['seedling_type'] ?? '') === 'Other' ? 'selected' : '' ?>>Other (specify in purpose)</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Quantity Requested <span class="text-danger">*</span></label>
                <input type="number" name="quantity_requested" class="form-control"
                       min="1" placeholder="Number of packs/seedlings"
                       value="<?= htmlspecialchars($old['quantity_requested'] ?? '') ?>" required>
            </div>
        </div>
    </div>

    <!-- ── FORM MODE: Purpose field ── -->
    <div class="ps-card mb-4" id="sectionForm" style="<?= $activeMode === 'upload' ? 'display:none;' : '' ?>">
        <h6 class="fw-bold text-ps-green mb-3">
            <i class="bi bi-chat-left-text me-2"></i>Purpose / Justification
        </h6>
        <textarea name="purpose" id="purposeField" class="form-control" rows="5"
                  placeholder="Describe the purpose of the tree planting activity, target beneficiaries, and expected outcomes..."
                  <?= $activeMode === 'form' ? 'required' : '' ?>><?= htmlspecialchars($old['purpose'] ?? '') ?></textarea>
        <div class="form-text mt-1">
            <i class="bi bi-info-circle me-1"></i>
            Provide a clear justification for this request including target beneficiaries and expected outcomes.
        </div>
    </div>

    <!-- ── UPLOAD MODE: File upload ── -->
    <div class="ps-card mb-4" id="sectionUpload" style="<?= $activeMode === 'form' ? 'display:none;' : '' ?>">
        <h6 class="fw-bold text-ps-green mb-1">
            <i class="bi bi-file-earmark-arrow-up me-2"></i>Upload Request Letter
        </h6>
        <p class="small text-muted mb-3">
            Upload your official request letter. The document should include the purpose, justification, and all relevant details.
        </p>

        <!-- Drop zone -->
        <div class="upload-dropzone" id="dropZone" onclick="document.getElementById('fileInput').click()">
            <div class="upload-dropzone-inner" id="dropZoneInner">
                <i class="bi bi-cloud-arrow-up upload-dropzone-icon"></i>
                <div class="upload-dropzone-title">Click to browse or drag & drop</div>
                <div class="upload-dropzone-sub">PDF, DOC, DOCX — max 5 MB</div>
            </div>
            <!-- File selected state -->
            <div class="upload-file-preview d-none" id="filePreview">
                <i class="bi bi-file-earmark-check upload-file-icon"></i>
                <div>
                    <div class="upload-file-name" id="fileName">—</div>
                    <div class="upload-file-size text-muted small" id="fileSize">—</div>
                </div>
                <button type="button" class="upload-file-remove" onclick="clearFile(event)" title="Remove file">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <input type="file" name="request_letter" id="fileInput"
               accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
               class="d-none"
               onchange="handleFileSelect(this)">

        <div class="mt-2 d-flex gap-3 flex-wrap">
            <span class="small text-muted"><i class="bi bi-file-earmark-pdf text-danger me-1"></i>PDF</span>
            <span class="small text-muted"><i class="bi bi-file-earmark-word text-primary me-1"></i>DOC</span>
            <span class="small text-muted"><i class="bi bi-file-earmark-word text-primary me-1"></i>DOCX</span>
            <span class="small text-muted"><i class="bi bi-hdd me-1"></i>Max 5 MB</span>
        </div>

        <!-- File hash display (shown after upload) -->
        <div id="fileHashInfo" class="d-none mt-3 p-3 rounded" style="background:#f0faf0;border:1px solid #b7dfb7;">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-shield-check text-success"></i>
                <span class="small fw-semibold text-success">File integrity verified</span>
            </div>
            <div class="small text-muted">SHA-256: <code id="fileHashValue" class="small"></code></div>
            <input type="hidden" name="file_hash" id="fileHashInput">
        </div>
    </div>

    <!-- ── Submit buttons ── -->
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-ps-primary">
            <i class="bi bi-send me-2"></i>Submit Proposal
        </button>
        <a href="index.php?action=dashboard" class="btn btn-outline-secondary">Cancel</a>
    </div>

</form>

<!-- ── Styles ── -->
<style>
/* Mode toggle cards */
.mode-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 20px;
    border: 2px solid var(--ps-border);
    border-radius: 12px;
    cursor: pointer;
    flex: 1;
    min-width: 220px;
    transition: border-color 0.2s, background 0.2s;
    background: #fff;
    user-select: none;
}
.mode-card:hover { border-color: var(--ps-green-light); background: var(--ps-green-pale); }
.mode-card.active {
    border-color: var(--ps-green);
    background: var(--ps-green-pale);
    box-shadow: 0 0 0 3px rgba(45,90,39,0.1);
}
.mode-card-icon {
    width: 40px; height: 40px;
    background: var(--ps-green-pale);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    font-size: 1.2rem;
    color: var(--ps-green);
    transition: background 0.2s;
}
.mode-card.active .mode-card-icon { background: var(--ps-green); color: #fff; }
.mode-card-title { font-size: 0.9rem; font-weight: 700; color: var(--ps-text); margin-bottom: 2px; }
.mode-card-desc  { font-size: 0.78rem; color: var(--ps-muted); line-height: 1.4; }

/* Drop zone */
.upload-dropzone {
    border: 2px dashed #c8ddc5;
    border-radius: 12px;
    padding: 36px 24px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    background: #fafcfa;
    position: relative;
}
.upload-dropzone:hover,
.upload-dropzone.drag-over {
    border-color: var(--ps-green);
    background: var(--ps-green-pale);
}
.upload-dropzone-icon { font-size: 2.5rem; color: var(--ps-green-light); display: block; margin-bottom: 10px; }
.upload-dropzone-title { font-size: 0.95rem; font-weight: 600; color: var(--ps-text); margin-bottom: 4px; }
.upload-dropzone-sub   { font-size: 0.8rem; color: var(--ps-muted); }

/* File preview inside dropzone */
.upload-file-preview {
    display: flex;
    align-items: center;
    gap: 14px;
    text-align: left;
    justify-content: center;
}
.upload-file-icon { font-size: 2rem; color: var(--ps-green); flex-shrink: 0; }
.upload-file-name { font-size: 0.9rem; font-weight: 600; color: var(--ps-text); word-break: break-all; }
.upload-file-remove {
    background: none; border: none;
    color: #dc3545; font-size: 1rem;
    cursor: pointer; padding: 4px;
    margin-left: auto;
    flex-shrink: 0;
    transition: color 0.2s;
}
.upload-file-remove:hover { color: #a71d2a; }
</style>

<script>
// ── Mode switching ────────────────────────────────────────
function switchMode(mode) {
    document.getElementById('submissionMode').value = mode;

    const isUpload = mode === 'upload';

    document.getElementById('modeCardForm').classList.toggle('active', !isUpload);
    document.getElementById('modeCardUpload').classList.toggle('active', isUpload);

    document.getElementById('sectionForm').style.display   = isUpload ? 'none' : '';
    document.getElementById('sectionUpload').style.display = isUpload ? '' : 'none';

    // Toggle required on purpose textarea
    const purpose = document.getElementById('purposeField');
    purpose.required = !isUpload;

    // Toggle required on file input
    document.getElementById('fileInput').required = isUpload;
}

// ── File selection ────────────────────────────────────────
function handleFileSelect(input) {
    if (!input.files.length) return;
    const file = input.files[0];
    showFilePreview(file);
}

function showFilePreview(file) {
    const inner   = document.getElementById('dropZoneInner');
    const preview = document.getElementById('filePreview');
    const name    = document.getElementById('fileName');
    const size    = document.getElementById('fileSize');

    name.textContent = file.name;
    size.textContent = formatBytes(file.size);

    inner.classList.add('d-none');
    preview.classList.remove('d-none');
}

function clearFile(e) {
    e.stopPropagation();
    document.getElementById('fileInput').value = '';
    document.getElementById('dropZoneInner').classList.remove('d-none');
    document.getElementById('filePreview').classList.add('d-none');
}

function formatBytes(bytes) {
    if (bytes < 1024)       return bytes + ' B';
    if (bytes < 1048576)    return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(2) + ' MB';
}

// ── Drag & drop ───────────────────────────────────────────
const dropZone = document.getElementById('dropZone');

dropZone.addEventListener('dragover', e => {
    e.preventDefault();
    dropZone.classList.add('drag-over');
});
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (!file) return;

    const allowed = ['application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

    if (!allowed.includes(file.type)) {
        alert('Only PDF, DOC, and DOCX files are allowed.');
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        alert('File size must not exceed 5 MB.');
        return;
    }

    // Assign to file input via DataTransfer
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('fileInput').files = dt.files;
    showFilePreview(file);
});

// ── Init mode on page load ────────────────────────────────
switchMode('<?= $activeMode ?>');
</script>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
