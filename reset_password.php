<?php
require_once 'config.php';
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Проверка подключения к БД
try {
    $test = $pdo->query("SELECT 1");
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

$errors = [];
$success = '';
$tokenValid = false;
$email = '';

// Проверка токена
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Проверяем токен в базе данных
        $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $resetRequest = $stmt->fetch();
        
        if ($resetRequest) {
            $email = $resetRequest['email'];
            $expiresAt = new DateTime($resetRequest['expires_at']);
            $now = new DateTime();
            
            if ($now < $expiresAt) {
                $tokenValid = true;
            } else {
                $errors[] = "Срок действия ссылки для сброса пароля истек";
            }
        } else {
            $errors[] = "Неверная ссылка для сброса пароля";
        }
    } catch (PDOException $e) {
        $errors[] = "Ошибка при проверке токена: " . $e->getMessage();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка формы сброса пароля
    $token = $_POST['token'] ?? '';
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    if (empty($password)) {
        $errors[] = "Пароль обязателен для заполнения";
    } elseif (strlen($password) < 6) {
        $errors[] = "Пароль должен содержать минимум 6 символов";
    } elseif ($password !== $confirmPassword) {
        $errors[] = "Пароли не совпадают";
    }
    
if (empty($errors)) {
    try {
        // Проверяем токен еще раз перед изменением пароля
        $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $resetRequest = $stmt->fetch();
        
        if ($resetRequest) {
            $email = $resetRequest['email'];
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Обновляем пароль пользователя
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $stmt->execute([$hashedPassword, $email]);
            
            // Удаляем использованный токен
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
            
            $success = "Пароль успешно изменен! Теперь вы можете <a href='login.php'>войти</a> с новым паролем.";
        } else {
            $errors[] = "Неверный токен для сброса пароля";
        }
    } catch (PDOException $e) {
        $errors[] = "Ошибка при сбросе пароля: " . $e->getMessage();
    }
}
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сброс пароля | HAMSTER</title>
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <h2>Сброс пароля</h2>
        
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
        <?php elseif (isset($_GET['token']) && $tokenValid): ?>
            <form method="POST" action="reset_password.php">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token']) ?>">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" value="<?= htmlspecialchars($email) ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="password">Новый пароль</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Подтвердите новый пароль</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn">Сменить пароль</button>
            </form>
        <?php elseif (!isset($_GET['token'])): ?>
            <div class="info-message">
                <p>Для сброса пароля перейдите по ссылке из письма, которое мы вам отправили.</p>
                <p>Если вы не получали письмо, проверьте папку "Спам" или <a href="register.php?forgot_password=1">запросите новое письмо</a>.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>