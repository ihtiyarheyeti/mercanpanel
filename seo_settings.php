<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

// Sadece admin erişebilir
if (!is_admin()) {
    $_SESSION['error'] = "Bu sayfaya erişim yetkiniz bulunmamaktadır.";
    header('Location: index.php');
    exit;
}

// Başlık ayarla
$page_title = "SEO Ayarları";

require_once 'includes/header.php';

// Hata ve başarı mesajlarını göster
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
    unset($_SESSION['success']);
}

try {
    // SEO ayarlarını veritabanından al
    $stmt = $pdo->query("SELECT * FROM seo_settings LIMIT 1");
    $seo_settings = $stmt->fetch(PDO::FETCH_ASSOC);

    // Eğer kayıt yoksa varsayılan değerleri oluştur
    if (!$seo_settings) {
        $defaultSettings = [
            'meta_title' => 'Admin Panel',
            'meta_description' => 'Admin Panel - Gelişmiş Yönetim Sistemi',
            'meta_keywords' => 'admin, panel, yönetim, sistem',
            'robots_txt' => "User-agent: *\nAllow: /",
            'sitemap_xml' => '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>'
        ];

        $stmt = $pdo->prepare("INSERT INTO seo_settings (meta_title, meta_description, meta_keywords, robots_txt, sitemap_xml) 
                              VALUES (:meta_title, :meta_description, :meta_keywords, :robots_txt, :sitemap_xml)");
        $stmt->execute($defaultSettings);
        
        // Yeni eklenen kaydı al
        $stmt = $pdo->query("SELECT * FROM seo_settings LIMIT 1");
        $seo_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Form gönderildiğinde
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF kontrolü
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('CSRF token doğrulaması başarısız.');
        }

        $stmt = $pdo->prepare("UPDATE seo_settings SET 
            meta_title = :meta_title,
            meta_description = :meta_description,
            meta_keywords = :meta_keywords,
            og_title = :og_title,
            og_description = :og_description,
            og_image = :og_image,
            twitter_card = :twitter_card,
            twitter_title = :twitter_title,
            twitter_description = :twitter_description,
            twitter_image = :twitter_image,
            robots_txt = :robots_txt,
            sitemap_xml = :sitemap_xml,
            google_analytics_id = :google_analytics_id,
            google_verification_code = :google_verification_code,
            bing_verification_code = :bing_verification_code
        ");

        $stmt->execute([
            'meta_title' => $_POST['meta_title'],
            'meta_description' => $_POST['meta_description'],
            'meta_keywords' => $_POST['meta_keywords'],
            'og_title' => $_POST['og_title'],
            'og_description' => $_POST['og_description'],
            'og_image' => $_POST['og_image'],
            'twitter_card' => $_POST['twitter_card'],
            'twitter_title' => $_POST['twitter_title'],
            'twitter_description' => $_POST['twitter_description'],
            'twitter_image' => $_POST['twitter_image'],
            'robots_txt' => $_POST['robots_txt'],
            'sitemap_xml' => $_POST['sitemap_xml'],
            'google_analytics_id' => $_POST['google_analytics_id'],
            'google_verification_code' => $_POST['google_verification_code'],
            'bing_verification_code' => $_POST['bing_verification_code']
        ]);

        $_SESSION['success'] = "SEO ayarları başarıyla güncellendi.";
        header("Location: seo_settings.php");
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Hata: " . $e->getMessage();
    header("Location: seo_settings.php");
    exit;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">SEO Ayarları</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h4>Genel SEO Ayarları</h4>
                                <div class="form-group">
                                    <label>Meta Başlık</label>
                                    <input type="text" class="form-control" name="meta_title" value="<?php echo htmlspecialchars($seo_settings['meta_title']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Meta Açıklama</label>
                                    <textarea class="form-control" name="meta_description" rows="3"><?php echo htmlspecialchars($seo_settings['meta_description']); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Meta Anahtar Kelimeler</label>
                                    <textarea class="form-control" name="meta_keywords" rows="2"><?php echo htmlspecialchars($seo_settings['meta_keywords']); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h4>Open Graph Ayarları</h4>
                                <div class="form-group">
                                    <label>OG Başlık</label>
                                    <input type="text" class="form-control" name="og_title" value="<?php echo htmlspecialchars($seo_settings['og_title']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>OG Açıklama</label>
                                    <textarea class="form-control" name="og_description" rows="3"><?php echo htmlspecialchars($seo_settings['og_description']); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>OG Resim URL</label>
                                    <input type="text" class="form-control" name="og_image" value="<?php echo htmlspecialchars($seo_settings['og_image']); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h4>Twitter Card Ayarları</h4>
                                <div class="form-group">
                                    <label>Twitter Card Tipi</label>
                                    <select class="form-control" name="twitter_card">
                                        <option value="summary" <?php echo $seo_settings['twitter_card'] == 'summary' ? 'selected' : ''; ?>>Summary</option>
                                        <option value="summary_large_image" <?php echo $seo_settings['twitter_card'] == 'summary_large_image' ? 'selected' : ''; ?>>Summary Large Image</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Twitter Başlık</label>
                                    <input type="text" class="form-control" name="twitter_title" value="<?php echo htmlspecialchars($seo_settings['twitter_title']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Twitter Açıklama</label>
                                    <textarea class="form-control" name="twitter_description" rows="3"><?php echo htmlspecialchars($seo_settings['twitter_description']); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Twitter Resim URL</label>
                                    <input type="text" class="form-control" name="twitter_image" value="<?php echo htmlspecialchars($seo_settings['twitter_image']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h4>Diğer Ayarlar</h4>
                                <div class="form-group">
                                    <label>Google Analytics ID</label>
                                    <input type="text" class="form-control" name="google_analytics_id" value="<?php echo htmlspecialchars($seo_settings['google_analytics_id']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Google Doğrulama Kodu</label>
                                    <input type="text" class="form-control" name="google_verification_code" value="<?php echo htmlspecialchars($seo_settings['google_verification_code']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Bing Doğrulama Kodu</label>
                                    <input type="text" class="form-control" name="bing_verification_code" value="<?php echo htmlspecialchars($seo_settings['bing_verification_code']); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h4>Robots.txt</h4>
                                <div class="form-group">
                                    <textarea class="form-control" name="robots_txt" rows="5"><?php echo htmlspecialchars($seo_settings['robots_txt']); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h4>Sitemap.xml</h4>
                                <div class="form-group">
                                    <textarea class="form-control" name="sitemap_xml" rows="5"><?php echo htmlspecialchars($seo_settings['sitemap_xml']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">Ayarları Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 