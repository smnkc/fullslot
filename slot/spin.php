<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

try {
    // Kullanıcı kontrolü
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Oturum süresi dolmuş');
    }

    // Bahis miktarı kontrolü
    if (!isset($_POST['bet']) || !is_numeric($_POST['bet'])) {
        throw new Exception('Geçersiz bahis miktarı');
    }

    $bet = floatval($_POST['bet']);
    if ($bet <= 0) {
        throw new Exception('Bahis miktarı 0\'dan büyük olmalı');
    }

    // Kullanıcı bilgilerini al
    $stmt = $db->prepare("SELECT credits FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Kullanıcı bulunamadı');
    }

    // Bakiye kontrolü
    if ($user['credits'] < $bet) {
        throw new Exception('Yetersiz bakiye');
    }

    // Sembolleri tanımla
    $symbols = ['🍒', '🍊', '🍇', '🍎', '💎', '7️⃣'];
    $result = [];

    // Rastgele semboller seç
    for ($i = 0; $i < 3; $i++) {
        $result[] = $symbols[array_rand($symbols)];
    }

    // Kazanç hesapla
    $win_amount = 0;
    $multiplier = 0;

    // Üç aynı sembol
    if ($result[0] === $result[1] && $result[1] === $result[2]) {
        switch ($result[0]) {
            case '7️⃣':
                $multiplier = 40;
                break;
            case '💎':
                $multiplier = 35;
                break;
            case '🍒':
                $multiplier = 30;
                break;
            case '🍇':
                $multiplier = 25;
                break;
            case '🍊':
                $multiplier = 20;
                break;
            case '🍎':
                $multiplier = 15;
                break;
        }
    }
    // İki aynı sembol
    elseif ($result[0] === $result[1] || $result[1] === $result[2] || $result[0] === $result[2]) {
        $multiplier = 2;
    }

    $win_amount = $bet * $multiplier;

    // Veritabanı işlemleri
    $db->beginTransaction();

    try {
        // Krediyi güncelle
        $final_credits = $user['credits'] - $bet + $win_amount;
        $stmt = $db->prepare("UPDATE users SET credits = ? WHERE id = ?");
        $stmt->execute([$final_credits, $_SESSION['user_id']]);

        // Oyun geçmişini kaydet
        $stmt = $db->prepare("INSERT INTO game_history (user_id, game_type, bet_amount, win_amount, result, created_at) VALUES (?, 'slot', ?, ?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], $bet, $win_amount, implode(',', $result)]);

        // İşlem geçmişini kaydet
        if ($win_amount > 0) {
            $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, type, description, created_at) VALUES (?, ?, 'win', ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $win_amount, 'Slot oyunu kazancı']);
        }

        $db->commit();

        // Sonucu döndür
        echo json_encode([
            'status' => 'success',
            'symbols' => $result,
            'win' => $win_amount,
            'balance' => $final_credits,
            'message' => $win_amount > 0 ? sprintf('Tebrikler! %.2f kredi kazandınız!', $win_amount) : ''
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Hata: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
}