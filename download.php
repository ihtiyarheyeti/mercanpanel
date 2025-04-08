<?php
require_once 'config/database.php';
require_once 'includes/Settings.php';
require_once 'includes/FileManager.php';

session_start();

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Dosya ID'si kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Geçersiz dosya ID\'si.');
}

try {
    // Dosya bilgilerini al
    $stmt = $conn->prepare("SELECT * FROM files WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        die('Dosya bulunamadı.');
    }

    $file_path = 'uploads/files/' . $file['stored_name'];
    
    if (!file_exists($file_path)) {
        die('Dosya bulunamadı.');
    }

    // Dosya indirme sayısını güncelle
    $stmt = $conn->prepare("UPDATE files SET download_count = download_count + 1 WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    // Dosyayı indir
    header('Content-Type: ' . $file['file_type']);
    header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
    header('Content-Length: ' . $file['file_size']);
    header('Cache-Control: no-cache');
    readfile($file_path);
    exit;
    
} catch (PDOException $e) {
    die('Veritabanı hatası.');
}
?> 
 