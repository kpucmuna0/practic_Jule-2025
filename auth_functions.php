<?php
require_once 'config.php';


function isLoggedIn() {
    return isset($_SESSION['user_id']);
}


function getCurrentUserWithRole() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u 
                          JOIN user_roles ur ON u.user_id = ur.user_id
                          JOIN role r ON ur.role_id = r.id_role
                          WHERE u.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}


function getCurrentUser() {
    $user = getCurrentUserWithRole();
    if ($user) {
        unset($user['role_name']); 
    }
    return $user;
}


function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: about.html?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

function requireAdmin() {
    requireAuth(); 
    $user = getCurrentUserWithRole();
    if (!$user || $user['role_name'] !== 'admin') {
        header("Location: /account.php"); 
        exit();
    }
}
?>