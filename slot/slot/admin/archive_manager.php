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

$message = '';
$messageType = '';

// Arşivleme işlemi
if (isset($_POST['archive'])) {
    $archive_date = $_POST['archive_date'];
    
    try {
        $db->beginTransaction();
        
        // Oyun geçmişini arşivle
        $stmt = $db->prepare("INSERT INTO game_history_archive 
            SELECT * FROM game_history 
            WHERE DATE(created_at) <= ?");
        $stmt->execute([$archive_date]);
        
        // Arşivlenen kayıtları sil
        $stmt = $db->prepare("DELETE FROM game_history 
            WHERE DATE(created_at) <= ?");
        $stmt->execute([$archive_date]);
        
        // İşlem geçmişini arşivle
        $stmt = $db->prepare("INSERT INTO transactions_archive 
            SELECT * FROM transactions 
            WHERE DATE(created_at) <= ?");
        $stmt->execute([$archive_date]);
        
        // Arşivlenen işlemleri sil
        $stmt = $db->prepare("DELETE FROM transactions 
            WHERE DATE(created_at) <= ?");
        $stmt->execute([$archive_date]);
        
        $db->commit();
        $message = "Veriler başarıyla arşivlendi!";
        $messageType = "success";
        
    } catch (Exception $e) {
        $db->rollBack();
        $message = "Arşivleme sırasında bir hata oluştu: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Arşiv silme işlemi
if (isset($_POST['delete_archive'])) {
    $delete_date = $_POST['delete_date'];
    
    try {
        $db->beginTransaction();
        
        // Arşivlenmiş oyun geçmişini sil
        $stmt = $db->prepare("DELETE FROM game_history_archive 
            WHERE DATE(created_at) <= ?");
        $stmt->execute([$delete_date]);
        
        // Arşivlenmiş işlemleri sil
        $stmt = $db->prepare("DELETE FROM transactions_archive 
            WHERE DATE(created_at) <= ?");
        $stmt->execute([$delete_date]);
        
        $db->commit();
        $message = "Arşiv başarıyla silindi!";
        $messageType = "success";
        
    } catch (Exception $e) {
        $db->rollBack();
        $message = "Arşiv silme sırasında bir hata oluştu: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Arşiv istatistiklerini al
$stmt = $db->query("SELECT 
    (SELECT COUNT(*) FROM game_history_archive) as game_count,
    (SELECT COUNT(*) FROM transactions_archive) as transaction_count,
    (SELECT MIN(created_at) FROM game_history_archive) as oldest_game,
    (SELECT MAX(created_at) FROM game_history_archive) as newest_game");
$archive_stats = $stmt->fetch();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arşiv Yönetimi - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Panel</a>
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
                        <a class="nav-link active" href="archive_manager.php">Arşiv Yönetimi</a>
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
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Arşiv İstatistikleri -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Arşiv İstatistikleri</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <h6>Arşivlenen Oyun Sayısı</h6>
                                <p class="h4"><?php echo number_format($archive_stats['game_count']); ?></p>
                            </div>
                            <div class="col-md-3">
                                <h6>Arşivlenen İşlem Sayısı</h6>
                                <p class="h4"><?php echo number_format($archive_stats['transaction_count']); ?></p>
                            </div>
                            <div class="col-md-3">
                                <h6>En Eski Kayıt</h6>
                                <p class="h4"><?php echo $archive_stats['oldest_game'] ? date('d.m.Y', strtotime($archive_stats['oldest_game'])) : '-'; ?></p>
                            </div>
                            <div class="col-md-3">
                                <h6>En Yeni Kayıt</h6>
                                <p class="h4"><?php echo $archive_stats['newest_game'] ? date('d.m.Y', strtotime($archive_stats['newest_game'])) : '-'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Arşivleme -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Verileri Arşivle</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" onsubmit="return confirm('Seçili tarihe kadar olan tüm veriler arşive taşınacak. Onaylıyor musunuz?');">
                            <div class="mb-3">
                                <label for="archive_date" class="form-label">Arşivlenecek Tarih</label>
                                <input type="text" class="form-control" id="archive_date" name="archive_date" required>
                                <div class="form-text">Bu tarihten önceki tüm veriler arşive taşınacak.</div>
                            </div>
                            <button type="submit" name="archive" class="btn btn-primary">Arşivle</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Arşiv Silme -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Arşiv Sil</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" onsubmit="return confirm('DİKKAT! Seçili tarihe kadar olan tüm arşiv verileri kalıcı olarak silinecek. Bu işlem geri alınamaz! Onaylıyor musunuz?');">
                            <div class="mb-3">
                                <label for="delete_date" class="form-label">Silinecek Tarih</label>
                                <input type="text" class="form-control" id="delete_date" name="delete_date" required>
                                <div class="form-text text-danger">Bu tarihten önceki tüm arşiv verileri kalıcı olarak silinecek!</div>
                            </div>
                            <button type="submit" name="delete_archive" class="btn btn-danger">Arşivi Sil</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Arşivlenen Kayıtlar -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Arşivlenen Kayıtlar</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="archiveTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="games-tab" data-bs-toggle="tab" data-bs-target="#games" type="button" role="tab">Oyun Geçmişi</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" type="button" role="tab">İşlemler</button>
                            </li>
                        </ul>
                        <div class="tab-content mt-3" id="archiveTabsContent">
                            <div class="tab-pane fade show active" id="games" role="tabpanel">
                                <?php
                                $games = $db->query("SELECT g.*, u.username 
                                    FROM game_history_archive g 
                                    LEFT JOIN users u ON g.user_id = u.id 
                                    ORDER BY g.created_at DESC 
                                    LIMIT 100")->fetchAll();
                                ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tarih</th>
                                                <th>Kullanıcı</th>
                                                <th>Oyun</th>
                                                <th>Bahis</th>
                                                <th>Kazanç</th>
                                                <th>Sonuç</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($games as $game): ?>
                                            <tr>
                                                <td><?php echo date('d.m.Y H:i', strtotime($game['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($game['username']); ?></td>
                                                <td><?php echo htmlspecialchars($game['game_type']); ?></td>
                                                <td><?php echo number_format($game['bet_amount'], 2); ?> ₺</td>
                                                <td><?php echo number_format($game['win_amount'], 2); ?> ₺</td>
                                                <td><?php echo htmlspecialchars($game['result']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="transactions" role="tabpanel">
                                <?php
                                $transactions = $db->query("SELECT t.*, u.username 
                                    FROM transactions_archive t 
                                    LEFT JOIN users u ON t.user_id = u.id 
                                    ORDER BY t.created_at DESC 
                                    LIMIT 100")->fetchAll();
                                ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tarih</th>
                                                <th>Kullanıcı</th>
                                                <th>Tür</th>
                                                <th>Miktar</th>
                                                <th>Açıklama</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transactions as $transaction): ?>
                                                <tr>
                                                    <td><?php echo date('d.m.Y H:i', strtotime($transaction['created_at'])); ?></td>
                                                    <td><?php echo htmlspecialchars($transaction['username']); ?></td>
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
                                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tarih seçici ayarları
        flatpickr("#archive_date", {
            locale: "tr",
            dateFormat: "Y-m-d",
            maxDate: "today",
            defaultDate: "today"
        });
        
        flatpickr("#delete_date", {
            locale: "tr",
            dateFormat: "Y-m-d",
            maxDate: "today",
            defaultDate: "today"
        });
    </script>
</body>
</html> 