<?php
include 'db.php';
header("Content-Type: application/json");

$id_usuario = intval($_POST['id_usuario'] ?? 0);
$id_producto = intval($_POST['id_producto'] ?? 0);

if ($id_usuario <= 0 || $id_producto <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$minutos_expiracion = 60;

// 1. Obtener stock total del producto
$stmt = $mysqli->prepare("SELECT stock FROM productos WHERE id = ?");
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    exit;
}
$stock_total = (int) $result->fetch_assoc()['stock'];
$stmt->close();

// 2. Obtener cantidad actual del usuario
$stmt = $mysqli->prepare("SELECT cantidad FROM carrito WHERE id_usuario = ? AND id_producto = ?");
$stmt->bind_param("ii", $id_usuario, $id_producto);
$stmt->execute();
$result = $stmt->get_result();
$cantidad_usuario = $result->num_rows > 0 ? (int) $result->fetch_assoc()['cantidad'] : 0;
$stmt->close();

// 3. Obtener reservas activas de otros usuarios
$stmt = $mysqli->prepare("
    SELECT SUM(cantidad) as reservado
    FROM carrito 
    WHERE id_producto = ? 
      AND id_usuario != ? 
      AND TIMESTAMPDIFF(MINUTE, fecha, NOW()) <= ?
");
$stmt->bind_param("iii", $id_producto, $id_usuario, $minutos_expiracion);
$stmt->execute();
$result = $stmt->get_result();
$reservado_otros = (int) ($result->fetch_assoc()['reservado'] ?? 0);
$stmt->close();

// 4. Calcular stock disponible para este usuario
$stock_disponible = $stock_total - $reservado_otros;
$proxima_cantidad = $cantidad_usuario + 1;

if ($proxima_cantidad > $stock_disponible) {
    echo json_encode([
        'success' => false,
        'message' => "Has alcanzado el máximo permitido"
    ]);
    exit;
}

// 5. Insertar o actualizar carrito
$stmt = $mysqli->prepare("
    INSERT INTO carrito (id_usuario, id_producto, cantidad, fecha)
    VALUES (?, ?, 1, NOW())
    ON DUPLICATE KEY UPDATE cantidad = cantidad + 1, fecha = NOW()
");
$stmt->bind_param("ii", $id_usuario, $id_producto);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Producto añadido al carrito']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al agregar al carrito']);
}

$stmt->close();
$mysqli->close();
?>
