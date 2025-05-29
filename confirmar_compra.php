<?php
header('Content-Type: application/json');
require 'db.php';

$input = json_decode(file_get_contents("php://input"), true);

$idUsuario = $input['id_usuario'] ?? null;
$productos = $input['productos'] ?? [];
$metodoPago = $input['metodo_pago'] ?? 'Efectivo';

if (!$idUsuario || empty($productos)) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan datos para procesar la compra."
    ]);
    exit;
}

$total = 0;

// Cálculo del total (suma precios desde base de datos)
foreach ($productos as $item) {
    $idProducto = $item['id_producto'];
    $cantidad = $item['cantidad'];

    $stmt = $mysqli->prepare("SELECT precio FROM productos WHERE id = ?");
    $stmt->bind_param("i", $idProducto);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();

    if ($producto) {
        $subtotal = $producto['precio'] * $cantidad;
        $total += $subtotal;
    }
}

// Insertar pedido
$stmt = $mysqlin->prepare("INSERT INTO pedidos (id_usuario, total, metodo_pago) VALUES (?, ?, ?)");
$stmt->bind_param("ids", $idUsuario, $total, $metodoPago);
if (!$stmt->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Error al registrar el pedido."
    ]);
    exit;
}

$idPedido = $stmt->insert_id;

// Insertar detalle del pedido
$stmtDetalle = $mysqli->prepare("INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, subtotal) VALUES (?, ?, ?, ?)");
foreach ($productos as $item) {
    $idProducto = $item['id_producto'];
    $cantidad = $item['cantidad'];

    // Obtener el precio de nuevo para registrar subtotal
    $stmtPrecio = $mysqli->prepare("SELECT precio FROM productos WHERE id = ?");
    $stmtPrecio->bind_param("i", $idProducto);
    $stmtPrecio->execute();
    $res = $stmtPrecio->get_result();
    $precio = $res->fetch_assoc()['precio'] ?? 0;

    $subtotal = $precio * $cantidad;

    $stmtDetalle->bind_param("iiid", $idPedido, $idProducto, $cantidad, $subtotal);
    $stmtDetalle->execute();
}

// Vaciar carrito del usuario
$stmt = $mysqli->prepare("DELETE FROM carrito WHERE id_usuario = ?");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();

echo json_encode([
    "success" => true,
    "message" => "Compra confirmada con éxito. Método de pago: $metodoPago"
]);
