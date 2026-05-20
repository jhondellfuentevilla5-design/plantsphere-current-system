<?php
/**
 * API Bootstrap
 * Shared setup for all API endpoints — session, DB, models, helpers
 */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../controllers/SessionGuard.php';
require_once __DIR__ . '/../models/SecurityLog.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/ValidationReport.php';
require_once __DIR__ . '/../models/ServiceRequest.php';

// ── Always respond as JSON ────────────────────────────────
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// ── Helpers ───────────────────────────────────────────────

function apiRequireLogin() {
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Please log in.']);
        exit;
    }
}

function apiRequireRole($roles) {
    apiRequireLogin();
    $roles = (array)$roles;
    if (!in_array($_SESSION['user']['role'], $roles)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden. Insufficient permissions.']);
        exit;
    }
}

function apiSuccess($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function apiError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}
?>
