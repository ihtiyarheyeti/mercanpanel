<?php
try {
    $host = 'localhost';
    $dbname = 'admin_panel';
    $username = 'root';
    $password = '';
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => false
    ));
} catch(PDOException $e) {
    // Hata mesajını daha detaylı hale getirelim
    error_log("Veritabanı bağlantı hatası: " . $e->getMessage());
    echo "Veritabanı bağlantı hatası: " . $e->getMessage();
    die();
}
?> 