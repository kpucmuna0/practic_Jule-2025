<?php
require_once 'auth_functions.php';
require_once 'config.php';
session_start();

// Проверка авторизации и прав администратора
requireAdmin();

// Получение данных текущего пользователя
$current_user = getCurrentUserWithRole();

// Подключение к базе данных через $pdo из config.php
try {
    $roles = $pdo->query("SELECT * FROM role")->fetchAll();
    // Получение статистики
    $stats = [
        'new_bookings' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
        'pending_payments' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
        'reviews_pending' => $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn(),
        'occupancy_rate' => $pdo->query("SELECT ROUND(COUNT(*) / (SELECT COUNT(*) FROM rooms) * 100) FROM bookings WHERE check_in <= CURDATE() AND check_out >= CURDATE()")->fetchColumn()
    ];

    // Получение последних бронирований
    $bookings = $pdo->query("
        SELECT b.*, u.first_name, u.last_name, r.room_number, rt.name 
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN rooms r ON b.room_id = r.room_id
        JOIN type_rooms rt ON r.id_type = rt.id_type
        ORDER BY b.created_at DESC
        LIMIT 10
    ")->fetchAll();

    // Получение последних отзывов
    $reviews = $pdo->query("
        SELECT r.*, u.first_name, u.last_name 
        FROM reviews r
        JOIN users u ON r.user_id = u.user_id
        ORDER BY r.created_at DESC
        LIMIT 10
    ")->fetchAll();

    // Получение всех номеров
    $rooms = $pdo->query("
        SELECT r.*, rt.name 
        FROM rooms r
        JOIN type_rooms rt ON r.id_type = rt.id_type
    ")->fetchAll();

    // Получение всех пользователей
    $users = $pdo->query("
        SELECT u.*, r.role_name 
        FROM users u
        JOIN user_roles ur ON u.user_id = ur.user_id
        JOIN role r ON ur.role_id = r.id_role
    ")->fetchAll();

    // Получение типов номеров
    $room_types = $pdo->query("SELECT * FROM type_rooms")->fetchAll();

} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

// Функция для форматирования даты
function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | HAMSTER</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .wrapper {
            display: flex;
            min-height: 100vh;
        }
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: #343a40;
            color: #fff;
            transition: all 0.3s;
        }
        #sidebar .sidebar-header {
            padding: 20px;
            background: #212529;
            text-align: center;
        }
        #sidebar ul.components {
            padding: 20px 0;
        }
        #sidebar ul li a {
            padding: 10px 20px;
            display: block;
            color: #adb5bd;
            text-decoration: none;
        }
        #sidebar ul li a:hover {
            color: #fff;
            background: #495057;
        }
        #sidebar ul li.active > a {
            color: #fff;
            background: #007bff;
        }
        .main-content {
            width: 100%;
            overflow-x: hidden;
        }
        .stat-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        .room-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .content-section {
            display: none;
        }
        .content-section.active {
            display: block;
        }
        .badge {
            font-weight: normal;
        }
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .nav-logo {
            font-weight: bold;
            font-size: 1.5rem;
            color: #343a40;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Боковое меню -->
        <aside id="sidebar">
            <div class="sidebar-header">
                <h3>HAMSTER Админ</h3>
            </div>
            
            <ul class="sidebar-nav">
                <li class="active">
                    <a href="#dashboard" data-section="dashboard">
                        <i class="fas fa-home"></i> <span>Главная</span>
                    </a>
                </li>
                <li>
                    <a href="#bookings" data-section="bookings">
                        <i class="fas fa-calendar-check"></i> <span>Бронирования</span>
                    </a>
                </li>
                <li>
                    <a href="#rooms" data-section="rooms">
                        <i class="fas fa-bed"></i> <span>Номера</span>
                    </a>
                </li>
                <li>
                    <a href="#reviews" data-section="reviews">
                        <i class="fas fa-star"></i> <span>Отзывы</span>
                    </a>
                </li>
                <li>
                    <a href="#users" data-section="users">
                        <i class="fas fa-users"></i> <span>Пользователи</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Основной контент -->
        <div class="main-content">
            <!-- Верхняя навигация -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button class="btn btn-sidebar" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="d-flex align-items-center">
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i> 
                                <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?>

                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/account.php"><i class="fas fa-user me-2"></i>Профиль</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Выйти</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Контент страницы -->
            <div class="content p-4">
                <!-- Dashboard -->
                <section id="dashboard" class="content-section active">
                    <h2 class="mb-4">Главная панель</h2>
                    
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="stat-title">Новые бронирования</h6>
                                            <h3 class="stat-value"><?php echo $stats['new_bookings']; ?></h3>
                                        </div>
                                        <div class="stat-icon bg-primary">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="stat-title">Неоплаченные</h6>
                                            <h3 class="stat-value"><?php echo $stats['pending_payments']; ?></h3>
                                        </div>
                                        <div class="stat-icon bg-warning">
                                            <i class="fas fa-exclamation-circle"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="stat-title">Отзывы</h6>
                                            <h3 class="stat-value"><?php echo $stats['reviews_pending']; ?></h3>
                                        </div>
                                        <div class="stat-icon bg-info">
                                            <i class="fas fa-star"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="stat-title">Загрузка отеля</h6>
                                            <h3 class="stat-value"><?php echo $stats['occupancy_rate']; ?>%</h3>
                                        </div>
                                        <div class="stat-icon bg-success">
                                            <i class="fas fa-chart-line"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Последние бронирования</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Гость</th>
                                                    <th>Номер</th>
                                                    <th>Даты</th>
                                                    <th>Сумма</th>
                                                    <th>Статус</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($bookings, 0, 5) as $booking): ?>
                                                <tr>
                                                    <td>#<?php echo $booking['booking_id']; ?></td>
                                                    <td><?php echo htmlspecialchars($booking['first_name'] . ' ' . htmlspecialchars($booking['last_name'])); ?></td>
                                                    <td><?php echo htmlspecialchars($booking['name']); ?></td>
                                                    <td><?php echo formatDate($booking['check_in']) . ' - ' . formatDate($booking['check_out']); ?></td>
                                                    <td><?php echo number_format($booking['total_price'], 2); ?> руб.</td>
                                                    <td>
                                                        <span class="badge <?php 
                                                            echo $booking['status'] === 'confirmed' ? 'bg-success' : 
                                                                ($booking['status'] === 'cancelled' ? 'bg-danger' : 'bg-warning'); 
                                                        ?>">
                                                            <?php 
                                                                echo $booking['status'] === 'confirmed' ? 'Подтверждено' : 
                                                                    ($booking['status'] === 'cancelled' ? 'Отменено' : 'Ожидает'); 
                                                            ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Последние отзывы</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach (array_slice($reviews, 0, 3) as $review): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <strong><?php echo htmlspecialchars($review['first_name'] . ' ' . htmlspecialchars($review['last_name'])); ?></strong>
                                            <div>
                                                <?php for ($i = 0; $i < 5; $i++): ?>
                                                    <i class="fas fa-star<?php echo $i < $review['rating'] ? '' : '-empty'; ?> text-warning"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars(substr($review['comment'], 0, 50)); ?>...</p>
                                        <small class="text-muted"><?php echo formatDate($review['created_at']); ?></small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Бронирования -->
                <section id="bookings" class="content-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Бронирования</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookingModal">
                            <i class="fas fa-plus me-1"></i> Добавить бронирование
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Гость</th>
                                    <th>Номер</th>
                                    <th>Даты</th>
                                    <th>Гости</th>
                                    <th>Сумма</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['booking_id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['first_name'] . ' ' . htmlspecialchars($booking['last_name'])); ?></td>
                                    <td><?php echo htmlspecialchars($booking['name']); ?></td>
                                    <td><?php echo formatDate($booking['check_in']) . ' - ' . formatDate($booking['check_out']); ?></td>
                                    <td><?php echo $booking['guests']; ?></td>
                                    <td><?php echo number_format($booking['total_price'], 2); ?> руб.</td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $booking['status'] === 'confirmed' ? 'bg-success' : 
                                                ($booking['status'] === 'cancelled' ? 'bg-danger' : 'bg-warning'); 
                                        ?>">
                                            <?php 
                                                echo $booking['status'] === 'confirmed' ? 'Подтверждено' : 
                                                    ($booking['status'] === 'cancelled' ? 'Отменено' : 'Ожидает'); 
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-success me-1 confirm-booking" data-booking-id="<?= $booking['booking_id'] ?>" title="Подтвердить"><i class="fas fa-check"></i></button>
                                        <button class="btn btn-sm btn-primary me-1 edit-booking" data-booking-id="<?= $booking['booking_id'] ?>" title="Редактировать"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-danger cancel-booking" data-booking-id="<?= $booking['booking_id'] ?>" title="Отменить"><i class="fas fa-times"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Номера -->
                <section id="rooms" class="content-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Управление номерами</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                            <i class="fas fa-plus me-1"></i> Добавить номер
                        </button>
                    </div>
                    <div class="row">
                        <?php foreach ($rooms as $room): ?>  
                        <div class="col-md-4 mb-4">
                            <div class="card room-card" data-room-id="<?= $room['room_id'] ?>">
                                <div class="room-img-container">
                                    <img src="https://via.placeholder.com/300x200" class="card-img-top" alt="Номер">
                                    <span class="badge <?php echo $room['is_available'] ? 'bg-success' : 'bg-danger'; ?> position-absolute top-0 end-0 m-2">
                                        <?php echo $room['is_available'] ? 'Доступен' : 'Занят'; ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h5>
                                    <p class="card-text">
                                        <strong>Номер:</strong> <?php echo htmlspecialchars($room['room_number']); ?><br>
                                        <strong>Вместимость:</strong> <?php echo $room['capacity']; ?> гостей
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="text-primary fw-bold"><?php echo number_format($room['price'], 2); ?> руб.</span> / ночь
                                        </div>
                                        <div>
                                            <button class="btn btn-sm btn-outline-primary me-1 edit-room" data-room-id="<?= $room['room_id'] ?>"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-outline-danger delete-room" data-room-id="<?= $room['room_id'] ?>"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Отзывы -->
                <section id="reviews" class="content-section">
                    <h2 class="mb-4">Отзывы</h2>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Пользователь</th>
                                    <th>Рейтинг</th>
                                    <th>Комментарий</th>
                                    <th>Дата</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reviews as $review): ?>
                                <tr>
                                    <td><?php echo $review['review_id']; ?></td>
                                    <td><?php echo htmlspecialchars($review['first_name'] . ' ' . htmlspecialchars($review['last_name'])); ?></td>
                                    <td>
                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i < $review['rating'] ? '' : '-empty'; ?> text-warning"></i>
                                        <?php endfor; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($review['comment'], 0, 50)); ?>...</td>
                                    <td><?php echo formatDate($review['created_at']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-success me-1 approve-review" data-review-id="<?= $review['review_id'] ?>" title="Одобрить"><i class="fas fa-check"></i></button>
                                        <button class="btn btn-sm btn-danger delete-review" data-review-id="<?= $review['review_id'] ?>" title="Удалить"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Пользователи -->
                <section id="users" class="content-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Пользователи</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus me-1"></i> Добавить пользователя
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Имя</th>
                                    <th>Email</th>
                                    <th>Телефон</th>
                                    <th>Роль</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['user_id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . htmlspecialchars($user['last_name'])); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit-user" data-user-id="<?= $user['user_id'] ?>" title="Редактировать"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-danger delete-user" data-user-id="<?= $user['user_id'] ?>" title="Удалить"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Модальное окно добавления бронирования -->
    <div class="modal fade" id="addBookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить бронирование</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bookingForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Гость</label>
                                <select class="form-select" name="user_id" required>
                                    <option value="">Выберите гостя</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['user_id']; ?>">
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . htmlspecialchars($user['last_name'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Номер</label>
                                <select class="form-select" name="room_id" required>
                                    <option value="">Выберите номер</option>
                                    <?php foreach ($rooms as $room): ?>
                                        <option value="<?php echo $room['room_id']; ?>" data-price="<?= $room['price'] ?>">
                                            <?php echo htmlspecialchars($room['name'] . ' (№' . $room['room_number'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Дата заезда</label>
                                <input type="date" class="form-control" name="check_in" id="check_in" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Дата выезда</label>
                                <input type="date" class="form-control" name="check_out" id="check_out" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Количество гостей</label>
                                <input type="number" class="form-control" name="guests" min="1" value="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Статус</label>
                                <select class="form-select" name="status" required>
                                    <option value="pending">Ожидает</option>
                                    <option value="confirmed">Подтверждено</option>
                                    <option value="cancelled">Отменено</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Особые пожелания</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="saveBooking">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования бронирования -->
    <div class="modal fade" id="editBookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать бронирование</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editBookingForm">
                        <input type="hidden" name="booking_id" id="editBookingId">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Гость</label>
                                <select class="form-select" name="user_id" id="editBookingUserId" required>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['user_id']; ?>">
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . htmlspecialchars($user['last_name'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Номер</label>
                                <select class="form-select" name="room_id" id="editBookingRoomId" required>
                                    <?php foreach ($rooms as $room): ?>
                                        <option value="<?php echo $room['room_id']; ?>" data-price="<?= $room['price'] ?>">
                                            <?php echo htmlspecialchars($room['name'] . ' (№' . $room['room_number'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Дата заезда</label>
                                <input type="date" class="form-control" name="check_in" id="editBookingCheckIn" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Дата выезда</label>
                                <input type="date" class="form-control" name="check_out" id="editBookingCheckOut" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Количество гостей</label>
                                <input type="number" class="form-control" name="guests" id="editBookingGuests" min="1" value="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Статус</label>
                                <select class="form-select" name="status" id="editBookingStatus" required>
                                    <option value="pending">Ожидает</option>
                                    <option value="confirmed">Подтверждено</option>
                                    <option value="cancelled">Отменено</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Особые пожелания</label>
                            <textarea class="form-control" name="notes" id="editBookingNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="updateBooking">Сохранить изменения</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно добавления номера -->
    <div class="modal fade" id="addRoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить номер</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="roomForm">
                        <div class="mb-3">
                            <label class="form-label">Номер комнаты</label>
                            <input type="text" class="form-control" name="room_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Тип номера</label>
                            <select class="form-select" name="id_type" required>
                                <?php foreach ($room_types as $type): ?>
                                    <option value="<?= $type['id_type'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Цена за ночь</label>
                            <input type="number" class="form-control" name="price" min="0" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Вместимость</label>
                            <input type="number" class="form-control" name="capacity" min="1" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="saveRoom">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования номера -->
    <div class="modal fade" id="editRoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать номер</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editRoomForm">
                        <input type="hidden" name="room_id" id="editRoomId">
                        <div class="mb-3">
                            <label class="form-label">Номер комнаты</label>
                            <input type="text" class="form-control" name="room_number" id="editRoomNumber" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Тип номера</label>
                            <select class="form-select" name="id_type" id="editRoomType" required>
                                <?php foreach ($room_types as $type): ?>
                                    <option value="<?= $type['id_type'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Цена за ночь</label>
                            <input type="number" class="form-control" name="price" id="editRoomPrice" min="0" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Вместимость</label>
                            <input type="number" class="form-control" name="capacity" id="editRoomCapacity" min="1" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="updateRoom">Сохранить изменения</button>
                </div>
            </div>
        </div>
    </div>

<!-- Модальное окно добавления пользователя -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить пользователя</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Пароль</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Имя</label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Фамилия</label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Телефон</label>
                        <input type="tel" class="form-control" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Роль</label>
                        <select class="form-select" name="role_id" required>
                            <?php
                            // Убедитесь, что $roles инициализирована как массив
                            $roles = [
                                ['id_role' => 1, 'role_name' => 'Администратор'],
                                ['id_role' => 2, 'role_name' => 'Пользователь'],
                                // Добавьте другие роли по мере необходимости
                            ];

                            // Используйте $roles в цикле foreach
                            foreach ($roles as $role) {
                                echo '<option value="' . $role['id_role'] . '">' . htmlspecialchars($role['role_name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="saveUser">Сохранить</button>
            </div>
        </div>
    </div>
</div>


    <!-- Модальное окно редактирования пользователя -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" name="user_id" id="editUserId">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editUserEmail" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Имя</label>
                            <input type="text" class="form-control" name="first_name" id="editUserFirstName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Фамилия</label>
                            <input type="text" class="form-control" name="last_name" id="editUserLastName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Телефон</label>
                            <input type="tel" class="form-control" name="phone" id="editUserPhone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Роль</label>
                        <select class="form-select" name="role_id" required>
                            <?php
                            // Убедитесь, что $roles инициализирована как массив
                            $roles = [
                                ['id_role' => 1, 'role_name' => 'Администратор'],
                                ['id_role' => 2, 'role_name' => 'Пользователь'],
                                // Добавьте другие роли по мере необходимости
                            ];

                            // Используйте $roles в цикле foreach
                            foreach ($roles as $role) {
                                echo '<option value="' . $role['id_role'] . '">' . htmlspecialchars($role['role_name']) . '</option>';
                            }
                            ?>
                        </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="updateUser">Сохранить изменения</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Подключение JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin.js"></script>
    <script>
        // Переключение между разделами
        document.querySelectorAll('[data-section]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Удаляем активный класс у всех ссылок
                document.querySelectorAll('[data-section]').forEach(el => {
                    el.parentElement.classList.remove('active');
                });
                
                // Добавляем активный класс к текущей ссылке
                this.parentElement.classList.add('active');
                
                // Скрываем все разделы
                document.querySelectorAll('.content-section').forEach(section => {
                    section.classList.remove('active');
                });
                
                // Показываем выбранный раздел
                const sectionId = this.getAttribute('data-section');
                document.getElementById(sectionId).classList.add('active');
            });
        });

        // Переключение бокового меню
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

// Пример исправления для работы с путями
document.addEventListener('DOMContentLoaded', function() {
    // Используем относительные пути для AJAX-запросов
    const baseUrl = window.location.origin;
    
    // Сохранение бронирования
    document.getElementById('saveBooking').addEventListener('click', function() {
        const form = document.getElementById('bookingForm');
        const formData = new FormData(form);
        
        fetch(baseUrl + '/save_booking.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Бронирование успешно добавлено');
                location.reload();
            } else {
                alert('Ошибка: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Произошла ошибка при сохранении');
        });
    });
            });

        // Редактирование бронирования
        document.querySelectorAll('.edit-booking').forEach(btn => {
            btn.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-booking-id');
                fetchBookingData(bookingId);
            });
        });

        function fetchBookingData(bookingId) {
            fetch('get_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ booking_id: bookingId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('editBookingId').value = data.booking.booking_id;
                    document.getElementById('editBookingUserId').value = data.booking.user_id;
                    document.getElementById('editBookingRoomId').value = data.booking.room_id;
                    document.getElementById('editBookingCheckIn').value = data.booking.check_in;
                    document.getElementById('editBookingCheckOut').value = data.booking.check_out;
                    document.getElementById('editBookingGuests').value = data.booking.guests;
                    document.getElementById('editBookingStatus').value = data.booking.status;
                    document.getElementById('editBookingNotes').value = data.booking.notes;

                    const modal = new bootstrap.Modal(document.getElementById('editBookingModal'));
                    modal.show();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            });
        }

        document.getElementById('updateBooking').addEventListener('click', function() {
            const form = document.getElementById('editBookingForm');
            const formData = new FormData(form);

            fetch('update_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Бронирование успешно обновлено');
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при обновлении');
            });
        });

        // Подтверждение бронирования
        document.querySelectorAll('.confirm-booking').forEach(btn => {
            btn.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-booking-id');
                updateBookingStatus(bookingId, 'confirmed');
            });
        });

        // Отмена бронирования
        document.querySelectorAll('.cancel-booking').forEach(btn => {
            btn.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-booking-id');
                updateBookingStatus(bookingId, 'cancelled');
            });
        });

        function updateBookingStatus(bookingId, status) {
            if (confirm(`Вы уверены, что хотите ${status === 'confirmed' ? 'подтвердить' : 'отменить'} это бронирование?`)) {
                fetch('update_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ booking_id: bookingId, status: status })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Статус бронирования обновлен');
                        location.reload();
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                });
            }
        }

        // Сохранение номера
        document.getElementById('saveRoom').addEventListener('click', function() {
            const form = document.getElementById('roomForm');
            const formData = new FormData(form);
            
            fetch('save_room.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Номер успешно добавлен');
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при сохранении');
            });
        });

        // Редактирование номера
        document.querySelectorAll('.edit-room').forEach(btn => {
            btn.addEventListener('click', function() {
                const roomId = this.getAttribute('data-room-id');
                fetchRoomData(roomId);
            });
        });

        function fetchRoomData(roomId) {
            fetch('get_room.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ room_id: roomId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('editRoomId').value = data.room.room_id;
                    document.getElementById('editRoomNumber').value = data.room.room_number;
                    document.getElementById('editRoomType').value = data.room.id_type;
                    document.getElementById('editRoomPrice').value = data.room.price;
                    document.getElementById('editRoomCapacity').value = data.room.capacity;

                    const modal = new bootstrap.Modal(document.getElementById('editRoomModal'));
                    modal.show();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            });
        }

        document.getElementById('updateRoom').addEventListener('click', function() {
            const form = document.getElementById('editRoomForm');
            const formData = new FormData(form);

            fetch('update_room.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Номер успешно обновлен');
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при обновлении');
            });
        });

        // Удаление номера
        document.querySelectorAll('.delete-room').forEach(btn => {
            btn.addEventListener('click', function() {
                const roomId = this.getAttribute('data-room-id');
                if (confirm('Вы уверены, что хотите удалить этот номер?')) {
                    fetch('delete_room.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ room_id: roomId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Номер удален');
                            location.reload();
                        } else {
                            alert('Ошибка: ' + data.message);
                        }
                    });
                }
            });
        });

        // Сохранение пользователя
        document.getElementById('saveUser').addEventListener('click', function() {
            const form = document.getElementById('userForm');
            const formData = new FormData(form);
            
            fetch('save_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Пользователь успешно добавлен');
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при сохранении');
            });
        });

        // Редактирование пользователя
        document.querySelectorAll('.edit-user').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                fetchUserData(userId);
            });
        });

        function fetchUserData(userId) {
            fetch('get_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('editUserId').value = data.user.user_id;
                    document.getElementById('editUserEmail').value = data.user.email;
                    document.getElementById('editUserFirstName').value = data.user.first_name;
                    document.getElementById('editUserLastName').value = data.user.last_name;
                    document.getElementById('editUserPhone').value = data.user.phone;
                    document.getElementById('editUserRole').value = data.user.role_id;

                    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                    modal.show();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            });
        }

        document.getElementById('updateUser').addEventListener('click', function() {
            const form = document.getElementById('editUserForm');
            const formData = new FormData(form);

            fetch('update_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Пользователь успешно обновлен');
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при обновлении');
            });
        });

        // Удаление пользователя
        document.querySelectorAll('.delete-user').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                if (confirm('Вы уверены, что хотите удалить этого пользователя?')) {
                    fetch('delete_user.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ user_id: userId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Пользователь удален');
                            location.reload();
                        } else {
                            alert('Ошибка: ' + data.message);
                        }
                    });
                }
            });
        });

        // Одобрение отзыва
        document.querySelectorAll('.approve-review').forEach(btn => {
            btn.addEventListener('click', function() {
                const reviewId = this.getAttribute('data-review-id');
                if (confirm('Вы уверены, что хотите одобрить этот отзыв?')) {
                    fetch('approve_review.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ review_id: reviewId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Отзыв одобрен');
                            location.reload();
                        } else {
                            alert('Ошибка: ' + data.message);
                        }
                    });
                }
            });
        });

        // Удаление отзыва
        document.querySelectorAll('.delete-review').forEach(btn => {
            btn.addEventListener('click', function() {
                const reviewId = this.getAttribute('data-review-id');
                if (confirm('Вы уверены, что хотите удалить этот отзыв?')) {
                    fetch('delete_review.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ review_id: reviewId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Отзыв удален');
                            location.reload();
                        } else {
                            alert('Ошибка: ' + data.message);
                        }
                    });
                }
            });
        });

        // Автоматический расчет стоимости бронирования
        document.getElementById('check_in').addEventListener('change', calculateTotalPrice);
        document.getElementById('check_out').addEventListener('change', calculateTotalPrice);
        document.querySelector('select[name="room_id"]').addEventListener('change', calculateTotalPrice);

        function calculateTotalPrice() {
            const checkIn = new Date(document.getElementById('check_in').value);
            const checkOut = new Date(document.getElementById('check_out').value);
            const roomSelect = document.querySelector('select[name="room_id"]');
            const price = roomSelect.options[roomSelect.selectedIndex].getAttribute('data-price');
            
            if (checkIn && checkOut && price && checkIn < checkOut) {
                const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
                const totalPrice = nights * price;
                document.getElementById('total_price').value = totalPrice.toFixed(2);
            }
        }
    </script>
</body>
</html>