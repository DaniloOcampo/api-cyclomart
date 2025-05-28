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

// 1. Obtener stock total
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

// 2. Obtener cantidad actual del usuario en carrito
$stmt = $mysqli->prepare("SELECT cantidad FROM carrito WHERE id_usuario = ? AND id_producto = ?");
$stmt->bind_param("ii", $id_usuario, $id_producto);
$stmt->execute();
$result = $stmt->get_result();
$cantidad_actual_usuario = $result->num_rows > 0 ? (int) $result->fetch_assoc()['cantidad'] : 0;
$stmt->close();

// 3. Obtener reservas activas (todos los usuarios) en los últimos 60 minutos
$stmt = $mysqli->prepare("
    SELECT SUM(cantidad) as total_reservado 
    FROM carrito 
    WHERE id_producto = ? 
    AND TIMESTAMPDIFF(MINUTE, fecha, NOW()) <= 60
");
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$result = $stmt->get_result();
$total_reservado = (int)($result->fetch_assoc()['total_reservado'] ?? 0);
$stmt->close();

// 4. Calcular disponibilidad real para este usuario
$stock_disponible_para_usuario = $stock_total - ($total_reservado - $cantidad_actual_usuario);

// ¿Puede agregar 1 más?
$proxima_cantidad = $cantidad_actual_usuario + 1;

if ($proxima_cantidad > $stock_disponible_para_usuario) {
    echo json_encode([
        'success' => false,
        'message' => "No hay suficiente stock disponible. Solo puedes tener hasta $stock_disponible_para_usuario unidades."
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
    echo json_encode(['success' => false, 'message' => 'Error al guardar en el carrito']);
}

$stmt->close();
$mysqli->close();
?>
