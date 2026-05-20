<?php
/**
 * API: ping_session
 * Resets the idle session timer to keep the user logged in.
 * Called every 2 seconds on user activity from layout_foot.php
 *
 * Method: GET
 * Auth:   Requires active session
 * Returns: { success: true, remaining: int }
 */

require_once __DIR__ . '/bootstrap.php';

if (!isset($_SESSION['user'])) {
    apiSuccess(['ok' => true, 'remaining' => 0]);
}

// Reset idle timer
$_SESSION['last_activity'] = time();

// Touch the DB session record
$secLog = new SecurityLog($conn);
$secLog->touchSession();

apiSuccess([
    'ok'        => true,
    'remaining' => SessionGuard::remainingSeconds(),
]);
?>
