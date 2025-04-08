<?php
require_once 'config/database.php';

try {
    // Önce sütunun var olup olmadığını kontrol et
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    $exists = $stmt->fetch();

    if (!$exists) {
        // Sütun yoksa ekle ve varsayılan değeri 'user' olarak ayarla
        $sql = "ALTER TABLE users 
                ADD COLUMN role ENUM('admin', 'user') NOT NULL DEFAULT 'user' 
                AFTER email";
        $conn->exec($sql);
        
        // Admin kullanıcısının rolünü güncelle
        $sql = "UPDATE users SET role = 'admin' WHERE username = 'admin'";
        $conn->exec($sql);
        
        echo "role sütunu başarıyla eklendi ve admin kullanıcısı güncellendi.";
    } else {
        echo "role sütunu zaten mevcut.";
    }
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?> 