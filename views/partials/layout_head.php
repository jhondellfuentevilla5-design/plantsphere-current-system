<?php
$pageTitle = $pageTitle ?? 'Plant Sphere';
require_once __DIR__ . '/../../controllers/SessionGuard.php';
SessionGuard::check();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Plant Sphere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="ps-app">
<?php include __DIR__ . '/sidebar.php'; ?>
<!-- ══ MAIN WRAPPER ══ -->
<div class="ps-main">
    <!-- Top bar -->
    <header class="ps-topbar">
        <button class="ps-topbar-toggle d-lg-none" id="sidebarOpen" onclick="openSidebar()" aria-label="Open sidebar">
            <i class="bi bi-list"></i>
        </button>
        <div class="ps-topbar-title"><?= htmlspecialchars($pageTitle) ?></div>
        <div class="ps-topbar-right">
            <span class="small text-muted d-none d-md-inline">
                <?= date('l, F j, Y') ?>
            </span>
        </div>
    </header>
    <!-- Page content -->
    <div class="ps-content" style="position:relative;">
        <!-- ══ LOGIN SUCCESS TOAST ══ -->
        <?php if (!empty($_SESSION['login_success'])): 
            unset($_SESSION['login_success']);
            $fname = htmlspecialchars($_SESSION['user']['firstname'] ?? '');
            $roleLabels = [
                'admin'                     => 'System Administrator',
                'community_organizer'       => 'Community Organizer',
                'community_affairs_worker'  => 'Community Affairs Worker',
                'agricultural_technologist' => 'Agricultural Technologist',
                'mao'                       => 'Municipal Agriculture Office',
            ];
            $roleLabel = $roleLabels[$_SESSION['user']['role'] ?? ''] ?? 'User';
        ?>
        <div id="loginSuccessToast" class="login-toast" role="alert" aria-live="polite">
            <div class="login-toast-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="login-toast-body">
                <div class="login-toast-title">Login Successful!</div>
                <div class="login-toast-msg">
                    Welcome back, <strong><?= $fname ?></strong>. You are logged in as <strong><?= $roleLabel ?></strong>.
                </div>
            </div>
            <button class="login-toast-close" onclick="dismissToast()" aria-label="Dismiss">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <style>
        .login-toast {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: #fff;
            border: 1.5px solid #b7dfb7;
            border-left: 5px solid #2d5a27;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(45,90,39,0.12);
            animation: toastSlideIn 0.4s cubic-bezier(0.16,1,0.3,1);
        }
        @keyframes toastSlideIn {
            from { opacity: 0; transform: translateY(-16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .login-toast.hiding {
            animation: toastSlideOut 0.3s ease forwards;
        }
        @keyframes toastSlideOut {
            to { opacity: 0; transform: translateY(-10px); max-height: 0; padding: 0; margin: 0; border: none; }
        }
        .login-toast-icon {
            font-size: 1.4rem;
            color: #2d5a27;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .login-toast-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #1a2e1a;
            margin-bottom: 2px;
        }
        .login-toast-msg {
            font-size: 0.82rem;
            color: #4a7c44;
        }
        .login-toast-close {
            background: none;
            border: none;
            color: #adb5bd;
            cursor: pointer;
            font-size: 0.85rem;
            margin-left: auto;
            padding: 2px 4px;
            flex-shrink: 0;
            transition: color 0.2s;
        }
        .login-toast-close:hover { color: #2d5a27; }
        </style>
        <script>
        function dismissToast() {
            const t = document.getElementById('loginSuccessToast');
            if (t) {
                t.classList.add('hiding');
                setTimeout(() => t.remove(), 320);
            }
        }
        // Auto-dismiss after 5 seconds
        setTimeout(dismissToast, 5000);
        </script>
        <?php endif; ?>
        <!-- ══ SKELETON OVERLAY ══ -->
        <div id="ps-skeleton-overlay" aria-hidden="true">

            <!-- Page header -->
            <div class="sk-page-header">
                <div class="sk sk-page-title"></div>
                <div class="sk sk-page-sub"></div>
            </div>

            <!-- Stat cards row -->
            <div class="row g-3 mb-4">
                <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="col-6 col-xl-3">
                    <div class="sk-stat-card">
                        <div class="sk sk-stat-value"></div>
                        <div class="sk sk-stat-label"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <!-- Main content row -->
            <div class="row g-4">
                <!-- Left card -->
                <div class="col-md-4">
                    <div class="sk-card">
                        <div class="sk sk-text w-40 mb-3" style="height:16px;"></div>
                        <div class="sk sk-btn mb-2"></div>
                        <div class="sk sk-btn mb-2"></div>
                        <div class="sk sk-btn mb-4"></div>
                        <div class="sk sk-text w-40 mb-2" style="height:14px;"></div>
                        <div class="sk sk-text w-75"></div>
                        <div class="sk sk-text w-60"></div>
                    </div>
                </div>
                <!-- Right card with table -->
                <div class="col-md-8">
                    <div class="sk-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="sk sk-text w-40" style="height:16px;"></div>
                            <div class="sk sk-text" style="width:60px;height:12px;"></div>
                        </div>
                        <div class="sk sk-table-head"></div>
                        <?php for ($i = 0; $i < 5; $i++): ?>
                        <div class="sk sk-table-row"></div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- Bottom card with steps -->
            <div class="sk-card mt-4">
                <div class="sk sk-text w-40 mb-4" style="height:16px;"></div>
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <?php for ($i = 0; $i < 7; $i++): ?>
                    <div class="d-flex flex-column align-items-center flex-fill">
                        <div class="sk sk-step-circle"></div>
                        <div class="sk sk-step-label"></div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

        </div><!-- /#ps-skeleton-overlay -->

        <!-- Session warning banner -->
        <div id="sessionWarning" class="d-none alert alert-warning py-2 small mb-3 d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-clock-history"></i>
            <span>Your session will expire in <strong id="sessionCountdown">2:00</strong>. Move your mouse to stay logged in.</span>
        </div>
        <!-- Session progress bar -->
        <div style="position:fixed;top:0;left:0;right:0;height:3px;background:#e9ecef;z-index:9999;">
            <div id="sessionBar" style="height:100%;background:#2d5a27;transition:width 1s linear;width:100%;"></div>
        </div>
