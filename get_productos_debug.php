<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

include 'db.php';
$mysqli->set_charset("utf8");

$base_url = "https://api-cyclomart-1.onrender.com/";

$id_usuario = isset($_GET['id_usuario']) ? intval($_GET['id_usuario']) : 0;

// Tiempo de expiración de reserva (en minutos)
$minutos_expiracion = 60;

$sql = "
    SELECT 
        p.id, 
        p.nombre, 
        p.precio, 
        p.imagen, 
        p.descripcion, 
        p.tipo, 
        p.categoria_id, 
        c.nombre AS categoria,
        p.stock,
        GREATEST(
            p.stock 
            - IFNULL((
                SELECT SUM(c2.cantidad)
                FROM carrito c2
                WHERE 
                    c2.id_producto = p.id
                    AND c2.id_usuario != ?
                    AND TIMESTAMPDIFF(MINUTE, c2.fecha, NOW()) <= ?
            ), 0),
        0) AS stock_disponible
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", $id_usuario, $minutos_expiracion);
$stmt->execute();
$result = $stmt->get_result();


file_put_contents("debug_stock.txt", "NOW: " . date("Y-m-d H:i:s") . PHP_EOL, FILE_APPEND);
$reservas_debug = $mysqli->query("SELECT id_usuario, id_producto, cantidad, fecha FROM carrito");
while ($r = $reservas_debug->fetch_assoc()) {
    file_put_contents("debug_stock.txt", json_encode($r) . PHP_EOL, FILE_APPEND);
}

$productos = [];

while ($row = $result->fetch_assoc()) {
    if (!empty($row['imagen'])) {
        $row['imagen'] = $base_url . $row['imagen'];
    }
    $productos[] = $row;
}

// Devuelve SOLO el array de productos, sin objeto "success"
echo json_encode($productos);

$stmt->close();
$mysqli->close();
?>
