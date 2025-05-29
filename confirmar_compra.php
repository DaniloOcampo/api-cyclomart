<?php
header("Content-Type: application/json; charset=UTF-8");
include 'db.php';
$mysqli->set_charset("utf8");

$data = json_decode(file_get_contents("php://input"), true);

$id_usuario = intval($data['id_usuario'] ?? 0);
$productos = $data['productos'] ?? [];

if ($id_usuario <= 0 || empty($productos)) {
    echo json_encode(["success" => false, "message" => "Datos incompletos."]);
    exit;
}

$mysqli->begin_transaction();

try {
    // Crear el pedido
    $sql_pedido = "INSERT INTO pedidos (id_usuario, fecha) VALUES (?, NOW())";
    $stmt = $mysqli->prepare($sql_pedido);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $id_pedido = $stmt->insert_id;

    foreach ($productos as $item) {
        $id_producto = intval($item['id_producto']);
        $cantidad = intval($item['cantidad']);

        // Verificar stock actual
        $sql = "SELECT stock FROM productos WHERE id = ? FOR UPDATE";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $stmt->bind_result($stock);
        if (!$stmt->fetch()) {
            throw new Exception("Producto no encontrado: $id_producto");
        }
        $stmt->close();

        if ($stock < $cantidad) {
            throw new Exception("Stock insuficiente para el producto $id_producto");
        }

        // Descontar stock
        $sql_update = "UPDATE productos SET stock = stock - ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql_update);
        $stmt->bind_param("ii", $cantidad, $id_producto);
        $stmt->execute();

        // Insertar en detalle_pedido
        $sql_detalle = "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($sql_detalle);
        $stmt->bind_param("iii", $id_pedido, $id_producto, $cantidad);
        $stmt->execute();
    }

    $mysqli->commit();
    echo json_encode(["success" => true, "message" => "Compra confirmada."]);
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>
