<?php 
require_once('simplepie-1.5\autoloader.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //Aqui se obtiene el json que el javascript le mando al php
    //Cuando hizo el fetch
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    $feedUrl = $data["url"]; //Se obtiene el url del objeto json

    $feed = new SimplePie(); //Se inicia objeto simplepie
    $feed->set_feed_url($feedUrl);//Se asigna el url del feed que se parseara
    $feed->enable_cache(false); //Esto es para evitar errores
    $feed->init();//Se inicializa simplepie
    $feed->handle_content_type(); //Para que lo obtenido sea compatible con contenido html o algo asi

    $titulo = $feed->get_title();//Se obtiene el titulo del feed
    $descripcion = $feed->get_description(); //Se obtiene la descripcion del feed
    $url = $feed->get_permalink();//Se obtiene el link de la página que hostea el link
    $imageurl = $feed->get_image_url();//Se obtiene un link que hace referencia a una imagen del feed
    $rssurl = $feed->subscribe_url();//Se obtiene el link del rss, basicamente el que mandaste por javascript

    //Comienza el proceso de inicializar conexión con la db
    $host = "localhost";
    $usuario = "root";
    $contrasena = "";
    $db = "lector rss";

    $conn = new mysqli($host, $usuario, $contrasena, $db);

    //Si no se logra establecer conexión con la db se disparará este error
    if ($conn->connect_error) {
        $connError = $conn->connect_error;
        $conn->close();
        header("Content-Type: application/json");
        echo json_encode(["mensaje" => "Error de conexión: " . $connError]);
    }

    //Sentencia sql para insertar los feeds
    $sql = "INSERT INTO feeds (titulo, descripcion, url, imageurl, rssurl)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), descripcion = VALUES(descripcion),
    imageurl = VALUES(imageurl), rssurl = VALUES(rssurl);";

    $resultado = $conn->prepare($sql);
    //Esto es para que a los ? se les asigne una variable, las s significa que son strings
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