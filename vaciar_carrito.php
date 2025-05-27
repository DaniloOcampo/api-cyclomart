<?php
include 'db.php';
header('Content-Type: application/json');

$id_usuario = $_POST['id_usuario'] ?? null;

if ($id_usuario === null || intval($id_usuario) <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de usuario inválido'
    ]);
    exit;
}

$sql = "DELETE FROM carrito WHERE id_usuario = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $id_usuario);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Carrito vaciado correctamente'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al vaciar carrito'
    ]);
}

$stmt->close();
$mysqli->close();
?>
