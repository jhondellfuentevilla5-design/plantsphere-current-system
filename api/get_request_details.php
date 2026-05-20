<?php
/**
 * API: get_request_details
 * Returns full details of a service request for the site validation form.
 * Allows the technologist to load request info dynamically without page reload.
 *
 * Method: GET
 * Auth:   Requires login (agricultural_technologist)
 * Params: ?request_id=<int>
 * Returns: { success: true, request: { ... } }
 */

require_once __DIR__ . '/bootstrap.php';

apiRequireRole('agricultural_technologist');

$requestId = intval($_GET['request_id'] ?? 0);

if ($requestId <= 0) {
    apiError('Invalid request_id.');
}

$srModel = new ServiceRequest($conn);
$request = $srModel->getById($requestId);

if (!$request) {
    apiError('Request not found.', 404);
}

// Only return safe fields — never expose internal IDs or sensitive data unnecessarily
apiSuccess([
    'request' => [
        'id'                   => (int)$request['id'],
        'request_number'       => $request['request_number'],
        'activity_name'        => $request['activity_name'],
        'target_location'      => $request['target_location'],
        'target_date'          => $request['target_date'],
        'seedling_type'        => $request['seedling_type'],
        'quantity_requested'   => (int)$request['quantity_requested'],
        'number_of_participants' => (int)$request['number_of_participants'],
        'organizer'            => $request['firstname'] . ' ' . $request['lastname'],
        'status'               => $request['status'],
    ]
]);
?>
