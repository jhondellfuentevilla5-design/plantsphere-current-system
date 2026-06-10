<?php
/**
 * API: submit_validation
 * Submits a site validation report + optional site photos.
 * Moves the request status from 'for_validation' to 'validated'.
 *
 * Method: POST (multipart/form-data)
 * Auth:   Requires login (agricultural_technologist)
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../models/Notification.php';

apiRequireRole('agricultural_technologist');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Method not allowed. Use POST.', 405);
}

// Accept form-data (for file uploads) or JSON
$input = $_POST;
if (empty($input)) {
    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
}

// ── Validate required fields ──────────────────────────────
$required = [
    'request_id', 'site_location', 'site_area', 'validation_date',
    'schedule_date', 'soil_condition', 'accessibility',
    'recommended_species', 'seed_packs_counted', 'available_seedlings',
    'findings', 'recommendation'
];

foreach ($required as $field) {
    if (empty(trim((string)($input[$field] ?? '')))) {
        apiError("Missing required field: $field");
    }
}

$requestId = intval($input['request_id']);

// ── Check request exists and is in correct status ─────────
$srModel = new ServiceRequest($conn);
$request = $srModel->getById($requestId);

if (!$request) {
    apiError('Service request not found.', 404);
}
if ($request['status'] !== 'for_validation') {
    apiError('Request is not in for_validation status. Current status: ' . $request['status']);
}

// ── Handle site photo uploads ─────────────────────────────
$savedPhotos  = [];
$photoErrors  = [];
$uploadDir    = __DIR__ . '/../uploads/site_photos/';

if (!empty($_FILES['site_photos']['name'][0])) {
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize      = 5 * 1024 * 1024; // 5 MB
    $maxPhotos    = 5;
    $files        = $_FILES['site_photos'];
    $count        = count($files['name']);

    if ($count > $maxPhotos) {
        apiError("Maximum $maxPhotos photos allowed.");
    }

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        if ($files['size'][$i] > $maxSize) {
            $photoErrors[] = $files['name'][$i] . ' exceeds 5 MB limit.';
            continue;
        }

        // Verify MIME via finfo
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($files['tmp_name'][$i]);
        if (!in_array($realMime, $allowedMimes)) {
            $photoErrors[] = $files['name'][$i] . ' is not a valid image.';
            continue;
        }

        // Generate safe filename
        $ext      = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
        $safeName = time() . '_' . $requestId . '_' . $i . '_site.' . strtolower($ext);
        $dest     = $uploadDir . $safeName;

        if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
            $savedPhotos[] = 'uploads/site_photos/' . $safeName;
        } else {
            $photoErrors[] = 'Failed to save ' . $files['name'][$i];
        }
    }
}

$photosJson = !empty($savedPhotos) ? json_encode($savedPhotos) : null;

// ── Create validation report ──────────────────────────────
$vrModel = new ValidationReport($conn);
$data = [
    'request_id'          => $requestId,
    'technologist_id'     => $_SESSION['user']['id'],
    'site_location'       => trim($input['site_location']),
    'site_area'           => floatval($input['site_area']),
    'validation_date'     => $input['validation_date'],
    'schedule_date'       => $input['schedule_date'],
    'soil_condition'      => trim($input['soil_condition']),
    'accessibility'       => trim($input['accessibility']),
    'recommended_species' => trim($input['recommended_species']),
    'seed_packs_counted'  => intval($input['seed_packs_counted']),
    'available_seedlings' => intval($input['available_seedlings']),
    'findings'            => trim($input['findings']),
    'recommendation'      => trim($input['recommendation']),
    'site_photos'         => $photosJson,
    'site_lat'            => !empty($input['site_lat']) ? floatval($input['site_lat']) : null,
    'site_lng'            => !empty($input['site_lng']) ? floatval($input['site_lng']) : null,
];

$vrId = $vrModel->create($data);

if (!$vrId) {
    apiError('Failed to save validation report.', 500);
}

// ── Update request status ─────────────────────────────────
$srModel->updateStatus($requestId, 'validated', 'Site validation completed.');

// ── Send notifications ────────────────────────────────────
$notifModel = new Notification($conn);
$notifModel->create(
    $request['user_id'],
    'Site Validation Complete',
    'Site validation for your request ' . $request['request_number'] . ' has been completed. A request slip is being prepared.'
);
$notifModel->create(
    $_SESSION['user']['id'],
    'Prepare Request Slip',
    'Site validation done for ' . $request['request_number'] . '. Please prepare the request slip.'
);

// ── Log activity ──────────────────────────────────────────
$secLog = new SecurityLog($conn);
$secLog->logActivity(
    $_SESSION['user']['id'],
    'submit_validation',
    'technologist',
    'Submitted site validation report for request: ' . $request['request_number']
);

apiSuccess([
    'validation_id'  => (int)$vrId,
    'request_number' => $request['request_number'],
    'photos_saved'   => count($savedPhotos),
    'photo_errors'   => $photoErrors,
    'message'        => 'Validation report submitted. Request moved to validated status.',
]);
?>
