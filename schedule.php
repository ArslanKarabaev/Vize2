<?php
session_start();
include 'db_connection.php'; // Заменить на свой путь к файлу подключения

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = $_POST['schedule_id'];
    $student_id = $_SESSION['user_id']; // Предполагается, что student_id хранится в сессии

    // Проверка и запись
    $stmt = $pdo->prepare("
        SELECT doctor_id 
        FROM schedules 
        WHERE id = :schedule_id
    ");
    $stmt->execute([':schedule_id' => $schedule_id]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($schedule) {
        $stmt = $pdo->prepare("
            INSERT INTO appointments (student_id, doctor_id, schedule_id) 
            VALUES (:student_id, :doctor_id, :schedule_id)
        ");
        $stmt->execute([
            ':student_id' => $student_id,
            ':doctor_id' => $schedule['doctor_id'],
            ':schedule_id' => $schedule_id
        ]);
        echo "Вы успешно записались на прием!";
    } else {
        echo "Выбранный график не найден.";
    }
}
?>

<?php
$title = "График врачей";
ob_start();
?>

<h2>График работы врачей</h2>
<!-- Список графиков -->
<ul>
    <?php foreach ($schedules as $schedule): ?>
        <li>
            <?= htmlspecialchars($schedule['doctor_name']) ?> — 
            <?= htmlspecialchars($schedule['day']) ?>: 
            <?= htmlspecialchars($schedule['time_from']) ?> - 
            <?= htmlspecialchars($schedule['time_to']) ?>
        </li>
    <?php endforeach; ?>
</ul>

<a href="index.php" class="button">Вернуться на главную</a>

<?php
$content = ob_get_clean();
include 'template.php';
?>



<!-- HTML форма записи -->
<form method="POST">
    <label>Выберите график:</label>
    <select name="schedule_id">
        <?php foreach ($schedules as $schedule): ?>
            <option value="<?= $schedule['id']; ?>">
                <?= htmlspecialchars($schedule['doctor_name']); ?> - 
                <?= htmlspecialchars($schedule['day']); ?> 
                (<?= htmlspecialchars($schedule['time_from']); ?> - <?= htmlspecialchars($schedule['time_to']); ?>)
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Записаться</button>
</form>