<?php
header('Content-Type: application/json');
include 'db.php';

$id_usuario = intval($_GET['id_usuario'] ?? 0);

if ($id_usuario <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
    exit;
}

$base_url = "https://api-cyclomart-1.onrender.com/";

$sql = "SELECT c.id_producto, p.nombre, p.precio, c.cantidad, p.stock AS stock_total, p.imagen
        FROM carrito c
        JOIN productos p ON c.id_producto = p.id
        WHERE c.id_usuario = ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

$carrito = [];

while ($row = $result->fetch_assoc()) {
    if (!empty($row['imagen'])) {
        $row['imagen'] = $base_url . $row['imagen'];
    }
    $carrito[] = [
        'id' => (int)$row['id_producto'],
        'nombre' => $row['nombre'],
        'precio' => (float)$row['precio'],
        'cantidad' => (int)$row['cantidad'],
        'stock_total' => (int)$row['stock_total'],
        'imagen' => $row['imagen'] ?? ''
    ];
}

$stmt->close();
$mysqli->close();

echo json_encode(['success' => true, 'carrito' => $carrito]);
