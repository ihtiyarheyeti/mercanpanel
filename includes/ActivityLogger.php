<?php
class ActivityLogger {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function log($user_id, $action, $description = '') {
        $stmt = $this->conn->prepare("
            INSERT INTO user_logs (user_id, action, description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $user_id,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
    }
    
    public function getUserLogs($user_id, $limit = 50) {
        $stmt = $this->conn->prepare("
            SELECT l.*, u.username 
            FROM user_logs l
            JOIN users u ON l.user_id = u.id
            WHERE l.user_id = ?
            ORDER BY l.created_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAllLogs($limit = 50) {
        $stmt = $this->conn->prepare("
            SELECT l.*, u.username 
            FROM user_logs l
            JOIN users u ON l.user_id = u.id
            ORDER BY l.created_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function searchLogs($query, $limit = 50) {
        $stmt = $this->conn->prepare("
            SELECT l.*, u.username 
            FROM user_logs l
            JOIN users u ON l.user_id = u.id
            WHERE l.action LIKE ? OR l.description LIKE ? OR u.username LIKE ?
            ORDER BY l.created_at DESC
            LIMIT ?
        ");
        
        $search = "%$query%";
        $stmt->execute([$search, $search, $search, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 
 