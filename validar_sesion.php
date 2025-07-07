<?php
include_once 'configuracion.php';
session_start();

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

if (isset($_POST['usuario']) && isset($_POST['contrasena'])) {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];

    // Consulta para verificar el usuario y la contraseña
    $stmt = $mysqli->prepare("SELECT id, user, nombre, correo, tipo FROM usuarios WHERE user = ? AND contrasena = ?");
    $stmt->bind_param("ss", $usuario, $contrasena);
    $stmt->execute();
    $resultado = $stmt->get_result();


    if ($resultado->num_rows > 0) {
        $fila_usuario = $resultado->fetch_assoc();

        $_SESSION['autenticado'] = true;
        $_SESSION['usuario'] = $fila_usuario['user'];
        $_SESSION['nombre'] = $fila_usuario['nombre'];
        $_SESSION['correo'] = $fila_usuario['correo'];
        $_SESSION['tipo'] = $fila_usuario['tipo'];

        header("Location: index.php");
        exit();

    } else {
        $_SESSION['error_login'] = "Usuario o contraseña incorrectos.";
    	header("Location: iniciar_sesion.php");
    	exit();
		
    }

    $stmt->close();
} else {
    $_SESSION['error_login'] = "Por favor, complete todos los campos.";
    header("Location: iniciar_sesion.php");
    exit();
}

?>