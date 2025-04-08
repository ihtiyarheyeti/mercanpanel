<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Settings.php';
require_once 'includes/helpers.php';
require_once 'includes/NotificationManager.php';

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

// Aktif görüntülenen mesaj
$active_conversation = isset($_GET['conversation']) ? (int)$_GET['conversation'] : 0;
$conversation_user = null;

// Yeni mesaj gönderme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    // CSRF kontrolü
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Güvenlik doğrulaması başarısız.';
    } else {
        $recipient_id = isset($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : 0;
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        
        if ($recipient_id <= 0) {
            $_SESSION['error'] = 'Geçersiz alıcı.';
        } elseif (empty($message)) {
            $_SESSION['error'] = 'Mesaj boş olamaz.';
        } else {
            try {
                // Alıcının gerçekten var olduğunu kontrol et
                $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
                $stmt->execute([$recipient_id]);
                $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($recipient) {
                    // Mesajı kaydet
                    $stmt = $conn->prepare("
                        INSERT INTO messages (sender_id, recipient_id, message, created_at) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$current_user_id, $recipient_id, $message]);
                    
                    // Bildirim oluştur
                    $notificationManager = new NotificationManager($conn);
                    // Bildirim tercihlerini kontrol et
                    if ($notificationManager->shouldSendNotification($recipient_id, 'message')) {
                        $title = 'Yeni Mesaj';
                        $message_preview = strlen($message) > 50 ? substr($message, 0, 47) . '...' : $message;
                        $message_notification = $_SESSION['username'] . ' size bir mesaj gönderdi: ' . $message_preview;
                        $notificationManager->send($recipient_id, $title, $message_notification, 'message', 'messages.php?conversation=' . $current_user_id);
                    }
                    
                    $_SESSION['success'] = 'Mesaj başarıyla gönderildi.';
                } else {
                    $_SESSION['error'] = 'Alıcı bulunamadı.';
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
            }
        }
    }
    
    // Sayfayı yenile
    header('Location: messages.php' . ($active_conversation > 0 ? '?conversation=' . $active_conversation : ''));
    exit;
}

// Mesajı okundu olarak işaretleme
if (isset($_GET['mark_read']) && $active_conversation > 0) {
    try {
        $stmt = $conn->prepare("
            UPDATE messages 
            SET is_read = 1 
            WHERE recipient_id = ? AND sender_id = ?
        ");
        $stmt->execute([$current_user_id, $active_conversation]);
    } catch (PDOException $e) {
        // Hata olursa sessizce devam et
    }
}

// Konuşma listesini al
$conversations = [];
try {
    // En son mesajı aldığın veya gönderdiğin kullanıcıları bul
    $stmt = $conn->prepare("
        SELECT 
            u.id, 
            u.username, 
            u.full_name, 
            u.profile_photo,
            m.created_at,
            (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND recipient_id = ? AND is_read = 0) as unread_count
        FROM 
            users u
        JOIN 
            messages m ON (m.sender_id = u.id AND m.recipient_id = ?) OR (m.recipient_id = u.id AND m.sender_id = ?)
        WHERE 
            u.id != ?
        GROUP BY 
            u.id
        ORDER BY 
            m.created_at DESC
    ");
    $stmt->execute([$current_user_id, $current_user_id, $current_user_id, $current_user_id]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Eğer aktif bir konuşma varsa, kullanıcı bilgilerini al
    if ($active_conversation > 0) {
        $stmt = $conn->prepare("SELECT id, username, full_name, profile_photo FROM users WHERE id = ?");
        $stmt->execute([$active_conversation]);
        $conversation_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$conversation_user) {
            $active_conversation = 0;
        }
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
}

// Kullanıcı listesini al (yeni mesaj için)
$users = [];
try {
    $stmt = $conn->prepare("
        SELECT id, username, full_name, profile_photo 
        FROM users 
        WHERE id != ? 
        ORDER BY username
    ");
    $stmt->execute([$current_user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Hata olursa sessizce devam et
}

// Mesajları al (eğer aktif bir konuşma varsa)
$messages = [];
if ($active_conversation > 0 && $conversation_user) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                m.id, 
                m.sender_id, 
                m.recipient_id, 
                m.message, 
                m.created_at,
                m.is_read,
                u.username as sender_name,
                u.profile_photo as sender_photo
            FROM 
                messages m
            JOIN 
                users u ON m.sender_id = u.id
            WHERE 
                (m.sender_id = ? AND m.recipient_id = ?) OR (m.sender_id = ? AND m.recipient_id = ?)
            ORDER BY 
                m.created_at ASC
        ");
        $stmt->execute([$current_user_id, $active_conversation, $active_conversation, $current_user_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
    }
}

// Okunmamış mesaj sayısını al
$unread_count = 0;
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM messages 
        WHERE recipient_id = ? AND is_read = 0
    ");
    $stmt->execute([$current_user_id]);
    $unread_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Hata olursa sessizce devam et
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Mesajlar <?php if ($unread_count > 0): ?><span class="badge bg-danger"><?php echo $unread_count; ?></span><?php endif; ?></h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
        <i class="fas fa-plus me-1"></i> Yeni Mesaj
    </button>
</div>

<div class="row">
    <!-- Konuşma Listesi -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Konuşmalar</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($conversations)): ?>
                    <div class="p-3 text-center text-muted">
                        <i class="fas fa-comments fa-3x mb-3"></i>
                        <p>Henüz konuşma bulunmamaktadır.</p>
                        <p>Yeni bir mesaj göndermek için "Yeni Mesaj" butonuna tıklayın.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($conversations as $conversation): ?>
                            <a href="messages.php?conversation=<?php echo $conversation['id']; ?>&mark_read=1" 
                               class="list-group-item list-group-item-action <?php echo $active_conversation == $conversation['id'] ? 'active' : ''; ?>">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <img src="<?php echo !empty($conversation['profile_photo']) ? 'uploads/profiles/' . h($conversation['profile_photo']) : 'assets/img/default-avatar.png'; ?>" 
                                             class="rounded-circle" width="40" height="40" alt="Avatar">
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo h($conversation['full_name'] ?: $conversation['username']); ?></h6>
                                            <small><?php echo formatDate($conversation['created_at'], true); ?></small>
                                        </div>
                                        <?php if ($conversation['unread_count'] > 0): ?>
                                            <span class="badge bg-danger"><?php echo $conversation['unread_count']; ?> yeni mesaj</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Mesaj Alanı -->
    <div class="col-md-8">
        <div class="card">
            <?php if ($active_conversation > 0 && $conversation_user): ?>
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <img src="<?php echo !empty($conversation_user['profile_photo']) ? 'uploads/profiles/' . h($conversation_user['profile_photo']) : 'assets/img/default-avatar.png'; ?>" 
                                 class="rounded-circle" width="40" height="40" alt="Avatar">
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-0"><?php echo h($conversation_user['full_name'] ?: $conversation_user['username']); ?></h5>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="chat-messages p-3" style="height: 400px; overflow-y: auto;">
                        <?php if (empty($messages)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>Henüz mesaj bulunmamaktadır.</p>
                                <p>Mesaj göndermek için aşağıdaki formu kullanın.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="message mb-3 <?php echo $message['sender_id'] == $current_user_id ? 'text-end' : ''; ?>">
                                    <?php if ($message['sender_id'] != $current_user_id): ?>
                                        <div class="d-flex mb-1">
                                            <img src="<?php echo !empty($message['sender_photo']) ? 'uploads/profiles/' . h($message['sender_photo']) : 'assets/img/default-avatar.png'; ?>" 
                                                 class="rounded-circle me-2" width="30" height="30" alt="Avatar">
                                            <div class="fw-bold"><?php echo h($message['sender_name']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="message-content p-2 rounded 
                                         <?php echo $message['sender_id'] == $current_user_id ? 'bg-primary text-white' : 'bg-light'; ?>"
                                         style="display: inline-block; max-width: 80%;">
                                        <?php echo nl2br(h($message['message'])); ?>
                                    </div>
                                    <div class="message-time small text-muted mt-1">
                                        <?php echo formatDate($message['created_at'], true); ?>
                                        <?php if ($message['sender_id'] == $current_user_id && $message['is_read']): ?>
                                            <i class="fas fa-check-double ms-1 text-info" title="Okundu"></i>
                                        <?php elseif ($message['sender_id'] == $current_user_id): ?>
                                            <i class="fas fa-check ms-1" title="İletildi"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer">
                    <form method="post" action="messages.php?conversation=<?php echo $active_conversation; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="recipient_id" value="<?php echo $active_conversation; ?>">
                        <div class="input-group">
                            <textarea class="form-control" name="message" placeholder="Mesajınızı yazın..." rows="1" required></textarea>
                            <button type="submit" name="send_message" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="card-body text-center py-5">
                    <i class="fas fa-comments fa-4x mb-3 text-muted"></i>
                    <h5>Mesajlaşmaya Başlayın</h5>
                    <p class="text-muted">Sol taraftan bir konuşma seçin veya yeni bir mesaj gönderin.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                        <i class="fas fa-plus me-1"></i> Yeni Mesaj
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Yeni Mesaj Modal -->
<div class="modal fade" id="newMessageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Mesaj</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="messages.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="mb-3">
                        <label for="recipient_id" class="form-label">Alıcı</label>
                        <select class="form-select" id="recipient_id" name="recipient_id" required>
                            <option value="">Kullanıcı seçin...</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo h($user['full_name'] ?: $user['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Mesaj</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="send_message" class="btn btn-primary">Gönder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mesaj alanına otomatik kaydırma
    const chatMessages = document.querySelector('.chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Textarea otomatik boyutlandırma
    const messageTextarea = document.querySelector('textarea[name="message"]');
    if (messageTextarea) {
        messageTextarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?> 