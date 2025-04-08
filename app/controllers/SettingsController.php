<?php

namespace app\controllers;

use app\core\Controller;
use app\core\Database;
use PDO;

class SettingsController extends Controller
{
    private $db;

    public function __construct()
    {
        // Database örneğini constructor'da alalım
        $this->db = Database::getInstance();
    }

    public function index()
    {
        // Tüm ayarları veritabanından alalım
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
        $settings_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Session flash mesajını alalım
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $message = $_SESSION['flash_message'] ?? null; // Flash mesajı al (varsa)
        unset($_SESSION['flash_message']); // Mesajı gösterdikten sonra sil

        // Açık şekilde main layout'u belirtiyoruz
        $this->render('settings/index', [
            'settings' => $settings_raw, 
            'title' => 'Genel Ayarlar', 
            'message' => $message
        ], 'main');
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Güncellenecek ayarlar (formdan gelenler)
            $allowed_settings = [
                // Genel ayarlar
                'site_title', 'logo', 'contact_email', 'mail_host', 'mail_port', 
                'mail_username', 'mail_password', 'mail_encryption',
                
                // SEO ayarları
                'meta_title', 'meta_description', 'meta_keywords', 'robots_txt', 'google_analytics',
                
                // Sosyal medya bağlantıları
                'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin', 'social_youtube',
                
                // API anahtarları
                'api_google_maps', 'api_recaptcha_site', 'api_recaptcha_secret', 'api_payment_gateway', 'api_webhook_url',
                
                // Özel kodlar
                'custom_css', 'custom_js', 'custom_header', 'custom_footer'
            ]; 
            
            $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)
                    ON DUPLICATE KEY UPDATE setting_value = :value";
                    
            $stmt = $this->db->getConnection()->prepare($sql);

            foreach ($allowed_settings as $key) {
                if (isset($_POST[$key])) {
                    $value = $_POST[$key];
                    
                    // Güvenlik açısından temizlik işlemleri
                    if ($key === 'robots_txt' || $key === 'custom_css' || $key === 'custom_js' || 
                        $key === 'custom_header' || $key === 'custom_footer') {
                        // Bu alanlarda HTML kodları olabileceği için dokunmuyoruz
                    } else {
                        // Diğer alanlarda temel temizlik yapabiliriz
                        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    }
                    
                    // PDOStatement üzerinden bindValue kullanalım
                    $stmt->bindValue(':key', $key);
                    $stmt->bindValue(':value', $value);
                    $stmt->execute();
                }
            }

            // Session başlatıldığından emin olun
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            // Basit bir flash mesaj sistemi (Session helper'ınız varsa onu kullanın)
            $_SESSION['flash_message'] = 'Ayarlar başarıyla güncellendi.';

            // Ayarlar sayfasına geri yönlendir
            header('Location: /mercanpanel/settings'); // Yönlendirme helper'ınız varsa onu kullanın (örn: Redirect::to('/settings'))
            exit;

        } else {
            // POST değilse ana sayfaya yönlendir
            header('Location: /mercanpanel/settings');
            exit;
        }
    }
} 