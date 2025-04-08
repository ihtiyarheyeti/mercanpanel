<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Settings.php';

// Yalnızca AJAX isteklerine yanıt ver
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    die(json_encode(['success' => false, 'message' => 'Sadece AJAX istekleri kabul edilir.']));
}

// CSRF kontrolü
if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || $_SERVER['HTTP_X_CSRF_TOKEN'] !== $_SESSION['csrf_token']) {
    die(json_encode(['success' => false, 'message' => 'Geçersiz güvenlik tokeni.']));
}

// Yönetici değilse reddet
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Bu işlem için yönetici yetkileri gereklidir.']));
}

// Gelen veriyi al
$json = file_get_contents('php://input');
$data = json_decode($json, true);
$action = $data['action'] ?? '';

$settings = Settings::getInstance($conn);
$backup_path = rtrim($settings->get('backup_path', 'backups/'), '/') . '/';
$include_files = $settings->get('backup_include_files', 1);

// Klasörü oluştur (yoksa)
if (!file_exists($backup_path)) {
    if (!mkdir($backup_path, 0755, true)) {
        die(json_encode(['success' => false, 'message' => 'Yedekleme klasörü oluşturulamadı.']));
    }
}

// Manuel yedekleme
if ($action === 'manual_backup') {
    try {
        $timestamp = date('Y-m-d_H-i-s');
        $db_backup_file = $backup_path . 'db_backup_' . $timestamp . '.sql';
        
        // Veritabanı yapılandırması
        $db_host = $conn->query("SELECT @@hostname")->fetchColumn();
        $db_name = $conn->query("SELECT DATABASE()")->fetchColumn();
        
        // MySQL dump komutu
        $command = "mysqldump --host={$db_host} --user=" . DB_USER . " --password=" . DB_PASS . " {$db_name} > {$db_backup_file}";
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            throw new Exception('Veritabanı yedeği alınamadı.');
        }
        
        // Dosya yedeği (eğer ayarlarda etkinse)
        $files_backup_file = '';
        if ($include_files) {
            $files_backup_file = $backup_path . 'files_backup_' . $timestamp . '.zip';
            $files_dir = 'uploads/';
            
            $zip = new ZipArchive();
            if ($zip->open($files_backup_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                
                // Rekürsif olarak klasörü zip'e ekle
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($files_dir),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                
                foreach ($files as $name => $file) {
                    // Dizinleri atla
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen(realpath($files_dir)) + 1);
                        
                        $zip->addFile($filePath, $relativePath);
                    }
                }
                
                $zip->close();
            } else {
                throw new Exception('Dosya yedeği oluşturulamadı.');
            }
        }
        
        // Başarılı mesajı
        $message = 'Veritabanı yedeği oluşturuldu: ' . basename($db_backup_file);
        if ($include_files && !empty($files_backup_file)) {
            $message .= ', Dosya yedeği oluşturuldu: ' . basename($files_backup_file);
        }
        
        // Eski yedekleri temizle
        cleanupOldBackups($backup_path, $settings->get('backup_retention', 7));
        
        die(json_encode(['success' => true, 'message' => $message]));
        
    } catch (Exception $e) {
        die(json_encode(['success' => false, 'message' => $e->getMessage()]));
    }
} else {
    die(json_encode(['success' => false, 'message' => 'Geçersiz işlem.']));
}

/**
 * Belirtilen süreden daha eski yedekleri temizler
 */
function cleanupOldBackups($backup_path, $days) {
    $cutoff = time() - ($days * 86400); // gün sayısını saniyeye çevir
    
    foreach (glob($backup_path . 'db_backup_*.sql') as $file) {
        if (filemtime($file) < $cutoff) {
            unlink($file);
        }
    }
    
    foreach (glob($backup_path . 'files_backup_*.zip') as $file) {
        if (filemtime($file) < $cutoff) {
            unlink($file);
        }
    }
} 