<?php
/**
 * API: get_validation_id
 * Returns the validation report ID linked to a service request.
 * Used in prepare_slip.php when technologist selects a validated request.
 *
 * Method: GET
 * Auth:   Requires login (agricultural_technologist)
 * Params: ?request_id=<int>
 * Returns: { success: true, validation_id: int|null }
 */

require_once __DIR__ . '/bootstrap.php';

apiRequireRole('agricultural_technologist');

$requestId = intval($_GET['request_id'] ?? 0);

if ($requestId <= 0) {
    apiError('Invalid request_id.');
}

$vrModel = new ValidationReport($conn);
$vr      = $vrModel->getByRequest($requestId);

apiSuccess(['validation_id' => $vr ? (int)$vr['id'] : null]);
?>
