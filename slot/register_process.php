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
    
    // Başlangıç kredisini al
    $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'initial_credits'");
    $initial_credits = floatval($stmt->fetch()['setting_value'] ?? 1000);

    // Yeni kullanıcı oluştur
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, email, password, credits) VALUES (?, ?, ?, ?)");
    
    try {
        $stmt->execute([$username, $email, $hashed_password, $initial_credits]);
        $_SESSION['success'] = "Kayıt başarılı! Lütfen giriş yapın.";
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Kayıt sırasında bir hata oluştu!";
        header("Location: register.php");
        exit();
    }
}

header("Location: register.php");
exit(); 