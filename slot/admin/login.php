<?php
session_start();
require_once '../config/db.php';

// Zaten giriş yapmış admin varsa yönlendir
if (isset($_SESSION['user_id']) && $_SESSION['is_admin']) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Kullanıcı adı ve şifre gereklidir!';
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_admin = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];

                // Son giriş zamanını güncelle
                $stmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$user['id']]);

                header("Location: dashboard.php");
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
    <title>Admin Girişi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark">
    <div class="container mt-5">
        <div class="card mx-auto" style="max-width: 400px;">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0">Admin Girişi</h4>
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
                    <a href="../index.php" class="text-decoration-none">Ana Sayfaya Dön</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 