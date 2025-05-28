<?php
include 'db.php';
header('Content-Type: application/json');

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
    $conn->beginTransaction();

    // Obtener stock disponible del producto
    $sqlStock = "SELECT stock FROM productos WHERE id = ? FOR UPDATE";
    $stmtStock = $conn->prepare($sqlStock);
    $stmtStock->execute([$id_producto]);
    $stock = $stmtStock->fetchColumn();

    if ($stock === false) {
        throw new Exception("Producto no encontrado.");
    }

    // Obtener cantidad actual en el carrito
    $sqlCarrito = "SELECT cantidad FROM carrito WHERE id_usuario = ? AND id_producto = ?";
    $stmtCarrito = $conn->prepare($sqlCarrito);
    $stmtCarrito->execute([$id_usuario, $id_producto]);
    $cantidadActual = $stmtCarrito->fetchColumn() ?? 0;

    $diferencia = $cantidad - $cantidadActual;

    if ($diferencia > 0 && $diferencia > $stock) {
        throw new Exception("No hay suficiente stock disponible.");
    }

    // Actualizar la cantidad del producto en el carrito
    $sqlUpdate = "UPDATE carrito SET cantidad = ? WHERE id_usuario = ? AND id_producto = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $resultado = $stmtUpdate->execute([$cantidad, $id_usuario, $id_producto]);

    if ($resultado) {
        $conn->commit();
        echo json_encode([
            "success" => true,
            "message" => "Cantidad actualizada correctamente."
        ]);
    } else {
        throw new Exception("Error al actualizar la cantidad.");
    }

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
