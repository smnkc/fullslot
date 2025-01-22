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
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: users.php");
    exit();
}

// Admin sadece kendi hesabını düzenleyebilir veya normal kullanıcıları düzenleyebilir
if ($user['is_admin'] && $user['id'] !== $_SESSION['user_id']) {
    $_SESSION['error'] = "Diğer admin kullanıcıları düzenleyemezsiniz!";
    header("Location: users.php");
    exit();
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    
    try {
        // Kullanıcı adı ve email kontrolü (kendi bilgileri hariç)
        $stmt = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Bu kullanıcı adı veya email zaten kullanımda!";
        } else {
            // Güncelleme işlemi
            if ($new_password !== '') {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $email, $hashed_password, $user_id]);
                
                // Eğer admin kendi hesabını güncelliyorsa session'ı da güncelle
                if ($user_id === $_SESSION['user_id']) {
                    $_SESSION['username'] = $username;
                }
            } else {
                $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmt->execute([$username, $email, $user_id]);
                
                // Eğer admin kendi hesabını güncelliyorsa session'ı da güncelle
                if ($user_id === $_SESSION['user_id']) {
                    $_SESSION['username'] = $username;
                }
            }
            
            $_SESSION['success'] = "Kullanıcı bilgileri güncellendi!";
            header("Location: users.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Güncelleme sırasında bir hata oluştu!";
        error_log("Kullanıcı güncelleme hatası: " . $e->getMessage());
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
                        <a class="nav-link active" href="users.php">Kullanıcılar</a>
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
                        <h5 class="card-title mb-0">Kullanıcı Düzenle</h5>
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
                                <label for="username" class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Yeni Şifre (Boş bırakılabilir)</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <div class="form-text">Şifreyi değiştirmek istemiyorsanız boş bırakın.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kredi</label>
                                <input type="text" class="form-control" value="<?php echo number_format($user['credits'], 2); ?>" readonly>
                                <div class="form-text">Kredi eklemek için <a href="add_credits.php?id=<?php echo $user_id; ?>">buraya tıklayın</a>.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kayıt Tarihi</label>
                                <input type="text" class="form-control" value="<?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Son Giriş</label>
                                <input type="text" class="form-control" value="<?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : '-'; ?>" readonly>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Güncelle</button>
                                <a href="users.php" class="btn btn-secondary">İptal</a>
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