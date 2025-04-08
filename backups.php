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

// Yönetici değilse dashboard'a yönlendir
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Settings sınıfını başlat
$settings = Settings::getInstance($conn);
$backup_path = rtrim($settings->get('backup_path', 'backups/'), '/') . '/';

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Yedek silme işlemi
if (isset($_POST['delete_backup']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $backup_file = $_POST['backup_file'] ?? '';
    $full_path = $backup_path . basename($backup_file);
    
    if (file_exists($full_path) && is_file($full_path)) {
        if (unlink($full_path)) {
            $_SESSION['success'] = 'Yedek dosyası başarıyla silindi.';
        } else {
            $_SESSION['error'] = 'Yedek dosyası silinemedi.';
        }
    } else {
        $_SESSION['error'] = 'Yedek dosyası bulunamadı.';
    }
    
    header('Location: backups.php');
    exit;
}

// Yedek indirme işlemi
if (isset($_GET['download'])) {
    $download_file = basename($_GET['download']);
    $full_path = $backup_path . $download_file;
    
    if (file_exists($full_path) && is_file($full_path)) {
        // İndirme işlemi
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $download_file . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($full_path));
        readfile($full_path);
        exit;
    } else {
        $_SESSION['error'] = 'İndirmek istediğiniz dosya bulunamadı.';
    }
}

// Yedekleri getir
$backups = [];

if (file_exists($backup_path)) {
    // Veritabanı yedekleri
    foreach (glob($backup_path . 'db_backup_*.sql') as $file) {
        $backups[] = [
            'name' => basename($file),
            'type' => 'database',
            'size' => filesize($file),
            'date' => filemtime($file)
        ];
    }
    
    // Dosya yedekleri
    foreach (glob($backup_path . 'files_backup_*.zip') as $file) {
        $backups[] = [
            'name' => basename($file),
            'type' => 'files',
            'size' => filesize($file),
            'date' => filemtime($file)
        ];
    }
    
    // Tarihe göre sırala (en yeni en üstte)
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

// Sayfalama için değişkenler
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$total_backups = count($backups);
$total_pages = ceil($total_backups / $limit);
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * $limit;

// Sayfadaki yedekler
$page_backups = array_slice($backups, $offset, $limit);

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Sistem Yedekleri</h1>
    <div>
        <a href="cron.php" class="btn btn-info" target="_blank">
            <i class="fas fa-sync me-1"></i> Cron Görevi Çalıştır
        </a>
        <button type="button" class="btn btn-primary" id="manual_backup">
            <i class="fas fa-download me-1"></i> Manuel Yedek
        </button>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Yedek Listesi</h5>
        <small>Toplam: <?php echo $total_backups; ?> yedek</small>
    </div>
    <div class="card-body">
        <?php if (empty($page_backups)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Henüz oluşturulmuş yedek bulunmamaktadır.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Dosya Adı</th>
                            <th>Tip</th>
                            <th>Boyut</th>
                            <th>Tarih</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($page_backups as $backup): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-<?php echo $backup['type'] === 'database' ? 'database' : 'file-archive'; ?> me-2 text-<?php echo $backup['type'] === 'database' ? 'info' : 'warning'; ?>"></i>
                                        <?php echo h($backup['name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $backup['type'] === 'database' ? 'info' : 'warning'; ?>">
                                        <?php echo $backup['type'] === 'database' ? 'Veritabanı' : 'Dosyalar'; ?>
                                    </span>
                                </td>
                                <td><?php echo formatFileSize($backup['size']); ?></td>
                                <td><?php echo formatDate(date('Y-m-d H:i:s', $backup['date'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="backups.php?download=<?php echo urlencode($backup['name']); ?>" class="btn btn-sm btn-outline-primary" title="İndir">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-backup" 
                                                data-backup="<?php echo h($backup['name']); ?>" title="Sil">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Sayfalama" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>">Önceki</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>">Sonraki</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Yedekleme Bilgileri</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-bold">Yedekleme Klasörü:</label>
                    <div><?php echo h($backup_path); ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Otomatik Yedekleme:</label>
                    <div>
                        <?php if ($settings->get('auto_backup', 0)): ?>
                            <span class="badge bg-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Devre Dışı</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Yedekleme Sıklığı:</label>
                    <div>
                        <?php 
                        $frequency = $settings->get('backup_frequency', 'daily');
                        $label = [
                            'daily' => 'Günlük',
                            'weekly' => 'Haftalık',
                            'monthly' => 'Aylık'
                        ][$frequency] ?? 'Günlük';
                        echo $label;
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-bold">Son Yedekleme:</label>
                    <div>
                        <?php 
                        $last_time = $settings->get('last_backup_time', 0);
                        echo $last_time ? formatDate(date('Y-m-d H:i:s', $last_time)) : 'Henüz yedek alınmamış';
                        ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Yedek Saklama Süresi:</label>
                    <div><?php echo $settings->get('backup_retention', 7); ?> gün</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Dosya Yedeği:</label>
                    <div>
                        <?php if ($settings->get('backup_include_files', 1)): ?>
                            <span class="badge bg-success">Dahil</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Dahil Değil</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Yedek Silme Modal -->
<div class="modal fade" id="deleteBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yedek Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bu yedeği silmek istediğinizden emin misiniz?</p>
                <p class="text-danger fw-bold" id="backupFileName"></p>
            </div>
            <div class="modal-footer">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="backup_file" id="backupFileInput">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="delete_backup" class="btn btn-danger">Sil</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manuel yedekleme
    document.getElementById('manual_backup').addEventListener('click', function() {
        if (confirm('Veritabanı ve dosyaların manuel yedeği alınacak. Devam etmek istiyor musunuz?')) {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Yedekleniyor...';
            
            fetch('backup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({action: 'manual_backup'})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Yedekleme başarıyla tamamlandı: ' + data.message);
                    location.reload();
                } else {
                    throw new Error(data.message || 'Yedekleme işlemi başarısız oldu.');
                }
            })
            .catch(error => {
                alert('Hata: ' + error.message);
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-download me-1"></i> Manuel Yedek';
            });
        }
    });
    
    // Yedek silme
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteBackupModal'));
    const backupFileName = document.getElementById('backupFileName');
    const backupFileInput = document.getElementById('backupFileInput');
    
    document.querySelectorAll('.delete-backup').forEach(button => {
        button.addEventListener('click', function() {
            const backupName = this.dataset.backup;
            backupFileName.textContent = backupName;
            backupFileInput.value = backupName;
            deleteModal.show();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?> 