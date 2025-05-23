<?php
header("Content-Type: application/json");
include 'db.php';

$id_usuario = $_POST['usuario_id'] ?? null;
$id_producto = $_POST['producto_id'] ?? null;
$cantidad = $_POST['cantidad'] ?? null;

if (!$id_usuario || !$id_producto || !$cantidad) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan parámetros obligatorios."
    ]);
    exit;
}

$sql = "UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("iii", $cantidad, $id_usuario, $id_producto);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Cantidad actualizada correctamente."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error al actualizar la cantidad."
    ]);
}

$stmt->close();
$mysqli->close();
?>
