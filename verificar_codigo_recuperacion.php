<?php
require 'db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$correo = $data['correo'] ?? '';
$codigo = $data['codigo'] ?? '';

if (empty($correo) || empty($codigo)) {
    echo json_encode(["status" => "error", "mensaje" => "Datos incompletos"]);
    exit;
}

// Verifica que exista un código válido para ese correo
$stmt = $conexion->prepare("SELECT * FROM codigos_2fa WHERE correo = ? AND codigo = ? LIMIT 1");
$stmt->bind_param("ss", $correo, $codigo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "mensaje" => "Código inválido o ya usado"]);
    exit;
}

// Elimina el código para evitar reutilización
$stmt = $conexion->prepare("DELETE FROM codigos_2fa WHERE correo = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();

echo json_encode(["status" => "ok", "mensaje" => "Código verificado correctamente"]);
?>