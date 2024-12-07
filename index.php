<?php
session_start();

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php"); // Если не авторизован, перенаправляем на страницу входа
    exit;
}

// Проверяем роль пользователя
$user_role = $_SESSION['role']; // Предполагаем, что роль сохраняется в сессии

// Заголовок страницы
$title = "Главная страница";
ob_start();
?>

<h1>Добро пожаловать в медицинскую поликлинику университета!</h1>
<p>Здравствуйте, <?= isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'гость'; ?>!</p>

<?php if ($user_role === 'student'): ?>
    <p><a href="view_schedules.php" class="button">Записаться на приём</a></p>
    <p><a href="medical_card.php" class="button">Моя медицинская карта</a></p>
<?php elseif ($user_role === 'doctor'): ?>
    <p><a href="doctor_appointments.php" class="button">Мои записи</a></p>
    <p><a href="view_schedules_doc.php" class="button">Мой график</a></p>
<?php endif; ?>

<p><a href="logout.php" class="button">Выход</a></p>

<?php
$content = ob_get_clean();
include 'template.php';
?>
