<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli("localhost", "root", "", "DB_ACLAS");
$conn->set_charset("utf8mb4");

$id = $_POST['id'];
$nombre = $_POST['nombre'];
$matricula = $_POST['matricula'];
$grado = $_POST['grado'];
$fecha = $_POST['fecha_registro'];

// Manejo de foto
$fotoPath = "";
if (!empty($_FILES['foto']['name'])) {
    $targetDir = "../Img/";
    $fotoPath = $targetDir . basename($_FILES['foto']['name']);
    move_uploaded_file($_FILES['foto']['tmp_name'], $fotoPath);
}

$sql = "UPDATE estudiantes SET nombre=?, matricula=?, grado=?, fecha_registro=?";
$params = [$nombre, $matricula, $grado, $fecha];

if ($fotoPath) {
    $sql .= ", foto=?";
    $params[] = $fotoPath;
}

$sql .= " WHERE id=?";
$params[] = $id;

$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat("s", count($params) - 1) . "i", ...$params);
$stmt->execute();

header("Location: students.php");
exit;
?>
