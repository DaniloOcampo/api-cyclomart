<?php
include 'db.php';
header("Content-Type: application/json");

// Convertimos a enteros para asegurar validación
$id_usuario = intval($_POST['id_usuario'] ?? 0);
$id_producto = intval($_POST['id_producto'] ?? 0);

if ($id_usuario <= 0 || $id_producto <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Parámetros inválidos."
    ]);
    exit;
}

$sql = "DELETE FROM carrito WHERE id_usuario = ? AND id_producto = ?";
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

