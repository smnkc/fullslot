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

// Ödeme ID kontrolü
if (!isset($_GET['id'])) {
    header("Location: deposit.php");
    exit();
}

// Ödeme bilgilerini al
$stmt = $db->prepare("SELECT * FROM payment_requests WHERE id = ? AND user_id = ?");
$stmt->execute([$_GET['id'], $_SESSION['user_id']]);
$payment = $stmt->fetch();

if (!$payment) {
    header("Location: deposit.php");
    exit();
}

// Banka hesap bilgilerini al
$stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('bank_name', 'bank_account_holder', 'bank_iban', 'papara_number', 'papara_holder')");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Detayları - Slot Oyunu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require_once 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Ödeme Detayları</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6 class="alert-heading">Ödeme Tutarı</h6>
                            <h3 class="mb-0"><?php echo number_format($payment['amount'], 2); ?> TL</h3>
                        </div>

                        <?php if ($payment['payment_method'] === 'havale'): ?>
                            <div class="mb-4">
                                <h6>Banka Hesap Bilgileri</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Banka</th>
                                        <td><?php echo htmlspecialchars($settings['bank_name'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Hesap Sahibi</th>
                                        <td><?php echo htmlspecialchars($settings['bank_account_holder'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <th>IBAN</th>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2"><?php echo htmlspecialchars($settings['bank_iban'] ?? ''); ?></span>
                                                <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('<?php echo htmlspecialchars($settings['bank_iban'] ?? ''); ?>')">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="mb-4">
                                <h6>Papara Hesap Bilgileri</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Hesap Sahibi</th>
                                        <td><?php echo htmlspecialchars($settings['papara_holder'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Papara No</th>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2"><?php echo htmlspecialchars($settings['papara_number'] ?? ''); ?></span>
                                                <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('<?php echo htmlspecialchars($settings['papara_number'] ?? ''); ?>')">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-warning">
                            <h6 class="alert-heading">Önemli Bilgiler</h6>
                            <ul class="mb-0">
                                <li>Ödemenizi yaptıktan sonra destek ekibimiz ile iletişime geçiniz.</li>
                                <li>Ödeme açıklamasına kullanıcı adınızı (<strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>) yazmayı unutmayınız.</li>
                                <li>Ödeme onayı ortalama 5-10 dakika içerisinde yapılmaktadır.</li>
                            </ul>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="deposit.php" class="btn btn-secondary">Geri Dön</a>
                            <a href="support.php" class="btn btn-primary">Destek Ekibine Yazın</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Kopyalandı!');
        }).catch(err => {
            console.error('Kopyalama başarısız:', err);
        });
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 