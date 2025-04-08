<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Settings.php';
require_once 'includes/helpers.php';

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Settings sınıfını başlat
$settings = Settings::getInstance($conn);

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF kontrolü
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Güvenlik doğrulaması başarısız.');
        }

        // Ayarları güncelle
        $settingsToUpdate = [
            'site_title' => $_POST['site_title'] ?? '',
            'site_description' => $_POST['site_description'] ?? '',
            'admin_email' => $_POST['admin_email'] ?? '',
            'max_file_size' => (int)($_POST['max_file_size'] ?? 5) * 1024 * 1024, // MB to bytes
            'allowed_extensions' => $_POST['allowed_extensions'] ?? '',
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
            'timezone' => $_POST['timezone'] ?? 'Europe/Istanbul',
            'date_format' => $_POST['date_format'] ?? 'd.m.Y H:i',
            'theme_color' => $_POST['theme_color'] ?? '#0d6efd',
            'logo_path' => $_POST['logo_path'] ?? '',
            'favicon_path' => $_POST['favicon_path'] ?? '',
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_port' => $_POST['smtp_port'] ?? '',
            'smtp_username' => $_POST['smtp_username'] ?? '',
            'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
            
            // Güvenlik ayarları
            'require_2fa' => isset($_POST['require_2fa']) ? 1 : 0,
            'min_password_length' => (int)($_POST['min_password_length'] ?? 8),
            'password_complexity' => isset($_POST['password_complexity']) ? 1 : 0,
            'session_timeout' => (int)($_POST['session_timeout'] ?? 60),
            'login_attempts' => (int)($_POST['login_attempts'] ?? 5),
            'login_lockout_time' => (int)($_POST['login_lockout_time'] ?? 15),
            
            // Performans ayarları
            'cache_enabled' => isset($_POST['cache_enabled']) ? 1 : 0,
            'cache_time' => (int)($_POST['cache_time'] ?? 60),
            'items_per_page' => (int)($_POST['items_per_page'] ?? 20),
            'minify_html' => isset($_POST['minify_html']) ? 1 : 0,
            'gzip_compression' => isset($_POST['gzip_compression']) ? 1 : 0,
            
            // Yedekleme ayarları
            'auto_backup' => isset($_POST['auto_backup']) ? 1 : 0,
            'backup_frequency' => $_POST['backup_frequency'] ?? 'daily',
            'backup_retention' => (int)($_POST['backup_retention'] ?? 7),
            'backup_path' => $_POST['backup_path'] ?? 'backups/',
            'backup_include_files' => isset($_POST['backup_include_files']) ? 1 : 0
        ];

        // SMTP şifresi sadece girilmişse güncelle
        if (!empty($_POST['smtp_password'])) {
            $settingsToUpdate['smtp_password'] = $_POST['smtp_password'];
        }

        // Ayarları toplu güncelle
        $settings->updateMultiple($settingsToUpdate);

        $_SESSION['success'] = 'Ayarlar başarıyla güncellendi.';
        header('Location: settings.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Hata: ' . $e->getMessage();
    }
}

// CSRF token oluştur
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Mevcut ayarları al
$current_settings = [
    'site_title' => $settings->get('site_title', 'Admin Panel'),
    'site_description' => $settings->get('site_description', ''),
    'admin_email' => $settings->get('admin_email', ''),
    'max_file_size' => $settings->get('max_file_size', 5 * 1024 * 1024) / 1024 / 1024, // bytes to MB
    'allowed_extensions' => $settings->get('allowed_extensions', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx'),
    'maintenance_mode' => $settings->get('maintenance_mode', 0),
    'timezone' => $settings->get('timezone', 'Europe/Istanbul'),
    'date_format' => $settings->get('date_format', 'd.m.Y H:i'),
    'theme_color' => $settings->get('theme_color', '#0d6efd'),
    'logo_path' => $settings->get('logo_path', ''),
    'favicon_path' => $settings->get('favicon_path', ''),
    'smtp_host' => $settings->get('smtp_host', ''),
    'smtp_port' => $settings->get('smtp_port', ''),
    'smtp_username' => $settings->get('smtp_username', ''),
    'smtp_encryption' => $settings->get('smtp_encryption', 'tls'),
    
    // Güvenlik ayarları
    'require_2fa' => $settings->get('require_2fa', 0),
    'min_password_length' => $settings->get('min_password_length', 8),
    'password_complexity' => $settings->get('password_complexity', 0),
    'session_timeout' => $settings->get('session_timeout', 60),
    'login_attempts' => $settings->get('login_attempts', 5),
    'login_lockout_time' => $settings->get('login_lockout_time', 15),
    
    // Performans ayarları
    'cache_enabled' => $settings->get('cache_enabled', 0),
    'cache_time' => $settings->get('cache_time', 60),
    'items_per_page' => $settings->get('items_per_page', 20),
    'minify_html' => $settings->get('minify_html', 0),
    'gzip_compression' => $settings->get('gzip_compression', 0),
    
    // Yedekleme ayarları
    'auto_backup' => $settings->get('auto_backup', 0),
    'backup_frequency' => $settings->get('backup_frequency', 'daily'),
    'backup_retention' => $settings->get('backup_retention', 7),
    'backup_path' => $settings->get('backup_path', 'backups/'),
    'backup_include_files' => $settings->get('backup_include_files', 1)
];

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Gelişmiş Ayarlar</h1>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('settingsForm').submit();">
                    <i class="fas fa-save me-1"></i> Kaydet
                </button>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                </div>
            <?php endif; ?>

            <form id="settingsForm" method="post" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="row g-4">
                    <!-- Genel Ayarlar -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Genel Ayarlar</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="site_title" class="form-label">Site Başlığı</label>
                                    <input type="text" class="form-control" id="site_title" name="site_title" 
                                           value="<?php echo htmlspecialchars($current_settings['site_title']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="site_description" class="form-label">Site Açıklaması</label>
                                    <textarea class="form-control" id="site_description" name="site_description" rows="2"><?php echo htmlspecialchars($current_settings['site_description']); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="admin_email" class="form-label">Admin E-posta</label>
                                    <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                           value="<?php echo htmlspecialchars($current_settings['admin_email']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="timezone" class="form-label">Saat Dilimi</label>
                                    <select class="form-select" id="timezone" name="timezone">
                                        <?php
                                        $timezones = DateTimeZone::listIdentifiers();
                                        foreach ($timezones as $tz) {
                                            $selected = $tz === $current_settings['timezone'] ? 'selected' : '';
                                            echo "<option value=\"$tz\" $selected>$tz</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="date_format" class="form-label">Tarih Formatı</label>
                                    <input type="text" class="form-control" id="date_format" name="date_format" 
                                           value="<?php echo htmlspecialchars($current_settings['date_format']); ?>" required>
                                    <div class="form-text">Örnek: d.m.Y H:i</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dosya Ayarları -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-file me-2"></i>Dosya Ayarları</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="max_file_size" class="form-label">Maksimum Dosya Boyutu (MB)</label>
                                    <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                                           value="<?php echo htmlspecialchars($current_settings['max_file_size']); ?>" required min="1" max="100">
                                </div>
                                <div class="mb-3">
                                    <label for="allowed_extensions" class="form-label">İzin Verilen Uzantılar</label>
                                    <input type="text" class="form-control" id="allowed_extensions" name="allowed_extensions" 
                                           value="<?php echo htmlspecialchars($current_settings['allowed_extensions']); ?>" required>
                                    <div class="form-text">Virgülle ayırarak yazın (örn: jpg,png,pdf)</div>
                                </div>
                                <div class="mb-3">
                                    <label for="logo_path" class="form-label">Logo Yolu</label>
                                    <input type="text" class="form-control" id="logo_path" name="logo_path" 
                                           value="<?php echo htmlspecialchars($current_settings['logo_path']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="favicon_path" class="form-label">Favicon Yolu</label>
                                    <input type="text" class="form-control" id="favicon_path" name="favicon_path" 
                                           value="<?php echo htmlspecialchars($current_settings['favicon_path']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- E-posta Ayarları -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>E-posta Ayarları</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="smtp_host" class="form-label">SMTP Sunucu</label>
                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                           value="<?php echo htmlspecialchars($current_settings['smtp_host']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="smtp_port" class="form-label">SMTP Port</label>
                                    <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                           value="<?php echo htmlspecialchars($current_settings['smtp_port']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="smtp_username" class="form-label">SMTP Kullanıcı Adı</label>
                                    <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                                           value="<?php echo htmlspecialchars($current_settings['smtp_username']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="smtp_password" class="form-label">SMTP Şifre</label>
                                    <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                           placeholder="Değiştirmek için yeni şifre girin">
                                </div>
                                <div class="mb-3">
                                    <label for="smtp_encryption" class="form-label">SMTP Güvenlik</label>
                                    <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                        <option value="tls" <?php echo $current_settings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?php echo $current_settings['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                        <option value="none" <?php echo $current_settings['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>Yok</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Görünüm Ayarları -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-paint-brush me-2"></i>Görünüm Ayarları</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="theme_color" class="form-label">Tema Rengi</label>
                                    <input type="color" class="form-control form-control-color w-100" id="theme_color" name="theme_color" 
                                           value="<?php echo htmlspecialchars($current_settings['theme_color']); ?>">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                               <?php echo $current_settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="maintenance_mode">Bakım Modu</label>
                                    </div>
                                    <div class="form-text">Bakım modu aktif olduğunda sadece yöneticiler giriş yapabilir.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Güvenlik Ayarları -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Güvenlik Ayarları</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="require_2fa" name="require_2fa" 
                                               <?php echo $current_settings['require_2fa'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="require_2fa">İki Faktörlü Kimlik Doğrulama Zorunlu</label>
                                    </div>
                                    <div class="form-text">Tüm kullanıcılara 2FA kullanma zorunluluğu getirir.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="min_password_length" class="form-label">Minimum Şifre Uzunluğu</label>
                                    <input type="number" class="form-control" id="min_password_length" name="min_password_length" 
                                           value="<?php echo htmlspecialchars($current_settings['min_password_length']); ?>" required min="6" max="32">
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="password_complexity" name="password_complexity" 
                                               <?php echo $current_settings['password_complexity'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="password_complexity">Şifre Karmaşıklığı Gerekli</label>
                                    </div>
                                    <div class="form-text">Şifrelerde büyük-küçük harf, sayı ve özel karakter zorunluluğu.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="session_timeout" class="form-label">Oturum Zaman Aşımı (dakika)</label>
                                    <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                           value="<?php echo htmlspecialchars($current_settings['session_timeout']); ?>" required min="5" max="1440">
                                    <div class="form-text">Kullanıcı hareketsizliğinde otomatik çıkış süresi.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="login_attempts" class="form-label">Maksimum Giriş Denemesi</label>
                                    <input type="number" class="form-control" id="login_attempts" name="login_attempts" 
                                           value="<?php echo htmlspecialchars($current_settings['login_attempts']); ?>" required min="3" max="10">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="login_lockout_time" class="form-label">Hesap Kilitleme Süresi (dakika)</label>
                                    <input type="number" class="form-control" id="login_lockout_time" name="login_lockout_time" 
                                           value="<?php echo htmlspecialchars($current_settings['login_lockout_time']); ?>" required min="5" max="60">
                                    <div class="form-text">Maksimum giriş denemesi aşıldığında hesabın kilitlenme süresi.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performans Ayarları -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Performans Ayarları</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="cache_enabled" name="cache_enabled" 
                                               <?php echo $current_settings['cache_enabled'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="cache_enabled">Önbellek Etkin</label>
                                    </div>
                                    <div class="form-text">Sistem performansını artırmak için önbelleğe alma işlemini etkinleştirir.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="cache_time" class="form-label">Önbellek Süresi (dakika)</label>
                                    <input type="number" class="form-control" id="cache_time" name="cache_time" 
                                           value="<?php echo htmlspecialchars($current_settings['cache_time']); ?>" required min="5" max="1440">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="items_per_page" class="form-label">Sayfa Başına Öğe Sayısı</label>
                                    <input type="number" class="form-control" id="items_per_page" name="items_per_page" 
                                           value="<?php echo htmlspecialchars($current_settings['items_per_page']); ?>" required min="5" max="100">
                                    <div class="form-text">Listelenen dosya ve kullanıcıların sayfa başına maksimum sayısı.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="minify_html" name="minify_html" 
                                               <?php echo $current_settings['minify_html'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="minify_html">HTML Sıkıştırma</label>
                                    </div>
                                    <div class="form-text">Sayfa yükleme hızını artırmak için HTML çıktısını sıkıştırır.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="gzip_compression" name="gzip_compression" 
                                               <?php echo $current_settings['gzip_compression'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="gzip_compression">GZIP Sıkıştırma</label>
                                    </div>
                                    <div class="form-text">Sayfalara gzip sıkıştırma uygular (sunucu desteği gerektirir).</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Yedekleme Ayarları -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-database me-2"></i>Yedekleme Ayarları</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="auto_backup" name="auto_backup" 
                                               <?php echo $current_settings['auto_backup'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="auto_backup">Otomatik Yedekleme</label>
                                    </div>
                                    <div class="form-text">Belirtilen sıklıkta otomatik yedekleme işlemi gerçekleştirir.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="backup_frequency" class="form-label">Yedekleme Sıklığı</label>
                                    <select class="form-select" id="backup_frequency" name="backup_frequency">
                                        <option value="daily" <?php echo $current_settings['backup_frequency'] === 'daily' ? 'selected' : ''; ?>>Günlük</option>
                                        <option value="weekly" <?php echo $current_settings['backup_frequency'] === 'weekly' ? 'selected' : ''; ?>>Haftalık</option>
                                        <option value="monthly" <?php echo $current_settings['backup_frequency'] === 'monthly' ? 'selected' : ''; ?>>Aylık</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="backup_retention" class="form-label">Yedek Saklama Süresi (gün)</label>
                                    <input type="number" class="form-control" id="backup_retention" name="backup_retention" 
                                           value="<?php echo htmlspecialchars($current_settings['backup_retention']); ?>" required min="1" max="90">
                                    <div class="form-text">Eski yedekler bu süreden sonra otomatik silinir.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="backup_path" class="form-label">Yedekleme Klasörü</label>
                                    <input type="text" class="form-control" id="backup_path" name="backup_path" 
                                           value="<?php echo htmlspecialchars($current_settings['backup_path']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="backup_include_files" name="backup_include_files" 
                                               <?php echo $current_settings['backup_include_files'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="backup_include_files">Dosyaları Yedekle</label>
                                    </div>
                                    <div class="form-text">Yedeklemelere dosyaları da dahil eder (veritabanı her zaman dahildir).</div>
                                </div>
                                
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-primary" id="manual_backup">
                                        <i class="fas fa-download me-1"></i> Manuel Yedekleme
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Form doğrulama
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Tema rengi değiştiğinde önizleme
document.getElementById('theme_color').addEventListener('change', function(e) {
    document.documentElement.style.setProperty('--bs-primary', e.target.value);
});

document.addEventListener('DOMContentLoaded', function() {
    // Manuel yedekleme
    document.getElementById('manual_backup').addEventListener('click', function() {
        if (confirm('Veritabanı ve dosyaların manuel yedeği alınacak. Devam etmek istiyor musunuz?')) {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Yedekleniyor...';
            
            fetch('backup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>'
                },
                body: JSON.stringify({action: 'manual_backup'})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Yedekleme başarıyla tamamlandı: ' + data.message);
                } else {
                    throw new Error(data.message || 'Yedekleme işlemi başarısız oldu.');
                }
            })
            .catch(error => {
                alert('Hata: ' + error.message);
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-download me-1"></i> Manuel Yedekleme';
            });
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?> 
 