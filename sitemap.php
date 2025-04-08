<?php
require_once 'config/database.php';

// SEO ayarlarını veritabanından al
$stmt = $pdo->query("SELECT sitemap_xml FROM seo_settings LIMIT 1");
$seo_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Content-Type header'ını ayarla
header('Content-Type: application/xml');

// Sitemap.xml içeriğini yazdır
echo $seo_settings['sitemap_xml'];
?> 