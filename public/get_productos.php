<?php
header("Content-Type: application/json");
include 'db.php';

$sql = "SELECT productos.*, categorias.nombre AS categoria 
        FROM productos
        JOIN categorias ON productos.categoria_id = categorias.id";
$result = $conn->query($sql);

$productos = array();
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

echo json_encode($productos);
?>
