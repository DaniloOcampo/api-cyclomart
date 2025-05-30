<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
include 'db.php';

// Obtener los datos JSON enviados
$data = json_decode(file_get_contents("php://input"));

if (empty($data->nombre) || empty($data->apellido) || empty($data->correo) || empty($data->documento) || empty($data->contrasena)) {
    echo json_encode(["status" => "error", "mensaje" => "Faltan datos en la solicitud"]);
    exit();
}

$nombre = $data->nombre;
$apellido = $data->apellido;
$correo = $data->correo;
$documento = $data->documento;
$contrasena = $data->contrasena;
$rol = 'cliente'; // ✅ campo fijo por defecto

$contrasena_encriptada = password_hash($contrasena, PASSWORD_DEFAULT);

// Verificar si el correo ya existe
$checkSql = "SELECT id FROM usuarios WHERE correo = ?";
$checkStmt = $mysqli->prepare($checkSql);
if ($checkStmt === false) {
    echo json_encode(["status" => "error", "mensaje" => "Error en la preparación de la consulta de verificación"]);
    exit();
}
$checkStmt->bind_param("s", $correo);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    echo json_encode(["status" => "error", "mensaje" => "El correo ya está registrado"]);
    $checkStmt->close();
    $mysqli->close();
    exit();
}
$checkStmt->close();

// Insertar nuevo usuario con rol
$sql = "INSERT INTO usuarios (nombre, apellido, correo, contrasena, documento, rol) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $mysqli->prepare($sql);

if ($stmt === false) {
    echo json_encode(["status" => "error", "mensaje" => "Error en la preparación de la consulta"]);
    exit();
}

$stmt->bind_param("ssssss", $nombre, $apellido, $correo, $contrasena_encriptada, $documento, $rol);

if ($stmt->execute()) {
    echo json_encode(["status" => "ok", "mensaje" => "Usuario registrado"]);
} else {
    echo json_encode(["status" => "error", "mensaje" => "No se pudo registrar el usuario: " . $stmt->error]);
}

$stmt->close();
$mysqli->close();
?>