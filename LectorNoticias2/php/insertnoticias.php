<?php 
require_once('simplepie-1.5\autoloader.php');

/*Este PHP se encargará de obtener todas las noticias de los feeds que se le pasaron a simplepie
con la información obtenida de simplepie se subiran las noticias a la tabla noticias en la db
Sus categorias seran asignadas a la tabla categorias en la db, si una categoria ya esta en la db
no pasará nada
Una vez las noticias y las categorias esten en la db, se actualizara la tabla noticias_categorias
La cual relaciona cada una de las noticias con las categorias a las que pertenece */

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    //Una vez obtenido el json pasado por fetch, se recorrerá con foreach, y los rssurl serán
    //Asignados al array feedsUrl
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
        //Aqui se van a ir recorriendo cada uno de los feeds escaneados por simplepie
        //Cada noticia del feed en cuestion será insertada en la db, incluyendo sus categorias
        foreach ($feeds->multifeed_objects as $feed) {
            //Se obtiene el url de la página que hostea el feed, funciona como llave foranéa para las noticias
            $urlFeed = $feed->get_permalink(); 
            
            //Aqui cada una de las noticias obtenidas del feed serán subidas insertadas a la db
            foreach ($feed->get_items() as $item) {
                //Se obtiene información de la noticia
                $titulo = $item->get_title();
                $fecha = $item->get_date('Y-m-d H:i:s');
                $noticiaUrl = $item->get_permalink();

                //Aqui se hacen validaciones para verificar que la descripcion de la noticia
                //No se pase de 300 carácteres, si lo hace, se corta y se añade ... al final
                if (strlen($item->get_description()) > 300) {
                    $descripcion = substr($item->get_description(),0,300);
                    $descripcion = substr($descripcion, 0, strrpos($descripcion, ' ')) . "...";
                } else {
                    $descripcion = $item->get_description();
                }

                //En el caso de que la descripción por alguna razón contenga tags html (Como la página Xataka)
                //La descripción tendrá la misma información que el titulo
                //Esto es debido a que incluso si las tags html se quitan, la descripción que queda es ilegible
                if ($descripcion !== strip_tags($descripcion)) {
                    $descripcion = $titulo;
                }
                
                //Aqui se prepara la consulta sql y se ejecuta, insertando las noticias
                //Para mas información sobre como funciona el prepare y el bind_param,
                //Leer los comentarios en insertfeed.php
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
                
                //Se obtiene la id del item insertado, necesario para las asociaciones que se creará
                //En la tabla noticias_categorias
                $itemId = $conn->insert_id; 
                

                /*Si la noticia tiene categorias, se ejecutara este código, que inserta las categorias en su determinada
                tabla en la db, y luego insertara las relaciones entre la noticia y sus categorias en la db
                El $itemId != 0 es para verificar que la noticia sea una nueva noticia siendo insertada a la db
                Lo que pasa es que si en el código de arriba se intento insertar una noticia que ya está en la db,
                pues el insert no ocurre, gracias al on duplicate key update, y entonces el $conn->insert_id devolverá 0,
                Cuando devuelve 0 podemos asumir que la noticia fue inserta en la db en otra ocasión y se puede
                asumir que sus categorias y asociaciones ya fueron subidas a la db anteriormente
                Por lo que este código no se tiene que ejecutar
                
                tldr: Si la noticia tiene categorias se ejecuta esto
                Pero si la noticia ya estaba en la db desde antes de ejecutar este php
                Pues no se tiene que hacer esto, pues sus categorias ya fueron asignadas*/
                if ($item->get_categories() != null && $itemId != 0) {
                    $categoryId = [];
                    
                    /*Aqui se recorrera cada categoria que tiene la noticia
                    en cada iteración la categoria será subida a la db y su id será guardada
                    en el array categoryId para su uso en la tabla relación noticias_categorias */
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
                            //Este código se ejecuta cuando el id insertado es igual a 0
                            //Esto significa que la categoria ya estaba en la db antes y por ende ninguna inserción se realizo
                            //ya que NECESITAMOS la id para la relación noticias_categorias, tendremos que obtenerla con otra sentencia
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
                    
                    //Este foreach recorre cada id que se encuentra en el array categoryId
                    foreach ($categoryId as $catId) {
                        //En cada iteración se insertará una nueva relación entre una noticia y una categoria
                        //a la tabla noticias_categorias
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
    echo json_encode(["mensaje" => "Error: Método no permitido"]);
}
?>