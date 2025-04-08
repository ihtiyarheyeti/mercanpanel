<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/FileManager.php';

// Oturum kontrolü
checkLogin();

if (!isset($_GET['id'])) {
    header('Location: files.php');
    exit;
}

$fileManager = new FileManager($conn);
$user_id = $_SESSION['user_id'];
$file_id = (int)$_GET['id'];
$success = '';
$error = '';

try {
    // Dosya bilgilerini al
    $file = $fileManager->getFile($file_id);
    
    // Dosya bulunamadıysa
    if (!$file) {
        throw new Exception('Dosya bulunamadı.');
    }
    
    // Dosyaya erişim izni kontrolü
    if ($file['user_id'] != $user_id) {
        throw new Exception('Bu dosyayı düzenleme izniniz yok.');
    }
    
    // Form gönderildi mi?
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $description = $_POST['description'] ?? '';
        $is_public = isset($_POST['is_public']);
        $categories = $_POST['categories'] ?? [];
        
        // Dosya bilgilerini güncelle
        $fileManager->updateFile($file_id, [
            'description' => $description,
            'is_public' => $is_public,
            'categories' => $categories
        ]);
        
        $success = "Dosya başarıyla güncellendi.";
        
        // Dosya bilgilerini yeniden al
        $file = $fileManager->getFile($file_id);
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

$categories = $fileManager->listCategories();
$page_title = "Dosya Düzenle";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Dosya Düzenle</h5>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="form-group">
                            <label>Dosya Adı</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($file['original_name']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>Dosya Tipi</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst($file['file_type']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>Dosya Boyutu</label>
                            <input type="text" class="form-control" value="<?php echo formatFileSize($file['file_size']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Açıklama</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($file['description']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="categories">Kategoriler</label>
                            <select class="form-control" id="categories" name="categories[]" multiple>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo in_array($category['id'], array_column($file['categories'], 'id')) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_public" name="is_public" <?php echo $file['is_public'] ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="is_public">Herkese Açık</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                        <a href="files.php" class="btn btn-secondary">İptal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Dosya boyutunu formatla
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

include 'includes/footer.php';
?> 
 