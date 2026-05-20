<?php

class User {
    private $conn;

    const MAX_ATTEMPTS   = 5;
    const LOCKOUT_MINUTES = 15;

    const MIN_LENGTH     = 8;

    public function __construct($conn) {
        $this->conn = $conn;
    }


    public function register($firstname, $lastname, $email, $password, $role = 'community_organizer') {
        
        if (!$this->validatePassword($password)) {
            return false;
        }
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO users (firstname, lastname, email, password, role, password_changed_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([$firstname, $lastname, $email, $hash, $role]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            return 'invalid';
        }

        if (!$user['is_active']) {
            return 'inactive';
        }

        if ($user['is_locked']) {
            if ($user['locked_until'] && new DateTime() < new DateTime($user['locked_until'])) {
                return 'locked';
            }

            $this->resetLockout($user['id']);
            $user['is_locked'] = 0;
            $user['failed_attempts'] = 0;
        }

        if (!password_verify($password, $user['password'])) {
            $this->incrementFailedAttempts($user['id']);
            return 'invalid';
        }

        $this->resetLockout($user['id']);
        $this->updateLastLogin($user['id']);

        unset($user['password']);
        return $user;
    }


    public function validatePassword($password) {
        if (strlen($password) < self::MIN_LENGTH)          return false;
        if (!preg_match('/[A-Z]/', $password))             return false;
        if (!preg_match('/[a-z]/', $password))             return false;
        if (!preg_match('/[0-9]/', $password))             return false;
        if (!preg_match('/[^A-Za-z0-9]/', $password))     return false;
        return true;
    }

    public static function passwordPolicyHint() {
        return 'Min. 8 characters with uppercase, lowercase, number, and special character (e.g. @#$!).';
    }


    private function incrementFailedAttempts($userId) {
        $stmt = $this->conn->prepare("
            UPDATE users
            SET failed_attempts = failed_attempts + 1,
                is_locked = IF(failed_attempts + 1 >= ?, 1, 0),
                locked_until = IF(failed_attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), locked_until)
            WHERE id = ?
        ");
        $stmt->execute([self::MAX_ATTEMPTS, self::MAX_ATTEMPTS, self::LOCKOUT_MINUTES, $userId]);
    }

    private function resetLockout($userId) {
        $stmt = $this->conn->prepare("
            UPDATE users SET failed_attempts = 0, is_locked = 0, locked_until = NULL WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }

    private function updateLastLogin($userId) {
        $stmt = $this->conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }

    public function setActive($userId, $active) {
        $stmt = $this->conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        return $stmt->execute([$active ? 1 : 0, $userId]);
    }

    public function unlockAccount($userId) {
        $stmt = $this->conn->prepare("
            UPDATE users SET is_locked = 0, failed_attempts = 0, locked_until = NULL WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }

    public function changePassword($userId, $newPassword) {
        if (!$this->validatePassword($newPassword)) return false;
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->conn->prepare("
            UPDATE users SET password = ?, password_changed_at = NOW() WHERE id = ?
        ");
        return $stmt->execute([$hash, $userId]);
    }

    public function findById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if ($u) unset($u['password']);
        return $u;
    }

    public function getAll() {
        $stmt = $this->conn->prepare("
            SELECT id, firstname, lastname, email, role, is_active, is_locked,
                   failed_attempts, last_login, password_changed_at, data_classification, created_at
            FROM users ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllByRole($role) {
        $stmt = $this->conn->prepare("SELECT id, firstname, lastname, email, role FROM users WHERE role = ?");
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }

    public function updateRole($userId, $role) {
        $allowed = ['admin','community_organizer','community_affairs_worker','agricultural_technologist','mao'];
        if (!in_array($role, $allowed)) return false;
        $stmt = $this->conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        return $stmt->execute([$role, $userId]);
    }

    public function updateClassification($userId, $classification) {
        $allowed = ['public','internal','confidential'];
        if (!in_array($classification, $allowed)) return false;
        $stmt = $this->conn->prepare("UPDATE users SET data_classification = ? WHERE id = ?");
        return $stmt->execute([$classification, $userId]);
    }

    public function delete($userId) {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        return $stmt->execute([$userId]);
    }

    public function getStats() {
        $r = $this->conn->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
        $byRole = [];
        foreach ($r->fetchAll() as $row) $byRole[$row['role']] = $row['cnt'];

        $r2 = $this->conn->query("SELECT COUNT(*) as c FROM users WHERE is_locked = 1");
        $locked = (int)$r2->fetch()['c'];

        $r3 = $this->conn->query("SELECT COUNT(*) as c FROM users WHERE is_active = 0");
        $inactive = (int)$r3->fetch()['c'];

        return compact('byRole', 'locked', 'inactive');
    }
}
?>
