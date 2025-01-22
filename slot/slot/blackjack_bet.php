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

if (!isset($_POST['bet']) || !is_numeric($_POST['bet']) || $_POST['bet'] <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz bahis miktarı']);
    exit;
}

$bet = floatval($_POST['bet']);

try {
    $db->beginTransaction();

    // Kullanıcı bakiyesini kontrol et
    $stmt = $db->prepare("SELECT credits FROM users WHERE id = ? FOR UPDATE");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Kullanıcı bulunamadı');
    }

    if ($user['credits'] < $bet) {
        throw new Exception('Yetersiz bakiye');
    }

    // Bakiyeyi güncelle
    $stmt = $db->prepare("UPDATE users SET credits = credits - ? WHERE id = ?");
    if (!$stmt->execute([$bet, $_SESSION['user_id']])) {
        throw new Exception('Bakiye güncellenemedi');
    }

    // İşlem kaydı
    $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, type) VALUES (?, ?, 'bet')");
    if (!$stmt->execute([$_SESSION['user_id'], -$bet])) {
        throw new Exception('İşlem kaydedilemedi');
    }

    $db->commit();

    // Güncel bakiyeyi al
    $stmt = $db->prepare("SELECT credits FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $newBalance = $stmt->fetchColumn();

    error_log("Bahis sonrası bakiye: " . $newBalance);

    echo json_encode([
        'status' => 'success',
        'message' => 'Bahis alındı',
        'balance' => $newBalance
    ]);

} catch (Exception $e) {
    $db->rollBack();
    error_log("Bahis hatası: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}