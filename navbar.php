<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Kullanıcı bilgilerini al
$stmt = $db->prepare("SELECT username, email, credits FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Aktif sayfayı belirle
$current_page = basename($_SERVER['PHP_SELF']);

?>
<style>
.user-nav {
    background: #2c3034;
    padding: 0.5rem 0;
    margin-bottom: 2rem;
    position: relative;
}

.user-nav .nav {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
}

.user-nav .nav-link {
    color: rgba(255,255,255,.7);
    padding: 0.5rem 1rem;
    text-decoration: none;
    transition: all 0.2s ease;
    border-radius: 4px;
    margin: 0 2px;
    white-space: nowrap;
}

.user-nav .nav-link:hover {
    color: rgba(255,255,255,1);
    background: rgba(255,255,255,.1);
}

.user-nav .nav-link.active {
    color: #fff;
    background: rgba(255,255,255,.15);
}

.user-nav .credits-display {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    margin: 0 1rem;
    white-space: nowrap;
}

.user-nav .nav-link i {
    margin-right: 0.4rem;
}

@media (max-width: 768px) {
    .user-nav {
        padding: 0;
    }
    
    .user-nav .credits-display {
        width: 100%;
        text-align: center;
        margin: 0;
        padding: 0.8rem;
        border-radius: 0;
        position: relative;
        cursor: pointer;
    }
    
    .user-nav .credits-display:after {
        content: '\F282';
        font-family: 'bootstrap-icons';
        position: absolute;
        right: 1rem;
        transition: transform 0.3s;
    }
    
    .user-nav .credits-display.active:after {
        transform: rotate(180deg);
    }
    
    .user-nav .nav {
        display: none;
        width: 100%;
        padding: 0.5rem;
        background: #343a40;
        flex-direction: column;
        align-items: stretch;
    }
    
    .user-nav .nav.show {
        display: flex;
    }
    
    .user-nav .nav-item {
        width: 100%;
    }
    
    .user-nav .nav-link {
        width: 100%;
        padding: 0.8rem;
        margin: 2px 0;
        text-align: left;
    }
}

@media (max-width: 576px) {
    .user-nav .credits-display {
        font-size: 1rem;
        padding: 0.6rem;
    }
    
    .user-nav .nav-link {
        font-size: 0.9rem;
        padding: 0.6rem;
    }
    
    .user-nav .nav-link i {
        margin-right: 0.3rem;
    }
}
</style>

<nav class="user-nav">
    <div class="container px-0">
        <div class="credits-display" onclick="toggleMenu()">
            <i class="bi bi-coin"></i> Kredi: <?php echo number_format($user['credits'], 2, ',', '.'); ?>
        </div>
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-house"></i> Ana Sayfa
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'game.php' ? 'active' : ''; ?>" href="game.php">
                    <i class="bi bi-controller"></i> Slot
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'blackjack.php' ? 'active' : ''; ?>" href="blackjack.php">
                    <i class="bi bi-suit-spade"></i> Blackjack
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'deposit.php' ? 'active' : ''; ?>" href="deposit.php">
                    <i class="bi bi-plus-circle"></i> Yükle
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                    <i class="bi bi-person"></i> Profil
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'support.php' ? 'active' : ''; ?>" href="support.php">
                    <i class="bi bi-headset"></i> Destek
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Çıkış
                </a>
            </li>
        </ul>
    </div>
</nav>

<script>
function toggleMenu() {
    const nav = document.querySelector('.user-nav .nav');
    const creditsDisplay = document.querySelector('.user-nav .credits-display');
    nav.classList.toggle('show');
    creditsDisplay.classList.toggle('active');
}

// Menü dışına tıklandığında menüyü kapat
document.addEventListener('click', function(event) {
    const nav = document.querySelector('.user-nav .nav');
    const creditsDisplay = document.querySelector('.user-nav .credits-display');
    const isClickInside = nav.contains(event.target) || creditsDisplay.contains(event.target);
    
    if (!isClickInside && nav.classList.contains('show')) {
        nav.classList.remove('show');
        creditsDisplay.classList.remove('active');
    }
});

window.addEventListener('beforeunload', function(e) {
    if (gameState === 'playing') {
        e.preventDefault();
        e.returnValue = 'Oyun devam ediyor. Sayfadan ayrılırsanız bahsinizi kaybedeceksiniz!';
    }
});
</script>
