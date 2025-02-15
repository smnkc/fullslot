<?php
session_start();
require_once '../config/db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit();
}

// Kullanıcı düzenleme işlemi
$edit_user = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $edit_user = $stmt->fetch();
}

// Düzenleme formunun gönderilmesi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = (int)$_POST['user_id'];
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    
    try {
        $db->beginTransaction();
        
        if ($new_password !== '') {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET email = ?, password = ?, is_admin = ? WHERE id = ?");
            $stmt->execute([$email, $hashed_password, $is_admin, $user_id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET email = ?, is_admin = ? WHERE id = ?");
            $stmt->execute([$email, $is_admin, $user_id]);
        }
        
        $db->commit();
        $_SESSION['success'] = "Kullanıcı bilgileri güncellendi!";
        header("Location: users.php");
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error'] = "Güncelleme sırasında bir hata oluştu!";
    }
}

try {
    // Kullanıcıları getir (admin dahil)
    $sql = "SELECT id, username, email, credits, created_at, last_login, is_admin FROM users ORDER BY id DESC";
    $users = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require_once 'admin_navbar.php'; ?>

    <div class="container mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($edit_user): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Kullanıcı Düzenle</h5>
                    <a href="users.php" class="btn btn-secondary btn-sm">Geri Dön</a>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($edit_user['username']); ?>" readonly>
                            <div class="form-text">Kullanıcı adı değiştirilemez.</div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-posta</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Yeni Şifre</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <div class="form-text">Şifreyi değiştirmek istemiyorsanız boş bırakın.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kredi</label>
                            <input type="text" class="form-control" value="<?php echo number_format($edit_user['credits'], 2); ?>" readonly>
                            <div class="form-text">Kredi eklemek veya silmek için ilgili butonları kullanın.</div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin" <?php echo $edit_user['is_admin'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_admin">Admin Yetkisi</label>
                        </div>
                        <button type="submit" name="edit_user" class="btn btn-primary">Güncelle</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Kullanıcı Yönetimi</h5>
                <a href="add_user.php" class="btn btn-success">
                    <i class="bi bi-person-plus"></i> Kullanıcı Ekle
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kullanıcı Adı</th>
                                <th>E-posta</th>
                                <th>Kredi</th>
                                <th>Kayıt Tarihi</th>
                                <th>Son Giriş</th>
                                <th>Rol</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo number_format($user['credits'], 2); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td><?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : '-'; ?></td>
                                    <td><?php echo $user['is_admin'] ? '<span class="badge bg-danger">Admin</span>' : '<span class="badge bg-primary">Kullanıcı</span>'; ?></td>
                                    <td>
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary" title="Düzenle">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if (!$user['is_admin'] || $user['id'] === $_SESSION['user_id']): ?>
                                        <a href="add_credits.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-success" title="Kredi Ekle">
                                            <i class="bi bi-coin"></i>
                                        </a>
                                        <a href="remove_credits.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" title="Kredi Çıkar">
                                            <i class="bi bi-dash-circle"></i>
                                        </a>
                                        <a href="transactions.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="İşlemler">
                                            <i class="bi bi-list"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (!$user['is_admin']): ?>
                                        <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="btn btn-sm btn-danger" title="Sil">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">Henüz kullanıcı bulunmuyor.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteUser(userId, username) {
            if (confirm(username + ' kullanıcısını silmek istediğinize emin misiniz?')) {
                fetch('delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + userId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert('Hata: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    alert('Bir hata oluştu!');
                });
            }
        }

        function removeCredits(userId, username) {
            const amount = prompt(username + ' kullanıcısından silinecek kredi miktarını girin:');
            if (amount === null) return;

            const credits = parseFloat(amount);
            if (isNaN(credits) || credits <= 0) {
                alert('Geçerli bir miktar girin!');
                return;
            }

            fetch('remove_credits.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + userId + '&amount=' + credits
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                alert('Bir hata oluştu!');
            });
        }
    </script>
</body>
</html> 