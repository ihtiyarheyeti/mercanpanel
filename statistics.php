<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Settings.php';
require_once 'includes/helpers.php';

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$settings = Settings::getInstance($conn);

// Haftalık ve aylık grafikler için tarih aralıkları
$current_date = date('Y-m-d');
$week_ago = date('Y-m-d', strtotime('-7 days'));
$month_ago = date('Y-m-d', strtotime('-30 days'));

try {
    // Toplam kullanıcı, dosya ve dosya boyutunu al
    $stmt = $conn->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT COUNT(*) FROM files");
    $total_files = $stmt->fetchColumn();
    
    $stmt = $conn->query("SELECT SUM(file_size) FROM files");
    $total_file_size = $stmt->fetchColumn() ?: 0;
    
    // Son 7 gün için kullanıcı girişleri
    try {
        $stmt = $conn->prepare("
            SELECT DATE(created_at) as login_date, COUNT(*) as login_count 
            FROM user_logs 
            WHERE created_at >= ? AND action = 'login'
            GROUP BY DATE(created_at) 
            ORDER BY login_date
        ");
        $stmt->execute([$week_ago]);
        $weekly_logins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // user_logs tablosu yoksa veya başka bir hata oluşursa boş dizi kullan
        $weekly_logins = [];
    }
    
    // Son 30 gün için yüklenen dosyalar
    $stmt = $conn->prepare("
        SELECT DATE(upload_date) as upload_date, COUNT(*) as upload_count 
        FROM files 
        WHERE upload_date >= ? 
        GROUP BY DATE(upload_date) 
        ORDER BY upload_date
    ");
    $stmt->execute([$month_ago]);
    $monthly_uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Dosya türlerine göre dağılım
    $stmt = $conn->query("
        SELECT 
            CASE 
                WHEN file_type LIKE 'image/%' THEN 'Resim'
                WHEN file_type LIKE 'application/pdf' THEN 'PDF'
                WHEN file_type LIKE 'application/msword' OR file_type LIKE 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' THEN 'Word'
                WHEN file_type LIKE 'application/vnd.ms-excel' OR file_type LIKE 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' THEN 'Excel'
                WHEN file_type LIKE 'application/zip' OR file_type LIKE 'application/x-rar-compressed' THEN 'Arşiv'
                WHEN file_type LIKE 'video/%' THEN 'Video'
                WHEN file_type LIKE 'audio/%' THEN 'Ses'
                ELSE 'Diğer'
            END as file_category,
            COUNT(*) as count
        FROM files
        GROUP BY file_category
        ORDER BY count DESC
    ");
    $file_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kullanıcı rollerine göre dağılım
    $stmt = $conn->query("
        SELECT role, COUNT(*) as count
        FROM users
        GROUP BY role
        ORDER BY count DESC
    ");
    $user_roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // En çok indirilen dosyalar
    try {
        $stmt = $conn->query("
            SELECT f.*, u.username
            FROM files f
            JOIN users u ON f.uploaded_by = u.id
            ORDER BY f.download_count DESC
            LIMIT 10
        ");
        $top_downloads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // download_count sütunu yoksa veya başka bir hata oluşursa boş dizi kullan
        $top_downloads = [];
    }
    
} catch (PDOException $e) {
    error_log("İstatistik hatası: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Sistem İstatistikleri</h1>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-left-primary h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs text-uppercase mb-1 text-primary">Toplam Kullanıcı</div>
                        <div class="h5 mb-0 font-weight-bold"><?= $total_users ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-left-success h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs text-uppercase mb-1 text-success">Toplam Dosya</div>
                        <div class="h5 mb-0 font-weight-bold"><?= $total_files ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-file fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-left-info h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs text-uppercase mb-1 text-info">Toplam Dosya Boyutu</div>
                        <div class="h5 mb-0 font-weight-bold"><?= formatFileSize($total_file_size) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-database fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-left-warning h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs text-uppercase mb-1 text-warning">Disk Kullanımı</div>
                        <div class="progress" style="height: 15px;">
                            <?php 
                            $disk_usage = round(($total_file_size / (1024 * 1024 * 1024)) / 100, 2);
                            $usage_percent = min(100, max(0, $disk_usage * 100));
                            ?>
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $usage_percent ?>%">
                                <?= $usage_percent ?>%
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-hdd fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Haftalık Kullanıcı Girişleri</h5>
            </div>
            <div class="card-body">
                <canvas id="weeklyLoginsChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Aylık Dosya Yüklemeleri</h5>
            </div>
            <div class="card-body">
                <canvas id="monthlyUploadsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Dosya Türleri Dağılımı</h5>
            </div>
            <div class="card-body">
                <canvas id="fileTypesChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Kullanıcı Rolleri Dağılımı</h5>
            </div>
            <div class="card-body">
                <canvas id="userRolesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">En Çok İndirilen Dosyalar</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Dosya Adı</th>
                        <th>Tip</th>
                        <th>Boyut</th>
                        <th>Yükleyen</th>
                        <th>İndirme Sayısı</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_downloads)): ?>
                    <tr>
                        <td colspan="5" class="text-center">Henüz dosya indirme verisi bulunmamaktadır.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($top_downloads as $file): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="<?= getFileIcon($file['original_name']) ?> me-2"></i>
                                    <?= h($file['original_name']) ?>
                                </div>
                            </td>
                            <td><?= h($file['file_type']) ?></td>
                            <td><?= formatFileSize($file['file_size']) ?></td>
                            <td><?= h($file['username']) ?></td>
                            <td>
                                <span class="badge bg-primary"><?= $file['download_count'] ?? 0 ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js Kütüphanesi -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Haftalık giriş grafiği
    const weeklyLoginsData = <?= json_encode($weekly_logins) ?>;
    const weeklyLabels = weeklyLoginsData.length ? weeklyLoginsData.map(item => formatDate(item.login_date)) : ['Veri Yok'];
    const weeklyValues = weeklyLoginsData.length ? weeklyLoginsData.map(item => item.login_count) : [0];
    
    new Chart(document.getElementById('weeklyLoginsChart'), {
        type: 'line',
        data: {
            labels: weeklyLabels,
            datasets: [{
                label: 'Giriş Sayısı',
                data: weeklyValues,
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: '#fff',
                pointRadius: 3,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Aylık yükleme grafiği
    const monthlyUploadsData = <?= json_encode($monthly_uploads) ?>;
    const monthlyLabels = monthlyUploadsData.length ? monthlyUploadsData.map(item => formatDate(item.upload_date)) : ['Veri Yok'];
    const monthlyValues = monthlyUploadsData.length ? monthlyUploadsData.map(item => item.upload_count) : [0];
    
    new Chart(document.getElementById('monthlyUploadsChart'), {
        type: 'bar',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'Yükleme Sayısı',
                data: monthlyValues,
                backgroundColor: 'rgba(28, 200, 138, 0.8)',
                borderRadius: 4
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Dosya türleri grafiği
    const fileTypesData = <?= json_encode($file_types) ?>;
    const fileTypeLabels = fileTypesData.length ? fileTypesData.map(item => item.file_category) : ['Veri Yok'];
    const fileTypeValues = fileTypesData.length ? fileTypesData.map(item => item.count) : [0];
    const fileTypeColors = [
        'rgba(78, 115, 223, 0.8)',
        'rgba(28, 200, 138, 0.8)',
        'rgba(246, 194, 62, 0.8)',
        'rgba(231, 74, 59, 0.8)',
        'rgba(54, 185, 204, 0.8)',
        'rgba(133, 135, 150, 0.8)',
        'rgba(105, 0, 132, 0.8)',
        'rgba(0, 150, 136, 0.8)'
    ];
    
    new Chart(document.getElementById('fileTypesChart'), {
        type: 'pie',
        data: {
            labels: fileTypeLabels,
            datasets: [{
                data: fileTypeValues,
                backgroundColor: fileTypeColors
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
    
    // Kullanıcı rolleri grafiği
    const userRolesData = <?= json_encode($user_roles) ?>;
    const userRoleLabels = userRolesData.length ? userRolesData.map(item => item.role.charAt(0).toUpperCase() + item.role.slice(1)) : ['Veri Yok'];
    const userRoleValues = userRolesData.length ? userRolesData.map(item => item.count) : [0];
    const userRoleColors = [
        'rgba(231, 74, 59, 0.8)',
        'rgba(246, 194, 62, 0.8)',
        'rgba(54, 185, 204, 0.8)'
    ];
    
    new Chart(document.getElementById('userRolesChart'), {
        type: 'doughnut',
        data: {
            labels: userRoleLabels,
            datasets: [{
                data: userRoleValues,
                backgroundColor: userRoleColors
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            },
            cutout: '65%'
        }
    });
    
    // Tarih formatı fonksiyonu
    function formatDate(dateString) {
        const options = { day: 'numeric', month: 'short' };
        return new Date(dateString).toLocaleDateString('tr-TR', options);
    }
});
</script>

<?php include 'includes/footer.php'; ?> 