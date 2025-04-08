<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Settings.php';
require_once 'includes/helpers.php';
require_once 'includes/NotificationManager.php';

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$settings = Settings::getInstance($conn);
$notificationManager = new NotificationManager($conn);
$current_user_id = $_SESSION['user_id'];

// Kullanıcı bilgilerini al
$user = [];
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$current_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error'] = 'Kullanıcı bulunamadı.';
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}

// Profil fotoğrafı yükleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Güvenlik doğrulaması başarısız.';
    } else {
        $file = $_FILES['profile_photo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $_SESSION['error'] = 'Sadece JPG, PNG ve GIF formatları desteklenir.';
        } elseif ($file['size'] > $max_size) {
            $_SESSION['error'] = 'Dosya boyutu en fazla 5MB olabilir.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Dosya yükleme hatası: ' . $file['error'];
        } else {
            $upload_dir = 'uploads/profile_photos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Eski fotoğrafı sil
            if ($user['profile_photo'] && file_exists($user['profile_photo'])) {
                unlink($user['profile_photo']);
            }
            
            // Yeni dosya adı oluştur
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $current_user_id . '_' . time() . '.' . $extension;
            $target_file = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                // Veritabanını güncelle
                $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                if ($stmt->execute([$target_file, $current_user_id])) {
                    $_SESSION['success'] = 'Profil fotoğrafı başarıyla güncellendi.';
                    $_SESSION['profile_photo'] = $target_file; // Session'a profil fotoğrafını ekle
                    $user['profile_photo'] = $target_file; // Görüntüleme için user array'ini güncelle
                } else {
                    $_SESSION['error'] = 'Veritabanı güncellenirken hata oluştu.';
                }
            } else {
                $_SESSION['error'] = 'Dosya yüklenirken hata oluştu.';
            }
        }
    }
    header('Location: profile.php');
    exit;
}

// Profil fotoğrafı silme işlemi
if (isset($_POST['delete_photo']) && $_POST['delete_photo'] === '1') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Güvenlik doğrulaması başarısız.';
    } else {
        if ($user['profile_photo'] && file_exists($user['profile_photo'])) {
            unlink($user['profile_photo']);
        }
        
        $stmt = $conn->prepare("UPDATE users SET profile_photo = NULL WHERE id = ?");
        if ($stmt->execute([$current_user_id])) {
            $_SESSION['success'] = 'Profil fotoğrafı başarıyla silindi.';
            $_SESSION['profile_photo'] = null; // Session'dan profil fotoğrafını kaldır
            $user['profile_photo'] = null;
        } else {
            $_SESSION['error'] = 'Profil fotoğrafı silinirken hata oluştu.';
        }
    }
    header('Location: profile.php');
    exit;
}

// Profil güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Güvenlik doğrulaması başarısız.';
    } else {
        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        
        // Validasyon
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Geçerli bir e-posta adresi giriniz.';
        } else {
            try {
                // E-posta adresi başka bir kullanıcı tarafından kullanılıyor mu kontrol et
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $current_user_id]);
                
                if ($stmt->rowCount() > 0) {
                    $_SESSION['error'] = 'Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.';
                } else {
                    // Veritabanını güncelle
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET full_name = ?, email = ? 
                        WHERE id = ?
                    ");
                    
                    if ($stmt->execute([$full_name, $email, $current_user_id])) {
                        $_SESSION['success'] = 'Profil bilgileri başarıyla güncellendi.';
                        
                        // Bildirim oluştur
                        if ($notificationManager->shouldSendNotification($current_user_id, 'system')) {
                            $notificationManager->send(
                                $current_user_id,
                                'Profil Güncellendi',
                                'Profil bilgileriniz ' . date('d.m.Y H:i') . ' tarihinde başarıyla güncellendi.',
                                'user'
                            );
                        }
                        
                        // Sayfayı yenile
                        header('Location: profile.php');
                        exit;
                    } else {
                        $_SESSION['error'] = 'Profil güncellenirken bir hata oluştu.';
                    }
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
            }
        }
    }
}

// Şifre değiştirme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Güvenlik doğrulaması başarısız.';
    } else {
        $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        // Validasyon
        if (empty($current_password)) {
            $_SESSION['error'] = 'Mevcut şifrenizi giriniz.';
        } elseif (empty($new_password)) {
            $_SESSION['error'] = 'Yeni şifrenizi giriniz.';
        } elseif (strlen($new_password) < 8) {
            $_SESSION['error'] = 'Şifre en az 8 karakter uzunluğunda olmalıdır.';
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['error'] = 'Şifreler eşleşmiyor.';
        } else {
            try {
                // Mevcut şifreyi kontrol et
                if (!password_verify($current_password, $user['password'])) {
                    $_SESSION['error'] = 'Mevcut şifre hatalı.';
                } else {
                    // Yeni şifreyi kaydet
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET password = ? 
                        WHERE id = ?
                    ");
                    
                    if ($stmt->execute([$hashed_password, $current_user_id])) {
                        $_SESSION['success'] = 'Şifreniz başarıyla değiştirildi.';
                        
                        // Bildirim oluştur
                        if ($notificationManager->shouldSendNotification($current_user_id, 'security')) {
                            $notificationManager->send(
                                $current_user_id,
                                'Şifre Değiştirildi',
                                'Hesap şifreniz ' . date('d.m.Y H:i') . ' tarihinde değiştirildi. Bu işlemi siz yapmadıysanız, lütfen hemen yeni bir şifre oluşturun.',
                                'security'
                            );
                        }
                        
                        // Sayfayı yenile
                        header('Location: profile.php');
                        exit;
                    } else {
                        $_SESSION['error'] = 'Şifre değiştirilirken bir hata oluştu.';
                    }
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
            }
        }
    }
}

$page_title = "Profil";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Profil</h1>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo htmlspecialchars($_SESSION['success']);
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo htmlspecialchars($_SESSION['error']);
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Profil Fotoğrafı -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profil Fotoğrafı</h5>
                </div>
                <div class="card-body text-center">
                    <?php if ($user['profile_photo'] && file_exists($user['profile_photo'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" 
                             alt="Profil Fotoğrafı" 
                             class="img-fluid rounded-circle mb-3" 
                             style="max-width: 200px; height: auto;">
                        
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="delete_photo" value="1">
                            <button type="submit" class="btn btn-danger btn-sm" 
                                    onclick="return confirm('Profil fotoğrafını silmek istediğinizden emin misiniz?')">
                                <i class="fas fa-trash-alt"></i> Fotoğrafı Sil
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" 
                             style="width: 200px; height: 200px;">
                            <i class="fas fa-user fa-5x text-secondary"></i>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="mb-3">
                            <label for="profile_photo" class="form-label">Yeni Fotoğraf Seç</label>
                            <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*" required>
                            <div class="form-text">
                                Maksimum dosya boyutu: 5MB<br>
                                İzin verilen formatlar: JPG, PNG, GIF
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Fotoğrafı Kaydet
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Profil Bilgileri -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profil Bilgileri</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="profile.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Kullanıcı Adı</label>
                            <input type="text" class="form-control" id="username" value="<?php echo h($user['username']); ?>" readonly>
                            <div class="form-text">Kullanıcı adı değiştirilemez.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Ad Soyad</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo h($user['full_name']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">E-posta Adresi</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo h($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Rol</label>
                            <input type="text" class="form-control" id="role" value="<?php echo h(ucfirst($user['role'])); ?>" readonly>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Bilgileri Kaydet
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Şifre Değiştirme -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Şifre Değiştir</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="profile.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mevcut Şifre</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Yeni Şifre</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">Şifreniz en az 8 karakter uzunluğunda olmalıdır.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Yeni Şifre (Tekrar)</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key me-1"></i> Şifreyi Değiştir
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Hesap Bilgileri -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Hesap Bilgileri</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 row">
                        <label class="col-sm-5 col-form-label">Kayıt Tarihi</label>
                        <div class="col-sm-7">
                            <p class="form-control-plaintext"><?php echo formatDate($user['created_at']); ?></p>
                        </div>
                    </div>
                    
                    <div class="mb-3 row">
                        <label class="col-sm-5 col-form-label">Son Giriş</label>
                        <div class="col-sm-7">
                            <?php
                            // Son giriş bilgisini al
                            try {
                                $stmt = $conn->prepare("
                                    SELECT created_at, ip_address FROM user_logs 
                                    WHERE user_id = ? AND action = 'login' 
                                    ORDER BY created_at DESC LIMIT 1
                                ");
                                $stmt->execute([$current_user_id]);
                                $last_login = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($last_login) {
                                    echo '<p class="form-control-plaintext">' . formatDate($last_login['created_at']) . ' - ' . h($last_login['ip_address']) . '</p>';
                                } else {
                                    echo '<p class="form-control-plaintext">Bilgi yok</p>';
                                }
                            } catch (PDOException $e) {
                                echo '<p class="form-control-plaintext">Bilgi alınamadı</p>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="mb-3 row">
                        <label class="col-sm-5 col-form-label">IP Adresi</label>
                        <div class="col-sm-7">
                            <p class="form-control-plaintext"><?php echo h($_SERVER['REMOTE_ADDR']); ?></p>
                        </div>
                    </div>
                    
                    <div class="mb-3 row">
                        <label class="col-sm-5 col-form-label">Tarayıcı</label>
                        <div class="col-sm-7">
                            <p class="form-control-plaintext"><?php echo h($_SERVER['HTTP_USER_AGENT']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 
 