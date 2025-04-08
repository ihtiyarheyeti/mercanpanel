<?php
require_once 'includes/init.php';
require_once 'includes/auth_check.php';

// Mesaj ID'sini kontrol et
$message_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($message_id <= 0) {
    $_SESSION['error'] = $lang['messages_error_not_found'];
    header('Location: messages.php');
    exit;
}

try {
    // Mesajı getir
    $stmt = $conn->prepare("
        SELECT m.*, 
               s.username as sender_name,
               r.username as receiver_name
        FROM messages m
        LEFT JOIN users s ON m.sender_id = s.id
        LEFT JOIN users r ON m.receiver_id = r.id
        WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?)
    ");
    $stmt->execute([$message_id, $_SESSION['user_id'], $_SESSION['user_id']]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        throw new Exception($lang['messages_error_not_found']);
    }

    // Eğer alıcı isek ve mesaj okunmamışsa, okundu olarak işaretle
    if ($message['receiver_id'] == $_SESSION['user_id'] && !$message['is_read']) {
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
        $stmt->execute([$message_id]);
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: messages.php');
    exit;
}

// Header'ı dahil et
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= htmlspecialchars($message['subject']) ?></h5>
                    <a href="messages.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> <?= $lang['back'] ?>
                    </a>
                </div>
                <div class="card-body">
                    <div class="message-info mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><?= $lang['messages_from'] ?>:</strong> <?= htmlspecialchars($message['sender_name']) ?></p>
                                <p><strong><?= $lang['messages_to'] ?>:</strong> <?= htmlspecialchars($message['receiver_name']) ?></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p><strong><?= $lang['messages_date'] ?>:</strong> <?= format_date($message['send_date']) ?></p>
                                <p>
                                    <strong><?= $lang['messages_status'] ?>:</strong>
                                    <?php if ($message['receiver_id'] == $_SESSION['user_id']): ?>
                                        <?php if ($message['is_read']): ?>
                                            <span class="badge bg-success"><?= $lang['messages_read'] ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning"><?= $lang['messages_unread'] ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-info"><?= $lang['messages_sent'] ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="message-content">
                        <div class="card">
                            <div class="card-body bg-light">
                                <?= nl2br(htmlspecialchars($message['message'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <?php if ($message['receiver_id'] == $_SESSION['user_id']): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#replyModal">
                            <i class="fas fa-reply"></i> <?= $lang['messages']['reply'] ?>
                        </button>
                    <?php endif; ?>
                    <a href="messages.php?delete=<?= $message['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                       class="btn btn-danger"
                       onclick="return confirm('<?= $lang['confirm_delete'] ?>')">
                        <i class="fas fa-trash"></i> <?= $lang['delete'] ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($message['receiver_id'] == $_SESSION['user_id']): ?>
<!-- Yanıt Modal -->
<div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="replyModalLabel"><?= $lang['messages']['reply'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="message_send.php">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="receiver_id" value="<?= $message['sender_id'] ?>">
                    <div class="mb-3">
                        <label for="subject" class="form-label"><?= $lang['messages_subject'] ?></label>
                        <input type="text" class="form-control" id="subject" name="subject" 
                               value="Re: <?= htmlspecialchars($message['subject']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label"><?= $lang['messages_message'] ?></label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $lang['close'] ?></button>
                    <button type="submit" class="btn btn-primary"><?= $lang['send'] ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?> 
 
require_once 'includes/init.php';
require_once 'includes/auth_check.php';

// Mesaj ID'sini kontrol et
$message_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($message_id <= 0) {
    $_SESSION['error'] = $lang['messages_error_not_found'];
    header('Location: messages.php');
    exit;
}

try {
    // Mesajı getir
    $stmt = $conn->prepare("
        SELECT m.*, 
               s.username as sender_name,
               r.username as receiver_name
        FROM messages m
        LEFT JOIN users s ON m.sender_id = s.id
        LEFT JOIN users r ON m.receiver_id = r.id
        WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?)
    ");
    $stmt->execute([$message_id, $_SESSION['user_id'], $_SESSION['user_id']]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        throw new Exception($lang['messages_error_not_found']);
    }

    // Eğer alıcı isek ve mesaj okunmamışsa, okundu olarak işaretle
    if ($message['receiver_id'] == $_SESSION['user_id'] && !$message['is_read']) {
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
        $stmt->execute([$message_id]);
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: messages.php');
    exit;
}

// Header'ı dahil et
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= htmlspecialchars($message['subject']) ?></h5>
                    <a href="messages.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> <?= $lang['back'] ?>
                    </a>
                </div>
                <div class="card-body">
                    <div class="message-info mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><?= $lang['messages_from'] ?>:</strong> <?= htmlspecialchars($message['sender_name']) ?></p>
                                <p><strong><?= $lang['messages_to'] ?>:</strong> <?= htmlspecialchars($message['receiver_name']) ?></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p><strong><?= $lang['messages_date'] ?>:</strong> <?= format_date($message['send_date']) ?></p>
                                <p>
                                    <strong><?= $lang['messages_status'] ?>:</strong>
                                    <?php if ($message['receiver_id'] == $_SESSION['user_id']): ?>
                                        <?php if ($message['is_read']): ?>
                                            <span class="badge bg-success"><?= $lang['messages_read'] ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning"><?= $lang['messages_unread'] ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-info"><?= $lang['messages_sent'] ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="message-content">
                        <div class="card">
                            <div class="card-body bg-light">
                                <?= nl2br(htmlspecialchars($message['message'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <?php if ($message['receiver_id'] == $_SESSION['user_id']): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#replyModal">
                            <i class="fas fa-reply"></i> <?= $lang['messages']['reply'] ?>
                        </button>
                    <?php endif; ?>
                    <a href="messages.php?delete=<?= $message['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                       class="btn btn-danger"
                       onclick="return confirm('<?= $lang['confirm_delete'] ?>')">
                        <i class="fas fa-trash"></i> <?= $lang['delete'] ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($message['receiver_id'] == $_SESSION['user_id']): ?>
<!-- Yanıt Modal -->
<div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="replyModalLabel"><?= $lang['messages']['reply'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="message_send.php">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="receiver_id" value="<?= $message['sender_id'] ?>">
                    <div class="mb-3">
                        <label for="subject" class="form-label"><?= $lang['messages_subject'] ?></label>
                        <input type="text" class="form-control" id="subject" name="subject" 
                               value="Re: <?= htmlspecialchars($message['subject']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label"><?= $lang['messages_message'] ?></label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $lang['close'] ?></button>
                    <button type="submit" class="btn btn-primary"><?= $lang['send'] ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?> 
 
 