<?php
class ValidationReport {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO validation_reports 
            (request_id, technologist_id, site_location, site_area, validation_date, schedule_date,
             soil_condition, accessibility, recommended_species, seed_packs_counted, available_seedlings,
             findings, recommendation, site_photos, site_lat, site_lng)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            $data['request_id'],
            $data['technologist_id'],
            $data['site_location'],
            $data['site_area'],
            $data['validation_date'],
            $data['schedule_date'],
            $data['soil_condition'],
            $data['accessibility'],
            $data['recommended_species'],
            $data['seed_packs_counted'],
            $data['available_seedlings'],
            $data['findings'],
            $data['recommendation'],
            $data['site_photos'] ?? null,
            $data['site_lat'] ?? null,
            $data['site_lng'] ?? null,
        ]);
        return $result ? $this->conn->lastInsertId() : false;
    }

    public function getByRequest($requestId) {
        $stmt = $this->conn->prepare("
            SELECT vr.*, u.firstname, u.lastname
            FROM validation_reports vr
            JOIN users u ON vr.technologist_id = u.id
            WHERE vr.request_id = ?
            ORDER BY vr.created_at DESC LIMIT 1
        ");
        $stmt->execute([$requestId]);
        return $stmt->fetch();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("
            SELECT vr.*, u.firstname, u.lastname,
                   sr.request_number, sr.activity_name, sr.target_location
            FROM validation_reports vr
            JOIN users u ON vr.technologist_id = u.id
            JOIN service_requests sr ON vr.request_id = sr.id
            WHERE vr.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("
            SELECT vr.*, u.firstname, u.lastname,
                   sr.request_number, sr.activity_name
            FROM validation_reports vr
            JOIN users u ON vr.technologist_id = u.id
            JOIN service_requests sr ON vr.request_id = sr.id
            ORDER BY vr.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
