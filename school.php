<?php
$cat = isset($_GET['cat']) ? $_GET['cat'] : 0;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ЕҢ ҮЛГИЛИ МЕКТЕП</title>
    <link rel="stylesheet" href="app/style.css">  <!-- Подключаем стиль -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
</head>
<body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let offset = 0;
    let limit = 15;
    let loading = false;
    let endReached = false;
    let searchQuery = "";

    // Инициализация Telegram Web App
    window.Telegram.WebApp.ready();

    const tg = window.Telegram.WebApp;
    tg.expand(); // раскрывает окно на весь экран

    // Показываем кнопку "Назад"
    tg.BackButton.show();
    
    // Назначаем обработчик нажатия
    tg.BackButton.onClick(() => {
        window.location.href = "index.html?auth=85148"; // Перенаправление на index.html
    });
    const user = tg.initDataUnsafe.user;
    if (user && user.id) {
        fetch(`app/check_vote.php?user_id=${user.id}`)
            .then(response => response.json())
            .then(data => {
                if (data.voted) {
                    window.location.href = "stat.php?user_id=" + user.id;
                }
            });
    }
    // Загружаем данные
    function loadData() {
        if (loading || endReached) return;

        loading = true;
        $("#loading").show();
        $("#loadMore").hide();

        $.get("app/load.php", {
            cat: "<?php echo $cat; ?>",
            offset: offset,
            limit: limit,
            q: searchQuery
        }, function (data) {
            if (data.length === 0) {
                endReached = true;
                $("#loadMore").hide();
            }

            data.forEach(item => {
                let viewCount = item.view_count ?? 0; // если null, то 0
                $("#content").append(`
                    <a href="vote.php?id=${item.id}&auth=<?php echo rand(5,5555); ?>" class="item">
                        <div class="item-name">${item.name}</div>
                        <div class="view-count">${viewCount}</div>
                    </a>`);
            });

            if (data.length < limit) {
                endReached = true;
                $("#loadMore").hide();
            } else {
                $("#loadMore").show();
            }

            offset += limit;
            loading = false;
            $("#loading").hide();
        }, "json");
    }

    $(document).ready(function () {
        loadData();

        // Поиск
        $("#search").on("input", function () {
            searchQuery = $(this).val().toLowerCase().trim();
            offset = 0;
            endReached = false;
            $("#content").empty();
            $("#loadMore").hide();
            loadData();
        });

        // Кнопка "Ещё"
        $("#loadMore").on("click", function () {
            loadData();
        });

        // Бесконечный скролл
        $(window).scroll(function () {
            if ($(window).scrollTop() + $(window).height() >= $(document).height() - 200) {
                loadData();
            }
        });
    });
</script>

    <h1>ЕҢ ҮЛГИЛИ МЕКТЕП</h1>
    
    <!-- Поисковая строка -->
    <input type="text" id="search" placeholder="Излеў...">
    <div id="content"></div>
    
    <!-- Кнопка "Ещё" -->
    <button id="loadMore" class="btn-load-more" style="display: none;">Басқа мектеплер</button>

    <!-- Индикатор загрузки -->
    <div id="loading" style="display: none;">Загрузка...</div>

</body>
</html>