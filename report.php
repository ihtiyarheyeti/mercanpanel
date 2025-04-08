<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/Settings.php';
require_once 'includes/Statistics.php';

// Oturum kontrolü
checkLogin();

// Settings ve Statistics sınıflarını başlat
$settings = Settings::getInstance($conn);
$statistics = new Statistics($conn);

// Rapor ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: statistics.php');
    exit;
}

// Raporu al
$report = $statistics->getReport($_GET['id']);

if (!$report) {
    $_SESSION['error'] = "Rapor bulunamadı.";
    header('Location: statistics.php');
    exit;
}

// Rapor verilerini çöz
$report_data = json_decode($report['report_data'], true);

// Sayfa başlığı
$page_title = "Rapor: " . $report['title'];

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
                        <a class="nav-link active" href="#overview" data-bs-toggle="tab">
                            <i class="fas fa-info-circle"></i> Genel Bilgiler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#data" data-bs-toggle="tab">
                            <i class="fas fa-database"></i> Rapor Verileri
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#charts" data-bs-toggle="tab">
                            <i class="fas fa-chart-bar"></i> Grafikler
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Ana İçerik -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo htmlspecialchars($report['title']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Yazdır
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                    </div>
                </div>
            </div>

            <div class="tab-content">
                <!-- Genel Bilgiler -->
                <div class="tab-pane fade show active" id="overview">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Rapor Bilgileri</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Oluşturan:</strong> <?php echo htmlspecialchars($report['username']); ?></p>
                                    <p><strong>Oluşturulma Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($report['created_at'])); ?></p>
                                    <p><strong>Şablon:</strong> <?php echo htmlspecialchars($report['template_name']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Açıklama:</strong></p>
                                    <p><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rapor Verileri -->
                <div class="tab-pane fade" id="data">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Rapor Verileri</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Anahtar</th>
                                            <th>Değer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($report_data['data'] as $key => $value) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($key) . "</td>";
                                            if (is_array($value)) {
                                                echo "<td><pre>" . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) . "</pre></td>";
                                            } else {
                                                echo "<td>" . htmlspecialchars($value) . "</td>";
                                            }
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grafikler -->
                <div class="tab-pane fade" id="charts">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Kullanıcı İstatistikleri</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="userStatsChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Dosya İstatistikleri</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="fileStatsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Grafik verilerini hazırla
const reportData = <?php echo json_encode($report_data); ?>;

// Kullanıcı istatistikleri grafiği
new Chart(document.getElementById('userStatsChart'), {
    type: 'bar',
    data: {
        labels: ['Toplam Kullanıcı', 'Aktif Kullanıcı', 'Son 30 Gün'],
        datasets: [{
            label: 'Kullanıcı İstatistikleri',
            data: [
                reportData.data.total_users || 0,
                reportData.data.active_users || 0,
                reportData.data.recent_logins || 0
            ],
            backgroundColor: [
                'rgb(75, 192, 192)',
                'rgb(54, 162, 235)',
                'rgb(255, 205, 86)'
            ]
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Dosya istatistikleri grafiği
new Chart(document.getElementById('fileStatsChart'), {
    type: 'line',
    data: {
        labels: ['Toplam Dosya', 'Toplam İndirme', 'Son 30 Gün'],
        datasets: [{
            label: 'Dosya İstatistikleri',
            data: [
                reportData.data.total_files || 0,
                reportData.data.total_downloads || 0,
                reportData.data.recent_uploads || 0
            ],
            borderColor: 'rgb(255, 99, 132)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// PDF'e dışa aktar
function exportToPDF() {
    // PDF oluşturma işlemi
    alert('PDF dışa aktarma özelliği yakında eklenecek.');
}

// Excel'e dışa aktar
function exportToExcel() {
    // Excel oluşturma işlemi
    alert('Excel dışa aktarma özelliği yakında eklenecek.');
}
</script> 