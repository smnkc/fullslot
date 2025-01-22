<?php
session_start();
require_once '../config/db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim!']);
    exit();
}

// POST verilerini kontrol et
if (!isset($_POST['id']) || !isset($_POST['amount'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek!']);
    exit();
}

$user_id = (int)$_POST['id'];
$amount = (float)$_POST['amount'];

if ($amount <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz miktar!']);
    exit();
}

try {
    // Kullanıcının mevcut kredisini kontrol et
    $stmt = $db->prepare("SELECT credits FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Kullanıcı bulunamadı!');
    }

    if ($user['credits'] < $amount) {
        throw new Exception('Kullanıcının yeterli kredisi yok!');
    }

    // Veritabanı işlemini başlat
    $db->beginTransaction();

    // Kullanıcının kredisini güncelle
    $stmt = $db->prepare("UPDATE users SET credits = credits - ? WHERE id = ?");
    $stmt->execute([$amount, $user_id]);

    // İşlem kaydını ekle
    $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'admin_remove', 'Admin tarafından kredi silindi')");
    $stmt->execute([$user_id, $amount]);

    // İşlemi tamamla
    $db->commit();

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Kredi başarıyla silindi.']);
} catch (Exception $e) {
    // Hata durumunda işlemi geri al
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Kredi silme hatası: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} 