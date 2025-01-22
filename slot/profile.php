<?php
session_start();
require_once 'config/db.php';

// Kullanıcı kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Admin kontrolü
if ($_SESSION['is_admin']) {
    header("Location: admin/dashboard.php");
    exit();
}

// Kullanıcı bilgilerini al
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = trim($_POST['new_password']);
    $new_password_confirm = trim($_POST['new_password_confirm']);
    
    $error = false;
    
    // Email kontrolü
    if ($email !== $user['email']) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Bu email adresi zaten kullanımda!";
            $error = true;
        }
    }
    
    // Şifre değişikliği yapılacak mı?
    if (!$error && $new_password !== '') {
        if (!password_verify($current_password, $user['password'])) {
            $_SESSION['error'] = "Mevcut şifreniz hatalı!";
            $error = true;
        } elseif ($new_password !== $new_password_confirm) {
            $_SESSION['error'] = "Yeni şifreler eşleşmiyor!";
            $error = true;
        }
    }
    
    // Güncelleme işlemi
    if (!$error) {
        try {
            if ($new_password !== '') {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
                $stmt->execute([$email, $hashed_password, $_SESSION['user_id']]);
            } else {
                $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
            }
            
            $_SESSION['success'] = "Profil bilgileriniz güncellendi!";
            header("Location: profile.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Güncelleme sırasında bir hata oluştu!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Slot Oyunu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Slot Oyunu</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="game.php">Oyun Oyna</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="bi bi-coin"></i> Kredi: <?php echo number_format($user['credits'], 2); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($user['username']); ?>
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
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Profil Bilgileri</h5>
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

                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success">
                                <?php 
                                echo $_SESSION['success'];
                                unset($_SESSION['success']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                <div class="form-text">Kullanıcı adı değiştirilemez.</div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Mevcut Şifre</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                                <div class="form-text">Şifrenizi değiştirmek istiyorsanız doldurun.</div>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Yeni Şifre</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            <div class="mb-3">
                                <label for="new_password_confirm" class="form-label">Yeni Şifre Tekrar</label>
                                <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kredi</label>
                                <input type="text" class="form-control" value="<?php echo number_format($user['credits'], 2); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kayıt Tarihi</label>
                                <input type="text" class="form-control" value="<?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Son Giriş</label>
                                <input type="text" class="form-control" value="<?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : '-'; ?>" readonly>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Güncelle</button>
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