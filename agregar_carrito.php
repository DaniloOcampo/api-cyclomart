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

// Inicia transacción para evitar condiciones de carrera
$mysqli->begin_transaction();

try {
    // Consulta el stock original del producto
    $sql = "SELECT stock FROM productos WHERE id = ? FOR UPDATE";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Producto no encontrado');
    }

    $stock = (int)$result->fetch_assoc()['stock'];
    $stmt->close();

    // Consultar la cantidad actual del producto en el carrito del usuario
    $sql = "SELECT cantidad FROM carrito WHERE id_usuario = ? AND id_producto = ? FOR UPDATE";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();

    $cantidadEnCarrito = 0;
    if ($result->num_rows > 0) {
        $cantidadEnCarrito = (int)$result->fetch_assoc()['cantidad'];
    }
    $stmt->close();

    // Verifica si hay suficiente stock disponible
    if ($cantidadEnCarrito >= $stock) {
        throw new Exception('Ya has añadido el máximo disponible de este producto');
    }

    // Insertar o actualizar carrito
    $sql = "INSERT INTO carrito (id_usuario, id_producto, cantidad)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE cantidad = cantidad + 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $id_producto);
    if (!$stmt->execute()) {
        throw new Exception('Error al guardar en el carrito');
    }

    $stmt->close();
    $mysqli->commit();

    echo json_encode(['success' => true, 'message' => 'Producto añadido al carrito']);
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$mysqli->close();

