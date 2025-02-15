<?php
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
session_start();
require_once '../config/db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit();
}

// Tarih filtresi
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'today';

// Filtreye göre tarihleri ayarla
switch($filter) {
    case 'today':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        $period_text = "Bugün";
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
        $period_text = "Son 7 Gün";
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $end_date = date('Y-m-d');
        $period_text = "Son 30 Gün";
        break;
    default:
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        $period_text = "Bugün";
        break;
}

// İstatistikleri al
$stats = [];

// Toplam kullanıcı sayısı
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE is_admin = 0");
$stats['total_users'] = $stmt->fetch()['total'];

// Toplam kredi miktarı
$stmt = $db->query("SELECT SUM(credits) as total FROM users WHERE is_admin = 0");
$stats['total_credits'] = $stmt->fetch()['total'] ?? 0;

// Seçili tarih aralığındaki işlem sayısı
$stmt = $db->prepare("SELECT COUNT(*) as total FROM transactions WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$stats['transactions_period'] = $stmt->fetch()['total'];

// Kar/Zarar İstatistikleri - Seçili tarih aralığı için
$stmt = $db->prepare("SELECT 
    COALESCE(SUM(bet_amount), 0) as total_volume,
    COALESCE(SUM(win_amount), 0) as total_wins,
    COUNT(*) as total_games
    FROM game_history 
    WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$period_stats = $stmt->fetch();

$stats['total_volume'] = $period_stats['total_volume'];
$stats['total_wins'] = $period_stats['total_wins'];
$stats['total_games'] = $period_stats['total_games'];
$stats['net_profit'] = $stats['total_volume'] - $stats['total_wins'];

// Bugünün istatistikleri
$stmt = $db->prepare("SELECT 
    COALESCE(SUM(bet_amount), 0) as today_volume,
    COALESCE(SUM(win_amount), 0) as today_wins,
    COUNT(*) as today_games
    FROM game_history 
    WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$today_stats = $stmt->fetch();

$stats['today_volume'] = $today_stats['today_volume'];
$stats['today_wins'] = $today_stats['today_wins'];
$stats['today_games'] = $today_stats['today_games'];
$stats['today_profit'] = $stats['today_volume'] - $stats['today_wins'];

// Son kullanıcıları al (son 24 saat içinde kaydolanlar)
$stmt = $db->query("SELECT id, username, credits, last_login, created_at 
                    FROM users 
                    WHERE is_admin = 0 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ORDER BY created_at DESC 
                    LIMIT 5");
$recent_users = $stmt->fetchAll();

// Bekleyen ödemeleri al
$stmt = $db->query("SELECT p.*, u.username 
                    FROM payment_requests p 
                    JOIN users u ON p.user_id = u.id 
                    WHERE p.status = 'pending' 
                    ORDER BY p.created_at DESC 
                    LIMIT 5");
$pending_payments = $stmt->fetchAll();

// Aktif kullanıcı sayısı (son 24 saat içinde giriş yapanlar)
$stmt = $db->query("SELECT COUNT(*) as total 
                    FROM users 
                    WHERE is_admin = 0 
                    AND last_login >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stats['active_users'] = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require_once 'admin_navbar.php'; ?>

    <div class="container mt-4">
        <!-- Filtreleme -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-auto">
                        <label class="col-form-label">Zaman Aralığı:</label>
                    </div>
                    <div class="col-auto">
                        <select name="filter" class="form-select" onchange="this.form.submit()">
                            <option value="today" <?php echo $filter == 'today' ? 'selected' : ''; ?>>Bugün</option>
                            <option value="week" <?php echo $filter == 'week' ? 'selected' : ''; ?>>Son 7 Gün</option>
                            <option value="month" <?php echo $filter == 'month' ? 'selected' : ''; ?>>Son 30 Gün</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Genel İstatistikler -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Toplam Oyun Hacmi</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['total_volume'], 2); ?> ₺</h3>
                        <small><?php echo $period_text; ?> (<?php echo number_format($stats['total_games']); ?> oyun)</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title">Toplam Ödenen</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['total_wins'], 2); ?> ₺</h3>
                        <small><?php echo $period_text; ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card <?php echo $stats['net_profit'] >= 0 ? 'bg-success' : 'bg-danger'; ?> text-white">
                    <div class="card-body">
                        <h6 class="card-title">Net Kar/Zarar</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['net_profit'], 2); ?> ₺</h3>
                        <small><?php echo $period_text; ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Aktif Kullanıcılar</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['active_users']); ?></h3>
                        <small>Son 24 saat (Toplam: <?php echo number_format($stats['total_users']); ?>)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bugünün İstatistikleri -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Bugünün İstatistikleri</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <h6>Oyun Hacmi</h6>
                                <p class="h4"><?php echo number_format($stats['today_volume'], 2); ?> ₺</p>
                                <small class="text-muted"><?php echo number_format($stats['today_games']); ?> oyun</small>
                            </div>
                            <div class="col-md-3">
                                <h6>Ödenen</h6>
                                <p class="h4"><?php echo number_format($stats['today_wins'], 2); ?> ₺</p>
                            </div>
                            <div class="col-md-3">
                                <h6>Net Kar/Zarar</h6>
                                <p class="h4 <?php echo $stats['today_profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo number_format($stats['today_profit'], 2); ?> ₺
                                </p>
                            </div>
                            <div class="col-md-3">
                                <h6>Yeni Üyeler</h6>
                                <p class="h4"><?php echo count($recent_users); ?></p>
                                <small class="text-muted">Son 24 saat</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Son Kullanıcılar -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Son Kayıt Olan Kullanıcılar</h5>
                        <a href="users.php" class="btn btn-primary btn-sm">Tüm Kullanıcılar</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kullanıcı Adı</th>
                                        <th>Kredi</th>
                                        <th>Son Giriş</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo number_format($user['credits'], 2); ?></td>
                                        <td><?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : '-'; ?></td>
                                        <td>
                                            <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary" title="Düzenle">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="transactions.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="İşlemler">
                                                <i class="bi bi-list"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Bekleyen Ödemeler</h5>
                        <a href="payments.php" class="btn btn-primary btn-sm">Tüm Ödemeler</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_payments)): ?>
                            <p class="text-muted mb-0">Bekleyen ödeme bulunmuyor.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Kullanıcı</th>
                                            <th>Miktar</th>
                                            <th>Yöntem</th>
                                            <th>Tarih</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['username']); ?></td>
                                            <td><?php echo number_format($payment['amount'], 2); ?> TL</td>
                                            <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?></td>
                                            <td>
                                                <a href="payments.php" class="btn btn-sm btn-success" title="Ödemelere Git">
                                                    <i class="bi bi-check-lg"></i>
                                                </a>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 