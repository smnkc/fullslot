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
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'week';

// Filtreye göre tarihleri ayarla
switch($filter) {
    case 'today':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $end_date = date('Y-m-d');
        break;
    case 'all':
        $start_date = '2000-01-01';
        $end_date = date('Y-m-d');
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
    SUM(bet_amount) as total_volume,
    SUM(win_amount) as total_wins
    FROM game_history 
    WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$period_stats = $stmt->fetch();

$stats['total_volume'] = $period_stats['total_volume'] ?? 0;
$stats['total_wins'] = $period_stats['total_wins'] ?? 0;
$stats['net_profit'] = $stats['total_volume'] - $stats['total_wins'];

// Günlük istatistikler
$stmt = $db->prepare("
    SELECT 
        DATE(created_at) as date,
        SUM(bet_amount) as bets,
        SUM(win_amount) as wins
    FROM game_history 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute([$start_date, $end_date]);
$daily_stats = $stmt->fetchAll();

// Son kullanıcıları al
$stmt = $db->query("SELECT id, username, email, credits, last_login FROM users WHERE is_admin = 0 ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Panel</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Kullanıcılar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php">İşlemler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">Ayarlar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="archive_manager.php">Arşiv Yönetimi</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Çıkış Yap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Filtreleme -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-auto">
                        <label class="col-form-label">Hızlı Filtreler:</label>
                    </div>
                    <div class="col-auto">
                        <select name="filter" class="form-select" onchange="this.form.submit()">
                            <option value="today" <?php echo $filter == 'today' ? 'selected' : ''; ?>>Bugün</option>
                            <option value="week" <?php echo $filter == 'week' ? 'selected' : ''; ?>>Son 7 Gün</option>
                            <option value="month" <?php echo $filter == 'month' ? 'selected' : ''; ?>>Son 30 Gün</option>
                            <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>Tüm Zamanlar</option>
                            <option value="custom" <?php echo $filter == 'custom' ? 'selected' : ''; ?>>Özel Tarih</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="col-form-label">Tarih Aralığı:</label>
                    </div>
                    <div class="col-auto">
                        <input type="text" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>" placeholder="Başlangıç">
                    </div>
                    <div class="col-auto">
                        <input type="text" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>" placeholder="Bitiş">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Filtrele</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kar/Zarar İstatistikleri -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Toplam Oyun Hacmi</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['total_volume'], 2); ?> ₺</h3>
                        <small>Seçili dönemdeki toplam bahis</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title">Toplam Ödenen</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['total_wins'], 2); ?> ₺</h3>
                        <small>Seçili dönemdeki ödemeler</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card <?php echo $stats['net_profit'] >= 0 ? 'bg-success' : 'bg-danger'; ?> text-white">
                    <div class="card-body">
                        <h6 class="card-title">Net Kar/Zarar</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['net_profit'], 2); ?> ₺</h3>
                        <small>Seçili dönemdeki net kazanç</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Kazanç Oranı</h6>
                        <h3 class="mb-0">%<?php echo $stats['total_volume'] > 0 ? number_format(($stats['net_profit'] / $stats['total_volume']) * 100, 2) : '0.00'; ?></h3>
                        <small>Seçili dönemdeki oran</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafik -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Dönem Kar/Zarar Grafiği</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="profitChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Son Kullanıcılar -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Son Kayıt Olan Kullanıcılar</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Kullanıcı Adı</th>
                                        <th>E-posta</th>
                                        <th>Kredi</th>
                                        <th>Son Giriş</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo number_format($user['credits'], 2); ?></td>
                                        <td><?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : '-'; ?></td>
                                        <td>
                                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="add_credits.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-success">
                                                <i class="bi bi-plus-circle"></i>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tarih seçici ayarları
        flatpickr("#start_date", {
            locale: "tr",
            dateFormat: "Y-m-d",
            maxDate: "today"
        });

        flatpickr("#end_date", {
            locale: "tr",
            dateFormat: "Y-m-d",
            maxDate: "today"
        });

        // Grafik verilerini hazırla
        const dates = <?php echo json_encode(array_column($daily_stats, 'date')); ?>;
        const bets = <?php echo json_encode(array_column($daily_stats, 'bets')); ?>;
        const wins = <?php echo json_encode(array_column($daily_stats, 'wins')); ?>;
        const profits = bets.map((bet, index) => bet - wins[index]);

        // Grafik oluştur
        const ctx = document.getElementById('profitChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Günlük Kar/Zarar',
                    data: profits,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html> 
</html> 