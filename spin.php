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
        $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, type, description, created_at) VALUES (?, ?, 'oyun_kayip', 'Slot oyunu bahis', NOW())");
        $stmt->execute([$_SESSION['user_id'], -$bet]);
        
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception('Bahis işlemi sırasında bir hata oluştu');
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

    // Maksimum kazanç kontrolü
    if ($win_amount > $max_win) {
        $win_amount = $max_win;
    }

    // Kazanç varsa ekle
    if ($win_amount > 0) {
        $db->beginTransaction();
        try {
            // Kazancı ekle
            $final_credits = $new_credits + $win_amount;
            $stmt = $db->prepare("UPDATE users SET credits = ? WHERE id = ?");
            $stmt->execute([$final_credits, $_SESSION['user_id']]);

            // Kazanç işlemini kaydet
            $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, type, description, created_at) VALUES (?, ?, 'oyun_kazanc', 'Slot oyunu kazancı', NOW())");
            $stmt->execute([$_SESSION['user_id'], $win_amount]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception('Kazanç işlemi sırasında bir hata oluştu');
        }
    } else {
        $final_credits = $new_credits;
    }

    // Oyun geçmişini kaydet
    $stmt = $db->prepare("INSERT INTO game_history (user_id, game_type, bet_amount, win_amount, result, created_at) VALUES (?, 'slot', ?, ?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $bet, $win_amount, implode(',', $result)]);

    // Sonucu döndür
    $response = [
        'status' => 'success',
        'symbols' => $result,
        'win' => $win_amount,
        'message' => $win_amount > 0 ? sprintf('Tebrikler! %.2f kredi kazandınız!', $win_amount) : ''
    ];

    // Sadece kazanç varsa bakiye bilgisini ekle
    if ($win_amount > 0) {
        $response['balance'] = '<i class="bi bi-coin"></i> Kredi: ' . number_format($final_credits, 2, ',', '.');
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}