<?php
// Подключение к базе данных
include 'db_connection.php'; // Заменить на свой путь к файлу подключения

// Запрос для получения графиков
$stmt = $pdo->query("
    SELECT schedules.id, users.name AS doctor_name, schedules.day, schedules.time_from, schedules.time_to 
    FROM schedules
    JOIN users ON schedules.doctor_id = users.id
    WHERE users.role = 'doctor'
    
");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>График врачей</title>
</head>
<body>

<h1>График врачей</h1>

<!-- Форма записи на прием -->
<form method="POST" action="schedule.php">
    <label for="schedule_id">Выберите график:</label>
    <select name="schedule_id" id="schedule_id" required>
        <?php foreach ($schedules as $schedule): ?>
            <option value="<?= $schedule['id']; ?>">
                <?= htmlspecialchars($schedule['doctor_name']); ?> - 
                <?= htmlspecialchars($schedule['day']); ?> 
                (<?= htmlspecialchars($schedule['time_from']); ?> - <?= htmlspecialchars($schedule['time_to']); ?>)
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Записаться на прием</button>
</form>

</body>
</html>
