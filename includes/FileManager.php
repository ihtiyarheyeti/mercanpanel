<?php
class FileManager {
    private $conn;
    private $settings;
    private $upload_dir = 'uploads';
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->settings = Settings::getInstance($conn);
        
        // Upload dizinini oluştur
        if (!is_dir($this->upload_dir)) {
            if (!mkdir($this->upload_dir, 0777, true)) {
                error_log("Upload dizini oluşturulamadı: " . $this->upload_dir);
                throw new Exception("Dosya yükleme dizini oluşturulamadı.");
            }
        }
        
        // Dizin yazılabilir mi kontrol et
        if (!is_writable($this->upload_dir)) {
            if (!chmod($this->upload_dir, 0777)) {
                error_log("Upload dizini yazılabilir yapılamadı: " . $this->upload_dir);
                throw new Exception("Dosya yükleme dizini yazılabilir değil.");
            }
        }
    }
    
    /**
     * Dosya yükleme
     */
    public function uploadFile($file, $user_id, $is_public = false) {
        try {
            // Dosya kontrolü
            if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Dosya yüklenirken bir hata oluştu.');
            }
            
            // Boyut kontrolü
            $max_size = $this->settings->get('file_max_size', 10485760);
            if ($file['size'] > $max_size) {
                throw new Exception('Dosya boyutu çok büyük. Maksimum: ' . $this->formatSize($max_size));
            }
            
            // Tip kontrolü
            $allowed_types = explode(',', $this->settings->get('file_allowed_types', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip,rar'));
            $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Bu dosya türüne izin verilmiyor.');
            }
            
            // Dosya adını güvenli hale getir
            $original_filename = $file['name'];
            $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $original_filename);
            
            // Yıl/ay bazlı klasör yapısı
            $year_month = date('Y/m');
            $upload_path = $this->upload_dir . '/' . $year_month;
            
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            
            $file_path = $upload_path . '/' . $filename;
            
            // Dosyayı taşı
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                throw new Exception('Dosya kaydedilirken bir hata oluştu.');
            }
            
            // Veritabanına kaydet
            $stmt = $this->conn->prepare("
                INSERT INTO files (
                    user_id, filename, original_filename, 
                    file_path, file_type, file_size, is_public
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                $filename,
                $original_filename,
                $file_path,
                $file_type,
                $file['size'],
                $is_public ? 1 : 0
            ]);
            
            return $this->conn->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Dosya yükleme hatası: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Dosya silme
     */
    public function deleteFile($file_id, $user_id) {
        try {
            // Dosya bilgilerini al
            $stmt = $this->conn->prepare("
                SELECT * FROM files 
                WHERE id = ? AND (user_id = ? OR ? IN (
                    SELECT user_id FROM user_permissions 
                    WHERE permission_id = (
                        SELECT id FROM permissions WHERE name = 'manage_files'
                    )
                ))
            ");
            $stmt->execute([$file_id, $user_id, $user_id]);
            $file = $stmt->fetch();
            
            if (!$file) {
                throw new Exception('Dosya bulunamadı veya silme yetkiniz yok.');
            }
            
            // Fiziksel dosyayı sil
            if (file_exists($file['file_path'])) {
                if (!unlink($file['file_path'])) {
                    throw new Exception('Dosya silinirken bir hata oluştu.');
                }
            }
            
            // Veritabanından sil
            $stmt = $this->conn->prepare("DELETE FROM files WHERE id = ?");
            $stmt->execute([$file_id]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Dosya silme hatası: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Dosya indirme
     */
    public function downloadFile($file_id, $user_id) {
        try {
            // Dosya bilgilerini al
            $stmt = $this->conn->prepare("
                SELECT * FROM files 
                WHERE id = ? AND (user_id = ? OR is_public = 1 OR ? IN (
                    SELECT user_id FROM user_permissions 
                    WHERE permission_id = (
                        SELECT id FROM permissions WHERE name = 'manage_files'
                    )
                ))
            ");
            $stmt->execute([$file_id, $user_id, $user_id]);
            $file = $stmt->fetch();
            
            if (!$file) {
                throw new Exception('Dosya bulunamadı veya indirme yetkiniz yok.');
            }
            
            // Dosya var mı kontrol et
            if (!file_exists($file['file_path'])) {
                throw new Exception('Dosya bulunamadı.');
            }
            
            // İndirme sayısını artır
            $stmt = $this->conn->prepare("
                UPDATE files 
                SET download_count = download_count + 1 
                WHERE id = ?
            ");
            $stmt->execute([$file_id]);
            
            // Dosya tipine göre header'ları ayarla
            $mime_types = [
                'pdf'  => 'application/pdf',
                'doc'  => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls'  => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'png'  => 'image/png',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif'  => 'image/gif',
                'zip'  => 'application/zip',
                'rar'  => 'application/x-rar-compressed'
            ];
            
            $file_type = strtolower($file['file_type']);
            $mime_type = isset($mime_types[$file_type]) ? $mime_types[$file_type] : 'application/octet-stream';
            
            // Header'ları ayarla
            header('Content-Type: ' . $mime_type);
            header('Content-Disposition: attachment; filename="' . $file['original_filename'] . '"');
            header('Content-Length: ' . filesize($file['file_path']));
            header('Cache-Control: no-cache');
            header('Pragma: no-cache');
            
            // Dosyayı gönder
            readfile($file['file_path']);
            exit;
            
        } catch (Exception $e) {
            error_log("Dosya indirme hatası: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Dosya listesi
     */
    public function getFiles($user_id, $limit = null, $offset = null, $public_only = false) {
        try {
            $sql = "
                SELECT f.*, u.username 
                FROM files f 
                JOIN users u ON f.user_id = u.id 
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($public_only) {
                $sql .= " AND f.is_public = 1";
            } else {
                $sql .= " AND (f.user_id = ? OR f.is_public = 1)";
                $params[] = $user_id;
            }
            
            $sql .= " ORDER BY f.created_at DESC";
            
            if ($limit !== null) {
                $sql .= " LIMIT ?";
                $params[] = (int)$limit;
                
                if ($offset !== null) {
                    $sql .= " OFFSET ?";
                    $params[] = (int)$offset;
                }
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Dosya listesi hatası: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Toplam dosya sayısı
     */
    public function getTotalFiles($user_id, $public_only = false) {
        try {
            $sql = "
                SELECT COUNT(*) 
                FROM files 
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($public_only) {
                $sql .= " AND is_public = 1";
            } else {
                $sql .= " AND (user_id = ? OR is_public = 1)";
                $params[] = $user_id;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn();
            
        } catch (Exception $e) {
            error_log("Toplam dosya sayısı hatası: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Dosya boyutunu formatla
     */
    private function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Dosya önizleme
     */
    public function previewFile($file_id, $user_id) {
        try {
            // Dosya bilgilerini al
            $stmt = $this->conn->prepare("
                SELECT * FROM files 
                WHERE id = ? AND (user_id = ? OR is_public = 1 OR ? IN (
                    SELECT user_id FROM user_permissions 
                    WHERE permission_id = (
                        SELECT id FROM permissions WHERE name = 'manage_files'
                    )
                ))
            ");
            $stmt->execute([$file_id, $user_id, $user_id]);
            $file = $stmt->fetch();
            
            if (!$file) {
                throw new Exception('Dosya bulunamadı veya görüntüleme yetkiniz yok.');
            }
            
            // Dosya var mı kontrol et
            if (!file_exists($file['file_path'])) {
                throw new Exception('Dosya bulunamadı.');
            }
            
            // Dosya tipine göre önizleme
            $preview_types = [
                'image' => ['jpg', 'jpeg', 'png', 'gif'],
                'pdf' => ['pdf'],
                'text' => ['txt', 'csv', 'md', 'html', 'css', 'js', 'php', 'json', 'xml'],
                'code' => ['java', 'py', 'cpp', 'c', 'cs', 'rb', 'swift', 'go', 'rs']
            ];
            
            $file_type = strtolower($file['file_type']);
            $preview_type = null;
            
            foreach ($preview_types as $type => $extensions) {
                if (in_array($file_type, $extensions)) {
                    $preview_type = $type;
                    break;
                }
            }
            
            return [
                'id' => $file['id'],
                'name' => $file['original_filename'],
                'type' => $file_type,
                'preview_type' => $preview_type,
                'path' => $file['file_path'],
                'size' => $file['file_size']
            ];
            
        } catch (Exception $e) {
            error_log("Dosya önizleme hatası: " . $e->getMessage());
            throw $e;
        }
    }
} 