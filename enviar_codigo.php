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
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'cyclomart.envios@gmail.com';
    $mail->Password = 'opim vwjr mrwu gnuo';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('cyclomart.envios@gmail.com', 'Soporte Cyclomart');
    $mail->addAddress($correo);
    $mail->isHTML(true);
    $mail->Encoding = 'base64';
    $mail->Subject = 'Código de verificación';
    $mail->Body = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='UTF-8'>
  <style>
    .container {
      max-width: 500px;
      margin: auto;
      background-color: #ffffff;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      padding: 20px;
      font-family: Arial, sans-serif;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .header {
      text-align: center;
      color: #00796B;
      font-size: 22px;
      margin-bottom: 20px;
    }
    .code {
      font-size: 32px;
      text-align: center;
      font-weight: bold;
      color: #333;
      background-color: #f0f0f0;
      padding: 12px;
      border-radius: 6px;
      letter-spacing: 4px;
    }
    .footer {
      margin-top: 30px;
      font-size: 14px;
      color: #777;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class='container'>
    <div class='header'>Tu código de verificación</div>
    <p style='text-align:center;'>Ingresa el siguiente código en la aplicación Cyclomart:</p>
    <div class='code'>$codigo</div>
    <div class='footer'>Si no solicitaste este código, puedes ignorar este mensaje.</div>
  </div>
</body>
</html>
";


    $mail->send();
    echo json_encode(["status" => "ok", "mensaje" => "Código enviado al correo"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "mensaje" => "Error al enviar correo: {$mail->ErrorInfo}"]);
}
?>
