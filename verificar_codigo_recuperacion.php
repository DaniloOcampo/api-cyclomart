<?php
header("Content-Type: application/json; charset=UTF-8");
include 'db.php';
$mysqli->set_charset("utf8");

$data = json_decode(file_get_contents("php://input"), true);

$correo = $data['correo'] ?? '';
$codigo = $data['codigo'] ?? '';
$nuevaContrasena = $data['nueva_contrasena'] ?? '';

if (empty($correo) || empty($codigo) || empty($nuevaContrasena)) {
    echo json_encode(["status" => "error", "mensaje" => "Datos incompletos"]);
    exit;
}

// Buscar el último código válido para recuperación
$sql = "SELECT codigo, expiracion FROM codigos_2fa 
        WHERE correo = ? AND tipo = 'recuperacion' 
        ORDER BY creado DESC LIMIT 1";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$stmt->bind_result($codigoRegistrado, $expiracion);

if ($stmt->fetch()) {
    $stmt->close();

    // Verificar expiración
    if (strtotime($expiracion) < time()) {
        echo json_encode(["status" => "error", "mensaje" => "El código ha expirado"]);
        exit;
    }

    if ($codigoRegistrado !== $codigo) {
        echo json_encode(["status" => "error", "mensaje" => "Código incorrecto"]);
        exit;
    }

} else {
    echo json_encode(["status" => "error", "mensaje" => "No se encontró un código de recuperación válido"]);
    exit;
}

// Hashear la nueva contraseña
$hash = password_hash($nuevaContrasena, PASSWORD_DEFAULT);

// Actualizar la contraseña del usuario
$stmt = $mysqli->prepare("UPDATE usuarios SET contrasena = ? WHERE correo = ?");
$stmt->bind_param("ss", $hash, $correo);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(["status" => "ok", "mensaje" => "Contraseña actualizada correctamente"]);
} else {
    echo json_encode(["status" => "error", "mensaje" => "No se pudo actualizar la contraseña"]);
}

$stmt->close();
$mysqli->close();
?>
