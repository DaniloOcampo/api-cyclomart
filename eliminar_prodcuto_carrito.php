<?php
header("Content-Type: application/json");
include 'db.php';

$id_usuario = $_POST['usuario_id'] ?? null;
$id_producto = $_POST['producto_id'] ?? null;

if (!$id_usuario || !$id_producto) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan parámetros obligatorios."
    ]);
    exit;
}

$sql = "DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_usuario, $id_producto);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Producto eliminado del carrito."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error al eliminar el producto."
    ]);
}

$stmt->close();
$conn->close();
?>
