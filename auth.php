<?php
session_start(); // Запуск сессии

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

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Сохранение данных пользователя в сессии
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            // Редирект на страницу в зависимости от роли
            if ($user['role'] === 'student') {
                header("Location: student_dashboard.php");
            } else if ($user['role'] === 'doctor') {
                header("Location: doctor_dashboard.php");
            }
            exit;
        } else {
            $error = "Неверный email или пароль.";
        }
    } catch (PDOException $e) {
        die("Ошибка: " . $e->getMessage());
    }
}
?>

<!-- HTML форма входа -->
<form method="POST">
    <label>Email: <input type="email" name="email" required></label><br>
    <label>Пароль: <input type="password" name="password" required></label><br>
    <button type="submit">Войти</button>
</form>

<?php if (!empty($error)): ?>
    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>
