<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/ActivityLogger.php';
require_once 'includes/PermissionManager.php';

// Oturum kontrolü
checkLogin();

// İzin kontrolü
$permissionManager = new PermissionManager($conn);
if (!$permissionManager->hasPermission($_SESSION['user_id'], 'view_logs')) {
    header('Location: index.php');
    exit;
}

$activityLogger = new ActivityLogger($conn);
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 50;

if ($search) {
    $logs = $activityLogger->searchLogs($search, $limit);
} else {
    $logs = $activityLogger->getAllLogs($limit);
}

$page_title = "Aktivite Logları";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Aktivite Logları</h5>
                </div>
                <div class="card-body">
                    <form method="get" class="mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Ara..." value="<?php echo htmlspecialchars($search); ?>">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Ara
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Kullanıcı</th>
                                    <th>İşlem</th>
                                    <th>Açıklama</th>
                                    <th>IP Adresi</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['username']); ?></td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td><?php echo htmlspecialchars($log['description']); ?></td>
                                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                    <td><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 
 