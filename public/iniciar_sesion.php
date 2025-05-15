<?php
header("Content-Type: application/json");
include 'db.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->correo) || empty($data->contrasena)) {
    echo json_encode(["status" => "error", "mensaje" => "Faltan datos en la solicitud"]);
    exit();
}

$correo = $data->correo;
$contrasena = $data->contrasena;

$sql = "SELECT * FROM usuarios WHERE correo = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(["status" => "error", "mensaje" => "Error en la preparación de la consulta"]);
    exit();
}

$stmt->bind_param("s", $correo);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "mensaje" => "Usuario no encontrado"]);
    exit();
}

$user = $result->fetch_assoc();

// Verificar la contraseña
if (password_verify($contrasena, $user['contrasena'])) {
    echo json_encode(["status" => "ok", "mensaje" => "Login exitoso", "usuario" => $user]);
} else {
    echo json_encode(["status" => "error", "mensaje" => "Contraseña incorrecta"]);
}

$stmt->close();
$conn->close();
?>
