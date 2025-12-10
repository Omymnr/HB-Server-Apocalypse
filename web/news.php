<?php
// 1. CONFIGURACIÓN

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';
$lParam = ($lang == 'es') ? 'en' : 'es';
$lBtnText = ($lang == 'es') ? 'ENGLISH' : 'ESPAÑOL';

// 2. TRADUCCIONES
$txt = [
    'es' => [
        'menu_home' => 'Inicio', 'menu_down' => 'Descargas', 'menu_news' => 'Noticias', 'menu_rank' => 'Rankings', 'menu_info' => 'Info Servidor', 'menu_forum' => 'Foro',
        'sub_build' => 'Simulador PJ', 'sub_best' => 'Bestiario (Mobs)', 'sub_atlas' => 'Atlas (Mapas)', 'sub_item' => 'Base de Objetos', 'sub_spell' => 'Magias y Skills', 'sub_event' => 'Eventos', 'sub_rules' => 'Reglas',
        'title' => 'Últimas Noticias', 'th_date' => 'Fecha', 'th_topic' => 'Asunto',
        // Noticias
        'n1' => 'Parche 1.5: Nuevo Boss Añadido',
        'n2' => '¡Fin de Semana de Doble EXP!',
        'n3' => 'Mantenimiento del Servidor'
    ],
    'en' => [
        'menu_home' => 'Home', 'menu_down' => 'Downloads', 'menu_news' => 'News', 'menu_rank' => 'Rankings', 'menu_info' => 'Server Info', 'menu_forum' => 'Forum',
        'sub_build' => 'Character Builder', 'sub_best' => 'Bestiary (Mobs)', 'sub_atlas' => 'Atlas (Maps)', 'sub_item' => 'Items Database', 'sub_spell' => 'Spells & Skills', 'sub_event' => 'Events', 'sub_rules' => 'Rules',
        'title' => 'Latest News', 'th_date' => 'Date', 'th_topic' => 'Topic',
        // News
        'n1' => 'Patch 1.5: New Boss Added',
        'n2' => 'Double EXP Weekend!',
        'n3' => 'Server Maintenance'
    ]
];
$t = $txt[$lang];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News - Helbreath Apocalypse</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?php echo ($lang=='en')?'show-en':'show-es'; ?>">

    <header>
        <div class="logo">HELBREATH <span style="color:white">APOCALYPSE</span></div>
        <nav>
            <ul>
                <li><a href="index.php?lang=<?php echo $lang; ?>"><?php echo $t['menu_home']; ?></a></li>
                <li><a href="downloads.php?lang=<?php echo $lang; ?>"><?php echo $t['menu_down']; ?></a></li>
                <li><a href="news.php?lang=<?php echo $lang; ?>" class="active"><?php echo $t['menu_news']; ?></a></li>
                <li><a href="rankings.php?lang=<?php echo $lang; ?>"><?php echo $t['menu_rank']; ?></a></li>
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
        <a href="?lang=<?php echo $lParam; ?>" class="lang-btn" style="text-decoration:none; padding:10px 20px; border:1px solid #555; color:white;">
            <?php echo $lBtnText; ?>
        </a>
    </header>

    <main>
        <section class="section">
            <h2 class="section-title"><?php echo $t['title']; ?></h2>
            <table class="news-table">
                <thead>
                    <tr>
                        <th><?php echo $t['th_date']; ?></th>
                        <th><?php echo $t['th_topic']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="news-date">10 DEC 2025</td>
                        <td><span class="news-tag" style="color:#2ecc71">UPDATE</span> <?php echo $t['n1']; ?></td>
                    </tr>
                    <tr>
                        <td class="news-date">08 DEC 2025</td>
                        <td><span class="news-tag" style="color:#f1c40f">EVENT</span> <?php echo $t['n2']; ?></td>
                    </tr>
                    <tr>
                        <td class="news-date">01 DEC 2025</td>
                        <td><span class="news-tag" style="color:#e74c3c">MAINTENANCE</span> <?php echo $t['n3']; ?></td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>

    <footer>
        <div class="social-links">
            <a href="#"><i class="fab fa-discord"></i></a>
        </div>
        <p class="copyright">&copy; 2025 Helbreath Apocalypse.</p>
    </footer>
    <script src="script.js"></script>
</body>
</html>