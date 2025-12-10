<?php
session_start();
require 'db.php';

// Si no está logueado, fuera
if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$msg = "";
$msgClass = "";

// --- LÓGICA DE ACTUALIZACIÓN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. CAMBIAR AVATAR
    if (isset($_FILES['avatar']) && $_FILES['avatar']['name'] != "") {
        $target_dir = "uploads/avatars/";
        $ext = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
        $valid_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if(in_array($ext, $valid_ext)){
            $new_name = $user_id . "_" . time() . "." . $ext;
            $target_file = $target_dir . $new_name;
            
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                $conn->query("UPDATE users SET avatar = '$new_name' WHERE id = $user_id");
                $msg = "Avatar actualizado correctamente.";
                $msgClass = "success";
            } else {
                $msg = "Error al subir la imagen.";
                $msgClass = "error";
            }
        } else {
            $msg = "Formato no válido.";
            $msgClass = "error";
        }
    }

    // 2. CAMBIAR FIRMA (NUEVO)
    if (isset($_POST['update_signature'])) {
        $signature = $conn->real_escape_string($_POST['signature']);
        // Guardamos la firma
        $conn->query("UPDATE users SET signature = '$signature' WHERE id = $user_id");
        $msg = "Firma actualizada correctamente.";
        $msgClass = "success";
    }

    // 3. CAMBIAR DATOS (Usuario / Email)
    if (isset($_POST['update_info'])) {
        $new_user  = $conn->real_escape_string($_POST['username']);
        $new_email = $conn->real_escape_string($_POST['email']);
        
        $check = $conn->query("SELECT id FROM users WHERE (username='$new_user' OR email='$new_email') AND id != $user_id");
        if($check->num_rows > 0){
            $msg = "Nombre o Email en uso.";
            $msgClass = "error";
        } else {
            $conn->query("UPDATE users SET username = '$new_user', email = '$new_email' WHERE id = $user_id");
            $_SESSION['username'] = $new_user; 
            $msg = "Datos actualizados.";
            $msgClass = "success";
        }
    }

    // 4. CAMBIAR CONTRASEÑA
    if (isset($_POST['update_pass'])) {
        $current_pass = $_POST['current_pass'];
        $new_pass = $_POST['new_pass'];
        $confirm_pass = $_POST['confirm_pass'];

        $sql = "SELECT password FROM users WHERE id = $user_id";
        $row = $conn->query($sql)->fetch_assoc();

        if (password_verify($current_pass, $row['password'])) {
            if ($new_pass === $confirm_pass) {
                if(strlen($new_pass) >= 4) {
                    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $conn->query("UPDATE users SET password = '$hash' WHERE id = $user_id");
                    $msg = "Contraseña cambiada.";
                    $msgClass = "success";
                } else {
                    $msg = "Contraseña muy corta.";
                    $msgClass = "error";
                }
            } else {
                $msg = "No coinciden las contraseñas.";
                $msgClass = "error";
            }
        } else {
            $msg = "Contraseña actual incorrecta.";
            $msgClass = "error";
        }
    }
}

// OBTENER DATOS ACTUALES (Incluyendo firma)
$sql_user = "SELECT username, email, avatar, signature, created_at FROM users WHERE id = $user_id";
$user = $conn->query($sql_user)->fetch_assoc();
$avatar_file = ($user['avatar'] && file_exists("uploads/avatars/".$user['avatar'])) ? $user['avatar'] : 'default.png';
$avatar_path = "uploads/avatars/" . $avatar_file;
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil - Helbreath Apocalypse</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?php echo ($lang=='en')?'show-en':'show-es'; ?>">

    <header>
        <div class="logo">HELBREATH <span style="color:white">APOCALYPSE</span></div>
        <nav>
            <ul>
                <li><a href="forum.php?lang=<?php echo $lang; ?>">VOLVER AL FORO</a></li>
                <li><a href="logout.php" style="color:#ff4d4d;">CERRAR SESIÓN</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="section">
            <h2 class="section-title">PANEL DE CONTROL</h2>

            <?php if($msg != ''): ?>
                <div class="msg-box <?php echo $msgClass; ?>" style="text-align:center;"><?php echo $msg; ?></div>
            <?php endif; ?>

            <div class="profile-grid">
                
                <div class="profile-card">
                    <img src="<?php echo $avatar_path; ?>" alt="Avatar" class="current-avatar">
                    <h3 style="color:var(--text-light); margin-bottom:1rem;"><?php echo htmlspecialchars($user['username']); ?></h3>
                    
                    <form action="" method="POST" enctype="multipart/form-data">
                        <label style="display:block; margin-bottom:10px; color:#888; font-size:0.8rem;">Cambiar Imagen</label>
                        <input type="file" name="avatar" style="color:#aaa; font-size:0.8rem; width:100%; margin-bottom:10px;">
                        <button type="submit" class="cta-btn small-btn" style="width:100%;">SUBIR FOTO</button>
                    </form>
                    
                    <div style="margin-top:2rem; font-size:0.8rem; color:#666;">
                        Registrado el:<br> <?php echo date("d/m/Y", strtotime($user['created_at'])); ?>
                    </div>
                </div>

                <div class="profile-forms">
                    
                    <h3 class="profile-section-title"><i class="fas fa-pen-nib"></i> Firma del Foro</h3>
                    <form action="" method="POST" style="margin-bottom:3rem;">
                        <input type="hidden" name="update_signature" value="1">
                        <div class="form-group">
                            <label>Texto de la Firma</label>
                            <textarea name="signature" class="form-input" style="height:80px; resize:vertical;"><?php echo htmlspecialchars($user['signature'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="cta-btn small-btn outline">GUARDAR FIRMA</button>
                    </form>

                    <h3 class="profile-section-title"><i class="fas fa-id-card"></i> Datos Personales</h3>
                    <form action="" method="POST" style="margin-bottom:3rem;">
                        <input type="hidden" name="update_info" value="1">
                        <div class="form-group">
                            <label>Nombre de Usuario</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-input" required>
                        </div>
                        <button type="submit" class="cta-btn small-btn outline">GUARDAR DATOS</button>
                    </form>

                    <h3 class="profile-section-title"><i class="fas fa-lock"></i> Seguridad</h3>
                    <form action="" method="POST">
                        <input type="hidden" name="update_pass" value="1">
                        <div class="form-group">
                            <label>Contraseña Actual</label>
                            <input type="password" name="current_pass" class="form-input" required>
                        </div>
                        <div style="display:flex; gap:1rem;">
                            <div class="form-group" style="flex:1;">
                                <label>Nueva Contraseña</label>
                                <input type="password" name="new_pass" class="form-input" required>
                            </div>
                            <div class="form-group" style="flex:1;">
                                <label>Confirmar</label>
                                <input type="password" name="confirm_pass" class="form-input" required>
                            </div>
                        </div>
                        <button type="submit" class="cta-btn small-btn" style="border-color:#ff4d4d; color:#ff4d4d;">CAMBIAR CONTRASEÑA</button>
                    </form>
                </div>

            </div>
        </section>
    </main>
    <footer><p class="copyright">&copy; 2025 Helbreath Apocalypse.</p></footer>
</body>
</html>