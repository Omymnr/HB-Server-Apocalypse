<?php
// rankings.php - INTEGRADO

// 1. CONFIGURACIÓN SERVIDOR

// 2. SISTEMA DE RANKINGS
$cacheFile = "rankings_cache.json";
$updateInterval = 600; 
$shouldUpdate = !file_exists($cacheFile);

if (!$shouldUpdate) {
    $data = json_decode(file_get_contents($cacheFile), true);
    if (time() - $data['timestamp'] > $updateInterval) $shouldUpdate = true;
}

if ($shouldUpdate) {
    if(file_exists('rankings_engine.php')) {
        include 'rankings_engine.php';
        $data = json_decode(file_get_contents($cacheFile), true);
    } else {
        $data = ['timestamp' => time(), 'level' => [], 'eks' => [], 'contrib' => []];
    }
}

// 3. IDIOMA
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';
$lParam = ($lang == 'es') ? 'en' : 'es';
$lBtnText = ($lang == 'es') ? 'ENGLISH' : 'ESPAÑOL';

$txt = [
    'es' => [
        'menu_home' => 'Inicio', 'menu_down' => 'Descargas', 'menu_news' => 'Noticias', 'menu_rank' => 'Rankings', 'menu_info' => 'Info Servidor', 'menu_forum' => 'Foro',
        'sub_build' => 'Simulador PJ', 'sub_best' => 'Bestiario (Mobs)', 'sub_atlas' => 'Atlas (Mapas)', 'sub_item' => 'Base de Objetos', 'sub_spell' => 'Magias y Skills', 'sub_event' => 'Eventos', 'sub_rules' => 'Reglas',
        'title' => 'Rankings del Apocalipsis', 'tab_level' => 'Nivel', 'tab_eks' => 'Asesinatos', 'tab_contrib' => 'Contribución', 'col_rank' => 'Puesto', 'col_name' => 'Personaje', 'updated' => 'Última actualización:'
    ],
    'en' => [
        'menu_home' => 'Home', 'menu_down' => 'Downloads', 'menu_news' => 'News', 'menu_rank' => 'Rankings', 'menu_info' => 'Server Info', 'menu_forum' => 'Forum',
        'sub_build' => 'Character Builder', 'sub_best' => 'Bestiary (Mobs)', 'sub_atlas' => 'Atlas (Maps)', 'sub_item' => 'Items Database', 'sub_spell' => 'Spells & Skills', 'sub_event' => 'Events', 'sub_rules' => 'Rules',
        'title' => 'Apocalypse Rankings', 'tab_level' => 'Level', 'tab_eks' => 'Enemy Kills', 'tab_contrib' => 'Contribution', 'col_rank' => 'Rank', 'col_name' => 'Character', 'updated' => 'Last updated:'
    ]
];
$t = $txt[$lang];

function renderRows($list, $valueKey) {
    $html = ''; $rank = 1;
    if(empty($list)) return "<tr><td colspan='3' style='padding:20px;text-align:center;'>---</td></tr>";
    foreach ($list as $p) {
        $class = ''; $icon = '';
        if ($rank == 1) { $class = 'gold'; $icon = ' <i class="fas fa-crown"></i>'; }
        elseif ($rank == 2) { $class = 'silver'; }
        elseif ($rank == 3) { $class = 'bronze'; }
        $html .= "<tr class='$class'><td class='rank-num'>#$rank</td><td class='char-name'>" . htmlspecialchars($p['name']) . $icon . "</td><td class='stat-val'>" . number_format($p[$valueKey]) . "</td></tr>";
        $rank++;
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?> - Helbreath Apocalypse</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .ranking-wrapper { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .tabs { display: flex; justify-content: center; gap: 5px; margin-bottom: 0; }
        .tab-btn { background: rgba(20, 20, 20, 0.8); border: 1px solid #444; border-bottom: none; color: #888; padding: 15px 30px; cursor: pointer; text-transform: uppercase; font-family: 'Cinzel', serif; font-weight: bold; font-size: 1.1em; transition: all 0.3s ease; flex: 1; max-width: 200px; border-radius: 5px 5px 0 0; }
        .tab-btn:hover { background: #333; color: #fff; }
        .tab-btn.active { background: #c0392b; color: #fff; border-color: #c0392b; box-shadow: 0 -2px 10px rgba(192, 57, 43, 0.4); }
        .ranking-container { display: none; animation: fadeIn 0.4s ease-in-out; background: rgba(10, 10, 10, 0.95); border: 1px solid #c0392b; box-shadow: 0 0 30px rgba(0,0,0,0.8); border-radius: 0 0 5px 5px; overflow: hidden; }
        .ranking-container.active { display: block; }
        .hb-table { width: 100%; border-collapse: collapse; }
        .hb-table th { background: linear-gradient(to bottom, #222, #111); color: #c0392b; padding: 18px; text-transform: uppercase; font-family: 'Cinzel', serif; letter-spacing: 1px; border-bottom: 2px solid #555; font-size: 0.9em; }
        .hb-table td { padding: 15px; border-bottom: 1px solid #222; font-family: 'Roboto', sans-serif; color: #ccc; }
        .hb-table tr:hover { background: rgba(192, 57, 43, 0.1); }
        .gold { background: linear-gradient(90deg, rgba(241, 196, 15, 0.1), transparent); }
        .gold .rank-num { color: #f1c40f; font-weight: bold; font-size: 1.2em; text-shadow: 0 0 10px rgba(241, 196, 15, 0.5); } .gold .char-name { color: #fff; font-weight: bold; }
        .silver { background: linear-gradient(90deg, rgba(189, 195, 199, 0.1), transparent); }
        .silver .rank-num { color: #bdc3c7; font-weight: bold; font-size: 1.1em; text-shadow: 0 0 10px rgba(189, 195, 199, 0.5); }
        .bronze { background: linear-gradient(90deg, rgba(211, 84, 0, 0.1), transparent); }
        .bronze .rank-num { color: #d35400; font-weight: bold; font-size: 1.1em; text-shadow: 0 0 10px rgba(211, 84, 0, 0.5); }
        .stat-val { text-align: right; font-family: 'Courier New', monospace; font-weight: bold; color: #aaa; }
        .footer-info { margin-top: 15px; font-size: 12px; color: #666; text-align: right; font-style: italic; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="<?php echo ($lang=='en')?'show-en':'show-es'; ?>">

    <header>
        <div class="logo">HELBREATH <span style="color:white">APOCALYPSE</span></div>
        <nav>
            <ul>
                <li><a href="index.php?lang=<?php echo $lang; ?>"><?php echo $t['menu_home']; ?></a></li>
                <li><a href="downloads.php?lang=<?php echo $lang; ?>"><?php echo $t['menu_down']; ?></a></li>
                <li><a href="news.php?lang=<?php echo $lang; ?>"><?php echo $t['menu_news']; ?></a></li>
                <li><a href="rankings.php?lang=<?php echo $lang; ?>" class="active"><?php echo $t['menu_rank']; ?></a></li>
                <li>
                    <a href="#"><?php echo $t['menu_info']; ?> <i class="fas fa-chevron-down"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="CharacterStats.php?lang=<?php echo $lang; ?>"><i class="fas fa-user-shield"></i> &nbsp; <?php echo $t['sub_build']; ?></a></li>
                        <li><a href="#"><i class="fas fa-dragon"></i> &nbsp; <?php echo $t['sub_best']; ?></a></li>
                        <li><a href="#"><i class="fas fa-map"></i> &nbsp; <?php echo $t['sub_atlas']; ?></a></li>
                        <li><a href="#"><i class="fas fa-scroll"></i> &nbsp; <?php echo $t['sub_item']; ?></a></li>
                        <li><a href="#"><i class="fas fa-magic"></i> &nbsp; <?php echo $t['sub_spell']; ?></a></li>
                        <li><a href="#"><i class="fas fa-calendar-alt"></i> &nbsp; <?php echo $t['sub_event']; ?></a></li>
                        <li><a href="#"><i class="fas fa-book"></i> &nbsp; <?php echo $t['sub_rules']; ?></a></li>
                    </ul>
                </li>
                <li><a href="forum.php?lang=<?php echo $lang; ?>"><?php echo $t['menu_forum']; ?></a></li>
            </ul>
        </nav>
        <a href="?lang=<?php echo $lParam; ?>" class="lang-btn" style="text-decoration:none; display:inline-block; padding:10px 20px; border:1px solid #555; color:white;">
            <?php echo $lBtnText; ?>
        </a>
    </header>

    <main>
        <section class="section">
            <h2 class="section-title"><?php echo $t['title']; ?></h2>
            <div class="ranking-wrapper">
                <div class="tabs">
                    <button class="tab-btn active" onclick="openTab('level')"><i class="fas fa-chart-line"></i> <?php echo $t['tab_level']; ?></button>
                    <button class="tab-btn" onclick="openTab('eks')"><i class="fas fa-skull"></i> <?php echo $t['tab_eks']; ?></button>
                    <button class="tab-btn" onclick="openTab('contrib')"><i class="fas fa-hands-helping"></i> <?php echo $t['tab_contrib']; ?></button>
                </div>

                <div id="tab-level" class="ranking-container active">
                    <table class="hb-table"><thead><tr><th width="15%"><?php echo $t['col_rank']; ?></th><th width="60%"><?php echo $t['col_name']; ?></th><th width="25%" style="text-align:right"><?php echo $t['tab_level']; ?></th></tr></thead><tbody><?php echo renderRows($data['level'], 'level'); ?></tbody></table>
                </div>
                <div id="tab-eks" class="ranking-container">
                    <table class="hb-table"><thead><tr><th width="15%"><?php echo $t['col_rank']; ?></th><th width="60%"><?php echo $t['col_name']; ?></th><th width="25%" style="text-align:right"><?php echo $t['tab_eks']; ?></th></tr></thead><tbody><?php echo renderRows($data['eks'], 'eks'); ?></tbody></table>
                </div>
                <div id="tab-contrib" class="ranking-container">
                    <table class="hb-table"><thead><tr><th width="15%"><?php echo $t['col_rank']; ?></th><th width="60%"><?php echo $t['col_name']; ?></th><th width="25%" style="text-align:right"><?php echo $t['tab_contrib']; ?></th></tr></thead><tbody><?php echo renderRows($data['contrib'], 'contrib'); ?></tbody></table>
                </div>
                <div class="footer-info"><i class="fas fa-clock"></i> <?php echo $t['updated']; ?> <?php echo date("Y-m-d H:i", $data['timestamp']); ?></div>
            </div>
        </section>
    </main>

    <footer>
        <div class="social-links"><a href="#"><i class="fab fa-discord"></i></a></div>
        <p class="copyright">&copy; 2025 Helbreath Apocalypse.</p>
    </footer>
    <script>
        function openTab(tabName) {
            var containers = document.getElementsByClassName('ranking-container'); for (var i = 0; i < containers.length; i++) containers[i].classList.remove('active');
            var buttons = document.getElementsByClassName('tab-btn'); for (var i = 0; i < buttons.length; i++) buttons[i].classList.remove('active');
            document.getElementById('tab-' + tabName).classList.add('active'); event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>