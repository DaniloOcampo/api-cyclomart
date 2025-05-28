<?php
include 'db.php';
header("Content-Type: application/json");

$id_usuario = intval($_POST['id_usuario'] ?? 0);
$id_producto = intval($_POST['id_producto'] ?? 0);
$cantidad_nueva = intval($_POST['cantidad'] ?? 0);

if ($id_usuario <= 0 || $id_producto <= 0 || $cantidad_nueva < 1) {
    echo json_encode([
        "success" => false,
        "message" => "Parámetros inválidos."
    ]);
    exit;
}

$minutos_expiracion = 60;

// 1. Obtener stock total del producto
$stmt = $mysqli->prepare("SELECT stock FROM productos WHERE id = ?");
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Producto no encontrado."]);
    exit;
}
$stock_total = (int)$result->fetch_assoc()['stock'];
$stmt->close();

// 2. Obtener cantidad actual del usuario
$stmt = $mysqli->prepare("SELECT cantidad FROM carrito WHERE id_usuario = ? AND id_producto = ?");
$stmt->bind_param("ii", $id_usuario, $id_producto);
$stmt->execute();
$result = $stmt->get_result();
$cantidad_actual = $result->num_rows > 0 ? (int)$result->fetch_assoc()['cantidad'] : 0;
$stmt->close();

// 3. Obtener reservas activas de otros usuarios
$stmt = $mysqli->prepare("
    SELECT SUM(cantidad) AS reservado
    FROM carrito 
    WHERE id_producto = ? 
      AND id_usuario != ? 
      AND TIMESTAMPDIFF(MINUTE, fecha, NOW()) <= ?
");
$stmt->bind_param("iii", $id_producto, $id_usuario, $minutos_expiracion);
$stmt->execute();
$result = $stmt->get_result();
$reservado_otros = (int)($result->fetch_assoc()['reservado'] ?? 0);
$stmt->close();

// 4. Calcular stock disponible para este usuario
$stock_disponible = $stock_total - $reservado_otros;
if ($cantidad_nueva > $stock_disponible) {
    echo json_encode([
        "success" => false,
        "message" => "Solo puedes tener hasta $stock_disponible unidades."
    ]);
    exit;
}

// 5. Actualizar la cantidad y renovar la fecha
$stmt = $mysqli->prepare("UPDATE carrito SET cantidad = ?, fecha = NOW() WHERE id_usuario = ? AND id_producto = ?");
$stmt->bind_param("iii", $cantidad_nueva, $id_usuario, $id_producto);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Cantidad actualizada correctamente."]);
} else {
    echo json_encode(["success" => false, "message" => "Error al actualizar la cantidad."]);
}

$stmt->close();
$mysqli->close();
?>
