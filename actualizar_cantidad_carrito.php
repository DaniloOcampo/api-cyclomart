<?php
include 'db.php';
header("Content-Type: application/json");

$id_usuario = intval($_POST['id_usuario'] ?? 0);
$id_producto = intval($_POST['id_producto'] ?? 0);
$cantidad_nueva = intval($_POST['cantidad'] ?? -1);

// Validación básica
if ($id_usuario <= 0 || $id_producto <= 0 || $cantidad_nueva < 1) {
    echo json_encode([
        "success" => false,
        "message" => "Parámetros inválidos."
    ]);
    exit;
}

// Verificar stock total
$stmt = $mysqli->prepare("SELECT stock FROM productos WHERE id = ?");
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Producto no encontrado."
    ]);
    exit;
}

$stock_total = (int) $result->fetch_assoc()['stock'];
$stmt->close();

// Verificar cantidad ya reservada por otros
$stmt = $mysqli->prepare("SELECT SUM(cantidad) AS reservado FROM carrito WHERE id_producto = ? AND id_usuario != ?");
$stmt->bind_param("ii", $id_producto, $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$reservado_por_otros = (int) ($result->fetch_assoc()['reservado'] ?? 0);
$stmt->close();

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
