<?php
session_start();
include 'db_connection.php'; // Подключение к базе данных

// Проверка роли студента
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: auth.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// Проверка наличия медицинской карточки
$stmt = $pdo->prepare("SELECT id FROM medical_cards WHERE student_id = :student_id");
$stmt->execute([':student_id' => $student_id]);
$card = $stmt->fetch(PDO::FETCH_ASSOC);

// Если карточки нет, создаем
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$card) {
    $stmt = $pdo->prepare("INSERT INTO medical_cards (student_id) VALUES (:student_id)");
    $stmt->execute([':student_id' => $student_id]);
    header('Location: create_medical_card.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Медицинская карточка</title>
</head>
<body>
<h1>Моя медицинская карточка</h1>

<?php if ($card): ?>
    <p>Ваша медицинская карточка уже создана. ID: <?= htmlspecialchars($card['id']); ?></p>
<?php else: ?>
    <p>У вас пока нет медицинской карточки.</p>
    <form method="POST">
        <button type="submit">Создать медицинскую карточку</button>
    </form>
<?php endif; ?>

</body>
</html>
