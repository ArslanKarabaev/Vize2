<?php
session_start();
if ($_SESSION['role'] !== 'doctor') {
    header("Location: auth.php");
    exit;
}
echo "Добро пожаловать, " . htmlspecialchars($_SESSION['name']) . "! Это ваш кабинет врача.";
?>
