<?php
require_once "config.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM estudiantes WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: students.php?msg=deleted");
        exit;
    } else {
        echo "Error al eliminar.";
    }
} else {
    echo "ID no recibido.";
}

$conn->close();
?>
