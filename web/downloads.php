<?php
// 1. CONFIGURACIÓN
$serverIP = '89.7.69.125'; $serverPort = 9907;
$conn = @fsockopen($serverIP, $serverPort, $errno, $errstr, 1);
$isOnline = (bool)$conn; if($conn) fclose($conn);

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';
$lParam = ($lang == 'es') ? 'en' : 'es';
$lBtnText = ($lang == 'es') ? 'ENGLISH' : 'ESPAÑOL';

// 2. TRADUCCIONES
$txt = [
    'es' => [
        'menu_home' => 'Inicio', 'menu_down' => 'Descargas', 'menu_news' => 'Noticias', 'menu_rank' => 'Rankings', 'menu_info' => 'Info Servidor', 'menu_forum' => 'Foro', 'status_on' => 'ONLINE', 'status_off' => 'OFFLINE',
        'sub_build' => 'Simulador PJ', 'sub_best' => 'Bestiario (Mobs)', 'sub_atlas' => 'Atlas (Mapas)', 'sub_item' => 'Base de Objetos', 'sub_spell' => 'Magias y Skills', 'sub_event' => 'Eventos', 'sub_rules' => 'Reglas',
        'title' => 'Descargar Cliente', 'desc' => 'Obtén el cliente completo para jugar Helbreath Apocalypse.',
        'note' => '<strong>HelbreathLauncher.exe</strong> actualizará automáticamente tus archivos.',
        'req_title' => 'Requisitos del Sistema', 'min' => 'Mínimos', 'rec' => 'Recomendados'
    ],
    'en' => [
        'menu_home' => 'Home', 'menu_down' => 'Downloads', 'menu_news' => 'News', 'menu_rank' => 'Rankings', 'menu_info' => 'Server Info', 'menu_forum' => 'Forum', 'status_on' => 'ONLINE', 'status_off' => 'OFFLINE',
        'sub_build' => 'Character Builder', 'sub_best' => 'Bestiary (Mobs)', 'sub_atlas' => 'Atlas (Maps)', 'sub_item' => 'Items Database', 'sub_spell' => 'Spells & Skills', 'sub_event' => 'Events', 'sub_rules' => 'Rules',
        'title' => 'Download Client', 'desc' => 'Get the full client to start playing Helbreath Apocalypse.',
        'note' => '<strong>HelbreathLauncher.exe</strong> will automatically update your game files.',
        'req_title' => 'System Requirements', 'min' => 'Minimum', 'rec' => 'Recommended'
    ]
];
$t = $txt[$lang];
$stClass = $isOnline ? 'status-online' : 'status-offline';
$stText = $isOnline ? $t['status_on'] : $t['status_off'];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Downloads - Helbreath Apocalypse</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .req-separator { margin: 3rem 0; border: 0; height: 1px; background-image: linear-gradient(to right, rgba(0, 0, 0, 0), var(--primary-gold), rgba(0, 0, 0, 0)); }
        .requirements-container { display: flex; flex-wrap: wrap; gap: 2rem; justify-content: center; text-align: left; }
        .req-column { flex: 1; min-width: 280px; background: rgba(0, 0, 0, 0.4); padding: 1.5rem; border: 1px solid #333; border-radius: 4px; }
        .req-title { color: var(--primary-gold); font-family: 'Cinzel', serif; border-bottom: 1px solid #444; padding-bottom: 0.5rem; margin-bottom: 1rem; font-size: 1.1rem; text-align: center; }
        .req-list { font-size: 0.9rem; }
        .req-row { display: flex; justify-content: space-between; margin-bottom: 0.5rem; border-bottom: 1px dashed #333; padding-bottom: 0.2rem; }
        .req-label { color: var(--text-muted); font-weight: bold; }
        .req-value { color: var(--text-light); text-align: right; }
    </style>
</head>
<body class="<?php echo ($lang=='en')?'show-en':'show-es'; ?>">

    <header>
        <div class="logo">HELBREATH <span style="color:white">APOCALYPSE</span></div>
        <nav>
            <ul>
                <li><a href="index.php?lang=<?php echo $lang; ?>"><?php echo $t['menu_home']; ?></a></li>
                <li><a href="downloads.php?lang=<?php echo $lang; ?>" class="active"><?php echo $t['menu_down']; ?></a></li>
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
                <li><a href="https://discord.gg/tuserver" target="_blank"><?php echo $t['menu_forum']; ?></a></li>
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
            <h2 class="section-title"><?php echo $t['title']; ?></h2>
            <div class="download-box">
                <p style="margin-bottom: 2rem; font-size: 1.2rem;"><?php echo $t['desc']; ?></p>
                <a href="https://drive.google.com/file/d/1LqeEN--VIIXcWnWaymQIwDxoAAMuTPZG/view?usp=drive_link" target="_blank" class="drive-btn">
                    <i class="fab fa-google-drive"></i> DOWNLOAD FULL CLIENT
                </a>
                <div class="download-note">
                    <i class="fas fa-info-circle"></i> &nbsp; <?php echo $t['note']; ?>
                </div>

                <hr class="req-separator">

                <h3 style="margin-bottom: 2rem; color: var(--text-light); font-size: 1.5rem;"><?php echo $t['req_title']; ?></h3>

                <div class="requirements-container">
                    <div class="req-column">
                        <div class="req-title"><?php echo $t['min']; ?></div>
                        <div class="req-list">
                            <div class="req-row"><span class="req-label">OS</span><span class="req-value">Win XP/7/10</span></div>
                            <div class="req-row"><span class="req-label">CPU</span><span class="req-value">Dual Core</span></div>
                            <div class="req-row"><span class="req-label">RAM</span><span class="req-value">2 GB</span></div>
                            <div class="req-row"><span class="req-label">GPU</span><span class="req-value">DirectX 9</span></div>
                            <div class="req-row"><span class="req-label">HDD</span><span class="req-value">1 GB</span></div>
                        </div>
                    </div>
                    <div class="req-column">
                        <div class="req-title"><?php echo $t['rec']; ?></div>
                        <div class="req-list">
                            <div class="req-row"><span class="req-label">OS</span><span class="req-value">Windows 10</span></div>
                            <div class="req-row"><span class="req-label">CPU</span><span class="req-value">Ryzen 3 / i5</span></div>
                            <div class="req-row"><span class="req-label">RAM</span><span class="req-value">4 GB</span></div>
                            <div class="req-row"><span class="req-label">GPU</span><span class="req-value">GTX 460+</span></div>
                            <div class="req-row"><span class="req-label">VRAM</span><span class="req-value">1024 MB</span></div>
                            <div class="req-row"><span class="req-label">HDD</span><span class="req-value">2 GB</span></div>
                            <div class="req-row"><span class="req-label">Shader</span><span class="req-value">v5.0</span></div>
                        </div>
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