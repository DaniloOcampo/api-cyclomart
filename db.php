<?php
$host = "tramway.proxy.rlwy.net";
$port = 18242;
$user = "root";
$password = "ScxxoIjnbZsCbLEkoEWqbpMoZcuqhFtJ"; // Cambia esto por tu contraseña real
$dbname = "railway";

// Crear conexión
$mysqli = new mysqli($host, $user, $password, $dbname, $port);

// Verificar conexión
if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// Opcional: establecer charset
$mysqli->set_charset("utf8");
?>
