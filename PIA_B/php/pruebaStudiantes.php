<?php
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Conexión
require_once "config.php";

// Acción recibida desde el HTML
$action = isset($_GET['action']) ? $_GET['action'] : '';
$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';

// Buscar estudiante por código
if ($action === 'find' && !empty($codigo)) {
    $stmt = $conn->prepare("SELECT matricula AS codigo, nombre, grado FROM estudiantes WHERE matricula = ?");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();

    // Si existe el estudiante
    if ($result->num_rows > 0) {
        $estudiante = $result->fetch_assoc();
        echo json_encode($estudiante);
    } else {
        echo json_encode(["error" => "No encontrado"]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// Listar todos (si no hay 'action')
if (empty($action)) {
    $result = $conn->query("SELECT matricula AS codigo, nombre, grado FROM estudiantes ORDER BY nombre ASC");
    $estudiantes = [];

    while ($row = $result->fetch_assoc()) {
        $estudiantes[] = $row;
    }

    echo json_encode($estudiantes);
    $conn->close();
    exit;
}

// Si llega una acción no válida
echo json_encode(["error" => "Acción no válida"]);
$conn->close();
