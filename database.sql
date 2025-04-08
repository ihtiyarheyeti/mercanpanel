-- Veritabanını oluştur
CREATE DATABASE IF NOT EXISTS admin_panel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE admin_panel;

-- Kullanıcılar tablosu
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100),
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    phone VARCHAR(20),
    address TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kullanıcı logları tablosu
CREATE TABLE IF NOT EXISTS user_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Dosyalar tablosu
CREATE TABLE IF NOT EXISTS files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size BIGINT NOT NULL,
    is_public TINYINT(1) DEFAULT 0,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ayarlar tablosu
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- İzinler tablosu
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Kullanıcı izinleri tablosu
CREATE TABLE IF NOT EXISTS user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY user_permission_unique (user_id, permission_id)
) ENGINE=InnoDB;

-- Varsayılan admin kullanıcısı
INSERT INTO users (username, password, email, full_name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'System Admin', 'admin');

-- Varsayılan izinler
INSERT INTO permissions (name, description) VALUES
('manage_users', 'Kullanıcıları yönetme izni'),
('manage_files', 'Dosyaları yönetme izni'),
('manage_settings', 'Ayarları yönetme izni'),
('view_logs', 'Logları görüntüleme izni');

-- Admin kullanıcısına tüm izinleri ver
INSERT INTO user_permissions (user_id, permission_id)
SELECT 1, id FROM permissions;

-- Varsayılan ayarlar
INSERT INTO settings (setting_key, setting_value) VALUES
('site_title', 'Lutuf Panel'),
('site_description', 'Dosya ve Kullanıcı Yönetim Paneli'),
('site_keywords', 'panel, yönetim, dosya, kullanıcı'),
('site_logo', ''),
('site_favicon', ''),
('site_email', 'admin@example.com'),
('smtp_host', ''),
('smtp_port', '587'),
('smtp_user', ''),
('smtp_pass', ''),
('smtp_secure', 'tls'),
('file_max_size', '10485760'),
('file_allowed_types', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip,rar'),
('version', '1.0.0');

-- Hizmetler tablosu
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Kullanıcı aktivite logları tablosu
CREATE TABLE IF NOT EXISTS user_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- İzinler tablosu
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Kullanıcı izinleri tablosu
CREATE TABLE IF NOT EXISTS user_permissions (
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, permission_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- Bildirim tercihleri tablosu
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    email_notifications TINYINT(1) NOT NULL DEFAULT 1,
    push_notifications TINYINT(1) NOT NULL DEFAULT 1,
    login_notifications TINYINT(1) NOT NULL DEFAULT 1,
    permission_changes TINYINT(1) NOT NULL DEFAULT 1,
    profile_updates TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Kullanıcı 2FA tablosu
CREATE TABLE IF NOT EXISTS user_2fa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    secret_key VARCHAR(32) NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2FA giriş denemeleri tablosu
CREATE TABLE IF NOT EXISTS 2fa_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Dosya kategorileri tablosu
CREATE TABLE IF NOT EXISTS file_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    parent_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES file_categories(id) ON DELETE SET NULL
);

-- Dosya-kategori ilişki tablosu
CREATE TABLE IF NOT EXISTS file_category_relations (
    file_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (file_id, category_id),
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES file_categories(id) ON DELETE CASCADE
);

-- Dosya izinleri tablosu
CREATE TABLE IF NOT EXISTS file_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id INT NOT NULL,
    user_id INT NOT NULL,
    can_view TINYINT(1) NOT NULL DEFAULT 0,
    can_download TINYINT(1) NOT NULL DEFAULT 0,
    can_edit TINYINT(1) NOT NULL DEFAULT 0,
    can_delete TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY file_user (file_id, user_id),
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- İstatistik tablosu
CREATE TABLE IF NOT EXISTS statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    total_users INT DEFAULT 0,
    active_users INT DEFAULT 0,
    total_files INT DEFAULT 0,
    total_downloads INT DEFAULT 0,
    total_uploads INT DEFAULT 0,
    total_logins INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY date_unique (date)
);

-- Rapor şablonları tablosu
CREATE TABLE IF NOT EXISTS report_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    template_type ENUM('user', 'file', 'system', 'custom') NOT NULL,
    template_content TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Raporlar tablosu
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    report_data JSON,
    generated_by INT NOT NULL,
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES report_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Varsayılan ayarları ekle
INSERT INTO settings (setting_key, setting_value) VALUES
('site_title', 'Admin Panel'),
('site_logo', 'assets/images/logo.png'),
('contact_email', 'info@example.com'),
('active_theme', 'default'),
('meta_description', 'Admin Panel - Site Yönetim Sistemi'),
('meta_keywords', 'admin, panel, yönetim, sistem'),
('facebook_url', ''),
('twitter_url', ''),
('instagram_url', ''),
('google_analytics_id', ''),
('recaptcha_site_key', ''),
('recaptcha_secret_key', ''),
('custom_css', ''),
('custom_js', '');

-- Varsayılan admin kullanıcısı oluştur (şifre: admin123)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Varsayılan izinleri ekle
INSERT INTO permissions (name, description) VALUES
('view_dashboard', 'Dashboard görüntüleme'),
('manage_users', 'Kullanıcı yönetimi'),
('manage_services', 'Hizmet yönetimi'),
('manage_settings', 'Ayarlar yönetimi'),
('view_logs', 'Log görüntüleme'),
('manage_permissions', 'İzin yönetimi');

-- Admin kullanıcısına tüm izinleri ver
INSERT INTO user_permissions (user_id, permission_id)
SELECT u.id, p.id
FROM users u
CROSS JOIN permissions p
WHERE u.role = 'admin';

-- Varsayılan bildirim tercihlerini ekle
INSERT INTO notification_preferences (user_id, email_notifications, push_notifications, login_notifications, permission_changes, profile_updates)
SELECT id, 1, 1, 1, 1, 1 FROM users;

-- Varsayılan dosya kategorileri
INSERT INTO file_categories (name, slug, description) VALUES
('Resimler', 'images', 'Resim dosyaları'),
('Dökümanlar', 'documents', 'Döküman dosyaları'),
('Medya', 'media', 'Ses ve video dosyaları'),
('Arşivler', 'archives', 'Sıkıştırılmış dosyalar');

-- Varsayılan rapor şablonları
INSERT INTO report_templates (name, description, template_type, template_content, created_by) VALUES
('Kullanıcı Aktivite Raporu', 'Kullanıcı aktivitelerini gösteren rapor', 'user', '{"sections":["login_history","file_activities","profile_changes"]}', 1),
('Dosya İstatistikleri', 'Dosya yükleme ve indirme istatistikleri', 'file', '{"sections":["upload_stats","download_stats","category_stats"]}', 1),
('Sistem Performansı', 'Sistem performans ve kullanım istatistikleri', 'system', '{"sections":["server_stats","user_stats","file_stats"]}', 1);

-- Varsayılan istatistik girişi
INSERT INTO statistics (date, total_users, active_users, total_files, total_downloads, total_uploads, total_logins)
VALUES (CURDATE(), 1, 1, 0, 0, 0, 0); 
 