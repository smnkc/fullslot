<?php
session_start();
require_once '../config/db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit();
}

try {
    // Kullanıcıları getir (filtreleme için)
    $users = $db->query("SELECT id, username FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

    // Filtreleme parametrelerini al
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    
    // İşlem geçmişini getir
    $sql = "SELECT t.*, u.username 
            FROM transactions t 
            JOIN users u ON t.user_id = u.id";
    
    if ($user_id > 0) {
        $sql .= " WHERE t.user_id = :user_id";
    }
    
    $sql .= " ORDER BY t.id DESC";
    
    $stmt = $db->prepare($sql);
    if ($user_id > 0) {
        $stmt->bindParam(':user_id', $user_id);
    }
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İşlem Geçmişi - Admin Panel</title>
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
                        <a class="nav-link" href="users.php">Kullanıcılar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="transactions.php">İşlemler</a>
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
                <h5 class="card-title mb-0">İşlem Geçmişi</h5>
                <div>
                    <button onclick="deleteSelectedTransactions()" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Seçilenleri Sil
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form class="mb-3" method="get">
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label for="user_id" class="col-form-label">Kullanıcı:</label>
                        </div>
                        <div class="col-auto">
                            <select name="user_id" id="user_id" class="form-select" onchange="this.form.submit()">
                                <option value="0">Tümü</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $user_id == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                </th>
                                <th>ID</th>
                                <th>Kullanıcı</th>
                                <th>Miktar</th>
                                <th>Tür</th>
                                <th>Açıklama</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($transactions)): ?>
                                <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="transaction-checkbox" value="<?php echo $transaction['id']; ?>">
                                    </td>
                                    <td><?php echo $transaction['id']; ?></td>
                                    <td><?php echo htmlspecialchars($transaction['username']); ?></td>
                                    <td><?php echo number_format($transaction['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['type']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['description'] ?? '-'); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($transaction['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">İşlem geçmişi bulunamadı.</td>
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
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.getElementsByClassName('transaction-checkbox');
            
            for (let checkbox of checkboxes) {
                checkbox.checked = selectAll.checked;
            }
        }

        function deleteSelectedTransactions() {
            const checkboxes = document.getElementsByClassName('transaction-checkbox');
            const selectedIds = [];
            
            for (let checkbox of checkboxes) {
                if (checkbox.checked) {
                    selectedIds.push(checkbox.value);
                }
            }
            
            if (selectedIds.length === 0) {
                alert('Lütfen silinecek işlemleri seçin!');
                return;
            }
            
            if (confirm('Seçili işlemleri silmek istediğinize emin misiniz?')) {
                fetch('delete_transactions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        transaction_ids: selectedIds
                    })
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
    </script>
</body>
</html> 