<?php
/**
 * Oturum ve yetki kontrolleri
 */

session_start();

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Kullanıcı rolü kontrolü
if (isset($required_role) && $_SESSION['role'] !== $required_role) {
    header('Location: index.php');
    exit;
}

// Oturum süresini kontrol et
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Son aktivite zamanını güncelle
$_SESSION['last_activity'] = time();

// IP adresi kontrolü
if (!isset($_SESSION['ip']) || $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Tarayıcı kontrolü
if (!isset($_SESSION['user_agent']) || $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Oturum kimliğini yenile
if (!isset($_SESSION['created']) || time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Admin kontrolü
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Kullanıcı rolünü kontrol et
function check_role($required_role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        header('Location: index.php');
        exit;
    }
}

// İzin kontrolü
function check_permission($permission) {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Admin her zaman tüm izinlere sahiptir
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        return true;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM user_permissions up
            JOIN permissions p ON up.permission_id = p.id
            WHERE up.user_id = ? AND p.name = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $permission]);
        return (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("İzin kontrolü hatası: " . $e->getMessage());
        return false;
    }
}

// Kullanıcı rollerini tanımla
if (!defined('ROLE_ADMIN')) define('ROLE_ADMIN', 'admin');
if (!defined('ROLE_EDITOR')) define('ROLE_EDITOR', 'editor');
if (!defined('ROLE_USER')) define('ROLE_USER', 'user');

/**
 * Editör kontrolü
 */
function is_editor() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], [ROLE_ADMIN, ROLE_EDITOR]);
}

/**
 * Kullanıcı kontrolü
 */
function is_user() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], [ROLE_ADMIN, ROLE_EDITOR, ROLE_USER]);
}

/**
 * Oturum sahibi kontrolü
 */
function is_owner($user_id) {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id;
}

/**
 * Yetki kontrolü
 */
function authorize($required_role) {
    if (!check_permission($required_role)) {
        $_SESSION['error'] = 'Bu işlem için yetkiniz bulunmamaktadır.';
        header('Location: index.php');
        exit;
    }
}

/**
 * CSRF token doğrulama
 */
function validate_csrf($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $_SESSION['error'] = 'Güvenlik doğrulaması başarısız.';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    return true;
} 
 