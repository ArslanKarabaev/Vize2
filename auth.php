<?php
session_start();
include 'db_connection.php'; // Подключение к базе данных

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Проверка в таблице doctor
    $stmtDoctor = $pdo->prepare("SELECT id, name, password, role FROM doctor WHERE email = :email");
    $stmtDoctor->execute([':email' => $email]);
    $doctor = $stmtDoctor->fetch(PDO::FETCH_ASSOC);

    if ($doctor && password_verify($password, $doctor['password'])) {
        // Если пользователь найден в таблице doctor
        $_SESSION['user_id'] = $doctor['id'];
        $_SESSION['name'] = $doctor['name'];
        $_SESSION['role'] = 'doctor';//$doctor['role'];

        header("Location: doctor_dashboard.php");
        exit();
    }

    // Проверка в таблице student
    $stmtStudent = $pdo->prepare("SELECT id, name, password FROM student WHERE email = :email");
    $stmtStudent->execute([':email' => $email]);
    $student = $stmtStudent->fetch(PDO::FETCH_ASSOC);

    if ($student && password_verify($password, $student['password'])) {
        // Если пользователь найден в таблице student
        $_SESSION['user_id'] = $student['id'];
        $_SESSION['name'] = $student['name'];
        $_SESSION['role'] = 'student';

        header("Location: student_dashboard.php");
        exit();
    }

    $error = "Неверный email или пароль.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кирүү</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(-135deg, #0A2B9B 31%, #030F35 100%);
        }

        .container {
            background-color: rgba(255, 255, 255, 0.6);
            width: 800px;
            height: 300px;
            padding: 20px;
            border-radius: 23px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            
        }

        .container h1 {
            margin-bottom: 20px;
            font-size: 26px;
            color: #071F6F;
        }

        .form-group {
            margin-bottom: 15px;
            padding-left: 20px;
            padding-right: 25px;
        }

        .form-group label {
            display: block;
            font-weight: normal;
            text-align: left;
            color: #071F6F;
        }

        .form-group input{
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 13px;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
            background-color: rgba(255, 255, 255, 0.8);
        }

        .form-group button {
            width: 140px;
            padding: 10px;
            background-color: #071F6F;
            color: white;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-size: 15px;
            font-weight: bold;
            font-family: 'Montserrat',sans-serif;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border 0.3s ease; /* плавные переходы */
        }

        .form-group button:hover {
            background-color: #0c2165;
            transform: scale(1.05); /* Увеличение кнопки */
            box-shadow: 0 0 15px rgba(10, 43, 155, 0.7); /* Эффект свечения */
            border: 3px solid #071F6F; /* Подсветка бордера */
        }
    </style>
</head>
<body>
    <div class="container"> 
        <h1>Кирүү</h1>
        <form method="POST">
            <div class="form-group">
                <label for="email">Почта</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit">Кирүү</button>
            </div>
            <p>Нет учетной записи? <a href="reg_stud.php">Зарегистрироваться как студент</a> или <a href="reg_doc.php">как доктор</a>.</p>
        </form>
    </div>
</body>
</html>