<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/PermissionManager.php';

// Oturum kontrolü
checkLogin();

// İzin kontrolü
$permissionManager = new PermissionManager($conn);
if (!$permissionManager->hasPermission($_SESSION['user_id'], 'manage_users')) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

// Kullanıcı bilgilerini al
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: users.php');
    exit;
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $bio = trim($_POST['bio']);
    $password = trim($_POST['password']);
    
    // E-posta kontrolü
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        $error = "Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.";
    } else {
        // Şifre değişikliği varsa
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, bio = ?, password = ?
                WHERE id = ?
            ");
            $stmt->execute([$first_name, $last_name, $email, $phone, $address, $bio, $hashed_password, $user_id]);
        } else {
            $stmt = $conn->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, bio = ?
                WHERE id = ?
            ");
            $stmt->execute([$first_name, $last_name, $email, $phone, $address, $bio, $user_id]);
        }
        
        if ($stmt->rowCount() > 0) {
            $success = "Kullanıcı bilgileri başarıyla güncellendi.";
            // Kullanıcı bilgilerini yeniden al
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Kullanıcı bilgileri güncellenirken bir hata oluştu.";
        }
    }
}

$page_title = "Kullanıcı Düzenle";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Kullanıcı Düzenle</h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="form-group">
                            <label for="first_name">Ad</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Soyad</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">E-posta</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Telefon</label>
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Adres</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio">Hakkımda</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Yeni Şifre</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Değiştirmek istemiyorsanız boş bırakın">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                        <a href="users.php" class="btn btn-secondary">İptal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 
 