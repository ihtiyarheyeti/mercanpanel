<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Yeni Kullanıcı - Mercan Panel' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --sidebar-width: 280px;
        }
        
        body {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 1rem;
            color: white;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
        }
        
        .sidebar-header img {
            max-width: 150px;
            height: auto;
            margin-bottom: 1rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.8rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .nav-link i {
            width: 24px;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }
        
        .top-bar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.8rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="/mercanpanel/public/img/logo.png" alt="Mercan Panel Logo" onerror="this.src='data:image/svg+xml;charset=UTF-8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'150\' height=\'45\' viewBox=\'0 0 150 45\'><text x=\'50%\' y=\'50%\' font-size=\'18\' fill=\'white\' text-anchor=\'middle\' dominant-baseline=\'middle\'>Mercan Panel</text></svg>'">
            <h5 class="mb-0">Yönetim Paneli</h5>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link" href="/mercanpanel/dashboard">
                <i class="fas fa-home me-2"></i>
                Dashboard
            </a>
            <a class="nav-link active" href="/mercanpanel/users">
                <i class="fas fa-users me-2"></i>
                Kullanıcılar
            </a>
            <a class="nav-link" href="/mercanpanel/settings">
                <i class="fas fa-cog me-2"></i>
                Ayarlar
            </a>
            <a class="nav-link" href="/mercanpanel/themes">
                <i class="fas fa-paint-brush me-2"></i>
                Temalar
            </a>
            <a class="nav-link text-danger" href="/mercanpanel/logout">
                <i class="fas fa-sign-out-alt me-2"></i>
                Çıkış Yap
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Yeni Kullanıcı Oluştur</h4>
            <a href="/mercanpanel/users" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Geri Dön
            </a>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-plus me-2"></i>Kullanıcı Bilgileri
                </h5>
            </div>
            <div class="card-body p-4">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/mercanpanel/users/create">
                    <div class="mb-3">
                        <label for="username" class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="mb-4">
                        <label for="role" class="form-label">Rol</label>
                        <select class="form-select" id="role" name="role">
                            <option value="superadmin">Süper Admin (Tam Yetki)</option>
                            <option value="admin">Yönetici (Genel Yönetim)</option>
                            <option value="editor">Editör (İçerik Yönetimi)</option>
                            <option value="moderator">Moderatör (İçerik Denetimi)</option>
                            <option value="user" selected>Kullanıcı (Temel Yetkiler)</option>
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

                    <div class="d-flex justify-content-end">
                        <a href="/mercanpanel/users" class="btn btn-light me-2">İptal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 