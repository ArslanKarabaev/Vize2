<?php
session_start();
include 'db_connection.php'; // Подключение к базе данных

// Проверка, что пользователь врач
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: auth.php');
    exit();
}

$doctor_id = $_SESSION['user_id'];

// Получение записей студентов
$stmt = $pdo->prepare("
    SELECT 
        a.id AS appointment_id,
        u.name AS student_name,
        s.day AS appointment_date,
        s.time_from AS start_time,
        s.time_to AS end_time
    FROM appointments a
    JOIN users u ON a.student_id = u.id
    JOIN schedules s ON a.schedule_id = s.id
    WHERE a.doctor_id = :doctor_id
    ORDER BY s.day, s.time_from
");
$stmt->execute([':doctor_id' => $doctor_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Записи на прием</title>
</head>
<body>
<h1>Записи студентов на прием</h1>

<?php if (empty($appointments)): ?>
    <p>На данный момент нет записей.</p>
<?php else: ?>
    <table border="1">
        <thead>
            <tr>
                <th>Имя студента</th>
                <th>Дата</th>
                <th>Время</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?= htmlspecialchars($appointment['student_name']); ?></td>
                    <td><?= htmlspecialchars($appointment['appointment_date']); ?></td>
                    <td><?= htmlspecialchars($appointment['start_time'] . ' - ' . $appointment['end_time']); ?></td>
                    <td>
                        <a href="view_medical_card.php?student_id=<?= $appointment['student_id']; ?>">Просмотр медкарты</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</body>
</html>
