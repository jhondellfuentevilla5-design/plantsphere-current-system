<?php
class RsbsaRegistry {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function register($data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO rsbsa_registry 
                (user_id, rsbsa_number, barangay, municipality, province, farm_size, crop_type, registration_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $data['user_id'],
                $data['rsbsa_number'],
                $data['barangay'],
                $data['municipality'],
                $data['province'],
                $data['farm_size'],
                $data['crop_type'],
                $data['registration_date']
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getByUser($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM rsbsa_registry WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("
            SELECT r.*, u.firstname, u.lastname, u.email
            FROM rsbsa_registry r
            JOIN users u ON r.user_id = u.id
            ORDER BY r.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("
            SELECT r.*, u.firstname, u.lastname, u.email
            FROM rsbsa_registry r
            JOIN users u ON r.user_id = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateStatus($id, $status) {
        $stmt = $this->conn->prepare("UPDATE rsbsa_registry SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function checkByUser($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM rsbsa_registry WHERE user_id = ? AND status = 'verified'");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function registerAssisted($data) {
        // Same as register — allows technologist to register on behalf of a user
        return $this->register($data);
    }
}
?>
