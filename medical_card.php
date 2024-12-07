<?php
session_start();
include 'db_connection.php';

// Проверка роли студента
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: auth.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// Проверка существования медицинской карточки
$stmt = $pdo->prepare("SELECT id FROM medical_cards WHERE student_id = :student_id");
$stmt->execute([':student_id' => $student_id]);
$card = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$card) {
    echo "<p>У вас нет медицинской карточки. Создайте её сначала.</p>";
    exit();
}

$medical_card_id = $card['id'];

// Добавление записи о болезни
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем, заданы ли значения
    $disease_name = isset($_POST['disease_name']) ? trim($_POST['disease_name']) : null;
    $disease_date = isset($_POST['disease_date']) ? trim($_POST['disease_date']) : null;
    $medications = !empty($_POST['medications']) ? trim($_POST['medications']) : null;

    // Проверка, что обязательные поля заполнены
    if ($disease_name && $disease_date) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO medical_records (medical_card_id, disease_name, disease_date, medications)
                VALUES (:medical_card_id, :disease_name, :disease_date, :medications)
            ");
            $stmt->execute([
                ':medical_card_id' => $medical_card_id,
                ':disease_name' => $disease_name,
                ':disease_date' => $disease_date,
                ':medications' => $medications
            ]);

            header("Location: medical_card.php");
            echo "<p>Запись успешно добавлена!</p>";
            exit();
        } catch (PDOException $e) {
            echo "<p>Ошибка добавления записи: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p>Пожалуйста, заполните все обязательные поля (название болезни и дату).</p>";
    }
}


// Получение всех записей о болезнях
$stmt = $pdo->prepare("
    SELECT disease_name, disease_date, medications, created_at 
    FROM medical_records 
    WHERE medical_card_id = :medical_card_id
    ORDER BY disease_date DESC
");
$stmt->execute([':medical_card_id' => $medical_card_id]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Медицинская карточка</title>
</head>
<body>
<h1>Медицинская карточка</h1>

<!-- Добавление новой записи -->
<h2>Добавить запись о болезни</h2>
<form method="POST">
    <label for="disease_name">Название болезни:</label>
    <input type="text" id="disease_name" name="disease_name" required>
    <br>

    <label for="disease_date">Дата:</label>
    <input type="date" id="disease_date" name="disease_date" required>
    <br>

    <label for="medications">Лекарства (необязательно):</label>
    <textarea id="medications" name="medications" placeholder="Укажите принимаемые лекарства"></textarea>
    <br>

    <button type="submit">Добавить</button>
</form>







<!-- Вывод списка болезней -->
<h2>История болезней</h2>
<?php if (empty($records)): ?>
    <p>Нет записей о болезнях.</p>
<?php else: ?>
    <ul>
        <?php foreach ($records as $record): ?>
            <li>
                <strong>Болезнь:</strong> <?= htmlspecialchars($record['disease_name']); ?><br>
                <strong>Дата:</strong> <?= htmlspecialchars($record['disease_date']); ?><br>
                <?php if (!empty($record['medications'])): ?>
                    <strong>Лекарства:</strong> <?= htmlspecialchars($record['medications']); ?><br>
                <?php endif; ?>
                <small><em>Добавлено: <?= htmlspecialchars($record['created_at']); ?></em></small>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php
// Путь для сохранения загруженных файлов
$upload_dir = 'uploads/';

// Создаем директорию, если её нет
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Обработка загрузки файла
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['medical_file'])) {
    $file = $_FILES['medical_file'];

    // Проверяем ошибки загрузки
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_name = basename($file['name']);
        $file_path = $upload_dir . time() . "_" . $file_name; // Уникальное имя файла

        // Проверяем тип файла
        $file_type = mime_content_type($file['tmp_name']);
        if ($file_type === 'application/pdf') {
            // Перемещаем файл в папку uploads
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Сохраняем информацию о файле в базе данных
                $stmt = $pdo->prepare("
                    INSERT INTO medical_files (medical_card_id, file_name, file_path) 
                    VALUES (:medical_card_id, :file_name, :file_path)
                ");
                $stmt->execute([
                    ':medical_card_id' => $medical_card_id,
                    ':file_name' => $file_name,
                    ':file_path' => $file_path
                ]);

                echo "<p>Файл успешно загружен.</p>";
            } else {
                echo "<p>Ошибка при перемещении файла.</p>";
            }
        } else {
            echo "<p>Допускаются только PDF-файлы.</p>";
        }
    } else {
        echo "<p>Ошибка загрузки файла.</p>";
    }
}

//Удаление файла
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file_id'])) {
    $file_id = (int)$_POST['delete_file_id'];

    // Проверяем, существует ли файл в базе данных
    $stmt = $pdo->prepare("
        SELECT file_path 
        FROM medical_files 
        WHERE id = :id AND medical_card_id = :medical_card_id
    ");
    $stmt->execute([
        ':id' => $file_id,
        ':medical_card_id' => $medical_card_id
    ]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    
    if ($file) {
        echo htmlspecialchars($file['delete_file_id']);
        echo "<p>Путь к файлу: " . htmlspecialchars($file['file_path']) . "</p>";
        // Проверяем, существует ли файл на сервере
        if (file_exists($file['file_path'])) {
            // Удаляем файл с сервера
            if (unlink($file['file_path'])) {
                // Удаляем запись из базы данных
                $stmt = $pdo->prepare("DELETE FROM medical_files WHERE id = :id");
                $stmt->execute([':id' => $file_id]);
                echo "<p>Файл успешно удален.</p>";
            } else {
                echo "<p>Не удалось удалить файл. Проверьте права доступа.</p>";
            }
        } else {
            echo "<p>Файл отсутствует на сервере. Удаление записи из базы данных.</p>";
            $stmt = $pdo->prepare("DELETE FROM medical_files WHERE id = :id");
            $stmt->execute([':id' => $file_id]);
        }
    } else {
        echo "<p>Файл не найден или доступ запрещен.</p>";
    }
}






// Получение списка файлов
$stmt = $pdo->prepare("
    SELECT file_name, file_path, uploaded_at 
    FROM medical_files 
    WHERE medical_card_id = :medical_card_id
    ORDER BY uploaded_at DESC
");
$stmt->execute([':medical_card_id' => $medical_card_id]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
$content = ob_get_clean();
include 'template.php';
?>

<h2>Загрузить документ</h2>
<form method="POST" enctype="multipart/form-data">
    <label for="medical_file">Выберите файл (только PDF):</label>
    <input type="file" id="medical_file" name="medical_file" accept="application/pdf" required>
    <br>
    <button type="submit">Загрузить</button>
</form>

<h2>Загруженные документы</h2>
<?php if (empty($files)): ?>
    <p>Документы отсутствуют.</p>
<?php else: ?>
    <ul>
        <?php foreach ($files as $file): ?>
            <li>
                <a href="<?= htmlspecialchars($file['file_path']); ?>" target="_blank">
                    <?= htmlspecialchars($file['file_name']); ?>
                </a>
                <small>(Загружено: <?= htmlspecialchars($file['uploaded_at']); ?>)</small>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="delete_file_id" value="<?= htmlspecialchars($file['id']); ?>">
                    <button type="submit" onclick="return confirm('Вы уверены, что хотите удалить этот файл?');">Удалить</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>



</body>
</html>
