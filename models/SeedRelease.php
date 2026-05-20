<?php
class SeedRelease {
    private $conn;
    public function __construct($conn) { $this->conn = $conn; }

    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO seed_releases (request_id, slip_id, released_by, quantity_released, release_date, recipient_name, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$data['request_id'], $data['slip_id'], $data['released_by'], $data['quantity_released'], $data['release_date'], $data['recipient_name'], $data['remarks'] ?? null]);
        return $result ? $this->conn->lastInsertId() : false;
    }

    public function getByRequest($requestId) {
        $stmt = $this->conn->prepare("SELECT sr2.*, u.firstname, u.lastname FROM seed_releases sr2 JOIN users u ON sr2.released_by = u.id WHERE sr2.request_id = ? ORDER BY sr2.created_at DESC LIMIT 1");
        $stmt->execute([$requestId]);
        return $stmt->fetch();
    }

    public function getPendingReleases() {
        $stmt = $this->conn->prepare("SELECT rs.*, sr.request_number, sr.activity_name, sr.target_location, sr.target_date, sr.seedling_type, sr.quantity_requested, u.firstname, u.lastname FROM request_slips rs JOIN service_requests sr ON rs.request_id = sr.id JOIN users u ON sr.user_id = u.id WHERE rs.finalized_status = 'finalized' AND sr.status = 'finalized' ORDER BY rs.finalized_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT sr2.*, sr.request_number, sr.activity_name, u.firstname, u.lastname FROM seed_releases sr2 JOIN service_requests sr ON sr2.request_id = sr.id JOIN users u ON sr2.released_by = u.id ORDER BY sr2.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getTotalReleased() {
        $stmt = $this->conn->prepare("SELECT SUM(quantity_released) as total FROM seed_releases");
        $stmt->execute();
        return (int)($stmt->fetch()['total'] ?? 0);
    }
}
?>
