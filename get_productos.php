
<?php
header("Content-Type: application/json; charset=UTF-8");

include 'db.php';
$mysqli->set_charset("utf8");

$base_url = "https://api-cyclomart-1.onrender.com/";

$id_usuario = isset($_GET['id_usuario']) ? intval($_GET['id_usuario']) : 0;
$minutos_expiracion = 60;

// 1. Obtener todas las reservas activas
$reservas = [];
$sql = "
    SELECT id_usuario, id_producto, cantidad
    FROM carrito
    WHERE TIMESTAMPDIFF(MINUTE, fecha, NOW()) <= ?
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $minutos_expiracion);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reservas[] = $row;
}
$stmt->close();

// 2. Obtener productos y calcular stock disponible
$productos = [];
$sql = "
    SELECT p.id, p.nombre, p.precio, p.imagen, p.descripcion, p.tipo, p.categoria_id, c.nombre AS categoria, p.stock
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
";
$result = $mysqli->query($sql);

while ($row = $result->fetch_assoc()) {
    $id_producto = (int)$row['id'];
    $stock_total = (int)$row['stock'];
    $reservado = 0;

    foreach ($reservas as $r) {
        if ((int)$r['id_producto'] === $id_producto && (int)$r['id_usuario'] !== $id_usuario) {
            $reservado += (int)$r['cantidad'];
        }
    }

    $row['stock_disponible'] = max($stock_total - $reservado, 0);

    if (!empty($row['imagen'])) {
        $row['imagen'] = $base_url . $row['imagen'];
    }

    $productos[] = $row;
}

$mysqli->close();
echo json_encode($productos, JSON_PRETTY_PRINT);
?>
