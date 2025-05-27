<?php
require '../conexion.php';

header('Content-Type: application/json');

// Obtener los parámetros del cuerpo de la solicitud
$id_usuario = $_POST['usuario_id'] ?? null;
$id_producto = $_POST['producto_id'] ?? null;
$cantidad = $_POST['cantidad'] ?? null;

// Validación estricta con comparación tipo-strict para evitar falsos negativos (como cantidad = 0)
if ($id_usuario === null || $id_producto === null || $cantidad === null) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan parámetros obligatorios."
    ]);
    exit;
}

// Actualizar la cantidad del producto en el carrito
$sql = "UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?";
$stmt = $conn->prepare($sql);
$resultado = $stmt->execute([$cantidad, $id_usuario, $id_producto]);

if ($resultado) {
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
?>
