<?php
session_start();
require 'db.php';

if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit(); }

$cat_id = isset($_GET['cat']) ? (int)$_GET['cat'] : 2;
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';

// --- DICCIONARIO ---
$txt = [
    'es' => [
        'title_page' => 'Crear Nuevo Tema',
        'label_subject' => 'Título del Tema',
        'label_msg' => 'Mensaje',
        'btn_post' => 'PUBLICAR TEMA',
        'cancel' => 'Cancelar'
    ],
    'en' => [
        'title_page' => 'Create New Topic',
        'label_subject' => 'Topic Title',
        'label_msg' => 'Message',
        'btn_post' => 'POST TOPIC',
        'cancel' => 'Cancel'
    ]
];
$t = $txt[$lang];

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $subject = $conn->real_escape_string($_POST['subject']);
    $content = $conn->real_escape_string($_POST['content']);
    $user_id = $_SESSION['user_id'];
    $cat     = (int)$_POST['cat_id'];

    $sql_topic = "INSERT INTO topics (topic_subject, topic_cat, topic_by) VALUES ('$subject', $cat, $user_id)";
    
    if($conn->query($sql_topic)){
        $topic_id = $conn->insert_id;
        $sql_post = "INSERT INTO posts (post_content, post_topic, post_by) VALUES ('$content', $topic_id, $user_id)";
        $conn->query($sql_post);
        header("Location: view_topic.php?id=$topic_id&lang=$lang");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $t['title_page']; ?></title>
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
                <h2 style="text-align:center; color:var(--primary-gold);"><?php echo $t['title_page']; ?></h2>
                <form method="POST">
                    <input type="hidden" name="cat_id" value="<?php echo $cat_id; ?>">
                    <div class="form-group">
                        <label><?php echo $t['label_subject']; ?></label>
                        <input type="text" name="subject" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo $t['label_msg']; ?></label>
                        
                        <div class="toolbar" style="margin-bottom:5px; background:#222; padding:5px; border:1px solid #444;">
                            <button type="button" onclick="insertTag('b')" style="font-weight:bold;">B</button>
                            <button type="button" onclick="insertTag('i')" style="font-style:italic;">I</button>
                            <button type="button" onclick="insertTag('u')" style="text-decoration:underline;">U</button>
                            <button type="button" onclick="insertTag('s')" style="text-decoration:line-through;">S</button>
                            <button type="button" onclick="insertTag('center')">Center</button>
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

                        <textarea name="content" required></textarea>
                    </div>
                    
                    <div style="text-align:center; margin-top:20px;">
                        <button type="submit" class="cta-btn"><?php echo $t['btn_post']; ?></button>
                        <a href="category.php?id=<?php echo $cat_id; ?>&lang=<?php echo $lang; ?>" style="margin-left:20px; color:#888;"><?php echo $t['cancel']; ?></a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>