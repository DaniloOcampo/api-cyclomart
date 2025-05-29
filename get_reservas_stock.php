<?php
header("Content-Type: application/json; charset=UTF-8");

include 'db.php';
$mysqli->set_charset("utf8");

$id_usuario = isset($_GET['id_usuario']) ? intval($_GET['id_usuario']) : 0;
$minutos_expiracion = 60;

$ahora = date("Y-m-d H:i:s");

// 1. Obtener todas las reservas activas (últimos 60 min)
$reservas = [];
$sql = "
    SELECT id_usuario, id_producto, cantidad, fecha
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

// 2. Calcular stock disponible para cada producto
$stock_resultado = [];
$sql = "SELECT id, nombre, stock FROM productos";
$result = $mysqli->query($sql);

while ($producto = $result->fetch_assoc()) {
    $id_producto = $producto['id'];
    $stock_total = (int)$producto['stock'];
    $reservado = 0;

    foreach ($reservas as $r) {
        if ((int)$r['id_producto'] === (int)$id_producto && (int)$r['id_usuario'] !== $id_usuario) {
            $reservado += (int)$r['cantidad'];
        }
    }

    $stock_disponible = max($stock_total - $reservado, 0);
    $stock_resultado[] = [
        "id_producto" => $id_producto,
        "nombre" => $producto['nombre'],
        "stock_total" => $stock_total,
        "reservado_por_otros" => $reservado,
        "stock_disponible_para_usuario" => $stock_disponible
    ];
}

$mysqli->close();

echo json_encode([
    "now" => $ahora,
    "usuario_actual" => $id_usuario,
    "reservas_activas" => $reservas,
    "stock_por_producto" => $stock_resultado
], JSON_PRETTY_PRINT);
?>
