<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Settings.php';
require_once 'includes/helpers.php';

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Yönetici değilse dashboard'a yönlendir
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// AJAX istekleri için JSON response header
header('Content-Type: application/json');

try {
    // CSRF kontrolü (GET istekleri hariç)
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Güvenlik doğrulaması başarısız.');
        }
    }

    // Kullanıcı bilgilerini getir
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_user') {
        $user_id = (int)$_GET['id'];
        
        $stmt = $conn->prepare("SELECT id, username, email, full_name, role, status FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception('Kullanıcı bulunamadı.');
        }
        
        echo json_encode($user);
        exit;
    }

    // Yeni kullanıcı ekle
    elseif (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $full_name = trim($_POST['full_name']);
        $role = $_POST['role'];
        $status = $_POST['status'];

        // Zorunlu alanları kontrol et
        if (empty($username) || empty($email) || empty($password)) {
            throw new Exception('Lütfen tüm zorunlu alanları doldurun.');
        }

        // Kullanıcı adı ve email benzersiz olmalı
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.');
        }

        // Şifre kontrolü
        if (strlen($password) < 8) {
            throw new Exception('Şifre en az 8 karakter olmalıdır.');
        }

        // Yeni kullanıcıyı ekle
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, full_name, role, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $username,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $full_name,
            $role,
            $status
        ]);

        $_SESSION['success'] = 'Kullanıcı başarıyla eklendi.';
        echo json_encode(['success' => true]);
        exit;
    }

    // Kullanıcı düzenle
    elseif (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
        $user_id = (int)$_POST['user_id'];
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name']);
        $role = $_POST['role'];
        $status = $_POST['status'];
        $password = trim($_POST['password']);

        // Zorunlu alanları kontrol et
        if (empty($username) || empty($email)) {
            throw new Exception('Lütfen tüm zorunlu alanları doldurun.');
        }

        // Kullanıcı adı ve email benzersiz olmalı
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.');
        }

        // Şifre değiştirilecekse kontrol et
        if (!empty($password)) {
            if (strlen($password) < 8) {
                throw new Exception('Şifre en az 8 karakter olmalıdır.');
            }
        }

        // Kullanıcıyı güncelle
        $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, status = ?";
        $params = [$username, $email, $full_name, $role, $status];

        if (!empty($password)) {
            $sql .= ", password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $user_id;

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        $_SESSION['success'] = 'Kullanıcı başarıyla güncellendi.';
        echo json_encode(['success' => true]);
        exit;
    }

    throw new Exception('Geçersiz işlem.');

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?> 