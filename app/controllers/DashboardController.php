<?php

namespace app\controllers;

use app\core\Controller;

class DashboardController extends Controller {
    public function index() {
        // Oturum kontrolü
        if (!isset($_SESSION['user'])) {
            header('Location: /mercanpanel/login');
            exit;
        }

        // View render ederken dashboard/index.php içeriğini main.php layout'u içerisinde göster
        $this->render('dashboard/dashboard_content', [
            'title' => 'Dashboard - Mercan Panel',
            'user' => $_SESSION['user']
        ]);
    }
} 