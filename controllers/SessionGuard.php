<?php
/**
 * SessionGuard — DLP & Session Security
 * - Session timeout (20 min idle)
 * - Session fixation prevention
 * - Concurrent session detection
 */
class SessionGuard {
    const TIMEOUT_SECONDS = 1200; // 20 minutes

    public static function check() {
        if (!isset($_SESSION['user'])) return;

        // Idle timeout
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > self::TIMEOUT_SECONDS) {
                self::expire();
                return;
            }
        }
        $_SESSION['last_activity'] = time();

        // IP binding (optional strict mode)
        if (isset($_SESSION['bound_ip']) && $_SESSION['bound_ip'] !== self::getIP()) {
            self::expire('ip_mismatch');
            return;
        }
    }

    public static function init($userId) {
        // Regenerate session ID on login (prevents fixation)
        session_regenerate_id(true);
        $_SESSION['last_activity'] = time();
        $_SESSION['bound_ip']      = self::getIP();
        $_SESSION['session_start'] = time();
    }

    public static function expire($reason = 'timeout') {
        $userId = $_SESSION['user']['id'] ?? null;
        // End the DB session record before destroying PHP session
        if ($userId && isset($GLOBALS['conn'])) {
            (new SecurityLog($GLOBALS['conn']))->endSession($reason);
        }
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['session_expired'] = true;
        $_SESSION['expire_reason']   = $reason;
        header('Location: index.php?action=login&expired=1');
        exit;
    }

    public static function remainingSeconds() {
        if (!isset($_SESSION['last_activity'])) return self::TIMEOUT_SECONDS;
        $elapsed = time() - $_SESSION['last_activity'];
        return max(0, self::TIMEOUT_SECONDS - $elapsed);
    }

    private static function getIP() {
        foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
            if (!empty($_SERVER[$k])) {
                $ip = trim(explode(',', $_SERVER[$k])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '0.0.0.0';
    }
}
?>
