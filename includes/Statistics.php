<?php
require_once 'config/database.php';

class Statistics {
    private $conn;
    private $charts_dir = 'assets/charts/';
    private $reports_dir = 'assets/reports/';

    public function __construct($conn) {
        $this->conn = $conn;
        
        // Dizinleri oluştur
        if (!file_exists($this->charts_dir)) {
            mkdir($this->charts_dir, 0777, true);
        }
        if (!file_exists($this->reports_dir)) {
            mkdir($this->reports_dir, 0777, true);
        }
    }

    // Günlük istatistikleri güncelle
    public function updateDailyStats() {
        try {
            $this->conn->beginTransaction();
            
            // Günlük istatistikleri güncelle
            $stmt = $this->conn->prepare("
                INSERT INTO statistics (
                    date, total_users, active_users, total_files, 
                    total_downloads, total_uploads, total_logins
                ) VALUES (
                    CURDATE(),
                    (SELECT COUNT(*) FROM users),
                    (SELECT COUNT(*) FROM users WHERE last_login >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
                    (SELECT COUNT(*) FROM files),
                    (SELECT SUM(download_count) FROM files),
                    (SELECT COUNT(*) FROM files WHERE DATE(created_at) = CURDATE()),
                    (SELECT COUNT(*) FROM user_logs WHERE action = 'login' AND DATE(created_at) = CURDATE())
                ) ON DUPLICATE KEY UPDATE
                    total_users = VALUES(total_users),
                    active_users = VALUES(active_users),
                    total_files = VALUES(total_files),
                    total_downloads = VALUES(total_downloads),
                    total_uploads = VALUES(total_uploads),
                    total_logins = VALUES(total_logins)
            ");
            
            $stmt->execute();
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("İstatistik güncelleme hatası: " . $e->getMessage());
            return false;
        }
    }

    // Mevcut istatistikleri al
    private function getCurrentStats() {
        $stats = [
            'total_users' => 0,
            'active_users' => 0,
            'total_files' => 0,
            'total_downloads' => 0,
            'total_uploads' => 0,
            'total_logins' => 0
        ];

        // Toplam kullanıcı sayısı
        $stmt = $this->conn->query("SELECT COUNT(*) FROM users");
        $stats['total_users'] = $stmt->fetchColumn();

        // Aktif kullanıcı sayısı (son 24 saat içinde giriş yapan)
        $stmt = $this->conn->query("
            SELECT COUNT(DISTINCT user_id) 
            FROM user_logs 
            WHERE action = 'login' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stats['active_users'] = $stmt->fetchColumn();

        // Dosya istatistikleri
        $stmt = $this->conn->query("
            SELECT 
                COUNT(*) as total_files,
                SUM(download_count) as total_downloads,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as total_uploads
            FROM files
        ");
        $file_stats = $stmt->fetch();
        $stats['total_files'] = $file_stats['total_files'];
        $stats['total_downloads'] = $file_stats['total_downloads'];
        $stats['total_uploads'] = $file_stats['total_uploads'];

        // Giriş sayısı
        $stmt = $this->conn->query("
            SELECT COUNT(*) 
            FROM user_logs 
            WHERE action = 'login' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stats['total_logins'] = $stmt->fetchColumn();

        return $stats;
    }

    // Belirli bir tarih aralığı için istatistikleri al
    public function getStatsByDateRange($start_date, $end_date) {
        $stmt = $this->conn->prepare("
            SELECT * FROM statistics 
            WHERE date BETWEEN ? AND ?
            ORDER BY date ASC
        ");
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll();
    }

    // Grafik oluştur
    public function generateChart($data, $type = 'line', $title = '') {
        require_once 'vendor/autoload.php';
        
        $chart = new \PhpOffice\PhpSpreadsheet\Chart\Chart(
            $title,
            new \PhpOffice\PhpSpreadsheet\Chart\Title('X-Axis'),
            new \PhpOffice\PhpSpreadsheet\Chart\Title('Y-Axis'),
            new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(
                $data,
                \PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_LINECHART,
                \PhpOffice\PhpSpreadsheet\Chart\DataSeries::GROUPING_STANDARD,
                range(0, count($data) - 1),
                null,
                null
            )
        );

        $filename = $this->charts_dir . uniqid() . '.png';
        $chart->render($filename);
        
        return $filename;
    }

    // Rapor oluştur
    public function generateReport($template_id, $user_id, $params = []) {
        $template = $this->getReportTemplate($template_id);
        if (!$template) {
            throw new Exception('Rapor şablonu bulunamadı');
        }

        $report_data = $this->getReportData($template['template_type'], $params);
        
        // PDF oluştur
        require_once 'vendor/autoload.php';
        $pdf = new \TCPDF();
        $pdf->AddPage();
        
        // Rapor içeriğini oluştur
        $content = $this->generateReportContent($template, $report_data);
        $pdf->writeHTML($content);
        
        $filename = $this->reports_dir . uniqid() . '.pdf';
        $pdf->Output($filename, 'F');
        
        // Raporu veritabanına kaydet
        $stmt = $this->conn->prepare("
            INSERT INTO reports (template_id, title, description, report_data, generated_by, file_path)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $template_id,
            $template['name'],
            $template['description'],
            json_encode($report_data),
            $user_id,
            $filename
        ]);
        
        return $filename;
    }

    // Rapor şablonunu al
    private function getReportTemplate($template_id) {
        $stmt = $this->conn->prepare("SELECT * FROM report_templates WHERE id = ?");
        $stmt->execute([$template_id]);
        return $stmt->fetch();
    }

    // Rapor verilerini al
    private function getReportData($type, $params) {
        switch ($type) {
            case 'user':
                return $this->getUserReportData($params);
            case 'file':
                return $this->getFileReportData($params);
            case 'system':
                return $this->getSystemReportData($params);
            default:
                return [];
        }
    }

    // Kullanıcı raporu verilerini al
    private function getUserReportData($params) {
        $data = [];
        
        // Giriş geçmişi
        $stmt = $this->conn->query("
            SELECT u.username, ul.action, ul.created_at, ul.ip_address
            FROM user_logs ul
            JOIN users u ON ul.user_id = u.id
            ORDER BY ul.created_at DESC
            LIMIT 100
        ");
        $data['login_history'] = $stmt->fetchAll();
        
        // Dosya aktiviteleri
        $stmt = $this->conn->query("
            SELECT u.username, f.original_name, f.created_at, f.download_count
            FROM files f
            JOIN users u ON f.user_id = u.id
            ORDER BY f.created_at DESC
            LIMIT 100
        ");
        $data['file_activities'] = $stmt->fetchAll();
        
        return $data;
    }

    // Dosya raporu verilerini al
    private function getFileReportData($params) {
        $data = [];
        
        // Yükleme istatistikleri
        $stmt = $this->conn->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as upload_count,
                SUM(file_size) as total_size
            FROM files
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 30
        ");
        $data['upload_stats'] = $stmt->fetchAll();
        
        // İndirme istatistikleri
        $stmt = $this->conn->query("
            SELECT 
                f.original_name,
                f.download_count,
                f.file_size,
                u.username
            FROM files f
            JOIN users u ON f.user_id = u.id
            ORDER BY f.download_count DESC
            LIMIT 50
        ");
        $data['download_stats'] = $stmt->fetchAll();
        
        // Kategori istatistikleri
        $stmt = $this->conn->query("
            SELECT 
                fc.name as category,
                COUNT(f.id) as file_count,
                SUM(f.download_count) as total_downloads
            FROM file_categories fc
            LEFT JOIN file_category_relations fcr ON fc.id = fcr.category_id
            LEFT JOIN files f ON fcr.file_id = f.id
            GROUP BY fc.id
        ");
        $data['category_stats'] = $stmt->fetchAll();
        
        return $data;
    }

    // Sistem raporu verilerini al
    private function getSystemReportData($params) {
        $data = [];
        
        // Sunucu istatistikleri
        $data['server_stats'] = [
            'memory_usage' => memory_get_usage(true),
            'disk_usage' => disk_free_space('/'),
            'cpu_usage' => sys_getloadavg()[0]
        ];
        
        // Kullanıcı istatistikleri
        $stmt = $this->conn->query("
            SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_count,
                COUNT(CASE WHEN role = 'editor' THEN 1 END) as editor_count
            FROM users
        ");
        $data['user_stats'] = $stmt->fetch();
        
        // Dosya istatistikleri
        $stmt = $this->conn->query("
            SELECT 
                COUNT(*) as total_files,
                SUM(file_size) as total_size,
                AVG(file_size) as avg_size,
                COUNT(DISTINCT file_type) as type_count
            FROM files
        ");
        $data['file_stats'] = $stmt->fetch();
        
        return $data;
    }

    // Rapor içeriğini oluştur
    private function generateReportContent($template, $data) {
        $content = '<h1>' . htmlspecialchars($template['name']) . '</h1>';
        $content .= '<p>' . htmlspecialchars($template['description']) . '</p>';
        
        $sections = json_decode($template['template_content'], true)['sections'];
        
        foreach ($sections as $section) {
            if (isset($data[$section])) {
                $content .= $this->generateSectionContent($section, $data[$section]);
            }
        }
        
        return $content;
    }

    // Bölüm içeriğini oluştur
    private function generateSectionContent($section, $data) {
        $content = '<h2>' . ucfirst(str_replace('_', ' ', $section)) . '</h2>';
        
        switch ($section) {
            case 'login_history':
            case 'file_activities':
                $content .= $this->generateTableContent($data);
                break;
            case 'upload_stats':
            case 'download_stats':
            case 'category_stats':
                $content .= $this->generateChartContent($data);
                break;
            case 'server_stats':
            case 'user_stats':
            case 'file_stats':
                $content .= $this->generateStatsContent($data);
                break;
        }
        
        return $content;
    }

    // Tablo içeriğini oluştur
    private function generateTableContent($data) {
        if (empty($data)) {
            return '<p>Veri bulunamadı</p>';
        }
        
        $content = '<table border="1" cellpadding="5">';
        $content .= '<tr>';
        foreach (array_keys($data[0]) as $header) {
            $content .= '<th>' . ucfirst(str_replace('_', ' ', $header)) . '</th>';
        }
        $content .= '</tr>';
        
        foreach ($data as $row) {
            $content .= '<tr>';
            foreach ($row as $cell) {
                $content .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $content .= '</tr>';
        }
        
        $content .= '</table>';
        return $content;
    }

    // Grafik içeriğini oluştur
    private function generateChartContent($data) {
        if (empty($data)) {
            return '<p>Veri bulunamadı</p>';
        }
        
        $chart_data = [];
        foreach ($data as $row) {
            $chart_data[] = [
                'label' => $row['date'] ?? $row['original_name'] ?? $row['category'],
                'value' => $row['upload_count'] ?? $row['download_count'] ?? $row['file_count']
            ];
        }
        
        $chart_file = $this->generateChart($chart_data);
        return '<img src="' . $chart_file . '" alt="Chart">';
    }

    // İstatistik içeriğini oluştur
    private function generateStatsContent($data) {
        if (empty($data)) {
            return '<p>Veri bulunamadı</p>';
        }
        
        $content = '<ul>';
        foreach ($data as $key => $value) {
            $content .= '<li><strong>' . ucfirst(str_replace('_', ' ', $key)) . ':</strong> ' . 
                       $this->formatValue($key, $value) . '</li>';
        }
        $content .= '</ul>';
        
        return $content;
    }

    // Değerleri formatla
    private function formatValue($key, $value) {
        switch ($key) {
            case 'memory_usage':
                return $this->formatBytes($value);
            case 'disk_usage':
                return $this->formatBytes($value);
            case 'total_size':
                return $this->formatBytes($value);
            case 'avg_size':
                return $this->formatBytes($value);
            default:
                return htmlspecialchars($value);
        }
    }

    // Bayt değerlerini formatla
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function getDailyStats($start_date = null, $end_date = null) {
        $sql = "SELECT * FROM statistics WHERE 1=1";
        $params = [];
        
        if ($start_date) {
            $sql .= " AND date >= ?";
            $params[] = $start_date;
        }
        
        if ($end_date) {
            $sql .= " AND date <= ?";
            $params[] = $end_date;
        }
        
        $sql .= " ORDER BY date DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getFileStats() {
        $stats = [];
        
        // Toplam dosya sayısı
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM files");
        $stmt->execute();
        $stats['total_files'] = $stmt->fetch()['count'];
        
        // Dosya türlerine göre dağılım
        $stmt = $this->conn->prepare("
            SELECT file_type, COUNT(*) as count 
            FROM files 
            GROUP BY file_type
        ");
        $stmt->execute();
        $stats['file_types'] = $stmt->fetchAll();
        
        // Toplam indirme sayısı
        $stmt = $this->conn->prepare("SELECT SUM(download_count) as count FROM files");
        $stmt->execute();
        $stats['total_downloads'] = $stmt->fetch()['count'];
        
        // Son 30 günlük yüklenen dosya sayısı
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM files 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $stats['recent_uploads'] = $stmt->fetch()['count'];
        
        return $stats;
    }
    
    public function getUserStats() {
        $stats = [];
        
        // Toplam kullanıcı sayısı
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $stats['total_users'] = $stmt->fetch()['count'];
        
        // Aktif kullanıcı sayısı (son 30 gün)
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE last_login >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $stats['active_users'] = $stmt->fetch()['count'];
        
        // Rol dağılımı
        $stmt = $this->conn->prepare("
            SELECT role, COUNT(*) as count 
            FROM users 
            GROUP BY role
        ");
        $stmt->execute();
        $stats['role_distribution'] = $stmt->fetchAll();
        
        // Son 30 günlük giriş sayısı
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM user_logs 
            WHERE action = 'login' 
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $stats['recent_logins'] = $stmt->fetch()['count'];
        
        return $stats;
    }
    
    public function getSystemStats() {
        $stats = [];
        
        // Disk kullanımı
        $stats['disk_usage'] = [
            'total' => disk_total_space('/'),
            'free' => disk_free_space('/'),
            'used' => disk_total_space('/') - disk_free_space('/')
        ];
        
        // PHP bellek kullanımı
        $stats['memory_usage'] = [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true)
        ];
        
        // Veritabanı boyutu
        $stmt = $this->conn->prepare("
            SELECT SUM(data_length + index_length) as size 
            FROM information_schema.tables 
            WHERE table_schema = ?
        ");
        $stmt->execute([$this->conn->query('SELECT DATABASE()')->fetchColumn()]);
        $stats['database_size'] = $stmt->fetch()['size'];
        
        return $stats;
    }
    
    public function getReport($report_id) {
        $stmt = $this->conn->prepare("
            SELECT r.*, u.username, t.name as template_name
            FROM reports r
            JOIN users u ON r.generated_by = u.id
            JOIN report_templates t ON r.template_id = t.id
            WHERE r.id = ?
        ");
        $stmt->execute([$report_id]);
        return $stmt->fetch();
    }
    
    public function getReports($user_id = null) {
        $sql = "SELECT r.*, u.username, t.name as template_name FROM reports r
                JOIN users u ON r.generated_by = u.id
                JOIN report_templates t ON r.template_id = t.id";
        
        if ($user_id) {
            $sql .= " WHERE r.generated_by = ?";
            $params = [$user_id];
        } else {
            $params = [];
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
} 
 