<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['usuario'])) {
    header("Location: ./iniciar_sesion.php");
    exit();
}

require_once './configuracion.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);

$usuario_logeado = $_SESSION['usuario'];
$id_logeado = null;

$stmt_idusr = $mysqli->prepare("SELECT id FROM usuarios WHERE user = ?");
if ($stmt_idusr) {
    $stmt_idusr->bind_param("s", $usuario_logeado);
    $stmt_idusr->execute();
    $res_idusr = $stmt_idusr->get_result();

    if ($res_idusr->num_rows === 1) {
        $datosusr = $res_idusr->fetch_assoc();
        $id_logeado = $datosusr['id'];
    }

    $res_idusr->free();
    $stmt_idusr->close();

} else {
    $_SESSION['msg'] = "Error interno al verificar usuario.";
    header("Location: usr_libro.php");
    exit();
}

if (is_null($id_logeado)) {
    $_SESSION['msg'] = "Error de autenticación. Por favor, inicia sesión de nuevo.";
     header("Location: ./iniciar_sesion.php");
    exit();
}


$id_libro_borrar = $_GET['id'] ?? null;

if (!$id_libro_borrar) {
    $_SESSION['msg'] = "ID del libro no especificado para eliminar.";
    header("Location: usr_libro.php");
    exit();
}

$libro_imag = null;
$es_duenio = false;

$stmt_duenio = $mysqli->prepare("SELECT id_usuario, imagen FROM libros WHERE id = ?");
if ($stmt_duenio) {
    $stmt_duenio->bind_param("i", $id_libro_borrar);
    $stmt_duenio->execute();
    $res_duenio = $stmt_duenio->get_result();

    if ($res_duenio->num_rows === 1) {
        $libro_datos = $res_duenio->fetch_assoc();
        if ($libro_datos['id_usuario'] == $id_logeado) {
            $es_duenio = true;
            $libro_imag = $libro_datos['imagen'];
        }
    }
    $res_duenio->free();
    $stmt_duenio->close();

} else {
     $_SESSION['msg'] = "Error interno al verificar la propiedad del libro.";
     header("Location: usr_libro.php");
     exit();
}

if (!$es_duenio) {
    $_SESSION['msg'] = "No tienes permisos para eliminar este libro.";
    header("Location: usr_libro.php");
    exit();
}

// Checa si el libro esta prestado
$esta_prestado = false;
$stmt_prestamo = $mysqli->prepare("SELECT COUNT(*) AS loan_count FROM prestamos WHERE id_libro = ? AND estado = 'prestado'");
if ($stmt_prestamo) {
    $stmt_prestamo->bind_param("i", $id_libro_borrar);
    $stmt_prestamo->execute();
    $res_prestamo = $stmt_prestamo->get_result();
    $datos = $res_prestamo->fetch_assoc();

    if ($datos['loan_count'] > 0) {
        $esta_prestado = true;
    }

    $res_prestamo->free();
    $stmt_prestamo->close();

} else {
    $_SESSION['msg'] = "Error interno al verificar el estado del préstamo.";
    header("Location: usr_libro.php");
    exit();
}


if ($esta_prestado) {
    $_SESSION['msg'] = "No puedes eliminar este libro porque actualmente está prestado.";
    header("Location: usr_libro.php");
    exit();

} else {
    // Libro no prestado

    $stmt_borrar = $mysqli->prepare("DELETE FROM libros WHERE id = ? AND id_usuario = ?");
    if ($stmt_borrar) {
        $stmt_borrar->bind_param("ii", $id_libro_borrar, $id_logeado);

        if ($stmt_borrar->execute()) {
            if ($stmt_borrar->affected_rows > 0) {
                // Borrar la imagen del servidor
                if ($libro_imag && file_exists($libro_imag)) {
                     
                     if (strpos($libro_imag, 'no_disponible.png') === false) { 
                        @unlink($libro_imag); 
                     }
                }

                $_SESSION['msg'] = "Libro eliminado con éxito.";
                header("Location: usr_libro.php");
                exit();
            } else {
                 $_SESSION['msg'] = "El libro no se pudo eliminar o no fue encontrado (quizás ya fue eliminado).";

                 header("Location: usr_libro.php");
                 exit();
            }

        } else {
            $_SESSION['msg'] = "Error al eliminar el libro: " . $stmt_borrar->error;
            header("Location: usr_libro.php");
            exit();
        }

        $stmt_borrar->close();

    } else {
         $_SESSION['msg'] = "Error interno al preparar la eliminación.";
         header("Location: usr_libro.php");
         exit();
    }
}

if (isset($mysqli) && $mysqli) {
    $mysqli->close();
}

?>