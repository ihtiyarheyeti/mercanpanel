<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/UserLogger.php';

// Oturum kontrolü
checkLogin();

// Sadece adminler logları görebilir
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$logger = new UserLogger($conn);

// Filtreleme
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

// Logları al
$logs = $logger->getLogs($user_id, $limit);

// Kullanıcı listesi
$stmt = $conn->query("SELECT id, username FROM users ORDER BY username");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Aktivite Logları";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Aktivite Logları</h5>
        </div>
        <div class="card-body">
            <!-- Filtreler -->
            <form method="get" class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Kullanıcı</label>
                            <select name="user_id" class="form-control">
                                <option value="">Tüm Kullanıcılar</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $user_id == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Kayıt Sayısı</label>
                            <select name="limit" class="form-control">
                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                                <option value="200" <?php echo $limit == 200 ? 'selected' : ''; ?>>200</option>
                                <option value="500" <?php echo $limit == 500 ? 'selected' : ''; ?>>500</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary form-control">Filtrele</button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Log Tablosu -->
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Kullanıcı</th>
                            <th>İşlem</th>
                            <th>Açıklama</th>
                            <th>IP Adresi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($log['username']); ?></td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                <td><?php echo htmlspecialchars($log['description']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 
 