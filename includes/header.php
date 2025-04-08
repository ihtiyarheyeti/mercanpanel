<?php
// Oturum kontrolü
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($page_title)) {
    $page_title = 'LutufPanel';
}

// Gerekli dosyaları dahil et
require_once __DIR__ . '/Settings.php';
require_once __DIR__ . '/Language.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Settings sınıfını başlat
$settings = Settings::getInstance($conn);
$language = Language::getInstance();

// SEO ayarlarını veritabanından al
try {
    $stmt = $pdo->query("SELECT * FROM seo_settings LIMIT 1");
    $seo_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Eğer SEO ayarları yoksa varsayılan değerleri kullan
    if (!$seo_settings) {
        $seo_settings = [
            'meta_title' => $settings->get('site_title', 'Admin Panel'),
            'meta_description' => '',
            'meta_keywords' => '',
            'og_title' => '',
            'og_description' => '',
            'og_image' => '',
            'twitter_card' => '',
            'twitter_title' => '',
            'twitter_description' => '',
            'twitter_image' => '',
            'google_analytics_id' => '',
            'google_verification_code' => '',
            'bing_verification_code' => ''
        ];
    }
} catch (PDOException $e) {
    // SEO tablosu henüz oluşturulmamış olabilir, varsayılan değerleri kullan
    $seo_settings = [
        'meta_title' => $settings->get('site_title', 'Admin Panel'),
        'meta_description' => '',
        'meta_keywords' => '',
        'og_title' => '',
        'og_description' => '',
        'og_image' => '',
        'twitter_card' => '',
        'twitter_title' => '',
        'twitter_description' => '',
        'twitter_image' => '',
        'google_analytics_id' => '',
        'google_verification_code' => '',
        'bing_verification_code' => ''
    ];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $language->getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Genel SEO Meta Etiketleri -->
    <title><?php echo htmlspecialchars($seo_settings['meta_title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seo_settings['meta_description']); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seo_settings['meta_keywords']); ?>">
    
    <!-- Open Graph Meta Etiketleri -->
    <meta property="og:title" content="<?php echo htmlspecialchars($seo_settings['og_title'] ?: $seo_settings['meta_title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seo_settings['og_description'] ?: $seo_settings['meta_description']); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($seo_settings['og_image']); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    
    <!-- Twitter Card Meta Etiketleri -->
    <meta name="twitter:card" content="<?php echo htmlspecialchars($seo_settings['twitter_card']); ?>">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($seo_settings['twitter_title'] ?: $seo_settings['meta_title']); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($seo_settings['twitter_description'] ?: $seo_settings['meta_description']); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($seo_settings['twitter_image'] ?: $seo_settings['og_image']); ?>">
    
    <!-- Google ve Bing Doğrulama -->
    <?php if ($seo_settings['google_verification_code']): ?>
    <meta name="google-site-verification" content="<?php echo htmlspecialchars($seo_settings['google_verification_code']); ?>">
    <?php endif; ?>
    
    <?php if ($seo_settings['bing_verification_code']): ?>
    <meta name="msvalidate.01" content="<?php echo htmlspecialchars($seo_settings['bing_verification_code']); ?>">
    <?php endif; ?>
    
    <!-- Google Analytics -->
    <?php if ($seo_settings['google_analytics_id']): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($seo_settings['google_analytics_id']); ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo htmlspecialchars($seo_settings['google_analytics_id']); ?>');
    </script>
    <?php endif; ?>

    <!-- CSS Dosyaları -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- JavaScript Dosyaları -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dropdown menüleri jQuery ile aktifleştir
            $('.dropdown-toggle').dropdown();
            
            // Tüm dropdown menü butonlarını seç
            const dropdownButtons = document.querySelectorAll('[data-bs-toggle="dropdown"]');
            
            // Her butona tıklama olayı ekle
            dropdownButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Diğer açık menüleri kapat
                    dropdownButtons.forEach(otherButton => {
                        if (otherButton !== button) {
                            otherButton.classList.remove('show');
                            otherButton.nextElementSibling?.classList.remove('show');
                        }
                    });
                    
                    // Tıklanan menüyü aç/kapat
                    button.classList.toggle('show');
                    button.nextElementSibling?.classList.toggle('show');
                });
            });
            
            // Sayfa herhangi bir yerine tıklandığında menüleri kapat
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    dropdownButtons.forEach(button => {
                        button.classList.remove('show');
                        button.nextElementSibling?.classList.remove('show');
                    });
                }
            });

            // Mobil menü toggle
            const navbarToggler = document.querySelector('.navbar-toggler');
            const sidebar = document.querySelector('.sidebar');
            
            if (navbarToggler && sidebar) {
                navbarToggler.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }
            
            // Ekran dışı tıklamada menüyü kapat
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 768) {
                    if (!sidebar.contains(event.target) && !navbarToggler.contains(event.target)) {
                        sidebar.classList.remove('show');
                    }
                }
            });
        });
    </script>
    
    <style>
        :root {
            --sidebar-width: 250px;
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --light-bg: #f8f9fa;
        }
        
        body {
            min-height: 100vh;
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
            position: relative;
        }
        
        .wrapper {
            min-height: 100vh;
            position: relative;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: var(--primary-color);
            color: #fff;
            position: fixed;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            top: 0;
            left: 0;
        }
        
        .content-wrapper {
            position: relative;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: var(--light-bg);
            padding: 20px;
        }
        
        .navbar {
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,.05);
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            position: relative;
            z-index: 1050;
        }
        
        .main-content {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
            padding: 1.5rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,.1);
            padding-left: 1.25rem;
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background: var(--secondary-color);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .navbar-brand {
            font-weight: 600;
            color: var(--primary-color);
            padding: 0.5rem 0;
        }
        
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,.05);
            margin-bottom: 1rem;
            overflow: hidden;
        }
        
        .card-header {
            background: #fff;
            border-bottom: 1px solid rgba(0,0,0,.05);
            padding: 1rem;
        }
        
        .border-left-primary { border-left: 4px solid var(--secondary-color); }
        .border-left-success { border-left: 4px solid #1cc88a; }
        .border-left-info { border-left: 4px solid #36b9cc; }
        .border-left-warning { border-left: 4px solid #f6c23e; }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,.1);
            border-radius: 8px;
            z-index: 1051;
        }
        
        .dropdown-item {
            padding: 0.5rem 1rem;
        }
        
        .dropdown-item:hover {
            background-color: var(--light-bg);
        }
        
        .dropdown-item.active {
            background-color: var(--secondary-color);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .content-wrapper {
                margin-left: 0;
            }
        }
        
        .navbar .dropdown-menu {
            margin-top: 0.5rem;
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,.1);
            border-radius: 8px;
            z-index: 1051;
        }
        
        .navbar .nav-link {
            padding: 0.5rem 1rem;
            color: var(--primary-color);
        }
        
        .navbar .nav-link:hover {
            color: var(--secondary-color);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            background: var(--light-bg);
            transition: all 0.3s ease;
        }
        
        .user-menu:hover {
            background: #e9ecef;
        }
        
        .user-menu img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-menu .fa-user-circle {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 0.5rem;
            }
            
            .navbar-collapse {
                background: white;
                padding: 1rem;
                border-radius: 8px;
                box-shadow: 0 0 15px rgba(0,0,0,.1);
                position: absolute;
                top: 100%;
                right: 1rem;
                left: 1rem;
                z-index: 1000;
            }
        }
        
        /* Dropdown menü stilleri */
        .dropdown-menu.show {
            display: block;
            margin-top: 0.5rem;
            animation: fadeIn 0.2s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .nav-link.user-menu {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            background: var(--light-bg);
            transition: all 0.3s ease;
        }
        
        .nav-link.user-menu:hover,
        .nav-link.user-menu.show {
            background: #e9ecef;
        }
        
        .nav-link.user-menu .fa-chevron-down {
            transition: transform 0.2s ease;
        }
        
        .nav-link.user-menu.show .fa-chevron-down {
            transform: rotate(180deg);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="py-4 px-3 mb-3">
                <a class="text-decoration-none text-white" href="index.php">
                    <?php if ($logo = $settings->get('site_logo')): ?>
                        <img src="<?php echo htmlspecialchars($logo); ?>" height="30" alt="Logo" class="img-fluid">
                    <?php else: ?>
                        <h4 class="mb-0"><?php echo htmlspecialchars($settings->get('site_title', 'Admin Panel')); ?></h4>
                    <?php endif; ?>
                </a>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo isActiveMenu('index') ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActiveMenu('files') ? 'active' : ''; ?>" href="files.php">
                        <i class="fas fa-file"></i> Dosyalar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActiveMenu('users') ? 'active' : ''; ?>" href="users.php">
                        <i class="fas fa-users"></i> Kullanıcılar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActiveMenu('messages') ? 'active' : ''; ?>" href="messages.php">
                        <i class="fas fa-comments"></i> Mesajlar
                        <?php if (isset($unread_messages) && $unread_messages > 0): ?>
                            <span class="badge bg-danger float-end"><?php echo $unread_messages; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActiveMenu('notifications') ? 'active' : ''; ?>" href="notifications.php">
                        <i class="fas fa-bell"></i> Bildirimler
                        <?php if (isset($unread_notifications) && $unread_notifications > 0): ?>
                            <span class="badge bg-danger float-end"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActiveMenu('settings') ? 'active' : ''; ?>" href="settings.php">
                        <i class="fas fa-cog"></i> Ayarlar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActiveMenu('seo_settings') ? 'active' : ''; ?>" href="seo_settings.php">
                        <i class="fas fa-search"></i> SEO Ayarları
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActiveMenu('statistics') ? 'active' : ''; ?>" href="statistics.php">
                        <i class="fas fa-chart-bar"></i> İstatistikler
                    </a>
                </li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActiveMenu('backups') ? 'active' : ''; ?>" href="backups.php">
                        <i class="fas fa-database"></i> Yedekler
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light">
                <div class="container-fluid">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ms-auto align-items-center">
                            <!-- Dil Seçimi -->
                            <li class="nav-item dropdown">
                                <button class="nav-link" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-globe"></i> <?php echo $language->getLanguageName(); ?>
                                    <i class="fas fa-chevron-down ms-1 small"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php foreach ($language->getAvailableLanguages() as $code => $name): ?>
                                    <li>
                                        <a class="dropdown-item <?php echo $language->getCurrentLanguage() == $code ? 'active' : ''; ?>" 
                                           href="change_language.php?lang=<?php echo $code; ?>">
                                           <?php echo $name; ?>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                            
                            <!-- Kullanıcı Menüsü -->
                            <li class="nav-item dropdown">
                                <button class="nav-link user-menu" type="button" data-bs-toggle="dropdown">
                                    <?php if (isset($_SESSION['profile_photo']) && $_SESSION['profile_photo'] && file_exists($_SESSION['profile_photo'])): ?>
                                        <img src="<?php echo htmlspecialchars($_SESSION['profile_photo']); ?>" 
                                             alt="Profil Fotoğrafı"
                                             style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle"></i>
                                    <?php endif; ?>
                                    <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                    <i class="fas fa-chevron-down ms-1 small"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user fa-fw"></i> Profil</a></li>
                                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog fa-fw"></i> Ayarlar</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt fa-fw"></i> Çıkış</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="main-content">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo htmlspecialchars($_SESSION['success']);
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo htmlspecialchars($_SESSION['error']);
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Sayfa içeriği buraya gelecek -->


                
