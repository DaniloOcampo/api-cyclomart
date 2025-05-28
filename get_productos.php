<?php
header("Content-Type: application/json");
include 'db.php';

$id_usuario = intval($_GET['id_usuario'] ?? 0);

if ($id_usuario <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
    exit;
}

// Obtener los productos del carrito del usuario junto con el stock real desde la tabla productos
$sql = "
    SELECT 
        c.id_producto AS id,
        p.nombre,
        c.cantidad,
        p.precio,
        p.stock
    FROM carrito c
    JOIN productos p ON c.id_producto = p.id
    WHERE c.id_usuario = ?
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

$carrito = [];
while ($row = $result->fetch_assoc()) {
    $carrito[] = $row;
}

echo json_encode([
    'success' => true,
    'carrito' => $carrito
]);

$stmt->close();
$mysqli->close();
?>
