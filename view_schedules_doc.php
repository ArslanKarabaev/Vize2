<?php
session_start();
include 'db_connection.php'; // Подключение к базе данных

// Проверка, что пользователь врач
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: auth.php');
    exit();
}

$doctor_id = $_SESSION['user_id']; // ID врача из сессии

// Получаем график врача
$stmt = $pdo->prepare("
    SELECT id, day, time_from, time_to FROM schedules
    WHERE doctor_id = :doctor_id
    ");
$stmt->execute([':doctor_id' => $doctor_id]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ваш рабочий график</title>
</head>
<body>

<h1>Ваш рабочий график</h1>

<?php if (count($schedules) > 0): ?>
    <table border="1">
        <thead>
            <tr>
                <th>День</th>
                <th>Время начала</th>
                <th>Время окончания</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($schedules as $schedule): ?>
                <tr>
                    <td><?= htmlspecialchars($schedule['day']); ?></td>
                    <td><?= htmlspecialchars($schedule['time_from']); ?></td>
                    <td><?= htmlspecialchars($schedule['time_to']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>У вас нет доступных графиков.</p>
<?php endif; ?>

</body>
</html>
