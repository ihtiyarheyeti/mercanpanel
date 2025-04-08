<?php

// Hata raporlama
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Uygulama sabitleri
define('ROOT_PATH', dirname(__FILE__));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/views');
define('CONTROLLER_PATH', APP_PATH . '/controllers');
define('MODEL_PATH', APP_PATH . '/models');
define('CORE_PATH', APP_PATH . '/core');
define('CONFIG_PATH', ROOT_PATH . '/config');

// Veritabanı ayarlarını yükle
require_once CONFIG_PATH . '/database.php';

// Autoloader
spl_autoload_register(function ($class) {
    $file = ROOT_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Router'ı başlat
$router = new app\core\Router();

// Rotaları tanımla
$router->add('', ['controller' => 'HomeController', 'action' => 'index']);
$router->add('login', ['controller' => 'AuthController', 'action' => 'login']);
$router->add('logout', ['controller' => 'AuthController', 'action' => 'logout']);
$router->add('dashboard', ['controller' => 'DashboardController', 'action' => 'index']);
$router->add('users', ['controller' => 'UserController', 'action' => 'index']);
$router->add('users/create', ['controller' => 'UserController', 'action' => 'create']);
$router->add('users/edit/{id}', ['controller' => 'UserController', 'action' => 'edit']);
$router->add('users/delete/{id}', ['controller' => 'UserController', 'action' => 'delete']);
$router->add('settings', ['controller' => 'SettingsController', 'action' => 'index']);
$router->add('settings/update', ['controller' => 'SettingsController', 'action' => 'update', 'method' => 'POST']);
$router->add('themes', ['controller' => 'ThemeController', 'action' => 'index']);

// URL'yi al ve route'u çalıştır
$url = $_SERVER['REQUEST_URI'];
$url = str_replace('/mercanpanel', '', $url);
$url = trim($url, '/');

// Debug için URL'yi yazdır
error_log("Requested URL: " . $url);
error_log("Available routes: " . print_r($router->getRoutes(), true));

try {
    $router->dispatch($url);
} catch (Exception $e) {
    error_log("Router Error: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
    header("HTTP/1.0 500 Internal Server Error");
    echo "<div class='alert alert-danger'>";
    echo "<h4>Sistem Hatası</h4>";
    echo "<p>Üzgünüz, bir sistem hatası oluştu. Lütfen daha sonra tekrar deneyin.</p>";
    if (ini_get('display_errors')) {
        echo "<p><strong>Hata:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Dosya:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
    }
    echo "</div>";
} 