<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// =========================
// CONFIGURACIÓN BASE DE DATOS
// =========================
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "DB_ACLAS";

// =========================
// CONEXIÓN
// =========================
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// =========================
// PROCESAR FORMULARIO
// =========================
$mensaje = "";
$tipo = ""; // success | error

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre         = trim($_POST['nombre']);
    $apellido       = trim($_POST['apellido']);
    $matricula      = trim($_POST['matricula']);
    $fechaNacimiento= $_POST['fechaNacimiento'];
    $grado          = $_POST['grado'];
    $telefono       = trim($_POST['telefono']);
    $email          = !empty($_POST['email']) ? trim($_POST['email']) : null;

    // Manejo de foto
    $foto = null;
    if (!empty($_FILES['photo']['name'])) {
        $fotoNombre = uniqid("foto_") . "." . pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $rutaDestino = "uploads/" . $fotoNombre;

        if (!is_dir("uploads")) {
            mkdir("uploads", 0777, true);
        }

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $rutaDestino)) {
            $foto = $rutaDestino;
        }
    }

    // Preparar SQL
    $stmt = $conn->prepare("INSERT INTO estudiantes 
        (nombre, apellido, matricula, fecha_nacimiento, grado, telefono, email, foto) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", 
        $nombre, $apellido, $matricula, $fechaNacimiento, $grado, 
        $telefono, $email, $foto
    );

    if ($stmt->execute()) {
        $mensaje = "✅ Estudiante registrado correctamente.";
        $tipo = "success";
        
    } else {
        $mensaje = "❌ Error al registrar: " . $stmt->error;
        $tipo = "error";
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Estudiante - RegistroAclas</title>
    <link rel="stylesheet" href="../css/register.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Botón regresar -->
    <button class="btn-back" onclick="window.history.back()">
        <i class="fas fa-arrow-left"></i>
        Regresar
    </button>

    <div class="container">
        <div class="register-card">
            <div class="register-header">
                <div class="register-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h2>Registro de Nuevo Estudiante</h2>
                <p>Completa la información para registrar un nuevo estudiante en el sistema</p>
            </div>

            <!-- MENSAJE -->
            <?php if (!empty($mensaje)): ?>
                <div class="alert <?= $tipo ?>">
                    <?= $mensaje ?>
                </div>
            <?php endif; ?>

            <form id="registerForm" class="register-form" method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre"><i class="fas fa-user"></i> Nombre</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="apellido"><i class="fas fa-user"></i> Apellido</label>
                        <input type="text" id="apellido" name="apellido" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="matricula"><i class="fas fa-id-card"></i> Número de Matrícula</label>
                        <input type="number" id="matricula" name="matricula" maxlength="6" oninput="if(this.value.length>6)this.value=this.value.slice(0,6);" required>
                    </div>
                    <div class="form-group">
                        <label for="fechaNacimiento"><i class="fas fa-calendar"></i> Fecha de Nacimiento</label>
                        <input type="date" id="fechaNacimiento" name="fechaNacimiento" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="grado"><i class="fas fa-graduation-cap"></i> Grado</label>
                        <select id="grado" name="grado" required>
                            <option value="">Selecciona un grado</option>
                            <option value="10-1">10-1</option>
                            <option value="11-1">11-1</option>

                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono"><i class="fas fa-phone"></i> Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" required>
                    </div>
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email (Opcional)</label>
                        <input type="email" id="email" name="email">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-camera"></i> Foto del Estudiante</label>
                    <div class="photo-upload">
                        <div class="upload-area" onclick="document.getElementById('photoInput').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Haz clic para subir una foto</p>
                            <span>JPG, PNG - Máximo 2MB</span>
                        </div>
                        <input type="file" id="photoInput" name="photo" accept="image/*" style="display: none;">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Registrar Estudiante
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
