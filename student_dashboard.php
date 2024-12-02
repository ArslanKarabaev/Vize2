<?php
session_start();
if ($_SESSION['role'] !== 'student') {
    header("Location: auth.php");
    exit;
}

// Подключение к базе данных
$host = 'localhost';
$db = 'university_clinic';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Получение списка врачей и их графиков
$stmt = $pdo->query("
    SELECT schedules.*, users.name AS doctor_name 
    FROM schedules 
    JOIN users ON schedules.doctor_id = users.id 
    WHERE users.role = 'doctor' 
    ORDER BY schedules.day, schedules.time_from
");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>График врачей</title>
</head>
<body>
    <h1>Добро пожаловать, <?= htmlspecialchars($_SESSION['name']); ?></h1>
    <h2>График врачей</h2>
    <table border="1">
        <tr>
            <th>Врач</th>
            <th>Дата</th>
            <th>Время</th>
        </tr>
        <?php foreach ($schedules as $schedule): ?>
            <tr>
                <td><?= htmlspecialchars($schedule['doctor_name']); ?></td>
                <td><?= htmlspecialchars($schedule['day']); ?></td>
                <td><?= htmlspecialchars($schedule['time_from']); ?> - <?= htmlspecialchars($schedule['time_to']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
