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

// Iniciar transacción
$mysqli->begin_transaction();

try {
    foreach ($productos as $item) {
        $id_producto = intval($item['id_producto']);
        $cantidad = intval($item['cantidad']);

        // Verificar stock disponible (incluyendo expiración de reservas)
        $sql = "SELECT stock FROM productos WHERE id = ? FOR UPDATE";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $stmt->bind_result($stock);
        if ($stmt->fetch() === null) {
            throw new Exception("Producto no encontrado: $id_producto");
        }
        $stmt->close();

        // Sumar cuántas unidades están reservadas por otros usuarios
        $sql = "SELECT SUM(cantidad) FROM carrito WHERE id_producto = ? AND id_usuario != ? AND TIMESTAMPDIFF(MINUTE, fecha, NOW()) <= 60";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $id_producto, $id_usuario);
        $stmt->execute();
        $stmt->bind_result($reservadas_otros);
        $stmt->fetch();
        $stmt->close();

        $reservadas_otros = intval($reservadas_otros);
        $stock_disponible = $stock - $reservadas_otros;

        if ($cantidad > $stock_disponible) {
            throw new Exception("Stock insuficiente para el producto $id_producto.");
        }

        // Descontar del stock real
        $sql = "UPDATE productos SET stock = stock - ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $cantidad, $id_producto);
        $stmt->execute();
        $stmt->close();
    }

    // Vaciar el carrito del usuario
    $sql = "DELETE FROM carrito WHERE id_usuario = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->close();

    $mysqli->commit();
    echo json_encode(["success" => true, "message" => "Compra realizada con éxito."]);
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(["success" => false, "message" => "Error en la compra: " . $e->getMessage()]);
}
?>
