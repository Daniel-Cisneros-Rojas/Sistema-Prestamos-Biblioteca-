<?php
session_start();
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: index.php");
    exit();
}
$id_libro=$_GET['id'];
$id_prestamo=$_GET['id_prestamo'];
if ($id_libro === null) {
    $_SESSION['msg_prestamo'] = [
        "tipo" => "danger",
        "texto" => "No se ha proporcionado un ID de libro."
    ];
    header("Location: prestamos.php");
    exit();
}

include_once 'configuracion.php';   
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
        if ($mysqli->connect_error) {
            $_SESSION['msg_prestamo'] = [
        	"tipo" => "danger",
        	"texto" => "Error de conexión: " . $mysqli->connect_error
    		];
    		header("Location: prestamos.php");
    		exit();
        }
        $consulta="UPDATE libros SET estado = 'disponible' WHERE id = ?";
        $stmt = $mysqli->prepare($consulta);
        $stmt->bind_param("i", $id_libro);
        $stmt->execute();

        try{
            $consulta="UPDATE prestamos SET estado = 'devuelto',fin_prestamo= ? WHERE id= ? ";
            $stmt = $mysqli->prepare($consulta);
            $fecha_actual = date("Y-m-d H:i:s");
            $stmt->bind_param("si", $fecha_actual, $id_prestamo);
            $stmt->execute();

            header("Location: prestamos.php");
        }catch(Exception $e){
            $_SESSION['msg_prestamo'] = [
        	"tipo" => "danger",
        	"texto" => "Error al actualizar el estado del libro: " . $stmt->error
    		];
    		header("Location: prestamos.php");
    		exit();
        }
        

?>