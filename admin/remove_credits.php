<?php
session_start();
require_once '../config/db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit();
}

// Kullanıcı ID kontrolü
if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

// Kullanıcı bilgilerini al
$stmt = $db->prepare("SELECT username, credits FROM users WHERE id = ?");
$stmt->execute([(int)$_GET['id']]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: users.php");
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);
    
    if ($amount <= 0) {
        $message = "Geçerli bir miktar girin!";
        $messageType = "danger";
    } elseif ($amount > $user['credits']) {
        $message = "Kullanıcının mevcut kredisinden fazla kredi çıkaramazsınız!";
        $messageType = "danger";
    } else {
        try {
            $db->beginTransaction();
            
            // Kullanıcının kredisini güncelle
            $stmt = $db->prepare("UPDATE users SET credits = credits - ? WHERE id = ?");
            $stmt->execute([$amount, $_GET['id']]);
            
            // İşlem kaydı oluştur
            $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'kredi_silme', ?)");
            $stmt->execute([$_GET['id'], -$amount, $description]);
            
            $db->commit();
            
            $_SESSION['success'] = "Kredi başarıyla çıkarıldı!";
            header("Location: users.php");
            exit();
        } catch (PDOException $e) {
            $db->rollBack();
            $message = "Bir hata oluştu!";
            $messageType = "danger";
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kredi Çıkar - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require_once 'admin_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Kredi Çıkar</h5>
                        <a href="users.php" class="btn btn-secondary btn-sm">Geri Dön</a>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong> adlı kullanıcının mevcut kredisi: 
                            <strong><?php echo number_format($user['credits'], 2); ?></strong>
                        </div>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Çıkarılacak Miktar</label>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" max="<?php echo $user['credits']; ?>" required>
                                <div class="form-text">Mevcut krediden fazla çıkaramazsınız.</div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Açıklama</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger">Kredi Çıkar</button>
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