<?php
header('Content-Type: application/json');
include 'db.php';

if (!$mysqli) {
    echo json_encode(['success' => false, 'message' => 'No hay conexión a la base de datos']);
    exit;
}

$id_usuario = intval($_POST['id_usuario'] ?? 0);
$id_producto = intval($_POST['id_producto'] ?? 0);

if ($id_usuario <= 0 || $id_producto <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

// Verificar stock total
$stmt = $mysqli->prepare("SELECT stock FROM productos WHERE id = ?");
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
    exit;
}
$stock_total = (int) $result->fetch_assoc()['stock'];
$stmt->close();

// Verificar cuántos hay ya en el carrito del usuario
$stmt = $mysqli->prepare("SELECT cantidad FROM carrito WHERE id_usuario = ? AND id_producto = ?");
$stmt->bind_param("ii", $id_usuario, $id_producto);
$stmt->execute();
$result = $stmt->get_result();
$cantidad_actual = $result->num_rows > 0 ? (int)$result->fetch_assoc()['cantidad'] : 0;
$stmt->close();

// Verificar cuánto han reservado otros usuarios
$stmt = $mysqli->prepare("SELECT SUM(cantidad) AS reservado FROM carrito WHERE id_producto = ? AND id_usuario != ?");
$stmt->bind_param("ii", $id_producto, $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$reservado_otros = (int) ($result->fetch_assoc()['reservado'] ?? 0);
$stmt->close();

$stock_disponible = $stock_total - $reservado_otros;

if ($cantidad_actual + 1 > $stock_disponible) {
    echo json_encode(['success' => false, 'message' => "No hay suficiente stock disponible. Solo quedan $stock_disponible unidades."]);
    exit;
}

// Insertar o actualizar
$sql = "INSERT INTO carrito (id_usuario, id_producto, cantidad)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE cantidad = cantidad + 1";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", $id_usuario, $id_producto);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Producto añadido al carrito']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar en el carrito']);
}

$stmt->close();
$mysqli->close();
?>


