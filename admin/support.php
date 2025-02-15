<?php
session_start();
require_once '../config/db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit();
}

// Yanıt gönderme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    $message = trim($_POST['message']);
    
    if (empty($message)) {
        $_SESSION['error'] = "Lütfen mesajınızı yazın!";
    } else {
        try {
            $db->beginTransaction();
            
            // Mesajı ekle
            $stmt = $db->prepare("INSERT INTO support_messages (ticket_id, user_id, message, is_admin) VALUES (?, ?, ?, 1)");
            $stmt->execute([$ticket_id, $_SESSION['user_id'], $message]);
            
            // Talebin durumunu güncelle
            $stmt = $db->prepare("UPDATE support_tickets SET status = 'answered' WHERE id = ?");
            $stmt->execute([$ticket_id]);
            
            $db->commit();
            $_SESSION['success'] = "Yanıtınız başarıyla gönderildi!";
            header("Location: support.php?ticket=" . $ticket_id);
            exit();
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Bir hata oluştu, lütfen tekrar deneyin!";
        }
    }
}

// Talep kapatma/açma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    $new_status = $_POST['new_status'];
    
    try {
        $stmt = $db->prepare("UPDATE support_tickets SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $ticket_id]);
        
        $_SESSION['success'] = "Talep durumu güncellendi!";
        header("Location: support.php?ticket=" . $ticket_id);
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Bir hata oluştu, lütfen tekrar deneyin!";
    }
}

// Toplu silme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_tickets'])) {
    $ticket_ids = $_POST['ticket_ids'] ?? [];
    
    if (empty($ticket_ids)) {
        $_SESSION['error'] = "Lütfen silinecek talepleri seçin!";
    } else {
        try {
            $db->beginTransaction();
            
            $placeholders = str_repeat('?,', count($ticket_ids) - 1) . '?';
            
            // Önce mesajları sil
            $stmt = $db->prepare("DELETE FROM support_messages WHERE ticket_id IN ($placeholders)");
            $stmt->execute($ticket_ids);
            
            // Sonra talepleri sil
            $stmt = $db->prepare("DELETE FROM support_tickets WHERE id IN ($placeholders)");
            $stmt->execute($ticket_ids);
            
            $db->commit();
            $_SESSION['success'] = "Seçili talepler başarıyla silindi!";
            header("Location: support.php");
            exit();
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Silme işlemi sırasında bir hata oluştu!";
        }
    }
}

// Destek taleplerini getir
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$sql = "SELECT t.*, u.username, 
        (SELECT COUNT(*) FROM support_messages WHERE ticket_id = t.id) as message_count,
        (SELECT MAX(created_at) FROM support_messages WHERE ticket_id = t.id) as last_message
        FROM support_tickets t 
        JOIN users u ON t.user_id = u.id";

if ($status_filter !== 'all') {
    $sql .= " WHERE t.status = ?";
}

$sql .= " ORDER BY t.updated_at DESC";

$stmt = $db->prepare($sql);
if ($status_filter !== 'all') {
    $stmt->execute([$status_filter]);
} else {
    $stmt->execute();
}
$tickets = $stmt->fetchAll();

// Belirli bir talebin detaylarını görüntüleme
$selected_ticket = null;
$messages = [];
if (isset($_GET['ticket'])) {
    $ticket_id = (int)$_GET['ticket'];
    
    $stmt = $db->prepare("SELECT t.*, u.username 
                         FROM support_tickets t 
                         JOIN users u ON t.user_id = u.id 
                         WHERE t.id = ?");
    $stmt->execute([$ticket_id]);
    $selected_ticket = $stmt->fetch();
    
    if ($selected_ticket) {
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
    <title>Destek Yönetimi - Admin Panel</title>
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
    </style>
</head>
<body class="bg-light">
    <?php require_once 'admin_navbar.php'; ?>

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
                    <div class="card-header">
                        <h5 class="card-title mb-0">Destek Talepleri</h5>
                    </div>
                    <div class="card-body p-0">
                        <!-- Filtreler -->
                        <div class="p-3 border-bottom">
                            <form method="GET" class="mb-3">
                                <select class="form-select" name="status" onchange="this.form.submit()">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Tümü</option>
                                    <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>Açık</option>
                                    <option value="answered" <?php echo $status_filter === 'answered' ? 'selected' : ''; ?>>Yanıtlandı</option>
                                    <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Kapalı</option>
                                </select>
                            </form>
                        </div>

                        <!-- Talep Listesi ve Silme Formu -->
                        <form method="POST" id="deleteForm" onsubmit="return confirmDelete()">
                            <div class="p-3 border-bottom">
                                <div class="mb-2">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        <label class="form-check-label" for="selectAll">
                                            Tümünü Seç
                                        </label>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="delete_tickets" class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i> Seçili Talepleri Sil
                                    </button>
                                </div>
                            </div>

                            <div class="list-group list-group-flush">
                                <?php if (empty($tickets)): ?>
                                    <div class="list-group-item text-center text-muted">
                                        Destek talebi bulunmuyor.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="ticket_ids[]" value="<?php echo $ticket['id']; ?>">
                                                </div>
                                                <a href="?ticket=<?php echo $ticket['id']; ?>&status=<?php echo $status_filter; ?>" 
                                                   class="text-decoration-none text-dark flex-grow-1 ms-2">
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
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small><?php echo htmlspecialchars($ticket['username']); ?></small>
                                                        <small class="text-muted">
                                                            <?php echo date('d.m.Y H:i', strtotime($ticket['updated_at'])); ?>
                                                        </small>
                                                    </div>
                                                    <small class="text-muted">
                                                        Mesaj: <?php echo $ticket['message_count']; ?>
                                                    </small>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Mesajlar -->
            <div class="col-md-8">
                <?php if ($selected_ticket): ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <?php echo htmlspecialchars($selected_ticket['subject']); ?>
                                <small class="text-muted ms-2">
                                    (<?php echo htmlspecialchars($selected_ticket['username']); ?>)
                                </small>
                            </h5>
                            <?php if ($selected_ticket['status'] !== 'closed'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="ticket_id" value="<?php echo $selected_ticket['id']; ?>">
                                    <input type="hidden" name="new_status" value="closed">
                                    <button type="submit" name="toggle_status" class="btn btn-danger btn-sm">
                                        Talebi Kapat
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="ticket_id" value="<?php echo $selected_ticket['id']; ?>">
                                    <input type="hidden" name="new_status" value="open">
                                    <button type="submit" name="toggle_status" class="btn btn-success btn-sm">
                                        Talebi Aç
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <!-- Mesajlar -->
                            <div class="messages mb-4">
                                <?php foreach ($messages as $message): ?>
                                    <div class="message <?php echo $message['is_admin'] ? 'admin' : 'user'; ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>
                                                <?php echo htmlspecialchars($message['username']); ?>
                                                <?php echo $message['is_admin'] ? ' (Admin)' : ''; ?>
                                            </strong>
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
                            <p class="mb-0">Görüntülemek için bir destek talebi seçin.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('#deleteForm input[name="ticket_ids[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
    }

    function confirmDelete() {
        const checkboxes = document.querySelectorAll('#deleteForm input[name="ticket_ids[]"]:checked');
        
        if (checkboxes.length === 0) {
            alert('Lütfen silinecek talepleri seçin!');
            return false;
        }
        
        let message = `${checkboxes.length} adet seçili talep silinecek.\nBu işlem geri alınamaz! Onaylıyor musunuz?`;
        return confirm(message);
    }
    </script>
</body>
</html> 