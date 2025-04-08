<?php
require_once 'config/database.php';

try {
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Kullanıcı Tablosu Yapısı:\n\n";
    foreach ($columns as $column) {
        echo "Sütun: {$column['Field']}\n";
        echo "Tip: {$column['Type']}\n";
        echo "Boş Olabilir: {$column['Null']}\n";
        echo "Varsayılan: {$column['Default']}\n";
        echo "------------------------\n";
    }
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?> 