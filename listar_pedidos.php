<?php
header('Content-Type: application/json');
require 'db.php';

// Consulta para obtener los pedidos con total de productos
$sql = "
    SELECT 
        p.id AS id,
        p.fecha,
        p.metodo_pago,
        SUM(dp.cantidad) AS cantidadTotal,
        p.total
    FROM pedidos p
    LEFT JOIN detalle_pedido dp ON p.id = dp.id_pedido
    GROUP BY p.id
    ORDER BY p.fecha DESC
";



$result = $mysqli->query($sql);

if ($result && $result->num_rows > 0) {
    $pedidos = [];

while ($row = $result->fetch_assoc()) {
    $pedidos[] = [
        "id" => $row["id"],
        "fecha" => $row["fecha"],
        "metodoPago" => $row["metodo_pago"],
        "cantidadTotal" => (int)$row["cantidadTotal"],
        "total" => (float)$row["total"]
    ];
}


    echo json_encode($pedidos);
} else {
    echo json_encode([]);
}

$mysqli->close();
