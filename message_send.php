<?php
require_once 'includes/init.php';
require_once 'includes/auth_check.php';

// POST isteği kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: messages.php');
    exit;
}

// CSRF token kontrolü
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = $lang['error_csrf'];
    header('Location: messages.php');
    exit;
}

try {
    // Form verilerini al
    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Verileri kontrol et
    if ($receiver_id <= 0) {
        throw new Exception($lang['messages_error_send']);
    }

    if (empty($subject) || empty($message)) {
        throw new Exception($lang['error_required_fields']);
    }

    // Alıcının var olduğunu kontrol et
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$receiver_id]);
    if (!$stmt->fetch()) {
        throw new Exception($lang['messages_error_not_found']);
    }

    // Mesajı kaydet
    $stmt = $conn->prepare("
        INSERT INTO messages (sender_id, receiver_id, subject, message, send_date, is_read) 
        VALUES (?, ?, ?, ?, NOW(), 0)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $receiver_id,
        $subject,
        $message
    ]);

    $_SESSION['success'] = $lang['messages_send_success'];

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: messages.php');
exit; 
 
require_once 'includes/init.php';
require_once 'includes/auth_check.php';

// POST isteği kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: messages.php');
    exit;
}

// CSRF token kontrolü
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = $lang['error_csrf'];
    header('Location: messages.php');
    exit;
}

try {
    // Form verilerini al
    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Verileri kontrol et
    if ($receiver_id <= 0) {
        throw new Exception($lang['messages_error_send']);
    }

    if (empty($subject) || empty($message)) {
        throw new Exception($lang['error_required_fields']);
    }

    // Alıcının var olduğunu kontrol et
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$receiver_id]);
    if (!$stmt->fetch()) {
        throw new Exception($lang['messages_error_not_found']);
    }

    // Mesajı kaydet
    $stmt = $conn->prepare("
        INSERT INTO messages (sender_id, receiver_id, subject, message, send_date, is_read) 
        VALUES (?, ?, ?, ?, NOW(), 0)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $receiver_id,
        $subject,
        $message
    ]);

    $_SESSION['success'] = $lang['messages_send_success'];

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: messages.php');
exit; 
 
 