<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'admin') {
    echo "<h1>Acceso Denegado</h1>";
    echo "<p>No tienes permisos para realizar esta acción.</p>";
    exit();
}


require_once './configuracion.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);

$id_libro_borrar = $_GET['id'] ?? null;

if (!$id_libro_borrar) {
    $msg_error = "ID del libro no especificado para eliminar.";
    $_SESSION['msg'] = $msg_error;
    header("Location: admin_libro.php");
    exit();
}

$libro_imag = null;
$libro_existe = false;

$stmt_imagen = $mysqli->prepare("SELECT imagen FROM libros WHERE id = ?");
if ($stmt_imagen) {
    $stmt_imagen->bind_param("i", $id_libro_borrar);
    $stmt_imagen->execute();
    $res_imagen = $stmt_imagen->get_result();

    if ($res_imagen->num_rows === 1) {
        $libro_datos = $res_imagen->fetch_assoc();
        $libro_imag = $libro_datos['imagen'];
        $libro_existe = true;
    }
    $res_imagen->free();
    $stmt_imagen->close();

} else {
     $msg_error = "Error interno al obtener la ruta de la imagen.";
     $_SESSION['msg'] = $msg_error;
     header("Location: admin_libro.php");
     exit();
}

if (!$libro_existe) {
     $msg_error = "El libro con el ID especificado no fue encontrado.";
     $_SESSION['msg'] = $msg_error;
     header("Location: admin_libro.php");
     exit();
}


// Checa si el libro esta prestado
$esta_prestado = false;
$stmt_prestamo = $mysqli->prepare("SELECT COUNT(*) AS num_prestamos FROM prestamos WHERE id_libro = ? AND estado = 'prestado'");
if ($stmt_prestamo) {
    $stmt_prestamo->bind_param("i", $id_libro_borrar);
    $stmt_prestamo->execute();
    $res_prestamo = $stmt_prestamo->get_result();
    $datos = $res_prestamo->fetch_assoc();

    if ($datos['num_prestamos'] > 0) {
        $esta_prestado = true;
    }

    $res_prestamo->free();
    $stmt_prestamo->close();

} else {
    $msg_error = "Error interno al verificar el estado del préstamo.";
    $_SESSION['msg'] = $msg_error;
    header("Location: admin_libro.php");
    exit();
}


if ($esta_prestado) {
    $msg_error = "No puedes eliminar este libro porque actualmente está prestado.";
    $_SESSION['msg'] = $msg_error;
    header("Location: admin_libro.php");
    exit();

} else {
    // Libro no prestado

    $stmt_borrar = $mysqli->prepare("DELETE FROM libros WHERE id = ?");
    if ($stmt_borrar) {
        $stmt_borrar->bind_param("i", $id_libro_borrar);

        if ($stmt_borrar->execute()) {

            if ($stmt_borrar->affected_rows > 0) {
                // Borra la imagen del servidor
                if ($libro_imag && file_exists($libro_imag)) {

                     if (strpos($libro_imag, 'no_disponible.png') === false) {
                        @unlink($libro_imag);
                     }
                }

                $msg_exito = "Libro eliminado con éxito por el administrador.";
                $_SESSION['msg'] = $msg_exito;
                header("Location: admin_libro.php");
                exit();
            } else {
                 $msg_error = "El libro no se pudo eliminar o no fue encontrado (quizás ya fue eliminado).";
                 $_SESSION['msg'] = $msg_error;
                 header("Location: admin_libro.php");
                 exit();
            }

        } else {
            $msg_error = "Error al eliminar el libro: " . $stmt_borrar->error;
            $_SESSION['msg'] = $msg_error;
            header("Location: admin_libro.php");
            exit();
        }

        $stmt_borrar->close();

    } else {
         $msg_error = "Error interno al preparar la eliminación.";
         $_SESSION['msg'] = $msg_error;
         header("Location: admin_libro.php");
         exit();
    }
}

if (isset($mysqli) && $mysqli) {
    $mysqli->close();
}

?>