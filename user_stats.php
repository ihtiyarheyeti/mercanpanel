<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/UserStats.php';
require_once 'includes/PermissionManager.php';

// Oturum kontrolü
checkLogin();

// İzin kontrolü
$permissionManager = new PermissionManager($conn);
if (!$permissionManager->hasPermission($_SESSION['user_id'], 'view_stats')) {
    header('Location: index.php');
    exit;
}

$userStats = new UserStats($conn);
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;

$totalUsers = $userStats->getTotalUsers();
$activeUsers = $userStats->getActiveUsers();
$newUsers = $userStats->getNewUsers($days);
$loginStats = $userStats->getLoginStats($days);
$topActions = $userStats->getTopActions();
$roleDistribution = $userStats->getRoleDistribution();

$page_title = "Kullanıcı İstatistikleri";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Kullanıcı İstatistikleri</h5>
                </div>
                <div class="card-body">
                    <form method="get" class="mb-3">
                        <div class="form-group">
                            <label for="days">Gün Sayısı</label>
                            <select class="form-control" id="days" name="days" onchange="this.form.submit()">
                                <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>Son 7 gün</option>
                                <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>Son 30 gün</option>
                                <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>>Son 90 gün</option>
                            </select>
                        </div>
                    </form>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Toplam Kullanıcı</h6>
                                    <h2 class="mb-0"><?php echo $totalUsers; ?></h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Aktif Kullanıcı</h6>
                                    <h2 class="mb-0"><?php echo $activeUsers; ?></h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Yeni Kullanıcı</h6>
                                    <h2 class="mb-0"><?php echo $newUsers; ?></h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Ortalama Günlük Giriş</h6>
                                    <h2 class="mb-0"><?php echo round(count($loginStats) / $days, 1); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">En Çok Yapılan İşlemler</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>İşlem</th>
                                                    <th>Sayı</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($topActions as $action): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($action['action']); ?></td>
                                                    <td><?php echo $action['count']; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Rol Dağılımı</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Rol</th>
                                                    <th>Sayı</th>
                                                    <th>Yüzde</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($roleDistribution as $role): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($role['role']); ?></td>
                                                    <td><?php echo $role['count']; ?></td>
                                                    <td><?php echo round(($role['count'] / $totalUsers) * 100, 1); ?>%</td>
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
            </div>
        </div>
    </div>
</div>

 
 