<?php
session_start();
include 'db_connection.php'; // Подключение к базе данных

// Получаем ID студента из сессии
$student_id = $_SESSION['user_id'];

// Запрос для получения записей студента
$stmt = $pdo->prepare("
    SELECT appointments.id, users.name AS doctor_name, schedules.day, schedules.time_from, schedules.time_to 
    FROM appointments
    JOIN schedules ON appointments.schedule_id = schedules.id
    JOIN users ON schedules.doctor_id = users.id
    WHERE appointments.student_id = :student_id
");
$stmt->execute([':student_id' => $student_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Отмена записи
if (isset($_GET['cancel'])) {
    $appointment_id = $_GET['cancel'];

    // Получаем дату приема для выбранной записи
    $stmt = $pdo->prepare("
        SELECT schedules.day 
        FROM appointments
        JOIN schedules ON appointments.schedule_id = schedules.id
        WHERE appointments.id = :appointment_id AND appointments.student_id = :student_id
    ");
    $stmt->execute([
        ':appointment_id' => $appointment_id,
        ':student_id' => $student_id
    ]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($appointment) {
        // Получаем текущую дату
        $current_date = date('Y-m-d');

        // Сравниваем текущую дату с датой записи (включая день)
        if ($appointment['day'] > $current_date) {
            // Запись можно отменить
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = :appointment_id AND student_id = :student_id");
            $stmt->execute([
                ':appointment_id' => $appointment_id,
                ':student_id' => $student_id
            ]);
            echo "Запись отменена!";
        } else {
            // Если дата приема уже прошла или остался менее одного дня
            echo "Отмена записи возможна только за день до приема.";
        }
    } else {
        echo "Запись не найдена.";
    }

    // Перенаправляем назад, чтобы обновить страницу
    header("Location: my_appointments.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои записи</title>
</head>
<body>

<h1>Мои записи на прием</h1>

<?php if (count($appointments) > 0): ?>
    <ul>
        <?php foreach ($appointments as $appointment): ?>
            <li>
                <?= htmlspecialchars($appointment['doctor_name']); ?> - 
                <?= htmlspecialchars($appointment['day']); ?> 
                (<?= htmlspecialchars($appointment['time_from']); ?> - <?= htmlspecialchars($appointment['time_to']); ?>)
                
                <!-- Проверка, можно ли отменить запись -->
                <?php
                // Получаем текущую дату
                $current_date = date('Y-m-d');
                $can_cancel = ($appointment['day'] > $current_date); // Разница должна быть хотя бы 1 день
                ?>

                <?php if ($can_cancel): ?>
                    <a href="?cancel=<?= $appointment['id']; ?>">Отменить запись</a>
                <?php else: ?>
                    <span>Отмена невозможна</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Записей на прием нет.</p>
<?php endif; ?>

</body>
</html>
