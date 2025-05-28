<?php
header('Content-Type: application/json');
include 'db.php';

$id_usuario = intval($_GET['id_usuario'] ?? 0);

if ($id_usuario <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
    exit;
}

$base_url = "https://api-cyclomart-1.onrender.com/";
$minutos_expiracion = 60;

$sql = "
    SELECT 
        c.id_producto, 
        p.nombre, 
        p.precio, 
        c.cantidad, 
        p.imagen,
        p.stock AS stock_total,
        (
            p.stock 
            - IFNULL((
                SELECT SUM(c2.cantidad)
                FROM carrito c2 
                WHERE c2.id_producto = p.id 
                AND c2.id_usuario != c.id_usuario 
                AND TIMESTAMPDIFF(MINUTE, c2.fecha, NOW()) <= ?
            ), 0)
        ) AS stock_disponible
    FROM carrito c
    JOIN productos p ON c.id_producto = p.id
    WHERE c.id_usuario = ?
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", $minutos_expiracion, $id_usuario);
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
        'stock' => max((int)$row['stock_disponible'], (int)$row['cantidad']),  // importante: asegura que nunca baje por debajo de lo que ya tiene el usuario
        'imagen' => $row['imagen'] ?? ''
    ];
}

$stmt->close();
$mysqli->close();

echo json_encode(['success' => true, 'carrito' => $carrito]);
