<?php
session_start();
include 'db_connection.php'; // Подключение к базе данных

// Проверка, что пользователь врач
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: auth.php');
    exit();
}

$doctor_id = $_SESSION['user_id']; // ID врача из сессии

// Проверка наличия графика для врача
$stmt = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE doctor_id = :doctor_id");
$stmt->execute([':doctor_id' => $doctor_id]);
$existing_schedule_count = $stmt->fetchColumn();

// Если графика нет, врач может добавить новый
if ($existing_schedule_count == 0) {
    // Обработка формы для добавления нового графика
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Массив дней недели
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        foreach ($days as $day) {
            // Проверка, является ли день выходным
            $is_day_off = isset($_POST["is_day_off_$day"]) ? true : false;

            // Если день не выходной, добавляем время
            if (!$is_day_off) {
                $time_from = $_POST["time_from_$day"];
                $time_to = $_POST["time_to_$day"];

                // Проверка, чтобы время начала не было позже времени конца
                if ($time_from >= $time_to) {
                    echo "Время начала должно быть раньше времени конца!";
                    exit();
                }

                // Добавление нового графика
                $stmt = $pdo->prepare("
                    INSERT INTO schedules (doctor_id, day, time_from, time_to, is_day_off)
                    VALUES (:doctor_id, :day, :time_from, :time_to, :is_day_off)
                ");
                $stmt->execute([
                    ':doctor_id' => $doctor_id,
                    ':day' => $day,  // Название дня
                    ':time_from' => $time_from,
                    ':time_to' => $time_to,
                    ':is_day_off' => $is_day_off
                ]);
            } else {
                // Если день выходной, добавляем запись с флагом is_day_off
                $stmt = $pdo->prepare("
                    INSERT INTO schedules (doctor_id, day, is_day_off)
                    VALUES (:doctor_id, :day, :is_day_off)
                ");
                $stmt->execute([
                    ':doctor_id' => $doctor_id,
                    ':day' => $day,  // Название дня
                    ':is_day_off' => $is_day_off
                ]);
            }
        }
        echo "График успешно создан!";
    }
} else {
    echo "Вы уже создали свой рабочий график!";
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
    <title>Создать рабочий график</title>
    <script>
        // Функция для управления видимостью времени
        function toggleTimeFields(day) {
            const isDayOff = document.getElementById(`is_day_off_${day}`).checked;
            const timeFields = document.getElementById(`time_fields_${day}`);
            if (isDayOff) {
                timeFields.style.display = 'none';
            } else {
                timeFields.style.display = 'block';
            }
        }
    </script>
</head>
<body>

<h1>Создать рабочий график</h1>

<?php if ($existing_schedule_count == 0): ?>
    <form method="POST">
        <?php 
        // Массив дней недели для отображения
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        foreach ($days as $day): ?>
            <h3><?= $day; ?></h3>
            
            <!-- Чекбокс для выбора выходного дня -->
            <label for="is_day_off_<?= $day; ?>">
                <input type="checkbox" name="is_day_off_<?= $day; ?>" id="is_day_off_<?= $day; ?>" 
                       onchange="toggleTimeFields('<?= $day; ?>')">
                Выходной день
            </label>
            <br><br>

            <!-- Время работы, если день не выходной -->
            <div id="time_fields_<?= $day; ?>" style="display: block;">
                <label for="time_from_<?= $day; ?>">Время начала:</label>
                <input type="time" name="time_from_<?= $day; ?>" id="time_from_<?= $day; ?>">
                <br><br>
                <label for="time_to_<?= $day; ?>">Время окончания:</label>
                <input type="time" name="time_to_<?= $day; ?>" id="time_to_<?= $day; ?>">
                <br><br>
            </div>
        <?php endforeach; ?>

        <button type="submit">Создать график</button>
    </form>
<?php else: ?>
    <p>Вы уже создали свой рабочий график. Вы можете редактировать или обновить его, если необходимо.</p>
<?php endif; ?>

</body>
</html>
