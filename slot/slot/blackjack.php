<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Kullanıcı bilgilerini al
$stmt = $db->prepare("SELECT username, credits FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blackjack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .game-table {
            background: #2c7744;
            padding: 30px;
            border-radius: 15px;
            margin: 20px 0;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }
        .dealer-hand, .player-hand {
            min-height: 150px;
            padding: 20px;
            border-radius: 10px;
            background: rgba(255,255,255,0.1);
            margin: 20px 0;
        }
        .hand-label {
            color: white;
            font-size: 24px;
            margin-bottom: 15px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }
        .playing-card {
            width: 100px;
            height: 140px;
            background: white;
            border-radius: 10px;
            display: inline-flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            position: relative;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            margin: 5px;
            transition: transform 0.3s ease;
        }
        .playing-card.hidden {
            background: #1a4b2d;
            color: white;
        }
        .playing-card:hover {
            transform: translateY(-5px);
        }
        .card-value {
            position: absolute;
            top: 5px;
            left: 5px;
            font-size: 24px;
        }
        .card-suit {
            font-size: 48px;
        }
        .bet-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .bet-info {
            font-size: 24px;
            padding: 10px 30px;
            background: #1a4b2d;
            color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .game-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
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
        .hand-total {
            color: white;
            font-size: 20px;
            margin-top: 10px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }
        @media (max-width: 768px) {
            .playing-card {
                width: 80px;
                height: 112px;
                font-size: 18px;
            }
            .card-value {
                font-size: 18px;
            }
            .card-suit {
                font-size: 36px;
            }
            .bet-controls {
                gap: 10px;
            }
            .bet-info {
                font-size: 20px;
                padding: 8px 20px;
            }
            .btn-lg {
                padding: 10px 20px;
                font-size: 16px;
            }
            .hand-label {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div id="messageBox" class="alert message"></div>

        <div class="card">
            <div class="card-body">
                <div class="game-table">
                    <div class="dealer-hand">
                        <h3 class="hand-label">Kurpiyer</h3>
                        <div id="dealerHand"></div>
                        <p class="hand-total">Toplam: <span id="dealerTotal">0</span></p>
                    </div>

                    <div class="player-hand">
                        <h3 class="hand-label">Oyuncu</h3>
                        <div id="playerHand"></div>
                        <p class="hand-total">Toplam: <span id="playerTotal">0</span></p>
                        
                        <div class="bet-controls">
                            <button type="button" class="btn btn-secondary bet-button" data-amount="10">+10</button>
                            <button type="button" class="btn btn-secondary bet-button" data-amount="20">+20</button>
                            <button type="button" class="btn btn-secondary bet-button" data-amount="50">+50</button>
                            <button type="button" class="btn btn-secondary bet-button" data-amount="100">+100</button>
                            <div class="bet-info">Bahis: <span id="betAmount">0</span></div>
                            <button type="button" class="btn btn-danger" id="clearBetButton">Temizle</button>
                        </div>

                        <div class="game-buttons">
                            <button id="dealButton" class="btn btn-success btn-lg">
                                <i class="bi bi-play-circle"></i> Dağıt
                            </button>
                            <button id="hitButton" class="btn btn-warning btn-lg" disabled>
                                <i class="bi bi-plus-circle"></i> Kart Çek
                            </button>
                            <button id="standButton" class="btn btn-info btn-lg" disabled>
                                <i class="bi bi-hand-thumbs-up"></i> Kal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Blackjack Kuralları</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Temel Kurallar</h6>
                        <ul>
                            <li>Oyunun amacı 21'e en yakın toplamı elde etmektir</li>
                            <li>As 1 veya 11 olarak sayılabilir</li>
                            <li>Vale, Kız ve Papaz 10 sayılır</li>
                            <li>21'i geçerseniz kaybedersiniz</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Ödemeler</h6>
                        <ul>
                            <li>Blackjack (As + 10 değerli kart) = 3:2</li>
                            <li>Normal kazanç = 1:1</li>
                            <li>Beraberlik = Bahis iade</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let deck = [];
        let dealerHand = [];
        let playerHand = [];
        let gameState = 'betting'; // betting, playing, ended
        let currentBet = 0;
        let currentBalance = 0;

        // Sayfa yüklendiğinde bakiyeyi al
        document.addEventListener('DOMContentLoaded', function() {
            const balanceText = document.getElementById('balanceAmount').textContent.trim();
            console.log('Başlangıç bakiye text:', balanceText);
            currentBalance = parseFloat(balanceText.replace(/\./g, '').replace(',', '.'));
            console.log('Başlangıç bakiye:', currentBalance);
        });

        // Desteyi oluştur
        function createDeck() {
            const suits = ['♠', '♣', '♥', '♦'];
            const values = Array.from({length: 13}, (_, i) => i + 1);
            deck = [];
            
            for (let suit of suits) {
                for (let value of values) {
                    deck.push({suit, value});
                }
            }
            
            shuffleDeck();
        }

        // Desteyi karıştır
        function shuffleDeck() {
            for (let i = deck.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [deck[i], deck[j]] = [deck[j], deck[i]];
            }
        }

        // Kart çek
        function drawCard() {
            if (deck.length === 0) {
                createDeck();
            }
            return deck.pop();
        }

        // El değerini hesapla
        function calculateHandValue(hand) {
            let value = 0;
            let aces = 0;
            
            for (let card of hand) {
                if (card.value === 1) {
                    aces++;
                } else {
                    value += Math.min(10, card.value);
                }
            }
            
            for (let i = 0; i < aces; i++) {
                if (value + 11 <= 21) {
                    value += 11;
                } else {
                    value += 1;
                }
            }
            
            return value;
        }

        // Kart görüntüsü oluştur
        function createCardElement(card, hidden = false) {
            const cardDiv = document.createElement('div');
            cardDiv.className = 'playing-card';
            
            if (hidden) {
                cardDiv.innerHTML = '🂠';
                cardDiv.classList.add('hidden');
            } else {
                const values = {
                    '1': 'A',
                    '11': 'J',
                    '12': 'Q',
                    '13': 'K'
                };
                
                const displayValue = values[card.value] || card.value;
                const color = ['♥', '♦'].includes(card.suit) ? 'red' : 'black';
                cardDiv.style.color = color;
                cardDiv.innerHTML = `
                    <div class="card-value">${displayValue}</div>
                    <div class="card-suit">${card.suit}</div>
                `;
            }
            
            return cardDiv;
        }

        // Arayüzü güncelle
        function updateUI() {
            const dealerHandDiv = document.getElementById('dealerHand');
            const playerHandDiv = document.getElementById('playerHand');
            
            dealerHandDiv.innerHTML = '';
            playerHandDiv.innerHTML = '';
            
            dealerHand.forEach((card, index) => {
                const hidden = index === 1 && gameState === 'playing';
                dealerHandDiv.appendChild(createCardElement(card, hidden));
            });
            
            playerHand.forEach(card => {
                playerHandDiv.appendChild(createCardElement(card));
            });
            
            document.getElementById('dealerTotal').textContent = 
                gameState === 'playing' ? calculateHandValue([dealerHand[0]]) : calculateHandValue(dealerHand);
            document.getElementById('playerTotal').textContent = calculateHandValue(playerHand);
            
            document.getElementById('hitButton').disabled = gameState !== 'playing';
            document.getElementById('standButton').disabled = gameState !== 'playing';
            document.getElementById('dealButton').disabled = gameState === 'playing';
        }

        // Bakiye güncelle
        function updateBalance(newBalance) {
            console.log('Yeni bakiye geliyor:', newBalance);
            if (typeof newBalance === 'number') {
                currentBalance = newBalance;
                const formatted = newBalance.toLocaleString('tr-TR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).replace('.', ',');
                document.getElementById('balanceAmount').textContent = formatted;
                console.log('Bakiye güncellendi:', currentBalance);
            }
        }

        // Bahis ekle
        function addBet(amount) {
            console.log('Mevcut bakiye:', currentBalance);
            console.log('Eklenecek bahis:', amount);
            
            if (gameState !== 'betting') return;
            
            const newBet = currentBet + amount;
            console.log('Yeni bahis olacak:', newBet);
            
            if (newBet > currentBalance) {
                showMessage('Yetersiz bakiye!', true);
                return;
            }
            
            currentBet = newBet;
            document.getElementById('betAmount').textContent = currentBet;
            console.log('Bahis güncellendi:', currentBet);
        }

        // Bahisi temizle
        function clearBet() {
            if (gameState !== 'betting') return;
            currentBet = 0;
            document.getElementById('betAmount').textContent = '0';
        }

        // Oyunu başlat
        function startGame() {
            if (currentBet <= 0) {
                showMessage('Lütfen bahis yapın!', true);
                return;
            }

            if (currentBet > currentBalance) {
                showMessage('Yetersiz bakiye!', true);
                return;
            }

            if (gameState !== 'betting') {
                return;
            }

            const formData = new FormData();
            formData.append('bet', currentBet);

            fetch('blackjack_bet.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.status === 'error') {
                    showMessage(data.message, true);
                    return;
                }

                if (data.balance !== undefined) {
                    console.log('Yeni bakiye:', data.balance);
                    updateBalance(parseFloat(data.balance));
                }

                gameState = 'playing';
                createDeck();
                dealerHand = [drawCard(), drawCard()];
                playerHand = [drawCard(), drawCard()];
                updateUI();

                if (calculateHandValue(playerHand) === 21) {
                    if (calculateHandValue(dealerHand) === 21) {
                        endGame('push');
                    } else {
                        endGame('blackjack');
                    }
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                showMessage('Bir hata oluştu!', true);
            });
        }

        // Kart çek
        function hit() {
            if (gameState !== 'playing') return;
            
            playerHand.push(drawCard());
            updateUI();
            
            const handValue = calculateHandValue(playerHand);
            if (handValue > 21) {
                endGame('bust');
            } else if (handValue === 21) {
                stand();
            }
        }

        // Kal
        function stand() {
            if (gameState !== 'playing') return;
            
            while (calculateHandValue(dealerHand) < 17) {
                dealerHand.push(drawCard());
            }
            
            const playerValue = calculateHandValue(playerHand);
            const dealerValue = calculateHandValue(dealerHand);
            
            let result;
            if (dealerValue > 21) {
                result = 'player';
            } else if (playerValue > dealerValue) {
                result = 'player';
            } else if (playerValue < dealerValue) {
                result = 'dealer';
            } else {
                result = 'push';
            }
            
            endGame(result);
        }

        // Oyunu bitir
        function endGame(result) {
            gameState = 'ended';
            updateUI();
            
            let message = '';
            let win = 0;
            
            switch (result) {
                case 'blackjack':
                    message = 'Blackjack! Kazandınız!';
                    win = currentBet * 2.5;
                    break;
                case 'player':
                    message = 'Kazandınız!';
                    win = currentBet * 2;
                    break;
                case 'dealer':
                    message = 'Kaybettiniz!';
                    break;
                case 'bust':
                    message = '21\'i geçtiniz! Kaybettiniz!';
                    break;
                case 'push':
                    message = 'Berabere!';
                    win = currentBet;
                    break;
            }
            
            if (win > 0) {
                const formData = new FormData();
                formData.append('win', win);
                
                fetch('blackjack_win.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        showMessage(message);
                        if (data.balance !== undefined) {
                            console.log('Kazanç sonrası bakiye:', data.balance);
                            updateBalance(parseFloat(data.balance));
                        }
                    } else {
                        showMessage('Hata: ' + data.message, true);
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    showMessage('Bir hata oluştu!', true);
                });
            } else {
                showMessage(message);
            }
            
            setTimeout(() => {
                gameState = 'betting';
                dealerHand = [];
                playerHand = [];
                currentBet = 0;
                updateUI();
                document.getElementById('betAmount').textContent = '0';
            }, 2000);
        }

        // Mesaj göster
        function showMessage(message, isError = false) {
            const messageDiv = document.getElementById('messageBox');
            messageDiv.textContent = message;
            messageDiv.style.display = 'block';
            messageDiv.classList.toggle('alert-danger', isError);
            messageDiv.classList.toggle('alert-success', !isError);
            setTimeout(() => messageDiv.style.display = 'none', 3000);
        }

        // Event listeners
        $(document).ready(function() {
            $('.bet-button').click(function() {
                addBet(parseInt($(this).data('amount')));
            });

            $('#clearBetButton').click(function() {
                clearBet();
            });

            $('#dealButton').click(function() {
                startGame();
            });

            $('#hitButton').click(function() {
                hit();
            });

            $('#standButton').click(function() {
                stand();
            });
        });
    </script>
</body>
</html>