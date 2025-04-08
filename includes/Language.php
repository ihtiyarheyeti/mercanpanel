<?php
/**
 * Dil Yönetimi Sınıfı
 * Çoklu dil desteği sağlar
 */
class Language {
    private static $instance = null;
    private $lang;
    private $translations = [];
    private $availableLanguages = [
        'tr' => 'Türkçe',
        'en' => 'English'
    ];
    
    /**
     * Singleton metodu
     * @param PDO $conn Veritabanı bağlantısı
     * @return Language
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Yapıcı metod
     */
    private function __construct() {
        // Session içinde dil ayarı varsa kullan
        if (isset($_SESSION['lang']) && array_key_exists($_SESSION['lang'], $this->availableLanguages)) {
            $this->lang = $_SESSION['lang'];
        } else {
            // Tarayıcı dilini al
            $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'tr', 0, 2);
            // Eğer desteklenen bir dil ise kullan, değilse varsayılan (Türkçe)
            $this->lang = array_key_exists($browserLang, $this->availableLanguages) ? $browserLang : 'tr';
            $_SESSION['lang'] = $this->lang;
        }
        
        // Dil dosyasını yükle
        $this->loadLanguageFile();
    }
    
    /**
     * Dil dosyasını yükler
     */
    private function loadLanguageFile() {
        $filePath = __DIR__ . '/../lang/' . $this->lang . '.php';
        
        if (file_exists($filePath)) {
            $this->translations = include $filePath;
        } else {
            // Varsayılan dil (TR) dosyasını yükle
            $defaultFilePath = __DIR__ . '/../lang/tr.php';
            if (file_exists($defaultFilePath)) {
                $this->translations = include $defaultFilePath;
            }
        }
    }
    
    /**
     * Dil değiştirir
     * @param string $lang Dil kodu
     * @return bool Başarılı mı
     */
    public function changeLanguage($lang) {
        if (array_key_exists($lang, $this->availableLanguages)) {
            $this->lang = $lang;
            $_SESSION['lang'] = $lang;
            $this->loadLanguageFile();
            return true;
        }
        return false;
    }
    
    /**
     * Çeviri döndürür
     * @param string $key Çeviri anahtarı
     * @param array $params Parametre değiştirilecek değerler
     * @return string Çeviri
     */
    public function get($key, $params = []) {
        // Nokta notasyonu ile alt dizilere erişim sağlar (örn: "login.title")
        $keys = explode('.', $key);
        $translation = $this->translations;
        
        foreach ($keys as $k) {
            if (isset($translation[$k])) {
                $translation = $translation[$k];
            } else {
                return $key; // Çeviri bulunamadı
            }
        }
        
        // Eğer çeviri bulunamadıysa anahtarı döndür
        if (!is_string($translation)) {
            return $key;
        }
        
        // Parametreleri değiştir
        if (!empty($params)) {
            foreach ($params as $paramKey => $paramValue) {
                $translation = str_replace(':' . $paramKey, $paramValue, $translation);
            }
        }
        
        return $translation;
    }
    
    /**
     * Mevcut dili döndürür
     * @return string Dil kodu
     */
    public function getCurrentLanguage() {
        return $this->lang;
    }
    
    /**
     * Kullanılabilir dilleri döndürür
     * @return array Diller
     */
    public function getAvailableLanguages() {
        return $this->availableLanguages;
    }
    
    /**
     * Dil adını döndürür
     * @param string $langCode Dil kodu
     * @return string Dil adı
     */
    public function getLanguageName($langCode = null) {
        $lang = $langCode ?: $this->lang;
        return $this->availableLanguages[$lang] ?? $lang;
    }
}

/**
 * Kısaltılmış çeviri fonksiyonu
 * @param string $key Çeviri anahtarı
 * @param array $params Parametre değiştirilecek değerler
 * @return string Çeviri
 */
function __($key, $params = []) {
    $language = Language::getInstance();
    return $language->get($key, $params);
} 