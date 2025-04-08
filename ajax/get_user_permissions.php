<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/PermissionManager.php';

// Oturum kontrolü
checkLogin();

// Sadece adminler izinleri görebilir
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit;
}

if (!isset($_GET['user_id'])) {
    http_response_code(400);
    exit;
}

$user_id = (int)$_GET['user_id'];
$permissionManager = new PermissionManager($conn);
$permissions = $permissionManager->getUserPermissions($user_id);

header('Content-Type: application/json');
echo json_encode($permissions);
?> 
 