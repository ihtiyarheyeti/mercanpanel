<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/Statistics.php';

// Oturum kontrolü
checkLogin();

// İstatistik sınıfını başlat
$stats = new Statistics();

// Günlük istatistikleri güncelle
$stats->updateDailyStats();

// Son 7 günün istatistiklerini al
$start_date = date('Y-m-d', strtotime('-7 days'));
$end_date = date('Y-m-d');
$weekly_stats = $stats->getStatsByDateRange($start_date, $end_date);

// Rapor şablonlarını al
$stmt = $conn->query("SELECT * FROM report_templates");
$templates = $stmt->fetchAll();

// Rapor oluşturma işlemi
if (isset($_POST['generate_report'])) {
    try {
        $report_file = $stats->generateReport(
            $_POST['template_id'],
            $_SESSION['user_id'],
            $_POST['params'] ?? []
        );
        $success_message = "Rapor başarıyla oluşturuldu. <a href='$report_file' target='_blank'>İndir</a>";
    } catch (Exception $e) {
        $error_message = "Rapor oluşturulurken bir hata oluştu: " . $e->getMessage();
    }
}

// Sayfa başlığı
$page_title = "Dashboard";

// Header'ı dahil et
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sol Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="files.php">
                            <i class="fas fa-file"></i> Dosyalar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Kullanıcılar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i> Ayarlar
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Ana İçerik -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#reportModal">
                        <i class="fas fa-file-pdf"></i> Rapor Oluştur
                    </button>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- İstatistik Kartları -->
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Toplam Kullanıcı</h5>
                            <h2 class="card-text"><?php echo $weekly_stats[0]['total_users'] ?? 0; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Aktif Kullanıcı</h5>
                            <h2 class="card-text"><?php echo $weekly_stats[0]['active_users'] ?? 0; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Toplam Dosya</h5>
                            <h2 class="card-text"><?php echo $weekly_stats[0]['total_files'] ?? 0; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">Toplam İndirme</h5>
                            <h2 class="card-text"><?php echo $weekly_stats[0]['total_downloads'] ?? 0; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafikler -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Kullanıcı Aktivitesi</h5>
                            <canvas id="userActivityChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Dosya İstatistikleri</h5>
                            <canvas id="fileStatsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Son Aktiviteler -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Son Aktiviteler</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Kullanıcı</th>
                                    <th>Aktivite</th>
                                    <th>Tarih</th>
                                    <th>IP Adresi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $conn->query("
                                    SELECT u.username, ul.action, ul.created_at, ul.ip_address
                                    FROM user_logs ul
                                    JOIN users u ON ul.user_id = u.id
                                    ORDER BY ul.created_at DESC
                                    LIMIT 10
                                ");
                                while ($row = $stmt->fetch()):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['action']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Rapor Oluşturma Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rapor Oluştur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="template_id" class="form-label">Rapor Şablonu</label>
                        <select class="form-select" id="template_id" name="template_id" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($templates as $template): ?>
                            <option value="<?php echo $template['id']; ?>">
                                <?php echo htmlspecialchars($template['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                        <input type="date" class="form-control" id="start_date" name="params[start_date]">
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">Bitiş Tarihi</label>
                        <input type="date" class="form-control" id="end_date" name="params[end_date]">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="generate_report" class="btn btn-primary">Oluştur</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Kullanıcı aktivite grafiği
const userActivityCtx = document.getElementById('userActivityChart').getContext('2d');
new Chart(userActivityCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($weekly_stats, 'date')); ?>,
        datasets: [{
            label: 'Aktif Kullanıcı',
            data: <?php echo json_encode(array_column($weekly_stats, 'active_users')); ?>,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }, {
            label: 'Giriş Sayısı',
            data: <?php echo json_encode(array_column($weekly_stats, 'total_logins')); ?>,
            borderColor: 'rgb(255, 99, 132)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        }
    }
});

// Dosya istatistikleri grafiği
const fileStatsCtx = document.getElementById('fileStatsChart').getContext('2d');
new Chart(fileStatsCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($weekly_stats, 'date')); ?>,
        datasets: [{
            label: 'Yüklenen Dosya',
            data: <?php echo json_encode(array_column($weekly_stats, 'total_uploads')); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.5)'
        }, {
            label: 'İndirilen Dosya',
            data: <?php echo json_encode(array_column($weekly_stats, 'total_downloads')); ?>,
            backgroundColor: 'rgba(255, 159, 64, 0.5)'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?> 
 