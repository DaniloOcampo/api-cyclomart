<?php
header('Content-Type: application/json');
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (
    !isset($data['id_usuario']) ||
    !isset($data['productos']) ||
    !isset($data['metodo_pago'])
) {
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit;
}

$id_usuario = $data['id_usuario'];

// Obtener correo del usuario
$stmtCorreo = $mysqli->prepare("SELECT correo FROM usuarios WHERE id = ?");
$stmtCorreo->bind_param("i", $id_usuario);
$stmtCorreo->execute();
$result = $stmtCorreo->get_result();
if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Usuario no encontrado"]);
    exit;
}
$correo = $result->fetch_assoc()['correo'];
$stmtCorreo->close();

$productos = $data['productos'];
$metodo_pago = $data['metodo_pago'];
$fecha = date("Y-m-d H:i:s");

// Validación extra
if (empty($productos)) {
    echo json_encode(["success" => false, "message" => "El carrito está vacío"]);
    exit;
}

// Calcular total
$total = 0;
foreach ($productos as $item) {
    $idProducto = $item['id_producto'];
    $cantidad = $item['cantidad'];

    // Obtener precio unitario actual desde la base de datos
    $stmtPrecio = $mysqli->prepare("SELECT precio, stock FROM productos WHERE id = ?");
    $stmtPrecio->bind_param("i", $idProducto);
    $stmtPrecio->execute();
    $resultado = $stmtPrecio->get_result();

    if ($resultado->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Producto no encontrado"]);
        exit;
    }

    $productoInfo = $resultado->fetch_assoc();
    $precio = $productoInfo['precio'];
    $stockActual = $productoInfo['stock'];

    if ($cantidad > $stockActual) {
        echo json_encode(["success" => false, "message" => "Stock insuficiente para el producto ID $idProducto"]);
        exit;
    }

    $total += $precio * $cantidad;
    $stmtPrecio->close();
}

// Insertar pedido
$stmtPedido = $mysqli->prepare("INSERT INTO pedidos (id_usuario, fecha, metodo_pago, total) VALUES (?, ?, ?, ?)");
$stmtPedido->bind_param("issd", $id_usuario, $fecha, $metodo_pago, $total);

if (!$stmtPedido->execute()) {
    echo json_encode(["success" => false, "message" => "Error al registrar el pedido"]);
    exit;
}

$idPedido = $stmtPedido->insert_id;
$stmtPedido->close();

// Insertar detalles del pedido y actualizar stock
foreach ($productos as $item) {
    $idProducto = $item['id_producto'];
    $cantidad = $item['cantidad'];
    $precio = $item['precio']; 
    $subtotal = $precio * $cantidad;


    // Insertar detalle
    $stmtDetalle = $mysqli->prepare("INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, subtotal) VALUES (?, ?, ?, ?)");
    $stmtDetalle->bind_param("iiid", $idPedido, $idProducto, $cantidad, $subtotal);
    $stmtDetalle->execute();
    $stmtDetalle->close();

    // Actualizar stock real
    $stmtStock = $mysqli->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
    $stmtStock->bind_param("ii", $cantidad, $idProducto);
    $stmtStock->execute();
    $stmtStock->close();
}

// Eliminar los productos comprados del carrito
foreach ($productos as $item) {
    $idProducto = $item['id_producto'];
    $stmtEliminar = $mysqli->prepare("DELETE FROM carrito WHERE id_usuario = ? AND id_producto = ?");
    $stmtEliminar->bind_param("ii", $id_usuario, $idProducto);
    $stmtEliminar->execute();
    $stmtEliminar->close();
}

// Obtener nombre y documento del usuario
$stmtDatos = $mysqli->prepare("SELECT nombre, apellido, documento FROM usuarios WHERE id = ?");
$stmtDatos->bind_param("i", $id_usuario);
$stmtDatos->execute();
$resultado = $stmtDatos->get_result();
$usuario = $resultado->fetch_assoc();
$stmtDatos->close();

$nombreCompleto = $usuario['nombre'] . ' ' . $usuario['apellido'];
$documento = $usuario['documento'];

// Generar PDF con FPDF
require 'fpdf/fpdf.php';

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Factura de Compra - Cyclomart', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, 'Nombre: ' . $nombreCompleto, 0, 1);
$pdf->Cell(0, 8, 'Documento: ' . $documento, 0, 1);
$pdf->Cell(0, 8, 'Fecha: ' . $fecha, 0, 1);
$pdf->Cell(0, 8, 'Pedido #: ' . $idPedido, 0, 1);
$pdf->Cell(0, 8, 'Metodo de pago: ' . $metodo_pago, 0, 1);
$pdf->Ln(5);

// Encabezado tabla
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(60, 10, 'Producto', 1);
$pdf->Cell(30, 10, 'Cantidad', 1);
$pdf->Cell(40, 10, 'Precio', 1);
$pdf->Cell(40, 10, 'Subtotal', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 12);
foreach ($productos as $item) {
    $idProducto = $item['id_producto'];
    $cantidad = $item['cantidad'];
    $precio = $item['precio'];
    $subtotal = $precio * $cantidad;

    $stmt = $mysqli->prepare("SELECT nombre FROM productos WHERE id = ?");
    $stmt->bind_param("i", $idProducto);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $nombreProducto = $resultado->fetch_assoc()['nombre'];
    $stmt->close();

    $pdf->Cell(60, 10, $nombreProducto, 1);
    $pdf->Cell(30, 10, $cantidad, 1);
    $pdf->Cell(40, 10, number_format($precio, 2), 1);
    $pdf->Cell(40, 10, number_format($subtotal, 2), 1);
    $pdf->Ln();
}

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(130, 10, 'TOTAL', 1);
$pdf->Cell(40, 10, number_format($total, 2), 1);

$archivoPDF = "factura_$idPedido.pdf";
$pdf->Output('F', $archivoPDF);

// Generar y enviar factura por correo con FPDF y PHPMailer
require_once __DIR__ . '/fpdf/fpdf.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->Subject = 'Factura de tu compra en Cyclomart';
    $mail->Body = 'Gracias por tu compra. Adjuntamos tu factura en PDF.';
    $mail->addAttachment($archivoPDF);

    $mail->send();
    unlink($archivoPDF);
} catch (Exception $e) {
    // Silenciar error del correo sin afectar la compra
}

echo json_encode(["success" => true, "message" => "Compra realizada con éxito"]);
?>
