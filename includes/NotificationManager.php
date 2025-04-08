<?php
/**
 * Bildirim Yöneticisi Sınıfı
 * Sistemdeki bildirimlerin yönetimini sağlar
 */
class NotificationManager {
    private $conn;
    private $settings;
    
    /**
     * Yapıcı metod
     * @param PDO $conn Veritabanı bağlantısı
     */
    public function __construct($conn) {
        $this->conn = $conn;
        $this->settings = Settings::getInstance($conn);
    }
    
    /**
     * Kullanıcıya bildirim gönderir
     * @param int $userId Kullanıcı ID
     * @param string $title Bildirim başlığı
     * @param string $message Bildirim mesajı
     * @param string $type Bildirim tipi
     * @param string|null $link Tıklanabilir link
     * @return bool Başarılı mı
     */
    public function send($userId, $title, $message, $type = 'info', $link = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, link)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([$userId, $title, $message, $type, $link]);
        } catch (PDOException $e) {
            // Loglama yapılabilir
            return false;
        }
    }
    
    /**
     * Tüm kullanıcılara bildirim gönderir
     * @param string $title Bildirim başlığı
     * @param string $message Bildirim mesajı
     * @param string $type Bildirim tipi
     * @param string|null $link Tıklanabilir link
     * @return bool Başarılı mı
     */
    public function sendToAll($title, $message, $type = 'info', $link = null) {
        try {
            $stmt = $this->conn->prepare("SELECT id FROM users");
            $stmt->execute();
            
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $success = true;
            
            foreach ($userIds as $userId) {
                $result = $this->send($userId, $title, $message, $type, $link);
                if (!$result) {
                    $success = false;
                }
            }
            
            return $success;
        } catch (PDOException $e) {
            // Loglama yapılabilir
            return false;
        }
    }
    
    /**
     * Bildirimi okundu olarak işaretler
     * @param int $notificationId Bildirim ID
     * @param int $userId Kullanıcı ID
     * @return bool Başarılı mı
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            
            return $stmt->execute([$notificationId, $userId]);
        } catch (PDOException $e) {
            // Loglama yapılabilir
            return false;
        }
    }
    
    /**
     * Kullanıcının tüm bildirimlerini okundu olarak işaretler
     * @param int $userId Kullanıcı ID
     * @return bool Başarılı mı
     */
    public function markAllAsRead($userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = ?
            ");
            
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            // Loglama yapılabilir
            return false;
        }
    }
    
    /**
     * Bildirimi siler
     * @param int $notificationId Bildirim ID
     * @param int $userId Kullanıcı ID
     * @return bool Başarılı mı
     */
    public function delete($notificationId, $userId) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND user_id = ?
            ");
            
            return $stmt->execute([$notificationId, $userId]);
        } catch (PDOException $e) {
            // Loglama yapılabilir
            return false;
        }
    }
    
    /**
     * Kullanıcının okunmamış bildirim sayısını döndürür
     * @param int $userId Kullanıcı ID
     * @return int Okunmamış bildirim sayısı
     */
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            // Loglama yapılabilir
            return 0;
        }
    }
    
    /**
     * Kullanıcının bildirim tercihlerini alır
     * @param int $userId Kullanıcı ID
     * @return array Bildirim tercihleri
     */
    public function getUserPreferences($userId) {
        return [
            'email_notifications' => (bool)$this->settings->get('user_' . $userId . '_email_notifications', 1),
            'system_notifications' => (bool)$this->settings->get('user_' . $userId . '_system_notifications', 1),
            'message_notifications' => (bool)$this->settings->get('user_' . $userId . '_message_notifications', 1),
            'login_notifications' => (bool)$this->settings->get('user_' . $userId . '_login_notifications', 1)
        ];
    }
    
    /**
     * Kullanıcı ayarına göre bildirim gönderip göndermemeyi kontrol eder
     * @param int $userId Kullanıcı ID
     * @param string $type Bildirim tipi
     * @return bool Gönderilmeli mi
     */
    public function shouldSendNotification($userId, $type) {
        $preferences = $this->getUserPreferences($userId);
        
        switch ($type) {
            case 'message':
                return $preferences['message_notifications'];
            case 'login':
                return $preferences['login_notifications'];
            default:
                return $preferences['system_notifications'];
        }
    }
    
    /**
     * E-posta bildirimi göndermelidir kontrolü
     * @param int $userId Kullanıcı ID
     * @return bool Gönderilmeli mi
     */
    public function shouldSendEmail($userId) {
        return (bool)$this->settings->get('user_' . $userId . '_email_notifications', 1);
    }
} 
 