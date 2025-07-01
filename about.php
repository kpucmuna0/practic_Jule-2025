<?php
require_once 'config.php'; // Подключаем файл конфигурации

session_start();
$userLoggedIn = isset($_SESSION['user_id']);

// Получение отзывов из базы данных
$reviews = [];
try {
    $query = "SELECT u.first_name, u.last_name, r.rating, r.comment, r.created_at
              FROM reviews r
              JOIN users u ON r.user_id = u.user_id
              ORDER BY r.created_at DESC";
    $stmt = $pdo->query($query);
    $reviews = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("Ошибка при получении отзывов: " . $e->getMessage());
}

// Рассчитать среднюю оценку
$averageRating = 0;
if (!empty($reviews)) {
    $totalRating = array_sum(array_column($reviews, 'rating'));
    $averageRating = $totalRating / count($reviews);
}

// Обработка отправки нового отзыва
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && $userLoggedIn) {
    $rating = intval($_POST['rating']);
    $comment = $_POST['comment'];
    $user_id = $_SESSION['user_id'];

    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        try {
            $insert_query = "INSERT INTO reviews (user_id, rating, comment) VALUES (:user_id, :rating, :comment)";
            $stmt = $pdo->prepare($insert_query);
            $stmt->execute(['user_id' => $user_id, 'rating' => $rating, 'comment' => $comment]);
            header("Location: about.php");
            exit();
        } catch (\PDOException $e) {
            $error = "Ошибка при сохранении отзыва. Попробуйте позже.";
        }
    } else {
        $error = "Пожалуйста, укажите оценку от 1 до 5 и напишите отзыв";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOTELYAR</title>
    <link rel="stylesheet" href="/css/about.css">
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
   
    <section class="hero">
        <div class="hero-content">
            <h1>HOTELYAR - Ваш комфорт в Ярославле</h1>

        </div>
    </section>
    
    <section id="about-section" class="section">
        <h2>Об отеле - HOTELYAR, г. Ярославль</h2>
        <p>Расположен в историческом центре города, всего в 500 метрах от Ярославского главного железнодорожного вокзала и в 15 км от аэропорта «Туношна». Благодаря удобной транспортной доступности, вы легко доберетесь как из Москвы, так и из других городов России.</p>

        <p>Рядом с отелем — главные достопримечательности Ярославля:</p>
        <p>✔ Советская площадь – сердце города с исторической застройкой</p>
        <p>✔ Волжская набережная – живописный променад с видом на реку</p>
        <p>✔ Ярославский театр драмы им. Ф. Волкова – первый профессиональный театр России</p>
        <p>✔ Церковь Ильи Пророка – объект ЮНЕСКО с уникальными фресками</p>
        <p>✔ Торговый центр «Аура» – крупнейший шопинг-молл в центре</p>
        <p>✔ Рынок «Коровники» – место, где можно купить свежие фермерские продукты</p>

    <p> В шаговой доступности — уютные кафе, рестораны с русской кухней и благоустроенные парки. Отель идеально подходит как для туристов, желающих погрузиться в атмосферу древнего города, так и для деловых путешественников.</p>

    <p>Выбирайте «HAMSTER» – комфорт в самом сердце Ярославля!</p>
        <h3>К услугам наших гостей:</h3>
        <ul>
            <li>4 номера, оснащенных всем необходимым как для комфортного отдыха, так и для продуктивной работы</li>
            <li>Горячий завтрак "шведский стол" (включен в стоимость)</li>
            <li>Бесплатный круглосуточный фитнес-центр</li>
            <li>Бесплатный бизнес-центр</li>
            <li>Лобби-бар</li>
            <li>2 конференц-зала</li>
            <li>Организация фуршетов и кофе-брейков</li>
        </ul>

        <h3>Условия проживания</h3>
        <p><strong>Режим работы гостиницы - круглосуточный</strong></p>

        <table>
            <tr>
                <td>Регистрация заезда</td>
                <td>14:00</td>
            </tr>
            <tr>
                <td>Регистрация выезда</td>
                <td>12:00</td>
            </tr>
            <tr>
                <td>Отмена бронирования</td>
                <td>Отмена бронирования без штрафа допустима не позднее, чем за 24 часа до расчетного времени заезда.</td>
            </tr>
            <tr>
                <td>Размещение с детьми</td>
                <td>Дети до 5 лет размещаются бесплатно, дети старше 5 лет - за дополнительную плату (500 руб.)</td>
            </tr>
            <tr>
                <td>Размещение с животными</td>
                <td>Не допускается, кроме собак-поводырей.</td>
            </tr>
            <tr>
                <td>Парковка</td>
                <td>Без дополнительной платы. Количество парковочных мест ограничено.</td>
            </tr>
        </table>

        <h3>Услуги раннего заезда и позднего выезда</h3>
        <p>Предоставляются за дополнительную плату:</p>
        <ul>
            <li>Ранний заезд до 02:00 – 100% стоимости суточного тарифа</li>
            <li>Ранний заезд 02:00 до 14:00 – 50% стоимости суточного тарифа</li>
            <li>Поздний выезд до 18:00 – 50% стоимости суточного тарифа</li>
            <li>Поздний выезд с 18:00 до 00:00 – 100% стоимости суточного тарифа</li>
        </ul>

        <h3>Правила и условия</h3>
        <p>Заселение в отель осуществляется по предъявлении оригинала документа, удостоверяющего личность каждого гостя.</p>
    </section>

     <section id="reviews-section" class="section">
        <h2>Отзывы наших гостей</h2>

        
        <?php if (!empty($reviews)): ?>
            <div class="average-rating">
                <p>Средняя оценка: <?= number_format($averageRating, 1) ?> из 5</p>
            </div>
        <?php endif; ?>

        <div class="reviews-container">
            <?php if (empty($reviews)): ?>
                <p>Пока нет отзывов. Будьте первым!</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review">
                        <div class="review-header">
                            <span class="review-author"><?= htmlspecialchars($review['first_name'] . ' ' . htmlspecialchars($review['last_name'])) ?></span>
                            <span class="review-date"><?= date('d.m.Y', strtotime($review['created_at'])) ?></span>
<div class="review-rating">
    <?php
    $rating = intval($review['rating']);
    
    for ($i = 1; $i <= $rating; $i++): ?>
        <span class="star filled">★</span>
    <?php endfor; ?>
    
    <?php for ($i = $rating + 1; $i <= 5; $i++): ?>
        <span class="star">☆</span>
    <?php endfor; ?>
    <span class="rating-value">(<?= $rating ?>)</span>
</div>
                        </div>
                        <div class="review-content"><?= htmlspecialchars($review['comment']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($userLoggedIn): ?>
            <div class="add-review">
                <h3>Оставить отзыв</h3>
                <form method="POST" action="about.php">
                    <div class="form-group">
                        <label for="rating">Оценка:</label>
                        <select name="rating" id="rating" required>
                            <option value="5">Отлично (5)</option>
                            <option value="4">Хорошо (4)</option>
                            <option value="3">Удовлетворительно (3)</option>
                            <option value="2">Плохо (2)</option>
                            <option value="1">Ужасно (1)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="comment">Ваш отзыв:</label>
                        <textarea name="comment" id="comment" rows="4" required></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="submit-btn">Отправить отзыв</button>
                </form>
            </div>
        <?php else: ?>
            <p class="login-prompt">Хотите оставить отзыв? <a href="login.php">Войдите</a> в свой аккаунт.</p>
        <?php endif; ?>
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
</body>
</html>
