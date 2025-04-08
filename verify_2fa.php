<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/TwoFactorAuth.php';

// Oturum kontrolü
if (!isset($_SESSION['2fa_user_id'])) {
    header('Location: login.php');
    exit;
}

$twoFactorAuth = new TwoFactorAuth($conn);
$user_id = $_SESSION['2fa_user_id'];
$error = '';

// 2FA doğrulama
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'];
    $secret = $twoFactorAuth->getSecretKey($user_id);
    
    // IP adresini al
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Son giriş denemelerini kontrol et
    if (!$twoFactorAuth->checkRecentAttempts($user_id, $ip_address)) {
        $error = "Çok fazla başarısız deneme. Lütfen 5 dakika sonra tekrar deneyin.";
    } else {
        // Kodu doğrula
        if ($twoFactorAuth->verifyCode($secret, $code)) {
            // Başarılı giriş
            $twoFactorAuth->logAttempt($user_id, $ip_address, true);
            
            $_SESSION['user_id'] = $_SESSION['2fa_user_id'];
            $_SESSION['username'] = $_SESSION['2fa_username'];
            $_SESSION['email'] = $_SESSION['2fa_email'];
            
            // Son giriş bilgilerini güncelle
            $stmt = $conn->prepare("UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?");
            $stmt->execute([$user_id]);
            
            // 2FA oturum değişkenlerini temizle
            unset($_SESSION['2fa_user_id']);
            unset($_SESSION['2fa_username']);
            unset($_SESSION['2fa_email']);
            
            header('Location: index.php');
            exit;
        } else {
            // Başarısız giriş
            $twoFactorAuth->logAttempt($user_id, $ip_address, false);
            $error = "Geçersiz doğrulama kodu.";
        }
    }
}

$page_title = "İki Faktörlü Doğrulama";
include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">İki Faktörlü Doğrulama</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <p class="text-center">
                        Google Authenticator uygulamasından aldığınız 6 haneli kodu girin.
                    </p>
                    
                    <form method="post">
                        <div class="form-group">
                            <label for="code">Doğrulama Kodu</label>
                            <input type="text" class="form-control" id="code" name="code" required 
                                   pattern="[0-9]{6}" maxlength="6" placeholder="000000">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Doğrula</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-link">Geri Dön</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 
 