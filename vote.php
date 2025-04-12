<?php
include('app/_inc.php');
$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$vote = 1;

// Получаем информацию о статье (школе)
$sql = "SELECT `id`, `name`, `section_id` FROM `news_articles` WHERE `id` = ?";
$stmt = $connect->prepare($sql);
if (!$stmt) {
    die('Ошибка подготовки запроса: ' . $connect->error);
}
$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();
$article = $result->fetch_assoc();

if (!$article) {
    echo "Школа не найдена.";
    exit;
}

// Получаем информацию о категории
$sqls = "SELECT `name` FROM `news_sections` WHERE `id` = ?";
$stmts = $connect->prepare($sqls);
if (!$stmts) {
    die('Ошибка подготовки запроса: ' . $connect->error);
}
$stmts->bind_param("i", $article['section_id']);
$stmts->execute();
$results = $stmts->get_result();
$cat = $results->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мектепке даўыс бериң</title>
    <link rel="stylesheet" href="app/style.css?scr=a1145">
    <script src="https://telegram.org/js/telegram-web-app.js"></script> <!-- Обязательно -->
</head>
<body>

<div class="vote-container">
    <h1>Мектепке даўыс бериң</h1>

    <?php if ($article): ?>
        <div class="school-info">
            <p><?php echo htmlspecialchars($cat['name']); ?></p>
            <h2>Сиз "<?php echo htmlspecialchars($article['name']); ?>"ке даўыс бермекшисиз бе?</h2>
            
            <button id="voteButton">Аўа</button> | 
            <a href='index.html?auth=128695'> 
                <button id="NoButton">Яқ</button>
            </a>
            <div id="message"></div>
        </div>
    <?php else: ?>
        <p>Школа не найдена.</p>
    <?php endif; ?>
</div>

<script>
    const tg = window.Telegram.WebApp;
    // Показываем кнопку "Назад"
    tg.BackButton.show();
    
    // Назначаем обработчик нажатия
    tg.BackButton.onClick(() => {
        window.location.href = "index.html?auth=95821"; // Перенаправление на index.html
    });
    const user = tg.initDataUnsafe.user;
    const user_id = user ? user.id : null;
    const article_id = <?php echo $article_id; ?>;

    document.getElementById('voteButton').addEventListener('click', function () {
        if (!user_id) {
            alert("Не удалось получить Telegram ID");
            return;
        }

        fetch('app/vote.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `article_id=${article_id}&user_id=${user_id}`
        })
        .then(response => response.text())
        .then(data => {
            document.getElementById('message').innerHTML = data;
            document.getElementById('voteButton').disabled = true;
        });
    });
</script>

</body>
</html>
