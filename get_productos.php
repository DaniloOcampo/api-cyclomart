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
                    AND TIMESTAMPDIFF(MINUTE, c2.fecha, NOW()) <= ?
            ), 0)
            + IFNULL((
                SELECT cantidad 
                FROM carrito 
                WHERE id_usuario = ? AND id_producto = p.id
            ), 0),
        0) AS stock_disponible
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", $minutos_expiracion, $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

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
