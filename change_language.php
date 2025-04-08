<?php
session_start();
require_once 'includes/Language.php';

// Dil değiştirme isteği kontrolü
if (isset($_GET['lang'])) {
    $newLang = $_GET['lang'];
    $language = Language::getInstance();
    $language->changeLanguage($newLang);
}

// Referrer yoksa, varsayılan olarak index.php'ye yönlendir
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';

// Yönlendir
header('Location: ' . $referrer);
exit; 