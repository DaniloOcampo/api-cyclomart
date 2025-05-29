<?php
header("Content-Type: application/json; charset=UTF-8");
include 'db.php';
$mysqli->set_charset("utf8");

$base_url = "https://api-cyclomart-1.onrender.com/";
$minutos_expiracion = 60;

// 1. Limpiar reservas vencidas (más de 60 minutos)
$mysqli->query("DELETE FROM carrito WHERE TIMESTAMPDIFF(MINUTE, fecha, NOW()) > $minutos_expiracion");

// 2. Obtener SUMA de reservas activas por producto (de todos los usuarios)
$reservas = [];
$sql = "
    SELECT id_producto, SUM(cantidad) AS total_reservado
    FROM carrito
    WHERE TIMESTAMPDIFF(MINUTE, fecha, NOW()) <= ?
    GROUP BY id_producto
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $minutos_expiracion);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reservas[(int)$row['id_producto']] = (int)$row['total_reservado'];
}
$stmt->close();

// 3. Obtener catálogo completo
$productos = [];
$sql = "
    SELECT p.id, p.nombre, p.precio, p.imagen, p.descripcion, p.tipo,
           p.categoria_id, c.nombre AS categoria, p.stock
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
";
$result = $mysqli->query($sql);

while ($row = $result->fetch_assoc()) {
    $id_producto = (int)$row['id'];
    $stock_total = (int)$row['stock'];
    $reservado = $reservas[$id_producto] ?? 0;
    $stock_disponible = max($stock_total - $reservado, 0);

    if (!empty($row['imagen'])) {
        $row['imagen'] = $base_url . $row['imagen'];
    }

    $row['stock_disponible'] = $stock_disponible;
    $productos[] = $row;
}

$mysqli->close();
echo json_encode($productos, JSON_PRETTY_PRINT);
?>
