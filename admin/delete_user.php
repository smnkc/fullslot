<?php
session_start();
require_once '../config/db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim!']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_POST['id'];

    // Admin kendini silemesin
    if ($user_id === $_SESSION['user_id']) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Kendi hesabınızı silemezsiniz!']);
        exit();
    }

    try {
        $db->beginTransaction();

        // Ödeme taleplerini sil
        $stmt = $db->prepare("DELETE FROM payment_requests WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // İşlem geçmişini sil
        $stmt = $db->prepare("DELETE FROM transactions WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Oyun geçmişini sil
        $stmt = $db->prepare("DELETE FROM game_history WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Kullanıcıyı sil
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0");
        $stmt->execute([$user_id]);

        $db->commit();

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Kullanıcı başarıyla silindi!']);
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Kullanıcı silme hatası: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Kullanıcı silinirken bir hata oluştu!']);
    }
    exit();
}

header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek!']);
exit(); 