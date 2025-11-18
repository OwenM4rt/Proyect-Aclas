<?php
// Configuración de la base de datos
$DB_HOST = "localhost";   // Servidor (localhost si es local)
$DB_USER = "root";        // Usuario de MySQL
$DB_PASS = "";            // Contraseña de MySQL (vacía si usas XAMPP por defecto)
$DB_NAME = "DB_ACLAS";    // Nombre de tu base de datos

// Crear conexión
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Verificar conexión
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// Opcional: Forzar UTF-8
$conn->set_charset("utf8mb4");
?>
