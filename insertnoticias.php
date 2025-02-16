<?php 
require_once('simplepie-1.5\autoloader.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    $feedsUrl = [];

    foreach ($data as $item) {
        $feedsUrl[] = $item["rssurl"];
    }

    $feeds = new SimplePie();
    $feeds->set_feed_url($feedsUrl);
    $feeds->enable_cache(false); 
    $feeds->init();
    $feeds->handle_content_type(); 

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

    try {
        foreach ($feeds->multifeed_objects as $feed) {
            $urlFeed = $feed->get_permalink();
    
            foreach ($feed->get_items() as $item) {
                $titulo = $item->get_title();
                $fecha = $item->get_date('Y-m-d H:i:s');
                $noticiaUrl = $item->get_permalink();

                if (strlen($item->get_description()) > 300) {
                    $descripcion = substr($item->get_description(),0,300);
                    $descripcion = substr($descripcion, 0, strrpos($descripcion, ' ')) . "...";
                } else {
                    $descripcion = $item->get_description();
                }

                if ($descripcion !== strip_tags($descripcion)) {
                    $descripcion = "";
                }
    
                $sql = "INSERT INTO noticias (titulo, descripcion, fecha, url, urlnoticia)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), descripcion = VALUES(descripcion),
                fecha = VALUES(fecha), url = VALUES(url);";
    
                $resultado = $conn->prepare($sql);
                $resultado->bind_param("sssss", $titulo, $descripcion, $fecha, $urlFeed, $noticiaUrl);
    
                if (!$resultado->execute()) {
                    throw new Exception("Error en la consulta: " . $conn->error);
                }

                $resultado->close();
    
                $itemId = $conn->insert_id;
    
                if ($item->get_categories() != null && $itemId != 0) {
                    $categoryId = [];
    
                    foreach ($item->get_categories() as $category) {
                        $categoryName = $category->get_label();
                        $sql = "INSERT INTO categorias (nombre) VALUES (?)
                        ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);";
                        
                        $resultado = $conn->prepare($sql);
                        $resultado->bind_param("s", $categoryName);
                        if (!$resultado->execute()) {
                            throw new Exception("Error en la consulta: " . $conn->error);
                        }
                        $resultado->close();
                        if ($conn->insert_id == 0) {
                            $sql = "SELECT id FROM categorias WHERE nombre = '$categoryName'; ";
                            $resultado = $conn->query($sql);
                            if (!$resultado) {
                                throw new Exception("Error en la consulta: " . $conn->error);
                            }
                            $fila = $resultado->fetch_assoc();
                            $categoryId[] = $fila["id"];
                        } else {
                            $categoryId[] = $conn->insert_id;
                        }
                    }
    
                    foreach ($categoryId as $catId) {
                        $sql = "INSERT INTO noticias_categorias (noticia_id, categoria_id) VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE noticia_id = VALUES(noticia_id), categoria_id = VALUES(categoria_id);";

                        $resultado = $conn->prepare($sql);
                        $resultado->bind_param("ii", $itemId, $catId);
                        if (!$resultado->execute()) {
                            //throw new Exception("Error en la consulta: " . $conn->error);
                            throw new Exception("Error en la consulta: " . $itemId);
                        }
                        $resultado->close();
                    }
                }
            }
        }

        $respuesta = [
            "mensaje" => "Se insertaron las noticias exitosamente"
        ];
    
        $conn->close();
    
        header("Content-Type: application/json");
        echo json_encode($respuesta);
    } catch (Exception $e) {
        $conn->close();
        header("Content-Type: application/json");
        echo json_encode(["mensaje" => "Error: " . $e->getMessage()]);
    }
} else {
    header("Content-Type: application/json");
    echo json_encode(["error" => "Método no permitido"]);
}
?>