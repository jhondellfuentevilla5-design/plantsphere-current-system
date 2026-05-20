<?php
/**
 * API: log_dlp_block
 * Logs a Data Loss Prevention violation when a user attempts
 * a blocked action (Ctrl+P, F12, PrintScreen, etc.)
 *
 * Method: GET
 * Auth:   Requires active session
 * Params: ?what=<key_name>
 * Returns: { success: true }
 */

require_once __DIR__ . '/bootstrap.php';

apiRequireLogin();

$what   = htmlspecialchars(strip_tags($_GET['what'] ?? 'unknown'));
$secLog = new SecurityLog($conn);
$secLog->logExport($_SESSION['user']['id'], "DLP_BLOCK:$what", true);

apiSuccess(['logged' => true, 'action' => $what]);
?>
