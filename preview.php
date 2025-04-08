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

// Dosya ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: files.php');
    exit;
}

$fileManager = new FileManager($conn);

try {
    $file = $fileManager->previewFile($_GET['id'], $_SESSION['user_id']);
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: files.php');
    exit;
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Dosya Önizleme: <?= htmlspecialchars($file['name']) ?></h5>
                    <div>
                        <a href="files.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Geri
                        </a>
                        <a href="download.php?id=<?= $file['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-download"></i> İndir
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($file['preview_type'] === 'image'): ?>
                        <div class="text-center">
                            <img src="<?= htmlspecialchars($file['path']) ?>" class="img-fluid" alt="<?= htmlspecialchars($file['name']) ?>" style="max-height: 80vh;">
                        </div>
                    <?php elseif ($file['preview_type'] === 'pdf'): ?>
                        <div class="ratio ratio-16x9">
                            <iframe src="<?= htmlspecialchars($file['path']) ?>" allowfullscreen></iframe>
                        </div>
                    <?php elseif ($file['preview_type'] === 'text' || $file['preview_type'] === 'code'): ?>
                        <?php
                        $content = file_get_contents($file['path']);
                        if ($content === false) {
                            echo '<div class="alert alert-danger">Dosya içeriği okunamadı.</div>';
                        } else {
                            if (mb_strlen($content) > 1024 * 100) { // 100KB'dan büyük dosyaları kısalt
                                $content = mb_substr($content, 0, 1024 * 100) . "\n\n... (Dosya çok büyük olduğu için kısaltıldı)";
                            }
                        ?>
                            <pre class="bg-light p-3 rounded" style="max-height: 80vh; overflow: auto;"><code><?= htmlspecialchars($content) ?></code></pre>
                        <?php } ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Bu dosya türü için önizleme kullanılamıyor.<br>
                            Dosya türü: <?= htmlspecialchars($file['type']) ?><br>
                            Dosya boyutu: <?= formatFileSize($file['size']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 