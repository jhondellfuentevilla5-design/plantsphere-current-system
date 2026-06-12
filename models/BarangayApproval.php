<?php
class BarangayApproval {
    private $conn;
    public function __construct($conn) { $this->conn = $conn; }

    public function create($requestId, $captainId) {
        $stmt = $this->conn->prepare("INSERT INTO barangay_approvals (request_id, captain_id, status) VALUES (?, ?, 'pending')");
        return $stmt->execute([$requestId, $captainId]);
    }

    public function getByRequest($requestId) {
        $stmt = $this->conn->prepare("SELECT ba.*, u.firstname, u.lastname FROM barangay_approvals ba JOIN users u ON ba.captain_id = u.id WHERE ba.request_id = ? ORDER BY ba.created_at DESC LIMIT 1");
        $stmt->execute([$requestId]);
        return $stmt->fetch();
    }

    public function getPendingForCaptain($captainId) {
        $stmt = $this->conn->prepare("SELECT ba.*, sr.request_number, sr.activity_name, sr.target_location, sr.target_date, sr.purpose, sr.request_letter, sr.seedling_type, sr.quantity_requested, u.firstname, u.lastname, u.email FROM barangay_approvals ba JOIN service_requests sr ON ba.request_id = sr.id JOIN users u ON sr.user_id = u.id WHERE ba.captain_id = ? AND ba.status = 'pending' ORDER BY ba.created_at DESC");
        $stmt->execute([$captainId]);
        return $stmt->fetchAll();
    }

    public function getAllForCaptain($captainId) {
        $stmt = $this->conn->prepare("SELECT ba.*, sr.request_number, sr.activity_name, sr.target_location, sr.target_date, sr.purpose, sr.request_letter, sr.seedling_type, sr.quantity_requested, u.firstname, u.lastname FROM barangay_approvals ba JOIN service_requests sr ON ba.request_id = sr.id JOIN users u ON sr.user_id = u.id WHERE ba.captain_id = ? ORDER BY ba.created_at DESC");
        $stmt->execute([$captainId]);
        return $stmt->fetchAll();
    }

    public function updateStatus($id, $status, $remarks = null) {
        $stmt = $this->conn->prepare("UPDATE barangay_approvals SET status = ?, remarks = ?, approved_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $remarks, $id]);
    }

    public function countPending($captainId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as c FROM barangay_approvals WHERE captain_id = ? AND status = 'pending'");
        $stmt->execute([$captainId]);
        return (int)$stmt->fetch()['c'];
    }
}
?>
