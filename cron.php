<?php
// Bu dosya cron görevleri tarafından çalıştırılmak üzere tasarlanmıştır
// Örnek cron ayarı: 0 3 * * * php /path/to/cron.php

define('RUNNING_CRON', true);
require_once 'config/database.php';
require_once 'includes/Settings.php';

// Çıktıyı kaydetmek için log dosyası
$log_file = 'logs/cron_' . date('Y-m-d') . '.log';
$log_dir = dirname($log_file);

// Log klasörünü oluştur
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Log dosyasına yaz
function log_message($message) {
    global $log_file;
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $message" . PHP_EOL, FILE_APPEND);
    echo "[$date] $message" . PHP_EOL;
}

log_message('Cron görevi başlatıldı');

// Ayarları al
$settings = Settings::getInstance($conn);
$auto_backup = $settings->get('auto_backup', 0);
$backup_frequency = $settings->get('backup_frequency', 'daily');
$backup_path = rtrim($settings->get('backup_path', 'backups/'), '/') . '/';
$backup_retention = $settings->get('backup_retention', 7);
$include_files = $settings->get('backup_include_files', 1);

// Önceki çalışma zamanını kontrol et
$last_run = $settings->get('last_backup_time', 0);
$current_time = time();
$run_backup = false;

// Yedekleme sıklığına göre çalıştırma kararı ver
if ($auto_backup) {
    switch ($backup_frequency) {
        case 'daily':
            // Son çalışmadan bu yana 24 saat geçti mi?
            $run_backup = ($current_time - $last_run) >= 86400;
            break;
        case 'weekly':
            // Son çalışmadan bu yana 7 gün geçti mi?
            $run_backup = ($current_time - $last_run) >= 604800;
            break;
        case 'monthly':
            // Son çalışmadan bu yana 30 gün geçti mi?
            $run_backup = ($current_time - $last_run) >= 2592000;
            break;
    }
}

// Otomatik yedekleme
if ($run_backup) {
    log_message('Otomatik yedekleme başlatılıyor...');
    
    try {
        // Klasörü oluştur (yoksa)
        if (!file_exists($backup_path)) {
            if (!mkdir($backup_path, 0755, true)) {
                throw new Exception('Yedekleme klasörü oluşturulamadı.');
            }
        }
        
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
        
        log_message('Veritabanı yedeği oluşturuldu: ' . basename($db_backup_file));
        
        // Dosya yedeği (eğer ayarlarda etkinse)
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
                log_message('Dosya yedeği oluşturuldu: ' . basename($files_backup_file));
            } else {
                throw new Exception('Dosya yedeği oluşturulamadı.');
            }
        }
        
        // Eski yedekleri temizle
        clean_old_backups($backup_path, $backup_retention);
        
        // Son çalışma zamanını güncelle
        $settings->set('last_backup_time', $current_time);
        
        log_message('Otomatik yedekleme başarıyla tamamlandı.');
        
    } catch (Exception $e) {
        log_message('HATA: ' . $e->getMessage());
    }
} else {
    log_message('Otomatik yedekleme zamanı gelmedi veya devre dışı.');
}

// Oturumları temizle
clean_old_sessions();

log_message('Cron görevi tamamlandı');

/**
 * Belirtilen süreden daha eski yedekleri temizler
 */
function clean_old_backups($backup_path, $days) {
    global $log_file;
    
    $cutoff = time() - ($days * 86400); // gün sayısını saniyeye çevir
    $removed = 0;
    
    foreach (glob($backup_path . 'db_backup_*.sql') as $file) {
        if (filemtime($file) < $cutoff) {
            unlink($file);
            $removed++;
        }
    }
    
    foreach (glob($backup_path . 'files_backup_*.zip') as $file) {
        if (filemtime($file) < $cutoff) {
            unlink($file);
            $removed++;
        }
    }
    
    log_message("$removed eski yedek dosyası temizlendi.");
}

/**
 * Eski oturum dosyalarını temizle
 */
function clean_old_sessions() {
    $session_path = session_save_path();
    if (empty($session_path)) {
        $session_path = '/tmp';
    }
    
    $session_files = glob($session_path . '/sess_*');
    $cleaned = 0;
    
    // 2 günden eski oturum dosyalarını temizle
    $cutoff = time() - (48 * 3600);
    
    foreach ($session_files as $file) {
        if (filemtime($file) < $cutoff) {
            unlink($file);
            $cleaned++;
        }
    }
    
    log_message("$cleaned eski oturum dosyası temizlendi.");
} 