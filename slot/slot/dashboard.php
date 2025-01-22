<?php
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
session_start();
require_once 'config/db.php';

// Kullanıcı kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Site adını al
$stmt = $db->query("SELECT setting_value FROM text_settings WHERE setting_key = 'site_name'");
$site_name = $stmt->fetch()['setting_value'] ?? 'Slot Oyunu';

// Bakım modu kontrolü
try {
    $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
    $maintenance_mode = (int)($stmt->fetch()['setting_value'] ?? 0);

    if ($maintenance_mode && (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin'])) {
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Bakım modu kontrolü hatası: " . $e->getMessage());
}

// Admin kontrolü
if ($_SESSION['is_admin']) {
    header("Location: admin/dashboard.php");
    exit();
}

// Kullanıcı bilgilerini al
$stmt = $db->prepare("SELECT credits FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Son işlemleri al
$stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$recent_transactions = $stmt->fetchAll();

// Son oyun geçmişini al
$stmt = $db->prepare("SELECT * FROM game_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$recent_games = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_name); ?> - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><?php echo htmlspecialchars($site_name); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="game.php">Slot Oyunu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="blackjack.php">Blackjack</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="bi bi-coin"></i> Kredi: <?php echo number_format($user['credits'], 2); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Çıkış Yap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Son İşlemler</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_transactions)): ?>
                            <p class="text-muted">Henüz işlem yapılmamış.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Tür</th>
                                            <th>Miktar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_transactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo date('d.m.Y H:i', strtotime($transaction['created_at'])); ?></td>
                                                <td>
                                                    <?php
                                                    switch($transaction['type']) {
                                                        case 'deposit':
                                                            echo '<span class="badge bg-success">Yükleme</span>';
                                                            break;
                                                        case 'withdraw':
                                                            echo '<span class="badge bg-danger">Çekim</span>';
                                                            break;
                                                        case 'game_win':
                                                            echo '<span class="badge bg-primary">Kazanç</span>';
                                                            break;
                                                        case 'game_loss':
                                                            echo '<span class="badge bg-warning">Kayıp</span>';
                                                            break;
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo number_format($transaction['amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Son Oyunlar</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_games)): ?>
                            <p class="text-muted">Henüz oyun oynanmamış.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Bahis</th>
                                            <th>Kazanç</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_games as $game): ?>
                                            <tr>
                                                <td><?php echo date('d.m.Y H:i', strtotime($game['created_at'])); ?></td>
                                                <td><?php echo number_format($game['bet_amount'], 2); ?></td>
                                                <td>
                                                    <?php if ($game['win_amount'] > 0): ?>
                                                        <span class="text-success">+<?php echo number_format($game['win_amount'], 2); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-danger"><?php echo number_format($game['win_amount'], 2); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Hemen Oynamaya Başla!</h5>
                        <p class="card-text">Şansını dene, büyük ödülü kazan!</p>
                        <a href="game.php" class="btn btn-primary btn-lg">Oyuna Başla</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 