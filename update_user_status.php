<?php
require_once 'config/database.php';

try {
    // Tüm kullanıcıların durumunu 'active' olarak güncelle
    $sql = "UPDATE users SET status = 'active' WHERE status IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    echo "Kullanıcı durumları başarıyla güncellendi.";
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?> 