<?php
require_once 'config/database.php';

// SEO ayarlarını veritabanından al
$stmt = $pdo->query("SELECT robots_txt FROM seo_settings LIMIT 1");
$seo_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Content-Type header'ını ayarla
header('Content-Type: text/plain');

// Robots.txt içeriğini yazdır
echo $seo_settings['robots_txt'];
?> 