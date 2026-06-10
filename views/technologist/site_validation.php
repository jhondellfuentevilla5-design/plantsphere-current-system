<?php
$pageTitle = 'Site Validation';
include __DIR__ . '/../partials/layout_head.php';

$srModel       = new ServiceRequest($conn);
$forValidation = $srModel->getByStatus('for_validation');
$requestId     = intval($_GET['request_id'] ?? 0);
$selectedRequest = $requestId ? $srModel->getById($requestId) : null;
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
/* ── Photo upload dropzone ── */
.photo-dropzone {
    border: 2px dashed #c8ddc5;
    border-radius: 12px;
    padding: 28px 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    background: #fafcfa;
}
.photo-dropzone:hover,
.photo-dropzone.drag-over {
    border-color: var(--ps-green);
    background: var(--ps-green-pale);
}
.photo-dropzone-icon { font-size: 2rem; color: var(--ps-green-light); display: block; margin-bottom: 8px; }
.photo-dropzone-title { font-size: 0.9rem; font-weight: 600; color: var(--ps-text); margin-bottom: 3px; }
.photo-dropzone-sub   { font-size: 0.78rem; color: var(--ps-muted); }

/* ── Photo preview grid ── */
.photo-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
}
.photo-preview-item {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    aspect-ratio: 1;
    border: 2px solid #d8e8d5;
}
.photo-preview-item img {
    width: 100%; height: 100%;
    object-fit: cover; display: block;
}
.photo-remove-btn {
    position: absolute; top: 4px; right: 4px;
    background: rgba(0,0,0,0.55); color: #fff;
    border: none; border-radius: 50%;
    width: 24px; height: 24px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.65rem; cursor: pointer;
    transition: background 0.2s;
}
.photo-remove-btn:hover { background: #dc3545; }
.photo-preview-label {
    position: absolute; bottom: 0; left: 0; right: 0;
    background: rgba(0,0,0,0.45);
    color: #fff; font-size: 0.65rem;
    text-align: center; padding: 3px 0;
}
</style>

<div class="ps-page-header">
    <h2>Site Validation</h2>
    <p>Conduct site validation and prepare the validation report with seed pack count.</p>
</div>

<!-- API response alert -->
<div id="apiAlert" class="d-none mb-3"></div>

<div class="row g-4">

    <!-- ── Left: Requests list ── -->
    <div class="col-md-4">
        <div class="ps-card">
            <h6 class="fw-bold text-ps-green mb-3">Requests for Validation</h6>
            <?php if (empty($forValidation)): ?>
                <div class="empty-state">
                    <i class="bi bi-clock" style="font-size:2rem;opacity:0.2;"></i>
                    <p class="small mt-2">No requests pending site validation.</p>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2" id="requestList">
                    <?php foreach ($forValidation as $req): ?>
                    <div class="request-item p-3 rounded border <?= $requestId == $req['id'] ? 'border-success bg-ps-pale' : '' ?>"
                         style="cursor:pointer;"
                         onclick="loadRequest(<?= $req['id'] ?>)">
                        <div class="small fw-semibold text-ps-green"><?= htmlspecialchars($req['request_number']) ?></div>
                        <div class="small"><?= htmlspecialchars($req['activity_name']) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($req['firstname'] . ' ' . $req['lastname']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Right: Validation form ── -->
    <div class="col-md-8">

        <!-- Request details card (loaded via API) -->
        <div id="requestDetailsCard" class="ps-card mb-3 <?= $selectedRequest ? '' : 'd-none' ?>">
            <h6 class="fw-bold text-ps-green mb-2">Request Details</h6>
            <div class="row g-2 small" id="requestDetailsBody">
                <?php if ($selectedRequest): ?>
                <div class="col-6"><span class="text-muted">Activity:</span> <span id="det-activity"><?= htmlspecialchars($selectedRequest['activity_name']) ?></span></div>
                <div class="col-6"><span class="text-muted">Organizer:</span> <span id="det-organizer"><?= htmlspecialchars($selectedRequest['firstname'] . ' ' . $selectedRequest['lastname']) ?></span></div>
                <div class="col-6"><span class="text-muted">Location:</span> <span id="det-location"><?= htmlspecialchars($selectedRequest['target_location']) ?></span></div>
                <div class="col-6"><span class="text-muted">Target Date:</span> <span id="det-date"><?= date('M d, Y', strtotime($selectedRequest['target_date'])) ?></span></div>
                <div class="col-6"><span class="text-muted">Seedling:</span> <span id="det-seedling"><?= htmlspecialchars($selectedRequest['seedling_type']) ?></span></div>
                <div class="col-6"><span class="text-muted">Qty Requested:</span> <span id="det-qty"><?= number_format($selectedRequest['quantity_requested']) ?></span></div>
                <?php else: ?>
                <div class="col-6"><span class="text-muted">Activity:</span> <span id="det-activity"></span></div>
                <div class="col-6"><span class="text-muted">Organizer:</span> <span id="det-organizer"></span></div>
                <div class="col-6"><span class="text-muted">Location:</span> <span id="det-location"></span></div>
                <div class="col-6"><span class="text-muted">Target Date:</span> <span id="det-date"></span></div>
                <div class="col-6"><span class="text-muted">Seedling:</span> <span id="det-seedling"></span></div>
                <div class="col-6"><span class="text-muted">Qty Requested:</span> <span id="det-qty"></span></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Validation form -->
        <div id="validationFormCard" class="ps-card <?= $selectedRequest ? '' : 'd-none' ?>">
            <h6 class="fw-bold text-ps-green mb-3">Validation Report Form</h6>
            <form id="validationForm" enctype="multipart/form-data">
                <input type="hidden" name="request_id" id="formRequestId"
                       value="<?= $requestId ?>">

                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="form-label">Site Location <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="site_location" id="site_location" class="form-control"
                                   value="<?= htmlspecialchars($selectedRequest['target_location'] ?? '') ?>"
                                   placeholder="Type address or click map to pin location" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="searchAddress()" title="Search on map">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Site Area (ha) <span class="text-danger">*</span></label>
                        <input type="number" name="site_area" class="form-control" step="0.01" min="0.01" required>
                    </div>
                </div>

                <!-- Hidden lat/lng fields -->
                <input type="hidden" name="site_lat" id="site_lat">
                <input type="hidden" name="site_lng" id="site_lng">

                <!-- ── Map ── -->
                <div class="mb-3">
                    <label class="form-label d-flex align-items-center gap-2">
                        <i class="bi bi-map-fill text-ps-green"></i>
                        Pin Planting Site on Map
                        <span class="text-muted fw-normal small">(click anywhere on map to mark the exact location)</span>
                    </label>
                    <div id="siteMap" style="height:320px; border-radius:10px; border:1.5px solid #d8e8d5; overflow:hidden;"></div>
                    <div id="mapCoords" class="small text-muted mt-1 d-none">
                        <i class="bi bi-geo-alt-fill text-success me-1"></i>
                        Pinned: <span id="coordsDisplay"></span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Validation Date <span class="text-danger">*</span></label>
                        <input type="date" name="validation_date" class="form-control"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Scheduled Planting Date <span class="text-danger">*</span></label>
                        <input type="date" name="schedule_date" id="schedule_date" class="form-control"
                               value="<?= $selectedRequest['target_date'] ?? '' ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Soil Condition <span class="text-danger">*</span></label>
                        <select name="soil_condition" class="form-select" required>
                            <option value="" disabled selected>Select condition</option>
                            <option>Excellent - Highly suitable</option>
                            <option>Good - Suitable</option>
                            <option>Fair - Moderately suitable</option>
                            <option>Poor - Needs improvement</option>
                            <option>Not suitable</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Site Accessibility <span class="text-danger">*</span></label>
                        <select name="accessibility" class="form-select" required>
                            <option value="" disabled selected>Select accessibility</option>
                            <option>Easily accessible</option>
                            <option>Accessible with vehicle</option>
                            <option>Accessible on foot only</option>
                            <option>Difficult to access</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Recommended Species <span class="text-danger">*</span></label>
                    <input type="text" name="recommended_species" id="recommended_species"
                           class="form-control" required>
                </div>

                <hr class="section-divider">
                <h6 class="fw-bold text-ps-green mb-3">Seed Pack Count</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Seed Packs Counted <span class="text-danger">*</span></label>
                        <input type="number" name="seed_packs_counted" class="form-control" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Available Seedlings <span class="text-danger">*</span></label>
                        <input type="number" name="available_seedlings" class="form-control" min="0" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Site Findings <span class="text-danger">*</span></label>
                    <textarea name="findings" class="form-control" rows="3" required
                              placeholder="Describe site conditions, observations, and findings..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Recommendation <span class="text-danger">*</span></label>
                    <textarea name="recommendation" class="form-control" rows="3" required
                              placeholder="Provide recommendations for the tree planting activity..."></textarea>
                </div>

                <!-- ── Site Photos ── -->
                <hr class="section-divider">
                <h6 class="fw-bold text-ps-green mb-1">
                    <i class="bi bi-camera me-2"></i>Site Photos
                    <span class="text-muted fw-normal small ms-1">(optional, max 5 photos)</span>
                </h6>
                <p class="small text-muted mb-3">Upload photos of the planting site to document its current condition and appearance.</p>

                <!-- Drop zone -->
                <div class="photo-dropzone" id="photoDropzone"
                     onclick="document.getElementById('sitePhotoInput').click()">
                    <i class="bi bi-image photo-dropzone-icon"></i>
                    <div class="photo-dropzone-title">Click to browse or drag & drop photos</div>
                    <div class="photo-dropzone-sub">JPG, PNG, WEBP — max 5 MB each, up to 5 photos</div>
                </div>
                <input type="file" name="site_photos[]" id="sitePhotoInput"
                       accept="image/jpeg,image/png,image/webp"
                       multiple class="d-none"
                       onchange="handlePhotoSelect(this)">

                <!-- Photo previews -->
                <div id="photoPreviewGrid" class="photo-preview-grid mt-3"></div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-ps-primary" id="submitBtn">
                        <i class="bi bi-send me-1"></i>
                        <span id="submitBtnText">Submit Validation Report</span>
                    </button>
                    <a href="index.php?action=site_validation" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Empty state -->
        <div id="emptyState" class="ps-card <?= $selectedRequest ? 'd-none' : '' ?>">
            <div class="empty-state">
                <i class="bi bi-geo-alt" style="font-size:2.5rem;opacity:0.2;"></i>
                <p class="mt-2">Select a request from the list to begin site validation.</p>
            </div>
        </div>

    </div>
</div>

<script>
// ── Load request details via API ──────────────────────────
function loadRequest(requestId) {
    // Highlight selected
    document.querySelectorAll('.request-item').forEach(el => {
        el.classList.remove('border-success', 'bg-ps-pale');
    });
    event.currentTarget.classList.add('border-success', 'bg-ps-pale');

    // Show loading state
    document.getElementById('requestDetailsCard').classList.remove('d-none');
    document.getElementById('validationFormCard').classList.remove('d-none');
    document.getElementById('emptyState').classList.add('d-none');

    // Init map after form is visible
    setTimeout(() => { if (!map) initMap(); else map.invalidateSize(); }, 150);

    // Call API
    fetch('api/get_request_details.php?request_id=' + requestId, {
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            showAlert('danger', data.error || 'Failed to load request details.');
            return;
        }
        const req = data.request;

        // Populate details card
        document.getElementById('det-activity').textContent  = req.activity_name;
        document.getElementById('det-organizer').textContent = req.organizer;
        document.getElementById('det-location').textContent  = req.target_location;
        document.getElementById('det-date').textContent      = req.target_date;
        document.getElementById('det-seedling').textContent  = req.seedling_type;
        document.getElementById('det-qty').textContent       = req.quantity_requested.toLocaleString();

        // Pre-fill form fields
        document.getElementById('formRequestId').value        = req.id;
        document.getElementById('site_location').value        = req.target_location;
        document.getElementById('schedule_date').value        = req.target_date;
        document.getElementById('recommended_species').value  = req.seedling_type;
    })
    .catch(() => showAlert('danger', 'Network error. Please try again.'));
}

// ── Submit validation via API (multipart/form-data for photo support) ──
document.getElementById('validationForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const btn     = document.getElementById('submitBtn');
    const btnText = document.getElementById('submitBtnText');
    btn.disabled  = true;
    btnText.textContent = 'Submitting...';

    const formData = new FormData(this);

    fetch('api/submit_validation.php', {
        method:      'POST',
        credentials: 'same-origin',
        body:        formData   // multipart — no Content-Type header needed
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert('success',
                '<i class="bi bi-check-circle-fill me-2"></i>' +
                'Validation report submitted for <strong>' + data.request_number + '</strong>. ' +
                (data.photos_saved > 0 ? data.photos_saved + ' photo(s) saved. ' : '') +
                'Request moved to validated status.'
            );
            document.querySelectorAll('.request-item').forEach(el => {
                if (el.querySelector('.text-ps-green') &&
                    el.querySelector('.text-ps-green').textContent.includes(data.request_number)) {
                    el.remove();
                }
            });
            document.getElementById('validationForm').reset();
            document.getElementById('photoPreviewGrid').innerHTML = '';
            document.getElementById('requestDetailsCard').classList.add('d-none');
            document.getElementById('validationFormCard').classList.add('d-none');
            document.getElementById('emptyState').classList.remove('d-none');
        } else {
            showAlert('danger', '<i class="bi bi-exclamation-circle-fill me-2"></i>' + data.error);
        }
    })
    .catch(() => showAlert('danger', 'Network error. Please try again.'))
    .finally(() => {
        btn.disabled = false;
        btnText.textContent = 'Submit Validation Report';
    });
});

// ── Photo handling ────────────────────────────────────────
const MAX_PHOTOS = 5;
const MAX_SIZE   = 5 * 1024 * 1024; // 5 MB
let selectedFiles = [];

function handlePhotoSelect(input) {
    const newFiles = Array.from(input.files);
    const allowed  = ['image/jpeg','image/png','image/webp'];

    for (const f of newFiles) {
        if (!allowed.includes(f.type)) {
            alert(`"${f.name}" is not a supported image type. Use JPG, PNG, or WEBP.`);
            continue;
        }
        if (f.size > MAX_SIZE) {
            alert(`"${f.name}" exceeds 5 MB limit.`);
            continue;
        }
        if (selectedFiles.length >= MAX_PHOTOS) {
            alert(`Maximum ${MAX_PHOTOS} photos allowed.`);
            break;
        }
        selectedFiles.push(f);
    }
    // Clear the input so same file can be re-added
    input.value = '';
    rebuildFileInput();
    renderPreviews();
}

function rebuildFileInput() {
    // Rebuild the file input's FileList from selectedFiles
    const dt = new DataTransfer();
    selectedFiles.forEach(f => dt.items.add(f));
    document.getElementById('sitePhotoInput').files = dt.files;
}

function removePhoto(index) {
    selectedFiles.splice(index, 1);
    rebuildFileInput();
    renderPreviews();
}

function renderPreviews() {
    const grid = document.getElementById('photoPreviewGrid');
    grid.innerHTML = '';
    selectedFiles.forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'photo-preview-item';
            div.innerHTML = `
                <img src="${e.target.result}" alt="Site photo ${i+1}">
                <button type="button" class="photo-remove-btn" onclick="removePhoto(${i})" title="Remove">
                    <i class="bi bi-x-lg"></i>
                </button>
                <div class="photo-preview-label">Photo ${i+1}</div>`;
            grid.appendChild(div);
        };
        reader.readAsDataURL(file);
    });

    // Update dropzone text
    const dz = document.getElementById('photoDropzone');
    dz.querySelector('.photo-dropzone-title').textContent =
        selectedFiles.length > 0
            ? `${selectedFiles.length} photo(s) selected — click to add more`
            : 'Click to browse or drag & drop photos';
}

// Drag & drop on photo dropzone
const photoDZ = document.getElementById('photoDropzone');
photoDZ.addEventListener('dragover', e => { e.preventDefault(); photoDZ.classList.add('drag-over'); });
photoDZ.addEventListener('dragleave', () => photoDZ.classList.remove('drag-over'));
photoDZ.addEventListener('drop', e => {
    e.preventDefault();
    photoDZ.classList.remove('drag-over');
    const dt = e.dataTransfer;
    if (dt.files.length) {
        const fakeInput = { files: dt.files };
        handlePhotoSelect(fakeInput);
    }
});

// ── Alert helper ──────────────────────────────────────────
function showAlert(type, message) {
    const el = document.getElementById('apiAlert');
    el.className = 'alert alert-' + type + ' d-flex align-items-center gap-2';
    el.innerHTML = message;
    el.classList.remove('d-none');
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    if (type === 'success') {
        setTimeout(() => el.classList.add('d-none'), 6000);
    }
}

// ── Auto-load if request_id in URL ────────────────────────
<?php if ($requestId): ?>
loadRequest(<?= $requestId ?>);
<?php endif; ?>
</script>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// ── Map initialization ────────────────────────────────────
let map, marker;
const DEFAULT_LAT = 7.0731;   // Davao City center
const DEFAULT_LNG = 125.6128;

function initMap(lat, lng) {
    if (map) return; // already initialized

    map = L.map('siteMap').setView([lat || DEFAULT_LAT, lng || DEFAULT_LNG], 13);

    // OpenStreetMap tiles (free, no API key)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    // Custom green marker icon
    const greenIcon = L.divIcon({
        className: '',
        html: '<div style="background:#2d5a27;width:18px;height:18px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.4);"></div>',
        iconSize: [18, 18],
        iconAnchor: [9, 18],
    });

    // If coordinates already set, show marker
    if (lat && lng) {
        marker = L.marker([lat, lng], { draggable: true, icon: greenIcon }).addTo(map);
        marker.on('dragend', e => updatePin(e.target.getLatLng().lat, e.target.getLatLng().lng));
        showCoordsDisplay(lat, lng);
    }

    // Click to place/move marker
    map.on('click', e => {
        const { lat, lng } = e.latlng;
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true, icon: greenIcon }).addTo(map);
            marker.on('dragend', ev => updatePin(ev.target.getLatLng().lat, ev.target.getLatLng().lng));
        }
        updatePin(lat, lng);
    });
}

function updatePin(lat, lng) {
    document.getElementById('site_lat').value = lat.toFixed(7);
    document.getElementById('site_lng').value = lng.toFixed(7);
    showCoordsDisplay(lat, lng);

    // Reverse geocode using Nominatim (free OpenStreetMap geocoder)
    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
        .then(r => r.json())
        .then(data => {
            if (data.display_name) {
                const locationField = document.getElementById('site_location');
                if (!locationField.value || confirm('Update site location field with map address?\n\n"' + data.display_name + '"')) {
                    locationField.value = data.display_name;
                }
            }
        })
        .catch(() => {}); // silently fail if offline
}

function showCoordsDisplay(lat, lng) {
    const coordsEl = document.getElementById('mapCoords');
    const displayEl = document.getElementById('coordsDisplay');
    displayEl.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
    coordsEl.classList.remove('d-none');
}

function searchAddress() {
    const query = document.getElementById('site_location').value.trim();
    if (!query) return;

    fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=1`)
        .then(r => r.json())
        .then(data => {
            if (data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lng = parseFloat(data[0].lon);
                map.setView([lat, lng], 16);
                if (marker) {
                    marker.setLatLng([lat, lng]);
                } else {
                    const greenIcon = L.divIcon({
                        className: '',
                        html: '<div style="background:#2d5a27;width:18px;height:18px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.4);"></div>',
                        iconSize: [18, 18], iconAnchor: [9, 18],
                    });
                    marker = L.marker([lat, lng], { draggable: true, icon: greenIcon }).addTo(map);
                    marker.on('dragend', e => updatePin(e.target.getLatLng().lat, e.target.getLatLng().lng));
                }
                updatePin(lat, lng);
            } else {
                alert('Location not found. Try a more specific address.');
            }
        })
        .catch(() => alert('Search failed. Check your internet connection.'));
}

// Initialize map when form becomes visible
const formObserver = new MutationObserver(() => {
    const card = document.getElementById('validationFormCard');
    if (card && !card.classList.contains('d-none')) {
        setTimeout(() => {
            if (!map) initMap();
            map.invalidateSize();
        }, 100);
    }
});
formObserver.observe(document.body, { attributes: true, subtree: true });

// Also init on page load if request pre-selected
<?php if ($requestId): ?>
document.addEventListener('DOMContentLoaded', () => setTimeout(() => { if (!map) initMap(); }, 300));
<?php endif; ?>
</script>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
