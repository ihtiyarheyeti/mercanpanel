<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/UserLogger.php';
require_once __DIR__ . '/PermissionManager.php';

// Oturum başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kullanıcının giriş yapıp yapmadığını kontrol et
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Kullanıcının admin olup olmadığını kontrol et
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Giriş kontrolü yap ve gerekirse yönlendir
 */
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Admin kontrolü yap ve gerekirse yönlendir
 */
function checkAdmin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: index.php');
        exit;
    }
}

/**
 * Kullanıcı çıkış yap
 */
function logout() {
    if (isset($_SESSION['user_id'])) {
        global $conn;
        
        // Çıkış logunu kaydet
        $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action, ip_address) VALUES (?, 'logout', ?)");
        $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
    }
    
    // Oturumu sonlandır
    session_destroy();
    
    // Giriş sayfasına yönlendir
    header('Location: login.php');
    exit;
}

/**
 * Dosya boyutunu formatla
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
 * XSS koruması için HTML çıktısını temizle
 */
function clean($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF token oluştur
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF token doğrula
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * IP adresini kontrol et
 */
function checkIP() {
    $allowed_ips = ['127.0.0.1', '::1']; // Localhost IP'leri
    $client_ip = $_SERVER['REMOTE_ADDR'];
    
    if (!in_array($client_ip, $allowed_ips)) {
        // IP adresi kaydını tut
        global $conn;
        $stmt = $conn->prepare("INSERT INTO ip_logs (ip_address, user_agent, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$client_ip, $_SERVER['HTTP_USER_AGENT']]);
    }
}

/**
 * Şifre karmaşıklığını kontrol et
 */
function checkPasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Şifre en az 8 karakter olmalıdır.';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Şifre en az bir büyük harf içermelidir.';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Şifre en az bir küçük harf içermelidir.';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Şifre en az bir rakam içermelidir.';
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Şifre en az bir özel karakter içermelidir.';
    }
    
    return $errors;
}

/**
 * Şifre sıfırlama token'ı oluştur
 */
function generatePasswordResetToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Şifre sıfırlama token'ını doğrula
 */
function validatePasswordResetToken($token) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

/**
 * Kullanıcı aktivitesini logla
 */
function logUserActivity($user_id, $action, $details = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $details, $_SERVER['REMOTE_ADDR']]);
}

function login($username, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['profile_photo'] = $user['profile_photo'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        
        // İzinleri yükle
        $permissionManager = new PermissionManager($conn);
        $permissionManager->loadUserPermissions($user['id']);
        $_SESSION['permissions'] = $permissionManager->getUserPermissions($user['id']);
        
        // Log kaydı
        $logger = new UserLogger($conn);
        $logger->log($user['id'], 'login', 'Kullanıcı girişi yapıldı');
        
        return true;
    }
    
    return false;
}

function checkPermission($permission) {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM user_permissions up
            JOIN permissions p ON up.permission_id = p.id
            WHERE up.user_id = ? AND p.name = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $permission]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        error_log("Yetki kontrolü hatası: " . $e->getMessage());
        return false;
    }
}

function requirePermission($permission) {
    if (!checkPermission($permission)) {
        header('Location: index.php');
        exit;
    }
}

// Kullanıcı bilgilerini al
function getUserInfo($user_id = null) {
    global $conn;
    
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? null;
    }
    
    if (!$user_id) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Kullanıcı bilgisi alınırken hata: " . $e->getMessage());
        return null;
    }
}

// Kullanıcı şifresini güncelle
function updatePassword($user_id, $new_password) {
    global $conn;
    
    try {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashed_password, $user_id]);
    } catch (Exception $e) {
        error_log("Şifre güncellenirken hata: " . $e->getMessage());
        return false;
    }
}

// Kullanıcı profilini güncelle
function updateProfile($user_id, $data) {
    global $conn;
    
    try {
        $allowed_fields = ['username', 'email', 'full_name', 'phone', 'address'];
        $updates = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $user_id;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        return $stmt->execute($params);
    } catch (Exception $e) {
        error_log("Profil güncellenirken hata: " . $e->getMessage());
        return false;
    }
}

// Kullanıcı oluştur
function createUser($data) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO users (
                username, password, email, full_name, role, 
                phone, address, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, 'user',
                ?, ?, NOW(), NOW()
            )
        ");
        
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        return $stmt->execute([
            $data['username'],
            $hashed_password,
            $data['email'],
            $data['full_name'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Kullanıcı oluşturulurken hata: " . $e->getMessage());
        return false;
    }
}

// Kullanıcı sil
function deleteUser($user_id) {
    global $conn;
    
    try {
        $conn->beginTransaction();
        
        // Kullanıcı loglarını sil
        $stmt = $conn->prepare("DELETE FROM user_logs WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Kullanıcı yetkilerini sil
        $stmt = $conn->prepare("DELETE FROM user_permissions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Kullanıcıyı sil
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $result = $stmt->execute([$user_id]);
        
        $conn->commit();
        return $result;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Kullanıcı silinirken hata: " . $e->getMessage());
        return false;
    }
}

// Kullanıcı listesini al
function getUsers($limit = null, $offset = null) {
    global $conn;
    
    try {
        $sql = "SELECT * FROM users ORDER BY created_at DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }
        
        $stmt = $conn->query($sql);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Kullanıcı listesi alınırken hata: " . $e->getMessage());
        return [];
    }
}

// Kullanıcı sayısını al
function getUserCount() {
    global $conn;
    
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM users");
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Kullanıcı sayısı alınırken hata: " . $e->getMessage());
        return 0;
    }
}

// Giriş kontrolü
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // 2FA kontrolü
        $twoFactorAuth = new TwoFactorAuth($conn);
        if ($twoFactorAuth->isEnabled($user['id'])) {
            // 2FA gerekiyor
            $_SESSION['2fa_user_id'] = $user['id'];
            $_SESSION['2fa_username'] = $user['username'];
            $_SESSION['redirect_after_2fa'] = 'dashboard.php';
            header('Location: verify_2fa.php');
            exit;
        } else {
            // Normal giriş
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Son giriş bilgilerini güncelle
            $stmt = $conn->prepare("UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            header('Location: dashboard.php');
            exit;
        }
    } else {
        $error = __('Geçersiz kullanıcı adı veya şifre');
    }
}
?> 
 