<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Database;

class UserController extends Controller {
    private $db;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
        
        // Oturum kontrolü
        if (!isset($_SESSION['user'])) {
            header('Location: /mercanpanel/login');
            exit;
        }
    }

    public function index() {
        try {
            // Kullanıcıları veritabanından çek
            $stmt = $this->db->query("SELECT * FROM users ORDER BY id DESC");
            $users = $stmt->fetchAll();
        } catch (\Exception $e) {
            // Tablo yoksa oluştur
            $this->createUsersTable();
            $users = [];
        }

        $this->render('users/index', [
            'title' => 'Kullanıcılar - Mercan Panel',
            'users' => $users
        ], 'main');
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';

            // Geçerli roller listesi
            $valid_roles = ['superadmin', 'admin', 'editor', 'moderator', 'user'];

            if (!empty($username) && !empty($password)) {
                // Rol kontrolü
                if (!in_array($role, $valid_roles)) {
                    $error = 'Geçersiz rol seçimi.';
                } else {
                    try {
                        // Şifreyi hashle
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                        // Kullanıcıyı ekle
                        $this->db->query(
                            "INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, NOW())",
                            [$username, $hashed_password, $role]
                        );

                        header('Location: /mercanpanel/users');
                        exit;
                    } catch (\Exception $e) {
                        $error = 'Kullanıcı eklenirken bir hata oluştu.';
                    }
                }
            } else {
                $error = 'Tüm alanları doldurun.';
            }
        }

        $this->render('users/create', [
            'title' => 'Yeni Kullanıcı - Mercan Panel',
            'error' => $error ?? null
        ], 'main');
    }

    public function edit($id = null) {
        if ($id === null) {
            // URL'den ID parametresi alınamadıysa kullanıcı listesine yönlendir
            header('Location: /mercanpanel/users');
            exit;
        }

        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'user';

                if (!empty($username)) {
                    // Şifre değiştirilecekse
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $sql = "UPDATE users SET username = ?, role = ?, password = ? WHERE id = ?";
                        $params = [$username, $role, $hashed_password, $id];
                    } else {
                        $sql = "UPDATE users SET username = ?, role = ? WHERE id = ?";
                        $params = [$username, $role, $id];
                    }

                    $this->db->query($sql, $params);

                    header('Location: /mercanpanel/users');
                    exit;
                } else {
                    $error = 'Kullanıcı adı boş olamaz.';
                }
            }

            // Kullanıcı bilgilerini getir
            $user = $this->db->query("SELECT * FROM users WHERE id = ?", [$id])->fetch();
            
            if (!$user) {
                header('Location: /mercanpanel/users');
                exit;
            }

            $this->render('users/edit', [
                'title' => 'Kullanıcı Düzenle - Mercan Panel',
                'user' => $user,
                'error' => $error ?? null
            ], 'main');
        } catch (\Exception $e) {
            error_log("UserController::edit error: " . $e->getMessage());
            header('Location: /mercanpanel/users');
            exit;
        }
    }

    public function delete($id) {
        try {
            $this->db->query("DELETE FROM users WHERE id = ?", [$id]);
        } catch (\Exception $e) {
            // Hata yönetimi
        }
        header('Location: /mercanpanel/users');
        exit;
    }

    private function createUsersTable() {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $this->db->query($sql);

        // Admin kullanıcısını ekle
        try {
            $this->db->query(
                "INSERT INTO users (username, password, role) VALUES (?, ?, ?)",
                ['admin', password_hash('admin', PASSWORD_DEFAULT), 'admin']
            );
        } catch (\Exception $e) {
            // Admin zaten var, geç
        }
    }
} 