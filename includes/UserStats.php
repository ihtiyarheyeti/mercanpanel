<?php
class UserStats {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getTotalUsers() {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM users");
        return $stmt->fetchColumn();
    }
    
    public function getActiveUsers() {
        $stmt = $this->conn->query("
            SELECT COUNT(DISTINCT user_id) 
            FROM user_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        return $stmt->fetchColumn();
    }
    
    public function getNewUsers($days = 30) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$days]);
        return $stmt->fetchColumn();
    }
    
    public function getLoginStats($days = 30) {
        $stmt = $this->conn->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM user_logs
            WHERE action = 'login'
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTopActions($limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT action, COUNT(*) as count
            FROM user_logs
            GROUP BY action
            ORDER BY count DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUserActivity($user_id, $days = 30) {
        $stmt = $this->conn->prepare("
            SELECT action, COUNT(*) as count
            FROM user_logs
            WHERE user_id = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY action
            ORDER BY count DESC
        ");
        $stmt->execute([$user_id, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getRoleDistribution() {
        $stmt = $this->conn->query("
            SELECT role, COUNT(*) as count
            FROM users
            GROUP BY role
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 
 