<?php
session_start();
require 'db.php';
require 'functions.php';

// 1. CONFIGURACIÓN DEL ESTADO DEL SERVIDOR
$serverIP = '89.7.69.125'; 
$serverPort = 9907; 
$conn_status = @fsockopen($serverIP, $serverPort, $errno, $errstr, 1);
$isOnline = (bool)$conn_status; if($conn_status) fclose($conn_status);

// 2. IDIOMAS
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';
$lParam = ($lang == 'es') ? 'en' : 'es';
$lBtnText = ($lang == 'es') ? 'ENGLISH' : 'ESPAÑOL';

// 3. TRADUCCIONES COMPLETAS
$txt = [
    'es' => [
        'menu_home' => 'Inicio', 'menu_down' => 'Descargas', 'menu_news' => 'Noticias', 'menu_rank' => 'Rankings', 'menu_info' => 'Info Servidor', 'menu_forum' => 'Foro', 
        'status_on' => 'ONLINE', 'status_off' => 'OFFLINE',
        'sub_build' => 'Simulador PJ', 'sub_best' => 'Bestiario (Mobs)', 'sub_atlas' => 'Atlas (Mapas)', 'sub_item' => 'Base de Objetos', 'sub_spell' => 'Magias y Skills', 'sub_event' => 'Eventos', 'sub_rules' => 'Reglas',
        'f_title' => 'Comunidad Apocalypse',
        'f_cat_main' => 'General',
        'f_cat_game' => 'Juego',
        'f_news' => 'Noticias y Anuncios',
        'f_news_desc' => 'Actualizaciones oficiales del servidor.',
        'f_general' => 'Discusión General',
        'f_general_desc' => 'Habla de todo lo relacionado con Helbreath.',
        'f_market' => 'Mercado',
        'f_market_desc' => 'Compra, venta e intercambio de items.',
        'f_guilds' => 'Clanes',
        'f_guilds_desc' => 'Reclutamiento y diplomacia.',
        'f_stats' => 'Temas / Msjs',
        'f_last' => 'Último Mensaje',
        'f_login' => 'Iniciar Sesión',
        'f_register' => 'Registrarse',
        'my_profile' => 'MI PERFIL',
        'stat_topics' => 'Temas',
        'stat_posts' => 'Mensajes',
        'no_msg' => '- Sin mensajes -',
        'logout' => 'DESCONECTAR'
    ],
    'en' => [
        'menu_home' => 'Home', 'menu_down' => 'Downloads', 'menu_news' => 'News', 'menu_rank' => 'Rankings', 'menu_info' => 'Server Info', 'menu_forum' => 'Forum', 
        'status_on' => 'ONLINE', 'status_off' => 'OFFLINE',
        'sub_build' => 'Character Builder', 'sub_best' => 'Bestiary (Mobs)', 'sub_atlas' => 'Atlas (Maps)', 'sub_item' => 'Items Database', 'sub_spell' => 'Spells & Skills', 'sub_event' => 'Events', 'sub_rules' => 'Rules',
        'f_title' => 'Apocalypse Community',
        'f_cat_main' => 'General',
        'f_cat_game' => 'Game World',
        'f_news' => 'News & Announcements',
        'f_news_desc' => 'Official server updates and patches.',
        'f_general' => 'General Discussion',
        'f_general_desc' => 'Talk about anything Helbreath related.',
        'f_market' => 'Marketplace',
        'f_market_desc' => 'Buy, sell, and trade items.',
        'f_guilds' => 'Guilds',
        'f_guilds_desc' => 'Recruitment and diplomacy.',
        'f_stats' => 'Topics / Posts',
        'f_last' => 'Last Post',
        'f_login' => 'Login',
        'f_register' => 'Register',
        'my_profile' => 'MY PROFILE',
        'stat_topics' => 'Topics',
        'stat_posts' => 'Posts',
        'no_msg' => '- No posts -',
        'logout' => 'LOGOUT'
    ]
];
$t = $txt[$lang];
$stClass = $isOnline ? 'status-online' : 'status-offline';
$stText = $isOnline ? $t['status_on'] : $t['status_off'];

// FUNCIÓN PARA OBTENER ESTADÍSTICAS
function getCategoryStats($conn, $cat_id, $lang) {
    $sql_t = "SELECT COUNT(*) as total FROM topics WHERE topic_cat = $cat_id";
    $topics = $conn->query($sql_t)->fetch_assoc()['total'];

    $sql_p = "SELECT COUNT(*) as total FROM posts p 
              JOIN topics t ON p.post_topic = t.topic_id 
              WHERE t.topic_cat = $cat_id";
    $posts = $conn->query($sql_p)->fetch_assoc()['total'];

    $sql_l = "SELECT u.username, u.rank, p.post_date 
              FROM posts p 
              JOIN topics t ON p.post_topic = t.topic_id 
              JOIN users u ON p.post_by = u.id 
              WHERE t.topic_cat = $cat_id 
              ORDER BY p.post_date DESC LIMIT 1";
    $last_res = $conn->query($sql_l);
    
    if ($last_res->num_rows > 0) {
        $last = $last_res->fetch_assoc();
        $rankData = getUserRank($last['rank'], $lang);
        $last['color'] = $rankData['color'];
    } else {
        $last = null;
    }

    return ['topics' => $topics, 'posts' => $posts, 'last' => $last];
}

$stats_news    = getCategoryStats($conn, 1, $lang);
$stats_general = getCategoryStats($conn, 2, $lang);
$stats_market  = getCategoryStats($conn, 3, $lang);
$stats_guilds  = getCategoryStats($conn, 4, $lang);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum - Helbreath Apocalypse</title>
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
                <li><a href="forum.php?lang=<?php echo $lang; ?>" class="active"><?php echo $t['menu_forum']; ?></a></li>
            </ul>
        </nav>
        <div class="server-status-display <?php echo $stClass; ?>">
            <span class="status-dot"></span> <?php echo $stText; ?>
        </div>
        <a href="?lang=<?php echo $lParam; ?>" class="lang-btn" style="text-decoration:none; padding:10px 20px; border:1px solid #555; color:white;">
            <?php echo $lBtnText; ?>
        </a>
    </header>

    <main>
        <section class="section">
            <h2 class="section-title"><?php echo $t['f_title']; ?></h2>

            <div class="forum-actions">
                <?php if(isset($_SESSION['username'])): ?>
                    <div style="display:flex; align-items:center; gap:15px;">
                        <a href="profile.php?lang=<?php echo $lang; ?>" style="color:var(--primary-gold); font-weight:bold; font-family:'Cinzel', serif; text-decoration:none;">
                            <i class="fas fa-user"></i> <?php echo $t['my_profile']; ?> (<?php echo htmlspecialchars($_SESSION['username']); ?>)
                        </a>
                        
                        <?php if(isset($_SESSION['rank']) && $_SESSION['rank'] >= 3): ?>
                             <a href="admin_users.php?lang=<?php echo $lang; ?>" style="color:#ff4d4d; border:1px solid #ff4d4d; padding:5px 10px; text-decoration:none;">ADMIN PANEL</a>
                        <?php endif; ?>

                        <a href="logout.php" class="cta-btn small-btn outline" style="border-color:#8a1c1c; color:#8a1c1c;"><?php echo $t['logout']; ?></a>
                    </div>
                <?php else: ?>
                    <a href="login.php?lang=<?php echo $lang; ?>" class="cta-btn small-btn"><?php echo $t['f_login']; ?></a>
                    <a href="register.php?lang=<?php echo $lang; ?>" class="cta-btn small-btn outline"><?php echo $t['f_register']; ?></a>
                <?php endif; ?>
            </div>

            <div class="forum-category">
                <div class="f-cat-header"><?php echo $t['f_cat_main']; ?></div>
                
                <div class="f-row">
                    <div class="f-icon"><i class="fas fa-scroll"></i></div>
                    <div class="f-info">
                        <h4><a href="category.php?id=1&lang=<?php echo $lang; ?>"><?php echo $t['f_news']; ?></a></h4>
                        <p><?php echo $t['f_news_desc']; ?></p>
                    </div>
                    <div class="f-stats">
                        <span><?php echo $stats_news['topics']; ?> <?php echo $t['stat_topics']; ?></span>
                        <span><?php echo $stats_news['posts']; ?> <?php echo $t['stat_posts']; ?></span>
                    </div>
                    <div class="f-last-post">
                        <?php if($stats_news['last']): ?>
                            <span class="f-author" style="color:<?php echo $stats_news['last']['color']; ?>">
                                <?php echo htmlspecialchars($stats_news['last']['username']); ?>
                            </span>
                            <span class="f-date"><?php echo date("d M Y", strtotime($stats_news['last']['post_date'])); ?></span>
                        <?php else: ?>
                            <span class="f-date"><?php echo $t['no_msg']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="f-row">
                    <div class="f-icon"><i class="fas fa-comments"></i></div>
                    <div class="f-info">
                        <h4><a href="category.php?id=2&lang=<?php echo $lang; ?>"><?php echo $t['f_general']; ?></a></h4>
                        <p><?php echo $t['f_general_desc']; ?></p>
                    </div>
                    <div class="f-stats">
                        <span><?php echo $stats_general['topics']; ?> <?php echo $t['stat_topics']; ?></span>
                        <span><?php echo $stats_general['posts']; ?> <?php echo $t['stat_posts']; ?></span>
                    </div>
                    <div class="f-last-post">
                        <?php if($stats_general['last']): ?>
                            <span class="f-author" style="color:<?php echo $stats_general['last']['color']; ?>">
                                <?php echo htmlspecialchars($stats_general['last']['username']); ?>
                            </span>
                            <span class="f-date"><?php echo date("d M Y", strtotime($stats_general['last']['post_date'])); ?></span>
                        <?php else: ?>
                            <span class="f-date"><?php echo $t['no_msg']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="forum-category">
                <div class="f-cat-header"><?php echo $t['f_cat_game']; ?></div>
                
                <div class="f-row">
                    <div class="f-icon"><i class="fas fa-balance-scale"></i></div>
                    <div class="f-info">
                        <h4><a href="category.php?id=3&lang=<?php echo $lang; ?>"><?php echo $t['f_market']; ?></a></h4>
                        <p><?php echo $t['f_market_desc']; ?></p>
                    </div>
                    <div class="f-stats">
                        <span><?php echo $stats_market['topics']; ?> <?php echo $t['stat_topics']; ?></span>
                        <span><?php echo $stats_market['posts']; ?> <?php echo $t['stat_posts']; ?></span>
                    </div>
                    <div class="f-last-post">
                        <?php if($stats_market['last']): ?>
                            <span class="f-author" style="color:<?php echo $stats_market['last']['color']; ?>">
                                <?php echo htmlspecialchars($stats_market['last']['username']); ?>
                            </span>
                            <span class="f-date"><?php echo date("d M Y", strtotime($stats_market['last']['post_date'])); ?></span>
                        <?php else: ?>
                            <span class="f-date"><?php echo $t['no_msg']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="f-row">
                    <div class="f-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="f-info">
                        <h4><a href="category.php?id=4&lang=<?php echo $lang; ?>"><?php echo $t['f_guilds']; ?></a></h4>
                        <p><?php echo $t['f_guilds_desc']; ?></p>
                    </div>
                    <div class="f-stats">
                        <span><?php echo $stats_guilds['topics']; ?> <?php echo $t['stat_topics']; ?></span>
                        <span><?php echo $stats_guilds['posts']; ?> <?php echo $t['stat_posts']; ?></span>
                    </div>
                    <div class="f-last-post">
                        <?php if($stats_guilds['last']): ?>
                            <span class="f-author" style="color:<?php echo $stats_guilds['last']['color']; ?>">
                                <?php echo htmlspecialchars($stats_guilds['last']['username']); ?>
                            </span>
                            <span class="f-date"><?php echo date("d M Y", strtotime($stats_guilds['last']['post_date'])); ?></span>
                        <?php else: ?>
                            <span class="f-date"><?php echo $t['no_msg']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

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