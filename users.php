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
    header('Location: dashboard.php');
    exit;
}

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$settings = Settings::getInstance($conn);

// Sayfalama için değişkenler
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Arama parametresi
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Toplam kullanıcı sayısını al
    $where = '';
    $params = [];
    if (!empty($search)) {
        $where = "WHERE username LIKE ? OR email LIKE ? OR full_name LIKE ?";
        $params = ["%$search%", "%$search%", "%$search%"];
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users $where");
    $stmt->execute($params);
    $total_users = $stmt->fetchColumn();
    
    // Toplam sayfa sayısını hesapla
    $total_pages = ceil($total_users / $limit);
    
    // Kullanıcıları getir
    $sql = "SELECT * FROM users $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    array_push($params, $limit, $offset);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Kullanıcı Yönetimi</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-user-plus"></i> Yeni Kullanıcı
        </button>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <form action="" method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="Kullanıcı ara..." value="<?= h($search) ?>">
                        <button type="submit" class="btn btn-outline-primary">Ara</button>
                        <?php if (!empty($search)): ?>
                            <a href="?page=1" class="btn btn-outline-secondary ms-2">Temizle</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Profil</th>
                            <th>Kullanıcı Adı</th>
                            <th>E-posta</th>
                            <th>Ad Soyad</th>
                            <th>Rol</th>
                            <th>Durum</th>
                            <th>Kayıt Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-user-slash fa-2x mb-3"></i>
                                        <p class="mb-0">Kullanıcı bulunamadı.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td>
                                        <img src="<?= !empty($user['profile_photo']) ? 'uploads/profiles/' . $user['profile_photo'] : 'assets/img/default-avatar.png' ?>" 
                                             alt="Profile" class="rounded-circle" width="40" height="40">
                                    </td>
                                    <td><?= h($user['username']) ?></td>
                                    <td><?= h($user['email']) ?></td>
                                    <td><?= h($user['full_name'] ?? '') ?></td>
                                    <td><span class="badge <?= getRoleBadgeClass($user['role'] ?? 'user') ?>"><?= ucfirst($user['role'] ?? 'user') ?></span></td>
                                    <td><span class="badge <?= $user['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>"><?= ucfirst($user['status'] ?? 'active') ?></span></td>
                                    <td><?= formatDate($user['created_at']) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-user" 
                                                    data-id="<?= $user['id'] ?>" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-user" 
                                                        data-id="<?= $user['id'] ?>" title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Sayfalama" class="mt-4">
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
        </div>
    </div>
</div>

<!-- Yeni Kullanıcı Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Kullanıcı Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">E-posta <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Rol <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="user">Kullanıcı</option>
                            <option value="editor">Editör</option>
                            <option value="admin">Yönetici</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Şifre <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                        <div class="form-text">En az 8 karakter olmalıdır.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ad Soyad</label>
                        <input type="text" name="full_name" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Durum <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="submit" form="addUserForm" class="btn btn-primary">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<!-- Kullanıcı Düzenle Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kullanıcı Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">E-posta <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Şifre</label>
                        <input type="password" name="password" class="form-control" minlength="8">
                        <div class="form-text">Değiştirmek istemiyorsanız boş bırakın.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ad Soyad</label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Rol <span class="text-danger">*</span></label>
                        <select name="role" id="edit_role" class="form-select" required>
                            <option value="user">Kullanıcı</option>
                            <option value="editor">Editör</option>
                            <option value="admin">Yönetici</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Durum <span class="text-danger">*</span></label>
                        <select name="status" id="edit_status" class="form-select" required>
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="submit" form="editUserForm" class="btn btn-primary">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form submit işlemleri
    ['addUserForm', 'editUserForm'].forEach(formId => {
        document.getElementById(formId).addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('user_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    throw new Error(data.error);
                }
            })
            .catch(error => {
                alert(error.message || 'Bir hata oluştu.');
            });
        });
    });

    // Kullanıcı düzenleme
    document.querySelectorAll('.edit-user').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.dataset.id;
            
            fetch(`user_actions.php?action=get_user&id=${userId}`)
                .then(response => response.json())
                .then(user => {
                    document.getElementById('edit_user_id').value = user.id;
                    document.getElementById('edit_username').value = user.username;
                    document.getElementById('edit_email').value = user.email;
                    document.getElementById('edit_full_name').value = user.full_name;
                    document.getElementById('edit_role').value = user.role;
                    document.getElementById('edit_status').value = user.status;
                    
                    new bootstrap.Modal(document.getElementById('editUserModal')).show();
                })
                .catch(error => {
                    alert('Kullanıcı bilgileri alınamadı.');
                });
        });
    });

    // Kullanıcı silme
    document.querySelectorAll('.delete-user').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')) {
                const userId = this.dataset.id;
                const formData = new FormData();
                formData.append('action', 'delete_user');
                formData.append('user_id', userId);
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
                
                fetch('user_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        throw new Error(data.error);
                    }
                })
                .catch(error => {
                    alert(error.message || 'Bir hata oluştu.');
                });
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?> 
 