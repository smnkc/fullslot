<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Oturum açmanız gerekiyor']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek metodu']);
    exit;
}

if (!isset($_POST['win']) || !is_numeric($_POST['win']) || $_POST['win'] <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz kazanç miktarı']);
    exit;
}

$win = floatval($_POST['win']);

try {
    $db->beginTransaction();

    // Kullanıcı bakiyesini güncelle
    $stmt = $db->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
    if (!$stmt->execute([$win, $_SESSION['user_id']])) {
        throw new Exception('Bakiye güncellenemedi');
    }

    // İşlem kaydı
    $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, type) VALUES (?, ?, 'win')");
    if (!$stmt->execute([$_SESSION['user_id'], $win])) {
        throw new Exception('İşlem kaydedilemedi');
    }

    $db->commit();

    // Güncel bakiyeyi al
    $stmt = $db->prepare("SELECT credits FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $newBalance = $stmt->fetchColumn();

    error_log("Kazanç sonrası bakiye: " . $newBalance);

    echo json_encode([
        'status' => 'success',
        'message' => 'Kazanç eklendi',
        'balance' => $newBalance
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log("Kazanç hatası: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}