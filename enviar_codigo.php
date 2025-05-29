<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require 'vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'db.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->correo)) {
    echo json_encode(["status" => "error", "mensaje" => "Correo es requerido"]);
    exit();
}

$correo = $data->correo;
$tipo = isset($data->tipo) && in_array($data->tipo, ['login', 'recuperacion']) ? $data->tipo : 'login';

// Validar que el usuario exista
$sql = "SELECT * FROM usuarios WHERE correo = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "mensaje" => "Usuario no encontrado"]);
    exit();
}

// Generar código de 6 dígitos
$codigo = rand(100000, 999999);
$expiracion = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Insertar código con tipo (login o recuperación)
$sql_insert = "INSERT INTO codigos_2fa (correo, codigo, expiracion, tipo)
               VALUES (?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE codigo = VALUES(codigo), expiracion = VALUES(expiracion), tipo = VALUES(tipo)";
$stmt2 = $mysqli->prepare($sql_insert);
$stmt2->bind_param("ssss", $correo, $codigo, $expiracion, $tipo);
$stmt2->execute();

// Enviar correo
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.tuservidor.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'tu@correo.com';
    $mail->Password = 'tu_contraseña';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('no-reply@tudominio.com', 'Soporte Cyclomart');
    $mail->addAddress($correo);
    $mail->isHTML(true);
    $mail->Subject = 'Código de verificación';
    $mail->Body = "<p>Tu código de verificación es: <strong>$codigo</strong></p>";

    $mail->send();
    echo json_encode(["status" => "ok", "mensaje" => "Código enviado al correo"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "mensaje" => "Error al enviar correo: {$mail->ErrorInfo}"]);
}
?>
