<?php
require_once 'config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['review_id'])) {
    $reviewId = $data['review_id'];

    try {
        $stmt = $pdo->prepare("UPDATE reviews SET is_approved = 1 WHERE review_id = ?");
        $stmt->execute([$reviewId]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных']);
}
?>
