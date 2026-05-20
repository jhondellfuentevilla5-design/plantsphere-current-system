<?php
class PlantingMaterial {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM planting_materials ORDER BY material_name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM planting_materials WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO planting_materials (material_name, material_type, quantity, unit, description)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['material_name'],
            $data['material_type'],
            $data['quantity'],
            $data['unit'],
            $data['description']
        ]);
    }

    public function update($id, $data) {
        $stmt = $this->conn->prepare("
            UPDATE planting_materials 
            SET material_name = ?, material_type = ?, quantity = ?, unit = ?, description = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['material_name'],
            $data['material_type'],
            $data['quantity'],
            $data['unit'],
            $data['description'],
            $id
        ]);
    }

    public function deductStock($id, $qty) {
        $stmt = $this->conn->prepare("UPDATE planting_materials SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
        return $stmt->execute([$qty, $id, $qty]);
    }

    public function getTotalStock() {
        $stmt = $this->conn->prepare("SELECT SUM(quantity) as total FROM planting_materials");
        $stmt->execute();
        $row = $stmt->fetch();
        return $row['total'] ?? 0;
    }
}
?>
