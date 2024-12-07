<?php
session_start();
include 'db_connection.php'; // Подключение к базе данных

// Проверка, что пользователь врач
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: auth.php');
    exit();
}

$doctor_id = $_SESSION['user_id'];

// Получение текущего графика врача
$stmt = $pdo->prepare("SELECT id, day, time_from, time_to, is_day_off FROM schedules WHERE doctor_id = :doctor_id");
$stmt->execute([':doctor_id' => $doctor_id]);
$schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обновление графика
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($schedule as $day_schedule) {
        $schedule_id = $day_schedule['id'];
        $is_day_off = isset($_POST["is_day_off_$schedule_id"]) ? 1 : 0;

        if ($is_day_off) {
            // Если день выходной, обнуляем время
            $stmt = $pdo->prepare("UPDATE schedules SET is_day_off = :is_day_off, time_from = NULL, time_to = NULL WHERE id = :id");
            $stmt->execute([
                ':is_day_off' => $is_day_off,
                ':id' => $schedule_id
            ]);
        } else {
            $time_from = $_POST["time_from_$schedule_id"];
            $time_to = $_POST["time_to_$schedule_id"];

            // Проверка на валидность времени
            if ($time_from >= $time_to) {
                echo "Время начала должно быть раньше времени окончания для дня ID $schedule_id!";
                exit();
            }

            $stmt = $pdo->prepare("
                UPDATE schedules 
                SET is_day_off = :is_day_off, time_from = :time_from, time_to = :time_to 
                WHERE id = :id
            ");
            $stmt->execute([
                ':is_day_off' => $is_day_off,
                ':time_from' => $time_from,
                ':time_to' => $time_to,
                ':id' => $schedule_id
            ]);
        }
    }
    echo "График успешно обновлен!";
    header("Location: update_schedule.php");
    exit();
}
?>

<?php
$content = ob_get_clean();
include 'template.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать график</title>
    <script>    
        function toggleTimeFields(scheduleId) {
            const isDayOff = document.getElementById(`is_day_off_${scheduleId}`).checked;
            const timeFields = document.getElementById(`time_fields_${scheduleId}`);
            if (isDayOff) {
                timeFields.style.display = 'none';
            } else {
                timeFields.style.display = 'block';
            }
        }
    </script>
</head>
<body>
<h1>Редактировать график</h1>
<form method="POST">
    <?php foreach ($schedule as $day_schedule): ?>
        <h3><?= htmlspecialchars($day_schedule['day']); ?></h3>

        <!-- Чекбокс для выходного дня -->
        <label for="is_day_off_<?= $day_schedule['id']; ?>">
            <input type="checkbox" 
                   name="is_day_off_<?= $day_schedule['id']; ?>" 
                   id="is_day_off_<?= $day_schedule['id']; ?>" 
                   <?= $day_schedule['is_day_off'] ? 'checked' : ''; ?>
                   onchange="toggleTimeFields('<?= $day_schedule['id']; ?>')">
            Выходной день
        </label>
        <br><br>

        <!-- Поля для ввода времени -->
        <div id="time_fields_<?= $day_schedule['id']; ?>" style="display: <?= $day_schedule['is_day_off'] ? 'none' : 'block'; ?>">
            <label for="time_from_<?= $day_schedule['id']; ?>">Время начала:</label>
            <input type="time" 
                   name="time_from_<?= $day_schedule['id']; ?>" 
                   id="time_from_<?= $day_schedule['id']; ?>" 
                   value="<?= htmlspecialchars($day_schedule['time_from']); ?>">
            <br><br>
            <label for="time_to_<?= $day_schedule['id']; ?>">Время окончания:</label>
            <input type="time" 
                   name="time_to_<?= $day_schedule['id']; ?>" 
                   id="time_to_<?= $day_schedule['id']; ?>" 
                   value="<?= htmlspecialchars($day_schedule['time_to']); ?>">
            <br><br>
        </div>
    <?php endforeach; ?>
    <button type="submit">Сохранить изменения</button>
</form>
</body>
</html>
