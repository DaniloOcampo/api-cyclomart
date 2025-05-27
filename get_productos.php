<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

include 'db.php';

$mysqli->set_charset("utf8");


$base_url = "https://api-cyclomart-1.onrender.com/";

$sql = "SELECT productos.id, productos.nombre, productos.precio, productos.imagen, 
               productos.descripcion, productos.tipo, productos.stock, 
               categorias.nombre AS categoria, productos.categoria_id
        FROM productos
        JOIN categorias ON productos.categoria_id = categorias.id";

$result = $mysqli->query($sql);

if ($result) {
    $productos = array();
    while ($row = $result->fetch_assoc()) {
        // Concatenar la URL completa de la imagen
        if (!empty($row['imagen'])) {
            $row['imagen'] = $base_url . $row['imagen'];
        }
        $productos[] = $row;
    }
    echo json_encode($productos);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Error en la consulta: " . $mysqli->error]);
}

$mysqli->close();
?>


