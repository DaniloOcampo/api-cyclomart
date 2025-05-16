<?php
header("Content-Type: application/json");
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$correo = $data['correo'] ?? '';
$actual = $data['actual'] ?? '';
$nueva = $data['nueva'] ?? '';

if (!$correo || !$actual || !$nueva) {
    echo json_encode(["status" => "error", "mensaje" => "Faltan datos"]);
    exit();
}

// Obtener hash actual de la base de datos
$sql = "SELECT contrasena FROM usuarios WHERE correo = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "mensaje" => "Usuario no encontrado"]);
    exit();
}

$row = $result->fetch_assoc();
$hashActual = $row['contrasena'];

// Verificar contraseña actual
if (!password_verify($actual, $hashActual)) {
    echo json_encode(["status" => "error", "mensaje" => "La contraseña actual es incorrecta"]);
    exit();
}

// Actualizar contraseña nueva
$hashNueva = password_hash($nueva, PASSWORD_DEFAULT);
$sqlUpdate = "UPDATE usuarios SET contrasena = ? WHERE correo = ?";
$stmtUpdate = $mysqli->prepare($sqlUpdate);
$stmtUpdate->bind_param("ss", $hashNueva, $correo);

if ($stmtUpdate->execute()) {
    echo json_encode(["status" => "ok", "mensaje" => "Contraseña actualizada"]);
} else {
    echo json_encode(["status" => "error", "mensaje" => "Error al actualizar la contraseña"]);
}

$stmt->close();
$stmtUpdate->close();
$mysqli->close();
?>
