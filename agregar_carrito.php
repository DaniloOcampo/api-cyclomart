<?php
include 'db.php';
header("Content-Type: application/json");

$id_usuario = intval($_POST['id_usuario'] ?? 0);
$id_producto = intval($_POST['id_producto'] ?? 0);

if ($id_usuario <= 0 || $id_producto <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

// 1. Obtener stock real
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
$cantidad_actual = $result->num_rows > 0 ? (int) $result->fetch_assoc()['cantidad'] : 0;
$stmt->close();

// 3. Verificar si puede agregar 1 más
if ($cantidad_actual + 1 > $stock_total) {
    echo json_encode(['success' => false, 'message' => 'Has alcanzado el máximo permitido']);
    exit;
}

// 4. Insertar o actualizar en carrito (reserva temporal)
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
