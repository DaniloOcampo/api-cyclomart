<?php
header('Content-Type: application/json');
require 'db.php'; // asegúrate que el path sea correcto

// Validar conexión
if (!isset($conn)) {
    if (isset($mysqli)) {
        $conn = $mysqli; // Adaptar si se usa $mysqli
    } else {
        echo json_encode(["success" => false, "message" => "Error: no se pudo conectar a la base de datos"]);
        exit;
    }
}

$input = json_decode(file_get_contents("php://input"), true);

$idUsuario = $input['id_usuario'] ?? null;
$productos = $input['productos'] ?? [];
$metodoPago = $input['metodo_pago'] ?? 'Efectivo';

if (!$idUsuario || empty($productos)) {
    echo json_encode(["success" => false, "message" => "Faltan datos para procesar la compra."]);
    exit;
}

// Calcular total
$total = 0;
foreach ($productos as $item) {
    $stmt = $conn->prepare("SELECT precio FROM productos WHERE id = ?");
    $stmt->bind_param("i", $item['id_producto']);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    $precio = $producto['precio'] ?? 0;
    $total += $precio * $item['cantidad'];
}

// Insertar en pedidos
$stmt = $conn->prepare("INSERT INTO pedidos (id_usuario, total, metodo_pago) VALUES (?, ?, ?)");
$stmt->bind_param("ids", $idUsuario, $total, $metodoPago);
if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Error al registrar el pedido."]);
    exit;
}

$idPedido = $stmt->insert_id;

// Insertar en detalle_pedido
$stmtDetalle = $conn->prepare("INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, subtotal) VALUES (?, ?, ?, ?)");
foreach ($productos as $item) {
    $stmt = $conn->prepare("SELECT precio FROM productos WHERE id = ?");
    $stmt->bind_param("i", $item['id_producto']);
    $stmt->execute();
    $res = $stmt->get_result();
    $precio = $res->fetch_assoc()['precio'] ?? 0;
    $subtotal = $precio * $item['cantidad'];

    $stmtDetalle->bind_param("iiid", $idPedido, $item['id_producto'], $item['cantidad'], $subtotal);
    $stmtDetalle->execute();
}

// Vaciar carrito
$stmt = $conn->prepare("DELETE FROM carrito WHERE id_usuario = ?");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();

echo json_encode([
    "success" => true,
    "message" => "Compra confirmada con éxito. Método de pago: $metodoPago"
]);
