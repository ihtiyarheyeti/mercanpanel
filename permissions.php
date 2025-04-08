<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/PermissionManager.php';

// Oturum kontrolü
checkLogin();

// Sadece adminler izinleri yönetebilir
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$permissionManager = new PermissionManager($conn);
$error = '';
$success = '';

// İzin güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permissions'])) {
    $user_id = (int)$_POST['user_id'];
    $permission_ids = isset($_POST['permissions']) ? $_POST['permissions'] : [];
    
    if ($permissionManager->updateUserPermissions($user_id, $permission_ids)) {
        $success = "İzinler başarıyla güncellendi.";
    } else {
        $error = "İzinler güncellenirken bir hata oluştu.";
    }
}

// Kullanıcı listesi
$stmt = $conn->query("SELECT id, username, role FROM users ORDER BY username");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İzin listesi
$permissions = $permissionManager->getAllPermissions();

$page_title = "İzin Yönetimi";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">İzin Yönetimi</h5>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label>Kullanıcı</label>
                    <select name="user_id" class="form-control" required>
                        <option value="">Kullanıcı Seçin</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['username']); ?> 
                                (<?php echo htmlspecialchars($user['role']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>İzinler</label>
                    <div class="row">
                        <?php foreach ($permissions as $permission): ?>
                            <div class="col-md-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           id="permission_<?php echo $permission['id']; ?>" 
                                           name="permissions[]" 
                                           value="<?php echo $permission['id']; ?>">
                                    <label class="custom-control-label" for="permission_<?php echo $permission['id']; ?>">
                                        <?php echo htmlspecialchars($permission['description']); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit" name="update_permissions" class="btn btn-primary">İzinleri Güncelle</button>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelector('select[name="user_id"]').addEventListener('change', function() {
    var userId = this.value;
    if (!userId) return;
    
    // AJAX ile kullanıcının mevcut izinlerini al
    fetch('ajax/get_user_permissions.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            // Tüm checkbox'ları temizle
            document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Kullanıcının izinlerini işaretle
            data.forEach(permission => {
                var checkbox = document.getElementById('permission_' + permission.id);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        });
});
</script>

<?php include 'includes/footer.php'; ?> 
 