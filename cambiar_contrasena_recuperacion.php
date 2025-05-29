<?php
require 'db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$correo = $data['correo'] ?? '';
$nueva = $data['nueva_contrasena'] ?? '';

if (empty($correo) || empty($nueva)) {
    echo json_encode(["status" => "error", "mensaje" => "Datos incompletos"]);
    exit;
}

// Verifica que el correo exista
$stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE correo = ? LIMIT 1");
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "mensaje" => "Correo no registrado"]);
    exit;
}

// Hash de la nueva contraseña
$nuevaHash = password_hash($nueva, PASSWORD_DEFAULT);

// Actualiza la contraseña
$stmt = $mysqli->prepare("UPDATE usuarios SET contrasena = ? WHERE correo = ?");
$stmt->bind_param("ss", $nuevaHash, $correo);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(["status" => "ok", "mensaje" => "Contraseña actualizada correctamente"]);
} else {
    echo json_encode(["status" => "error", "mensaje" => "No se pudo actualizar la contraseña"]);
}
?>
