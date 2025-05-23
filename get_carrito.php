<?php
header('Content-Type: application/json');
include 'db.php';

$id_usuario = intval($_GET['id_usuario'] ?? 0);

if ($id_usuario <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
    exit;
}

$sql = "SELECT c.id_producto, p.nombre, p.precio, c.cantidad
        FROM carrito c
        JOIN productos p ON c.id_producto = p.id
        WHERE c.id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

$carrito = [];

while ($row = $result->fetch_assoc()) {
    $carrito[] = [
        'id' => $row['id_producto'],
        'nombre' => $row['nombre'],
        'precio' => (float)$row['precio'],
        'cantidad' => (int)$row['cantidad']
    ];
}

echo json_encode(['success' => true, 'carrito' => $carrito]);
?>
