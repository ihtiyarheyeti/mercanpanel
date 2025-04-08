<?php

namespace app\core;

class Settings {
    private static $instance = null;
    private $settings = [];
    private $db;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->loadSettings();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadSettings() {
        try {
            $stmt = $this->db->query("SELECT * FROM settings");
            while ($row = $stmt->fetch()) {
                $this->settings[$row['key']] = $row['value'];
            }
        } catch (\Exception $e) {
            // Tablo yoksa oluştur
            $this->createSettingsTable();
        }
    }

    private function createSettingsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(255) NOT NULL UNIQUE,
            value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $this->db->query($sql);
    }

    public function get($key, $default = null) {
        return $this->settings[$key] ?? $default;
    }

    public function set($key, $value) {
        try {
            $stmt = $this->db->query(
                "INSERT INTO settings (`key`, value) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE value = ?",
                [$key, $value, $value]
            );
            $this->settings[$key] = $value;
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function __clone() {}
    public function __wakeup() {}
} 