<?php
require_once 'config/database.php';

try {
    // Admin kullanıcısını oluştur
    $stmt = $conn->prepare("
        INSERT INTO users (username, password, email, first_name, last_name, role) 
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        password = VALUES(password),
        email = VALUES(email),
        first_name = VALUES(first_name),
        last_name = VALUES(last_name),
        role = VALUES(role)
    ");

    $password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt->execute([
        'admin',
        $password,
        'admin@example.com',
        'System',
        'Admin',
        'admin'
    ]);

    echo "Admin kullanıcısı başarıyla oluşturuldu/güncellendi.\n";
    echo "Kullanıcı adı: admin\n";
    echo "Şifre: admin123\n";

} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
} 