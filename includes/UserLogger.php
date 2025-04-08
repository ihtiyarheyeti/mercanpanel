<?php
class UserLogger {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function log($user_id, $action, $description = null) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        $stmt = $this->conn->prepare("
            INSERT INTO user_logs (user_id, action, description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$user_id, $action, $description, $ip_address, $user_agent]);
    }
    
    public function getLogs($user_id = null, $limit = 100) {
        $sql = "SELECT l.*, u.username 
                FROM user_logs l 
                JOIN users u ON l.user_id = u.id";
        
        $params = [];
        if ($user_id) {
            $sql .= " WHERE l.user_id = ?";
            $params[] = $user_id;
        }
        
        $sql .= " ORDER BY l.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?> 
 