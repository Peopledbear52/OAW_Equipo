<?php 
require_once('simplepie-1.5\autoloader.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    $feedUrl = $data["url"];

    $feed = new SimplePie();
    $feed->set_feed_url($feedUrl); 
    $feed->enable_cache(false); 
    $feed->init();
    $feed->handle_content_type(); 

    $titulo = $feed->get_title();
    $descripcion = $feed->get_description();
    $url = $feed->get_permalink();
    $imageurl = $feed->get_image_url();
    $rssurl = $feed->subscribe_url();

    $host = "localhost";
    $usuario = "root";
    $contrasena = "";
    $db = "lector rss";

    $conn = new mysqli($host, $usuario, $contrasena, $db);

    if ($conn->connect_error) {
        $connError = $conn->connect_error;
        $conn->close();
        header("Content-Type: application/json");
        echo json_encode("Error de conexión: " . $connError);
    }

    $sql = "INSERT INTO feeds (titulo, descripcion, url, imageurl, rssurl)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), descripcion = VALUES(descripcion),
    imageurl = VALUES(imageurl), rssurl = VALUES(rssurl);";

    $resultado = $conn->prepare($sql);
    $resultado->bind_param("sssss", $titulo, $descripcion, $url, $imageurl, $rssurl);
    
    if (!$resultado->execute()) {
        $respuesta = ["mensaje" => "Error en la consulta: " . $conn->error];
    } else {
        $respuesta = ["mensaje" => "Se insertó el feed correctamente"];
    }

    $conn->close();

    header("Content-Type: application/json");
    echo json_encode($respuesta);
} else {
    echo json_encode(["mensaje" => "Error: Método no permitido"]);
}
?>