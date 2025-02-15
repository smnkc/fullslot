<?php
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../index.php');
    exit;
}

// Aktif sayfayı belirle
$current_page = basename($_SERVER['PHP_SELF']);

// Açık destek taleplerini say
$stmt = $db->prepare("SELECT COUNT(*) as count FROM support_tickets WHERE status = 'open'");
$stmt->execute();
$open_tickets = $stmt->fetch()['count'];
?>
<nav class="admin-nav">
    <div class="container px-0">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Panel
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="bi bi-people"></i> Kullanıcılar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'transactions.php' ? 'active' : ''; ?>" href="transactions.php">
                    <i class="bi bi-list-ul"></i> İşlemler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'payments.php' ? 'active' : ''; ?>" href="payments.php">
                    <i class="bi bi-credit-card"></i> Ödemeler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="bi bi-gear"></i> Ayarlar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'support.php' ? 'active' : ''; ?>" href="support.php">
                    <i class="bi bi-headset"></i> Destek
                    <?php if ($open_tickets > 0): ?>
                        <span class="badge bg-danger"><?php echo $open_tickets; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'archive_manager.php' ? 'active' : ''; ?>" href="archive_manager.php">
                    <i class="bi bi-archive"></i> Arşiv
                </a>
            </li>
            <li class="nav-item ms-auto">
                <a class="nav-link" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i> Çıkış Yap
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
.admin-nav {
    background: #343a40;
    margin-bottom: 1rem;
}

.admin-nav .nav-link {
    color: rgba(255,255,255,.75);
    padding: 1rem;
}

.admin-nav .nav-link:hover {
    color: rgba(255,255,255,.95);
    background: rgba(255,255,255,.1);
}

.admin-nav .nav-link.active {
    color: white;
    background: rgba(255,255,255,.1);
}

.admin-nav .nav-link i {
    margin-right: 0.3rem;
}
</style> 