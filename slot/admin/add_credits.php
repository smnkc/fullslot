<?php
session_start();
require_once '../config/db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit();
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Kullanıcı bilgilerini al
$stmt = $db->prepare("SELECT username, credits FROM users WHERE id = ? AND is_admin = 0");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: dashboard.php");
    exit();
}

// Kredi ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = (float)$_POST['amount'];
    $description = trim($_POST['description']);
    
    if ($amount > 0) {
        try {
            $db->beginTransaction();
            
            // Kullanıcı kredisini güncelle
            $stmt = $db->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);
            
            // İşlem kaydı oluştur
            $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'deposit', ?)");
            $stmt->execute([$user_id, $amount, $description]);
            
            $db->commit();
            $_SESSION['success'] = "Kredi başarıyla eklendi!";
            header("Location: dashboard.php");
            exit();
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Kredi eklenirken bir hata oluştu!";
        }
    } else {
        $_SESSION['error'] = "Geçerli bir miktar giriniz!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kredi Ekle - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Kullanıcılar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php">İşlemler</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Kredi Ekle - <?php echo htmlspecialchars($user['username']); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            <div class="mb-3">
                                <label for="current_credits" class="form-label">Mevcut Kredi</label>
                                <input type="text" class="form-control" id="current_credits" value="<?php echo number_format($user['credits'], 2); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="amount" class="form-label">Eklenecek Miktar</label>
                                <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Açıklama</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Kredi Ekle</button>
                                <a href="dashboard.php" class="btn btn-secondary">İptal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 