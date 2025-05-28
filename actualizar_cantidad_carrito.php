<?php
include 'db.php';
header("Content-Type: application/json");

$id_usuario = $_POST['id_usuario'] ?? null;
$id_producto = $_POST['id_producto'] ?? null;
$cantidad = $_POST['cantidad'] ?? null;

if ($id_usuario === null || $id_producto === null || $cantidad === null) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan parámetros obligatorios."
    ]);
    exit;
}

try {
    $mysqli->begin_transaction();

    // Verificar el stock actual del producto
    $stmt = $mysqli->prepare("SELECT stock FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();

    if (!$producto) {
        throw new Exception("Producto no encontrado.");
    }

    $stock_disponible = intval($producto['stock']);

    if ($cantidad > $stock_disponible) {
        throw new Exception("No hay suficiente stock disponible.");
    }

    // Actualizar la cantidad en el carrito
    $stmt = $mysqli->prepare("UPDATE carrito SET cantidad = ? WHERE id_usuario = ? AND id_producto = ?");
    $stmt->bind_param("iii", $cantidad, $id_usuario, $id_producto);
    $stmt->execute();

    $mysqli->commit();

    echo json_encode([
        "success" => true,
        "message" => "Cantidad actualizada correctamente."
    ]);
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

$mysqli->close();
?>
