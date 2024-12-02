<?php
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

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];

    // Проверка данных
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Некорректный email");
    }

    if ($role !== 'student' && $role !== 'doctor') {
        die("Некорректная роль");
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $password,
            ':role' => $role,
        ]);
        echo "Регистрация успешна!";
    } catch (PDOException $e) {
        die("Ошибка: " . $e->getMessage());
    }
}
?>

<!-- HTML форма регистрации -->
<form method="POST">
    <label>ФИО: <input type="text" name="name" required></label><br>
    <label>Email: <input type="email" name="email" required></label><br>
    <label>Пароль: <input type="password" name="password" required></label><br>
    <label>Роль:
        <select name="role">
            <option value="student">Студент</option>
            <option value="doctor">Врач</option>
        </select>
    </label><br>
    <button type="submit">Зарегистрироваться</button>
</form>
