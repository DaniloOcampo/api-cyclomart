<?php
header('Content-Type: application/json');
require 'db.php';

// Consulta para obtener los pedidos con total de productos
$sql = "
    SELECT 
        p.id,
        p.fecha,
        p.metodo_pago,
        p.total,
        p.estado,
        COUNT(dp.id) AS cantidad_total
    FROM pedidos p
    LEFT JOIN detalle_pedido dp ON p.id = dp.pedido_id
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
