<?php
require_once 'includes/functions.php';
checkLogin();

$conn = connectDB();
$message = '';
$error = '';

// Hizmet silme işlemi
if (isset($_GET['delete']) && $_SESSION['role'] === 'admin') {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = 'Hizmet başarıyla silindi.';
    } else {
        $error = 'Hizmet silinirken bir hata oluştu.';
    }
}

// Hizmet ekleme/düzenleme formu gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $image_url = $_POST['image_url'] ?? '';
    $id = $_POST['id'] ?? null;
    
    if (!empty($title)) {
        if ($id) {
            // Düzenleme
            $stmt = $conn->prepare("UPDATE services SET title = ?, description = ?, image_url = ? WHERE id = ?");
            $stmt->bind_param("sssi", $title, $description, $image_url, $id);
        } else {
            // Ekleme
            $stmt = $conn->prepare("INSERT INTO services (title, description, image_url, created_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $title, $description, $image_url, $_SESSION['user_id']);
        }
        
        if ($stmt->execute()) {
            $message = $id ? 'Hizmet başarıyla güncellendi.' : 'Hizmet başarıyla eklendi.';
        } else {
            $error = 'İşlem sırasında bir hata oluştu.';
        }
    } else {
        $error = 'Lütfen başlık alanını doldurun.';
    }
}

// Hizmetleri listele
$stmt = $conn->prepare("SELECT s.*, u.username as created_by_name FROM services s LEFT JOIN users u ON s.created_by = u.id ORDER BY s.created_at DESC");
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Düzenleme için hizmet bilgilerini al
$editService = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editService = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getPageTitle('Hizmetler'); ?></title>
    <link rel="stylesheet" href="<?php echo getThemePath(); ?>style.css">
</head>
<body>
    <div class="dashboard">
        <nav class="sidebar">
            <div class="logo">
                <img src="<?php echo getSetting('site_logo'); ?>" alt="Logo">
                <h2><?php echo getSetting('site_title'); ?></h2>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li class="active"><a href="services.php">Hizmetler</a></li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="settings.php">Ayarlar</a></li>
                    <li><a href="users.php">Kullanıcılar</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Çıkış Yap</a></li>
            </ul>
        </nav>
        
        <main class="content">
            <header>
                <h1>Hizmet Yönetimi</h1>
                <a href="services.php?action=add" class="btn btn-primary">Yeni Hizmet Ekle</a>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo escape($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo escape($error); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['action']) && $_GET['action'] === 'add' || isset($_GET['edit'])): ?>
                <div class="card">
                    <h2><?php echo $editService ? 'Hizmet Düzenle' : 'Yeni Hizmet Ekle'; ?></h2>
                    <form method="POST" action="">
                        <?php if ($editService): ?>
                            <input type="hidden" name="id" value="<?php echo $editService['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="title">Başlık</label>
                            <input type="text" id="title" name="title" value="<?php echo $editService ? escape($editService['title']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Açıklama</label>
                            <textarea id="description" name="description" rows="4"><?php echo $editService ? escape($editService['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="image_url">Resim URL</label>
                            <input type="url" id="image_url" name="image_url" value="<?php echo $editService ? escape($editService['image_url']) : ''; ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary"><?php echo $editService ? 'Güncelle' : 'Ekle'; ?></button>
                        <a href="services.php" class="btn btn-secondary">İptal</a>
                    </form>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Başlık</th>
                                <th>Açıklama</th>
                                <th>Oluşturan</th>
                                <th>Tarih</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                                <tr>
                                    <td><?php echo $service['id']; ?></td>
                                    <td><?php echo escape($service['title']); ?></td>
                                    <td><?php echo escape(substr($service['description'], 0, 50)) . '...'; ?></td>
                                    <td><?php echo escape($service['created_by_name']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($service['created_at'])); ?></td>
                                    <td>
                                        <a href="services.php?edit=<?php echo $service['id']; ?>" class="btn btn-sm btn-primary">Düzenle</a>
                                        <?php if ($_SESSION['role'] === 'admin'): ?>
                                            <a href="services.php?delete=<?php echo $service['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu hizmeti silmek istediğinizden emin misiniz?')">Sil</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 
 