<?php
class ServiceRequest {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($data) {
        $reqNumber = 'REQ-' . strtoupper(uniqid());
        $stmt = $this->conn->prepare("
            INSERT INTO service_requests 
            (request_number, user_id, activity_name, target_location, target_date,
             number_of_participants, seedling_type, quantity_requested, purpose,
             request_letter, proponent_name, association_name, recipient_name,
             recipient_position, activity_time)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            $reqNumber,
            $data['user_id'],
            $data['activity_name'],
            $data['target_location'],
            $data['target_date'],
            $data['number_of_participants'],
            $data['seedling_type'],
            $data['quantity_requested'],
            $data['purpose'],
            $data['request_letter'] ?? null,
            $data['proponent_name'] ?? null,
            $data['association_name'] ?? null,
            $data['recipient_name'] ?? null,
            $data['recipient_position'] ?? null,
            $data['activity_time'] ?? null,
        ]);
        return $result ? $this->conn->lastInsertId() : false;
    }

    public function getByUser($userId) {
        $stmt = $this->conn->prepare("
            SELECT sr.*, u.firstname, u.lastname 
            FROM service_requests sr
            JOIN users u ON sr.user_id = u.id
            WHERE sr.user_id = ?
            ORDER BY sr.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("
            SELECT sr.*, u.firstname, u.lastname, u.email
            FROM service_requests sr
            JOIN users u ON sr.user_id = u.id
            ORDER BY sr.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByStatus($status) {
        $stmt = $this->conn->prepare("
            SELECT sr.*, u.firstname, u.lastname, u.email
            FROM service_requests sr
            JOIN users u ON sr.user_id = u.id
            WHERE sr.status = ?
            ORDER BY sr.created_at DESC
        ");
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("
            SELECT sr.*, u.firstname, u.lastname, u.email, u.role
            FROM service_requests sr
            JOIN users u ON sr.user_id = u.id
            WHERE sr.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateStatus($id, $status, $remarks = null, $referredBy = null) {
        $stmt = $this->conn->prepare("
            UPDATE service_requests 
            SET status = ?, remarks = ?, referred_by = ?, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$status, $remarks, $referredBy, $id]);
    }

    public function getRequestNumber($id) {
        $stmt = $this->conn->prepare("SELECT request_number FROM service_requests WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $row['request_number'] : null;
    }
}
?>
