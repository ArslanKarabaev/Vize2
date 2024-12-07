<?php
session_start();
include 'db_connection.php'; // Подключение к базе данных

// Проверка, что пользователь студент
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: auth.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// Получение данных студента
$stmt = $pdo->prepare("SELECT * FROM student WHERE id = :student_id");
$stmt->execute([':student_id' => $student_id]);
$student_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Обновление личных данных
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student_data'])) {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $stmt = $pdo->prepare("UPDATE student SET name = :name, surname = :surname, phone = :phone, email = :email, department = :department WHERE id = :student_id");
    $stmt->execute([
        ':name' => $name,
        ':surname' => $surname,
        ':phone' => $phone,
        ':email' => $email,
        ':department' => $department,
        ':student_id' => $student_id
    ]);
    header('Location: student_dashboard.php');
    exit();
}

// Получение медкарты студента
$stmt = $pdo->prepare("SELECT id FROM medical_cards WHERE student_id = :student_id");
$stmt->execute([':student_id' => $student_id]);
$medical_card = $stmt->fetch(PDO::FETCH_ASSOC);

if ($medical_card) {
    $medical_card_id = $medical_card['id'];
    $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE medical_card_id = :medical_card_id");
    $stmt->execute([':medical_card_id' => $medical_card_id]);
    $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Создание медкарты
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_medical_card'])) {
    $stmt = $pdo->prepare("INSERT INTO medical_cards (student_id) VALUES (:student_id)");
    $stmt->execute([':student_id' => $student_id]);
    header('Location: student_dashboard.php');
    exit();
}

// Добавление новой болезни
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_medical_record'])) {
    $disease_name = $_POST['disease_name'];
    $disease_date = $_POST['disease_date'];
    $medications = $_POST['medications'];
    $stmt = $pdo->prepare("INSERT INTO medical_records (medical_card_id, disease_name, disease_date, medications) 
                           VALUES (:medical_card_id, :disease_name, :disease_date, :medications)");
    $stmt->execute([
        ':medical_card_id' => $medical_card_id,
        ':disease_name' => $disease_name,
        ':disease_date' => $disease_date,
        ':medications' => $medications
    ]);
    header('Location: student_dashboard.php');
    exit();
}

// Получение списка врачей
$stmt = $pdo->prepare("SELECT * FROM doctor");
$stmt->execute();
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка записи на прием
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $doctor_id = $_POST['doctor_id'];
    $appointment_time = $_POST['appointment_time'];

    // Проверка доступности времени
    $stmt = $pdo->prepare("SELECT COUNT(*) 
    FROM appointments 
    WHERE doctor_id = :doctor_id 
      AND appointment_time >= :appointment_time 
      AND appointment_time < TIMESTAMPADD(HOUR, 1, :appointment_time)");
$stmt->execute([':doctor_id' => $doctor_id, ':appointment_time' => $appointment_time]);
$is_taken = $stmt->fetchColumn();


if ($is_taken > 0) {
    // Если время уже занято
    $appointment_error = "Это время уже занято. Пожалуйста, выберите другое.";
} else {
    // Если время свободно, добавляем запись
    $stmt = $pdo->prepare("INSERT INTO appointments (student_id, doctor_id, appointment_time) 
                           VALUES (:student_id, :doctor_id, :appointment_time)");
    $stmt->execute([
        ':student_id' => $student_id,
        ':doctor_id' => $doctor_id,
        ':appointment_time' => $appointment_time
    ]);
    header('Location: student_dashboard.php');
    exit();
}

}

// Получение записей на прием
$stmt = $pdo->prepare("SELECT a.id, a.appointment_time, d.name AS doctor_name, d.surname AS doctor_surname, d.speciality 
                       FROM appointments a
                       JOIN doctor d ON a.doctor_id = d.id
                       WHERE a.student_id = :student_id");
$stmt->execute([':student_id' => $student_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);


//Загрузка пдф файла

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_file'])) {
    $medical_record_id = $_POST['medical_record_id'];
    
    // Проверяем существование medical_record_id в таблице medical_records
    $stmt = $pdo->prepare("SELECT id FROM medical_records WHERE id = :medical_record_id");
    $stmt->execute([':medical_record_id' => $medical_record_id]);
    $medical_record_id = $stmt->fetchColumn();
    
    if (!$medical_record_id) {
        echo $medical_record_id;
        echo "Ошибка: Медицинская запись не существует.";
        exit();
    }

    // Если файл загружен без ошибок
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0) {
        $file_name = basename($_FILES['pdf_file']['name']);
        $file_tmp = $_FILES['pdf_file']['tmp_name'];
        $upload_dir = 'uploads/';

        // Создаем папку, если она отсутствует
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_path = $upload_dir . uniqid() . '-' . $file_name;

        // Перемещаем загруженный файл
        if (move_uploaded_file($file_tmp, $file_path)) {
            // Вставляем данные в таблицу medical_files
            $stmt = $pdo->prepare("INSERT INTO medical_files (medical_record_id, file_name, file_path) 
                                   VALUES (:medical_record_id, :file_name, :file_path)");
            $stmt->execute([
                ':medical_record_id' => $medical_record_id,
                ':file_name' => $file_name,
                ':file_path' => $file_path
            ]);
            echo "Файл успешно загружен.";
            header("Location: student_dashboard.php");
            exit();
        } else {
            echo "Ошибка при загрузке файла.";
        }
    } else {
        echo "Ошибка: Файл не выбран или произошла ошибка при загрузке.";
    }
}



// Логика удаления файла
if (isset($_GET['delete_file_id'])) {
    $file_id = $_GET['delete_file_id'];

    // Получение информации о файле
    $stmt = $pdo->prepare("SELECT file_path FROM medical_files WHERE id = :file_id");
    $stmt->execute([':file_id' => $file_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        // Удаление файла с сервера
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }

        // Удаление записи из базы данных
        $stmt = $pdo->prepare("DELETE FROM medical_files WHERE id = :file_id");
        $stmt->execute([':file_id' => $file_id]);

        header("Location: student_dashboard.php");
        exit();
    }
}




?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет студента</title>
</head>
<body>
    <h1>Личный кабинет студента</h1>

    <!-- Личные данные -->
    <h2>Личные данные</h2>
<form method="POST" id="personal-data-form">
    <div>
        <label for="name">Имя:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($student_data['name']) ?>" disabled id="name">
    </div>
    <div>
        <label for="surname">Фамилия:</label>
        <input type="text" name="surname" value="<?= htmlspecialchars($student_data['surname']) ?>" disabled id="surname">
    </div>
    <div>
        <label for="phone">Телефон:</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($student_data['phone']) ?>" disabled id="phone">
    </div>
    <div>
        <label for="email">Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($student_data['email']) ?>" disabled id="email">
    </div>
    <div>
        <label for="department">Department:</label>
        <input type="text" name="department" value="<?= htmlspecialchars($student_data['department']) ?>" disabled id="department">
    </div>

    <button type="button" id="edit-button">Обновить</button>
    <button type="submit" name="update_student_data" id="save-button" style="display: none;">Сохранить изменения</button>
</form>

<script>
    // JavaScript для управления состоянием полей формы
    const editButton = document.getElementById('edit-button');
    const saveButton = document.getElementById('save-button');
    const form = document.getElementById('personal-data-form');
    const inputs = form.querySelectorAll('input');

    editButton.addEventListener('click', () => {
        inputs.forEach(input => input.disabled = false); // Разблокировать поля
        editButton.style.display = 'none'; // Скрыть кнопку "Обновить"
        saveButton.style.display = 'inline-block'; // Показать кнопку "Сохранить изменения"
    });
</script>


    <!-- Запись на прием -->
    <h2>Запись на прием к врачу</h2>
    <form method="POST">
        <label>Врач:
            <select name="doctor_id" required>
                <?php foreach ($doctors as $doctor): ?>
                    <option value="<?= $doctor['id'] ?>"><?= htmlspecialchars($doctor['name'] . ' ' . $doctor['surname'] . ' (' . $doctor['speciality'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </label><br>
        <label>Время: <input type="datetime-local" name="appointment_time" required></label><br>
        <button type="submit" name="book_appointment">Записаться</button>
    </form>
    <?php if (isset($appointment_error)): ?>
        <p style="color: red;"><?= $appointment_error ?></p>
    <?php endif; ?>

    <!-- Мои записи -->
    <h2>Мои записи</h2>
    <?php if ($appointments): ?>
        <ul>
            <?php foreach ($appointments as $appointment): ?>
                <li><?= htmlspecialchars($appointment['appointment_time']) ?> - 
                    <?= htmlspecialchars($appointment['doctor_name'] . ' ' . $appointment['doctor_surname']) ?> (<?= htmlspecialchars($appointment['speciality']) ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Записей нет</p>
    <?php endif; ?>

    <!-- Медицинская карта -->
    <h2>Медицинская карта</h2>
    <?php if ($medical_card): ?>
        <ul>
            <?php foreach ($medical_records as $record): ?>
                <li>
                    <strong><?= htmlspecialchars($record['disease_name']) ?></strong> (<?= htmlspecialchars($record['disease_date']) ?>)
                    <br>Лекарства: <?= htmlspecialchars($record['medications']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <form method="POST">
            <h3>Добавить запись о болезни</h3>
            <label>Название болезни: <input type="text" name="disease_name" required></label><br>
            <label>Дата: <input type="date" name="disease_date" required></label><br>
            <label>Лекарства(необязательно): <input type="text" name="medications"></label><br>
            <button type="submit" name="add_medical_record">Добавить</button>
        </form>
    <?php else: ?>
        <p>Медицинская карта отсутствует.</p>
        <form method="POST">
            <button type="submit" name="create_medical_card">Создать медицинскую карту</button>
        </form>
    <?php endif; ?>

    <!-- Форма для загрузки файла -->
    <form method="POST" enctype="multipart/form-data">
    <label for="pdf_file">Выберите файл (PDF):</label>
    <input type="file" name="pdf_file" accept=".pdf" required>
    <input type="hidden" name="medical_record_id" value="<?= htmlspecialchars($record['id']) ?>">
    <button type="submit" name="upload_file">Загрузить файл</button>
</form>






<br>

<!-- Список загруженных файлов -->
<?php foreach ($medical_records as $record): ?>
    <div class="record">
        <h3>Запись #<?= htmlspecialchars($record['id']) ?></h3>
        
        <!-- Отображение файлов, связанных с записью -->
        <?php
        $stmt = $pdo->prepare("SELECT * FROM medical_files WHERE medical_record_id = :record_id");
        $stmt->execute([':record_id' => $record['id']]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <?php if ($files): ?>
            <h4>Файлы:</h4>
            <ul>
                <?php foreach ($files as $file): ?>
                    <li>
                        <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank"><?= htmlspecialchars($file['file_name']) ?></a>
                        <a href="?delete_file_id=<?= $file['id'] ?>" onclick="return confirm('Вы уверены, что хотите удалить этот файл?')">Удалить</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Файлы отсутствуют</p>
        <?php endif; ?>
    </div>
<?php endforeach; ?>



</body>
</html>
