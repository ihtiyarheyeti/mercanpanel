<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Settings.php';
require_once 'includes/helpers.php';

// Kullanıcı giriş yapmamışsa hata döndür
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'Oturum açmanız gerekiyor.']));
}

// CSRF token kontrolü
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die(json_encode(['success' => false, 'error' => 'Geçersiz güvenlik tokeni.']));
}

$settings = Settings::getInstance($conn);
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'upload_file':
        if (!isset($_FILES['file'])) {
            die(json_encode(['success' => false, 'error' => 'Dosya seçilmedi.']));
        }

        $file = $_FILES['file'];
        $max_size = $settings->get('max_file_size', 5 * 1024 * 1024); // 5MB varsayılan
        
        // Dosya boyutu kontrolü
        if ($file['size'] > $max_size) {
            die(json_encode([
                'success' => false, 
                'error' => 'Dosya boyutu çok büyük. Maksimum: ' . formatFileSize($max_size)
            ]));
        }

        // Dosya uzantısı kontrolü
        $allowed_types = explode(',', $settings->get('allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip,rar'));
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            die(json_encode([
                'success' => false, 
                'error' => 'Bu dosya türüne izin verilmiyor.'
            ]));
        }

        // Dosyayı kaydet
        $upload_dir = 'uploads/files/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $new_filename = uniqid() . '.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO files (
                        original_name, 
                        stored_name, 
                        file_type, 
                        file_size, 
                        uploaded_by, 
                        upload_date
                    ) VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $file['name'],
                    $new_filename,
                    $file['type'],
                    $file['size'],
                    $_SESSION['user_id']
                ]);

                die(json_encode(['success' => true]));
            } catch (PDOException $e) {
                unlink($upload_path); // Dosyayı sil
                die(json_encode(['success' => false, 'error' => 'Veritabanı hatası.']));
            }
        } else {
            die(json_encode(['success' => false, 'error' => 'Dosya yüklenemedi.']));
        }
        break;

    case 'delete_file':
        if (!isset($_POST['file_id'])) {
            die(json_encode(['success' => false, 'error' => 'Dosya ID\'si belirtilmedi.']));
        }

        try {
            // Dosya bilgilerini al
            $stmt = $conn->prepare("
                SELECT * FROM files 
                WHERE id = ? AND (uploaded_by = ? OR ? = 'admin')
            ");
            $stmt->execute([$_POST['file_id'], $_SESSION['user_id'], $_SESSION['role']]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$file) {
                die(json_encode(['success' => false, 'error' => 'Dosya bulunamadı veya silme yetkiniz yok.']));
            }

            // Dosyayı fiziksel olarak sil
            $file_path = 'uploads/files/' . $file['stored_name'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // Veritabanından sil
            $stmt = $conn->prepare("DELETE FROM files WHERE id = ?");
            $stmt->execute([$_POST['file_id']]);

            die(json_encode(['success' => true]));
        } catch (PDOException $e) {
            die(json_encode(['success' => false, 'error' => 'Veritabanı hatası.']));
        }
        break;

    default:
        die(json_encode(['success' => false, 'error' => 'Geçersiz işlem.']));
}
?> 