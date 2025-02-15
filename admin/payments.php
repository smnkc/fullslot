<?php
session_start();
require_once '../config/db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit();
}

// Ödeme onaylama/reddetme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $payment_id = (int)$_POST['payment_id'];
    $action = $_POST['action'];
    $user_id = (int)$_POST['user_id'];
    $amount = floatval($_POST['amount']);

    try {
        $db->beginTransaction();

        if ($action === 'approve') {
            // Kullanıcının kredisini güncelle
            $stmt = $db->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);

            // Ödeme durumunu güncelle
            $stmt = $db->prepare("UPDATE payment_requests SET status = 'completed' WHERE id = ?");
            $stmt->execute([$payment_id]);

            // İşlem kaydı oluştur
            $stmt = $db->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'yukleme', 'Kredi yükleme')");
            $stmt->execute([$user_id, $amount]);

            $_SESSION['success'] = "Ödeme başarıyla onaylandı ve krediler yüklendi.";
        } else if ($action === 'reject') {
            // Ödemeyi reddet
            $stmt = $db->prepare("UPDATE payment_requests SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$payment_id]);

            $_SESSION['success'] = "Ödeme reddedildi.";
        }

        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error'] = "İşlem sırasında bir hata oluştu!";
        error_log($e->getMessage());
    }

    header("Location: payments.php");
    exit();
}

// Ödeme silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_payments'])) {
    $payment_ids = $_POST['payment_ids'] ?? [];
    $status = $_POST['status'] ?? '';
    $date = $_POST['date'] ?? '';

    try {
        $db->beginTransaction();

        $sql = "DELETE FROM payment_requests WHERE 1=1";
        $params = [];

        if (!empty($payment_ids)) {
            $sql .= " AND id IN (" . implode(',', array_map('intval', $payment_ids)) . ")";
        } elseif ($status !== '') {
            $sql .= " AND status = ?";
            $params[] = $status;
            if ($date !== '') {
                $sql .= " AND DATE(created_at) <= ?";
                $params[] = $date;
            }
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $db->commit();
        $_SESSION['success'] = "Seçili ödemeler başarıyla silindi!";
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error'] = "Silme işlemi sırasında bir hata oluştu!";
        error_log($e->getMessage());
    }

    header("Location: payments.php");
    exit();
}

// Ödemeleri listele
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT p.*, u.username 
        FROM payment_requests p 
        JOIN users u ON p.user_id = u.id";

if ($status_filter !== '') {
    $sql .= " WHERE p.status = :status";
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($sql);
if ($status_filter !== '') {
    $stmt->bindParam(':status', $status_filter);
}
$stmt->execute();
$payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Yönetimi - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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

        <!-- Toplu Silme Formu -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Ödeme Kayıtlarını Sil</h5>
            </div>
            <div class="card-body">
                <form method="POST" onsubmit="return confirm('Seçili ödemeleri silmek istediğinize emin misiniz?');">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Durum</label>
                            <select name="status" class="form-select">
                                <option value="">Tümü</option>
                                <option value="completed">Tamamlanan</option>
                                <option value="cancelled">İptal Edilen</option>
                                <option value="pending">Bekleyen</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tarihten Önce</label>
                            <input type="date" name="date" class="form-control" id="delete_date">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="delete_payments" class="btn btn-danger d-block">Seçili Kayıtları Sil</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Ödeme Talepleri</h5>
                <div class="btn-group">
                    <a href="?status=" class="btn btn-outline-primary <?php echo $status_filter === '' ? 'active' : ''; ?>">Tümü</a>
                    <a href="?status=pending" class="btn btn-outline-primary <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Bekleyenler</a>
                    <a href="?status=completed" class="btn btn-outline-primary <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">Tamamlananlar</a>
                    <a href="?status=cancelled" class="btn btn-outline-primary <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">İptal Edilenler</a>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" id="payments-form">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="select-all">
                                    </th>
                                    <th>ID</th>
                                    <th>Kullanıcı</th>
                                    <th>Miktar</th>
                                    <th>Yöntem</th>
                                    <th>Durum</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="payment_ids[]" value="<?php echo $payment['id']; ?>" class="payment-checkbox">
                                    </td>
                                    <td><?php echo $payment['id']; ?></td>
                                    <td><?php echo htmlspecialchars($payment['username']); ?></td>
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
                                    <td><?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?></td>
                                    <td>
                                        <?php if ($payment['status'] === 'pending'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $payment['user_id']; ?>">
                                                <input type="hidden" name="amount" value="<?php echo $payment['amount']; ?>">
                                                
                                                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm" onclick="return confirm('Bu ödemeyi onaylamak istediğinize emin misiniz?')">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Bu ödemeyi reddetmek istediğinize emin misiniz?')">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="delete_payments" value="1">
                                                <input type="hidden" name="payment_ids[]" value="<?php echo $payment['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bu ödeme kaydını silmek istediğinize emin misiniz?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Tarih seçici
        flatpickr("#delete_date", {
            dateFormat: "Y-m-d",
            maxDate: "today"
        });

        // Tümünü seç/kaldır
        document.getElementById('select-all').addEventListener('change', function() {
            document.querySelectorAll('.payment-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Tekli silme işlemi
        function deletePayment(id) {
            if (confirm('Bu ödeme kaydını silmek istediğinize emin misiniz?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="delete_payments" value="1">
                    <input type="hidden" name="payment_ids[]" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html> 