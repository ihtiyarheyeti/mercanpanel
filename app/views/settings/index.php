<?php
// Flash mesajı varsa göster
if (isset($message) && $message) {
    echo '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}
?>

<!-- Üst başlık kartı -->
<div class="card mb-4">
    <div class="card-body d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Panel Ayarları</h4>
        <button type="submit" class="btn btn-primary" form="settingsForm">
            <i class="fas fa-save me-2"></i>Ayarları Kaydet
        </button>
    </div>
</div>

<form id="settingsForm" action="/mercanpanel/settings/update" method="post">
    <!-- Genel Ayarlar -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-cog me-2"></i>Genel Ayarlar
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="site_title" class="form-label">Site Başlığı</label>
                <input type="text" class="form-control" id="site_title" name="site_title" value="<?= htmlspecialchars($settings['site_title'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="logo" class="form-label">Logo Yolu</label>
                <input type="text" class="form-control" id="logo" name="logo" value="<?= htmlspecialchars($settings['logo'] ?? '') ?>">
                <small class="form-text text-muted">Örnek: /assets/images/logo.png</small>
            </div>

            <div class="mb-3">
                <label for="contact_email" class="form-label">İletişim E-postası</label>
                <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>">
            </div>
        </div>
    </div>

    <!-- SEO Ayarları -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-search me-2"></i>SEO Ayarları
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="meta_title" class="form-label">Meta Başlık</label>
                <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?= htmlspecialchars($settings['meta_title'] ?? '') ?>">
                <small class="form-text text-muted">Tarayıcı sekmesinde görünen başlık</small>
            </div>

            <div class="mb-3">
                <label for="meta_description" class="form-label">Meta Açıklama</label>
                <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?= htmlspecialchars($settings['meta_description'] ?? '') ?></textarea>
                <small class="form-text text-muted">Arama motoru sonuçlarında gösterilen açıklama</small>
            </div>

            <div class="mb-3">
                <label for="meta_keywords" class="form-label">Meta Anahtar Kelimeler</label>
                <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" value="<?= htmlspecialchars($settings['meta_keywords'] ?? '') ?>">
                <small class="form-text text-muted">Virgülle ayrılmış anahtar kelimeler</small>
            </div>

            <div class="mb-3">
                <label for="robots_txt" class="form-label">Robots.txt İçeriği</label>
                <textarea class="form-control" id="robots_txt" name="robots_txt" rows="5"><?= htmlspecialchars($settings['robots_txt'] ?? "User-agent: *\nAllow: /") ?></textarea>
                <small class="form-text text-muted">Arama motoru robotları için yönergeler</small>
            </div>

            <div class="mb-3">
                <label for="google_analytics" class="form-label">Google Analytics Kodu</label>
                <input type="text" class="form-control" id="google_analytics" name="google_analytics" value="<?= htmlspecialchars($settings['google_analytics'] ?? '') ?>">
                <small class="form-text text-muted">Örnek: UA-XXXXXXXXX-X veya G-XXXXXXXXXX</small>
            </div>
        </div>
    </div>

    <!-- Sosyal Medya -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-share-alt me-2"></i>Sosyal Medya
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="social_facebook" class="form-label">Facebook</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                    <input type="url" class="form-control" id="social_facebook" name="social_facebook" value="<?= htmlspecialchars($settings['social_facebook'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="social_twitter" class="form-label">Twitter</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                    <input type="url" class="form-control" id="social_twitter" name="social_twitter" value="<?= htmlspecialchars($settings['social_twitter'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="social_instagram" class="form-label">Instagram</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                    <input type="url" class="form-control" id="social_instagram" name="social_instagram" value="<?= htmlspecialchars($settings['social_instagram'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="social_linkedin" class="form-label">LinkedIn</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                    <input type="url" class="form-control" id="social_linkedin" name="social_linkedin" value="<?= htmlspecialchars($settings['social_linkedin'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="social_youtube" class="form-label">YouTube</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fab fa-youtube"></i></span>
                    <input type="url" class="form-control" id="social_youtube" name="social_youtube" value="<?= htmlspecialchars($settings['social_youtube'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- API Anahtarları -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-key me-2"></i>API Anahtarları
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="api_google_maps" class="form-label">Google Maps API Anahtarı</label>
                <input type="text" class="form-control" id="api_google_maps" name="api_google_maps" value="<?= htmlspecialchars($settings['api_google_maps'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="api_recaptcha_site" class="form-label">reCAPTCHA Site Anahtarı</label>
                <input type="text" class="form-control" id="api_recaptcha_site" name="api_recaptcha_site" value="<?= htmlspecialchars($settings['api_recaptcha_site'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="api_recaptcha_secret" class="form-label">reCAPTCHA Gizli Anahtar</label>
                <input type="text" class="form-control" id="api_recaptcha_secret" name="api_recaptcha_secret" value="<?= htmlspecialchars($settings['api_recaptcha_secret'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="api_payment_gateway" class="form-label">Ödeme API Anahtarı</label>
                <input type="text" class="form-control" id="api_payment_gateway" name="api_payment_gateway" value="<?= htmlspecialchars($settings['api_payment_gateway'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="api_webhook_url" class="form-label">Webhook URL</label>
                <input type="url" class="form-control" id="api_webhook_url" name="api_webhook_url" value="<?= htmlspecialchars($settings['api_webhook_url'] ?? '') ?>">
            </div>
        </div>
    </div>

    <!-- Mail Ayarları -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="card-title mb-0">
                <i class="fas fa-envelope me-2"></i>Mail Ayarları
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="mail_host" class="form-label">Mail Sunucusu (Host)</label>
                <input type="text" class="form-control" id="mail_host" name="mail_host" value="<?= htmlspecialchars($settings['mail_host'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="mail_port" class="form-label">Mail Portu</label>
                <input type="text" class="form-control" id="mail_port" name="mail_port" value="<?= htmlspecialchars($settings['mail_port'] ?? '587') ?>">
            </div>
            <div class="mb-3">
                <label for="mail_username" class="form-label">Mail Kullanıcı Adı</label>
                <input type="text" class="form-control" id="mail_username" name="mail_username" value="<?= htmlspecialchars($settings['mail_username'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="mail_password" class="form-label">Mail Şifresi</label>
                <input type="password" class="form-control" id="mail_password" name="mail_password" value="<?= htmlspecialchars($settings['mail_password'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="mail_encryption" class="form-label">Mail Şifreleme</label>
                <select class="form-select" id="mail_encryption" name="mail_encryption">
                    <option value="tls" <?= ($settings['mail_encryption'] ?? 'tls') == 'tls' ? 'selected' : '' ?>>TLS</option>
                    <option value="ssl" <?= ($settings['mail_encryption'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                    <option value="none" <?= ($settings['mail_encryption'] ?? '') == 'none' ? 'selected' : '' ?>>Yok</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Özel CSS/JS -->
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-code me-2"></i>Özel CSS/JS
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="custom_css" class="form-label">Özel CSS</label>
                <textarea class="form-control font-monospace" id="custom_css" name="custom_css" rows="8" style="font-size: 0.9rem;"><?= htmlspecialchars($settings['custom_css'] ?? '') ?></textarea>
                <small class="form-text text-muted">Site genelinde kullanılacak özel CSS kodları</small>
            </div>

            <div class="mb-3">
                <label for="custom_js" class="form-label">Özel JavaScript</label>
                <textarea class="form-control font-monospace" id="custom_js" name="custom_js" rows="8" style="font-size: 0.9rem;"><?= htmlspecialchars($settings['custom_js'] ?? '') ?></textarea>
                <small class="form-text text-muted">Site genelinde kullanılacak özel JavaScript kodları</small>
            </div>

            <div class="mb-3">
                <label for="custom_header" class="form-label">Header'a Eklenecek Kodlar</label>
                <textarea class="form-control font-monospace" id="custom_header" name="custom_header" rows="4" style="font-size: 0.9rem;"><?= htmlspecialchars($settings['custom_header'] ?? '') ?></textarea>
                <small class="form-text text-muted">HTML &lt;head&gt; bölümüne eklenecek kodlar</small>
            </div>

            <div class="mb-3">
                <label for="custom_footer" class="form-label">Footer'a Eklenecek Kodlar</label>
                <textarea class="form-control font-monospace" id="custom_footer" name="custom_footer" rows="4" style="font-size: 0.9rem;"><?= htmlspecialchars($settings['custom_footer'] ?? '') ?></textarea>
                <small class="form-text text-muted">HTML &lt;body&gt; kapanmadan önce eklenecek kodlar</small>
            </div>
        </div>
    </div>
</form> 