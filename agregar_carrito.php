<?php
header('Content-Type: application/json');
require_once 'db.php';

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

$mysqli->begin_transaction();

try {
    $sql = "SELECT stock FROM productos WHERE id = ? FOR UPDATE";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Producto no encontrado');
    }

    $stock = (int)$result->fetch_assoc()['stock'];

    if ($stock < 1) {
        throw new Exception('Sin stock disponible');
    }

    $sql = "INSERT INTO carrito (id_usuario, id_producto, cantidad)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE cantidad = cantidad + 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $id_producto);
    if (!$stmt->execute()) {
        throw new Exception('Error al guardar en el carrito');
    }

    $sql = "UPDATE productos SET stock = stock - 1 WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id_producto);
    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar el stock');
    }

    $mysqli->commit();

    echo json_encode(['success' => true, 'message' => 'Producto añadido al carrito']);
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
