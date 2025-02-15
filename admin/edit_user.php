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
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([(int)$_GET['id']]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: users.php");
    exit();
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $username = trim($_POST['username']);
    
    try {
        $db->beginTransaction();
        
        // Kullanıcı adı kontrolü
        if ($username !== $user['username']) {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user['id']]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Bu kullanıcı adı zaten kullanımda!");
            }
        }
        
        if ($new_password !== '') {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, password = ?, is_admin = ? WHERE id = ?");
            $stmt->execute([$username, $email, $hashed_password, $is_admin, $user['id']]);
        } else {
            $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, is_admin = ? WHERE id = ?");
            $stmt->execute([$username, $email, $is_admin, $user['id']]);
        }
        
        $db->commit();
        $_SESSION['success'] = "Kullanıcı bilgileri güncellendi!";
        header("Location: users.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Düzenle - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require_once 'admin_navbar.php'; ?>

    <div class="container mt-4">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Kullanıcı Düzenle</h5>
                <a href="users.php" class="btn btn-secondary">Geri Dön</a>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                <div class="form-text">Kullanıcı adı benzersiz olmalıdır.</div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Yeni Şifre</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <div class="form-text">Şifreyi değiştirmek istemiyorsanız boş bırakın.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kredi</label>
                                <input type="text" class="form-control" value="<?php echo number_format($user['credits'], 2); ?>" readonly>
                                <div class="form-text">Kredi eklemek veya silmek için kullanıcı listesindeki ilgili butonları kullanın.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kayıt Tarihi</label>
                                <input type="text" class="form-control" value="<?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Son Giriş</label>
                                <input type="text" class="form-control" value="<?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : '-'; ?>" readonly>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin" <?php echo $user['is_admin'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_admin">Admin Yetkisi</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Güncelle</button>
                        <a href="users.php" class="btn btn-secondary">İptal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 