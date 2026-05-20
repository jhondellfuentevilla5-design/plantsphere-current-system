<?php
/**
 * API: submit_validation
 * Submits a site validation report for a service request.
 * Moves the request status from 'for_validation' to 'validated'.
 *
 * Method: POST (application/json or form-data)
 * Auth:   Requires login (agricultural_technologist)
 * Body:   All validation report fields
 * Returns: { success: true, validation_id: int, request_number: string }
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../models/Notification.php';

apiRequireRole('agricultural_technologist');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Method not allowed. Use POST.', 405);
}

// Accept JSON or form-data
$input = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
} else {
    $input = $_POST;
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
    'message'        => 'Validation report submitted. Request moved to validated status.',
]);
?>
