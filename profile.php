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
$stmt = $db->prepare("SELECT id, username, email, password, credits, created_at, last_login FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: index.php");
    exit();
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = trim($_POST['new_password']);
    $new_password_confirm = trim($_POST['new_password_confirm']);
    
    $error = false;
    
    // Şifre değişikliği yapılacak mı?
    if ($new_password !== '') {
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
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
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
    <title>Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.9rem;
        }
        
        .card-title {
            font-size: 1.1rem;
        }
        
        .btn {
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
        }
        
        .form-label {
            font-size: 0.9rem;
        }
    }

    @media (max-width: 576px) {
        .table-responsive {
            font-size: 0.8rem;
        }
        
        .badge {
            font-size: 0.7rem;
        }
        
        .card-body {
            padding: 1rem;
        }
    }
    </style>
</head>
<body class="bg-light">
    <?php require_once 'navbar.php'; ?>

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
                                <input type="text" class="form-control" id="email" 
                                       value="<?php echo isset($user['email']) && !empty($user['email']) ? htmlspecialchars($user['email']) : 'E-posta tanımlanmamış'; ?>" 
                                       readonly>
                                <div class="form-text">E-posta adresi değiştirilemez.</div>
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
                                <input type="text" class="form-control" value="<?php echo isset($user['created_at']) ? date('d.m.Y H:i', strtotime($user['created_at'])) : '-'; ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Son Giriş</label>
                                <input type="text" class="form-control" value="<?php echo isset($user['last_login']) && $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : '-'; ?>" readonly>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Güncelle</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Kredi Yükleme Geçmişi -->
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Kredi Yükleme Geçmişi</h5>
                        <a href="deposit.php" class="btn btn-primary btn-sm">Kredi Yükle</a>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $db->prepare("SELECT * FROM payment_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
                        $stmt->execute([$_SESSION['user_id']]);
                        $payments = $stmt->fetchAll();
                        
                        if (empty($payments)): ?>
                            <p class="text-muted mb-0">Henüz kredi yükleme işlemi bulunmuyor.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Miktar</th>
                                            <th>Yöntem</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?></td>
                                                <td><?php echo number_format($payment['amount'], 2); ?> TL</td>
                                                <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                                <td>
                                                    <?php
                                                    switch($payment['status']) {
                                                        case 'pending':
                                                            echo '<span class="badge bg-warning">Bekliyor</span>';
                                                            break;
                                                        case 'completed':
                                                            echo '<span class="badge bg-success">Tamamlandı</span>';
                                                            break;
                                                        case 'cancelled':
                                                            echo '<span class="badge bg-danger">İptal Edildi</span>';
                                                            break;
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 