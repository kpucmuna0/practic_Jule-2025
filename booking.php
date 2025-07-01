<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
session_start();

$userLoggedIn = isset($_SESSION['user_id']);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['book_room'])) {
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php?redirect=booking");
            exit();
        }
        
        $roomId = $_POST['room_id'];
        $checkIn = $_POST['check_in'];
        $checkOut = $_POST['check_out'];
        $guests = $_POST['guests'];
        $userId = $_SESSION['user_id'];
        
        
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = ?");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch();
        
        if (!$room) {
            die("Номер не найден");
        }
        
        
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM bookings 
    WHERE room_id = ? 
    AND check_in < ? 
    AND check_out > ?
");
$stmt->execute([$roomId, $checkOut, $checkIn]);
        $conflictingBookings = $stmt->fetchColumn();
        
        if ($conflictingBookings > 0) {
            die("Номер уже забронирован на выбранные даты");
        }
        
        
        if ($guests > $room['capacity']) {
            die("Количество гостей превышает вместимость номера");
        }
        
        
        $nights = (strtotime($checkOut) - strtotime($checkIn)) / (60 * 60 * 24);
        $totalPrice = $room['price'] * $nights;
        
        
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, total_price, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$userId, $roomId, $checkIn, $checkOut, $guests, $totalPrice]);
        
        header("Location: account.php");
        exit();
    }
    
   
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'get_booked_dates':
                $roomId = $_POST['room_id'];
                
                $stmt = $pdo->prepare("
                    SELECT check_in, check_out FROM bookings 
                    WHERE room_id = ?
                    ORDER BY check_in
                ");
                $stmt->execute([$roomId]);
                $bookings = $stmt->fetchAll();
                
                $bookedDates = [];
                foreach ($bookings as $booking) {
                    $start = new DateTime($booking['check_in']);
                    $end = new DateTime($booking['check_out']);
                    $end->modify('-1 day'); 
                    
                    $interval = new DateInterval('P1D');
                    $period = new DatePeriod($start, $interval, $end);
                    
                    foreach ($period as $date) {
                        $bookedDates[] = $date->format('Y-m-d');
                    }
                }
                
                echo json_encode(['booked_dates' => $bookedDates]);
                exit;
                
            case 'check_availability':
                $roomId = $_POST['room_id'];
                $checkIn = $_POST['check_in'];
                $checkOut = $_POST['check_out'];
                $guests = $_POST['guests'];
                
                
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM bookings 
    WHERE room_id = ? 
    AND check_in < ? 
    AND check_out > ?
");
$stmt->execute([$roomId, $checkOut, $checkIn]);
                $conflictingBookings = $stmt->fetchColumn();
                
                
                $stmt = $pdo->prepare("SELECT capacity, price FROM rooms WHERE room_id = ?");
                $stmt->execute([$roomId]);
                $room = $stmt->fetch();
                
                $available = ($conflictingBookings == 0 && $guests <= $room['capacity']);
                
                
                $nights = (strtotime($checkOut) - strtotime($checkIn)) / (60 * 60 * 24);
                $totalPrice = $room['price'] * $nights;
                
                echo json_encode([
                    'available' => $available,
                    'total_price' => $totalPrice,
                    'nights' => $nights,
                    'capacity_ok' => ($guests <= $room['capacity'])
                ]);
                exit;
        }
    }
}


$checkIn = isset($_GET['checkin']) ? $_GET['checkin'] : date('Y-m-d');
$checkOut = isset($_GET['checkout']) ? $_GET['checkout'] : date('Y-m-d', strtotime('+1 day'));
$guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 1;


$stmt = $pdo->prepare("
    SELECT r.*, tr.name AS type_name 
    FROM rooms r
    JOIN type_rooms tr ON r.id_type = tr.id_type
    WHERE r.capacity >= ? AND r.is_available > 0
    AND r.room_id NOT IN (
        SELECT room_id FROM bookings 
        WHERE (check_in < ? AND check_out > ?)
        OR (check_in >= ? AND check_in < ?)
        OR (check_out > ? AND check_out <= ?)
    )
");
$stmt->execute([$guests, $checkOut, $checkIn, $checkIn, $checkOut, $checkIn, $checkOut]);
$availableRooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бронирование | HOTELYAR</title>
    <link rel="stylesheet" href="/css/booking.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>

           :root {
            --primary: #003580;
            --secondary: #feba02;
            --accent: #ff6b6b;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            color: var(--dark);
            line-height: 1.7;
            background-color: var(--light);
            overflow-x: hidden;
        }
        
        
        .site-brow {
            background: linear-gradient(90deg, var(--secondary), var(--accent));
            height: 5px;
            width: 100%;
            box-shadow: var(--shadow);
        }
        
        
        .top-nav {
            background-color: var(--primary);
            padding: 0;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: var(--transition);
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 10px;
            height: 60px;
        }
        
        .nav-logo {
            color: white;
            font-weight: 700;
            font-size: 20px;
            letter-spacing: 1.5px;
            display: flex;
            align-items: center;
            height: 100%;
            transition: var(--transition);
        }
        
        .nav-logo:hover {
            color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            height: 100%;
        }
   
        .nav-menu li {
            margin-left: 1px;
            display: flex;
            align-items: center;
            height: 100%;
            position: relative;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            transition: var(--transition);
            letter-spacing: 1px;
            padding: 1px 1px;
            display: flex;
            align-items: center;
            height: 100%;
            position: relative;
        }
        
        .nav-menu a:hover {
            color: var(--secondary);
        }
        
        .nav-menu li::before {
            content: none !important;
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 3px;
            background-color: var(--secondary);
            transition: var(--transition);
            border-radius: 3px 3px 0 0;
        }
        
        .nav-menu a:hover::before {
            width: 100%;
        }
        
        
        .nav-menu a.active {
            color: var(--secondary);
            font-weight: 600;
        }
        
        .nav-menu a.active::before {
            width: 100%;
        }
        
        
        .booking-btn {
            background-color: var(--secondary);
            color: var(--primary);
            border-radius: 4px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.3s ease;
            margin-left: 10px;
        }
        
        .booking-btn:hover {
            background-color: #e6a800;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
       
        .hero {
            height: 500px;
            background: linear-gradient(rgba(0, 53, 128, 0.7), rgba(0, 53, 128, 0.5)), 
                        url('https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80') center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 0 20px;
            margin-bottom: 60px;
            position: relative;
        }
        .white-text {
    background: transparent !important;
    text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.5);
    padding: 0 !important;
}
        .hero::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
        }
        
        .hero-content {
            max-width: 800px;
            position: relative;
            z-index: 1;
        }
        
        .hero h1 {
            font-size: 48px;
            margin-bottom: 25px;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
            animation: fadeInDown 1s ease;
            font-weight: 700;
            line-height: 1.3;
        }
        
        .hero p {
            font-size: 24px;
            margin-bottom: 30px;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.5);
            animation: fadeInUp 1s ease;
            font-weight: 500;
            letter-spacing: 0.5px;
            line-height: 1.5;
            background: rgba(0, 53, 128, 0.7);
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
        }
        
        
        .section {
            max-width: 1200px;
            margin: 40px auto;
            padding: 50px;
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .section:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        h2 {
            color: var(--primary);
            margin-bottom: 40px;
            font-size: 36px;
            text-align: center;
            padding-bottom: 20px;
            position: relative;
        }
        
        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }
        
        h3 {
            color: var(--primary);
            margin: 40px 0 25px;
            font-size: 28px;
            position: relative;
            padding-left: 15px;
        }
        
        h3::before {
            content: '';
            position: absolute;
            left: 0;
            top: 5px;
            height: 70%;
            width: 5px;
            background-color: var(--secondary);
            border-radius: 3px;
        }
        
        p {
            margin-bottom: 20px;
            color: var(--dark);
            font-size: 16px;
            line-height: 1.8;
        }
        
        ul {
            margin-bottom: 30px;
            padding-left: 30px;
            list-style-type: none;
        }
        
        li {
            margin-bottom: 12px;
            position: relative;
            padding-left: 30px;
            color: var(--dark);
        }
        
        li::before {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: var(--secondary);
            position: absolute;
            left: 0;
            top: 2px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            box-shadow: var(--shadow);
            border-radius: 8px;
            overflow: hidden;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        th {
            background-color: var(--primary);
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 14px;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tr:hover {
            background-color: rgba(254, 186, 2, 0.1);
        }
        
        strong {
            color: var(--primary);
            font-weight: 600;
        }
        
        
        footer {
            background: linear-gradient(135deg, var(--primary), #002a66);
            color: white;
            text-align: center;
            padding: 50px 0 30px;
            margin-top: 80px;
            position: relative;
        }
        
        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(90deg, var(--secondary), var(--accent));
        }
        
        footer p {
            color: white;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .footer-logo {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 20px;
            display: inline-block;
            color: white;
        }
        
        .social-links {
            margin: 25px 0;
        }
        
        .social-links a {
            color: white;
            font-size: 20px;
            margin: 0 15px;
            transition: var(--transition);
            display: inline-block;
        }
        
        .social-links a:hover {
            color: var(--secondary);
            transform: translateY(-3px);
        }
        
        .copyright {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }
        
       
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
       
        @media (max-width: 992px) {
            .nav-container {
                padding: 0 20px;
            }
            
            .nav-menu li {
                margin-left: 10px;
            }
            
            .section {
                padding: 30px;
            }
            
            h2 {
                font-size: 30px;
            }
            
            h3 {
                font-size: 24px;
            }
            
            .hero h1 {
                font-size: 42px;
            }
            
            .hero p {
                font-size: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                height: auto;
                padding: 15px;
            }
            
            .nav-logo {
                margin-bottom: 15px;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-menu li {
                margin: 5px 10px;
            }
            
            .hero {
                height: 400px;
            }
            
            .hero h1 {
                font-size: 32px;
                margin-bottom: 15px;
            }
            
            .hero p {
                font-size: 18px;
                padding: 8px 15px;
            }
            
            .section {
                margin: 20px auto;
                padding: 25px;
            }
            
            .booking-btn {
                margin-left: 0;
                margin-top: 10px;
            }
        }
        
        
        .feature-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .feature-item {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            border-left: 4px solid var(--secondary);
        }
        
        .feature-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .feature-item h4 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 20px;
            display: flex;
            align-items: center;
        }
        
        .feature-item h4 i {
            margin-right: 10px;
            color: var(--secondary);
        }
        
        
        .advantages {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .advantage {
            flex: 1 1 200px;
            background: linear-gradient(135deg, rgba(0, 53, 128, 0.1), rgba(254, 186, 2, 0.1));
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            transition: var(--transition);
        }
        
        .advantage i {
            font-size: 40px;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .advantage h4 {
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .advantage:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        
        #reviews-section {
            margin-top: 40px;
        }

        .reviews-container {
            margin-bottom: 30px;
        }

        .review {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            border-left: 4px solid var(--secondary);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .review-author {
            font-weight: bold;
            color: var(--primary);
            font-size: 18px;
        }

        .review-date {
            color: var(--gray);
            font-size: 14px;
        }

        .review-rating {
            display: flex;
            margin-left: 15px;
        }

        .review-rating .star {
            color: #ccc;
            font-size: 20px;
            margin-right: 3px;
        }

        .review-rating .star.filled {
            color: var(--secondary);
        }

        .review-content {
            line-height: 1.6;
            color: var(--dark);
        }

        .login-prompt {
            text-align: center;
            margin-top: 30px;
            font-size: 16px;
        }

        .login-prompt a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
        }

        .login-prompt a:hover {
            text-decoration: underline;
        }

        
.user-menu {
    position: relative;
    padding-right: 15px;
}

.user-menu > a::after {
    content: '\f078';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    margin-left: 8px;
    font-size: 12px;
    transition: var(--transition);
}

.user-menu:hover > a::after {
    transform: rotate(180deg);
}

.user-menu .submenu {
    display: block; 
    position: absolute;
    top: 100%;
    right: 0;
    background-color: white;
    min-width: 200px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    z-index: 1000;
    border-radius: 8px;
    padding: 10px 0;
    opacity: 0;
    visibility: hidden;
    transition: var(--transition);
}

.user-menu:hover .submenu {
    opacity: 1;
    visibility: visible;
}

        .user-menu .submenu li {
            margin: 0;
            padding: 0;
            display: block;
        }

        .user-menu .submenu a {
            color: var(--dark) !important;
            padding: 8px 15px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            text-transform: none;
            transition: var(--transition);
        }
.user-menu .submenu a i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}
        .user-menu .submenu a:hover {
            background-color: rgba(0, 53, 128, 0.05);
            color: var(--primary) !important;
            padding-left: 25px;
        }
        
        
        .user-email {
            font-weight: 600;
            font-size: 13px;
            color: var(--secondary) !important;
            text-transform: none !important;
        }          
        .booking-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .booking-form div {
            display: flex;
            flex-direction: column;
        }
        
        .booking-form label {
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary);
        }
        
        .booking-form input, 
        .booking-form select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Montserrat', sans-serif;
        }
        
        .booking-form button[type="submit"] {
            background-color: var(--secondary);
            color: var(--primary);
            border: none;
            padding: 12px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            align-self: flex-end;
        }
        
        .booking-form button[type="submit"]:hover {
            background-color: #e6a800;
            transform: translateY(-2px);
        }
        
        .room-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .room-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .room-carousel {
            position: relative;
            overflow: hidden;
        }
        
        .carousel-slides {
            display: flex;
            transition: transform 0.5s ease;
        }
        
        .carousel-slide {
            min-width: 100%;
        }
        
        .carousel-slide img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 10;
        }
        
        .carousel-prev {
            left: 10px;
        }
        
        .carousel-next {
            right: 10px;
        }
        
        .carousel-dots {
            position: absolute;
            bottom: 10px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 5px;
        }
        
        .carousel-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
        }
        
        .carousel-dot.active {
            background: white;
        }
        
        .room-info {
            padding: 20px;
        }
        
        .room-info h3 {
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .room-info p {
            margin-bottom: 10px;
            color: var(--gray);
        }
        
        .price {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin: 15px 0;
        }
        
        .price small {
            font-size: 14px;
            font-weight: normal;
            color: var(--gray);
        }
        
        .select-room {
            background-color: var(--secondary);
            color: var(--primary);
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
        }
        
        .select-room:hover {
            background-color: #e6a800;
            transform: translateY(-2px);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            max-width: 600px;
            position: relative;
        }
        
        .close-modal {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
        }
        
        .modal-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 12px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        
        #confirm-booking {
            background-color: var(--secondary);
            color: var(--primary);
            border: none;
        }
        
        #cancel-booking {
            background-color: transparent;
            border: 1px solid var(--gray);
            color: var(--dark);
        }
        
        .auth-button-container {
            margin: 40px 0;
            text-align: center;
        }
        
        .auth-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow);
            text-transform: uppercase;
        }
        
        .auth-button:hover {
            background-color: #002a66;
            transform: translateY(-2px);
        }
        
        .benefits {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            margin-top: 40px;
        }
        
        .benefits h4 {
            color: var(--primary);
            margin-bottom: 20px;
            text-align: center;
        }
        
        .benefits ul {
            padding-left: 20px;
        }
        
        .benefits li {
            margin-bottom: 10px;
            position: relative;
            padding-left: 30px;
        }
        
        .benefits li::before {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: var(--secondary);
            position: absolute;
            left: 0;
            top: 2px;
        }
        
        .auth-button-container {
            margin: 30px 0;
            text-align: center;
        }
        
        .auth-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2c3e50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            text-transform: uppercase;
        }
        
        .auth-button:hover {
            background-color: #34495e;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .auth-button:active {
            transform: translateY(0);
            box-shadow: 0 2px 3px rgba(0,0,0,0.2);
        }
        
        
        @media (max-width: 768px) {
            .auth-button {
                padding: 10px 20px;
                font-size: 14px;
                width: 100%;
                max-width: 300px;
            }
        }



        
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }
        
        .modal.active .modal-content {
            transform: translateY(0);
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            color: #888;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .close-modal:hover {
            color: #333;
        }
        
       
        .modal h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 24px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        #room-booking-details {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        #room-booking-details p {
            margin: 5px 0;
            font-size: 16px;
        }
        
        #room-booking-details strong {
            color: #2c3e50;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        
        #availability-message {
            margin: 15px 0;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        
        .price-calculation {
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
            text-align: center;
        }
        
        .price-calculation p {
            margin: 5px 0;
            font-size: 16px;
        }
        
        #total-price {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        
       
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        #check-availability {
            background-color: #3498db;
            color: white;
        }
        
        #check-availability:hover {
            background-color: #2980b9;
        }
        
        #confirm-booking {
            background-color: #27ae60;
            color: white;
        }
        
        #confirm-booking:hover {
            background-color: #219653;
        }
        
        #cancel-booking {
            background-color: #e74c3c;
            color: white;
        }
        
        #cancel-booking:hover {
            background-color: #c0392b;
        }
        
        
        .room-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .room-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .room-card h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .room-card .price {
            font-size: 20px;
            font-weight: bold;
            color: #27ae60;
            margin: 10px 0;
        }
        
        .select-room {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .select-room:hover {
            background-color: #2980b9;
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
                <li><a href="booking.php" class="nav-link booking-btn">БРОНИРОВАНИЕ</a></li>
                <?php if ($userLoggedIn): ?>
                <li class="user-menu">
                        <a href="#" class="nav-link"><?= htmlspecialchars($_SESSION['email']) ?></a>
                        <ul class="submenu">
                            <li><a href="account.php"><i class="fas fa-user-circle"></i> Личный кабинет</a></li>
                            <li><a href="logout.php">Выйти</a>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <section id="booking-section" class="section">
        <h2>Бронирование номеров</h2>
        
        <div class="booking-form">
            <form method="get" action="booking.php">
                <div class="form-group">
                    <label for="checkin">Заезд</label>
                    <input type="date" id="checkin" name="checkin" value="<?= htmlspecialchars($checkIn) ?>" min="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="checkout">Выезд</label>
                    <input type="date" id="checkout" name="checkout" value="<?= htmlspecialchars($checkOut) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="guests">Гостей</label>
                    <select id="guests" name="guests">
                        <option value="1" <?= $guests == 1 ? 'selected' : '' ?>>1</option>
                        <option value="2" <?= $guests == 2 ? 'selected' : '' ?>>2</option>
                        <option value="3" <?= $guests == 3 ? 'selected' : '' ?>>3</option>
                        <option value="4" <?= $guests == 4 ? 'selected' : '' ?>>4</option>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="btn-primary">НАЙТИ НОМЕР</button>
                </div>
            </form>
        </div>
        
        <?php if (!empty($availableRooms)): ?>
        <div id="available-rooms">
            <h3>Доступные номера на выбранные даты</h3>
            
            <div class="room-list">
                <?php foreach ($availableRooms as $room): ?>
                <div class="room-card" data-room-id="<?= $room['room_id'] ?>">
                    <h3><?= htmlspecialchars($room['type_name']) ?></h3>
                    <p>Вместимость: до <?= $room['capacity'] ?> гостей</p>
                    <p>Номер: <?= $room['room_number'] ?></p>
                    <div class="price"><?= number_format($room['price'], 0, '', ' ') ?> ₽<br><small>за ночь</small></div>
                    <button class="select-room" data-room-id="<?= $room['room_id'] ?>">ВЫБРАТЬ</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['checkin'])): ?>
            <p>На выбранные даты нет доступных номеров. Пожалуйста, измените параметры поиска.</p>
        <?php endif; ?>

        <!-- Модальное окно бронирования -->
        <div id="booking-modal" class="modal">
            <div class="modal-content">
                <span class="close-modal"><i class="fas fa-times"></i></span>
                <h3>Бронирование номера</h3>
                <div id="room-booking-details"></div>
                
                <form id="booking-details-form">
                    <input type="hidden" name="room_id" id="modal-room-id">
                    <input type="hidden" name="book_room" value="1">
                    
                    <div class="form-group">
                        <label for="modal-checkin">Заезд</label>
                        <input type="date" id="modal-checkin" name="check_in" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="modal-checkout">Выезд</label>
                        <input type="date" id="modal-checkout" name="check_out" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="modal-guests">Гостей</label>
                        <select id="modal-guests" name="guests" required>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>
                    
                    <div id="availability-message"></div>
                    <div id="price-calculation" class="price-calculation" style="display: none;">
                        <p>Количество ночей: <span id="nights">0</span></p>
                        <p>Общая стоимость: <span id="total-price">0</span> ₽</p>
                    </div>
                    
                    <div class="modal-buttons">
                        <button type="submit" id="confirm-booking" class="btn-success">
                            <i class="fas fa-check"></i> ЗАБРОНИРОВАТЬ
                        </button>
                        <button type="button" id="cancel-booking" class="btn-danger">
                            <i class="fas fa-times"></i> ОТМЕНИТЬ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <footer>
        <div class="footer-logo">HOTELYAR</div>
        <p>г. Ярославль, ул. Колесова д.999</p>
        <p>Телефон: +7 (4852) 12-34-56</p>
        <p>Email: info@hamster-hotel.ru</p>
        
        <div class="social-links">
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-vk"></i></a>
            <a href="#"><i class="fab fa-telegram-plane"></i></a>
        </div>
        
        <p>Модуль онлайн-бронирования</p>
        
        <div class="copyright">
            © 2025 Отель HOTELYAR. Все права защищены.
        </div>
    </footer>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        function loadBookedDates(roomId) {
            return fetch('booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_booked_dates&room_id=${roomId}`
            })
            .then(response => response.json())
            .then(data => data.booked_dates)
            .catch(error => {
                console.error('Error:', error);
                return [];
            });
        }

        
function calculateNightsAndPrice() {
    const checkin = document.getElementById('modal-checkin').value;
    const checkout = document.getElementById('modal-checkout').value;
    const roomPriceText = document.getElementById('room-price').textContent;
    
const roomPrice = parseFloat(roomPriceText.replace(/\s+/g, ''));

    if (checkin && checkout) {
        const checkinDate = new Date(checkin);
        const checkoutDate = new Date(checkout);

        
        const timeDiff = checkoutDate.getTime() - checkinDate.getTime();
        const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));

        
        const totalPrice = nights * roomPrice;

        
        document.getElementById('nights').textContent = nights;
        document.getElementById('total-price').textContent = totalPrice.toLocaleString('ru-RU');

        
        document.getElementById('price-calculation').style.display = 'block';
    }
}




        
document.querySelectorAll('.select-room').forEach(button => {
    button.addEventListener('click', async function() {
        const roomId = this.getAttribute('data-room-id');
        const roomCard = this.closest('.room-card');
        const roomName = roomCard.querySelector('h3').textContent;
        const roomPriceText = roomCard.querySelector('.price').textContent.trim();
        
const roomPrice = parseFloat(roomPriceText.replace(/\s+/g, ''));

        
        document.getElementById('modal-room-id').value = roomId;
        document.getElementById('room-booking-details').innerHTML = `
            <p><strong>Номер:</strong> ${roomName}</p>
            <p><strong>Цена за ночь:</strong> <span id="room-price">${roomPrice}</span> ₽</p>
        `;

        
        const checkinValue = document.getElementById('checkin').value;
        const checkoutValue = document.getElementById('checkout').value;

        document.getElementById('modal-checkin').value = checkinValue;
        document.getElementById('modal-checkout').value = checkoutValue;
        document.getElementById('modal-guests').value = document.getElementById('guests').value;

        
        const checkinDate = new Date(checkinValue);
        checkinDate.setDate(checkinDate.getDate() + 1);
        const minCheckout = checkinDate.toISOString().split('T')[0];
        document.getElementById('modal-checkout').min = minCheckout;

        
        const bookedDates = await loadBookedDates(roomId);

        
        calculateNightsAndPrice();

        
        document.getElementById('booking-modal').classList.add('active');
    });
});

        
        document.querySelector('.close-modal').addEventListener('click', function() {
            document.getElementById('booking-modal').classList.remove('active');
        });

        
        document.getElementById('booking-modal').addEventListener('click', function(event) {
            if (event.target === this) {
                this.classList.remove('active');
            }
        });

        
        document.getElementById('modal-checkin').addEventListener('change', function() {
            
            const checkinDate = new Date(this.value);
            checkinDate.setDate(checkinDate.getDate() + 1);
            const minCheckout = checkinDate.toISOString().split('T')[0];
            document.getElementById('modal-checkout').min = minCheckout;
            
            
            if (new Date(document.getElementById('modal-checkout').value) < checkinDate) {
                document.getElementById('modal-checkout').value = minCheckout;
            }
            
            calculateNightsAndPrice();
        });

        document.getElementById('modal-checkout').addEventListener('change', calculateNightsAndPrice);

        
document.getElementById('confirm-booking').addEventListener('click', function(e) {
    e.preventDefault();

    const roomId = document.getElementById('modal-room-id').value;
    const checkIn = document.getElementById('modal-checkin').value;
    const checkOut = document.getElementById('modal-checkout').value;
    const guests = document.getElementById('modal-guests').value;

    
    const today = new Date().toISOString().split('T')[0];
    if (checkIn < today) {
        document.getElementById('availability-message').innerHTML =
            '<p class="error-message">Дата заезда не может быть раньше сегодняшней.</p>';
        return;
    }

    
    if (checkOut <= checkIn) {
        document.getElementById('availability-message').innerHTML =
            '<p class="error-message">Дата выезда должна быть позже даты заезда.</p>';
        return;
    }

    
    const formData = new FormData();
    formData.append('book_room', '1');
    formData.append('room_id', roomId);
    formData.append('check_in', checkIn);
    formData.append('check_out', checkOut);
    formData.append('guests', guests);

    
    fetch('booking.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        } else {
            return response.text();
        }
    })
    .then(data => {
        if (data) {
            alert(data);
            document.getElementById('booking-modal').classList.remove('active');
            window.location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
});



      
        document.getElementById('booking-details-form').addEventListener('submit', function(event) {
            event.preventDefault();

            const roomId = document.getElementById('modal-room-id').value;
            const checkIn = document.getElementById('modal-checkin').value;
            const checkOut = document.getElementById('modal-checkout').value;
            const guests = document.getElementById('modal-guests').value;

            fetch('booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `book_room=1&room_id=${roomId}&check_in=${checkIn}&check_out=${checkOut}&guests=${guests}`
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.text();
                }
            })
            .then(data => {
                if (data) {
                    alert(data);
                    document.getElementById('booking-modal').classList.remove('active');
                    window.location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        });

        // Отмена бронирования
        document.getElementById('cancel-booking').addEventListener('click', function() {
            document.getElementById('booking-modal').classList.remove('active');
        });
    });
    </script>
</body>
</html>