<?php
session_start();
require 'db.php';
require 'functions.php';

// Si no está logueado, fuera
if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit(); }

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Capturamos de dónde venimos
$from = isset($_GET['from']) ? $_GET['from'] : '';

$myRank = $_SESSION['rank'];
$myId = $_SESSION['user_id'];

// --- DICCIONARIO ---
$txt = [
    'es' => [
        'title' => 'Editar Mensaje',
        'lbl_subject' => 'Título del Tema',
        'lbl_msg' => 'Mensaje',
        'btn_save' => 'GUARDAR CAMBIOS',
        'btn_cancel' => 'Cancelar',
        'err_perm' => 'No tienes permiso para editar este mensaje.',
        'not_found' => 'Mensaje no encontrado.'
    ],
    'en' => [
        'title' => 'Edit Post',
        'lbl_subject' => 'Topic Title',
        'lbl_msg' => 'Message',
        'btn_save' => 'SAVE CHANGES',
        'btn_cancel' => 'Cancel',
        'err_perm' => 'You do not have permission to edit this post.',
        'not_found' => 'Post not found.'
    ]
];
$t = $txt[$lang];

// 1. OBTENER DATOS DEL POST
// Añadimos t.topic_cat para saber a qué categoría volver
$sql = "SELECT p.*, t.topic_subject, t.topic_cat 
        FROM posts p 
        JOIN topics t ON p.post_topic = t.topic_id 
        WHERE p.post_id = $post_id";

$res = $conn->query($sql);
if($res->num_rows == 0) die($t['not_found']);
$post = $res->fetch_assoc();

// Determinar el enlace de cancelación según de dónde venimos
if ($from == 'category') {
    $cancel_link = "category.php?id=" . $post['topic_cat'] . "&lang=" . $lang;
} else {
    // Por defecto volvemos al tema
    $cancel_link = "view_topic.php?id=" . $post['post_topic'] . "&lang=" . $lang;
}

// 2. CALCULAR SI ES EL PRIMER MENSAJE
$sql_first = "SELECT post_id FROM posts WHERE post_topic = " . $post['post_topic'] . " ORDER BY post_id ASC LIMIT 1";
$first_res = $conn->query($sql_first);
$first_row = $first_res->fetch_assoc();
$is_first_post = ($post['post_id'] == $first_row['post_id']);

// 3. VERIFICAR PERMISOS
if (!canEditPost($myRank, $myId, $post['post_by'])) {
    die("<h2 style='color:red;text-align:center;margin-top:50px;'>" . $t['err_perm'] . "</h2>");
}

// 4. GUARDAR CAMBIOS
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = $conn->real_escape_string($_POST['content']);
    
    // Actualizar Post
    $conn->query("UPDATE posts SET post_content = '$content' WHERE post_id = $post_id");
    
    // Si es el primer mensaje, actualizamos Título
    if ($is_first_post && isset($_POST['subject'])) {
        $subject = $conn->real_escape_string($_POST['subject']);
        $topic_id = $post['post_topic'];
        $conn->query("UPDATE topics SET topic_subject = '$subject' WHERE topic_id = $topic_id");
    }
    
    // Al guardar, volvemos a donde corresponde (o al tema para ver el cambio)
    // Generalmente al guardar quieres ver el cambio, así que ir al tema suele ser lo mejor,
    // pero si prefieres volver a la categoría usa $cancel_link.
    // De momento lo mandamos al tema para verificar visualmente.
    header("Location: view_topic.php?id=" . $post['post_topic'] . "&lang=$lang");
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $t['title']; ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="editor.js"></script>
    <style>
        textarea { width:100%; height:300px; background:#0a0a0a; border:1px solid #333; color:#ccc; padding:10px; resize:vertical; font-family: sans-serif; font-size: 1rem; }
        .toolbar button { background:#333; color:#ccc; border:1px solid #555; padding:5px 10px; cursor:pointer; margin-right:2px; }
        .toolbar button:hover { background:#555; color:white; }
    </style>
</head>
<body style="background-image:none; background-color:var(--bg-dark);">
    <main>
        <section class="section auth-container">
            <div class="auth-box" style="max-width:800px; text-align:left;">
                <h2 style="text-align:center; color:var(--primary-gold);"><?php echo $t['title']; ?></h2>
                
                <form method="POST">
                    <?php if($is_first_post): ?>
                    <div class="form-group">
                        <label><?php echo $t['lbl_subject']; ?></label>
                        <input type="text" name="subject" value="<?php echo htmlspecialchars($post['topic_subject']); ?>" class="form-input" required>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label><?php echo $t['lbl_msg']; ?></label>
                        
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

                        <textarea name="content" required><?php echo htmlspecialchars($post['post_content']); ?></textarea>
                    </div>
                    
                    <div style="text-align:center; margin-top:20px;">
                        <button type="submit" class="cta-btn"><?php echo $t['btn_save']; ?></button>
                        <a href="<?php echo $cancel_link; ?>" style="margin-left:20px; color:#888;"><?php echo $t['btn_cancel']; ?></a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>