<?php
session_start();
require_once '../config/db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit();
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Metin ayarlarını güncelle
        $text_settings = [
            'site_name' => $_POST['site_name']
        ];

        foreach ($text_settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO text_settings (setting_key, setting_value) 
                                VALUES (?, ?) 
                                ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }

        // Sayısal ayarları güncelle
        $settings = [
            'maintenance_mode' => (int)$_POST['maintenance_mode'],
            'initial_credits' => (float)$_POST['initial_credits'],
            'min_bet' => (float)$_POST['min_bet'],
            'max_bet' => (float)$_POST['max_bet'],
            'max_win' => (float)$_POST['max_win'],
            'max_daily_loss' => (float)$_POST['max_daily_loss'],
            'max_daily_bet' => (float)$_POST['max_daily_bet'],
            'login_attempts' => (int)$_POST['login_attempts']
        ];

        foreach ($settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) 
                                VALUES (?, ?) 
                                ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }

        $success = "Ayarlar başarıyla güncellendi!";
    } catch (PDOException $e) {
        $error = "Ayarlar güncellenirken bir hata oluştu: " . $e->getMessage();
    }
}

// Mevcut ayarları getir
try {
    // Sayısal ayarları getir
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    $current_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Metin ayarlarını getir
    $stmt = $db->query("SELECT setting_key, setting_value FROM text_settings");
    $current_text_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Varsayılan değerler
    $site_name = $current_text_settings['site_name'] ?? 'Slot Oyunu';
    $maintenance_mode = (int)($current_settings['maintenance_mode'] ?? 0);
    $initial_credits = (float)($current_settings['initial_credits'] ?? 100);
    $min_bet = (float)($current_settings['min_bet'] ?? 1);
    $max_bet = (float)($current_settings['max_bet'] ?? 100);
    $max_win = (float)($current_settings['max_win'] ?? 1000);
    $max_daily_loss = (float)($current_settings['max_daily_loss'] ?? 1000);
    $max_daily_bet = (float)($current_settings['max_daily_bet'] ?? 2000);
    $login_attempts = (int)($current_settings['login_attempts'] ?? 5);

} catch (PDOException $e) {
    $error = "Ayarlar yüklenirken bir hata oluştu: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Ayarları - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
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
                        <a class="nav-link active" href="settings.php">Ayarlar</a>
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
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Sistem Ayarları</h5>
            </div>
            <div class="card-body">
                <?php if (isset($success)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <form method="post">
                    <!-- Genel Ayarlar -->
                    <h5 class="mb-3">Genel Ayarlar</h5>
                    <div class="mb-3">
                        <label for="site_name" class="form-label">Site Adı</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="maintenance_mode" class="form-label">Bakım Modu</label>
                        <select class="form-select" id="maintenance_mode" name="maintenance_mode">
                            <option value="0" <?php echo $maintenance_mode == 0 ? 'selected' : ''; ?>>Kapalı</option>
                            <option value="1" <?php echo $maintenance_mode == 1 ? 'selected' : ''; ?>>Açık</option>
                        </select>
                        <div class="form-text">Bakım modu açıkken sadece adminler giriş yapabilir.</div>
                    </div>

                    <!-- Kredi Ayarları -->
                    <h5 class="mb-3 mt-4">Kredi Ayarları</h5>
                    <div class="mb-3">
                        <label for="initial_credits" class="form-label">Yeni Üye Başlangıç Kredisi</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="initial_credits" name="initial_credits" value="<?php echo htmlspecialchars($initial_credits); ?>" required>
                        <div class="form-text">Yeni kayıt olan kullanıcılara verilecek başlangıç kredisi miktarı.</div>
                    </div>

                    <!-- Oyun Ayarları -->
                    <h5 class="mb-3 mt-4">Oyun Ayarları</h5>
                    <div class="mb-3">
                        <label for="min_bet" class="form-label">Minimum Bahis Miktarı</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="min_bet" name="min_bet" value="<?php echo htmlspecialchars($min_bet); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="max_bet" class="form-label">Maksimum Bahis Miktarı</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="max_bet" name="max_bet" value="<?php echo htmlspecialchars($max_bet); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="max_win" class="form-label">Maksimum Kazanç Limiti</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="max_win" name="max_win" value="<?php echo htmlspecialchars($max_win); ?>" required>
                        <div class="form-text">Bir oyunda kazanılabilecek maksimum miktar.</div>
                    </div>

                    <!-- Güvenlik Ayarları -->
                    <h5 class="mb-3 mt-4">Güvenlik Ayarları</h5>
                    <div class="mb-3">
                        <label for="max_daily_loss" class="form-label">Günlük Maksimum Kayıp Limiti</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="max_daily_loss" name="max_daily_loss" value="<?php echo htmlspecialchars($max_daily_loss); ?>" required>
                        <div class="form-text">Kullanıcıların günlük kaybedebileceği maksimum miktar.</div>
                    </div>

                    <div class="mb-3">
                        <label for="max_daily_bet" class="form-label">Günlük Maksimum Bahis Limiti</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="max_daily_bet" name="max_daily_bet" value="<?php echo htmlspecialchars($max_daily_bet); ?>" required>
                        <div class="form-text">Kullanıcıların günlük oynayabileceği maksimum bahis miktarı.</div>
                    </div>

                    <div class="mb-3">
                        <label for="login_attempts" class="form-label">Maksimum Giriş Denemesi</label>
                        <input type="number" min="1" max="10" class="form-control" id="login_attempts" name="login_attempts" value="<?php echo htmlspecialchars($login_attempts); ?>" required>
                        <div class="form-text">Hesabın kilitlenmeden önceki başarısız giriş denemesi sayısı.</div>
                    </div>

                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 