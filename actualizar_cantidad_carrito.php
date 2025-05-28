<?php
include 'db.php';
header('Content-Type: application/json');

$id_usuario = $_POST['id_usuario'] ?? null;
$id_producto = $_POST['id_producto'] ?? null;
$cantidad = $_POST['cantidad'] ?? null;

if ($id_usuario === null || $id_producto === null || $cantidad === null) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan parámetros obligatorios."
    ]);
    exit;
}

// Validar stock disponible
$sqlStock = "SELECT stock FROM productos WHERE id = ?";
$stmtStock = $mysqli->prepare($sqlStock);
$stmtStock->bind_param("i", $id_producto);
$stmtStock->execute();
$resultStock = $stmtStock->get_result();
$producto = $resultStock->fetch_assoc();

if (!$producto) {
    echo json_encode(["success" => false, "message" => "Producto no encontrado."]);
    exit;
}

$stockDisponible = (int)$producto['stock'];
if ($cantidad > $stockDisponible) {
    echo json_encode([
        "success" => false,
        "message" => "No hay suficiente stock disponible."
    ]);
    exit;
}

// Actualizar cantidad
$sql = "UPDATE carrito SET cantidad = ? WHERE id_usuario = ? AND id_producto = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("iii", $cantidad, $id_usuario, $id_producto);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Cantidad actualizada correctamente."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error al actualizar la cantidad."
    ]);
}

$stmt->close();
$mysqli->close();
?>
