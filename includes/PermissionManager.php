<?php
class PermissionManager {
    private $conn;
    private $permissions = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function loadUserPermissions($user_id) {
        $stmt = $this->conn->prepare("
            SELECT p.name 
            FROM permissions p 
            JOIN user_permissions up ON p.id = up.permission_id 
            WHERE up.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $this->permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    public function hasPermission($permission) {
        return in_array($permission, $this->permissions);
    }
    
    public function getAllPermissions() {
        $stmt = $this->conn->query("SELECT * FROM permissions ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUserPermissions($user_id) {
        $stmt = $this->conn->prepare("
            SELECT p.* 
            FROM permissions p 
            JOIN user_permissions up ON p.id = up.permission_id 
            WHERE up.user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateUserPermissions($user_id, $permission_ids) {
        // Mevcut izinleri sil
        $stmt = $this->conn->prepare("DELETE FROM user_permissions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Yeni izinleri ekle
        if (!empty($permission_ids)) {
            $values = [];
            $params = [];
            foreach ($permission_ids as $permission_id) {
                $values[] = "(?, ?)";
                $params[] = $user_id;
                $params[] = $permission_id;
            }
            
            $sql = "INSERT INTO user_permissions (user_id, permission_id) VALUES " . implode(", ", $values);
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
        }
        
        return true;
    }
}
?> 
 