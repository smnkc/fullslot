<?php
session_start();
require_once '../config/db.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim!']);
    exit();
}

// JSON verisini al
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['transaction_ids']) || empty($data['transaction_ids'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Silinecek işlem seçilmedi!']);
    exit();
}

try {
    // Veritabanı işlemini başlat
    $db->beginTransaction();

    // İşlemleri sil
    $placeholders = str_repeat('?,', count($data['transaction_ids']) - 1) . '?';
    $sql = "DELETE FROM transactions WHERE id IN ($placeholders)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($data['transaction_ids']);

    // İşlemi tamamla
    $db->commit();

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'İşlemler başarıyla silindi.']);
} catch (PDOException $e) {
    // Hata durumunda işlemi geri al
    $db->rollBack();
    
    error_log("İşlem silme hatası: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'İşlemler silinirken bir hata oluştu.']);
} 