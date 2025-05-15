<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
    unset($user['contrasena']); // No enviar la contraseña al cliente
    echo json_encode(["status" => "ok", "mensaje" => "Login exitoso", "usuario" => $user]);
} else {
    echo json_encode(["status" => "error", "mensaje" => "Contraseña incorrecta"]);
}

$stmt->close();
$conn->close();
?>
