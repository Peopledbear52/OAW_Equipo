<?php
require_once('simplepie-1.5\autoloader.php');

if ($_SERVER["REQUEST_METHOD"] == "GET") {
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

    /*Esta sentencia sql obtiene cada una de las noticias guardadas en la db, juento al nombre del feed a que pertencen 
    y las categorias a las que estan asociadas, si no hay categorias, seran mostradas como null
    si hay más de una categorias, estás estaran separada por el separador '|', por lo que si quieren obtener las categorias
    de una determinada noticia por separado, tendran que crear un array separando el string por el separador '|' */
    $sql = "SELECT n.titulo, n.fecha, n.descripcion, n.urlnoticia, f.titulo AS feed_nombre, GROUP_CONCAT(c.nombre SEPARATOR '|') AS categorias 
    FROM noticias n JOIN feeds f ON n.url = f.url 
    LEFT JOIN noticias_categorias nc ON n.id = nc.noticia_id 
    LEFT JOIN Categorias c ON nc.categoria_id = c.id 
    GROUP BY n.id, n.titulo, n.fecha, n.descripcion, n.urlnoticia, f.titulo;";

    $resultado = $conn->query($sql);

    if (!$resultado) {
        die("Error en la consulta: " . $conn->error);
    }

    $datos = [];

    if ($resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }
    } else {
        $datos = ["mensaje" => "Error: No se encontraron registros"];
    }

    $conn->close();

    header("Content-Type: application/json");
    echo json_encode($datos);
} else {
    echo json_encode(["mensaje" => "Error: Método no permitido"]);
}
?>