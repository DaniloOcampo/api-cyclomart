<?php
header("Content-Type: application/json");
require 'db.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id)) {
    echo json_encode(["success" => false, "message" => "ID de usuario requerido"]);
    exit();
}

$id = intval($data->id);

// Verificar rol del usuario antes de eliminarlo
$check_sql = "SELECT rol FROM usuarios WHERE id = ?";
$check_stmt = $mysqli->prepare($check_sql);
$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Usuario no encontrado"]);
    exit();
}

$row = $result->fetch_assoc();

if ($row['rol'] === 'admin') {
    echo json_encode(["success" => false, "message" => "No se puede eliminar un usuario administrador"]);
    exit();
}

$check_stmt->close();

// Proceder con la eliminación
$sql = "DELETE FROM usuarios WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Usuario eliminado correctamente"]);
} else {
    echo json_encode(["success" => false, "message" => "Error al eliminar el usuario"]);
}

$stmt->close();
$mysqli->close();

