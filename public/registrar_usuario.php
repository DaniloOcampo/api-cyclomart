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

$contrasena_encriptada = password_hash($contrasena, PASSWORD_DEFAULT);

$sql = "INSERT INTO usuarios (nombre, apellido, correo, contrasena, documento) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(["status" => "error", "mensaje" => "Error en la preparación de la consulta"]);
    exit();
}

$stmt->bind_param("sssss", $nombre, $apellido, $correo, $contrasena_encriptada, $documento);

if ($stmt->execute()) {
    echo json_encode(["status" => "ok", "mensaje" => "Usuario registrado"]);
} else {
    echo json_encode(["status" => "error", "mensaje" => "No se pudo registrar el usuario: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

