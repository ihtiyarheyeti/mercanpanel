<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">
            <i class="fas fa-user-edit me-2"></i>Kullanıcı Düzenle
        </h5>
    </div>
    <div class="card-body p-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/mercanpanel/users/edit/<?= $user['id'] ?>">
            <div class="mb-3">
                <label for="username" class="form-label">Kullanıcı Adı</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Şifre</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Değiştirmek için yeni şifre girin">
                <small class="form-text text-muted">Şifreyi değiştirmek istemiyorsanız boş bırakın</small>
            </div>

            <div class="mb-4">
                <label for="role" class="form-label">Rol</label>
                <select class="form-select" id="role" name="role">
                    <option value="superadmin" <?= $user['role'] === 'superadmin' ? 'selected' : '' ?>>Süper Admin (Tam Yetki)</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Yönetici (Genel Yönetim)</option>
                    <option value="editor" <?= $user['role'] === 'editor' ? 'selected' : '' ?>>Editör (İçerik Yönetimi)</option>
                    <option value="moderator" <?= $user['role'] === 'moderator' ? 'selected' : '' ?>>Moderatör (İçerik Denetimi)</option>
                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Kullanıcı (Temel Yetkiler)</option>
                </select>
                <div class="form-text mt-2">
                    <i class="fas fa-info-circle me-1"></i>
                    Roller hakkında bilgi:
                    <ul class="mt-2 small">
                        <li><strong>Süper Admin:</strong> Tüm sistem yetkilerine sahiptir</li>
                        <li><strong>Yönetici:</strong> Genel yönetim yetkilerine sahiptir</li>
                        <li><strong>Editör:</strong> İçerik ekleme, düzenleme ve silme yetkilerine sahiptir</li>
                        <li><strong>Moderatör:</strong> Kullanıcı içeriklerini denetleme yetkisine sahiptir</li>
                        <li><strong>Kullanıcı:</strong> Temel kullanım yetkilerine sahiptir</li>
                    </ul>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Kullanıcı Bilgileri</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Kullanıcı ID:</strong> <?= $user['id'] ?></p>
                            <p><strong>Oluşturulma Tarihi:</strong> <?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Son Güncelleme:</strong> <?= date('d.m.Y H:i', strtotime($user['updated_at'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="/mercanpanel/users" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Geri Dön
                </a>
                <div>
                    <?php if ($user['id'] != 1): ?>
                    <a href="/mercanpanel/users/delete/<?= $user['id'] ?>" class="btn btn-danger me-2" onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">
                        <i class="fas fa-trash me-2"></i>Sil
                    </a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Kaydet
                    </button>
                </div>
            </div>
        </form>
    </div>
</div> 