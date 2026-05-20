<?php
class RequestSlip {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($data) {
        $slipNumber = 'SLIP-' . strtoupper(uniqid());
        $stmt = $this->conn->prepare("
            INSERT INTO request_slips 
            (slip_number, request_id, validation_id, prepared_by, materials_requested, quantity_approved)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            $slipNumber,
            $data['request_id'],
            $data['validation_id'],
            $data['prepared_by'],
            $data['materials_requested'],
            $data['quantity_approved']
        ]);
        return $result ? $this->conn->lastInsertId() : false;
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("
            SELECT rs.*, 
                   preparer.firstname AS prep_firstname, preparer.lastname AS prep_lastname,
                   approver.firstname AS appr_firstname, approver.lastname AS appr_lastname,
                   sr.request_number, sr.activity_name, sr.target_location, sr.target_date,
                   sr.seedling_type, sr.quantity_requested,
                   req_user.firstname AS req_firstname, req_user.lastname AS req_lastname,
                   vr.site_location, vr.validation_date, vr.seed_packs_counted, vr.available_seedlings
            FROM request_slips rs
            JOIN users preparer ON rs.prepared_by = preparer.id
            LEFT JOIN users approver ON rs.approved_by = approver.id
            JOIN service_requests sr ON rs.request_id = sr.id
            JOIN users req_user ON sr.user_id = req_user.id
            JOIN validation_reports vr ON rs.validation_id = vr.id
            WHERE rs.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByRequest($requestId) {
        $stmt = $this->conn->prepare("SELECT * FROM request_slips WHERE request_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$requestId]);
        return $stmt->fetch();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("
            SELECT rs.*, 
                   preparer.firstname AS prep_firstname, preparer.lastname AS prep_lastname,
                   sr.request_number, sr.activity_name
            FROM request_slips rs
            JOIN users preparer ON rs.prepared_by = preparer.id
            JOIN service_requests sr ON rs.request_id = sr.id
            ORDER BY rs.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getPending() {
        $stmt = $this->conn->prepare("
            SELECT rs.*, 
                   preparer.firstname AS prep_firstname, preparer.lastname AS prep_lastname,
                   sr.request_number, sr.activity_name, sr.target_location
            FROM request_slips rs
            JOIN users preparer ON rs.prepared_by = preparer.id
            JOIN service_requests sr ON rs.request_id = sr.id
            WHERE rs.status IN ('prepared','reviewed')
            ORDER BY rs.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateStatus($id, $status, $remarks = null, $approvedBy = null, $endorsementOffice = null) {
        $stmt = $this->conn->prepare("
            UPDATE request_slips 
            SET status = ?, mao_remarks = ?, approved_by = ?, endorsement_office = ?, approved_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$status, $remarks, $approvedBy, $endorsementOffice, $id]);
    }

    public function review($id) {
        $stmt = $this->conn->prepare("UPDATE request_slips SET status = 'reviewed' WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>
