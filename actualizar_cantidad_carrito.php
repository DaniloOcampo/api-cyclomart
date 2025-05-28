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
$cantidad_actual_usuario = $result->num_rows > 0 ? (int)$result->fetch_assoc()['cantidad'] : 0;
$stmt->close();

// 3. Obtener suma total de reservas activas (todos los usuarios)
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

// 4. Calcular el máximo que puede tener el usuario
$stock_disponible_para_usuario = $stock_total - ($total_reservado - $cantidad_actual_usuario);

if ($cantidad_nueva > $stock_disponible_para_usuario) {
    echo json_encode([
        "success" => false,
        "message" => "No hay suficiente stock disponible. Solo puedes tener hasta $stock_disponible_para_usuario unidades."
    ]);
    exit;
}

// 5. Actualizar cantidad y fecha
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

