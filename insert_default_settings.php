<?php
require_once 'config/database.php';

$default_settings = [
    'site_title' => 'Admin Panel',
    'site_description' => 'Yönetim Paneli',
    'admin_email' => 'admin@example.com',
    'timezone' => 'Europe/Istanbul',
    'date_format' => 'd.m.Y H:i',
    'max_file_size' => '10485760', // 10MB
    'allowed_extensions' => 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx',
    'theme_color' => '#007bff',
    'maintenance_mode' => '0'
];

try {
    $stmt = $conn->prepare("
        INSERT INTO settings (setting_key, setting_value) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");

    foreach ($default_settings as $key => $value) {
        $stmt->execute([$key, $value]);
    }
    
    echo "Varsayılan ayarlar başarıyla eklendi.";
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?> 