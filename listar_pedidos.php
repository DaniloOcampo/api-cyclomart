<?php
header("Content-Type: application/json");
require 'db.php';

$sql = "SELECT id, fecha, metodo_pago, total, id_usuario FROM pedidos";
$result = $mysqli->query($sql);

$pedidos = [];

while ($row = $result->fetch_assoc()) {
    // contar la cantidad de productos en el pedido
    $idPedido = $row['id'];
    $countSql = "SELECT SUM(cantidad) AS total_productos FROM detalle_pedido WHERE id_pedido = ?";
    $countStmt = $mysqli->prepare($countSql);
    $countStmt->bind_param("i", $idPedido);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $countData = $countResult->fetch_assoc();
    $row['cantidadTotal'] = (int) $countData['total_productos'];
    $pedidos[] = $row;
}

echo json_encode($pedidos);
?>
