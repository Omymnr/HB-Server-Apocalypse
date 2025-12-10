<?php
session_start();
require 'db.php';

// --- LOGICA DE REGISTRO ---
$msg = "";
$msgClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $pass1 = $_POST['password'];
    $pass2 = $_POST['confirm_password'];

    if ($pass1 !== $pass2) {
        $msg = "Passwords do not match!";
        $msgClass = "error";
    } else {
        // Verificar si existe usuario
        $check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'");
        if ($check->num_rows > 0) {
            $msg = "Username or Email already taken!";
            $msgClass = "error";
        } else {
            // Encriptar y guardar
            $hashed_pass = password_hash($pass1, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$hashed_pass')";
            
            if ($conn->query($sql) === TRUE) {
                $msg = "Account created successfully! <a href='login.php' style='color:#fff;text-decoration:underline;'>Login here</a>";
                $msgClass = "success";
            } else {
                $msg = "Error: " . $conn->error;
                $msgClass = "error";
            }
        }
    }
}

// --- CONFIGURACIÓN VISUAL (Misma que tu web) ---
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Helbreath Apocalypse</title>
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
                <h2>REGISTRO</h2>
                
                <?php if($msg != ''): ?>
                    <div class="msg-box <?php echo $msgClass; ?>"><?php echo $msg; ?></div>
                <?php endif; ?>

                <form action="register.php?lang=<?php echo $lang; ?>" method="POST">
                    <div class="form-group">
                        <label>Usuario</label>
                        <input type="text" name="username" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label>Confirmar Contraseña</label>
                        <input type="password" name="confirm_password" class="form-input" required>
                    </div>
                    <button type="submit" class="cta-btn" style="width:100%; border:none; cursor:pointer;">CREAR CUENTA</button>
                </form>
                <p style="margin-top:20px; color:#aaa;">¿Ya tienes cuenta? <a href="login.php?lang=<?php echo $lang; ?>" style="color:var(--primary-gold);">Inicia Sesión</a></p>
            </div>
        </section>
    </main>

    <footer>
        <p class="copyright">&copy; 2025 Helbreath Apocalypse.</p>
    </footer>
</body>
</html>