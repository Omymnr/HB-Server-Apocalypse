<?php
// 1. CONFIGURACIÓN BÁSICA (Idioma)
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';
$lParam = ($lang == 'es') ? 'en' : 'es';
$lBtnText = ($lang == 'es') ? 'ENGLISH' : 'ESPAÑOL';

// 2. TRADUCCIONES
$txt = [
    'es' => [
        'menu_home' => 'Inicio', 'menu_down' => 'Descargas', 'menu_news' => 'Noticias', 'menu_rank' => 'Rankings', 'menu_info' => 'Info Servidor', 'menu_forum' => 'Foro',
        'sub_build' => 'Simulador PJ', 'sub_best' => 'Bestiario (Mobs)', 'sub_atlas' => 'Atlas (Mapas)', 'sub_item' => 'Base de Objetos', 'sub_spell' => 'Magias y Skills', 'sub_event' => 'Eventos', 'sub_rules' => 'Reglas',
        'hero_title' => 'APOCALYPSE', 'hero_text' => 'La guerra entre Aresden y Elvine ha alcanzado su punto crítico.', 'hero_btn' => 'Comienza tu Viaje',
        'fac_ares_t' => 'ARESDEN', 'fac_ares_d' => 'Seguidores del dios Aresden. Fuerza y Honor.',
        'fac_elv_t' => 'ELVINE', 'fac_elv_d' => 'Seguidores del dios Elvine. Sabiduría y Magia.'
    ],
    'en' => [
        'menu_home' => 'Home', 'menu_down' => 'Downloads', 'menu_news' => 'News', 'menu_rank' => 'Rankings', 'menu_info' => 'Server Info', 'menu_forum' => 'Forum',
        'sub_build' => 'Character Builder', 'sub_best' => 'Bestiary (Mobs)', 'sub_atlas' => 'Atlas (Maps)', 'sub_item' => 'Items Database', 'sub_spell' => 'Spells & Skills', 'sub_event' => 'Events', 'sub_rules' => 'Rules',
        'hero_title' => 'APOCALYPSE', 'hero_text' => 'The war between Aresden and Elvine has reached its boiling point.', 'hero_btn' => 'Start Your Journey',
        'fac_ares_t' => 'ARESDEN', 'fac_ares_d' => 'Followers of the god Aresden. Strength and Honor.',
        'fac_elv_t' => 'ELVINE', 'fac_elv_d' => 'Followers of the god Elvine. Wisdom and Magic.'
    ]
];
$t = $txt[$lang];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helbreath Apocalypse - Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?php echo ($lang=='en')?'show-en':'show-es'; ?>">

    <header>
        <div class="logo">HELBREATH <span style="color:white">APOCALYPSE</span></div>
        <nav>
            <ul>
                <li><a href="index.php?lang=<?php echo $lang; ?>" class="active"><?php echo $t['menu_home']; ?></a></li>
                <li><a href="downloads.php?lang=<?php echo $lang; ?>"><?php echo $t['menu_down']; ?></a></li>
                <li><a href="news.php?lang=<?php echo $lang; ?>"><?php echo $t['menu_news']; ?></a></li>
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
        <section class="hero">
            <h1><?php echo $t['hero_title']; ?></h1>
            <p><?php echo $t['hero_text']; ?></p>
            <a href="downloads.php?lang=<?php echo $lang; ?>" class="cta-btn"><?php echo $t['hero_btn']; ?></a>
        </section>

        <div class="factions">
            <div class="faction aresden">
                <h3><?php echo $t['fac_ares_t']; ?></h3>
                <p><?php echo $t['fac_ares_d']; ?></p>
            </div>
            <div class="faction elvine">
                <h3><?php echo $t['fac_elv_t']; ?></h3>
                <p><?php echo $t['fac_elv_d']; ?></p>
            </div>
        </div>
    </main>

    <footer>
        <div class="social-links">
            <a href="#"><i class="fab fa-discord"></i></a>
            <a href="#"><i class="fab fa-facebook"></i></a>
        </div>
        <p class="copyright">&copy; 2025 Helbreath Apocalypse.</p>
    </footer>
    <script src="script.js"></script>
</body>
</html>