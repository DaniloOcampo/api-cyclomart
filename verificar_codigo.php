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

if (empty($data->correo) || empty($data->codigo)) {
    echo json_encode(["status" => "error", "mensaje" => "Faltan datos en la solicitud"]);
    exit();
}

$correo = $data->correo;
$codigo = $data->codigo;

// Buscar el código guardado y validar expiración
$sql = "SELECT codigo, expiracion FROM codigos_2fa WHERE correo = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "mensaje" => "Código no encontrado"]);
    exit();
}

$row = $result->fetch_assoc();

if ($row['codigo'] === $codigo) {
    $ahora = date('Y-m-d H:i:s');
    if ($ahora > $row['expiracion']) {
        echo json_encode(["status" => "error", "mensaje" => "El código ha expirado"]);
        exit();
    }

    // Aquí puedes devolver info del usuario para finalizar login
    // Recuperamos datos usuario
    $sqlUser = "SELECT id, nombre, apellido, correo, documento FROM usuarios WHERE correo = ?";
    $stmtUser = $mysqli->prepare($sqlUser);
    $stmtUser->bind_param("s", $correo);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();

    if ($resultUser->num_rows === 0) {
        echo json_encode(["status" => "error", "mensaje" => "Usuario no encontrado"]);
        exit();
    }

    $user = $resultUser->fetch_assoc();

    echo json_encode(["status" => "ok", "mensaje" => "Código verificado", "usuario" => $user]);

    $stmtUser->close();

} else {
    echo json_encode(["status" => "error", "mensaje" => "Código incorrecto"]);
}

$stmt->close();
$mysqli->close();
