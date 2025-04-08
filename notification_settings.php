<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Settings.php';

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// CSRF token kontrolü
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Güvenlik doğrulaması başarısız.';
    header('Location: notifications.php');
    exit;
}

$settings = Settings::getInstance($conn);
$current_user_id = $_SESSION['user_id'];

// Tercihleri kaydet
$email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
$system_notifications = isset($_POST['system_notifications']) ? 1 : 0;
$message_notifications = isset($_POST['message_notifications']) ? 1 : 0;
$login_notifications = isset($_POST['login_notifications']) ? 1 : 0;

try {
    // Her bir tercihi ayrı ayrı kaydet
    $settings->set('user_' . $current_user_id . '_email_notifications', $email_notifications);
    $settings->set('user_' . $current_user_id . '_system_notifications', $system_notifications);
    $settings->set('user_' . $current_user_id . '_message_notifications', $message_notifications);
    $settings->set('user_' . $current_user_id . '_login_notifications', $login_notifications);
    
    $_SESSION['success'] = 'Bildirim tercihleriniz başarıyla kaydedildi.';
} catch (Exception $e) {
    $_SESSION['error'] = 'Bildirim tercihleri kaydedilirken bir hata oluştu.';
}

header('Location: notifications.php');
exit; 