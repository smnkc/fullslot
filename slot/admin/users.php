<?php
session_start();
require_once '../config/db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit();
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
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
                                        <button onclick="removeCredits(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="btn btn-sm btn-warning" title="Kredi Sil">
                                            <i class="bi bi-dash-circle"></i>
                                        </button>
                                        <a href="user_transactions.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="İşlemler">
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