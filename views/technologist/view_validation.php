<?php
$pageTitle = 'Validation Report';
include __DIR__ . '/../partials/layout_head.php';

$requestId = intval($_GET['request_id'] ?? 0);
$vrModel   = new ValidationReport($conn);
$srModel   = new ServiceRequest($conn);

if (!$requestId) {
    echo '<div class="alert alert-danger">Invalid request.</div>';
    include __DIR__ . '/../partials/layout_foot.php';
    exit;
}

$report  = $vrModel->getByRequest($requestId);
$request = $srModel->getById($requestId);

if (!$report || !$request) {
    echo '<div class="alert alert-warning">No validation report found for this request.</div>';
    include __DIR__ . '/../partials/layout_foot.php';
    exit;
}

// Parse site photos
$photos = [];
if (!empty($report['site_photos'])) {
    $decoded = json_decode($report['site_photos'], true);
    if (is_array($decoded)) $photos = $decoded;
}
?>

<style>
.vr-section {
    background: #fff;
    border: 1px solid #d8e8d5;
    border-radius: 14px;
    padding: 24px 28px;
    margin-bottom: 20px;
}
.vr-section-title {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--ps-green);
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 1.5px solid #e8f5e2;
    display: flex;
    align-items: center;
    gap: 8px;
}
.vr-field { margin-bottom: 14px; }
.vr-label { font-size: 0.72rem; font-weight: 600; color: #6c757d; margin-bottom: 3px; }
.vr-value { font-size: 0.9rem; color: #1a2e1a; font-weight: 500; }
.vr-value.large { font-size: 1.1rem; font-weight: 700; color: var(--ps-green); }

/* Photo gallery */
.site-photo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
    margin-top: 8px;
}
.site-photo-item {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    aspect-ratio: 4/3;
    border: 2px solid #d8e8d5;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}
.site-photo-item:hover {
    transform: scale(1.02);
    box-shadow: 0 6px 20px rgba(0,0,0,0.18);
}
.site-photo-item img {
    width: 100%; height: 100%;
    object-fit: cover; display: block;
}
.site-photo-num {
    position: absolute; bottom: 0; left: 0; right: 0;
    background: rgba(0,0,0,0.5);
    color: #fff; font-size: 0.7rem;
    text-align: center; padding: 4px 0;
}

/* Lightbox */
.lightbox-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.88);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}
.lightbox-overlay.active { display: flex; }
.lightbox-img {
    max-width: 90vw;
    max-height: 80vh;
    border-radius: 10px;
    object-fit: contain;
    box-shadow: 0 20px 60px rgba(0,0,0,0.6);
}
.lightbox-controls {
    display: flex; align-items: center; gap: 20px;
    margin-top: 16px;
}
.lightbox-btn {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    color: #fff; border-radius: 8px;
    padding: 8px 18px; cursor: pointer;
    font-size: 0.85rem;
    transition: background 0.2s;
}
.lightbox-btn:hover { background: rgba(255,255,255,0.28); }
.lightbox-close {
    position: absolute; top: 18px; right: 24px;
    background: rgba(255,255,255,0.15); border: none;
    color: #fff; font-size: 1.4rem; cursor: pointer;
    border-radius: 50%; width: 40px; height: 40px;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s;
}
.lightbox-close:hover { background: #dc3545; }
.lightbox-counter {
    color: rgba(255,255,255,0.7);
    font-size: 0.85rem; min-width: 60px; text-align: center;
}

/* Print */
@media print {
    .ps-sidebar, .ps-topbar, .btn, .lightbox-overlay { display: none !important; }
    .vr-section { page-break-inside: avoid; }
    .site-photo-grid { grid-template-columns: repeat(3, 1fr); }
}
</style>

<!-- Page header -->
<div class="ps-page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h2>Validation Report</h2>
        <p>Site validation details for <strong><?= htmlspecialchars($request['request_number']) ?></strong></p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i>Print
        </button>
        <a href="index.php?action=prepare_slip" class="btn btn-ps-primary btn-sm">
            <i class="bi bi-file-earmark-text me-1"></i>Prepare Slip
        </a>
        <a href="index.php?action=site_validation" class="btn btn-outline-secondary btn-sm">← Back</a>
    </div>
</div>

<!-- Request summary banner -->
<div class="vr-section" style="background:var(--ps-green-pale); border-color:#b7dfb7;">
    <div class="row g-3">
        <div class="col-md-3">
            <div class="vr-label">Request Number</div>
            <div class="vr-value large"><?= htmlspecialchars($request['request_number']) ?></div>
        </div>
        <div class="col-md-3">
            <div class="vr-label">Activity</div>
            <div class="vr-value"><?= htmlspecialchars($request['activity_name']) ?></div>
        </div>
        <div class="col-md-3">
            <div class="vr-label">Organizer</div>
            <div class="vr-value"><?= htmlspecialchars($request['firstname'] . ' ' . $request['lastname']) ?></div>
        </div>
        <div class="col-md-3">
            <div class="vr-label">Request Status</div>
            <div class="mt-1"><span class="ps-badge ps-badge-<?= $request['status'] ?>"><?= str_replace('_',' ', $request['status']) ?></span></div>
        </div>
    </div>
</div>

<!-- Site Info -->
<div class="vr-section">
    <div class="vr-section-title"><i class="bi bi-geo-alt-fill"></i>Site Information</div>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="vr-label">Site Location</div>
            <div class="vr-value"><?= htmlspecialchars($report['site_location']) ?></div>
            <?php if (!empty($report['site_lat']) && !empty($report['site_lng'])): ?>
            <div class="small text-muted mt-1">
                <i class="bi bi-geo-alt-fill text-success me-1"></i>
                <?= number_format($report['site_lat'], 6) ?>, <?= number_format($report['site_lng'], 6) ?>
                <a href="https://www.google.com/maps?q=<?= $report['site_lat'] ?>,<?= $report['site_lng'] ?>"
                   target="_blank" class="ms-2 text-ps-green text-decoration-none small">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Open in Google Maps
                </a>
            </div>
            <?php endif; ?>
        </div>
        <div class="col-md-3">
            <div class="vr-label">Site Area</div>
            <div class="vr-value"><?= number_format($report['site_area'], 2) ?> ha</div>
        </div>
        <div class="col-md-3">
            <div class="vr-label">Validation Date</div>
            <div class="vr-value"><?= date('F d, Y', strtotime($report['validation_date'])) ?></div>
        </div>
        <div class="col-md-3">
            <div class="vr-label">Scheduled Planting Date</div>
            <div class="vr-value"><?= date('F d, Y', strtotime($report['schedule_date'])) ?></div>
        </div>
        <div class="col-md-3">
            <div class="vr-label">Soil Condition</div>
            <div class="vr-value"><?= htmlspecialchars($report['soil_condition']) ?></div>
        </div>
        <div class="col-md-3">
            <div class="vr-label">Accessibility</div>
            <div class="vr-value"><?= htmlspecialchars($report['accessibility']) ?></div>
        </div>
        <div class="col-md-3">
            <div class="vr-label">Recommended Species</div>
            <div class="vr-value"><?= htmlspecialchars($report['recommended_species']) ?></div>
        </div>
    </div>

    <?php if (!empty($report['site_lat']) && !empty($report['site_lng'])): ?>
    <!-- Embedded map showing pinned location -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <div id="reportMap" style="height:280px; border-radius:10px; border:1.5px solid #d8e8d5; overflow:hidden;"></div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const lat = <?= floatval($report['site_lat']) ?>;
        const lng = <?= floatval($report['site_lng']) ?>;
        const rmap = L.map('reportMap', { zoomControl: true, scrollWheelZoom: false })
                      .setView([lat, lng], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(rmap);
        const greenIcon = L.divIcon({
            className: '',
            html: '<div style="background:#2d5a27;width:20px;height:20px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.5);"></div>',
            iconSize: [20, 20], iconAnchor: [10, 20],
        });
        L.marker([lat, lng], { icon: greenIcon })
         .addTo(rmap)
         .bindPopup('<b><?= htmlspecialchars(addslashes($report['site_location'])) ?></b><br>Planting Site')
         .openPopup();
    });
    </script>
    <?php endif; ?>
</div>

<!-- Seed Pack Count -->
<div class="vr-section">
    <div class="vr-section-title"><i class="bi bi-box-seam-fill"></i>Seed Pack Count</div>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="p-3 rounded text-center" style="background:#e8f5e2; border:1px solid #b7dfb7;">
                <div class="vr-label">Seed Packs Counted</div>
                <div style="font-size:1.6rem; font-weight:800; color:var(--ps-green);">
                    <?= number_format($report['seed_packs_counted']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 rounded text-center" style="background:#e8f5e2; border:1px solid #b7dfb7;">
                <div class="vr-label">Available Seedlings</div>
                <div style="font-size:1.6rem; font-weight:800; color:var(--ps-green);">
                    <?= number_format($report['available_seedlings']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 rounded text-center" style="background:#e8f5e2; border:1px solid #b7dfb7;">
                <div class="vr-label">Quantity Requested</div>
                <div style="font-size:1.6rem; font-weight:800; color:var(--ps-green);">
                    <?= number_format($request['quantity_requested']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Findings & Recommendation -->
<div class="vr-section">
    <div class="vr-section-title"><i class="bi bi-clipboard2-text-fill"></i>Findings & Recommendation</div>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="vr-label mb-2">Site Findings</div>
            <div class="p-3 rounded small" style="background:#f8faf8; border:1px solid #d8e8d5; line-height:1.7;">
                <?= nl2br(htmlspecialchars($report['findings'])) ?>
            </div>
        </div>
        <div class="col-md-6">
            <div class="vr-label mb-2">Recommendation</div>
            <div class="p-3 rounded small" style="background:#f8faf8; border:1px solid #d8e8d5; line-height:1.7;">
                <?= nl2br(htmlspecialchars($report['recommendation'])) ?>
            </div>
        </div>
    </div>
</div>

<!-- Site Photos -->
<div class="vr-section">
    <div class="vr-section-title"><i class="bi bi-camera-fill"></i>Site Photos
        <?php if (!empty($photos)): ?>
            <span class="ms-auto text-muted fw-normal" style="font-size:0.75rem; letter-spacing:0; text-transform:none;">
                <?= count($photos) ?> photo(s) uploaded
            </span>
        <?php endif; ?>
    </div>

    <?php if (empty($photos)): ?>
        <div class="empty-state py-3">
            <i class="bi bi-image" style="font-size:2rem;opacity:0.2;"></i>
            <p class="mt-2 small">No site photos were uploaded for this validation.</p>
        </div>
    <?php else: ?>
        <div class="site-photo-grid" id="photoGrid">
            <?php foreach ($photos as $idx => $path): ?>
            <div class="site-photo-item" onclick="openLightbox(<?= $idx ?>)">
                <img src="<?= htmlspecialchars($path) ?>"
                     alt="Site photo <?= $idx + 1 ?>"
                     loading="lazy">
                <div class="site-photo-num">Photo <?= $idx + 1 ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="small text-muted mt-2">
            <i class="bi bi-info-circle me-1"></i>Click any photo to view full size.
        </p>
    <?php endif; ?>
</div>

<!-- Validated by -->
<div class="vr-section">
    <div class="vr-section-title"><i class="bi bi-person-check-fill"></i>Validated By</div>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="vr-label">Agricultural Technologist</div>
            <div class="vr-value"><?= htmlspecialchars($report['firstname'] . ' ' . $report['lastname']) ?></div>
        </div>
        <div class="col-md-4">
            <div class="vr-label">Validation Date</div>
            <div class="vr-value"><?= date('F d, Y', strtotime($report['validation_date'])) ?></div>
        </div>
        <div class="col-md-4">
            <div class="vr-label">Report Created</div>
            <div class="vr-value"><?= date('F d, Y g:i A', strtotime($report['created_at'])) ?></div>
        </div>
    </div>
</div>

<!-- Lightbox -->
<div class="lightbox-overlay" id="lightbox">
    <button class="lightbox-close" onclick="closeLightbox()">
        <i class="bi bi-x-lg"></i>
    </button>
    <img class="lightbox-img" id="lightboxImg" src="" alt="Site photo">
    <div class="lightbox-controls">
        <button class="lightbox-btn" onclick="prevPhoto()">
            <i class="bi bi-chevron-left me-1"></i> Prev
        </button>
        <span class="lightbox-counter" id="lightboxCounter">1 / 1</span>
        <button class="lightbox-btn" onclick="nextPhoto()">
            Next <i class="bi bi-chevron-right ms-1"></i>
        </button>
    </div>
</div>

<script>
const photos = <?= json_encode($photos) ?>;
let currentPhoto = 0;

function openLightbox(index) {
    currentPhoto = index;
    updateLightbox();
    document.getElementById('lightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = '';
}

function updateLightbox() {
    document.getElementById('lightboxImg').src = photos[currentPhoto];
    document.getElementById('lightboxCounter').textContent =
        (currentPhoto + 1) + ' / ' + photos.length;
}

function prevPhoto() {
    currentPhoto = (currentPhoto - 1 + photos.length) % photos.length;
    updateLightbox();
}

function nextPhoto() {
    currentPhoto = (currentPhoto + 1) % photos.length;
    updateLightbox();
}

// Keyboard navigation
document.addEventListener('keydown', e => {
    if (!document.getElementById('lightbox').classList.contains('active')) return;
    if (e.key === 'ArrowLeft')  prevPhoto();
    if (e.key === 'ArrowRight') nextPhoto();
    if (e.key === 'Escape')     closeLightbox();
});

// Close on overlay click
document.getElementById('lightbox').addEventListener('click', function(e) {
    if (e.target === this) closeLightbox();
});
</script>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
