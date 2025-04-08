<?php
require_once 'includes/init.php';
require_once 'includes/TwoFactorAuth.php';

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$twoFactorAuth = new TwoFactorAuth($conn);
$userId = $_SESSION['user_id'];
$error = '';

// 2FA aktif değilse dashboard'a yönlendir
if (!$twoFactorAuth->isEnabled($userId)) {
    header('Location: dashboard.php');
    exit;
}

// Doğrulama kodu kontrolü
if (isset($_POST['verify'])) {
    $secret = $twoFactorAuth->getSecret($userId);
    if ($twoFactorAuth->verifyCode($secret, $_POST['verification_code'])) {
        $_SESSION['2fa_verified'] = true;
        header('Location: ' . ($_SESSION['redirect_after_2fa'] ?? 'dashboard.php'));
        exit;
    } else {
        $error = __('Geçersiz doğrulama kodu');
    }
}

// Sayfa başlığı
$pageTitle = __('İki Faktörlü Doğrulama');

// Header'ı dahil et
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo $pageTitle; ?></h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <p><?php echo __('Lütfen Google Authenticator uygulamanızdaki kodu girin'); ?></p>
                    
                    <form method="post">
                        <div class="form-group">
                            <label for="verification_code"><?php echo __('Doğrulama Kodu'); ?></label>
                            <input type="text" class="form-control" id="verification_code" name="verification_code" required autofocus>
                        </div>
                        <button type="submit" name="verify" class="btn btn-primary btn-block">
                            <?php echo __('Doğrula'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Footer'ı dahil et
require_once 'includes/footer.php';
?> 