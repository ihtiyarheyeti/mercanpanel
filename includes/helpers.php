<?php
/**
 * Dosya boyutunu okunabilir formata dönüştürür
 * @param int $bytes Bayt cinsinden boyut
 * @param int $precision Ondalık hassasiyet
 * @return string Formatlanmış boyut
 */
function formatFileSize($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Tarihi Türkçe formatlar
 * @param string $date Tarih
 * @param bool $showTime Saat gösterilsin mi
 * @return string Formatlanmış tarih
 */
function formatDate($date, $showTime = true) {
    $format = 'd.m.Y';
    if ($showTime) {
        $format .= ' H:i';
    }
    return date($format, strtotime($date));
}

/**
 * Güvenli HTML çıktısı oluşturur
 * @param string $str Metin
 * @return string Güvenli HTML
 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Aktif menü elemanını kontrol eder
 * @param string $page Sayfa adı
 * @return string CSS class
 */
function isActiveMenu($page) {
    $current_page = basename($_SERVER['PHP_SELF']);
    $pages = [
        'index' => ['index.php'],
        'files' => ['files.php', 'file_edit.php', 'file_actions.php'],
        'users' => ['users.php', 'user_add.php', 'user_edit.php', 'user_permissions.php'],
        'messages' => ['messages.php', 'message_view.php', 'message_send.php'],
        'notifications' => ['notifications.php', 'notification_settings.php'],
        'settings' => ['settings.php'],
        'seo_settings' => ['seo_settings.php'],
        'statistics' => ['statistics.php', 'user_stats.php'],
        'backups' => ['backups.php', 'backup.php']
    ];
    
    return isset($pages[$page]) && in_array($current_page, $pages[$page]);
}

/**
 * Dosya uzantısına göre Font Awesome ikonu döndürür
 * @param string $extension Dosya uzantısı
 * @return string Font Awesome class
 */
function getFileIcon($extension) {
    $icons = [
        'pdf' => 'fa-file-pdf',
        'doc' => 'fa-file-word',
        'docx' => 'fa-file-word',
        'xls' => 'fa-file-excel',
        'xlsx' => 'fa-file-excel',
        'jpg' => 'fa-file-image',
        'jpeg' => 'fa-file-image',
        'png' => 'fa-file-image',
        'gif' => 'fa-file-image',
        'zip' => 'fa-file-archive',
        'rar' => 'fa-file-archive',
        'txt' => 'fa-file-alt',
    ];
    
    return isset($icons[strtolower($extension)]) ? $icons[strtolower($extension)] : 'fa-file';
}

/**
 * Kullanıcı rolüne göre renk class'ı döndürür
 * @param string $role Kullanıcı rolü
 * @return string Bootstrap renk class'ı
 */
function getRoleBadgeClass($role) {
    $classes = [
        'admin' => 'bg-danger',
        'editor' => 'bg-warning',
        'user' => 'bg-info'
    ];
    
    return isset($classes[$role]) ? $classes[$role] : 'bg-secondary';
}

/**
 * Mesaj türüne göre alert class'ı döndürür
 * @param string $type Mesaj türü
 * @return string Bootstrap alert class'ı
 */
function getAlertClass($type) {
    $classes = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    return isset($classes[$type]) ? $classes[$type] : 'alert-info';
}

/**
 * Para birimini formatlar
 * @param float $amount Miktar
 * @param string $currency Para birimi
 * @return string Formatlanmış para
 */
function formatMoney($amount, $currency = '₺') {
    return number_format($amount, 2, ',', '.') . ' ' . $currency;
}

/**
 * Metni kısaltır
 * @param string $text Metin
 * @param int $length Maksimum uzunluk
 * @return string Kısaltılmış metin
 */
function truncate($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . '...';
}

/**
 * Kullanıcının belirli bir izne sahip olup olmadığını kontrol eder
 * @param PDO $conn Veritabanı bağlantısı
 * @param int $userId Kullanıcı ID
 * @param string $permissionName İzin adı
 * @return bool İzin var mı
 */
function hasPermission($conn, $userId, $permissionName) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM user_permissions up
        JOIN permissions p ON up.permission_id = p.id
        WHERE up.user_id = ? AND p.name = ?
    ");
    
    $stmt->execute([$userId, $permissionName]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Kullanıcının admin olup olmadığını kontrol eder
 * @param array $user Kullanıcı bilgileri
 * @return bool Admin mi
 */
function isAdmin($user) {
    return isset($user['role']) && $user['role'] === 'admin';
}

/**
 * Oturum açmış kullanıcıyı kontrol eder
 * @return void
 */
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Bildirim tipine göre ikon döndürür
 * @param string $type Bildirim tipi
 * @return string Font Awesome ikon adı
 */
function getNotificationIcon($type) {
    $icons = [
        'message' => 'envelope',
        'system' => 'cog',
        'login' => 'sign-in-alt',
        'file' => 'file',
        'user' => 'user',
        'security' => 'shield-alt',
        'backup' => 'database',
        'warning' => 'exclamation-triangle',
        'info' => 'info-circle',
        'success' => 'check-circle'
    ];
    
    return isset($icons[$type]) ? $icons[$type] : 'bell';
}

// Güvenli string temizleme
function clean_string($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

// Dosya boyutunu formatla
function format_size($size) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $i = 0;
    while ($size >= 1024 && $i < 4) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}

// Tarih formatla
function format_date($date, $format = 'd.m.Y H:i') {
    return date($format, strtotime($date));
}

// Slug oluştur
function create_slug($string) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return $slug;
}

// Rastgele string oluştur
function generate_random_string($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

// Dosya uzantısını kontrol et
function check_file_extension($filename, $allowed_extensions) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, $allowed_extensions);
}

// Dosya MIME tipini kontrol et
function check_mime_type($file, $allowed_types) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file);
    finfo_close($finfo);
    return in_array($mime_type, $allowed_types);
}

// Güvenli dosya adı oluştur
function create_safe_filename($filename) {
    $info = pathinfo($filename);
    $ext  = $info['extension'];
    $filename = $info['filename'];
    
    // Dosya adını temizle
    $filename = preg_replace("/[^a-zA-Z0-9\-\_]/", '', $filename);
    
    // Benzersiz bir isim oluştur
    return $filename . '_' . time() . '.' . $ext;
}

// Hata mesajı göster
function show_error($message) {
    $_SESSION['error'] = $message;
}

// Başarı mesajı göster
function show_success($message) {
    $_SESSION['success'] = $message;
}

// Bilgi mesajı göster
function show_info($message) {
    $_SESSION['info'] = $message;
}

// Uyarı mesajı göster
function show_warning($message) {
    $_SESSION['warning'] = $message;
}

// Mesajları göster ve temizle
function display_messages() {
    $types = ['error', 'success', 'info', 'warning'];
    $output = '';
    
    foreach ($types as $type) {
        if (isset($_SESSION[$type])) {
            $output .= '<div class="alert alert-' . ($type == 'error' ? 'danger' : $type) . ' alert-dismissible fade show" role="alert">';
            $output .= $_SESSION[$type];
            $output .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            $output .= '</div>';
            unset($_SESSION[$type]);
        }
    }
    
    return $output;
}

// CSRF token oluştur
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF token doğrula
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}
?> 