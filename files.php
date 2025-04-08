<?php
require_once 'config/database.php';
require_once 'includes/Settings.php';
require_once 'includes/FileManager.php';
require_once 'includes/helpers.php';

session_start();

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$settings = Settings::getInstance($conn);
$fileManager = new FileManager($conn);

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Sayfalama için parametreler
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Arama parametresi
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Dosya yükleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    try {
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        $file_id = $fileManager->uploadFile($_FILES['file'], $_SESSION['user_id'], $is_public);
        $_SESSION['success'] = 'Dosya başarıyla yüklendi.';
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: files.php');
    exit;
}

// Dosya silme işlemi
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $fileManager->deleteFile($_GET['delete'], $_SESSION['user_id']);
        $_SESSION['success'] = 'Dosya başarıyla silindi.';
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: files.php');
    exit;
}

// Dosyaları listele
try {
    $where = '';
    $params = [];
    if (!empty($search)) {
        $where = "WHERE f.original_filename LIKE ? OR u.username LIKE ?";
        $params = ["%$search%", "%$search%"];
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) FROM files f LEFT JOIN users u ON f.user_id = u.id $where");
    $stmt->execute($params);
    $total_files = $stmt->fetchColumn();
    
    $total_pages = ceil($total_files / $per_page);
    
    $sql = "SELECT f.*, u.username as uploader_name 
            FROM files f 
            LEFT JOIN users u ON f.user_id = u.id 
            $where 
            ORDER BY f.created_at DESC 
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    array_push($params, $per_page, $offset);
    $stmt->execute($params);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Dosya listeleme hatası: " . $e->getMessage());
    $_SESSION['error'] = 'Dosyalar listelenirken bir hata oluştu.';
    $files = [];
    $total_pages = 0;
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Dosya Yönetimi</h5>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click();">
                        <i class="fas fa-upload"></i> Dosya Yükle
                    </button>
                </div>
                <div class="card-body">
                    <!-- Gizli Form -->
                    <form action="files.php" method="POST" enctype="multipart/form-data" id="uploadForm" style="display: none;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="file" name="file" id="fileInput" onchange="showFileDetails(this);">
                    </form>

                    <!-- Dosya Detay Formu -->
                    <div id="fileDetailsForm" style="display: none;" class="mb-4">
                        <form action="files.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Seçilen Dosya</label>
                                        <input type="text" class="form-control" id="selectedFileName" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Kategori</label>
                                        <select name="category" class="form-control" required>
                                            <option value="">Kategori Seçin</option>
                                            <option value="dokuman">Döküman</option>
                                            <option value="resim">Resim</option>
                                            <option value="video">Video</option>
                                            <option value="ses">Ses</option>
                                            <option value="diger">Diğer</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Etiketler</label>
                                        <input type="text" name="tags" class="form-control" placeholder="Virgülle ayırın">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Dosya hakkında kısa açıklama"></textarea>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="is_public" class="form-check-input" id="isPublic">
                                    <label class="form-check-label" for="isPublic">Herkese açık</label>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-secondary" onclick="cancelUpload()">İptal</button>
                                <button type="submit" class="btn btn-primary">Yükle</button>
                            </div>
                        </form>
                    </div>

                    <!-- Filtreler -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-select" id="categoryFilter" onchange="filterFiles()">
                                <option value="">Tüm Kategoriler</option>
                                <option value="dokuman">Döküman</option>
                                <option value="resim">Resim</option>
                                <option value="video">Video</option>
                                <option value="ses">Ses</option>
                                <option value="diger">Diğer</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="searchInput" placeholder="Dosya adı, etiket veya açıklama ara..." oninput="filterFiles()">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="sortFilter" onchange="filterFiles()">
                                <option value="newest">En Yeni</option>
                                <option value="oldest">En Eski</option>
                                <option value="name">İsme Göre</option>
                                <option value="size">Boyuta Göre</option>
                            </select>
                        </div>
                    </div>

                    <?php if (empty($files)): ?>
                    <div class="alert alert-info">
                        Henüz dosya yüklenmemiş.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Dosya Adı</th>
                                    <th>Boyut</th>
                                    <th>Tip</th>
                                    <th>Yükleyen</th>
                                    <th>Yükleme Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($files as $file): ?>
                                <tr>
                                    <td><?= htmlspecialchars($file['original_filename']) ?></td>
                                    <td><?= formatFileSize($file['file_size']) ?></td>
                                    <td><?= htmlspecialchars($file['file_type']) ?></td>
                                    <td><?= htmlspecialchars($file['uploader_name']) ?></td>
                                    <td><?= formatDate($file['created_at']) ?></td>
                                    <td>
                                        <?php if (in_array($file['file_type'], ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'csv', 'md', 'html', 'css', 'js', 'php', 'json', 'xml'])): ?>
                                        <a href="preview.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-secondary" title="Önizle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="download.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-info" title="İndir">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <?php if ($_SESSION['role'] === 'admin' || $file['user_id'] === $_SESSION['user_id']): ?>
                                        <a href="files.php?delete=<?= $file['id'] ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Bu dosyayı silmek istediğinize emin misiniz?')" title="Sil">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Sayfalama">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page-1 ?><?= !empty($search) ? '&search='.$search : '' ?>">Önceki</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search='.$search : '' ?>"><?= $i ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page+1 ?><?= !empty($search) ? '&search='.$search : '' ?>">Sonraki</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showFileDetails(input) {
    if (input.files && input.files[0]) {
        document.getElementById('selectedFileName').value = input.files[0].name;
        document.getElementById('fileDetailsForm').style.display = 'block';
    }
}

function cancelUpload() {
    document.getElementById('uploadForm').reset();
    document.getElementById('fileDetailsForm').style.display = 'none';
}

function filterFiles() {
    // AJAX ile filtreleme yapılacak
    const category = document.getElementById('categoryFilter').value;
    const search = document.getElementById('searchInput').value;
    const sort = document.getElementById('sortFilter').value;
    
    // AJAX isteği gönder
    // ...
}

document.addEventListener('DOMContentLoaded', function() {
    // Form gönderildiğinde
    document.getElementById('uploadForm').addEventListener('submit', function() {
        const submitButton = document.getElementById('uploadButton');
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Yükleniyor...';
    });

    // Başarı ve hata mesajlarını göster
    <?php if (isset($_SESSION['success'])): ?>
        alert('<?= htmlspecialchars($_SESSION['success']) ?>');
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        alert('<?= htmlspecialchars($_SESSION['error']) ?>');
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
});
</script>

<?php include 'includes/footer.php'; ?> 