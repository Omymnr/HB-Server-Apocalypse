<?php
session_start();
require 'db.php';

$msg = "";
$msgClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // Obtenemos también el RANK
    $result = $conn->query("SELECT id, username, password, rank FROM users WHERE username='$username'");
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // LOGIN CORRECTO
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['rank'] = $row['rank']; // ¡Importante! Guardamos el rango
            
            header("Location: forum.php?lang=" . ($_GET['lang'] ?? 'es'));
            exit();
        } else {
            $msg = "Contraseña incorrecta.";
            $msgClass = "error";
        }
    } else {
        $msg = "Usuario no encontrado.";
        $msgClass = "error";
    }
}
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <title>Login - Helbreath Apocalypse</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?php echo ($lang=='en')?'show-en':'show-es'; ?>">

    <header>
        <div class="logo">HELBREATH <span style="color:white">APOCALYPSE</span></div>
        <nav>
            <ul>
                <li><a href="index.php?lang=<?php echo $lang; ?>">INICIO</a></li>
                <li><a href="forum.php?lang=<?php echo $lang; ?>">VOLVER AL FORO</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="section auth-container">
            <div class="auth-box">
                <h2>LOGIN</h2>
                
                <?php if($msg != ''): ?>
                    <div class="msg-box <?php echo $msgClass; ?>"><?php echo $msg; ?></div>
                <?php endif; ?>

                <form action="login.php?lang=<?php echo $lang; ?>" method="POST">
                    <div class="form-group">
                        <label>Usuario</label>
                        <input type="text" name="username" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="password" class="form-input" required>
                    </div>
                    <button type="submit" class="cta-btn" style="width:100%; border:none; cursor:pointer;">ENTRAR</button>
                </form>
                <p style="margin-top:20px; color:#aaa;">¿No tienes cuenta? <a href="register.php?lang=<?php echo $lang; ?>" style="color:var(--primary-gold);">Regístrate</a></p>
            </div>
        </section>
    </main>
    <footer><p class="copyright">&copy; 2025 Helbreath Apocalypse.</p></footer>
</body>
</html>