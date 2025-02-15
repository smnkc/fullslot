<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

try {
    // Ayarları veritabanından al
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('min_bet', 'max_bet', 'max_daily_bet')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Günlük bahis kontrolü için kullanıcının bugünkü toplam bahislerini al
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT COALESCE(SUM(bet_amount), 0) as total_bets FROM game_history WHERE user_id = ? AND DATE(created_at) = ?");
    $stmt->execute([$_SESSION['user_id'], $today]);
    $daily_bets = $stmt->fetch(PDO::FETCH_ASSOC)['total_bets'];

    // Kalan günlük bahis limitini hesapla
    $max_daily_bet = floatval($settings['max_daily_bet'] ?? 10000);
    $remaining_daily_bet = $max_daily_bet - $daily_bets;

    echo json_encode([
        'min_bet' => floatval($settings['min_bet'] ?? 10),
        'max_bet' => floatval($settings['max_bet'] ?? 1000),
        'max_daily_bet' => $max_daily_bet,
        'remaining_daily_bet' => max(0, $remaining_daily_bet),
        'daily_bets' => $daily_bets
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Bahis limitleri alınırken bir hata oluştu: ' . $e->getMessage()
    ]);
} 