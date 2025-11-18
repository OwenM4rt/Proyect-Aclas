<?php
// ===================== CONFIGURACIÓN BÁSICA =====================
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

// ===================== MENSAJE DE IMPORTACIÓN =====================
$mensaje = "";
$tipoMensaje = "";

// ===================== PROCESAR IMPORTACIÓN CSV =====================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["csvFile"])) {
    $fileTmp = $_FILES["csvFile"]["tmp_name"];
    $fileName = $_FILES["csvFile"]["name"];
    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);

    if ($fileExt !== "csv") {
        $mensaje = "❌ El archivo debe ser formato CSV.";
        $tipoMensaje = "error";
    } else {
        $handle = fopen($fileTmp, "r");
        if ($handle) {
            $primeraFila = true;
            $importados = 0;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($primeraFila) { 
                    $primeraFila = false; 
                    continue; 
                }

                if (count($data) < 10) continue;

                list($nombre, $apellido, $matricula, $fecha_nacimiento, $grado, $telefono, $email, $foto, $fecha_registro, $activo) = $data;

                $stmt = $conn->prepare("INSERT INTO estudiantes (nombre, apellido, matricula, fecha_nacimiento, grado, telefono, email, foto, fecha_registro, activo)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssss", 
                    $nombre, $apellido, $matricula, $fecha_nacimiento, $grado, $telefono, $email, $foto, $fecha_registro, $activo
                );
                $stmt->execute();
                $importados++;
            }
            fclose($handle);
            $mensaje = "✅ Importación exitosa: $importados estudiantes agregados.";
            $tipoMensaje = "success";
        } else {
            $mensaje = "❌ No se pudo abrir el archivo CSV.";
            $tipoMensaje = "error";
        }
    }
}

// ===================== FILTRO =====================
$filtroGrado = $_GET['grado'] ?? "";

// Cargar grados disponibles
$gradosDisponibles = [];
$resGrados = $conn->query("SELECT DISTINCT grado FROM estudiantes ORDER BY grado ASC");
while ($row = $resGrados->fetch_assoc()) {
    $gradosDisponibles[] = $row['grado'];
}

// ===================== CONSULTA =====================
if ($filtroGrado) {
    $sqlEst = "SELECT id, matricula, nombre, apellido, grado, fecha_registro, foto 
               FROM estudiantes 
               WHERE LOWER(grado) LIKE ? 
               ORDER BY grado, nombre ASC";
    $stmt = $conn->prepare($sqlEst);
    $param = "%" . strtolower(trim($filtroGrado)) . "%";
    $stmt->bind_param("s", $param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sqlEst = "SELECT id, matricula, nombre, apellido, grado, fecha_registro, foto 
               FROM estudiantes 
               ORDER BY grado, nombre ASC";
    $result = $conn->query($sqlEst);
}

$totalEstudiantes = $result ? $result->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Estudiantes - EduControl</title>
    <link rel="stylesheet" href="../css/students.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .alert {
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 500;
            max-width: 800px;
            margin: 20px auto;
        }
        .alert.success { background-color: #d1f7c4; color: #256029; border: 1px solid #a5e29f; }
        .alert.error { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .hidden-input { display: none; }
    </style>
</head>
<body>
    <!-- ===================== NAVBAR ===================== -->
    <nav class="navbar">
        <div class="navbar-brand">
            <img src="../Img/logo.png" class="navbar-logo">
            <span>RegistroAclas</span>
        </div>
        <div class="navbar-nav">
            <a href="../html/index.html" class="nav-link"><i class="fas fa-home"></i> Inicio</a>
            <a href="../html/asistencia.html" class="nav-link"><i class="fas fa-clipboard-check"></i> Asistencia</a>
            <a href="students.php" class="nav-link active"><i class="fas fa-users"></i> Estudiantes</a>
            <a href="../html/reports.html" class="nav-link"><i class="fas fa-chart-bar"></i> Reportes</a>
        </div>
        <div class="navbar-counter">
            <div class="counter">
                <i class="fas fa-users"></i>
                <span id="totalStudentsCount"><?php echo $totalEstudiantes; ?> Estudiantes</span>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- ===================== ALERTAS ===================== -->
        <?php if ($mensaje): ?>
            <div class="alert <?php echo $tipoMensaje; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <!-- ===================== ENCABEZADO ===================== -->
        <div class="students-header">
            <div class="header-info">
                <h1>Gestión de Estudiantes</h1>
                <p>Administra la información de todos los estudiantes registrados</p>
            </div>
            <div class="header-actions">
                <!-- Botón Importar CSV -->
                <form method="POST" enctype="multipart/form-data" style="display:inline;">
                    <label for="csvFile" class="btn btn-secondary" style="cursor:pointer;">
                        <i class="fas fa-file-import"></i> Importar 
                    </label>
                    <input type="file" name="csvFile" id="csvFile" class="hidden-input" accept=".csv" onchange="this.form.submit()">
                </form>

                <!-- Botón Nuevo Estudiante -->
                <a href="../php/register.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Estudiante
                </a>
            </div>
        </div>

        <!-- ===================== FILTRO Y BUSCADOR ===================== -->
        <div class="filters-section">
            <div class="search-bar">
                <div class="input-icon"><i class="fas fa-search"></i></div>
                <input type="text" placeholder="Buscar por nombre, matrícula o email..." id="searchInput">
            </div>

            <form method="GET" action="students.php" class="filter-controls">
                <select name="grado" id="filterGrado" onchange="this.form.submit()">
                    <option value="">Todos los Grupos</option>
                    <?php 
                    foreach ($gradosDisponibles as $grado) {
                        $selected = ($grado == $filtroGrado) ? 'selected' : '';
                        echo "<option value=\"{$grado}\" {$selected}>Grupo: {$grado}</option>";
                    }
                    ?>
                </select>
                <?php if ($filtroGrado): ?>
                    <a href="students.php" class="btn btn-secondary btn-clear-filter" title="Limpiar Filtro">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- ===================== LISTA DE ESTUDIANTES ===================== -->
        <div class="students-section">
            <div class="section-header">
                <h2>Lista de Estudiantes 
                    <?php echo $filtroGrado ? "en Grupo: " . htmlspecialchars($filtroGrado) : ""; ?>
                </h2>
                <span id="studentsCount" class="count-badge"><?php echo $totalEstudiantes; ?> estudiantes</span>
            </div>
            
            <div id="studentsGrid" class="students-grid">
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($est = $result->fetch_assoc()) {
                        $fecha = date("d/m/Y", strtotime($est['fecha_registro']));
                        $nombreCompleto = trim($est['nombre'] . ' ' . $est['apellido']);
                        ?>
                        <div class="student-card">
                            <div class="student-card-header">
                                <img src="<?php echo $est['foto'] ?: '../Img/avatar.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($nombreCompleto); ?>" 
                                     class="student-avatar">
                                <div class="student-basic-info">
                                    <h3><?php echo htmlspecialchars($nombreCompleto); ?></h3>
                                    <span class="student-code">Código: <?php echo $est['matricula']; ?></span>
                                </div>
                            </div>
                            <div class="student-details">
                                <div class="detail-item">
                                    <span class="detail-label">Grado</span>
                                    <span class="detail-value"><span class="grade-badge"><?php echo htmlspecialchars($est['grado']); ?></span></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Registro</span>
                                    <span class="detail-value"><?php echo $fecha; ?></span>
                                </div>
                            </div>
                            <div class="student-actions">
                                <a href="../php/edit_students.php?id=<?php echo $est['id']; ?>" 
                                   class="action-btn edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../php/delete_students.php?id=<?php echo $est['id']; ?>" 
                                   class="action-btn delete" title="Eliminar"
                                   onclick="return confirm('¿Seguro que deseas eliminar a <?php echo htmlspecialchars($nombreCompleto); ?>?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div id="emptyState" class="empty-state">
                        <div class="empty-icon"><i class="fas fa-user-graduate"></i></div>
                        <h3>No hay estudiantes registrados</h3>
                        <p>Comienza agregando estudiantes al sistema.</p>
                        <a href="../php/register.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Registrar Nuevo Estudiante
                        </a>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const text = this.value.toLowerCase();
    const cards = document.querySelectorAll('.student-card');
    let visible = 0;
    cards.forEach(c => {
        const name = c.querySelector('.student-basic-info h3').textContent.toLowerCase();
        const code = c.querySelector('.student-code').textContent.toLowerCase();
        if (name.includes(text) || code.includes(text)) {
            c.style.display = 'block';
            visible++;
        } else {
            c.style.display = 'none';
        }
    });
    document.getElementById('studentsCount').textContent = visible + ' estudiantes';
    const empty = document.getElementById('emptyState');
    if (empty) empty.style.display = visible === 0 ? 'block' : 'none';
});
</script>

</body>
</html>

<?php 
if ($conn && !$conn->connect_error) {
    $conn->close();
}
?>
