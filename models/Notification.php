<?php
class Notification {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($userId, $title, $message) {
        $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $title, $message]);
    }

    public function getByUser($userId, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT * FROM notifications WHERE user_id = ? 
            ORDER BY created_at DESC LIMIT ?
        ");
        $stmt->bindValue(1, (int)$userId, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countUnread($userId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row['cnt'];
    }

    public function markAllRead($userId) {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
}
?>
