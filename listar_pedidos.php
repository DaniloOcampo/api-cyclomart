<?php
header('Content-Type: application/json');
require 'db.php';

// Consulta para obtener los pedidos con total de productos
$sql = "
    SELECT 
        p.id, p.fecha, p.metodo_pago, p.total, p.estado,
        COUNT(dp.id_producto) AS cantidad_total
    FROM pedidos p
    JOIN detalle_pedido dp ON dp.id_pedido = p.id
    GROUP BY p.id
    ORDER BY p.fecha DESC
";


$result = $mysqli->query($sql);

if ($result && $result->num_rows > 0) {
    $pedidos = [];

    while ($row = $result->fetch_assoc()) {
        $pedidos[] = [
            "id" => (int)$row['id'],
            "fecha" => $row['fecha'],
            "metodoPago" => $row['metodo_pago'],
            "total" => (float)$row['total'],
            "estado" => $row['estado'],
            "cantidadTotal" => (int)$row['cantidad_total']
        ];
    }

    echo json_encode($pedidos);
} else {
    echo json_encode([]);
}

$conn->close();
