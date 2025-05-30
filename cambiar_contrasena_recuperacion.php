<?php
header("Content-Type: application/json");
require 'db.php';

$data = json_decode(file_get_contents("php://input"));

$correo = $data->correo ?? '';
$nueva_contrasena = $data->nueva_contrasena ?? '';

if (empty($correo) || empty($nueva_contrasena)) {
    echo json_encode(["status" => "error", "mensaje" => "Faltan datos"]);
    exit;
}

$nueva_contrasena_hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);

$stmt = $mysqli->prepare("UPDATE usuarios SET contrasena = ? WHERE correo = ?");
if (!$stmt) {
    echo json_encode(["status" => "error", "mensaje" => "Error en la preparación de la consulta"]);
    exit;
}

$stmt->bind_param("ss", $nueva_contrasena_hash, $correo);

if ($stmt->execute()) {
    echo json_encode(["status" => "ok", "mensaje" => "Contraseña actualizada"]);
} else {
    echo json_encode(["status" => "error", "mensaje" => "No se pudo actualizar la contraseña"]);
}

$stmt->close();
$mysqli->close();
?>
