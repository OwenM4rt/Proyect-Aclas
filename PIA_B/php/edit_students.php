<?php
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST['id']);
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $grado = $_POST['grado'];
    $matricula = $_POST['matricula'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("UPDATE estudiantes 
                            SET nombre=?, apellido=?, grado=?, matricula=?, telefono=?, email=? 
                            WHERE id=?");
    $stmt->bind_param("ssssssi", $nombre, $apellido, $grado, $matricula, $telefono, $email, $id);

    if ($stmt->execute()) {
        header("Location: students.php?msg=updated");
        exit;
    } else {
        echo "❌ Error al actualizar.";
    }
    $stmt->close();
} elseif (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $res = $conn->query("SELECT * FROM estudiantes WHERE id=$id");
    if ($res->num_rows > 0) {
        $est = $res->fetch_assoc();
    } else {
        die("Estudiante no encontrado.");
    }
} else {
    die("ID no especificado.");
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Estudiante</title>
    <link rel="stylesheet" href="../css/student_edit.css">
</head>
<body>
    <div class="form-container">
        <form method="POST" action="edit_students.php">
            <h1>Editar Estudiante</h1>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($est['id']); ?>">

            <label>Nombre(s):</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($est['nombre']); ?>" required>

            <label>Apellido(s):</label>
            <input type="text" name="apellido" value="<?php echo htmlspecialchars($est['apellido']); ?>" required>

            <label>Grado:</label>
            <input type="text" name="grado" value="<?php echo htmlspecialchars($est['grado']); ?>" required>

            <label>Matrícula:</label>
            <input type="text" name="matricula" value="<?php echo htmlspecialchars($est['matricula']); ?>" required>

            <label>Teléfono:</label>
            <input type="text" name="telefono" value="<?php echo htmlspecialchars($est['telefono']); ?>">

            <label>Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($est['email']); ?>">

            <button type="submit">Actualizar</button>
        </form>
    </div>
</body>
</html>
