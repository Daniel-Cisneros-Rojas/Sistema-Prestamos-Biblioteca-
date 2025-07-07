
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

$stmt_usuario_id = $mysqli->prepare("SELECT id FROM usuarios WHERE user = ?");
if ($stmt_usuario_id) {
    $stmt_usuario_id->bind_param("s", $usuario_logeado);
    $stmt_usuario_id->execute();
    $res_usuario_id = $stmt_usuario_id->get_result();

    if ($res_usuario_id->num_rows === 1) {
        $datos_usuario = $res_usuario_id->fetch_assoc();
        $id_logeado = $datos_usuario['id'];
    }

    $res_usuario_id->free();
    $stmt_usuario_id->close();
} else {
    die("Error al preparar la consulta para obtener ID de usuario: " . $mysqli->error);
}

if (is_null($id_logeado)) {
    echo "Error interno: No se pudo verificar la identidad del usuario.";
    exit();
}

$libro = null;
$msg_error = '';
$msg_exito = '';
$elegido_id = null;
$tiene_prest_activo = false;
$prest_id = null;


function revisar_estado_libro($mysqli, $libro_id, &$tiene_prest_activo, &$prest_id) {
    $stmt_prestamo = $mysqli->prepare("SELECT id, fin_prestamo FROM prestamos WHERE id_libro = ? AND estado = 'prestado'");
    $stmt_prestamo->bind_param("i", $libro_id);
    $stmt_prestamo->execute();
    $res_prestamo = $stmt_prestamo->get_result();
    
    if ($res_prestamo->num_rows > 0) {
        $prestamo = $res_prestamo->fetch_assoc();
        $prest_id = $prestamo['id'];

        if (strtotime($prestamo['fin_prestamo']) >= strtotime(date('Y-m-d'))) {
            $tiene_prest_activo = true;
        }
    }
    
    $res_prestamo->free();
    $stmt_prestamo->close();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $elegido_id = $_POST['id_libro'] ?? null;

    if (!$elegido_id) {
        $msg_error = "ID del libro no proporcionado para la actualización.";
        $_SESSION['msg'] = $msg_error;
        header("Location: usr_libro.php");
        exit();
    } else {
        $stmt_duenio = $mysqli->prepare("SELECT id_usuario FROM libros WHERE id = ?");
        $stmt_duenio->bind_param("i", $elegido_id);
        $stmt_duenio->execute();
        $res_duenio = $stmt_duenio->get_result();

        if ($res_duenio->num_rows === 0) {
            $msg_error = "Hubo un problema.";
            $_SESSION['msg'] = $msg_error;
        header("Location: usr_libro.php");
        exit();
        } else {
            $datos_duenio = $res_duenio->fetch_assoc();
            $id_duenio = $datos_duenio['id_usuario'];

            if ($id_duenio != $id_logeado) {
                $msg_error = "No tienes permisos para editar este libro.";
                $_SESSION['msg'] = $msg_error;
        header("Location: usr_libro.php");
        exit();
            } else {

                revisar_estado_libro($mysqli, $elegido_id, $tiene_prest_activo, $prest_id);

                $titulo = $mysqli->real_escape_string($_POST['titulo'] ?? '');
                $autor = $mysqli->real_escape_string($_POST['autor'] ?? '');
                $anio = $mysqli->real_escape_string($_POST['anio'] ?? '');
                $editorial = $mysqli->real_escape_string($_POST['editorial'] ?? '');
                $descripcion = $mysqli->real_escape_string($_POST['descripcion'] ?? '');

                $stmt_estado = $mysqli->prepare("SELECT estado FROM libros WHERE id = ?");
                $stmt_estado->bind_param("i", $elegido_id);
                $stmt_estado->execute();
                $res_estado = $stmt_estado->get_result();
                $current_estado = $res_estado->fetch_assoc()['estado'] ?? 'prestado';
                $stmt_estado->close();

                $s_estado = $mysqli->real_escape_string($_POST['estado'] ?? '');
                $estado = $tiene_prest_activo ? $current_estado : $s_estado;

                if (!in_array($estado, ['disponible', 'prestado'])) {
                    $msg_error = "Estado inválido proporcionado.";
                    $_SESSION['msg'] = $msg_error;
                    header("Location: usr_libro.php");
                    exit();
                } elseif ($tiene_prest_activo && $s_estado === 'disponible') {
                    $msg_error = "No puedes cambiar el estado a 'disponible' mientras el libro está prestado y no está vencido.";
                    $_SESSION['msg'] = $msg_error;
                    header("Location: usr_libro.php");
                    exit();
                } else {
                    
                    if ($prest_id && $estado === 'disponible') {
                        $stmt_upd_p = $mysqli->prepare("UPDATE prestamos SET estado = 'devuelto', fin_prestamo = NOW() WHERE id = ?");
                        $stmt_upd_p->bind_param("i", $prest_id);
                        if (!$stmt_upd_p->execute()) {
                            $msg_error = "Error al actualizar el estado del préstamo: " . $stmt_upd_p->error;
                            $_SESSION['msg'] = $msg_error;
                            header("Location: usr_libro.php");
                            exit();
                        }
                        $stmt_upd_p->close();
                    }

                    
                    $stmt_imag = $mysqli->prepare("SELECT imagen FROM libros WHERE id = ?");
                    $stmt_imag->bind_param("i", $elegido_id);
                    $stmt_imag->execute();
                    $result_imag = $stmt_imag->get_result();
                    $elegido_datos = $result_imag->fetch_assoc();
                    $img_actual = $elegido_datos['imagen'] ?? null;
                    $stmt_imag->close();

                    // Subida de imagen
                    $nueva_img = $img_actual;
                    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                        $file_tmp_path = $_FILES['imagen']['tmp_name'];
                        $file_name = $_FILES['imagen']['name'];
                        $file_size = $_FILES['imagen']['size'];
                        $file_type = $_FILES['imagen']['type'];
                        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                        $tipos = ['jpg', 'jpeg', 'png', 'gif'];
                        $upload_dir = './imagenes/';

                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        if (!is_writable($upload_dir)) {
                            $msg_error = "El directorio de subida de imágenes no tiene permisos de escritura.";
                            $_SESSION['msg'] = $msg_error;
                            header("Location: usr_libro.php");
                            exit();
                        } else {
                            if (in_array($ext, $tipos) && $file_size <= 4000000) {
                                $nuevo_archivo = uniqid('', true) . '.' . $ext;
                                $dest_path = $upload_dir . $nuevo_archivo;
                                if (move_uploaded_file($file_tmp_path, $dest_path)) {
                                    $nueva_img = $dest_path;
                                    if ($img_actual && $img_actual !== $nueva_img && file_exists($img_actual)) {
                                        if (strpos($img_actual, 'no_disponible.png') === false) {
                                            @unlink($img_actual);
                                        }
                                    }
                                } else {
                                    $msg_error = "Hubo un error al subir la imagen.";
                                    $_SESSION['msg'] = $msg_error;
                                    header("Location: usr_libro.php");
                                    exit();
                                }
                            } else {
                                $msg_error = "Formato o tamaño de imagen no válido (permite JPG, PNG, GIF, máx 4MB).";
                                $_SESSION['msg'] = $msg_error;
                                header("Location: usr_libro.php");
                                exit();
                            }
                        }
                    } elseif (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $msg_error = "Error en la subida de archivo: Código " . $_FILES['imagen']['error'];
                        $_SESSION['msg'] = $msg_error;
                        header("Location: usr_libro.php");
                        exit();
                    }

                    // Actualizar libro
                    if (empty($msg_error)) {
                        $stmt_update = $mysqli->prepare("UPDATE libros SET titulo = ?, autor = ?, anio = ?, editorial = ?, descripcion = ?, imagen = ?, estado = ? WHERE id = ? AND id_usuario = ?");
                        $stmt_update->bind_param("sssssssii", $titulo, $autor, $anio, $editorial, $descripcion, $nueva_img, $estado, $elegido_id, $id_logeado);

                        if ($stmt_update->execute()) {
                            if ($stmt_update->affected_rows > 0) {
                                $msg_exito = "Libro actualizado con éxito.";
                                $_SESSION['msg'] = $msg_exito;
                                header("Location: usr_libro.php");
                                exit();
                            } elseif ($stmt_update->affected_rows === 0) {
                                $msg_info = "No se detectaron cambios en los datos del libro.";
                                $_SESSION['msg'] = $msg_info;
                                header("Location: usr_libro.php");
                                exit();
                            } else {
                                $msg_error = "Ocurrió un error inesperado durante la actualización.";
                                $_SESSION['msg'] = $msg_error;
                                header("Location: usr_libro.php");
                                exit();
                            }
                        } else {
                            $msg_error = "Error al ejecutar la actualización del libro: " . $stmt_update->error;
                            $_SESSION['msg'] = $msg_error;
                            header("Location: usr_libro.php");
                            exit();
                        }
                        $stmt_update->close();
                    }
                }
            }
        }
        $res_duenio->free();
        $stmt_duenio->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' || (!empty($msg_error) && $_SERVER['REQUEST_METHOD'] === 'POST')) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $elegido_id = $_GET['id'] ?? null;
    }

    if (!$elegido_id) {
        $msg_error = "ID del libro no especificado.";
        $_SESSION['msg'] = $msg_error;
        header("Location: usr_libro.php");
        exit();
    } else {
        $stmt_fetch = $mysqli->prepare("SELECT * FROM libros WHERE id = ? AND id_usuario = ?");
        $stmt_fetch->bind_param("ii", $elegido_id, $id_logeado);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();

        if ($result_fetch->num_rows === 1) {
            $libro = $result_fetch->fetch_assoc();
            revisar_estado_libro($mysqli, $elegido_id, $tiene_prest_activo, $prest_id);
        } else {
            $msg_error = "Libro no encontrado o no tienes permisos para editarlo.";
            $_SESSION['msg'] = $msg_error;
            header("Location: usr_libro.php");
            exit();
        }

        $result_fetch->free();
        $stmt_fetch->close();
    }
}

if (isset($mysqli) && $mysqli) {
    $mysqli->close();
}

?>
<!DOCTYPE html>
<html>

<head>
  <!-- Basic -->
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <!-- Mobile Metas -->
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <!-- Site Metas -->
  <meta name="keywords" content="" />
  <meta name="description" content="" />
  <meta name="author" content="" />
  <link rel="shortcut icon" href="imagenes/libroico.png" type="">

  <title> Editar Libro </title>

  <!-- bootstrap core css -->
  <link rel="stylesheet" type="text/css" href="css/bootstrap.css" />

  <!-- fonts style -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">

  <!--owl slider stylesheet -->
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />

  <!-- font awesome style -->
  <link href="css/font-awesome.min.css" rel="stylesheet" />

  <!-- Custom styles for this template -->
  <link href="css/style.css" rel="stylesheet" />
  <!-- responsive style -->
  <link href="css/responsive.css" rel="stylesheet" />


</head>

<body class="sub_page">

  <div class="hero_area">

    <div class="hero_bg_box">
      <div class="bg_img_box">
        <img src="imagenes/hero-bg.png" alt="">
      </div>
    </div>

    <!-- header section strats -->
    <header class="header_section">
      <div class="container-fluid">
        <nav class="navbar navbar-expand-lg custom_nav-container ">
          <a class="navbar-brand" href="index.php">
            <span>
              Biblioteca
            </span>
          </a>

          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class=""> </span>          </button>

          <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav  ">
              <li class="nav-item ">
                <a class="nav-link" href="index.php">Inicio </a>
              </li>
			  
              <li class="nav-item"><a class="nav-link" href="usr_libro.php">Volver a mis libros</a></li>
			  
			   
              
            </ul>
          </div>
        </nav>
      </div>
    </header>
    <!-- end header section -->
  </div>


  <!-- service section -->

  <section class="service_section layout_padding">
    <div class="service_container">
      <div class="container ">
        <div class="heading_container heading_center">
          <h2>
             <span></span>
          </h2>
		  </div>
        <div class="table-responsive">
		<?php
if (!empty($msg_error)) {
    echo "<div class='alert alert-danger'>" . htmlspecialchars($msg_error) . "</div>";
}
if (!empty($msg_exito)) {
    echo "<div class='alert alert-success'>" . htmlspecialchars($msg_exito) . "</div>";
}

if ($libro && empty($msg_error)):
?>
<form action="editar_libro.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="id_libro" value="<?php echo htmlspecialchars($libro['id']); ?>">

    <div class="form-group">
        <label for="titulo">Título:</label>
        <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($libro['titulo']); ?>" required>
    </div>

    <div class="form-group">
        <label for="autor">Autor:</label>
        <input type="text" class="form-control" id="autor" name="autor" value="<?php echo htmlspecialchars($libro['autor']); ?>" required>
    </div>

    <div class="form-group">
        <label for="anio">Año de Publicación:</label>
        <input type="date" class="form-control" id="anio" name="anio" value="<?php echo htmlspecialchars($libro['anio']); ?>" required>
    </div>

    <div class="form-group">
        <label for="editorial">Editorial:</label>
        <input type="text" class="form-control" id="editorial" name="editorial" value="<?php echo htmlspecialchars($libro['editorial']); ?>" required>
    </div>

    <div class="form-group">
        <label for="descripcion">Descripción:</label>
        <textarea class="form-control" id="descripcion" name="descripcion" required><?php echo htmlspecialchars($libro['descripcion']); ?></textarea>
    </div>

    <div class="form-group">
        <label for="estado">Estado:</label>
        <?php if ($tiene_prest_activo): ?>
            <select id="estado" class="form-control" name="estado" disabled>
                <option value="prestado" selected>Prestado</option>
            </select>
            <input type="hidden" name="estado" value="<?php echo htmlspecialchars($libro['estado']); ?>">
            <p class="text-warning mt-2">El libro está prestado. No puedes cambiar el estado.</p>
        <?php else: ?>
            <select id="estado" class="form-control" name="estado" required>
                <option value="disponible" <?php if ($libro['estado'] === 'disponible') echo 'selected'; ?>>Disponible</option>
                <option value="prestado" <?php if ($libro['estado'] === 'prestado') echo 'selected'; ?>>Prestado</option>
            </select>
            <?php if ($prest_id): ?>
                <p class="text-info mt-2">El préstamo está vencido. Cambiar el estado marcará el préstamo como devuelto.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="imagen">Imagen de Portada:</label><br>
        <?php if (!empty($libro['imagen'])): ?>
            <img src="<?php echo htmlspecialchars($libro['imagen']); ?>" alt="Portada" width="120" class="mb-2">
        <?php endif; ?>
        <input type="file" class="form-control-file" id="imagen" name="imagen" accept="image/*">
    </div>

    <p class="mt-2">Propietario (tú): <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong></p>

    <button type="submit" class="btn btn-success">Actualizar Libro</button>
</form>
<?php else: ?>
    <?php if (empty($msg_error)): ?>
            <p>No se pudo cargar la información del libro para editar.</p>
        <?php endif; ?>
<?php endif; ?>
		
        

        
          
		  
		  
		  
        
			
          
        </div>
      </div>
    </div>
  </section>

  <!-- end service section -->

    <!-- info section -->
<section class="info_section layout_padding2">
  <div class="container">
    <div class="row">
      
      <div class="col-md-6 col-lg-4 info_col">
        <div class="info_contact">
          <h4>Contacto</h4>
          <div class="contact_link_box">
            <a href="#">
              <i class="fa fa-map-marker" aria-hidden="true"></i>
              <span>Puebla, México</span>
            </a>
            <a href="#">
              <i class="fa fa-phone" aria-hidden="true"></i>
              <span>+52 123 456 7890</span>
            </a>
            <a href="#">
              <i class="fa fa-envelope" aria-hidden="true"></i>
              <span>bibliotecapersonal@correo.com</span>
            </a>
          </div>
        </div>
        <div class="info_social">
          <a href="#"><i class="fa fa-facebook" aria-hidden="true"></i></a>
          <a href="#"><i class="fa fa-twitter" aria-hidden="true"></i></a>
          <a href="#"><i class="fa fa-instagram" aria-hidden="true"></i></a>
        </div>
      </div>

      <div class="col-md-6 col-lg-8 info_col">
        <div class="info_detail">
          <h4>Acerca de la Biblioteca</h4>
          <p style="text-align: justify;">
            Esta es una biblioteca virtual personal donde puedes gestionar tus libros fácilmente. 
            Puedes registrar libros, actualizarlos, eliminar entradas y compartir tu colección. 
            También tienes acceso a un sistema de préstamos para que usuarios autorizados puedan solicitar tus libros de manera organizada.
          </p>
          <p style="text-align: justify;" class="mt-2">
            Nuestro objetivo es brindar una experiencia amigable y moderna para organizar tu biblioteca desde cualquier lugar.
          </p>
        </div>
      </div>

    </div>
  </div>
</section>
<!-- end info section -->

<!-- footer section -->
<section class="footer_section">
  <div class="container text-center">
    <p>
      &copy; <span id="displayYear"></span> Biblioteca Virtual Personal. Todos los derechos reservados.
    </p>
  </div>
</section>
<!-- end footer section -->

  <!-- jQery -->
  <script type="text/javascript" src="js/jquery-3.4.1.min.js"></script>
  <!-- popper js -->
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous">
  </script>
  <!-- bootstrap js -->
  <script type="text/javascript" src="js/bootstrap.js"></script>
  <!-- owl slider -->
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js">
  </script>
  <!-- custom js -->
  <script type="text/javascript" src="js/custom.js"></script>
  <!-- Google Map -->
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCh39n5U-4IoWpsVGUHWdqB6puEkhRLdmI&callback=myMap">
  </script>
  <!-- End Google Map -->

</body>

</html>