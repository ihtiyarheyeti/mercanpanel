<?php
require_once 'includes/auth.php';

// Çıkış yap
logout();

// Giriş sayfasına yönlendir
header('Location: login.php');
exit();
?> 
 