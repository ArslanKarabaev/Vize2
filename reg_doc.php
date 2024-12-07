<?php
session_start();
include 'db_connection.php'; // Подключение к базе данных

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $speciality = $_POST['speciality'];
    $phone = $_POST['phone'];
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'doctor';

    // Проверка уникальности email
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM doctor WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        $error = "Email уже зарегистрирован.";
    } else {
        // Вставка данных в таблицу
        $stmt = $pdo->prepare("
            INSERT INTO doctor (name, surname, speciality, phone, birthdate, gender, email, password, role) 
            VALUES (:name, :surname, :speciality, :phone, :birthdate, :gender, :email, :password, :role)
        ");
        $stmt->execute([
            ':name' => $name,
            ':surname' => $surname,
            ':speciality' => $speciality,
            ':phone' => $phone,
            ':birthdate' => $birthdate,
            ':gender' => $gender,
            ':email' => $email,
            ':password' => $password,
            ':role' => $role
        ]);

        header("Location: auth.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каттоо</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #0A2B9B 32%, #030F35 100%);
        }

        .container {
            background-color: rgba(255, 255, 255, 0.6);
            width: 800px;
            height: 600px;
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

        .form-group .radio-group {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
        }

        .form-group .radio-group label {
            display: inline-flex;
            align-items: center;
        }

        .form-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .form-row .half-width {
            width: 48%;
            border: none;
        }

        /* Изменения для расположения радиокнопок и текста в одну строку */
        .radio-group {
            display: inline-flex;
            gap: 15px;
            align-items: center;
            justify-content: flex-start;
        }

        .radio-group input {
            margin-right: 5px;
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
        <h1>Каттоо</h1>
        <form method="POST">
            <div class="form-row">
                <div class="form-group half-width">
                    <label for="name">Аты</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group half-width">
                    <label for="surname">Фамилиясы</label>
                    <input type="text" id="surname" name="surname" required>
                </div>
            </div>
            <div class="form-group">
                <label for="student-number">Кесип</label>
                <input type="text" id="speciality" name="speciality" required>
            </div>
            <div class="form-group">
                <label for="phone">Телефон Номери</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            <div class="form-row">
                <div class="form-group half-width">
                    <label for="birthdate">Туулган Күнү</label>
                    <input type="date" id="birthdate" name="birthdate" required>
                </div>
                <div class="form-group half-width">
                    <label>Жыныс</label>
                    <div class="radio-group">
                        <label><input type="radio" name="gender" value="female" required> Кыз</label>
                        <label><input type="radio" name="gender" value="male" required> Эркек</label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="email">Почта</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit">Катталуу</button>
            </div>
            <div class="login-link">
            Эгерде сизде аккаунт бар болсо, <a href="auth.php">кирүү</a>.
            </div>
        </form>
    </div>
</body>
</html>