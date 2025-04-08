<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Database;
use Exception;

class HomeController extends Controller {
    public function index() {
        try {
            $db = Database::getInstance();
            $db_status = $db->testConnection();
        } catch (Exception $e) {
            $db_status = false;
        }

        $this->render('home/index', [
            'title' => 'Mercan Panel - Ana Sayfa',
            'db_status' => $db_status
        ]);
    }
} 