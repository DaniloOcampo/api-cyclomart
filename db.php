<?php
$servername = "sql202.infinityfree.com";
$username = "if0_38987147";
$password = "6Xw83ovxuR";
$dbname = "if0_38987147_cyclomart";  // Aquí reemplaza XXX por el nombre exacto de tu base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>

