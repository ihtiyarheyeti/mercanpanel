CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan ayarları ekle
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('file_max_size', '10485760'),
('file_allowed_types', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip,rar,txt,csv'); 