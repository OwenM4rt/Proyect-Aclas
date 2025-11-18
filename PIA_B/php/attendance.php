<?php
// ===================== CONFIGURACIÓN =====================
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "DB_ACLAS";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$conn->set_charset("utf8mb4");

$today = date("Y-m-d");

// ===================== FUNCIONES AUXILIARES =====================
function jsonResponse($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ===================== BUSCAR ESTUDIANTE POR CÓDIGO =====================
if (isset($_GET['action']) && $_GET['action'] === 'find' && isset($_GET['codigo'])) {
    $codigo = $_GET['codigo'];
    $stmt = $conn->prepare("SELECT id, matricula AS codigo, nombre, grado, foto FROM estudiantes WHERE matricula = ? LIMIT 1");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        jsonResponse($result->fetch_assoc());
    } else {
        jsonResponse(["error" => "Estudiante no encontrado"]);
    }
}

// ===================== OBTENER ASISTENCIA DEL DÍA =====================
if (isset($_GET['action']) && $_GET['action'] === 'get_attendance') {
    $sql = "SELECT e.matricula AS codigo, e.nombre, e.grado, a.hora, a.estado
            FROM asistencia a
            JOIN estudiantes e ON a.estudiante_id = e.id
            WHERE a.fecha = ?
            ORDER BY a.hora DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    jsonResponse($data);
}

// ===================== GUARDAR ASISTENCIA =====================
if (isset($_GET['action']) && $_GET['action'] === 'save_attendance') {
    $inputJSON = file_get_contents('php://input');
    $asistencias = json_decode($inputJSON, true);

    if (!$asistencias || !is_array($asistencias)) {
        jsonResponse(["error" => "No se recibieron datos válidos."]);
    }

    $stmt = $conn->prepare("
        INSERT INTO asistencia (estudiante_id, fecha, hora, estado, fecha_registro)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE hora = VALUES(hora), estado = VALUES(estado)
    ");

    $guardados = 0;
    $errores = [];

    foreach ($asistencias as $a) {
        $codigo = $a['codigo'];
        $hora = $a['hora'] ?? '';
        $estado = $a['estado'] ?? '';

        if (empty($hora) || empty($estado)) {
            $errores[] = "Datos incompletos para código $codigo";
            continue;
        }

        $buscar = $conn->prepare("SELECT id FROM estudiantes WHERE matricula = ?");
        $buscar->bind_param("s", $codigo);
        $buscar->execute();
        $buscar->bind_result($estudiante_id);
        $buscar->fetch();
        $buscar->close();

        if (empty($estudiante_id)) {
            $errores[] = "Estudiante $codigo no encontrado";
            continue;
        }

        $stmt->bind_param("isss", $estudiante_id, $today, $hora, $estado);
        if ($stmt->execute()) $guardados++;
        else $errores[] = "Error al guardar $codigo: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    jsonResponse([
        "success" => "Se guardaron $guardados registros.",
        "errores" => $errores
    ]);
}
?>
