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
     $subtotal = $item * $cantidad;

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

echo json_encode(["success" => true, "message" => "Compra realizada con éxito"]);
?>
