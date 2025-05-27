<?php
include 'db.php';
header("Content-Type: application/json");

$id_usuario = $_POST['id_usuario'] ?? null;
$id_producto = $_POST['id_producto'] ?? null;

if ($id_usuario === null || $id_producto === null) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan parámetros obligatorios."
    ]);
    exit;
}

$sql = "DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?";
$stmt = $mysqli->prepare($sql);
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
$mysqli->close();
?>
