<?php
// db.php

// 1. HOST: MANTENER SIEMPRE 127.0.0.1
// Aunque la gente entre desde fuera, el script PHP se ejecuta DENTRO de tu PC.
// Por tanto, la conexión a la base de datos es siempre local.
$host = '127.0.0.1'; 

// 2. PUERTO: 3307
// Es vital poner este puerto porque tu XAMPP no usa el estándar (3306).
$port = 3307; 

$db   = 'helbreath_web';
$user = 'root'; 
$pass = ''; // Contraseña vacía por defecto en XAMPP

// 3. CONEXIÓN SEGURA
// Añadimos $port al final para forzar la entrada por el puerto 3307
try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
} catch (mysqli_sql_exception $e) {
    die("<h1>Error de Conexión a la Base de Datos</h1>
         <p>El servidor no puede hablar con la base de datos.</p>
         <p>Detalles: Verifique que MySQL (XAMPP) esté en verde y usando el puerto $port.</p>");
}

if ($conn->connect_error) {
    die("Fallo crítico: " . $conn->connect_error);
}
?>