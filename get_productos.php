<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

include 'db.php';
$mysqli->set_charset("utf8");

$base_url = "https://api-cyclomart-1.onrender.com/";

$id_usuario = isset($_GET['id_usuario']) ? intval($_GET['id_usuario']) : 0;

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
        GREATEST(
            p.stock - IFNULL((
                SELECT SUM(c2.cantidad) 
                FROM carrito c2 
                WHERE c2.id_producto = p.id AND c2.id_usuario != ?
            ), 0), 
        0) AS stock
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

$productos = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['imagen'])) {
        $row['imagen'] = $base_url . $row['imagen'];
    }
    $productos[] = $row;
}

echo json_encode($productos);

$stmt->close();
$mysqli->close();
?>
