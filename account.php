<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);

    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE user_id = ?");
    $stmt->execute([$firstName, $lastName, $phone, $_SESSION['user_id']]);

    
    header("Location: account.php");
    exit();
}


$stmt = $pdo->prepare("
    SELECT b.*, r.room_number, tr.name AS room_type
    FROM bookings b
    JOIN rooms r ON b.room_id = r.room_id
    JOIN type_rooms tr ON r.id_type = tr.id_type
    WHERE b.user_id = ?
    ORDER BY b.check_in DESC
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет | HAMSTER</title>
    <link rel="stylesheet" href="\css\booking.css">
    <style>
        .hidden-data {
            display: none;
        }

        .user-details p:hover .hidden-data {
            display: inline;
        }
    </style>
</head>
<body>
    <div class="site-brow"></div>
    <nav class="top-nav">
        <div class="nav-container">
            <div class="nav-logo">HOTELYAR</div>
            <ul class="nav-menu">
                <li><a href="about.php" class="nav-link">ОБ ОТЕЛЕ</a></li>
                <li><a href="rooms.php" class="nav-link">НОМЕРА</a></li>
                <li><a href="restaurant.php" class="nav-link">РЕСТОРАН</a></li>
                <li><a href="conference.php" class="nav-link">КОНФЕРЕНЦ-ЗАЛЫ</a></li>
                <li><a href="gallery.php" class="nav-link">ФОТОГАЛЕРЕЯ</a></li>
                <li><a href="contacts.php" class="nav-link">КОНТАКТЫ</a></li>
                <li><a href="booking.php" class="nav-link">БРОНИРОВАНИЕ</a></li>
                <li id="user-menu-item">
                    <a href="#" class="nav-link"><?= htmlspecialchars($_SESSION['email']) ?></a>
                    <ul class="submenu">
                        <li><a href="account.php">Личный кабинет</a></li>
                        <li><a href="logout.php">Выйти</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <section class="section">
        <h2>Личный кабинет</h2>

        <div id="account-info">
            <h3>Личные данные</h3>
            <div class="user-details">
                <p><strong>Имя:</strong> <span class="hidden-data"><?= htmlspecialchars($user['first_name']) ?></span></p>
                <p><strong>Фамилия:</strong> <span class="hidden-data"><?= htmlspecialchars($user['last_name']) ?></span></p>
                <p><strong>Email:</strong> <span class="hidden-data"><?= htmlspecialchars($user['email']) ?></span></p>
                <p><strong>Телефон:</strong> <span class="hidden-data"><?= htmlspecialchars($user['phone']) ?></span></p>
            </div>

            <h3>Изменить личные данные</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="first_name">Имя</label>
                    <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Фамилия</label>
                    <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
                </div>
                <button type="submit">Сохранить изменения</button>
            </form>

            <h3>Мои бронирования</h3>
            <div id="bookings-list">
                <?php if (empty($bookings)): ?>
                    <p>У вас пока нет активных бронирований.</p>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                    <div class="booking-item">
                        <h4>Номер: <?= htmlspecialchars($booking['room_type']) ?> (№<?= $booking['room_number'] ?>)</h4>
                        <p><strong>Даты:</strong> <?= date('d.m.Y', strtotime($booking['check_in'])) ?> - <?= date('d.m.Y', strtotime($booking['check_out'])) ?></p>
                        <p><strong>Гостей:</strong> <?= $booking['guests'] ?></p>
                        <p><strong>Стоимость:</strong> <?= number_format($booking['total_price'], 2, ',', ' ') ?> ₽</p>
                        <p><strong>Статус:</strong>
                            <span class="booking-status status-<?= $booking['status'] ?>">
                                <?= $booking['status'] === 'confirmed' ? 'Подтверждено' :
                                    ($booking['status'] === 'cancelled' ? 'Отменено' : 'Ожидание') ?>
                            </span>
                        </p>
                        <?php
                        $checkInDate = new DateTime($booking['check_in']);
                        $currentDate = new DateTime();
                        $interval = $checkInDate->diff($currentDate);
                        $hoursUntilCheckIn = $interval->days * 24 + $interval->h;
                        if (($booking['status'] === 'confirmed' || $booking['status'] === 'pending') && $hoursUntilCheckIn >= 24):
                        ?>
                            <button class="cancel-booking" data-booking-id="<?= $booking['booking_id'] ?>">Отменить бронирование</button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer>
        <p> HOTELYAR <br>
            2025, Официальный сайт<br>
             г. Ярославль, ул.Колесова д.999</p>

        <p> Модуль онлайн-бронирования </p>
    </footer>

    <script>
    document.querySelectorAll('.cancel-booking').forEach(button => {
        button.addEventListener('click', function() {
            const bookingId = this.getAttribute('data-booking-id');

            if (confirm('Вы уверены, что хотите отменить бронирование?')) {
                fetch('cancel_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `booking_id=${bookingId}`
                })
                .then(response => response.text())
                .then(data => {
                    alert(data);
                    window.location.reload();
                })
                .catch(error => console.error('Error:', error));
            }
        });
    });
    </script>
</body>
</html>
