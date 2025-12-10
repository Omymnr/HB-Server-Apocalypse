<?php
session_start();
require 'db.php';
require 'functions.php';

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';
$topic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$myRank = isset($_SESSION['rank']) ? $_SESSION['rank'] : 0;
$myId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// --- DICCIONARIO ---
$txt = [
    'es' => [
        'back_cat' => 'VOLVER A LA CATEGORÍA',
        'my_profile' => 'MI PERFIL',
        'logout' => 'DESCONECTAR',
        'tools' => 'HERRAMIENTAS:',
        'btn_unpin' => 'QUITAR FIJO',
        'btn_pin' => 'FIJAR TEMA',
        'btn_del' => 'BORRAR TEMA',
        'confirm_del' => '¿Seguro que quieres BORRAR este tema?',
        'reply_title' => 'Responder',
        'btn_reply' => 'ENVIAR RESPUESTA',
        'login_req' => 'Inicia sesión para responder.',
        'login_link' => 'Inicia sesión',
        'btn_edit_post' => 'EDITAR',
        'lbl_posts' => 'Mensajes:' // NUEVO
    ],
    'en' => [
        'back_cat' => 'BACK TO CATEGORY',
        'my_profile' => 'MY PROFILE',
        'logout' => 'LOGOUT',
        'tools' => 'ADMIN TOOLS:',
        'btn_unpin' => 'UNPIN TOPIC',
        'btn_pin' => 'PIN TOPIC',
        'btn_del' => 'DELETE TOPIC',
        'confirm_del' => 'Are you sure you want to DELETE this topic?',
        'reply_title' => 'Post a Reply',
        'btn_reply' => 'SEND REPLY',
        'login_req' => 'Please login to reply.',
        'login_link' => 'Login',
        'btn_edit_post' => 'EDIT',
        'lbl_posts' => 'Posts:' // NEW
    ]
];
$t = $txt[$lang];

// MODERACIÓN
if (isset($_GET['action']) && $myRank >= 3) {
    if ($_GET['action'] == 'delete') {
        $conn->query("DELETE FROM topics WHERE topic_id = $topic_id");
        $conn->query("DELETE FROM posts WHERE post_topic = $topic_id");
        header("Location: forum.php?lang=$lang");
        exit();
    }
    if ($_GET['action'] == 'pin') {
        $conn->query("UPDATE topics SET is_sticky = 1 WHERE topic_id = $topic_id");
        header("Location: view_topic.php?id=$topic_id&lang=$lang");
        exit();
    }
    if ($_GET['action'] == 'unpin') {
        $conn->query("UPDATE topics SET is_sticky = 0 WHERE topic_id = $topic_id");
        header("Location: view_topic.php?id=$topic_id&lang=$lang");
        exit();
    }
}

// RESPUESTA
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])){
    $content = $conn->real_escape_string($_POST['reply_content']);
    $user_id = $_SESSION['user_id'];
    $conn->query("INSERT INTO posts (post_content, post_topic, post_by) VALUES ('$content', $topic_id, $user_id)");
    header("Location: view_topic.php?id=$topic_id&lang=$lang");
    exit();
}

$sql_topic = "SELECT topic_subject, topic_cat, is_sticky FROM topics WHERE topic_id = $topic_id";
$res_topic = $conn->query($sql_topic);
if($res_topic->num_rows == 0) die("Topic not found");
$topic_data = $res_topic->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($topic_data['topic_subject']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="editor.js"></script>
    <style>
        .post-container { background: rgba(18,18,18,0.9); border: 1px solid #333; margin-bottom: 20px; display:flex; position:relative; }
        .post-sidebar { width: 220px; background: rgba(0,0,0,0.5); padding: 20px; text-align: center; border-right: 1px solid #333; }
        .post-content { flex: 1; padding: 20px; font-size: 1rem; line-height: 1.6; display: flex; flex-direction: column; }
        .post-text { flex: 1; } 
        .post-user { font-weight: bold; font-size: 1.1rem; display:block; margin-bottom:5px; margin-top: 10px; }
        .user-rank { font-size: 0.85rem; display: block; margin-bottom: 5px; font-family: 'Cinzel', serif; letter-spacing: 1px; }
        .user-stats { font-size: 0.75rem; color: #888; display: block; margin-top: 5px; } /* Estilo para stats */
        
        .post-date { font-size: 0.8rem; color: #666; margin-bottom: 10px; border-bottom: 1px solid #333; padding-bottom: 10px; display:flex; justify-content:space-between; }
        .reply-box { margin-top: 30px; padding: 20px; background: #111; border-top: 2px solid var(--primary-gold); }
        .avatar-small { width: 100px; height: 100px; border-radius: 50%; border: 2px solid #333; object-fit: cover; margin-bottom: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.5); }
        .post-signature { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #333; color: #888; font-size: 0.9rem; font-style: italic; }
        .mod-tools { margin-bottom: 20px; text-align: right; border-bottom: 1px solid #444; padding-bottom: 10px; }
        .btn-mod { padding: 5px 10px; font-size: 0.8rem; margin-left: 5px; cursor: pointer; text-decoration: none; color: white; }
        .btn-edit-post { color:#777; text-decoration:none; font-size:0.8rem; border:1px solid #444; padding:2px 8px; transition:0.3s; }
        .btn-edit-post:hover { color:white; border-color:white; background:#333; }
        .toolbar button { background:#333; color:#ccc; border:1px solid #555; padding:3px 8px; cursor:pointer; margin-right:2px; font-size:0.8rem; }
        .toolbar button:hover { background:#555; color:white; }
    </style>
</head>
<body class="<?php echo ($lang=='en')?'show-en':'show-es'; ?>">

    <header>
        <div class="logo">HELBREATH <span style="color:white">APOCALYPSE</span></div>
        <nav>
            <ul>
                <li><a href="category.php?id=<?php echo $topic_data['topic_cat']; ?>&lang=<?php echo $lang; ?>"><?php echo $t['back_cat']; ?></a></li>
                <?php if(isset($_SESSION['username'])): ?>
                    <li><a href="profile.php?lang=<?php echo $lang; ?>" style="color:var(--primary-gold);"><i class="fas fa-user"></i> <?php echo $t['my_profile']; ?></a></li>
                    <?php if($myRank >= 3): ?>
                         <li><a href="admin_users.php?lang=<?php echo $lang; ?>" style="color:#ff4d4d; border:1px solid #ff4d4d; padding:5px 10px; margin-left:10px;">ADMIN</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <section class="section">
            <h2 class="section-title" style="text-align:left; font-size:2rem;">
                <?php if($topic_data['is_sticky']) echo '<i class="fas fa-thumbtack" style="color:var(--secondary-red)"></i> '; ?>
                <?php echo htmlspecialchars($topic_data['topic_subject']); ?>
            </h2>

            <?php if($myRank >= 3): ?>
            <div class="mod-tools">
                <span style="color:var(--secondary-red); font-weight:bold; margin-right:10px;"><?php echo $t['tools']; ?></span>
                <?php if($topic_data['is_sticky']): ?>
                    <a href="?id=<?php echo $topic_id; ?>&action=unpin&lang=<?php echo $lang; ?>" class="btn-mod" style="background:#e67e22;"><?php echo $t['btn_unpin']; ?></a>
                <?php else: ?>
                    <a href="?id=<?php echo $topic_id; ?>&action=pin&lang=<?php echo $lang; ?>" class="btn-mod" style="background:#e67e22;"><?php echo $t['btn_pin']; ?></a>
                <?php endif; ?>
                <a href="?id=<?php echo $topic_id; ?>&action=delete&lang=<?php echo $lang; ?>" class="btn-mod" style="background:#c0392b;" onclick="return confirm('<?php echo $t['confirm_del']; ?>');"><?php echo $t['btn_del']; ?></a>
            </div>
            <?php endif; ?>

            <?php
            // SQL MODIFICADO: Añadimos una subconsulta para contar los posts de cada usuario
            $sql_posts = "SELECT p.post_id, p.post_content, p.post_date, p.post_by, 
                                 u.username, u.avatar, u.signature, u.rank,
                                 (SELECT COUNT(*) FROM posts WHERE post_by = u.id) as user_post_count 
                          FROM posts p 
                          LEFT JOIN users u ON p.post_by = u.id 
                          WHERE p.post_topic = $topic_id 
                          ORDER BY p.post_date ASC";
            $res_posts = $conn->query($sql_posts);
            
            while($row = $res_posts->fetch_assoc()):
                $avatar_file = (!empty($row['avatar']) && file_exists("uploads/avatars/".$row['avatar'])) ? $row['avatar'] : 'default.png';
                $rankData = getUserRank($row['rank'], $lang);
                
                $textColor = 'inherit';
                if($row['rank'] == 4) $textColor = '#e9ab00'; 
                elseif($row['rank'] == 3) $textColor = '#ff0000';

                $canEdit = canEditPost($myRank, $myId, $row['post_by']);
            ?>
                <div class="post-container">
                    <div class="post-sidebar">
                        <img src="uploads/avatars/<?php echo $avatar_file; ?>" alt="Avatar" class="avatar-small">
                        
                        <span class="post-user" style="color:<?php echo $rankData['color']; ?>">
                            <?php echo htmlspecialchars($row['username']); ?>
                        </span>
                        
                        <span class="user-rank" style="color:<?php echo $rankData['color']; ?>; <?php echo $rankData['style']; ?>">
                            <?php echo $rankData[$lang]; ?>
                        </span>

                        <span class="user-stats">
                            <?php echo $t['lbl_posts']; ?> <strong><?php echo $row['user_post_count']; ?></strong>
                        </span>

                    </div>
                    <div class="post-content">
                        <div class="post-date">
                            <span><?php echo date("d M Y, H:i", strtotime($row['post_date'])); ?></span>
                            <?php if($canEdit): ?>
                                <a href="edit_post.php?id=<?php echo $row['post_id']; ?>&lang=<?php echo $lang; ?>" class="btn-edit-post">
                                    <i class="fas fa-pen"></i> <?php echo $t['btn_edit_post']; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="post-text" style="color:<?php echo $textColor; ?>;">
                            <?php echo parseBBCode($row['post_content']); ?>
                        </div>
                        <?php if(!empty($row['signature'])): ?>
                            <div class="post-signature">
                                <?php echo parseBBCode($row['signature']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="reply-box">
                    <h3 style="color:var(--text-light); margin-bottom:10px;"><?php echo $t['reply_title']; ?></h3>
                    <div class="toolbar" style="margin-bottom:5px; background:#222; padding:5px; border:1px solid #444;">
                        <button type="button" onclick="insertTag('b')" style="font-weight:bold;">B</button>
                        <button type="button" onclick="insertTag('i')" style="font-style:italic;">I</button>
                        <button type="button" onclick="insertTag('u')" style="text-decoration:underline;">U</button>
                        <button type="button" onclick="insertTag('s')" style="text-decoration:line-through;">S</button>
                        <button type="button" onclick="insertTag('center')">Centrar</button>
                        <button type="button" onclick="insertTag('img')">IMG</button>
                        <button type="button" onclick="insertTag('url')">URL</button>
                        <input type="color" id="colorPicker" onchange="insertColor()" title="Color" style="height:25px; vertical-align:bottom;">
                        <select onchange="insertTag('size', this.value); this.value='';" style="height:25px;">
                            <option value="">Size</option>
                            <option value="12">12</option>
                            <option value="18">18</option>
                            <option value="24">24</option>
                            <option value="30">30</option>
                        </select>
                    </div>
                    <form method="POST">
                        <textarea name="reply_content" style="width:100%; height:150px; background:#0a0a0a; color:#fff; padding:10px; border:1px solid #333;" required></textarea>
                        <button type="submit" class="cta-btn small-btn" style="margin-top:10px;"><?php echo $t['btn_reply']; ?></button>
                    </form>
                </div>
            <?php else: ?>
                <div style="text-align:center; padding:20px; background:#111;">
                    <a href="login.php?lang=<?php echo $lang; ?>" style="color:var(--primary-gold);"><?php echo $t['login_link']; ?></a> <?php echo str_replace($t['login_link'], '', $t['login_req']); ?>
                </div>
            <?php endif; ?>

        </section>
    </main>
</body>
</html>