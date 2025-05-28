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

// 1. Consultar stock total
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

// 2. Consultar cantidad actual en el carrito del usuario
$stmt = $mysqli->prepare("SELECT cantidad FROM carrito WHERE id_usuario = ? AND id_producto = ?");
$stmt->bind_param("ii", $id_usuario, $id_producto);
$stmt->execute();
$result = $stmt->get_result();
$cantidad_actual_usuario = $result->num_rows > 0 ? (int) $result->fetch_assoc()['cantidad'] : 0;
$stmt->close();

// 3. Consultar reservas activas de otros usuarios (últimos 60 min)
$stmt = $mysqli->prepare("
    SELECT SUM(cantidad) AS reservado 
    FROM carrito 
    WHERE id_producto = ? 
      AND id_usuario != ? 
      AND TIMESTAMPDIFF(MINUTE, fecha, NOW()) <= 60
");
$stmt->bind_param("ii", $id_producto, $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$reservado_otros = (int) ($result->fetch_assoc()['reservado'] ?? 0);
$stmt->close();

// 4. Verificar si hay disponibilidad para añadir 1 más
$stock_disponible = $stock_total - $reservado_otros;
$proxima_cantidad = $cantidad_actual_usuario + 1;

if ($proxima_cantidad > $stock_disponible) {
    echo json_encode([
        'success' => false,
        'message' => "No hay suficiente stock disponible. Solo quedan $stock_disponible unidades."
    ]);
    exit;
}

// 5. Insertar o actualizar (y renovar fecha)
$stmt = $mysqli->prepare("
    INSERT INTO carrito (id_usuario, id_producto, cantidad, fecha)
    VALUES (?, ?, 1, NOW())
    ON DUPLICATE KEY UPDATE cantidad = cantidad + 1, fecha = NOW()
");
$stmt->bind_param("ii", $id_usuario, $id_producto);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Producto añadido al carrito']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar en el carrito']);
}

$stmt->close();
$mysqli->close();
?>


