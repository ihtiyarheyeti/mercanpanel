<?php
require_once 'config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(255) PRIMARY KEY,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $conn->exec($sql);
    echo "Settings tablosu başarıyla oluşturuldu.";
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?> 