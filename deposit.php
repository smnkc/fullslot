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

// Ayarları al
$stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('min_deposit', 'max_deposit')");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$min_deposit = floatval($settings['min_deposit'] ?? 50);
$max_deposit = floatval($settings['max_deposit'] ?? 10000);

// Kullanıcı bilgilerini al
$stmt = $db->prepare("SELECT username, credits FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Form gönderildi mi?
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $payment_method = $_POST['payment_method'];

    if ($amount < $min_deposit || $amount > $max_deposit) {
        $message = "Yükleme miktarı {$min_deposit} TL ile {$max_deposit} TL arasında olmalıdır!";
        $messageType = "danger";
    } else {
        try {
            $db->beginTransaction();

            // Ödeme kaydı oluştur
            $stmt = $db->prepare("INSERT INTO payment_requests (user_id, amount, payment_method, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $amount, $payment_method]);
            $payment_id = $db->lastInsertId();

            $db->commit();

            $_SESSION['success'] = "Ödeme talebiniz alındı! Lütfen aşağıdaki bilgileri kullanarak ödemenizi yapın.";
            header("Location: payment.php?id=" . $payment_id);
            exit();
        } catch (PDOException $e) {
            $db->rollBack();
            $message = "Bir hata oluştu, lütfen tekrar deneyin!";
            $messageType = "danger";
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
    <title>Kredi Yükle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    @media (max-width: 768px) {
        .btn-check + .btn {
            font-size: 0.9rem;
            padding: 0.5rem;
        }
        
        .table-responsive {
            font-size: 0.9rem;
        }
        
        .card-title {
            font-size: 1.1rem;
        }
    }

    @media (max-width: 576px) {
        .btn-check + .btn {
            font-size: 0.8rem;
            padding: 0.4rem;
        }
        
        .table-responsive {
            font-size: 0.8rem;
        }
        
        .badge {
            font-size: 0.7rem;
        }
        
        .form-text {
            font-size: 0.8rem;
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
                        <h5 class="card-title mb-0">Kredi Yükle</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Yüklenecek Miktar (TL)</label>
                                <input type="number" class="form-control" id="amount" name="amount" min="<?php echo $min_deposit; ?>" max="<?php echo $max_deposit; ?>" step="1" required>
                                <div class="form-text">
                                    Minimum: <?php echo number_format($min_deposit, 2); ?> TL<br>
                                    Maksimum: <?php echo number_format($max_deposit, 2); ?> TL
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ödeme Yöntemi</label>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="payment_method" id="havale" value="havale" checked>
                                        <label class="btn btn-outline-primary w-100" for="havale">
                                            <i class="bi bi-bank"></i> Havale/EFT
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="payment_method" id="papara" value="papara">
                                        <label class="btn btn-outline-primary w-100" for="papara">
                                            <i class="bi bi-wallet2"></i> Papara
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Ödemeye Geç</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Yükleme Geçmişi</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $db->prepare("SELECT * FROM payment_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                        $stmt->execute([$_SESSION['user_id']]);
                        $payments = $stmt->fetchAll();
                        
                        if (empty($payments)): ?>
                            <p class="text-muted mb-0">Henüz yükleme işlemi bulunmuyor.</p>
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