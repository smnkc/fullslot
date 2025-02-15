<?php
session_start();
require_once '../config/db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit();
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // Hangi formun gönderildiğini kontrol et
        if (isset($_POST['save_game'])) {
            // Oyun ayarları
            $settings = [
                'min_bet' => floatval($_POST['min_bet']),
                'max_bet' => floatval($_POST['max_bet']),
                'max_win' => floatval($_POST['max_win']),
                'max_daily_bet' => floatval($_POST['max_daily_bet'])
            ];
            $active_tab = 'game';
        } elseif (isset($_POST['save_payment'])) {
            // Ödeme ayarları
            $settings = [
                'min_deposit' => floatval($_POST['min_deposit']),
                'max_deposit' => floatval($_POST['max_deposit']),
                'bank_name' => $_POST['bank_name'],
                'bank_account_holder' => $_POST['bank_account_holder'],
                'bank_iban' => $_POST['bank_iban'],
                'papara_number' => $_POST['papara_number'],
                'papara_holder' => $_POST['papara_holder']
            ];
            $active_tab = 'payment';
        } elseif (isset($_POST['save_bonus'])) {
            // Bonus ayarları
            $settings = [
                'bonus_enabled' => isset($_POST['bonus_enabled']) ? '1' : '0',
                'daily_bonus' => floatval($_POST['daily_bonus']),
                'registration_bonus' => floatval($_POST['registration_bonus'])
            ];
            $active_tab = 'bonus';
        } elseif (isset($_POST['save_security'])) {
            // Güvenlik ayarları
            $settings = [
                'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0'
            ];
            $active_tab = 'security';
        } elseif (isset($_POST['save_general'])) {
            // Genel ayarlar
            $settings = [
                'initial_credits' => floatval($_POST['initial_credits'])
            ];
            
            // Site adını text_settings tablosuna kaydet
            $stmt = $db->prepare("INSERT INTO text_settings (setting_key, setting_value) VALUES ('site_name', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([$_POST['site_name']]);
            
            $active_tab = 'general';
        }

        // Ayarları kaydet
        if (isset($settings)) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            
            foreach ($settings as $key => $value) {
                $stmt->execute([$key, $value]);
            }
        }

        $db->commit();
        $_SESSION['success'] = "Ayarlar başarıyla güncellendi!";
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Ayar güncelleme hatası: " . $e->getMessage());
        $_SESSION['error'] = "Ayarlar güncellenirken bir hata oluştu!";
    }

    // Aynı sekmeye geri dön
    header("Location: settings.php" . (isset($active_tab) ? "?tab=" . $active_tab : ""));
    exit();
}

// Mevcut ayarları al
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Text ayarlarını al
$stmt = $db->query("SELECT setting_key, setting_value FROM text_settings");
$text_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Varsayılan değerler
$defaults = [
    'min_bet' => '10',
    'max_bet' => '1000',
    'max_win' => '10000',
    'max_daily_bet' => '10000',
    'initial_credits' => '1000',
    'bonus_enabled' => '0',
    'daily_bonus' => '100',
    'registration_bonus' => '500',
    'min_deposit' => '50',
    'max_deposit' => '10000',
    'bank_name' => '',
    'bank_account_holder' => '',
    'bank_iban' => '',
    'papara_number' => '',
    'papara_holder' => '',
    'maintenance_mode' => '0'
];

// Ayarları varsayılan değerlerle birleştir
$settings = array_merge($defaults, $settings);

// Active tab'ı belirle
$active_tab = $_GET['tab'] ?? 'general';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Ayarları - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    .nav-tabs .nav-link {
        color: #495057;
    }
    .nav-tabs .nav-link.active {
        font-weight: bold;
    }
    .page-title {
        font-size: 1.5rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #dee2e6;
    }
    </style>
</head>
<body class="bg-light">
    <?php require_once 'admin_navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="page-title">Sistem Ayarları</h2>

        <div class="card">
            <div class="card-body">
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
                <?php endif; ?>

                <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo (!isset($active_tab) || $active_tab == 'general') ? 'active' : ''; ?>" 
                                id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                            <i class="bi bi-gear"></i> Genel
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo (isset($active_tab) && $active_tab == 'payment') ? 'active' : ''; ?>" 
                                id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab">
                            <i class="bi bi-credit-card"></i> Ödeme
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo (isset($active_tab) && $active_tab == 'bonus') ? 'active' : ''; ?>" 
                                id="bonus-tab" data-bs-toggle="tab" data-bs-target="#bonus" type="button" role="tab">
                            <i class="bi bi-gift"></i> Bonus
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo (isset($active_tab) && $active_tab == 'game') ? 'active' : ''; ?>" 
                                id="game-tab" data-bs-toggle="tab" data-bs-target="#game" type="button" role="tab">
                            <i class="bi bi-controller"></i> Oyun
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo (isset($active_tab) && $active_tab == 'security') ? 'active' : ''; ?>" 
                                id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                            <i class="bi bi-shield-lock"></i> Güvenlik
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="settingsTabContent">
                    <!-- Genel Ayarlar -->
                    <div class="tab-pane fade <?php echo (!isset($active_tab) || $active_tab == 'general') ? 'show active' : ''; ?>" 
                         id="general" role="tabpanel">
                        <form method="post" id="generalForm">
                            <input type="hidden" name="active_tab" value="general">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="site_name" class="form-label">Site Adı</label>
                                        <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($text_settings['site_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="initial_credits" class="form-label">Başlangıç Kredisi</label>
                                        <input type="number" class="form-control" id="initial_credits" name="initial_credits" value="<?php echo $settings['initial_credits'] ?? 1000; ?>" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end mt-3">
                                <button type="submit" name="save_general" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Kaydet
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Ödeme Ayarları -->
                    <div class="tab-pane fade <?php echo (isset($active_tab) && $active_tab == 'payment') ? 'show active' : ''; ?>" 
                         id="payment" role="tabpanel">
                        <form method="post" id="paymentForm">
                            <input type="hidden" name="active_tab" value="payment">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-3">Yükleme Limitleri</h6>
                                    <div class="mb-3">
                                        <label for="min_deposit" class="form-label">Minimum Yükleme Miktarı</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" class="form-control" id="min_deposit" name="min_deposit" value="<?php echo htmlspecialchars($settings['min_deposit'] ?? 50); ?>" required>
                                            <span class="input-group-text">TL</span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="max_deposit" class="form-label">Maksimum Yükleme Miktarı</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" class="form-control" id="max_deposit" name="max_deposit" value="<?php echo htmlspecialchars($settings['max_deposit'] ?? 10000); ?>" required>
                                            <span class="input-group-text">TL</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-3">Banka Hesap Bilgileri</h6>
                                    <div class="mb-3">
                                        <label for="bank_name" class="form-label">Banka Adı</label>
                                        <input type="text" class="form-control" id="bank_name" name="bank_name" value="<?php echo htmlspecialchars($settings['bank_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="bank_account_holder" class="form-label">Hesap Sahibi</label>
                                        <input type="text" class="form-control" id="bank_account_holder" name="bank_account_holder" value="<?php echo htmlspecialchars($settings['bank_account_holder'] ?? ''); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="bank_iban" class="form-label">IBAN</label>
                                        <input type="text" class="form-control" id="bank_iban" name="bank_iban" value="<?php echo htmlspecialchars($settings['bank_iban'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-3">Papara Hesap Bilgileri</h6>
                                    <div class="mb-3">
                                        <label for="papara_holder" class="form-label">Papara Hesap Sahibi</label>
                                        <input type="text" class="form-control" id="papara_holder" name="papara_holder" value="<?php echo htmlspecialchars($settings['papara_holder'] ?? ''); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="papara_number" class="form-label">Papara Numarası</label>
                                        <input type="text" class="form-control" id="papara_number" name="papara_number" value="<?php echo htmlspecialchars($settings['papara_number'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end mt-3">
                                <button type="submit" name="save_payment" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Kaydet
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Bonus Ayarları -->
                    <div class="tab-pane fade <?php echo (isset($active_tab) && $active_tab == 'bonus') ? 'show active' : ''; ?>" 
                         id="bonus" role="tabpanel">
                        <form method="post" id="bonusForm">
                            <input type="hidden" name="active_tab" value="bonus">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input" id="bonus_enabled" name="bonus_enabled" <?php echo ($settings['bonus_enabled'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="bonus_enabled">Bonus Sistemi Aktif</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="daily_bonus" class="form-label">Günlük Bonus Miktarı</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="daily_bonus" name="daily_bonus" value="<?php echo $settings['daily_bonus'] ?? 100; ?>" step="0.01" required>
                                            <span class="input-group-text">TL</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="registration_bonus" class="form-label">Kayıt Bonusu</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="registration_bonus" name="registration_bonus" value="<?php echo $settings['registration_bonus'] ?? 500; ?>" step="0.01" required>
                                            <span class="input-group-text">TL</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end mt-3">
                                <button type="submit" name="save_bonus" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Kaydet
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Oyun Ayarları -->
                    <div class="tab-pane fade <?php echo (isset($active_tab) && $active_tab == 'game') ? 'show active' : ''; ?>" 
                         id="game" role="tabpanel">
                        <form method="post" id="gameForm">
                            <input type="hidden" name="active_tab" value="game">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="min_bet" class="form-label">Minimum Bahis</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="min_bet" name="min_bet" value="<?php echo $settings['min_bet'] ?? 10; ?>" step="0.01" required>
                                            <span class="input-group-text">TL</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="max_bet" class="form-label">Maksimum Bahis</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="max_bet" name="max_bet" value="<?php echo $settings['max_bet'] ?? 1000; ?>" step="0.01" required>
                                            <span class="input-group-text">TL</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="max_win" class="form-label">Maksimum Kazanç</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="max_win" name="max_win" value="<?php echo $settings['max_win'] ?? 10000; ?>" step="0.01" required>
                                            <span class="input-group-text">TL</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="max_daily_bet" class="form-label">Günlük Maksimum Bahis</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="max_daily_bet" name="max_daily_bet" value="<?php echo $settings['max_daily_bet'] ?? 10000; ?>" step="0.01" required>
                                            <span class="input-group-text">TL</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end mt-3">
                                <button type="submit" name="save_game" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Kaydet
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Güvenlik Ayarları -->
                    <div class="tab-pane fade <?php echo (isset($active_tab) && $active_tab == 'security') ? 'show active' : ''; ?>" 
                         id="security" role="tabpanel">
                        <form method="post" id="securityForm">
                            <input type="hidden" name="active_tab" value="security">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode" <?php echo ($settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="maintenance_mode">Bakım Modu</label>
                                            <div class="form-text">Bakım modu aktif olduğunda sadece adminler siteye erişebilir.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end mt-3">
                                <button type="submit" name="save_security" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Tab durumunu localStorage'da sakla
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function(tab) {
        tab.addEventListener('shown.bs.tab', function (e) {
            localStorage.setItem('activeSettingsTab', e.target.id);
        });
    });

    // Sayfa yüklendiğinde son aktif tab'ı göster
    document.addEventListener('DOMContentLoaded', function() {
        const activeTab = localStorage.getItem('activeSettingsTab');
        if (activeTab) {
            const tab = new bootstrap.Tab(document.querySelector('#' + activeTab));
            tab.show();
        }
    });

    // Form validasyonu
    function validateForm(formId) {
        const form = document.getElementById(formId);
        if (!form) return true;
        
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        return isValid;
    }

    // Her forma validasyon ekle
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this.id)) {
                e.preventDefault();
                alert('Lütfen tüm gerekli alanları doldurun!');
            }
        });
    });
    </script>
</body>
</html> 