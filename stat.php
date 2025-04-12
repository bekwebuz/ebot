<?php
include('app/_inc.php');
$sections = $connect->query("SELECT id, name FROM news_sections");
$sections_list = [];
while ($section = $sections->fetch_assoc()) {
    $sections_list[] = $section;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Рейтинг</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
            background: #f0f2f5;
        }
        .block {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table thead {
            background: #007bff;
            color: white;
        }
        table td, table th {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        select {
            padding: 8px;
            margin-bottom: 10px;
        }
        .custom-select {
            padding: 10px 15px;
            font-size: 16px;
            border: 2px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
            width: 100%;
            max-width: 300px;
            margin: 10px 0;
            transition: border-color 0.3s ease;
        }
        .custom-select:focus {
            border-color: #007bff;
            outline: none;
        }
        .custom-select option {
            padding: 10px;
        }
        label {
            font-size: 18px;
            color: #333;
            display: block;
            margin-bottom: 5px;
        }
        #vote-result {
            font-size: 16px;
            margin-top: 10px;
            color: #333;
        }
        /* Кнопка "Загрузить ещё" */
        .btn-load-more {
            padding: 12px 24px;
            background: linear-gradient(135deg, #007bff, #0ea5e9);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin: 20px auto;
            display: block;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: background 0.3s ease, transform 0.2s ease;
        }
        
        .btn-load-more:hover {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            transform: scale(1.05);
        }
        
        .btn-load-more:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>
    <div id="subscribe-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:#000000cc; z-index:9999; padding:40px;">
        <div style="background:white; border-radius:10px; padding:20px; max-width:500px; margin:auto; text-align:center;">
            <h3>Сиз төмендегі каналларға аъза болып шығыңыз</h3>
            <div id="channel-links"></div>
            <button onclick="location.reload()">Ағза болдым ✅</button>
        </div>
    </div>
    
    <div class="block" id="vote-block">
        <h1>Сиз даўыс берген мектеп</h1>
        <div id="vote-result">Загрузка...</div>
    </div>

    <div class="block">
        <h2>Рейтинг</h2>
        <label>Мектеплер бойынша рейтинг:</label>
        <select id="section-filter" class="custom-select">
            <option value="all">Ҳәмме мектеплер арасында</option>
            <?php foreach ($sections_list as $section): ?>
                <option value="<?= $section['id'] ?>"><?= htmlspecialchars($section['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <table>
            <thead>
                <tr>
                    <th>Мектеп аты</th>
                    <th>Даўыслар</th>
                </tr>
            </thead>
            <tbody id="top-schools">
                <tr><td colspan="2">Загрузка...</td></tr>
            </tbody>
        </table>
        <button id="btn-load-more" class="btn-load-more">Басқа мектеплер</button>
    </div>

    <script>
        const tg = window.Telegram?.WebApp;
        const user = tg?.initDataUnsafe?.user;
        const user_id = user?.id || 123456789;
        let offset = 0;
        let currentSection = 'all';
        let hasMore = true;

        const voteResult = document.getElementById("vote-result");
        const topTable = document.getElementById("top-schools");
        const sectionFilter = document.getElementById("section-filter");
        const loadMoreBtn = document.getElementById("btn-load-more");
        
        function checkSubscriptions(user_id) {
            return fetch('app/check_subs.php', {
                method: "POST",
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ user_id: user_id, channels: requiredChannels })
            }).then(res => res.json());
        }
    
        function showSubscribeModal(channels) {
            const linksContainer = document.getElementById('channel-links');
            linksContainer.innerHTML = '';
            channels.forEach(ch => {
                const a = document.createElement('a');
                a.href = ch.url;
                a.target = '_blank';
                a.innerText = ch.title;
                a.style.display = 'block';
                a.style.margin = '10px 0';
                linksContainer.appendChild(a);
            });
            document.getElementById('subscribe-modal').style.display = 'block';
        }
        
        
        function loadStats(section_id = 'all', append = false) {
            const body = new URLSearchParams();
            body.append("user_id", user_id);
            body.append("section_id", section_id);
            body.append("offset", offset);

            fetch('app/vote_handler.php', {
                method: "POST",
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: body.toString()
            })
            .then(res => res.json())
            .then(data => {
                if (data.user_vote) {
                    voteResult.innerHTML = `<strong>${data.user_vote.section_name}</strong> ${data.user_vote.article_name}`;
                    if (offset === 0 && data.user_section_id && currentSection === 'all') {
                        sectionFilter.value = data.user_section_id;
                        currentSection = data.user_section_id;
                        offset = 0;
                        topTable.innerHTML = "";
                        loadStats(currentSection);
                        return;
                    }
                } else {
                    voteResult.innerHTML = "Вы ещё не голосовали.";
                }

                if (!append) topTable.innerHTML = "";
                data.top_schools.forEach(row => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `<td>${row.section_name}<br>${row.name}</td><td>${row.view_count}</td>`;
                    topTable.appendChild(tr);
                });

                if (data.top_schools.length === 25) {
                    hasMore = true;
                    loadMoreBtn.style.display = "block";
                } else {
                    hasMore = false;
                    loadMoreBtn.style.display = "none";
                }
            })
            .catch(err => {
                voteResult.innerHTML = "Ошибка загрузки.";
                console.error(err);
            });
        }

        sectionFilter.addEventListener("change", (e) => {
            currentSection = e.target.value;
            offset = 0;
            hasMore = true;
            loadStats(currentSection);
        });

        loadMoreBtn.addEventListener("click", () => {
            if (!hasMore) return;
            offset += 25;
            loadStats(currentSection, true);
        });

        if (user_id) {
            loadStats(); // первая загрузка
        } else {
            voteResult.innerHTML = "Не удалось получить Telegram ID.";
        }
    </script>
</body>
</html>
