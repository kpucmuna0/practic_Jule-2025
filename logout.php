<?php
require_once 'config.php';
session_start();

session_destroy();
header("Location: about.html"); // Изменили на booking.php
exit();
?>