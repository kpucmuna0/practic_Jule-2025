<?php
require_once 'config.php';
require_once 'auth_functions.php';
session_start();

// Проверка AJAX-запроса
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (isset($_SESSION['user_id'])) {
    // Проверяем роль пользователя
    $user = getCurrentUserWithRole();
    if ($user && $user['role_name'] === 'admin') {
        $redirect = '/index.php'; // Абсолютный путь к админ-панели
    } else {
        $redirect = '/account.php'; // Абсолютный путь к ЛК пользователя
    }

    if ($isAjax) {
        die(json_encode(['success' => true, 'redirect' => $redirect]));
    } else {
        header("Location: $redirect");
        exit();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Все поля обязательны для заполнения';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u
                                 JOIN user_roles ur ON u.user_id = ur.user_id
                                 JOIN role r ON ur.role_id = r.id_role
                                 WHERE u.email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'Пользователь с таким email не найден';
            } elseif (!password_verify($password, $user['password_hash'])) {
                $error = 'Неверный пароль';
            } else {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role_name'];

                // Перенаправление
                $redirect = ($user['role_name'] === 'admin') ? '/index.php' : '/account.php';
                
                if ($isAjax) {
                    die(json_encode([
                        'success' => true,
                        'redirect' => $redirect
                    ]));
                } else {
                    header("Location: $redirect");
                    exit();
                }
            }
        } catch (PDOException $e) {
            $error = 'Ошибка сервера: ' . $e->getMessage();
        }
    }
}

if ($isAjax) {
    die(json_encode(['success' => false, 'error' => $error]));
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход | HAMSTER</title>
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <h2>Вход в личный кабинет</h2>
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php" id="loginForm">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Войти</button>
            <p>Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a></p>
        </form>
    </div>
</body>
</html>
