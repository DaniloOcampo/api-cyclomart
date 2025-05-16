<?php
header("Content-Type: application/json");
include 'db.php';

// Leer datos JSON del body
$data = json_decode(file_get_contents("php://input"), true);

$correo = $data['correo'] ?? '';
$nueva = $data['nueva'] ?? '';

if (!$correo || !$nueva) {
    echo json_encode(["status" => "error", "mensaje" => "Faltan datos"]);
    exit();
}

$hash = password_hash($nueva, PASSWORD_DEFAULT);

$sql = "UPDATE usuarios SET contrasena = ? WHERE correo = ?";
$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "mensaje" => "Error en la consulta: " . $mysqli->error]);
    exit();
}

$stmt->bind_param("ss", $hash, $correo);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "ok", "mensaje" => "Contraseña actualizada"]);
    } else {
        echo json_encode(["status" => "error", "mensaje" => "Correo no encontrado"]);
    }
} else {
    echo json_encode(["status" => "error", "mensaje" => "Error al actualizar"]);
}

$stmt->close();
$mysqli->close();
?>
