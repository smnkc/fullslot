<?php
// Veritabanı bağlantı bilgileri
$db_host = 'localhost';     // Veritabanı sunucusu
$db_name = 'osmanak1_slot';          // Veritabanı adı
$db_user = 'root';          // XAMPP varsayılan kullanıcı adı
$db_pass = '';              // XAMPP varsayılan şifre (boş)

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // SQL enjeksiyonuna karşı koruma
    $db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_turkish_ci");
    $db->exec("SET CHARACTER SET utf8mb4");
    error_log("Veritabanı bağlantısı başarılı");
} catch(PDOException $e) {
    error_log("Veritabanı bağlantı hatası [" . date('Y-m-d H:i:s') . "]: " . $e->getMessage());
    die("Veritabanına bağlanılamadı!");
}
?> 
