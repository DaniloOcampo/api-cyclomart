<?php
header('Content-Type: application/json');
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (
    !isset($data['usuario_id']) ||
    !isset($data['productos']) ||
    !isset($data['total']) ||
    !isset($data['metodo_pago']) ||
    empty($data['productos'])
) {
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit;
}

$usuario_id = $data['usuario_id'];
$productos = $data['productos'];
$total = $data['total'];
$metodo_pago = $data['metodo_pago'];

// Insertar en pedidos
$pedidoSql = "INSERT INTO pedidos (usuario_id, fecha, metodo_pago, total) VALUES (?, NOW(), ?, ?)";
$pedidoStmt = $mysqli->prepare($pedidoSql);
$pedidoStmt->bind_param("isd", $usuario_id, $metodo_pago, $total);

if (!$pedidoStmt->execute()) {
    echo json_encode(["success" => false, "message" => "Error al registrar el pedido"]);
    exit;
}

$pedido_id = $pedidoStmt->insert_id;

// Insertar detalles y descontar stock real
foreach ($productos as $producto) {
    $producto_id = $producto['id'];
    $cantidad = $producto['cantidad'];
    $precio = $producto['precio'];

    // Insertar detalle
    $detalleSql = "INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
    $detalleStmt = $mysqli->prepare($detalleSql);
    $detalleStmt->bind_param("iiid", $pedido_id, $producto_id, $cantidad, $precio);
    $detalleStmt->execute();

    // Descontar del stock real
    $updateStockSql = "UPDATE productos SET stock = stock - ? WHERE id = ?";
    $updateStockStmt = $mysqli->prepare($updateStockSql);
    $updateStockStmt->bind_param("ii", $cantidad, $producto_id);
    $updateStockStmt->execute();
}

echo json_encode(["success" => true, "message" => "Compra confirmada"]);
?>
