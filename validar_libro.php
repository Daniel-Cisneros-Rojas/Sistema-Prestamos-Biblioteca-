<?php
session_start();
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
     header("Location: iniciar_sesion.php");
       exit();
  }

include_once 'configuracion.php';
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}
    // Obtener id usuario
    $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE user = ?");
    $stmt->bind_param("s", $_SESSION['usuario']);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $row = $resultado->fetch_assoc();
    $id_usuario = $row['id'];
    $stmt->close();

    if(isset($_POST['titulo']) && isset($_POST['autor']) && isset($_POST['anio']) && isset($_POST['editorial']) && isset($_FILES['imagen']) && isset($_POST['descripcion'])) {
        $titulo = $_POST['titulo'];
        $autor = $_POST['autor'];
        $anio = $_POST['anio'];
        $editorial = $_POST['editorial'];
        $descripcion = $_POST['descripcion'];
        
        // Manejo de la imagen
        $imagen = $_FILES['imagen']['name'];
        $ruta_imagen = "imagenes/" . basename($imagen);
        
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_imagen)) {
            // Insertar en la base de datos
            $stmt = $mysqli->prepare("INSERT INTO libros (titulo, autor, anio, editorial, imagen, descripcion, id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $titulo, $autor, $anio, $editorial, $ruta_imagen, $descripcion, $id_usuario);
            
            if ($stmt->execute()) {
            $_SESSION['msg_registro'] = ['tipo' => 'success', 'texto' => 'Libro registrado exitosamente.'];
        } else {
            $_SESSION['msg_registro'] = ['tipo' => 'danger', 'texto' => 'Error al registrar el libro: ' . $stmt->error];
        }
        $stmt->close();
    } else {
        $_SESSION['msg_registro'] = ['tipo' => 'danger', 'texto' => 'Error al subir la imagen.'];
    }
} else {
    $_SESSION['msg_registro'] = ['tipo' => 'danger', 'texto' => 'Por favor complete todos los campos.'];
}

header("Location: registrar_libro.php");
exit();

?>
