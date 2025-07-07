<?php
session_start();
include_once 'configuracion.php';
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: ver_libros.php");
    exit();
}

if(isset($_POST['id_libro']) && isset($_POST['dias'])) {
    //recibir datos
    $id_libro = $_POST['id_libro'];
    $dias = $_POST['dias'];
    $fecha_inicio=date("Y-m-d");
    $fecha_fin = date('Y-m-d', strtotime($fecha_inicio . ' + ' . $dias . ' days'));
    //buscar id cliente
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
    if ($mysqli->connect_error) {
        $_SESSION['mensaje_prestamo'] = ["type" => "danger", "text" => "Error de conexión: " . $mysqli->connect_error];
        header("Location: solicitar_prestamo.php?id=$id_libro");
        exit();
    }
    $consulta = "SELECT id FROM usuarios WHERE user = ?";
    $stmt = $mysqli->prepare($consulta);
    $stmt->bind_param("s", $_SESSION['usuario']);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
        $id_cliente = $usuario['id'];
    } else {
        $_SESSION['mensaje_prestamo'] = ["type" => "danger", "text" => "No se encontró el usuario."];
        header("Location: solicitar_prestamo.php?id=$id_libro");
        exit();
    }
        
    // Insertar el préstamo en la tabla prestamos
    $consulta = "INSERT INTO prestamos (id_libro, id_usuario, inicio_prestamo, fin_prestamo) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($consulta);
    $stmt->bind_param("iiss", $id_libro, $id_cliente, $fecha_inicio, $fecha_fin);
    if ($stmt->execute()) {
        // Actualizar el estado del libro a 'no_disponible'
        $consulta = "UPDATE libros SET estado = 'prestado' WHERE id = ?";
        $stmt = $mysqli->prepare($consulta);
        $stmt->bind_param("i", $id_libro);
        if ($stmt->execute()) {
            $_SESSION['msg_prestamo'] = ["tipo" => "success", "texto" => "Préstamo registrado con éxito."];
            header("Location: prestamos.php");
            exit();
            
        } else {
            $_SESSION['mensaje_prestamo'] = ["type" => "danger", "text" => "Error al actualizar el estado del libro: " . $stmt->error];
            header("Location: solicitar_prestamo.php?id=$id_libro");
            exit();
        }
    } else {
        $_SESSION['mensaje_prestamo'] = ["type" => "danger", "text" => "Error al registrar el préstamo: " . $stmt->error];
        header("Location: solicitar_prestamo.php?id=$id_libro");
        exit();
    }

    

} else {
    $_SESSION['mensaje_prestamo'] = ["type" => "danger", "text" => "No se ha proporcionado un ID de libro."];
    header("Location: ver_libros.php");
    exit();
}

?>