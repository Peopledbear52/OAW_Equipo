<?php
$host = "localhost";
$usuario = "root";
$contrasena = "";
$db = "lector rss";

$conn = new mysqli($host, $usuario, $contrasena, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>