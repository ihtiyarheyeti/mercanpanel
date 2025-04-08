<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Kullanıcılar - Mercan Panel' ?></title>
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
            padding: 1rem 1.5rem;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }

        .table td {
            padding: 1rem 1.5rem;
            vertical-align: middle;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
        }

        .btn-action {
            padding: 0.5rem;
            border-radius: 8px;
            margin: 0 0.2rem;
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
            <h4 class="mb-0">Kullanıcı Yönetimi</h4>
            <a href="/mercanpanel/users/create" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Yeni Kullanıcı
            </a>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Kullanıcı Listesi
                    </h5>
                    <span class="badge bg-primary"><?= count($users) ?> Kullanıcı</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>Kullanıcı Adı</th>
                                <th>Rol</th>
                                <th>Kayıt Tarihi</th>
                                <th width="120">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-users text-muted mb-2" style="font-size: 2rem;"></i>
                                        <p class="text-muted mb-0">Henüz kullanıcı bulunmuyor.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-2" style="width: 32px; height: 32px; background: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                                </div>
                                                <?= htmlspecialchars($user['username']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $roleClasses = [
                                                'superadmin' => 'danger',
                                                'admin' => 'warning',
                                                'editor' => 'success',
                                                'moderator' => 'info',
                                                'user' => 'secondary'
                                            ];
                                            $roleBadgeClass = $roleClasses[$user['role']] ?? 'primary';
                                            ?>
                                            <span class="badge bg-<?= $roleBadgeClass ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <a href="/mercanpanel/users/edit/<?= $user['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($user['role'] !== 'admin' && $user['id'] != 1): ?>
                                                <a href="/mercanpanel/users/delete/<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 