<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/SecurityLog.php';

/**
 * AuthController — handles registration, login, logout
 * with full security logging and lockout enforcement
 */
class AuthController {
    private $user;
    private $log;

    public function __construct($conn) {
        $this->user = new User($conn);
        $this->log  = new SecurityLog($conn);
    }

    // ── Registration ─────────────────────────────────────────

    public function register($data) {
        if (empty($data['firstname']) || empty($data['lastname']) ||
            empty($data['email'])     || empty($data['password'])) {
            return ['success' => false, 'error' => 'All fields are required.'];
        }

        // Password policy check
        if (!$this->user->validatePassword($data['password'])) {
            return ['success' => false, 'error' => 'Password does not meet the security policy. ' . User::passwordPolicyHint()];
        }

        $allowed = ['community_organizer','community_affairs_worker','agricultural_technologist','mao','barangay_captain','department_head','nursery'];
        $role = in_array($data['role'] ?? '', $allowed) ? $data['role'] : 'community_organizer';

        $result = $this->user->register(
            trim(htmlspecialchars($data['firstname'])),
            trim(htmlspecialchars($data['lastname'])),
            strtolower(trim($data['email'])),
            $data['password'],
            $role
        );

        if ($result) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'Registration failed. Email may already be in use.'];
    }

    // ── Login ────────────────────────────────────────────────

    public function login($data) {
        $email    = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'Email and password are required.'];
        }

        $result = $this->user->login($email, $password);

        if (is_array($result)) {
            // Success
            $this->log->logLogin($result['id'], $email, 'success');
            $this->log->logActivity($result['id'], 'login', 'auth', 'User logged in successfully.');
            $this->log->startSession($result['id']);
            return ['success' => true, 'user' => $result];
        }

        // Failure
        $messages = [
            'locked'   => 'Account is temporarily locked due to too many failed attempts. Try again in ' . User::LOCKOUT_MINUTES . ' minutes.',
            'inactive' => 'Your account has been deactivated. Please contact the administrator.',
            'invalid'  => 'Invalid email or password.',
        ];

        $this->log->logLogin(null, $email, $result === 'locked' ? 'locked' : 'failed', $result);
        return ['success' => false, 'error' => $messages[$result] ?? 'Login failed.'];
    }

    // ── Logout ───────────────────────────────────────────────

    public function logout($userId, $email = '') {
        $this->log->endSession('logout');
        $this->log->logLogout($userId, $email);
        $this->log->logActivity($userId, 'logout', 'auth', 'User logged out.');
    }
}
?>
