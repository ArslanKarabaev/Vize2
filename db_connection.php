<?php
$host = 'localhost';
$dbname = 'university_clinic';
$username = 'root';  // замените на свой логин
$password = '';      // замените на свой пароль

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Ошибка подключения: " . $e->getMessage();
}
?>
