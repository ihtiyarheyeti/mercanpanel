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

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$settings = Settings::getInstance($conn);
$current_user_id = $_SESSION['user_id'];

// Tüm bildirimleri okundu olarak işaretle
if (isset($_GET['mark_all_read']) && $_GET['mark_all_read'] == 1) {
    try {
        $stmt = $conn->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = ?
        ");
        $stmt->execute([$current_user_id]);
        $_SESSION['success'] = 'Tüm bildirimler okundu olarak işaretlendi.';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Bildirimler işaretlenirken bir hata oluştu.';
    }
    
    header('Location: notifications.php');
    exit;
}

// Tek bir bildirimi okundu olarak işaretle
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = (int)$_GET['mark_read'];
    
    try {
        $stmt = $conn->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notification_id, $current_user_id]);
        
        // Eğer bildirimde bir link varsa, o sayfaya yönlendir
        $stmt = $conn->prepare("
            SELECT link FROM notifications 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notification_id, $current_user_id]);
        $link = $stmt->fetchColumn();
        
        if ($link) {
            header('Location: ' . $link);
            exit;
        }
        
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Bildirim işaretlenirken bir hata oluştu.';
    }
    
    header('Location: notifications.php');
    exit;
}

// Bildirimi sil
if (isset($_POST['delete_notification']) && isset($_POST['notification_id']) && isset($_POST['csrf_token'])) {
    if ($_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $notification_id = (int)$_POST['notification_id'];
        
        try {
            $stmt = $conn->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notification_id, $current_user_id]);
            $_SESSION['success'] = 'Bildirim başarıyla silindi.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Bildirim silinirken bir hata oluştu.';
        }
    } else {
        $_SESSION['error'] = 'Güvenlik doğrulaması başarısız.';
    }
    
    header('Location: notifications.php');
    exit;
}

// Bildirimleri al
$notifications = [];
try {
    $stmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$current_user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = 'Bildirimler alınırken bir hata oluştu.';
}

// Okunmamış bildirim sayısını al
$unread_count = 0;
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM notifications 
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$current_user_id]);
    $unread_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Hata olursa sessizce devam et
}

// Sayfalama için değişkenler
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$total_notifications = count($notifications);
$total_pages = ceil($total_notifications / $limit);
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * $limit;

// Sayfadaki bildirimler
$page_notifications = array_slice($notifications, $offset, $limit);

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Bildirimler <?php if ($unread_count > 0): ?><span class="badge bg-danger"><?php echo $unread_count; ?></span><?php endif; ?></h1>
    <div>
        <?php if (count($notifications) > 0): ?>
            <a href="notifications.php?mark_all_read=1" class="btn btn-outline-primary">
                <i class="fas fa-check-double me-1"></i> Tümünü Okundu İşaretle
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Tüm Bildirimler</h5>
        <span class="text-muted">Toplam: <?php echo $total_notifications; ?> bildirim</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($page_notifications)): ?>
            <div class="p-4 text-center">
                <i class="fas fa-bell-slash fa-3x mb-3 text-muted"></i>
                <p>Herhangi bir bildiriminiz bulunmamaktadır.</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($page_notifications as $notification): ?>
                    <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'list-group-item-primary'; ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <?php if ($notification['link']): ?>
                                <a href="notifications.php?mark_read=<?php echo $notification['id']; ?>" class="text-decoration-none text-body stretched-link">
                                    <h6 class="mb-1"><?php echo h($notification['title']); ?></h6>
                                </a>
                            <?php else: ?>
                                <h6 class="mb-1"><?php echo h($notification['title']); ?></h6>
                            <?php endif; ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                <button type="submit" name="delete_notification" class="btn btn-sm btn-link text-danger p-0 z-index-1" title="Sil" onclick="return confirm('Bu bildirimi silmek istediğinizden emin misiniz?');">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                        <p class="mb-1"><?php echo h($notification['message']); ?></p>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">
                                <?php if (!$notification['is_read']): ?>
                                    <span class="badge bg-info">Yeni</span>
                                <?php endif; ?>
                                <i class="fas fa-<?php echo getNotificationIcon($notification['type']); ?> ms-1"></i>
                            </small>
                            <small class="text-muted"><?php echo formatDate($notification['created_at']); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="p-3">
                    <nav aria-label="Sayfalama">
                        <ul class="pagination justify-content-center mb-0">
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
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Bildirim Tercihleri</h5>
    </div>
    <div class="card-body">
        <form method="post" action="notification_settings.php">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                               <?php echo $settings->get('user_' . $current_user_id . '_email_notifications', 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="email_notifications">E-posta Bildirimleri</label>
                    </div>
                    <div class="form-text">Önemli olaylarda e-posta bildirimleri alın.</div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="system_notifications" name="system_notifications" 
                               <?php echo $settings->get('user_' . $current_user_id . '_system_notifications', 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="system_notifications">Sistem Bildirimleri</label>
                    </div>
                    <div class="form-text">Sistem içi bildirimleri alın.</div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="message_notifications" name="message_notifications" 
                               <?php echo $settings->get('user_' . $current_user_id . '_message_notifications', 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="message_notifications">Mesaj Bildirimleri</label>
                    </div>
                    <div class="form-text">Yeni mesajlar için bildirim alın.</div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="login_notifications" name="login_notifications" 
                               <?php echo $settings->get('user_' . $current_user_id . '_login_notifications', 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="login_notifications">Giriş Bildirimleri</label>
                    </div>
                    <div class="form-text">Hesabınıza yeni giriş yapıldığında bildirim alın.</div>
                </div>
            </div>
            
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Tercihleri Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 
 