<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require 'vendor/autoload.php'; // Autoload de PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'db.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->correo)) {
    echo json_encode(["status" => "error", "mensaje" => "Correo es requerido"]);
    exit();
}

$correo = $data->correo;

// Validar que el usuario exista en la base de datos
$sql = "SELECT * FROM usuarios WHERE correo = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "mensaje" => "Usuario no encontrado"]);
    exit();
}

// Generar código aleatorio de 6 dígitos
$codigo = rand(100000, 999999);


// Insertar o actualizar el código y la expiración (5 minutos)
$expiracion = date('Y-m-d H:i:s', strtotime('+5 minutes'));

$sql_upsert = "INSERT INTO codigos_2fa (correo, codigo, expiracion) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE codigo = VALUES(codigo), expiracion = VALUES(expiracion)";
$stmt2 = $mysqli->prepare($sql_upsert);
$stmt2->bind_param("sss", $correo, $codigo, $expiracion);
$stmt2->execute();

// Preparar PHPMailer para enviar el correo
$mail = new PHPMailer(true);

try {
    // Configuración SMTP (ejemplo con Gmail)
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'cyclomart.envios@gmail.com'; // Cambia esto por tu correo
    $mail->Password = 'opim vwjr mrwu gnuo'; // Usa contraseña de app (no tu contraseña real)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('tuemail@gmail.com', 'CycloMart');
    $mail->addAddress($correo);

    $mail->isHTML(true);
    $mail->Subject = 'Código de verificación para CycloMart';
    $mail->Body    = "<p>Tu código de verificación es: <b>$codigo</b></p><p>Este código expirará en 5 minutos.</p>";

    $mail->send();

    echo json_encode(["status" => "ok", "mensaje" => "Código enviado correctamente"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "mensaje" => "Error al enviar el correo: {$mail->ErrorInfo}"]);
}

$stmt2->close();
$stmt->close();
$mysqli->close();
