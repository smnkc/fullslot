<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // Şifre kontrolü
    if ($password !== $password_confirm) {
        $_SESSION['error'] = "Şifreler eşleşmiyor!";
        header("Location: register.php");
        exit();
    }
    
    // Kullanıcı adı ve email kontrolü
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Bu kullanıcı adı veya email zaten kullanımda!";
        header("Location: register.php");
        exit();
    }
    
    // Ayarları al
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('initial_credits', 'bonus_enabled', 'registration_bonus')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $initial_credits = floatval($settings['initial_credits'] ?? 1000);
    $bonus_enabled = (int)($settings['bonus_enabled'] ?? 0);
    $registration_bonus = floatval($settings['registration_bonus'] ?? 0);

    // Toplam başlangıç kredisini hesapla
    $total_credits = $initial_credits;
    if ($bonus_enabled && $registration_bonus > 0) {
        $total_credits += $registration_bonus;
    }

    // Yeni kullanıcı oluştur
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $db->beginTransaction();

        // Kullanıcıyı ekle
        $stmt = $db->prepare("INSERT INTO users (username, email, password, credits) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashed_password, $total_credits]);
        $user_id = $db->lastInsertId();

        // Bonus işlemini kaydet
        if ($bonus_enabled && $registration_bonus > 0) {
            $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'bonus', 'Kayıt bonusu')");
            $stmt->execute([$user_id, $registration_bonus]);
        }

        // Başlangıç kredisi işlemini kaydet
        $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'baslangic', 'Başlangıç kredisi')");
        $stmt->execute([$user_id, $initial_credits]);

        $db->commit();

        $_SESSION['register_success'] = "Kayıt işleminiz başarıyla tamamlandı! " . 
            ($bonus_enabled && $registration_bonus > 0 ? 
            sprintf("Hoşgeldin bonusu olarak %.2f kredi hesabınıza tanımlandı.", $registration_bonus) : 
            "");
        
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Kayıt işlemi hatası - Kullanıcı: " . $username . " - Hata: " . $e->getMessage());
        $_SESSION['error'] = "Kayıt sırasında bir hata oluştu!";
        header("Location: register.php");
        exit();
    }
}

header("Location: register.php");
exit(); 