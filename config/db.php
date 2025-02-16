<?php
// Veritabanı bağlantı bilgileri
$db_host = 'localhost';     // Sunucu adı
$db_name = 'osmanak1_slot'; // Veritabanı adı (cPanel'den oluşturduğunuz)
$db_user = 'osmanak1_slot'; // Veritabanı kullanıcı adı (cPanel'den oluşturduğunuz)
$db_pass = 'BURAYA_CPANELDEN_ALDIGINIZ_SIFREYI_YAZIN'; // Veritabanı şifresi

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Türkçe karakter ayarları
    $db->exec("SET NAMES 'utf8mb4'");
    $db->exec("SET CHARACTER SET utf8mb4");
    $db->exec("SET COLLATION_CONNECTION = 'utf8mb4_turkish_ci'");
    $db->exec("SET time_zone = '+03:00'");
    
    error_log("Veritabanı bağlantısı başarılı");
} catch(PDOException $e) {
    error_log("Veritabanı bağlantı hatası [" . date('Y-m-d H:i:s') . "]: " . $e->getMessage());
    die("Veritabanına bağlanılamadı!");
}
?> 
