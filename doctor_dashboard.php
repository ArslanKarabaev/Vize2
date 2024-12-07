<?php
session_start();
require 'db_connection.php';

// Проверка, авторизован ли врач
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: auth.php');
    exit();
}

$doctor_id = $_SESSION['user_id'];

// Получение данных врача
$stmt = $pdo->prepare("SELECT * FROM doctor WHERE id = :id");
$stmt->execute([':id' => $doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    echo "Ошибка: врач не найден.";
    exit();
}

// Обновление данных врача
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_doctor'])) {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $speciality = $_POST['speciality'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    $stmt = $pdo->prepare("
        UPDATE doctor 
        SET name = :name, surname = :surname, speciality = :speciality, phone = :phone, email = :email 
        WHERE id = :id
    ");
    $stmt->execute([
        ':name' => $name,
        ':surname' => $surname,
        ':speciality' => $speciality,
        ':phone' => $phone,
        ':email' => $email,
        ':id' => $doctor_id
    ]);

    echo "Данные обновлены!";
    header("Refresh:0");
    exit();
}


// Получение расписания врача
$stmt = $pdo->prepare("SELECT * FROM schedules WHERE doctor_id = :doctor_id");
$stmt->execute([':doctor_id' => $doctor_id]);
$schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Инициализация дней недели
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manage_schedule'])) {
    $pdo->beginTransaction();
    try {
        foreach ($days_of_week as $day) {
            $time_from = $_POST['schedule'][$day]['time_from'] ?? null;
            $time_to = $_POST['schedule'][$day]['time_to'] ?? null;
            $is_day_off = isset($_POST['schedule'][$day]['is_day_off']) ? 1 : 0;

            // Проверка, существует ли запись для дня
            $stmt = $pdo->prepare("SELECT id FROM schedules WHERE doctor_id = :doctor_id AND day = :day");
            $stmt->execute([':doctor_id' => $doctor_id, ':day' => $day]);
            $existing_schedule = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_schedule) {
                // Обновление существующего графика
                $stmt = $pdo->prepare("
                    UPDATE schedules 
                    SET time_from = :time_from, time_to = :time_to, is_day_off = :is_day_off
                    WHERE doctor_id = :doctor_id AND day = :day
                ");
                $stmt->execute([
                    ':time_from' => $time_from,
                    ':time_to' => $time_to,
                    ':is_day_off' => $is_day_off,
                    ':doctor_id' => $doctor_id,
                    ':day' => $day
                ]);
            } else {
                // Создание нового графика
                $stmt = $pdo->prepare("
                    INSERT INTO schedules (doctor_id, day, time_from, time_to, is_day_off) 
                    VALUES (:doctor_id, :day, :time_from, :time_to, :is_day_off)
                ");
                $stmt->execute([
                    ':doctor_id' => $doctor_id,
                    ':day' => $day,
                    ':time_from' => $time_from,
                    ':time_to' => $time_to,
                    ':is_day_off' => $is_day_off
                ]);
            }
        }
        $pdo->commit();
        echo "График успешно обновлен!";
        header("Refresh:0");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Ошибка обновления графика: " . $e->getMessage();
    }
}

// Список пациентов на выбранный день
$date_filter = $_GET['date'] ?? date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT a.id AS appointment_id, s.name AS student_name, s.surname AS student_surname, a.appointment_time
    FROM appointments a
    JOIN student s ON a.student_id = s.id
    WHERE a.doctor_id = :doctor_id AND DATE(a.appointment_time) = :date
    ORDER BY a.appointment_time
");
$stmt->execute([':doctor_id' => $doctor_id, ':date' => $date_filter]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);






// Получение информации о студенте
$search_term = $_GET['search'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM student WHERE student_number LIKE :search_term");
$stmt->execute([':search_term' => '%' . $search_term . '%']);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Проверка, выбран ли студент
$student_id = $_GET['student_id'] ?? null;
if ($student_id) {
    // Получение информации о студенте
    $stmt = $pdo->prepare("SELECT * FROM student WHERE id = :student_id");
    $stmt->execute([':student_id' => $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    // Получение медицинской карты студента
    $stmt = $pdo->prepare("SELECT * FROM medical_cards WHERE student_id = :student_id");
    $stmt->execute([':student_id' => $student_id]);
    $medical_card = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$medical_card) {
        // Если карты нет, создаем ее
        $stmt = $pdo->prepare("INSERT INTO medical_cards (student_id) VALUES (:student_id)");
        $stmt->execute([':student_id' => $student_id]);
        $medical_card_id = $pdo->lastInsertId();
    } else {
        $medical_card_id = $medical_card['id'];
    }

    // Получение записей из медицинской карты
    $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE medical_card_id = :medical_card_id");
    $stmt->execute([':medical_card_id' => $medical_card_id]);
    $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Обработка добавления новой записи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_record'])) {
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
    header("Location: ?student_id=$student_id");
    exit();
}

// Обработка загрузки файла
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['medical_file'])) {
    $medical_record_id = $_POST['medical_record_id'];
    $file_name = $_FILES['medical_file']['name'];
    $file_tmp = $_FILES['medical_file']['tmp_name'];
    $file_path = "uploads/" . basename($file_name);

    if (move_uploaded_file($file_tmp, $file_path)) {
        $stmt = $pdo->prepare("INSERT INTO medical_files (medical_record_id, file_name, file_path) 
                               VALUES (:medical_record_id, :file_name, :file_path)");
        $stmt->execute([
            ':medical_record_id' => $medical_record_id,
            ':file_name' => $file_name,
            ':file_path' => $file_path
        ]);
        header("Location: ?student_id=$student_id");
        exit();
    } else {
        echo "Ошибка загрузки файла.";
    }
}

// Обработка удаления файла
if (isset($_GET['delete_file_id'])) {
    $file_id = $_GET['delete_file_id'];
    $stmt = $pdo->prepare("DELETE FROM medical_files WHERE id = :file_id");
    $stmt->execute([':file_id' => $file_id]);
    header("Location: ?student_id=$student_id");
    exit();
}




?>












<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет врача</title>
</head>
<body>
    <h1>Добро пожаловать, <?= htmlspecialchars($doctor['name'] . ' ' . $doctor['surname']) ?></h1>

    <!-- Личные данные врача -->

    <h2>Личные данные</h2>
<form method="POST" id="personal-data-form">
    <div>
        <label for="name">Имя:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($doctor['name']) ?>" disabled id="name">
    </div>
    <div>
        <label for="surname">Фамилия:</label>
        <input type="text" name="surname" value="<?= htmlspecialchars($doctor['surname']) ?>" disabled id="surname">
    </div>
    <div>
        <label for="phone">Телефон:</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($doctor['phone']) ?>" disabled id="phone">
    </div>
    <div>
        <label for="email">Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($doctor['email']) ?>" disabled id="email">
    </div>
    <div>
        <label for="speciality">Speciality:</label>
        <input type="text" name="department" value="<?= htmlspecialchars($doctor['speciality']) ?>" disabled id="speciality">
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

    <!-- Расписание врача -->
    <!-- Форма для управления графиком -->
<form method="POST">
    <?php foreach ($days_of_week as $day): ?>
        <?php
        $day_schedule = array_filter($schedule, fn($s) => $s['day'] === $day);
        $day_schedule = $day_schedule ? reset($day_schedule) : null;
        ?>
        <label>
            <?= htmlspecialchars($day) ?>:
            <input type="time" name="schedule[<?= htmlspecialchars($day) ?>][time_from]" 
                   value="<?= htmlspecialchars($day_schedule['time_from'] ?? '') ?>">
            -
            <input type="time" name="schedule[<?= htmlspecialchars($day) ?>][time_to]" 
                   value="<?= htmlspecialchars($day_schedule['time_to'] ?? '') ?>">
            <label>
                <input type="checkbox" name="schedule[<?= htmlspecialchars($day) ?>][is_day_off]" 
                       <?= isset($day_schedule['is_day_off']) && $day_schedule['is_day_off'] ? 'checked' : '' ?>> Выходной
            </label>
        </label>
        <br>
    <?php endforeach; ?>
    <button type="submit" name="manage_schedule">Сохранить график</button>
</form>

    <!-- Список пациентов -->
    <h2>Пациенты на <?= htmlspecialchars($date_filter) ?></h2>
    <form method="GET">
        <label for="date">Выберите дату:</label>
        <input type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>">
        <button type="submit">Показать</button>
    </form>

    <?php if ($appointments): ?>
        <ul>
            <?php foreach ($appointments as $appointment): ?>
                <li>
                    <?= htmlspecialchars($appointment['student_name'] . ' ' . $appointment['student_surname']) ?> - <?= htmlspecialchars($appointment['appointment_time']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Пациенты отсутствуют.</p>
    <?php endif; ?>




<?php
// Поиск студентов по студенческому номеру
$search_term = $_GET['search'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM student WHERE student_number LIKE :search_term");
$stmt->execute([':search_term' => '%' . $search_term . '%']);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<h2>Выбор студента</h2>
<form method="GET">
    <label for="search">Поиск по студенческому номеру:</label>
    <input type="text" name="search" id="search" value="<?= htmlspecialchars($search_term) ?>">
    <button type="submit">Искать</button>
</form>

<h3>Список студентов</h3>
<form method="GET">
    <label for="student_id">Выберите студента:</label>
    <select name="student_id" id="student_id">
        <option value="">-- Выберите студента --</option>
        <?php foreach ($students as $student_item): ?>
            <option value="<?= $student_item['id'] ?>" <?= isset($student_id) && $student_id == $student_item['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($student_item['name'] . ' ' . $student_item['surname'] . ' (' . $student_item['student_number'] . ')') ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Просмотреть медицинскую карту</button>
</form>






<!-- Список мед карт -->
    <h1>Медицинская карта студента</h1>

    <h2>Информация о студенте</h2>
    <p>Имя: <?= htmlspecialchars($student['name']) ?> <?= htmlspecialchars($student['surname']) ?></p>
    <p>Факультет: <?= htmlspecialchars($student['department']) ?></p>
    <p>Студенческий номер: <?= htmlspecialchars($student['student_number']) ?></p>

    <h2>Записи в медицинской карте</h2>
    <?php if ($medical_records): ?>
        <ul>
            <?php foreach ($medical_records as $record): ?>
                <li>
                    <strong>Болезнь:</strong> <?= htmlspecialchars($record['disease_name']) ?> (<?= htmlspecialchars($record['disease_date']) ?>)<br>
                    <strong>Лекарства:</strong> <?= htmlspecialchars($record['medications']) ?><br>

                    <form action="" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="medical_record_id" value="<?= $record['id'] ?>">
                        <label>Загрузить файл:</label>
                        <input type="file" name="medical_file" required>
                        <button type="submit">Загрузить</button>
                    </form>

                    <h4>Файлы:</h4>
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM medical_files WHERE medical_record_id = :record_id");
                    $stmt->execute([':record_id' => $record['id']]);
                    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php if ($files): ?>
                        <ul>
                            <?php foreach ($files as $file): ?>
                                <li>
                                    <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank"><?= htmlspecialchars($file['file_name']) ?></a>
                                    <a href="?delete_file_id=<?= $file['id'] ?>" onclick="return confirm('Удалить файл?')">Удалить</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Файлы отсутствуют</p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Записей нет.</p>
    <?php endif; ?>

    <h2>Добавить новую запись</h2>
    <form action="" method="post">
        <label>Болезнь:</label>
        <input type="text" name="disease_name" required><br>
        <label>Дата:</label>
        <input type="date" name="disease_date" required><br>
        <label>Лекарства:</label>
        <textarea name="medications"></textarea><br>
        <button type="submit" name="add_record">Добавить</button>
    </form>



</body>
</html>
