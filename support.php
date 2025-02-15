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

// Yeni destek talebi oluşturma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_ticket'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if (empty($subject) || empty($message)) {
        $_SESSION['error'] = "Lütfen tüm alanları doldurun!";
    } else {
        try {
            $db->beginTransaction();
            
            // Destek talebini oluştur
            $stmt = $db->prepare("INSERT INTO support_tickets (user_id, subject) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $subject]);
            $ticket_id = $db->lastInsertId();
            
            // İlk mesajı ekle
            $stmt = $db->prepare("INSERT INTO support_messages (ticket_id, user_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$ticket_id, $_SESSION['user_id'], $message]);
            
            $db->commit();
            $_SESSION['success'] = "Destek talebiniz başarıyla oluşturuldu!";
            header("Location: support.php");
            exit();
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Bir hata oluştu, lütfen tekrar deneyin!";
        }
    }
}

// Yeni mesaj gönderme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    $message = trim($_POST['message']);
    
    if (empty($message)) {
        $_SESSION['error'] = "Lütfen mesajınızı yazın!";
    } else {
        try {
            $db->beginTransaction();
            
            // Mesajı ekle
            $stmt = $db->prepare("INSERT INTO support_messages (ticket_id, user_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$ticket_id, $_SESSION['user_id'], $message]);
            
            // Talebin durumunu güncelle
            $stmt = $db->prepare("UPDATE support_tickets SET status = 'open' WHERE id = ?");
            $stmt->execute([$ticket_id]);
            
            $db->commit();
            $_SESSION['success'] = "Mesajınız başarıyla gönderildi!";
            header("Location: support.php?ticket=" . $ticket_id);
            exit();
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Bir hata oluştu, lütfen tekrar deneyin!";
        }
    }
}

// Destek taleplerini getir
$stmt = $db->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY updated_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll();

// Belirli bir talebin detaylarını görüntüleme
$selected_ticket = null;
$messages = [];
if (isset($_GET['ticket'])) {
    $ticket_id = (int)$_GET['ticket'];
    
    // Talebin kullanıcıya ait olduğunu kontrol et
    $stmt = $db->prepare("SELECT * FROM support_tickets WHERE id = ? AND user_id = ?");
    $stmt->execute([$ticket_id, $_SESSION['user_id']]);
    $selected_ticket = $stmt->fetch();
    
    if ($selected_ticket) {
        // Mesajları getir
        $stmt = $db->prepare("SELECT m.*, u.username, u.is_admin 
                             FROM support_messages m 
                             JOIN users u ON m.user_id = u.id 
                             WHERE m.ticket_id = ? 
                             ORDER BY m.created_at ASC");
        $stmt->execute([$ticket_id]);
        $messages = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .message {
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
        }
        .message.user {
            background-color: #f8f9fa;
            margin-right: 2rem;
        }
        .message.admin {
            background-color: #e9ecef;
            margin-left: 2rem;
        }
        .message-time {
            font-size: 0.8rem;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .message.user {
                margin-right: 1rem;
            }
            .message.admin {
                margin-left: 1rem;
            }
            .list-group-item {
                padding: 0.5rem;
            }
            .badge {
                font-size: 0.7rem;
            }
            .message-time {
                font-size: 0.7rem;
            }
        }

        @media (max-width: 576px) {
            .message.user, .message.admin {
                margin-left: 0;
                margin-right: 0;
            }
            .message {
                padding: 0.8rem;
                font-size: 0.9rem;
            }
            .list-group-item h6 {
                font-size: 0.9rem;
            }
            .card-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body class="bg-light">
    <?php require_once 'navbar.php'; ?>

    <div class="container mt-4">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Destek Talepleri Listesi -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Destek Talepleri</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                            <i class="bi bi-plus-lg"></i> Yeni Talep
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php if (empty($tickets)): ?>
                                <div class="list-group-item text-center text-muted">
                                    Henüz destek talebi bulunmuyor.
                                </div>
                            <?php else: ?>
                                <?php foreach ($tickets as $ticket): ?>
                                    <a href="?ticket=<?php echo $ticket['id']; ?>" class="list-group-item list-group-item-action <?php echo isset($_GET['ticket']) && $_GET['ticket'] == $ticket['id'] ? 'active' : ''; ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($ticket['subject']); ?></h6>
                                            <?php if ($ticket['status'] === 'answered'): ?>
                                                <span class="badge bg-success">Yanıtlandı</span>
                                            <?php elseif ($ticket['status'] === 'open'): ?>
                                                <span class="badge bg-warning">Açık</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Kapalı</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('d.m.Y H:i', strtotime($ticket['updated_at'])); ?>
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mesajlar -->
            <div class="col-md-8">
                <?php if ($selected_ticket): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($selected_ticket['subject']); ?></h5>
                        </div>
                        <div class="card-body">
                            <!-- Mesajlar -->
                            <div class="messages mb-4">
                                <?php foreach ($messages as $message): ?>
                                    <div class="message <?php echo $message['is_admin'] ? 'admin' : 'user'; ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong><?php echo htmlspecialchars($message['username']); ?></strong>
                                            <span class="message-time">
                                                <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>
                                            </span>
                                        </div>
                                        <div class="message-content">
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Yanıt Formu -->
                            <?php if ($selected_ticket['status'] !== 'closed'): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="ticket_id" value="<?php echo $selected_ticket['id']; ?>">
                                    <div class="mb-3">
                                        <label for="message" class="form-label">Yanıtınız</label>
                                        <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" name="reply" class="btn btn-primary">Yanıt Gönder</button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">
                                    Bu destek talebi kapatılmıştır.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center">
                            <p class="mb-0">Görüntülemek için bir destek talebi seçin veya yeni bir tane oluşturun.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Yeni Talep Modalı -->
    <div class="modal fade" id="newTicketModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Destek Talebi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="subject" class="form-label">Konu</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_message" class="form-label">Mesajınız</label>
                            <textarea class="form-control" id="new_message" name="message" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="new_ticket" class="btn btn-primary">Gönder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 