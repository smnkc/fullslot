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

    // Ayarları al
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('min_bet', 'max_bet', 'max_win', 'max_daily_bet')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Minimum ve maksimum bahis kontrolü
    $min_bet = floatval($settings['min_bet'] ?? 10);
    $max_bet = floatval($settings['max_bet'] ?? 1000);
    $max_win = floatval($settings['max_win'] ?? 10000);
    $max_daily_bet = floatval($settings['max_daily_bet'] ?? 10000);

    if ($bet < $min_bet) {
        throw new Exception("Minimum bahis miktarı: {$min_bet}");
    }
    if ($bet > $max_bet) {
        throw new Exception("Maksimum bahis miktarı: {$max_bet}");
    }

    // Günlük toplam bahis kontrolü
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT COALESCE(SUM(bet_amount), 0) as total_bets FROM game_history WHERE user_id = ? AND DATE(created_at) = ?");
    $stmt->execute([$_SESSION['user_id'], $today]);
    $daily_bets = floatval($stmt->fetch()['total_bets']);

    if (($daily_bets + $bet) > $max_daily_bet) {
        throw new Exception("Günlük maksimum bahis limitine ulaştınız: {$max_daily_bet}");
    }

    // Kullanıcı bilgilerini al
    $stmt = $db->prepare("SELECT credits FROM users WHERE id = ? FOR UPDATE");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Kullanıcı bulunamadı');
    }

    // Bakiye kontrolü
    if ($user['credits'] < $bet) {
        throw new Exception('Yetersiz bakiye');
    }

    // Önce bahis miktarını düş
    $db->beginTransaction();
    
    try {
        // Krediyi düş
        $new_credits = $user['credits'] - $bet;
        $stmt = $db->prepare("UPDATE users SET credits = ? WHERE id = ?");
        $stmt->execute([$new_credits, $_SESSION['user_id']]);
        
        // Bahis kaybı işlemini kaydet
        $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, type, description, created_at) VALUES (?, ?, 'oyun_kayip', 'Blackjack oyunu bahis', NOW())");
        $stmt->execute([$_SESSION['user_id'], -$bet]);
        
        $db->commit();

        // Oyun oturumu bilgilerini sakla
        $_SESSION['blackjack_bet'] = $bet;
        $_SESSION['blackjack_initial_balance'] = $new_credits;

        echo json_encode([
            'status' => 'success',
            'balance' => $new_credits,
            'message' => 'Bahis yapıldı'
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception('Bahis işlemi sırasında bir hata oluştu');
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}