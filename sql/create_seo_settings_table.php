<?php
require_once __DIR__ . '/../config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS seo_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        meta_title VARCHAR(255) NOT NULL,
        meta_description TEXT,
        meta_keywords TEXT,
        og_title VARCHAR(255),
        og_description TEXT,
        og_image VARCHAR(255),
        twitter_card VARCHAR(50),
        twitter_title VARCHAR(255),
        twitter_description TEXT,
        twitter_image VARCHAR(255),
        robots_txt TEXT,
        sitemap_xml TEXT,
        google_analytics_id VARCHAR(50),
        google_verification_code VARCHAR(100),
        bing_verification_code VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "SEO ayarları tablosu başarıyla oluşturuldu.\n";

    // Varsayılan SEO ayarlarını ekle
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
    echo "Varsayılan SEO ayarları eklendi.\n";

} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?> 