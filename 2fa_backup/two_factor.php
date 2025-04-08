<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/TwoFactorAuth.php';

// Oturum kontrolü
checkLogin();

$twoFactorAuth = new TwoFactorAuth($conn);
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// 2FA durumunu kontrol et
$is2FAEnabled = $twoFactorAuth->is2FAEnabled($user_id);

// 2FA'yı etkinleştir
if (isset($_POST['enable_2fa'])) {
    $code = $_POST['code'];
    $secret = $_POST['secret'];
    
    if ($twoFactorAuth->verifyCode($secret, $code)) {
        if ($twoFactorAuth->enable2FA($user_id)) {
            $success = "İki faktörlü doğrulama başarıyla etkinleştirildi.";
            $is2FAEnabled = true;
        } else {
            $error = "İki faktörlü doğrulama etkinleştirilirken bir hata oluştu.";
        }
    } else {
        $error = "Geçersiz doğrulama kodu.";
    }
}

// 2FA'yı devre dışı bırak
if (isset($_POST['disable_2fa'])) {
    $code = $_POST['code'];
    $secret = $twoFactorAuth->getSecretKey($user_id);
    
    if ($twoFactorAuth->verifyCode($secret, $code)) {
        if ($twoFactorAuth->disable2FA($user_id)) {
            $success = "İki faktörlü doğrulama başarıyla devre dışı bırakıldı.";
            $is2FAEnabled = false;
        } else {
            $error = "İki faktörlü doğrulama devre dışı bırakılırken bir hata oluştu.";
        }
    } else {
        $error = "Geçersiz doğrulama kodu.";
    }
}

// Yeni 2FA anahtarı oluştur
if (isset($_POST['generate_new'])) {
    $secret = $twoFactorAuth->generateSecretKey();
    $twoFactorAuth->setup2FA($user_id, $secret);
}

// Mevcut 2FA anahtarını al
$secret = $twoFactorAuth->getSecretKey($user_id);
if (!$secret) {
    $secret = $twoFactorAuth->generateSecretKey();
    $twoFactorAuth->setup2FA($user_id, $secret);
}

// QR kod URL'sini oluştur
$qrCodeUrl = $twoFactorAuth->getQRCodeUrl($_SESSION['email'], $secret);

$page_title = "İki Faktörlü Doğrulama";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">İki Faktörlü Doğrulama Ayarları</h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Durum</h5>
                                    <p class="card-text">
                                        İki faktörlü doğrulama şu anda: 
                                        <strong><?php echo $is2FAEnabled ? 'Etkin' : 'Devre dışı'; ?></strong>
                                    </p>
                                    
                                    <?php if (!$is2FAEnabled): ?>
                                    <form method="post" class="mt-3">
                                        <input type="hidden" name="secret" value="<?php echo $secret; ?>">
                                        <div class="form-group">
                                            <label for="code">Doğrulama Kodu</label>
                                            <input type="text" class="form-control" id="code" name="code" required>
                                            <small class="form-text text-muted">
                                                Google Authenticator uygulamasından aldığınız 6 haneli kodu girin.
                                            </small>
                                        </div>
                                        <button type="submit" name="enable_2fa" class="btn btn-primary">
                                            İki Faktörlü Doğrulamayı Etkinleştir
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <form method="post" class="mt-3">
                                        <div class="form-group">
                                            <label for="code">Doğrulama Kodu</label>
                                            <input type="text" class="form-control" id="code" name="code" required>
                                            <small class="form-text text-muted">
                                                Google Authenticator uygulamasından aldığınız 6 haneli kodu girin.
                                            </small>
                                        </div>
                                        <button type="submit" name="disable_2fa" class="btn btn-danger">
                                            İki Faktörlü Doğrulamayı Devre Dışı Bırak
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Kurulum</h5>
                                    <p class="card-text">
                                        İki faktörlü doğrulamayı etkinleştirmek için:
                                    </p>
                                    <ol>
                                        <li>Google Authenticator uygulamasını telefonunuza indirin</li>
                                        <li>Aşağıdaki QR kodu tarayın veya anahtarı manuel olarak girin</li>
                                        <li>Uygulamadan gelen 6 haneli kodu girin</li>
                                    </ol>
                                    
                                    <div class="text-center mb-3">
                                        <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code" class="img-fluid">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="secret">Manuel Anahtar</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="secret" value="<?php echo $secret; ?>" readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" onclick="copySecret()">
                                                    Kopyala
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <form method="post" class="mt-3">
                                        <button type="submit" name="generate_new" class="btn btn-secondary">
                                            Yeni Anahtar Oluştur
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copySecret() {
    var copyText = document.getElementById("secret");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    alert("Anahtar kopyalandı!");
}
</script>

<?php include 'includes/footer.php'; ?> 
 