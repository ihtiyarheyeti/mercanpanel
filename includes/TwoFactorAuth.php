<?php
require_once 'vendor/autoload.php';
use PragmaRX\Google2FA\Google2FA;

<<<<<<< HEAD
/**
 * İki faktörlü doğrulama sınıfı
 */
=======
>>>>>>> ccb5d96c7a3b1476e5a37fa4f9031fd59a992043
class TwoFactorAuth {
    private $conn;
    private $google2fa;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->google2fa = new Google2FA();
    }
    
    /**
<<<<<<< HEAD
     * Yeni bir 2FA anahtarı oluşturur
     * @param int $userId Kullanıcı ID
     * @return array QR kodu ve gizli anahtar
     */
    public function generateSecret($userId) {
        $secretKey = $this->google2fa->generateSecretKey();
        
        // Anahtarı veritabanına kaydet
        $stmt = $this->conn->prepare("
            INSERT INTO user_2fa (user_id, secret_key, enabled)
            VALUES (?, ?, 0)
            ON DUPLICATE KEY UPDATE secret_key = ?
        ");
        
        $stmt->execute([$userId, $secretKey, $secretKey]);
        
        // QR kodu oluştur
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            'LutufPanel',
            $_SESSION['username'],
            $secretKey
        );
        
        return [
            'secret' => $secretKey,
            'qrCode' => $qrCodeUrl
        ];
    }
    
    /**
     * 2FA kodunu doğrular
     * @param string $secret Gizli anahtar
     * @param string $code Doğrulanacak kod
     * @return bool Doğrulama başarılı mı
=======
     * 2FA için yeni bir secret key oluştur
     */
    public function generateSecretKey() {
        return $this->google2fa->generateSecretKey();
    }
    
    /**
     * QR kod URL'si oluştur
     */
    public function getQRCodeUrl($username, $secret) {
        return $this->google2fa->getQRCodeUrl(
            'LutufPanel',
            $username,
            $secret
        );
    }
    
    /**
     * 2FA kodunu doğrula
>>>>>>> ccb5d96c7a3b1476e5a37fa4f9031fd59a992043
     */
    public function verifyCode($secret, $code) {
        return $this->google2fa->verifyKey($secret, $code);
    }
    
    /**
<<<<<<< HEAD
     * Kullanıcının 2FA durumunu kontrol eder
     * @param int $userId Kullanıcı ID
     * @return bool 2FA aktif mi
     */
    public function isEnabled($userId) {
        $stmt = $this->conn->prepare("
            SELECT enabled FROM user_2fa WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return $result ? (bool)$result['enabled'] : false;
    }
    
    /**
     * 2FA'yı aktifleştirir
     * @param int $userId Kullanıcı ID
     * @return bool İşlem başarılı mı
     */
    public function enable($userId) {
        $stmt = $this->conn->prepare("
            UPDATE user_2fa SET enabled = 1 WHERE user_id = ?
        ");
        return $stmt->execute([$userId]);
    }
    
    /**
     * 2FA'yı devre dışı bırakır
     * @param int $userId Kullanıcı ID
     * @return bool İşlem başarılı mı
     */
    public function disable($userId) {
        $stmt = $this->conn->prepare("
            UPDATE user_2fa SET enabled = 0 WHERE user_id = ?
        ");
        return $stmt->execute([$userId]);
    }
    
    /**
     * Kullanıcının gizli anahtarını getirir
     * @param int $userId Kullanıcı ID
     * @return string|null Gizli anahtar
     */
    public function getSecret($userId) {
        $stmt = $this->conn->prepare("
            SELECT secret_key FROM user_2fa WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return $result ? $result['secret_key'] : null;
=======
     * Kullanıcının 2FA ayarlarını güncelle
     */
    public function updateUser2FA($user_id, $secret, $enabled = true) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO user_2fa (user_id, secret_key, enabled, created_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                secret_key = VALUES(secret_key),
                enabled = VALUES(enabled),
                updated_at = NOW()
            ");
            
            return $stmt->execute([$user_id, $secret, $enabled]);
        } catch (Exception $e) {
            error_log("2FA güncelleme hatası: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kullanıcının 2FA durumunu kontrol et
     */
    public function is2FAEnabled($user_id) {
        $stmt = $this->conn->prepare("
            SELECT enabled FROM user_2fa WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        return $result && $result['enabled'];
>>>>>>> ccb5d96c7a3b1476e5a37fa4f9031fd59a992043
    }
    
    /**
     * 2FA giriş denemesini kaydet
     */
    public function logAttempt($user_id, $success) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO 2fa_attempts (user_id, success, ip_address, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            
            return $stmt->execute([$user_id, $success, $_SERVER['REMOTE_ADDR']]);
        } catch (Exception $e) {
            error_log("2FA deneme kaydı hatası: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Son 2FA denemelerini kontrol et
     */
    public function checkRecentAttempts($user_id, $max_attempts = 5, $time_window = 300) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count FROM 2fa_attempts
            WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$user_id, $time_window]);
        $result = $stmt->fetch();
        
        return $result['count'] < $max_attempts;
    }
    
    /**
     * 2FA ayarlarını getir
     */
    public function get2FASettings($user_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM user_2fa WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }
    
    /**
<<<<<<< HEAD
=======
     * 2FA'yı devre dışı bırak
     */
    public function disable2FA($user_id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE user_2fa SET enabled = 0, updated_at = NOW()
                WHERE user_id = ?
            ");
            
            return $stmt->execute([$user_id]);
        } catch (Exception $e) {
            error_log("2FA devre dışı bırakma hatası: " . $e->getMessage());
            return false;
        }
    }
    
    /**
>>>>>>> ccb5d96c7a3b1476e5a37fa4f9031fd59a992043
     * Yedek kodları oluştur
     */
    public function generateBackupCodes($count = 8) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        }
        return $codes;
    }
    
    /**
     * Yedek kodları kaydet
     */
    public function saveBackupCodes($user_id, $codes) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE user_2fa SET backup_codes = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            
            return $stmt->execute([json_encode($codes), $user_id]);
        } catch (Exception $e) {
            error_log("Yedek kod kaydetme hatası: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Yedek kodu doğrula
     */
    public function verifyBackupCode($user_id, $code) {
        $settings = $this->get2FASettings($user_id);
        if (!$settings || !$settings['backup_codes']) {
            return false;
        }
        
        $backup_codes = json_decode($settings['backup_codes'], true);
        $index = array_search($code, $backup_codes);
        
        if ($index !== false) {
            // Kullanılan kodu listeden kaldır
            unset($backup_codes[$index]);
            $this->saveBackupCodes($user_id, array_values($backup_codes));
            return true;
        }
        
        return false;
    }
} 