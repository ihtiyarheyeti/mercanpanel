<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Database;

class AuthController extends Controller {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            // TODO: Kullanıcı doğrulama işlemleri burada yapılacak
            // Şimdilik basit bir kontrol
            if ($username === 'admin' && $password === 'admin') {
                $_SESSION['user'] = [
                    'id' => 1,
                    'username' => $username,
                    'role' => 'admin'
                ];
                header('Location: /mercanpanel/dashboard');
                exit;
            } else {
                $error = 'Geçersiz kullanıcı adı veya şifre!';
                $this->render('auth/login', ['error' => $error], null);
                return;
            }
        }

        $this->render('auth/login', [], null);
    }

    public function logout() {
        session_destroy();
        header('Location: /mercanpanel/login');
        exit;
    }
} 