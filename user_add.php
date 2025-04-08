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

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $bio = trim($_POST['bio']);
    $role = trim($_POST['role']);
    
    // Kullanıcı adı ve e-posta kontrolü
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $error = "Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.";
    } else {
        // Yeni kullanıcı ekle
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO users (username, first_name, last_name, email, password, phone, address, bio, role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$username, $first_name, $last_name, $email, $hashed_password, $phone, $address, $bio, $role])) {
            $success = "Kullanıcı başarıyla eklendi.";
            // Formu temizle
            $_POST = [];
        } else {
            $error = "Kullanıcı eklenirken bir hata oluştu.";
        }
    }
}

$page_title = "Yeni Kullanıcı Ekle";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Yeni Kullanıcı Ekle</h5>
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
                            <label for="username">Kullanıcı Adı</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="first_name">Ad</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Soyad</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">E-posta</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Şifre</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Telefon</label>
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Adres</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio">Hakkımda</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Rol</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="user" <?php echo isset($_POST['role']) && $_POST['role'] == 'user' ? 'selected' : ''; ?>>Kullanıcı</option>
                                <option value="editor" <?php echo isset($_POST['role']) && $_POST['role'] == 'editor' ? 'selected' : ''; ?>>Editör</option>
                                <option value="admin" <?php echo isset($_POST['role']) && $_POST['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
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
 