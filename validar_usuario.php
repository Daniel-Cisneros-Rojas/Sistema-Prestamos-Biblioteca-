<?php
session_start();

try{
    if (isset($_POST['usuario']) && isset($_POST['contrasena']) && isset($_POST['nombre']) && isset($_POST['correo'])) {
        $usuario = $_POST['usuario'];
        $contrasena = $_POST['contrasena'];
        $nombre = $_POST['nombre'];
        $correo = $_POST['correo'];
        $tipo = 'user'; 
    
    include_once 'configuracion.php';   
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
    if ($mysqli->connect_error) {
        die("Error de conexión: " . $mysqli->connect_error);
    }
    
    $stmt = $mysqli->prepare("SELECT * FROM usuarios WHERE user = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        $_SESSION['error_registro_usuario'] = "El usuario ya existe. Por favor elige otro.";
        header("Location: registrar_usuario.php");
		exit();
    } else {
        // Insertar el nuevo usuario

        $stmt = $mysqli->prepare("INSERT INTO usuarios (user, contrasena, nombre, correo, tipo) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $usuario, $contrasena, $nombre, $correo, $tipo);
        
        if ($stmt->execute()) {
            
            $_SESSION['autenticado'] = true;
            $_SESSION['usuario'] = $usuario;
            $_SESSION['tipo'] = $tipo;
            $_SESSION['nombre'] = $nombre;
            $_SESSION['correo'] = $correo;
            header("Location: index.php");
            exit();
            
        } else {
            $_SESSION['error_registro_usuario'] = "Error al registrar el usuario. Por favor, inténtelo más tarde.";
			header("Location: registrar_usuario.php");
			exit();
        }
        $stmt->close();
    }
    }
}catch(Exception $e){
    $_SESSION['error_registro_usuario'] = "Error: " . $e->getMessage();
    header("Location: registrar_usuario.php");
    exit();
}
?>