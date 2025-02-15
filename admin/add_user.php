<?php
session_start();
require_once '../config/db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $credits = floatval($_POST['credits']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    // Kullanıcı adı ve email kontrolü
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        $message = "Bu kullanıcı adı veya email zaten kullanımda!";
        $messageType = "danger";
    } else {
        // Şifreyi hash'le
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Yeni kullanıcı oluştur
        $stmt = $db->prepare("INSERT INTO users (username, email, password, credits, is_admin) VALUES (?, ?, ?, ?, ?)");
        
        try {
            $stmt->execute([$username, $email, $hashedPassword, $credits, $is_admin]);
            $message = "Kullanıcı başarıyla oluşturuldu!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Kullanıcı oluşturulurken bir hata oluştu!";
            $messageType = "danger";
            error_log("Kullanıcı oluşturma hatası - Detay: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Ekle - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
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
                        <a class="nav-link active" href="users.php">Kullanıcılar</a>
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
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Yeni Kullanıcı Ekle</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Şifre</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="credits" class="form-label">Başlangıç Kredisi</label>
                                <input type="number" class="form-control" id="credits" name="credits" value="1000" min="0" step="0.01" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin">
                                <label class="form-check-label" for="is_admin">Admin Yetkisi Ver</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Kullanıcı Oluştur</button>
                            <a href="users.php" class="btn btn-secondary">Geri Dön</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 