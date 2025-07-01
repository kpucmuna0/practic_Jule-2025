<?php
session_start();
require_once 'config.php';


$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';


if (empty($email) || empty($password)) {
    die("Заполните все поля");
}


$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    
    
header("Location: account.php"); 
    exit();
} else {
    
    die("Неверный email или пароль");
}
?>