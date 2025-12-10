<?php
session_start();
require 'db.php';
require 'functions.php';

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';
$lParam = ($lang == 'es') ? 'en' : 'es';
$myRank = isset($_SESSION['rank']) ? $_SESSION['rank'] : 0;

// Obtenemos la categoría
$cat_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// --- DICCIONARIO DE TRADUCCIÓN ---
$txt = [
    'es' => [
        'cat_1' => 'Noticias y Anuncios',
        'cat_2' => 'Discusión General',
        'cat_3' => 'Mercado',
        'cat_4' => 'Clanes',
        'btn_new' => 'NUEVO TEMA',
        'login_msg' => 'Inicia sesión para crear temas.',
        'th_topic' => 'Tema',
        'th_author' => 'Creador',
        'th_date' => 'Fecha',
        'th_last' => 'Último Mensaje',
        'th_admin' => 'Admin',
        'no_topics' => 'No hay temas en esta categoría aún.',
        'back_index' => 'VOLVER AL ÍNDICE',
        'my_profile' => 'MI PERFIL',
        'logout' => 'DESCONECTAR',
        'new_tag' => 'NUEVO',
        'confirm_del' => '¿Estás seguro de BORRAR este tema completo?'
    ],
    'en' => [
        'cat_1' => 'News & Announcements',
        'cat_2' => 'General Discussion',
        'cat_3' => 'Marketplace',
        'cat_4' => 'Guilds',
        'btn_new' => 'NEW TOPIC',
        'login_msg' => 'Login to post new topics.',
        'th_topic' => 'Topic',
        'th_author' => 'Author',
        'th_date' => 'Date',
        'th_last' => 'Last Post',
        'th_admin' => 'Admin',
        'no_topics' => 'No topics found in this category.',
        'back_index' => 'BACK TO INDEX',
        'my_profile' => 'MY PROFILE',
        'logout' => 'LOGOUT',
        'new_tag' => 'NEW',
        'confirm_del' => 'Are you sure you want to DELETE this entire topic?'
    ]
];
$t = $txt[$lang];

// --- LÓGICA DE BORRADO DESDE LA LISTA ---
if (isset($_GET['action']) && $_GET['action'] == 'delete_topic' && isset($_GET['tid'])) {
    if ($myRank >= 3) { // Solo Admin(3) o Owner(4)
        $del_id = (int)$_GET['tid'];
        $conn->query("DELETE FROM topics WHERE topic_id = $del_id");
        $conn->query("DELETE FROM posts WHERE post_topic = $del_id");
        header("Location: category.php?id=$cat_id&lang=$lang");
        exit();
    }
}

$cat_key = 'cat_' . $cat_id;
$cat_title = isset($t[$cat_key]) ? $t[$cat_key] : "Forum";
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $cat_title; ?> - Helbreath Apocalypse</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-actions a {
            display: inline-block;
            margin: 0 5px;
            color: #ccc;
            transition: 0.3s;
            font-size: 1rem;
        }
        .admin-actions a:hover { transform: scale(1.2); }
        .fa-edit { color: #3498db; }
        .fa-trash { color: #e74c3c; }
    </style>
</head>
<body class="<?php echo ($lang=='en')?'show-en':'show-es'; ?>">

    <header>
        <div class="logo">HELBREATH <span style="color:white">APOCALYPSE</span></div>
        <nav>
            <ul>
                <li><a href="forum.php?lang=<?php echo $lang; ?>"><?php echo $t['back_index']; ?></a></li>
                
                <?php if(isset($_SESSION['username'])): ?>
                    <li>
                        <a href="profile.php?lang=<?php echo $lang; ?>" style="color:var(--primary-gold);">
                            <i class="fas fa-user"></i> <?php echo $t['my_profile']; ?>
                        </a>
                    </li>
                    <?php if(isset($_SESSION['rank']) && $_SESSION['rank'] >= 3): ?>
                         <li><a href="admin_users.php?lang=<?php echo $lang; ?>" style="color:#ff4d4d; border:1px solid #ff4d4d; padding:5px 10px; margin-left:10px;">ADMIN</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <section class="section">
            <h2 class="section-title"><?php echo $cat_title; ?></h2>
            
            <div class="forum-actions">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="new_topic.php?cat=<?php echo $cat_id; ?>&lang=<?php echo $lang; ?>" class="cta-btn small-btn">
                        <i class="fas fa-plus"></i> <?php echo $t['btn_new']; ?>
                    </a>
                <?php else: ?>
                    <span style="color:#888;"><?php echo $t['login_msg']; ?></span>
                <?php endif; ?>
            </div>

            <div class="news-table-container">
                <table class="news-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th style="width:<?php echo ($myRank >= 3) ? '40%' : '50%'; ?>;"><?php echo $t['th_topic']; ?></th>
                            <th style="width:15%;"><?php echo $t['th_author']; ?></th>
                            <th style="width:15%;"><?php echo $t['th_date']; ?></th>
                            <th style="width:20%;"><?php echo $t['th_last']; ?></th>
                            <?php if($myRank >= 3): ?>
                                <th style="width:10%; text-align:center;"><?php echo $t['th_admin']; ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT 
                                    t.topic_id, t.topic_subject, t.topic_date, t.is_sticky, 
                                    creator.username as creator_name, creator.rank as creator_rank,
                                    last_post.post_date as last_post_date,
                                    last_user.username as last_user_name, last_user.rank as last_user_rank,
                                    (SELECT post_id FROM posts WHERE post_topic = t.topic_id ORDER BY post_id ASC LIMIT 1) as first_post_id
                                FROM topics t 
                                LEFT JOIN users creator ON t.topic_by = creator.id
                                LEFT JOIN (
                                    SELECT post_topic, MAX(post_id) as max_id
                                    FROM posts
                                    GROUP BY post_topic
                                ) latest_id ON t.topic_id = latest_id.post_topic
                                LEFT JOIN posts last_post ON latest_id.max_id = last_post.post_id
                                LEFT JOIN users last_user ON last_post.post_by = last_user.id
                                WHERE t.topic_cat = $cat_id 
                                ORDER BY t.is_sticky DESC, last_post.post_date DESC";
                        
                        $result = $conn->query($sql);

                        if($result->num_rows > 0):
                            while($row = $result->fetch_assoc()):
                                $creatorRank = getUserRank($row['creator_rank'], $lang);
                                $lastRank = getUserRank($row['last_user_rank'], $lang);
                                $is_new = (time() - strtotime($row['last_post_date'])) < 86400;
                        ?>
                            <tr <?php if($row['is_sticky']) echo 'style="background:rgba(255,215,0,0.05);"'; ?>>
                                <td>
                                    <?php if($row['is_sticky']): ?>
                                        <i class="fas fa-thumbtack" style="color:var(--secondary-red); margin-right:5px;" title="Fijado"></i>
                                    <?php endif; ?>
                                    
                                    <?php if($is_new): ?>
                                        <span class="badge-new"><?php echo $t['new_tag']; ?></span>
                                    <?php endif; ?>
                                    
                                    <a href="view_topic.php?id=<?php echo $row['topic_id']; ?>&lang=<?php echo $lang; ?>" style="font-weight:bold; font-size:1.1rem; color:var(--text-light);">
                                        <?php echo htmlspecialchars($row['topic_subject']); ?>
                                    </a>
                                </td>
                                
                                <td style="color:<?php echo $creatorRank['color']; ?>; font-weight:bold;">
                                    <?php echo htmlspecialchars($row['creator_name']); ?>
                                </td>
                                
                                <td style="color:#888; font-size:0.8rem;">
                                    <?php echo date("d/m/Y", strtotime($row['topic_date'])); ?>
                                </td>

                                <td>
                                    <div style="font-size:0.8rem; line-height:1.2;">
                                        <span style="color:<?php echo $lastRank['color']; ?>; font-weight:bold;">
                                            <?php echo htmlspecialchars($row['last_user_name']); ?>
                                        </span><br>
                                        <span style="color:#666;">
                                            <?php 
                                                $pTime = strtotime($row['last_post_date']);
                                                if(date('Ymd') == date('Ymd', $pTime)){
                                                    echo "Hoy, " . date("H:i", $pTime);
                                                } else {
                                                    echo date("d M Y, H:i", $pTime);
                                                }
                                            ?>
                                        </span>
                                    </div>
                                </td>

                                <?php if($myRank >= 3): ?>
                                <td class="admin-actions" style="text-align:center;">
                                    <a href="edit_post.php?id=<?php echo $row['first_post_id']; ?>&lang=<?php echo $lang; ?>&from=category" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="category.php?id=<?php echo $cat_id; ?>&action=delete_topic&tid=<?php echo $row['topic_id']; ?>&lang=<?php echo $lang; ?>" title="Borrar" onclick="return confirm('<?php echo $t['confirm_del']; ?>');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                                <?php endif; ?>

                            </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                            <tr><td colspan="<?php echo ($myRank >= 3) ? '5' : '4'; ?>" style="text-align:center; padding:2rem;"><?php echo $t['no_topics']; ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <footer><p class="copyright">&copy; 2025 Helbreath Apocalypse.</p></footer>
</body>
</html>