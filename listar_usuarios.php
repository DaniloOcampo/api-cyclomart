<?php
header("Content-Type: application/json");
require 'db.php';

$sql = "SELECT id, nombre, apellido, correo, documento, rol FROM usuarios";
$result = $mysqli->query($sql);

$usuarios = [];

while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
}

echo json_encode($usuarios);
?>
