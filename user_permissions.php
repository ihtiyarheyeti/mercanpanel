<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/PermissionManager.php';

// Oturum kontrolü
checkLogin();

// İzin kontrolü
$permissionManager = new PermissionManager($conn);
if (!$permissionManager->hasPermission($_SESSION['user_id'], 'manage_permissions')) {
    header('Location: index.php');
    exit;
}

$success = '';
$error = '';

// Kullanıcı seçildiğinde
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$user = null;
$permissions = [];

if ($user_id) {
    // Kullanıcı bilgilerini al
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Kullanıcının izinlerini al
        $permissions = $permissionManager->getUserPermissions($user_id);
    }
}

// İzinler güncellendiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id) {
    $new_permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
    
    // Tüm izinleri al
    $all_permissions = $permissionManager->getAllPermissions();
    
    // Her izin için kontrol et
    foreach ($all_permissions as $permission) {
        $has_permission = in_array($permission['id'], $new_permissions);
        $current_permission = $permissionManager->hasPermission($user_id, $permission['name']);
        
        // İzin değişikliği varsa
        if ($has_permission != $current_permission) {
            if ($has_permission) {
                $permissionManager->grantPermission($user_id, $permission['name']);
            } else {
                $permissionManager->revokePermission($user_id, $permission['name']);
            }
        }
    }
    
    $success = "İzinler başarıyla güncellendi.";
    // İzinleri yeniden al
    $permissions = $permissionManager->getUserPermissions($user_id);
}

// Tüm kullanıcıları al
$stmt = $conn->query("SELECT id, username, first_name, last_name FROM users ORDER BY username");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tüm izinleri al
$all_permissions = $permissionManager->getAllPermissions();

$page_title = "Kullanıcı İzinleri";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Kullanıcı İzinleri</h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="get" class="mb-3">
                        <div class="form-group">
                            <label for="user_id">Kullanıcı Seçin</label>
                            <select class="form-control" id="user_id" name="user_id" onchange="this.form.submit()">
                                <option value="">Kullanıcı Seçin</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo $user_id == $u['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['username'] . ' (' . $u['first_name'] . ' ' . $u['last_name'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                    
                    <?php if ($user): ?>
                    <form method="post">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>İzin</th>
                                        <th>Açıklama</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_permissions as $permission): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($permission['name']); ?></td>
                                        <td><?php echo htmlspecialchars($permission['description']); ?></td>
                                        <td>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" 
                                                       id="permission_<?php echo $permission['id']; ?>" 
                                                       name="permissions[]" 
                                                       value="<?php echo $permission['id']; ?>"
                                                       <?php echo in_array($permission['id'], $permissions) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" 
                                                       for="permission_<?php echo $permission['id']; ?>">
                                                    <?php echo in_array($permission['id'], $permissions) ? 'Aktif' : 'Pasif'; ?>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">İzinleri Kaydet</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.custom-control-input').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const label = this.nextElementSibling;
        label.textContent = this.checked ? 'Aktif' : 'Pasif';
    });
});
</script>

<?php include 'includes/footer.php'; ?> 
 