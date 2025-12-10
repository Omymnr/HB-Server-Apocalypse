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
        'title' => 'Simulador de Personaje',
        'base_stats' => 'Estadísticas Base', 'reset' => 'Reiniciar a Lvl 1',
        'der_stats' => 'Estadísticas Derivadas', 'hp' => 'Puntos de Vida (HP)', 'mp' => 'Puntos de Maná (MP)', 'sp' => 'Stamina (SP)', 'weight' => 'Límite de Peso',
        'p_dmg' => 'Daño Físico', 'hit' => 'Probabilidad de Golpe', 'def' => 'Defensa', 'm_dmg' => 'Daño Mágico', 'm_hit' => 'Golpe Mágico', 'm_res' => 'Resistencia Mágica'
    ],
    'en' => [
        'menu_home' => 'Home', 'menu_down' => 'Downloads', 'menu_news' => 'News', 'menu_rank' => 'Rankings', 'menu_info' => 'Server Info', 'menu_forum' => 'Forum',
        'sub_build' => 'Character Builder', 'sub_best' => 'Bestiary (Mobs)', 'sub_atlas' => 'Atlas (Maps)', 'sub_item' => 'Items Database', 'sub_spell' => 'Spells & Skills', 'sub_event' => 'Events', 'sub_rules' => 'Rules',
        'title' => 'Character Simulator',
        'base_stats' => 'Base Stats', 'reset' => 'Reset to Lvl 1',
        'der_stats' => 'Derived Stats', 'hp' => 'Hit Points (HP)', 'mp' => 'Mana Points (MP)', 'sp' => 'Stamina (SP)', 'weight' => 'Weight Limit',
        'p_dmg' => 'Physical Damage', 'hit' => 'Hit Probability', 'def' => 'Defense', 'm_dmg' => 'Magic Damage', 'm_hit' => 'Magic Hit', 'm_res' => 'Magic Resist'
    ]
];
$t = $txt[$lang];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Character Simulator - Helbreath Apocalypse</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .simulator-container { display: flex; flex-wrap: wrap; gap: 2rem; justify-content: center; align-items: flex-start; }
        .sim-controls { flex: 1; min-width: 300px; background: rgba(0, 0, 0, 0.6); border: 1px solid var(--border-color); padding: 2rem; }
        .sim-results { flex: 1; min-width: 300px; background: rgba(0, 0, 0, 0.8); border: 1px solid var(--primary-gold); padding: 2rem; box-shadow: 0 0 20px rgba(197, 160, 89, 0.1); }
        .control-group { margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #333; padding-bottom: 0.5rem; }
        .control-label { font-family: 'Cinzel', serif; color: var(--primary-gold); font-weight: bold; font-size: 1.1rem; }
        .input-wrapper { display: flex; align-items: center; gap: 5px; }
        .stat-btn { background: #333; color: white; border: 1px solid #555; width: 30px; height: 30px; cursor: pointer; font-weight: bold; transition: 0.2s; }
        .stat-btn:hover { background: var(--primary-gold); color: black; }
        .stat-input { width: 60px; background: #111; border: 1px solid var(--primary-gold); color: white; padding: 5px; text-align: center; font-family: 'Roboto', sans-serif; font-size: 1rem; }
        .result-row { display: flex; justify-content: space-between; padding: 0.8rem 0; border-bottom: 1px solid #444; }
        .result-row:last-child { border-bottom: none; }
        .res-label { color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; }
        .res-value { color: var(--text-light); font-weight: bold; font-size: 1.1rem; }
        .highlight { color: var(--secondary-red); } .mana { color: #3498db; } .stamina { color: #2ecc71; }
        .reset-btn { width: 100%; margin-top: 1rem; background: #333; color: var(--text-muted); border: 1px solid #555; padding: 10px; cursor: pointer; text-transform: uppercase; font-weight: bold; }
        .reset-btn:hover { background: var(--secondary-red); color: white; border-color: var(--secondary-red); }
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
                <li><a href="rankings.php?lang=<?php echo $lang; ?>"><?php echo $t['menu_rank']; ?></a></li>
                <li>
                    <a href="#" class="active"><?php echo $t['menu_info']; ?> <i class="fas fa-chevron-down"></i></a>
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

            <div class="simulator-container">
                <div class="sim-controls">
                    <h3 style="margin-bottom: 2rem; color:white; text-align:center;"><?php echo $t['base_stats']; ?></h3>
                    
                    <?php 
                    $stats = ['Level' => 'level', 'STR' => 'str', 'VIT' => 'vit', 'DEX' => 'dex', 'INT' => 'int', 'MAG' => 'mag', 'CHR' => 'chr'];
                    foreach($stats as $label => $id) {
                        $max = ($id == 'level') ? 180 : (($id == 'chr') ? 50 : 200);
                        $val = ($id == 'level') ? 1 : 10;
                        echo '<div class="control-group">
                                <span class="control-label">'.$label.'</span>
                                <div class="input-wrapper">
                                    <button class="stat-btn" onclick="updateVal(\''.$id.'\', -1)">-</button>
                                    <input type="number" id="'.$id.'" class="stat-input" value="'.$val.'" min="'.$val.'" max="'.$max.'" oninput="calculateStats()">
                                    <button class="stat-btn" onclick="updateVal(\''.$id.'\', 1)">+</button>
                                </div>
                              </div>';
                    }
                    ?>
                    <button class="reset-btn" onclick="resetStats()"><?php echo $t['reset']; ?></button>
                </div>

                <div class="sim-results">
                    <h3 style="margin-bottom: 2rem; color:white; text-align:center;"><?php echo $t['der_stats']; ?></h3>
                    
                    <div class="result-row"><span class="res-label"><?php echo $t['hp']; ?></span><span class="res-value highlight" id="res-hp">0</span></div>
                    <div class="result-row"><span class="res-label"><?php echo $t['mp']; ?></span><span class="res-value mana" id="res-mp">0</span></div>
                    <div class="result-row"><span class="res-label"><?php echo $t['sp']; ?></span><span class="res-value stamina" id="res-sp">0</span></div>
                    <div class="result-row"><span class="res-label"><?php echo $t['weight']; ?></span><span class="res-value" id="res-weight">0</span></div>
                    <div class="result-row" style="margin-top:1rem; border-top: 2px solid var(--primary-gold);"><span class="res-label"><?php echo $t['p_dmg']; ?></span><span class="res-value" id="res-dmg">0</span></div>
                    <div class="result-row"><span class="res-label"><?php echo $t['hit']; ?></span><span class="res-value" id="res-hit">0</span></div>
                    <div class="result-row"><span class="res-label"><?php echo $t['def']; ?></span><span class="res-value" id="res-def">0</span></div>
                    <div class="result-row" style="margin-top:1rem; border-top: 2px solid var(--primary-gold);"><span class="res-label"><?php echo $t['m_dmg']; ?></span><span class="res-value mana" id="res-mag-dmg">0</span></div>
                    <div class="result-row"><span class="res-label"><?php echo $t['m_hit']; ?></span><span class="res-value mana" id="res-mag-hit">0</span></div>
                    <div class="result-row"><span class="res-label"><?php echo $t['m_res']; ?></span><span class="res-value mana" id="res-mag-res">0</span></div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="social-links"><a href="#"><i class="fab fa-discord"></i></a></div>
        <p class="copyright">&copy; 2025 Helbreath Apocalypse.</p>
    </footer>
    <script src="script.js"></script>
    <script>
        function updateVal(id, change) {
            const input = document.getElementById(id);
            let val = parseInt(input.value) + change;
            let min = parseInt(input.min); let max = parseInt(input.max);
            if (val >= min && val <= max) { input.value = val; calculateStats(); }
        }
        function resetStats() {
            const ids = ['level','str','vit','dex','int','mag','chr'];
            ids.forEach(id => document.getElementById(id).value = (id === 'level') ? 1 : 10);
            calculateStats();
        }
        function calculateStats() {
            const lv = parseInt(document.getElementById('level').value)||1;
            const str = parseInt(document.getElementById('str').value)||10;
            const vit = parseInt(document.getElementById('vit').value)||10;
            const dex = parseInt(document.getElementById('dex').value)||10;
            const int = parseInt(document.getElementById('int').value)||10;
            const mag = parseInt(document.getElementById('mag').value)||10;

            const hp = 50+(lv*2)+(vit*3.5);
            const mp = 20+(lv*1.5)+(mag*3)+(int*1.2);
            const sp = 60+lv+(str*2.2);
            const weight = str*3.5+80;
            const dmg = Math.floor((str/3)+(dex/12));
            const hit = Math.floor(dex+(lv/2));
            const def = Math.floor(dex/5);
            const magDmg = Math.floor((mag/1.8)+(int/4));
            const magHit = Math.floor((mag/2)+(dex/6));
            const magRes = Math.floor((int/3)+(lv/4));

            document.getElementById('res-hp').innerText = Math.floor(hp);
            document.getElementById('res-mp').innerText = Math.floor(mp);
            document.getElementById('res-sp').innerText = Math.floor(sp);
            document.getElementById('res-weight').innerText = Math.floor(weight);
            document.getElementById('res-dmg').innerText = dmg;
            document.getElementById('res-hit').innerText = hit;
            document.getElementById('res-def').innerText = def;
            document.getElementById('res-mag-dmg').innerText = magDmg;
            document.getElementById('res-mag-hit').innerText = magHit;
            document.getElementById('res-mag-res').innerText = magRes + " %";
        }
        window.onload = function() { calculateStats(); }
    </script>
</body>
</html>