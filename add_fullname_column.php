<?php
require_once 'config/database.php';

try {
    // Önce sütunun var olup olmadığını kontrol et
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'full_name'");
    $exists = $stmt->fetch();

    if (!$exists) {
        // Sütun yoksa ekle
        $sql = "ALTER TABLE users ADD COLUMN full_name VARCHAR(255) NULL AFTER email";
        $conn->exec($sql);
        echo "full_name sütunu başarıyla eklendi.";
    } else {
        echo "full_name sütunu zaten mevcut.";
    }
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?> 