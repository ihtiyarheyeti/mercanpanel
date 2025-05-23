<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Mercan Panel' ?></title>
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
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }
        
        .stat-card i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .stat-card h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            color: #6c757d;
            margin: 0;
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
            <a class="nav-link" href="/mercanpanel/users">
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
        <div class="top-bar">
            <h4 class="mb-0"><?= htmlspecialchars($title ?? 'Mercan Panel') ?></h4>
            <?php if (isset($_SESSION['user'])): ?>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['user']['username'], 0, 1)) ?>
                </div>
                <div>
                    <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong>
                    <div class="text-muted small"><?= htmlspecialchars($_SESSION['user']['role'] ?? 'Kullanıcı') ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Flash Mesaj Alanı -->
        <?php
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'] ?? 'success';
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">'
                . htmlspecialchars($message) 
                . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
                . '</div>';
        }
        ?>

        <!-- Asıl Sayfa İçeriği -->
        {{content}}
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Aktif menü elemanını belirlemek için JS
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPath || 
                    (currentPath.startsWith(link.getAttribute('href')) && 
                     link.getAttribute('href') !== '/mercanpanel')) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html> 