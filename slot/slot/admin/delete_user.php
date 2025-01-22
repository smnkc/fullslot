<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim!']);
    exit();
}

// POST verisi kontrolü
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz kullanıcı ID!']);
    exit();
}

$userId = intval($_POST['id']);

// Admin kendini silmeye çalışıyorsa engelle
if ($userId === $_SESSION['user_id']) {
    echo json_encode(['status' => 'error', 'message' => 'Kendi hesabınızı silemezsiniz!']);
    exit();
}

try {
    $db->beginTransaction();

    // Önce kullanıcının oyun geçmişini sil
    $stmt = $db->prepare("DELETE FROM game_history WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Sonra kullanıcının işlemlerini sil
    $stmt = $db->prepare("DELETE FROM transactions WHERE user_id = ?");
    $stmt->execute([$userId]);

    // En son kullanıcıyı sil
    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0");
    $stmt->execute([$userId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("Kullanıcı bulunamadı veya admin hesabı!");
    }

    $db->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    $db->rollBack();
    error_log("Kullanıcı silme hatası: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Kullanıcı silinemedi: ' . $e->getMessage()
    ]);
} 