<?php
require_once 'config/database.php';

try {
    // Önce sütunun var olup olmadığını kontrol et
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
    $exists = $stmt->fetch();

    if (!$exists) {
        // Sütun yoksa ekle ve varsayılan değeri 'active' olarak ayarla
        $sql = "ALTER TABLE users 
                ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active' 
                AFTER role";
        $conn->exec($sql);
        echo "status sütunu başarıyla eklendi.";
    } else {
        echo "status sütunu zaten mevcut.";
    }
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?> 