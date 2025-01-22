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
$stmt = $db->prepare("SELECT credits FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slot Oyunu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .slot-machine {
            background: #2c3e50;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }
        .slot-row {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
        }
        .slot {
            width: 100px;
            height: 100px;
            background: #fff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            position: relative;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }
        .slot.spinning {
            animation: spin 0.5s linear infinite;
        }
        .slot.winner {
            animation: winner 0.5s ease infinite;
        }
        @keyframes spin {
            0% { transform: translateY(-5px) rotate(-5deg); }
            50% { transform: translateY(5px) rotate(5deg); }
            100% { transform: translateY(-5px) rotate(-5deg); }
        }
        @keyframes winner {
            0% { transform: scale(1); box-shadow: 0 0 10px gold, inset 0 0 10px rgba(0,0,0,0.2); }
            50% { transform: scale(1.1); box-shadow: 0 0 30px gold, inset 0 0 10px rgba(0,0,0,0.2); }
            100% { transform: scale(1); box-shadow: 0 0 10px gold, inset 0 0 10px rgba(0,0,0,0.2); }
        }
        .controls {
            text-align: center;
            margin: 30px 0;
        }
        .bet-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .bet-amount {
            font-size: 24px;
            padding: 10px 30px;
            background: #34495e;
            color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            display: none;
            min-width: 300px;
            text-align: center;
            padding: 15px 30px;
            border-radius: 5px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            margin-top: 30px;
        }
        .stat-box {
            background: #34495e;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .stat-box h5 {
            margin: 0 0 10px 0;
            color: rgba(255,255,255,0.8);
            font-size: 16px;
        }
        .stat-box span {
            font-size: 24px;
            font-weight: bold;
            color: white;
        }
        .btn {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .btn-lg {
            padding: 12px 30px;
            font-size: 18px;
            margin: 0 10px;
        }
        @media (max-width: 768px) {
            .slot {
                width: 80px;
                height: 80px;
                font-size: 36px;
            }
            .bet-controls {
                gap: 10px;
            }
            .bet-amount {
                font-size: 20px;
                padding: 8px 20px;
            }
            .btn-lg {
                padding: 10px 20px;
                font-size: 16px;
            }
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Slot Oyunu</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="game.php">Slot</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="blackjack.php">Blackjack</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profil</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">
                            Kredi: <span id="balanceAmount"><?php echo number_format($user['credits'], 2, ',', '.'); ?></span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Çıkış</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div id="messageBox" class="alert message"></div>

        <div class="card">
            <div class="card-body">
                <div class="slot-machine">
                    <div class="slot-row">
                        <div class="slot">🎰</div>
                        <div class="slot">🎰</div>
                        <div class="slot">🎰</div>
                    </div>

                    <div class="controls">
                        <div class="bet-controls">
                            <button class="btn btn-secondary bet-button" data-amount="10">10</button>
                            <button class="btn btn-secondary bet-button" data-amount="20">20</button>
                            <button class="btn btn-secondary bet-button" data-amount="50">50</button>
                            <button class="btn btn-secondary bet-button" data-amount="100">100</button>
                            <span class="bet-amount">Bahis: <span id="betAmount">0</span></span>
                            <button class="btn btn-danger" id="clearBetButton">Temizle</button>
                        </div>

                        <button id="spinButton" class="btn btn-success btn-lg">
                            <i class="bi bi-play-circle"></i> Çevir
                        </button>
                        <button id="autoSpinButton" class="btn btn-primary btn-lg">
                            <i class="bi bi-arrow-repeat"></i> Otomatik
                        </button>
                    </div>

                    <div class="stats">
                        <div class="stat-box">
                            <h5>Toplam Oyun</h5>
                            <span id="totalGames">0</span>
                        </div>
                        <div class="stat-box">
                            <h5>Kazanılan</h5>
                            <span id="wonGames">0</span>
                        </div>
                        <div class="stat-box">
                            <h5>Toplam Kazanç</h5>
                            <span id="totalWin">0</span>
                        </div>
                        <div class="stat-box">
                            <h5>En Yüksek Kazanç</h5>
                            <span id="highScore">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Kazanç Tablosu</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 col-md-4">
                        <p class="mb-2">7️⃣7️⃣7️⃣ = 40x</p>
                        <p class="mb-2">💎💎💎 = 35x</p>
                    </div>
                    <div class="col-6 col-md-4">
                        <p class="mb-2">🍒🍒🍒 = 30x</p>
                        <p class="mb-2">🍇🍇🍇 = 25x</p>
                    </div>
                    <div class="col-12 col-md-4">
                        <p class="mb-2">🍊🍊🍊 = 20x</p>
                        <p class="mb-2">🍎🍎🍎 = 15x</p>
                    </div>
                    <div class="col-12">
                        <p class="text-muted mb-0">İki aynı sembol = 2x</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentBet = 0;
        let isSpinning = false;
        let autoPlayInterval = null;
        let stats = {
            totalGames: 0,
            wonGames: 0,
            totalWin: 0,
            highScore: 0
        };

        function showMessage(message, isError = false) {
            const messageBox = $('#messageBox');
            messageBox.removeClass('alert-success alert-danger').addClass(isError ? 'alert-danger' : 'alert-success');
            messageBox.text(message).fadeIn();
            setTimeout(() => messageBox.fadeOut(), 3000);
        }

        function updateBalance(amount) {
            const formattedAmount = new Intl.NumberFormat('tr-TR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount);
            $('#balanceAmount').text(formattedAmount);
        }

        function validateBet(amount) {
            const balance = parseFloat($('#balanceAmount').text().replace(/\./g, '').replace(',', '.'));
            return amount > 0 && amount <= balance;
        }

        function setBet(amount) {
            if (isSpinning) return;
            currentBet = amount;
            $('#betAmount').text(amount);
            $('.bet-button').removeClass('btn-primary').addClass('btn-secondary');
            $(`.bet-button[data-amount="${amount}"]`).removeClass('btn-secondary').addClass('btn-primary');
        }

        function clearBet() {
            if (isSpinning) return;
            currentBet = 0;
            $('#betAmount').text('0');
            $('.bet-button').removeClass('btn-primary').addClass('btn-secondary');
        }

        function updateStats() {
            $('#totalGames').text(stats.totalGames);
            $('#wonGames').text(stats.wonGames);
            $('#totalWin').text(stats.totalWin);
            $('#highScore').text(stats.highScore);
        }

        function spin() {
            if (isSpinning || !validateBet(currentBet)) return;
            
            isSpinning = true;
            
            // Slotları döndür
            $('.slot').each(function() {
                $(this).text('🎰').addClass('spinning');
            });

            // Form verisi oluştur
            const formData = new FormData();
            formData.append('bet', currentBet);

            // Sunucuya istek at
            fetch('spin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(response => {
                if (response.status === 'error') {
                    showMessage(response.message, true);
                    $('.slot').removeClass('spinning');
                    isSpinning = false;
                    return;
                }

                // Slotları sırayla güncelle
                response.symbols.forEach((symbol, index) => {
                    setTimeout(() => {
                        $('.slot').eq(index)
                            .removeClass('spinning')
                            .text(symbol);
                        
                        if (response.win > 0) {
                            $('.slot').eq(index).addClass('winner');
                        }
                    }, index * 300);
                });

                setTimeout(() => {
                    updateBalance(response.balance);
                    if (response.win > 0) {
                        showMessage(response.message);
                        stats.wonGames++;
                        stats.totalWin += response.win;
                        stats.highScore = Math.max(stats.highScore, response.win);
                    }
                    stats.totalGames++;
                    updateStats();
                    isSpinning = false;
                }, 1000);
            })
            .catch(error => {
                console.error('Hata:', error);
                showMessage('Bir hata oluştu!', true);
                $('.slot').removeClass('spinning');
                isSpinning = false;
            });
        }

        function startAutoPlay() {
            if (autoPlayInterval) return;
            $('#autoSpinButton').removeClass('btn-primary').addClass('btn-danger').text('Durdur');
            autoPlayInterval = setInterval(() => {
                if (!isSpinning) spin();
            }, 2000);
        }

        function stopAutoPlay() {
            if (!autoPlayInterval) return;
            clearInterval(autoPlayInterval);
            autoPlayInterval = null;
            $('#autoSpinButton').removeClass('btn-danger').addClass('btn-primary').text('Otomatik');
        }

        // Sayfa yüklendiğinde
        $(document).ready(function() {
            // Bahis butonlarını etkinleştir
            $('.bet-button').on('click', function() {
                setBet(parseFloat($(this).data('amount')));
            });

            // Çevir butonunu etkinleştir
            $('#spinButton').on('click', spin);

            // Otomatik oynat butonunu etkinleştir
            $('#autoSpinButton').on('click', function() {
                if (autoPlayInterval) {
                    stopAutoPlay();
                } else {
                    startAutoPlay();
                }
            });

            // Bahis temizle butonunu etkinleştir
            $('#clearBetButton').on('click', clearBet);
        });
    </script>
</body>
</html>