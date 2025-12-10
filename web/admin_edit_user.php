<?php
session_start();
require 'db.php';
require 'functions.php';

// SEGURIDAD
if (!isset($_SESSION['rank']) || $_SESSION['rank'] < 3) die("Access Denied");

$myRank = $_SESSION['rank'];
$target_id = (int)$_GET['id'];
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';
$msg = "";

// --- DICCIONARIO ---
$txt = [
    'es' => [
        'page_title' => 'Editar Usuario - Admin',
        'editing' => 'EDITANDO A:',
        'lbl_user' => 'Nombre de Usuario',
        'lbl_email' => 'Email',
        'lbl_rank' => 'Rango',
        'rank_0' => 'Baneado / Invitado',
        'rank_1' => 'Miembro',
        'rank_2' => 'Veterano',
        'rank_3' => 'Administrador',
        'rank_4' => 'SERVER OWNER',
        'lbl_pass' => 'Forzar Nueva Contraseña (Opcional)',
        'ph_pass' => 'Escribe para cambiarla...',
        'lbl_sig' => 'Firma',
        'lbl_avatar' => 'Avatar',
        'chk_del_avatar' => 'Borrar Avatar (Poner default)',
        'btn_save' => 'GUARDAR TODO',
        'btn_cancel' => 'Cancelar',
        'msg_pass_reset' => 'Contraseña reiniciada. ',
        'err_owner' => 'ERROR: No puedes nombrar Owners. ',
        'msg_avatar_reset' => 'Avatar reseteado. ',
        'msg_saved' => 'Cambios guardados: ',
        'err_perm' => 'ERROR: No tienes rango suficiente para editar a este usuario.',
        'err_not_found' => 'Usuario no encontrado'
    ],
    'en' => [
        'page_title' => 'Edit User - Admin',
        'editing' => 'EDITING:',
        'lbl_user' => 'Username',
        'lbl_email' => 'Email',
        'lbl_rank' => 'Rank',
        'rank_0' => 'Banned / Guest',
        'rank_1' => 'Member',
        'rank_2' => 'Veteran',
        'rank_3' => 'Administrator',
        'rank_4' => 'SERVER OWNER',
        'lbl_pass' => 'Force New Password (Optional)',
        'ph_pass' => 'Type to change...',
        'lbl_sig' => 'Signature',
        'lbl_avatar' => 'Avatar',
        'chk_del_avatar' => 'Delete Avatar (Set default)',
        'btn_save' => 'SAVE ALL',
        'btn_cancel' => 'Cancel',
        'msg_pass_reset' => 'Password reset. ',
        'err_owner' => 'ERROR: You cannot name Owners. ',
        'msg_avatar_reset' => 'Avatar reset. ',
        'msg_saved' => 'Changes saved: ',
        'err_perm' => 'ERROR: You do not have enough rank to edit this user.',
        'err_not_found' => 'User not found'
    ]
];
$t = $txt[$lang];

// Obtener datos del objetivo
$sql = "SELECT * FROM users WHERE id = $target_id";
$res = $conn->query($sql);
if($res->num_rows == 0) die($t['err_not_found']);
$target = $res->fetch_assoc();

// VERIFICACIÓN CRÍTICA
if (!canManageUser($myRank, $target['rank'])) {
    die("<h2 style='color:red; text-align:center;'>" . $t['err_perm'] . "</h2>");
}

// --- PROCESAR CAMBIOS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. Resetear Contraseña
    if (!empty($_POST['new_pass'])) {
        $hash = password_hash($_POST['new_pass'], PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '$hash' WHERE id = $target_id");
        $msg .= $t['msg_pass_reset'];
    }

    // 2. Cambiar Rango
    if (isset($_POST['rank'])) {
        $new_rank = (int)$_POST['rank'];
        if ($myRank == 3 && $new_rank == 4) {
            $msg .= $t['err_owner'];
        } else {
            $conn->query("UPDATE users SET rank = $new_rank WHERE id = $target_id");
        }
    }

    // 3. Otros datos
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $signature = $conn->real_escape_string($_POST['signature']);
    
    $conn->query("UPDATE users SET username='$username', email='$email', signature='$signature' WHERE id = $target_id");
    
    // 4. Borrar Avatar
    if (isset($_POST['delete_avatar'])) {
        $conn->query("UPDATE users SET avatar = 'default.png' WHERE id = $target_id");
        $msg .= $t['msg_avatar_reset'];
    }

    $msg = "<div class='msg-box success'>" . $t['msg_saved'] . $msg . "</div>";
    
    // Recargar datos
    $target = $conn->query("SELECT * FROM users WHERE id = $target_id")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $t['page_title']; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background:var(--bg-dark);">
    <main>
        <section class="section auth-container">
            <div class="auth-box" style="max-width:600px; text-align:left;">
                <h2><?php echo $t['editing']; ?> <span style="color:var(--primary-gold)"><?php echo htmlspecialchars($target['username']); ?></span></h2>
                <?php echo $msg; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label><?php echo $t['lbl_user']; ?></label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($target['username']); ?>" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo $t['lbl_email']; ?></label>
                        <input type="text" name="email" value="<?php echo htmlspecialchars($target['email']); ?>" class="form-input">
                    </div>

                    <div class="form-group">
                        <label><?php echo $t['lbl_rank']; ?></label>
                        <select name="rank" class="form-input" style="background:#222;">
                            <option value="0" <?php if($target['rank']==0) echo 'selected'; ?>><?php echo $t['rank_0']; ?></option>
                            <option value="1" <?php if($target['rank']==1) echo 'selected'; ?>><?php echo $t['rank_1']; ?></option>
                            <option value="2" <?php if($target['rank']==2) echo 'selected'; ?>><?php echo $t['rank_2']; ?></option>
                            <option value="3" <?php if($target['rank']==3) echo 'selected'; ?>><?php echo $t['rank_3']; ?></option>
                            <?php if($myRank == 4): ?>
                                <option value="4" <?php if($target['rank']==4) echo 'selected'; ?>><?php echo $t['rank_4']; ?></option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group" style="background:#330000; padding:10px; border:1px solid red;">
                        <label style="color:red;"><?php echo $t['lbl_pass']; ?></label>
                        <input type="text" name="new_pass" placeholder="<?php echo $t['ph_pass']; ?>" class="form-input">
                    </div>

                    <div class="form-group">
                        <label><?php echo $t['lbl_sig']; ?></label>
                        <textarea name="signature" class="form-input" style="height:60px;"><?php echo htmlspecialchars($target['signature'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label><?php echo $t['lbl_avatar']; ?></label><br>
                        <img src="uploads/avatars/<?php echo $target['avatar'] ? $target['avatar'] : 'default.png'; ?>" style="width:50px; height:50px; vertical-align:middle;">
                        <input type="checkbox" name="delete_avatar"> <?php echo $t['chk_del_avatar']; ?>
                    </div>

                    <button type="submit" class="cta-btn small-btn"><?php echo $t['btn_save']; ?></button>
                    <a href="admin_users.php?lang=<?php echo $lang; ?>" style="margin-left:20px; color:#aaa;"><?php echo $t['btn_cancel']; ?></a>
                </form>
            </div>
        </section>
    </main>
</body>
</html>