<?php
header('Content-Type: application/json; charset=utf-8');

// ===================== CONFIGURACIÓN =====================
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "DB_ACLAS";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["error" => "Error de conexión a la base de datos"]);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {

    // === 1️⃣ Estadísticas generales del día ===
    case 'get_stats':
        $fechaHoy = date('Y-m-d');

        // Total estudiantes
        $resTotal = $conn->query("SELECT COUNT(*) AS total FROM estudiantes");
        $total = $resTotal->fetch_assoc()['total'] ?? 0;

        // Presentes hoy
        $resPres = $conn->prepare("
            SELECT COUNT(DISTINCT estudiante_id) AS presentes 
            FROM asistencia 
            WHERE fecha = ? AND LOWER(estado) = 'presente'
        ");
        $resPres->bind_param("s", $fechaHoy);
        $resPres->execute();
        $pres = $resPres->get_result()->fetch_assoc()['presentes'] ?? 0;

        $ausentes = max($total - $pres, 0);
        $tasa = $total > 0 ? round(($pres / $total) * 100, 1) : 0;

        echo json_encode([
            "total_students" => $total,
            "present_today" => $pres,
            "absent_today" => $ausentes,
            "attendance_rate" => $tasa
        ]);
        break;


    // === 2️⃣ Historial diario (últimos 7 o 30 días) ===
    case 'get_daily_attendance_history':
        $period = $_GET['period'] ?? 'week';
        $days = $period === 'month' ? 30 : 7;
        $fechaInicio = date('Y-m-d', strtotime("-$days days"));

        $stmt = $conn->prepare("
            SELECT fecha, COUNT(DISTINCT estudiante_id) AS presentes
            FROM asistencia
            WHERE fecha BETWEEN ? AND CURDATE()
              AND LOWER(estado) = 'presente'
            GROUP BY fecha
            ORDER BY fecha ASC
        ");
        $stmt->bind_param("s", $fechaInicio);
        $stmt->execute();
        $res = $stmt->get_result();

        $labels = [];
        $data = [];
        while ($r = $res->fetch_assoc()) {
            $labels[] = $r['fecha'];
            $data[] = intval($r['presentes']);
        }

        echo json_encode(["labels" => $labels, "data" => $data]);
        break;


    // === 3️⃣ Reporte por grado ===
    case 'get_grade_data':
        $fechaInicio = date('Y-m-d', strtotime("-30 days"));
        $sql = "
            SELECT e.grado,
                   COUNT(DISTINCT e.id) AS total_students,
                   SUM(CASE WHEN a.estado = 'Presente' THEN 1 ELSE 0 END) AS total_presentes
            FROM estudiantes e
            LEFT JOIN asistencia a 
              ON a.estudiante_id = e.id AND a.fecha >= '$fechaInicio'
            GROUP BY e.grado
            ORDER BY e.grado ASC
        ";
        $res = $conn->query($sql);
        $salida = [];
        while ($row = $res->fetch_assoc()) {
            $rate = $row['total_students'] > 0
                ? round(($row['total_presentes'] / ($row['total_students'] * 30)) * 100, 1)
                : 0;
            $salida[] = [
                "grado" => $row['grado'],
                "total_students" => intval($row['total_students']),
                "rate" => $rate
            ];
        }
        echo json_encode($salida);
        break;


    // === 4️⃣ Top 5 estudiantes más cumplidos ===
    case 'get_top_students':
        $fechaInicio = date('Y-m-d', strtotime("-30 days"));
        $sql = "
            SELECT e.id, e.nombre, e.apellido, e.grado, e.foto,
                   COUNT(a.id) AS asistencias
            FROM estudiantes e
            LEFT JOIN asistencia a 
              ON a.estudiante_id = e.id 
             AND a.fecha >= '$fechaInicio' 
             AND LOWER(a.estado) = 'presente'
            GROUP BY e.id
            ORDER BY asistencias DESC
            LIMIT 5
        ";
        $res = $conn->query($sql);
        $data = [];
        while ($r = $res->fetch_assoc()) {
            $data[] = [
                "nombre" => $r['nombre'] . " " . $r['apellido'],
                "grado" => $r['grado'],
                "foto" => $r['foto'] ?: "../Img/avatar.png",
                "rate" => intval($r['asistencias'])
            ];
        }
        echo json_encode($data);
        break;


    // === 5️⃣ Exportar asistencia (últimos 30 días) ===
    case 'get_export_data':
        $fechaInicio = date('Y-m-d', strtotime("-30 days"));
        $sql = "
            SELECT e.matricula, e.nombre, e.apellido, e.grado,
                   COUNT(a.id) AS asistencias_30_dias
            FROM estudiantes e
            LEFT JOIN asistencia a 
              ON a.estudiante_id = e.id 
             AND a.fecha >= '$fechaInicio' 
             AND LOWER(a.estado) = 'presente'
            GROUP BY e.id
            ORDER BY e.grado, e.nombre
        ";
        $res = $conn->query($sql);
        $out = [];
        while ($r = $res->fetch_assoc()) {
            $out[] = $r;
        }
        echo json_encode($out);
        break;

    default:
        echo json_encode(["error" => "Acción no válida"]);
        break;
}

$conn->close();
?>
