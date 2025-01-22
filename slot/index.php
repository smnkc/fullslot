<?php
session_start();
require_once 'config/db.php';

// Oturum kontrolü - Eğer giriş yapılmışsa dashboard'a yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Bakım modu kontrolü
try {
    $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
    $maintenance_mode = (int)($stmt->fetch()['setting_value'] ?? 0);

    if ($maintenance_mode && (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin'])) {
        echo '<!DOCTYPE html>
        <html lang="tr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Bakım Modu</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container mt-5">
                <div class="card mx-auto" style="max-width: 500px;">
                    <div class="card-body text-center">
                        <h3 class="card-title mb-4">🛠️ Bakım Modu</h3>
                        <p class="card-text">Sitemiz şu anda bakımdadır. Lütfen daha sonra tekrar deneyiniz.</p>
                        <hr>
                        <p class="small text-muted mb-0">
                            <a href="admin/login.php" class="text-decoration-none">Admin Girişi</a>
                        </p>
                    </div>
                </div>
            </div>
        </body>
        </html>';
        exit();
    }
} catch (PDOException $e) {
    error_log("Bakım modu kontrolü hatası: " . $e->getMessage());
}

// Giriş işlemi
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Kullanıcı adı ve şifre gereklidir!';
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];

                // Son giriş zamanını güncelle
                $stmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$user['id']]);

                if ($user['is_admin']) {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error = 'Geçersiz kullanıcı adı veya şifre!';
            }
        } catch (PDOException $e) {
            $error = 'Bir hata oluştu!';
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
    <title>Slot Oyunu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 mx-auto text-center mb-5">
                <h1 class="display-4">Slot Oyununa Hoş Geldiniz</h1>
                <p class="lead">Hemen üye olun, şansınızı deneyin!</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Giriş Yap</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Şifre</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
                        </form>
                        <div class="mt-3 text-center">
                            <a href="register.php" class="text-decoration-none">Hesabınız yok mu? Kayıt olun</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 