<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Вы не авторизованы");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $bookingId = $_POST['booking_id'];
    $userId = $_SESSION['user_id'];

    // Проверяем, что бронирование принадлежит пользователю
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ? AND user_id = ?");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        die("Бронирование не найдено");
    }

    // Проверяем, что до заезда осталось больше 24 часов
    $checkInDate = new DateTime($booking['check_in']);
    $currentDate = new DateTime();
    $interval = $checkInDate->diff($currentDate);
    $hoursUntilCheckIn = $interval->days * 24 + $interval->h;

    if ($hoursUntilCheckIn < 24) {
        die("Отмена бронирования возможна только за 24 часа до заезда");
    }

    // Отменяем бронирование
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
    $stmt->execute([$bookingId]);

    echo "Бронирование успешно отменено";
}
?>
