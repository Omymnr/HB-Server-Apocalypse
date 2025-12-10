<?php
session_start();
require 'db.php';
require 'functions.php';

// SEGURIDAD: Solo Admin(3) y Owner(4)
if (!isset($_SESSION['rank']) || $_SESSION['rank'] < 3) {
    die("<h1>ACCESS DENIED / ACCESO DENEGADO</h1>");
}

$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';
$myRank = $_SESSION['rank'];

// --- DICCIONARIO ---
$txt = [
    'es' => [
        'title' => 'Panel de Admin',
        'back_forum' => 'VOLVER AL FORO',
        'page_title' => 'GESTIÓN DE USUARIOS',
        'th_user' => 'Usuario',
        'th_email' => 'Email',
        'th_stats' => 'Estadísticas',
        'th_rank' => 'Rango Actual',
        'th_action' => 'Acciones',
        'btn_edit' => 'EDITAR COMPLETO',
        'btn_save' => 'Guardar',
        'btn_forbidden' => 'PROHIBIDO',
        'err_promote' => 'No puedes ascender a alguien a Dueño.',
        'msg_success' => 'Rango actualizado correctamente.',
        'opt_member' => 'Miembro',
        'opt_vet' => 'Veterano',
        'opt_admin' => 'Administrador',
        'opt_owner' => 'DUEÑO SERVIDOR',
        'lbl_posts' => 'Mensajes',
        'lbl_topics' => 'Temas Creados'
    ],
    'en' => [
        'title' => 'Admin Panel',
        'back_forum' => 'BACK TO FORUM',
        'page_title' => 'USER MANAGEMENT',
        'th_user' => 'Username',
        'th_email' => 'Email',
        'th_stats' => 'Statistics',
        'th_rank' => 'Current Rank',
        'th_action' => 'Actions',
        'btn_edit' => 'FULL EDIT',
        'btn_save' => 'Save',
        'btn_forbidden' => 'FORBIDDEN',
        'err_promote' => 'You cannot promote someone to Owner.',
        'msg_success' => 'Rank updated successfully.',
        'opt_member' => 'Member',
        'opt_vet' => 'Veteran',
        'opt_admin' => 'Administrator',
        'opt_owner' => 'SERVER OWNER',
        'lbl_posts' => 'Posts',
        'lbl_topics' => 'Topics Created'
    ]
];
$t = $txt[$lang];
$msg = "";

// LÓGICA CAMBIAR RANGO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_rank'])) {
    $target_user_id = (int)$_POST['user_id'];
    $new_rank = (int)$_POST['new_rank'];
    
    if ($_SESSION['rank'] == 3 && $new_rank == 4) {
        $msg = "<div class='msg-box error'>" . $t['err_promote'] . "</div>";
    } else {
        $conn->query("UPDATE users SET rank = $new_rank WHERE id = $target_user_id");
        $msg = "<div class='msg-box success'>" . $t['msg_success'] . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $t['title']; ?> - Helbreath Apocalypse</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: rgba(0,0,0,0.8); }
        .admin-table th { background: #333; color: var(--primary-gold); padding: 10px; text-align: left; }
        .admin-table td { border-bottom: 1px solid #222; padding: 10px; color: #ccc; }
        .action-btn { padding: 5px 10px; text-decoration: none; font-size: 0.8rem; border: 1px solid #555; margin-right: 5px; display:inline-block; }
        .btn-edit { background: #2980b9; color: white; }
        .btn-disabled { background: #333; color: #555; cursor: not-allowed; border-color:#333; }
        .rank-select { background: #111; color: #fff; border: 1px solid #444; padding: 5px; }
        .save-btn { background: var(--secondary-red); color: white; border: none; padding: 5px 10px; cursor: pointer; }
        .save-btn:hover { background: #a00; }
        .stat-badge { font-size: 0.8rem; background: #222; padding: 2px 6px; border-radius: 3px; margin-right: 5px; border: 1px solid #444; cursor: help; }
    </style>
</head>
<body style="background-color: var(--bg-dark);">

    <header>
        <div class="logo">ADMIN PANEL</div>
        <nav><ul><li><a href="forum.php?lang=<?php echo $lang; ?>"><?php echo $t['back_forum']; ?></a></li></ul></nav>
    </header>

    <main>
        <section class="section">
            <h2 class="section-title"><?php echo $t['page_title']; ?></h2>
            <?php echo $msg; ?>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?php echo $t['th_user']; ?></th>
                        <th><?php echo $t['th_email']; ?></th>
                        <th><?php echo $t['th_stats']; ?></th>
                        <th><?php echo $t['th_rank']; ?></th>
                        <th><?php echo $t['th_action']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // SQL: Contamos Posts y Temas para cada usuario
                    $sql = "SELECT id, username, email, rank,
                                   (SELECT COUNT(*) FROM posts WHERE post_by = users.id) as total_posts,
                                   (SELECT COUNT(*) FROM topics WHERE topic_by = users.id) as total_topics
                            FROM users 
                            ORDER BY rank DESC, id ASC";
                    $result = $conn->query($sql);
                    
                    while($row = $result->fetch_assoc()):
                        $rankData = getUserRank($row['rank'], $lang);
                        $canEdit = canManageUser($myRank, $row['rank']);
                    ?>
                    <tr>
                        <td style="font-weight:bold; color:<?php echo $rankData['color']; ?>">
                            <?php echo htmlspecialchars($row['username']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        
                        <td>
                            <span class="stat-badge" title="<?php echo $t['lbl_topics']; ?>">
                                <i class="fas fa-file-alt"></i> <?php echo $row['total_topics']; ?>
                            </span>
                            <span class="stat-badge" title="<?php echo $t['lbl_posts']; ?>">
                                <i class="fas fa-comment"></i> <?php echo $row['total_posts']; ?>
                            </span>
                        </td>

                        <td>
                            <form method="POST" style="display:flex; gap:10px;">
                                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="change_rank" value="1">
                                
                                <select name="new_rank" class="rank-select" <?php if(!$canEdit) echo 'disabled'; ?>>
                                    <option value="1" <?php if($row['rank']==1) echo 'selected'; ?>><?php echo $t['opt_member']; ?></option>
                                    <option value="2" <?php if($row['rank']==2) echo 'selected'; ?>><?php echo $t['opt_vet']; ?></option>
                                    <option value="3" <?php if($row['rank']==3) echo 'selected'; ?>><?php echo $t['opt_admin']; ?></option>
                                    <?php if($_SESSION['rank'] == 4): ?>
                                        <option value="4" <?php if($row['rank']==4) echo 'selected'; ?>><?php echo $t['opt_owner']; ?></option>
                                    <?php endif; ?>
                                </select>
                                <?php if($canEdit): ?>
                                    <button type="submit" class="save-btn"><?php echo $t['btn_save']; ?></button>
                                <?php endif; ?>
                            </form>
                        </td>
                        <td>
                            <?php if($canEdit): ?>
                                <a href="admin_edit_user.php?id=<?php echo $row['id']; ?>&lang=<?php echo $lang; ?>" class="action-btn btn-edit">
                                    <i class="fas fa-edit"></i> <?php echo $t['btn_edit']; ?>
                                </a>
                            <?php else: ?>
                                <span class="action-btn btn-disabled"><i class="fas fa-lock"></i> <?php echo $t['btn_forbidden']; ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>