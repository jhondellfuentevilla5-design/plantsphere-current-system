<?php
/**
 * SecurityLog Model
 * Handles login logs, activity logs, session tracking, export attempts
 */
class SecurityLog {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ── Login Logs ──────────────────────────────────────────

    public function logLogin($userId, $email, $status, $reason = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO login_logs (user_id, email, ip_address, user_agent, status, reason)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $userId, $email, $this->getIP(), $this->getUA(), $status, $reason
        ]);
    }

    public function logLogout($userId, $email) {
        $stmt = $this->conn->prepare("
            INSERT INTO login_logs (user_id, email, ip_address, user_agent, status, reason)
            VALUES (?, ?, ?, ?, 'logout', 'User logged out')
        ");
        return $stmt->execute([$userId, $email, $this->getIP(), $this->getUA()]);
    }

    public function getLoginLogs($limit = 100, $userId = null) {
        $limit = (int)$limit;
        if ($userId) {
            $stmt = $this->conn->prepare("
                SELECT ll.*, u.firstname, u.lastname
                FROM login_logs ll
                LEFT JOIN users u ON ll.user_id = u.id
                WHERE ll.user_id = ?
                ORDER BY ll.created_at DESC LIMIT $limit
            ");
            $stmt->execute([(int)$userId]);
        } else {
            $stmt = $this->conn->prepare("
                SELECT ll.*, u.firstname, u.lastname
                FROM login_logs ll
                LEFT JOIN users u ON ll.user_id = u.id
                ORDER BY ll.created_at DESC LIMIT $limit
            ");
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }

    public function countFailedRecent($email, $minutes = 15) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as cnt FROM login_logs
            WHERE email = ? AND status = 'failed'
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ");
        $stmt->execute([$email, $minutes]);
        return (int)$stmt->fetch()['cnt'];
    }

    // ── Activity Logs ────────────────────────────────────────

    public function logActivity($userId, $action, $module, $description) {
        $stmt = $this->conn->prepare("
            INSERT INTO activity_logs (user_id, action, module, description, ip_address)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $action, $module, $description, $this->getIP()]);
    }

    public function getActivityLogs($limit = 200, $userId = null, $module = null) {
        $where  = [];
        $params = [];

        if ($userId) { $where[] = 'al.user_id = ?'; $params[] = (int)$userId; }
        if ($module)  { $where[] = 'al.module = ?';  $params[] = $module; }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT al.*, u.firstname, u.lastname, u.role
            FROM activity_logs al
            JOIN users u ON al.user_id = u.id
            $whereSQL
            ORDER BY al.created_at DESC
            LIMIT " . (int)$limit;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Export Attempts ──────────────────────────────────────

    public function logExport($userId, $action, $blocked = false) {
        $stmt = $this->conn->prepare("
            INSERT INTO export_attempts (user_id, action, blocked, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $action, $blocked ? 1 : 0, $this->getIP()]);
    }

    public function getExportLogs($limit = 100) {
        $limit = (int)$limit;
        $stmt  = $this->conn->prepare("
            SELECT ea.*, u.firstname, u.lastname, u.role
            FROM export_attempts ea
            JOIN users u ON ea.user_id = u.id
            ORDER BY ea.created_at DESC LIMIT $limit
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ── Stats ────────────────────────────────────────────────

    public function getStats() {
        $stats = [];
        $q = function($sql) { return $this->conn->query($sql)->fetch()['c']; };

        $stats['failed_logins_24h']  = (int)$q("SELECT COUNT(*) as c FROM login_logs WHERE status='failed'  AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stats['success_logins_24h'] = (int)$q("SELECT COUNT(*) as c FROM login_logs WHERE status='success' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stats['locked_attempts']    = (int)$q("SELECT COUNT(*) as c FROM login_logs WHERE status='locked'");
        $stats['logouts_24h']        = (int)$q("SELECT COUNT(*) as c FROM login_logs WHERE status='logout'  AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stats['activities_24h']     = (int)$q("SELECT COUNT(*) as c FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stats['blocked_exports']    = (int)$q("SELECT COUNT(*) as c FROM export_attempts WHERE blocked=1");

        return $stats;
    }

    // ── User Sessions ────────────────────────────────────────

    /** Called on login — inserts a row into user_sessions */
    public function startSession($userId) {
        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + SessionGuard::TIMEOUT_SECONDS);

        $stmt = $this->conn->prepare("
            INSERT INTO user_sessions
                (user_id, session_token, ip_address, user_agent, last_activity, expires_at, is_active)
            VALUES (?, ?, ?, ?, NOW(), ?, 1)
        ");
        $stmt->execute([$userId, $token, $this->getIP(), $this->getUA(), $expiresAt]);

        $sessionId = (int)$this->conn->lastInsertId();
        $_SESSION['db_session_id']    = $sessionId;
        $_SESSION['db_session_token'] = $token;

        return $sessionId;
    }

    /** Called on logout or timeout — marks session inactive */
    public function endSession($reason = 'logout') {
        $sessionId = $_SESSION['db_session_id'] ?? null;
        if (!$sessionId) return;

        $stmt = $this->conn->prepare("
            UPDATE user_sessions
            SET is_active = 0, last_activity = NOW(), expires_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([(int)$sessionId]);
    }

    /** Keep last_activity fresh on every ping */
    public function touchSession() {
        $sessionId = $_SESSION['db_session_id'] ?? null;
        if (!$sessionId) return;

        $newExpiry = date('Y-m-d H:i:s', time() + SessionGuard::TIMEOUT_SECONDS);
        $stmt = $this->conn->prepare("
            UPDATE user_sessions
            SET last_activity = NOW(), expires_at = ?
            WHERE id = ? AND is_active = 1
        ");
        $stmt->execute([$newExpiry, (int)$sessionId]);
    }

    /** All sessions for admin view with calculated duration */
    public function getUserSessions($limit = 200) {
        $limit = (int)$limit;
        $stmt  = $this->conn->prepare("
            SELECT
                us.*,
                u.firstname, u.lastname, u.email, u.role,
                TIMESTAMPDIFF(SECOND, us.created_at,
                    IF(us.is_active = 1, NOW(), us.last_activity)
                ) AS duration_seconds
            FROM user_sessions us
            JOIN users u ON us.user_id = u.id
            ORDER BY us.created_at DESC
            LIMIT $limit
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Count currently active sessions */
    public function countActiveSessions() {
        $r = $this->conn->query("
            SELECT COUNT(*) as c FROM user_sessions
            WHERE is_active = 1 AND expires_at > NOW()
        ");
        return (int)$r->fetch()['c'];
    }

    // ── Session timeline (login_logs) ─────────────────────────

    public function getSessionTimeline($limit = 200) {
        $limit = (int)$limit;
        $stmt  = $this->conn->prepare("
            SELECT ll.*, u.firstname, u.lastname, u.role
            FROM login_logs ll
            LEFT JOIN users u ON ll.user_id = u.id
            ORDER BY ll.created_at DESC
            LIMIT $limit
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getActiveSessionsCount() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(DISTINCT user_id) as c
            FROM login_logs
            WHERE status = 'success'
            AND user_id NOT IN (
                SELECT DISTINCT user_id FROM login_logs lo2
                WHERE lo2.status = 'logout'
                AND lo2.created_at > (
                    SELECT MAX(lo3.created_at) FROM login_logs lo3
                    WHERE lo3.user_id = lo2.user_id AND lo3.status = 'success'
                )
            )
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute();
        return (int)$stmt->fetch()['c'];
    }

    // ── Helpers ──────────────────────────────────────────────

    private function getIP() {
        foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '0.0.0.0';
    }

    private function getUA() {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255);
    }
}
?>
