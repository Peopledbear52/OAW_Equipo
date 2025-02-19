<?php
/*
include 'conexion.php';

$busqueda = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';
$sql = "SELECT * FROM arbol_descripcion";//modificar

if (!empty($search)) {
    $sql .= " WHERE nombre_cientifico LIKE '%$search%' OR nombre_comun LIKE '%$search%'";//modificar
}

//$sql = "SELECT * FROM productos WHERE nombre LIKE '%$busqueda%' LIMIT 10";
//$result = $conn->query($sql);



$conn->close();
*/

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

    // Obtener el orden
    $order = isset($_GET['order']) ? $_GET['order'] : 'fecha'; // Orden predeterminado: fecha

    // Lista de columnas permitidas para evitar SQL Injection
    $allowed_orders = ['titulo', 'fecha', 'descripcion', 'categorias'];

    // Verifica si el parámetro "order" es válido, si no, usa "fecha" como predeterminado
    if (!in_array($order, $allowed_orders)) {
        $order = 'fecha';
    }

    $search = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';
    $sql = "SELECT n.titulo, n.fecha, n.descripcion, n.urlnoticia, f.titulo AS feed_nombre, GROUP_CONCAT(c.nombre SEPARATOR '|') AS categorias 
    FROM noticias n JOIN feeds f ON n.url = f.url 
    LEFT JOIN noticias_categorias nc ON n.id = nc.noticia_id 
    LEFT JOIN Categorias c ON nc.categoria_id = c.id 
    GROUP BY n.id, n.titulo, n.fecha, n.descripcion, n.urlnoticia, f.titulo
    ORDER BY $order ASC";
    
    if (!empty($search)) {
        $sql ="";
        $sql = "SELECT n.titulo, n.fecha, n.descripcion, n.urlnoticia, 
               f.titulo AS feed_nombre, 
               GROUP_CONCAT(c.nombre SEPARATOR '|') AS categorias 
        FROM noticias n 
        JOIN feeds f ON n.url = f.url 
        LEFT JOIN noticias_categorias nc ON n.id = nc.noticia_id 
        LEFT JOIN Categorias c ON nc.categoria_id = c.id 
        WHERE n.titulo LIKE '%$search%' 
           OR n.descripcion LIKE '%$search%' 
           OR n.fecha LIKE '%$search%' 
           OR c.nombre LIKE '%$search%'
        GROUP BY n.id, n.titulo, n.fecha, n.descripcion, n.urlnoticia, f.titulo
        ORDER BY $order ASC";
    }

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
        $datos = [];
    }

    $conn->close();

    header("Content-Type: application/json");
    echo json_encode($datos);
} else {
    echo json_encode(["mensaje" => "Error: Método no permitido"]);
}
?>