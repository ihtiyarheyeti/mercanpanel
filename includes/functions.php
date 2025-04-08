<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Oturum kontrolü
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit();
    }
}

// Yetki kontrolü
function checkPermission($requiredRole) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $requiredRole) {
        header('Location: /dashboard.php');
        exit();
    }
}

// Güvenli çıktı
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Ayarları getir
function getSetting($key) {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return null;
}

// Tema yolu
function getThemePath() {
    $theme = getSetting('active_theme');
    return "/assets/themes/{$theme}/";
}

// Başlık
function getPageTitle($title = '') {
    $siteTitle = getSetting('site_title');
    return $title ? "{$title} - {$siteTitle}" : $siteTitle;
}
?> 
 