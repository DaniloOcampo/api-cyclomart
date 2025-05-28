<?php
include 'db.php';
header("Content-Type: application/json");

$id_usuario = $_POST['id_usuario'] ?? null;
$id_producto = $_POST['id_producto'] ?? null;
$cantidad_nueva = $_POST['cantidad'] ?? null;

if ($id_usuario === null || $id_producto === null || $cantidad_nueva === null) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan parámetros obligatorios."
    ]);
    exit;
}

// Obtener stock total del producto
$stmt = $mysqli->prepare("SELECT stock FROM productos WHERE id = ?");
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stock_total = $row['stock'];
$stmt->close();

// Obtener cantidad reservada por otros usuarios en carrito
$stmt = $mysqli->prepare("SELECT SUM(cantidad) as reservado FROM carrito WHERE id_producto = ? AND id_usuario != ?");
$stmt->bind_param("ii", $id_producto, $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$reservado_por_otros = $row['reservado'] ?? 0;
$stmt->close();

// Calcular cantidad disponible para este usuario
$stock_disponible = $stock_total - $reservado_por_otros;

if ($cantidad_nueva > $stock_disponible) {
    echo json_encode([
        "success" => false,
        "message" => "No hay suficiente stock disponible. Solo quedan $stock_disponible unidades."
    ]);
    exit;
}

// Actualizar cantidad
$stmt = $mysqli->prepare("UPDATE carrito SET cantidad = ? WHERE id_usuario = ? AND id_producto = ?");
$stmt->bind_param("iii", $cantidad_nueva, $id_usuario, $id_producto);

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
