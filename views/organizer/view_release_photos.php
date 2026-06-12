<?php
$pageTitle = 'Seedling Release Photos';
include __DIR__ . '/../partials/layout_head.php';

$requestId = intval($_GET['request_id'] ?? 0);
$srModel   = new ServiceRequest($conn);
$request   = $srModel->getById($requestId);

// Security: organizer can only view their own
if (!$request || $request['user_id'] != $_SESSION['user']['id']) {
    echo '<div class="alert alert-danger">Not found.</div>';
    include __DIR__ . '/../partials/layout_foot.php';
    exit;
}

// Get release record
$stmt = $conn->prepare("
    SELECT sr2.*, u.firstname, u.lastname
    FROM seed_releases sr2
    JOIN users u ON sr2.released_by = u.id
    WHERE sr2.request_id = ?
    ORDER BY sr2.created_at DESC LIMIT 1
");
$stmt->execute([$requestId]);
$release = $stmt->fetch();

$photos = [];
if ($release && !empty($release['release_photos'])) {
    $decoded = json_decode($release['release_photos'], true);
    if (is_array($decoded)) $photos = $decoded;
}
?>

<style>
.release-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 14px;
    margin-top: 12px;
}
.release-gallery-item {
    border-radius: 12px;
    overflow: hidden;
    aspect-ratio: 4/3;
    border: 2px solid #d8e8d5;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}
.release-gallery-item:hover {
    transform: scale(1.02);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}
.release-gallery-item img { width:100%; height:100%; object-fit:cover; display:block; }

/* Lightbox */
.lb-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.88); z-index: 9999;
    align-items: center; justify-content: center; flex-direction: column;
}
.lb-overlay.active { display: flex; }
.lb-img { max-width: 90vw; max-height: 80vh; border-radius: 10px; object-fit: contain; }
.lb-controls { display: flex; align-items: center; gap: 20px; margin-top: 14px; }
.lb-btn {
    background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3);
    color: #fff; border-radius: 8px; padding: 7px 16px; cursor: pointer; font-size: 0.85rem;
}
.lb-btn:hover { background: rgba(255,255,255,0.28); }
.lb-close {
    position: absolute; top: 16px; right: 22px;
    background: rgba(255,255,255,0.15); border: none; color: #fff;
    font-size: 1.4rem; cursor: pointer; border-radius: 50%;
    width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;
}
.lb-close:hover { background: #dc3545; }
.lb-counter { color: rgba(255,255,255,0.7); font-size: 0.85rem; min-width: 60px; text-align: center; }
</style>

<div class="ps-page-header">
    <h2>Seedling Release Photos</h2>
    <p>Photos uploaded by the Nursery staff showing your seedlings ready for release.</p>
</div>

<!-- Release info banner -->
<?php if ($release): ?>
<div class="ps-card mb-4" style="background:#f0faf0; border:1px solid #b7dfb7;">
    <div class="row g-3 small">
        <div class="col-md-3">
            <div class="text-muted">Request #</div>
            <div class="fw-bold text-ps-green"><?= htmlspecialchars($request['request_number']) ?></div>
        </div>
        <div class="col-md-3">
            <div class="text-muted">Activity</div>
            <div><?= htmlspecialchars($request['activity_name']) ?></div>
        </div>
        <div class="col-md-3">
            <div class="text-muted">Quantity Released</div>
            <div class="fw-bold text-ps-green"><?= number_format($release['quantity_released']) ?> packs</div>
        </div>
        <div class="col-md-3">
            <div class="text-muted">Released By</div>
            <div><?= htmlspecialchars($release['firstname'] . ' ' . $release['lastname']) ?></div>
        </div>
        <div class="col-md-3">
            <div class="text-muted">Release Date</div>
            <div><?= date('F d, Y', strtotime($release['release_date'])) ?></div>
        </div>
        <div class="col-md-3">
            <div class="text-muted">Recipient</div>
            <div><?= htmlspecialchars($release['recipient_name']) ?></div>
        </div>
        <?php if ($release['remarks']): ?>
        <div class="col-md-6">
            <div class="text-muted">Remarks</div>
            <div><?= htmlspecialchars($release['remarks']) ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Photos -->
<div class="ps-card">
    <h6 class="fw-bold text-ps-green mb-1">
        <i class="bi bi-images me-2"></i>Seedling Photos
        <?php if (!empty($photos)): ?>
            <span class="text-muted fw-normal small ms-1"><?= count($photos) ?> photo(s)</span>
        <?php endif; ?>
    </h6>
    <p class="small text-muted mb-3">These photos show the condition and readiness of your seedlings before release.</p>

    <?php if (empty($photos)): ?>
        <div class="empty-state py-3">
            <i class="bi bi-camera-slash" style="font-size:2rem;opacity:0.2;"></i>
            <p class="mt-2 small">No photos were uploaded for this release.</p>
        </div>
    <?php else: ?>
        <div class="release-gallery">
            <?php foreach ($photos as $idx => $path): ?>
            <div class="release-gallery-item" onclick="openLb(<?= $idx ?>)">
                <img src="<?= htmlspecialchars($path) ?>"
                     alt="Seedling photo <?= $idx + 1 ?>"
                     loading="lazy">
            </div>
            <?php endforeach; ?>
        </div>
        <p class="small text-muted mt-2">
            <i class="bi bi-info-circle me-1"></i>Click any photo to view full size.
        </p>
    <?php endif; ?>
</div>

<div class="mt-3">
    <a href="index.php?action=my_requests" class="btn btn-outline-secondary btn-sm">← Back to My Proposals</a>
</div>

<!-- Lightbox -->
<div class="lb-overlay" id="lb">
    <button class="lb-close" onclick="closeLb()"><i class="bi bi-x-lg"></i></button>
    <img class="lb-img" id="lbImg" src="" alt="">
    <div class="lb-controls">
        <button class="lb-btn" onclick="prevLb()"><i class="bi bi-chevron-left me-1"></i>Prev</button>
        <span class="lb-counter" id="lbCounter"></span>
        <button class="lb-btn" onclick="nextLb()">Next <i class="bi bi-chevron-right ms-1"></i></button>
    </div>
</div>

<script>
const photos = <?= json_encode($photos) ?>;
let cur = 0;
function openLb(i) { cur = i; updateLb(); document.getElementById('lb').classList.add('active'); document.body.style.overflow = 'hidden'; }
function closeLb() { document.getElementById('lb').classList.remove('active'); document.body.style.overflow = ''; }
function updateLb() {
    document.getElementById('lbImg').src = photos[cur];
    document.getElementById('lbCounter').textContent = (cur + 1) + ' / ' + photos.length;
}
function prevLb() { cur = (cur - 1 + photos.length) % photos.length; updateLb(); }
function nextLb() { cur = (cur + 1) % photos.length; updateLb(); }
document.addEventListener('keydown', e => {
    if (!document.getElementById('lb').classList.contains('active')) return;
    if (e.key === 'ArrowLeft') prevLb();
    if (e.key === 'ArrowRight') nextLb();
    if (e.key === 'Escape') closeLb();
});
document.getElementById('lb').addEventListener('click', function(e) { if (e.target === this) closeLb(); });
</script>

<?php include __DIR__ . '/../partials/layout_foot.php'; ?>
