<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

require_once 'database/db.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/SessionGuard.php';
require_once 'models/User.php';
require_once 'models/SecurityLog.php';
require_once 'models/ServiceRequest.php';
require_once 'models/RsbsaRegistry.php';
require_once 'models/PlantingMaterial.php';
require_once 'models/ValidationReport.php';
require_once 'models/RequestSlip.php';
require_once 'models/Notification.php';
require_once 'models/BarangayApproval.php';
require_once 'models/SeedRelease.php';

$auth   = new AuthController($conn);
$secLog = new SecurityLog($conn);
$action = $_GET['action'] ?? 'login';
$error  = null;
$success = null;

if (isset($_SESSION['user'])) {
    SessionGuard::check();
}

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'registered') $success = "Registration successful! You can now log in.";
    if ($_GET['status'] === 'login_success') $success = "Welcome back!";
}
if (isset($_GET['expired'])) {
    $error = "Your session expired due to inactivity. Please log in again.";
}

function requireLogin() {
    if (!isset($_SESSION['user'])) {
        header('Location: index.php?action=login');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    $roles = (array)$roles;
    if (!in_array($_SESSION['user']['role'], $roles)) {
        header('Location: index.php?action=dashboard');
        exit;
    }
}

function logAct($conn, $action, $module, $desc) {
    if (!isset($_SESSION['user'])) return;
    (new SecurityLog($conn))->logActivity($_SESSION['user']['id'], $action, $module, $desc);
}

switch ($action) {

    case 'register':
        if (isset($_SESSION['user'])) { header('Location: index.php?action=dashboard'); exit; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $auth->register($_POST);
            if ($result['success']) {
                header('Location: index.php?action=login&status=registered');
                exit;
            }
            $error = $result['error'];
        }
        include 'views/register.php';
        break;

    case 'login':
        if (isset($_SESSION['user'])) { header('Location: index.php?action=dashboard'); exit; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $auth->login($_POST);
            if ($result['success']) {
                $_SESSION['user'] = $result['user'];
                $_SESSION['login_success'] = true; // flag for dashboard toast
                SessionGuard::init($result['user']['id']);
                header('Location: index.php?action=dashboard');
                exit;
            }
            $error = $result['error'];
        }
        include 'views/login.php';
        break;

    case 'logout':
        if (isset($_SESSION['user'])) {
            $auth->logout($_SESSION['user']['id'], $_SESSION['user']['email'] ?? '');
        }
        session_unset();
        session_destroy();
        header('Location: index.php?action=login');
        exit;

    case 'ping_session':
        require_once 'api/ping_session.php';
        exit;

    case 'log_dlp_block':
        require_once 'api/log_dlp_block.php';
        exit;

    case 'get_validation_id':
        require_once 'api/get_validation_id.php';
        exit;

    case 'get_request_details':
        require_once 'api/get_request_details.php';
        exit;

    case 'upload_request_letter':
        require_once 'api/upload_request_letter.php';
        exit;

    case 'submit_validation':
        require_once 'api/submit_validation.php';
        exit;

    case 'dashboard':
        requireLogin();
        $role = $_SESSION['user']['role'];
        logAct($conn, 'view_dashboard', $role, 'Viewed dashboard.');
        switch ($role) {
            case 'admin':                    include 'views/admin/dashboard.php'; break;
            case 'community_organizer':      include 'views/organizer/dashboard.php'; break;
            case 'community_affairs_worker': include 'views/affairs_worker/dashboard.php'; break;
            case 'agricultural_technologist':include 'views/technologist/dashboard.php'; break;
            case 'mao':                      include 'views/mao/dashboard.php'; break;
            case 'barangay_captain':         include 'views/barangay_captain/dashboard.php'; break;
            case 'department_head':          include 'views/department_head/dashboard.php'; break;
            case 'nursery':                  include 'views/nursery/dashboard.php'; break;
            default:                         include 'views/home.php';
        }
        break;

    case 'admin_users':
        requireRole('admin');
        logAct($conn, 'view_users', 'admin', 'Viewed user management.');
        include 'views/admin/users.php';
        break;

    case 'admin_login_logs':
        requireRole('admin');
        logAct($conn, 'view_login_logs', 'admin', 'Viewed login logs.');
        include 'views/admin/login_logs.php';
        break;

    case 'admin_activity_logs':
        requireRole('admin');
        logAct($conn, 'view_activity_logs', 'admin', 'Viewed activity logs.');
        include 'views/admin/activity_logs.php';
        break;

    case 'admin_export_logs':
        requireRole('admin');
        logAct($conn, 'view_export_logs', 'admin', 'Viewed export/DLP logs.');
        include 'views/admin/export_logs.php';
        break;

    case 'admin_sessions':
        requireRole('admin');
        logAct($conn, 'view_sessions', 'admin', 'Viewed user sessions.');
        include 'views/admin/sessions.php';
        break;

    case 'formal_request':
        requireRole('community_organizer');
        logAct($conn, 'view_formal_request', 'organizer', 'Viewed formal request form for ID: ' . intval($_GET['id'] ?? 0));
        include 'views/organizer/formal_request.php';
        break;

    case 'submit_request':
        requireLogin();
        logAct($conn, 'view_submit_request', 'organizer', 'Viewed submit request page.');
        include 'views/organizer/submit_request.php';
        break;

    case 'my_requests':
        requireLogin();
        logAct($conn, 'view_my_requests', 'organizer', 'Viewed my requests.');
        include 'views/organizer/my_requests.php';
        break;

    case 'rsbsa_form':
        requireLogin();
        logAct($conn, 'view_rsbsa_form', 'organizer', 'Viewed RSBSA form.');
        include 'views/organizer/rsbsa_form.php';
        break;

    case 'view_requests':
        requireRole('community_affairs_worker');
        logAct($conn, 'view_requests', 'affairs_worker', 'Viewed all requests.');
        include 'views/affairs_worker/view_requests.php';
        break;

    case 'seedling_info':
        requireRole('community_affairs_worker');
        logAct($conn, 'view_seedling_info', 'affairs_worker', 'Viewed seedling materials.');
        include 'views/affairs_worker/seedling_info.php';
        break;

    case 'refer_request':
        requireRole('community_affairs_worker');
        logAct($conn, 'view_refer_request', 'affairs_worker', 'Viewed refer request page for ID: ' . intval($_GET['id'] ?? 0));
        include 'views/affairs_worker/refer_request.php';
        break;

    case 'check_rsbsa':
        requireRole('agricultural_technologist');
        logAct($conn, 'view_check_rsbsa', 'technologist', 'Viewed RSBSA check.');
        include 'views/technologist/check_rsbsa.php';
        break;

    case 'verify_materials':
        requireRole('agricultural_technologist');
        logAct($conn, 'view_verify_materials', 'technologist', 'Viewed planting materials.');
        include 'views/technologist/verify_materials.php';
        break;

    case 'site_validation':
        requireRole('agricultural_technologist');
        logAct($conn, 'view_site_validation', 'technologist', 'Viewed site validation.');
        include 'views/technologist/site_validation.php';
        break;

    case 'validation_reports':
        requireRole(['agricultural_technologist', 'mao', 'department_head', 'admin']);
        logAct($conn, 'view_validation_reports', $_SESSION['user']['role'], 'Viewed all validation reports.');
        include 'views/technologist/validation_reports.php';
        break;

    case 'view_validation':
        requireRole(['agricultural_technologist', 'mao', 'department_head', 'admin']);
        logAct($conn, 'view_validation_report', $_SESSION['user']['role'], 'Viewed validation report for request ID: ' . intval($_GET['request_id'] ?? 0));
        include 'views/technologist/view_validation.php';
        break;

    case 'prepare_slip':
        requireRole('agricultural_technologist');
        logAct($conn, 'view_prepare_slip', 'technologist', 'Viewed prepare slip.');
        include 'views/technologist/prepare_slip.php';
        break;

    case 'review_slips':
        requireRole('mao');
        logAct($conn, 'view_review_slips', 'mao', 'Viewed review slips.');
        include 'views/mao/review_slips.php';
        break;

    case 'view_endorsement':
        requireRole(['mao', 'department_head', 'admin']);
        logAct($conn, 'view_endorsement', $_SESSION['user']['role'], 'Viewed endorsement letter for slip ID: ' . intval($_GET['slip_id'] ?? 0));
        include 'views/mao/endorsement_letter.php';
        break;

    case 'approve_slip':
        requireRole('mao');
        logAct($conn, 'view_approve_slip', 'mao', 'Viewed approve slip ID: ' . intval($_GET['id'] ?? 0));
        include 'views/mao/approve_slip.php';
        break;

    case 'route_request':
        requireRole('mao');
        logAct($conn, 'view_route_request', 'mao', 'Viewed routed requests.');
        include 'views/mao/route_request.php';
        break;

    // ── Barangay Captain Routes ───────────────────────────
    case 'captain_proposals':
        requireRole('barangay_captain');
        logAct($conn, 'view_proposals', 'barangay_captain', 'Viewed proposals for review.');
        include 'views/barangay_captain/proposals.php';
        break;

    case 'captain_history':
        requireRole('barangay_captain');
        logAct($conn, 'view_history', 'barangay_captain', 'Viewed approval history.');
        include 'views/barangay_captain/history.php';
        break;

    // ── Department Head Routes ────────────────────────────
    case 'depthead_finalize':
        requireRole('department_head');
        logAct($conn, 'view_finalize', 'department_head', 'Viewed finalization page.');
        include 'views/department_head/finalize.php';
        break;

    case 'depthead_history':
        requireRole('department_head');
        logAct($conn, 'view_history', 'department_head', 'Viewed finalization history.');
        include 'views/department_head/history.php';
        break;

    // ── Nursery Routes ────────────────────────────────────
    case 'nursery_release':
        requireRole('nursery');
        logAct($conn, 'view_release', 'nursery', 'Viewed seed pack release page.');
        include 'views/nursery/release.php';
        break;

    case 'nursery_guidance_log':
        requireRole('agricultural_technologist');
        logAct($conn, 'view_guidance_log', 'technologist', 'Viewed guidance log.');
        include 'views/technologist/guidance_log.php';
        break;

    case 'stakeholder_importance':
        requireRole('agricultural_technologist');
        logAct($conn, 'view_stakeholder_importance', 'technologist', 'Viewed stakeholder importance page.');
        include 'views/technologist/stakeholder_importance.php';
        break;

    case 'nursery_survival':
        requireRole('community_organizer');
        logAct($conn, 'view_survival', 'organizer', 'Viewed survival monitoring.');
        include 'views/organizer/survival_monitoring.php';
        break;

    case 'nursery_history':
        requireRole('nursery');
        logAct($conn, 'view_history', 'nursery', 'Viewed release history.');
        include 'views/nursery/history.php';
        break;

    case 'mark_notif_read':
        if (isset($_SESSION['user'])) {
            (new Notification($conn))->markAllRead($_SESSION['user']['id']);
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?action=dashboard'));
        exit;

    default:
        header('Location: index.php?action=login');
        exit;
}
?>
