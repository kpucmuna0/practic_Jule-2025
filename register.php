<?php
require_once 'config.php';
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Функция для отправки email с ссылкой на сброс пароля
function sendResetEmail($email, $token) {
    $to = $email;
    $subject = "Восстановление пароля для HAMSTER";
    
    $resetLink = "http://kura/reset_password.php?token=$token";
    
    $message = "
    <html>
    <head>
        <title>Восстановление пароля</title>
    </head>
    <body>
        <h2>Восстановление пароля</h2>
        <p>Вы запросили восстановление пароля для аккаунта HAMSTER.</p>
        <p>Для установки нового пароля перейдите по ссылке:</p>
        <p><a href='$resetLink'>$resetLink</a></p>
        <p>Если вы не запрашивали восстановление пароля, проигнорируйте это письмо.</p>
        <p>Ссылка действительна в течение 1 часа.</p>
        <hr>
        <p>С уважением,<br>Команда HAMSTER</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: no-reply@yourdomain.com\r\n";
    $headers .= "Reply-To: no-reply@yourdomain.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $message, $headers);
}

// Проверка подключения к БД
try {
    $test = $pdo->query("SELECT 1");
} catch (PDOException $e) {
    die(json_encode(['error' => "Ошибка подключения к БД: " . $e->getMessage()]));
}

// Если это AJAX-запрос, возвращаем JSON
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (isset($_SESSION['user_id'])) {
    if ($isAjax) {
        die(json_encode(['redirect' => 'account.php']));
    } else {
        header("Location: account.php");
        exit();
    }
}

$errors = [];
$success = '';
$showForgotPassword = false;

// Обработка формы восстановления пароля
if (isset($_GET['forgot_password'])) {
    $showForgotPassword = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка формы восстановления пароля
    if (isset($_POST['forgot_email'])) {
        $email = trim($_POST['forgot_email'] ?? '');
        
        if (empty($email)) {
            $errors[] = "Email обязателен для заполнения";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Некорректный email";
        } else {
            try {
                // Проверяем email пользователя
                $stmt = $pdo->prepare("SELECT email FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    // Генерируем токен для сброса пароля
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', time() + 3600); // Токен действителен 1 час
                    
                    // Сохраняем токен в базе данных
                    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                    $stmt->execute([$email, $token, $expires]);
                    
                    // Отправляем письмо с инструкциями
                    if (sendResetEmail($email, $token)) {
                        $success = "Инструкции по восстановлению пароля отправлены на ваш email. Проверьте вашу почту.";
                    } else {
                        $errors[] = "Не удалось отправить письмо. Попробуйте позже.";
                    }
                } else {
                    $errors[] = "Пользователь с таким email не найден";
                }
            } catch (PDOException $e) {
                $errors[] = "Ошибка при обработке запроса: " . $e->getMessage();
            }
        }
    }
    // Обработка формы регистрации
    else {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        
        // Валидация
        if (empty($firstName)) $errors[] = "Имя обязательно для заполнения";
        if (empty($email)) $errors[] = "Email обязателен для заполнения";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Некорректный email";
        if (empty($password)) $errors[] = "Пароль обязателен для заполнения";
        if (strlen($password) < 6) $errors[] = "Пароль должен содержать минимум 6 символов";
        if ($password !== $confirmPassword) $errors[] = "Пароли не совпадают";
        
        if (empty($errors)) {
            try {
                // Проверка email
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $errors[] = "Пользователь с таким email уже существует";
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    $pdo->beginTransaction();
                    
                    // Добавление пользователя
                    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$firstName, $lastName, $email, $phone, $hashedPassword]);
                    
                    $user_id = $pdo->lastInsertId();
                    
                    // Назначение роли
                    $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, 3)");
                    $stmt->execute([$user_id]);
                    
                    $pdo->commit();
                    
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['email'] = $email;
                    
                    header("Location: account.php");
                    exit();
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = "Ошибка регистрации: " . $e->getMessage();
                error_log("Registration Error: " . $e->getMessage());
            }
        }
    }
}

// Если это AJAX-запрос, возвращаем JSON с ошибками
if ($isAjax) {
    die(json_encode(['errors' => $errors]));
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $showForgotPassword ? 'Восстановление пароля' : 'Регистрация' ?> | HAMSTER</title>
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <?php if ($showForgotPassword): ?>
            <h2>Восстановление пароля</h2>
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message">
                    <?= $success ?>
                </div>
            <?php else: ?>
                <form method="POST" action="register.php?forgot_password=1">
                    <div class="form-group">
                        <label for="forgot_email">Email</label>
                        <input type="email" id="forgot_email" name="forgot_email" required>
                        <p class="hint">Введите email, указанный при регистрации. Мы отправим вам ссылку для восстановления пароля.</p>
                    </div>
                    <button type="submit" class="btn">Отправить инструкции</button>
                    <p><a href="register.php">Вернуться к регистрации</a></p>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <h2>Регистрация</h2>
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="register.php">
                <div class="form-group">
                    <label for="first_name">Имя</label>
                    <input type="text" id="first_name" name="first_name" required value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="last_name">Фамилия</label>
                    <input type="text" id="last_name" name="last_name" value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Подтвердите пароль</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn">Зарегистрироваться</button>
                <p>Уже есть аккаунт? <a href="login.php">Войдите</a></p>
                <p><a href="register.php?forgot_password=1">Забыли пароль?</a></p>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>