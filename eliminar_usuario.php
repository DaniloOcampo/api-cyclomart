<?php
header("Content-Type: application/json");
require 'db.php';

$id = $_POST['id'] ?? null;

if (!$id) {
    echo json_encode(["status" => "error", "mensaje" => "Falta el ID del usuario"]);
    exit;
}

$stmt = $mysqli->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "ok", "mensaje" => "Usuario eliminado"]);
} else {
    echo json_encode(["status" => "error", "mensaje" => "Error al eliminar"]);
}
?>
