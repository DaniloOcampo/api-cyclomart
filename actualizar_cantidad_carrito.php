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

// Obtener el stock total del producto desde la tabla productos
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

// Verificar si el usuario está pidiendo más de lo permitido
if ($cantidad_nueva > $stock_total) {
    echo json_encode([
        "success" => false,
        "message" => "Solo puedes tener hasta $stock_total unidades."
    ]);
    exit;
}

// Actualizar la cantidad en el carrito (y renovar la fecha)
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
