<?php 
require_once('simplepie-1.5\autoloader.php');

//Este php simplemente se conecta a la db, y obtiene los feeds almacenados ahi

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

    $sql = "SELECT * FROM feeds";

    $resultado = $conn->query($sql);

    if (!$resultado) {
        die("Error en la consulta: " . $conn->error);
    }

    $datos = [];

    //Este IF verifica que la db nos haya devuelto los feeds,
    //en caso de que no haya devuelto nada (Probablemente porque no hay ningun feed en la db)
    //Se mandará un mensaje de error
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