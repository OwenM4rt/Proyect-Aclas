<?php
// ==============================
// CONEXIÓN A LA BASE DE DATOS
// ==============================
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "DB_ACLAS";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// ==============================
// CONFIGURAR DESCARGA COMO EXCEL
// ==============================
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=estudiantes.xls");
header("Pragma: no-cache");
header("Expires: 0");

// ==============================
// CONSULTA Y EXPORTACIÓN
// ==============================
$sql = "SELECT id, matricula, nombre, apellido, correo, telefono, grado FROM estudiantes";
$result = $conn->query($sql);

// Encabezados de columna
echo "ID\tMatrícula\tNombre\tApellido\tCorreo\tTeléfono\tGrado\n";

// Filas de datos
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo $row['id'] . "\t" .
             $row['matricula'] . "\t" .
             $row['nombre'] . "\t" .
             $row['apellido'] . "\t" .
             $row['correo'] . "\t" .
             $row['telefono'] . "\t" .
             $row['grado'] . "\n";
    }
}

$conn->close();
?>
