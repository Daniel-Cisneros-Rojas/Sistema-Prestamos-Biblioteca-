<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'admin') {
    $_SESSION['msg'] = "Acceso denegado: no tienes permisos para ver esta sección.";
    header("Location: index.php");
    exit();
}


require_once './configuracion.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);

$libro = null;
$msg_error = '';
$msg_exito = '';
$prestamos_activos = [];

function revisar_prestamos($mysqli, $libro_id, &$prestamos_activos) {
    $stmt_prestamos = $mysqli->prepare("SELECT p.id, p.fin_prestamo, p.id_usuario, u.user 
        FROM prestamos p 
        JOIN usuarios u ON p.id_usuario = u.id 
        WHERE p.id_libro = ? AND p.estado = 'prestado'");
    $stmt_prestamos->bind_param("i", $libro_id);
    $stmt_prestamos->execute();
    $res = $stmt_prestamos->get_result();
    
    while ($prestamo = $res->fetch_assoc()) {
        $prestamos_activos[] = $prestamo;
    }
    
    $res->free();
    $stmt_prestamos->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $libro_id = $_POST['libro_id'] ?? null;

    if (!$libro_id) {
        $msg_error = "ID del libro no proporcionado para la actualización.";
        $_SESSION['msg'] = $msg_error;
        header("Location: admin_libro.php");
        exit();
    } else {
        $titulo = $mysqli->real_escape_string($_POST['titulo'] ?? '');
        $autor = $mysqli->real_escape_string($_POST['autor'] ?? '');
        $anio = $mysqli->real_escape_string($_POST['anio'] ?? '');
        $editorial = $mysqli->real_escape_string($_POST['editorial'] ?? '');
        $descripcion = $mysqli->real_escape_string($_POST['descripcion'] ?? '');
        $estado = $mysqli->real_escape_string($_POST['estado'] ?? '');

        revisar_prestamos($mysqli, $libro_id, $prestamos_activos);

        if ($estado === 'disponible' && !empty($prestamos_activos)) {
            $stmt_upd_prestamo = $mysqli->prepare("UPDATE prestamos SET estado = 'devuelto' WHERE id = ?");
            foreach ($prestamos_activos as $prestamo) {
                $stmt_upd_prestamo->bind_param("i", $prestamo['id']);
                if (!$stmt_upd_prestamo->execute()) {
                    $msg_error = "Error al actualizar el estado del préstamo ID {$prestamo['id']}: " . $stmt_upd_prestamo->error;
                    break;
                }
            }
            $stmt_upd_prestamo->close();
        }

        // Si no hay errores
        if (empty($msg_error)) {
            
            $stmt_elegido = $mysqli->prepare("SELECT imagen FROM libros WHERE id = ?");
            $stmt_elegido->bind_param("i", $libro_id);
            $stmt_elegido->execute();
            $res_elegido = $stmt_elegido->get_result();
            $elegido_datos = $res_elegido->fetch_assoc();
            $elegido_imagen = $elegido_datos['imagen'] ?? null;
            $stmt_elegido->close();

            
            $nueva_img = $elegido_imagen;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $file_tmp_path = $_FILES['imagen']['tmp_name'];
                $file_name = $_FILES['imagen']['name'];
                $file_size = $_FILES['imagen']['size'];
                $file_type = $_FILES['imagen']['type'];
                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                $tipos = ['jpg', 'jpeg', 'png', 'gif'];
                $upload_dir = './imagenes/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                if (!is_writable($upload_dir)) {
                    $msg_error = "El directorio de subida de imágenes no tiene permisos de escritura.";
                    $_SESSION['msg'] = $msg_error;
                    header("Location: admin_libro.php");
                    exit();
                } else {
                    if (in_array($file_extension, $tipos) && $file_size <= 4000000) {
                        $new_file_name = uniqid('', true) . '.' . $file_extension;
                        $dest_path = $upload_dir . $new_file_name;
                        if (move_uploaded_file($file_tmp_path, $dest_path)) {
                            $nueva_img = $dest_path;
                            if ($elegido_imagen && $elegido_imagen !== $nueva_img && file_exists($elegido_imagen)) {
                                if (strpos($elegido_imagen, 'no_disponible.png') === false) {
                                    unlink($elegido_imagen);
                                }
                            }
                        } else {
                            $msg_error = "Hubo un error al subir la imagen.";
                            $_SESSION['msg'] = $msg_error;
                            header("Location: admin_libro.php");
                            exit();
                        }
                    } else {
                        $msg_error = "Formato o tamaño de imagen no válido (permite JPG, PNG, GIF, máx 4MB).";
                        $_SESSION['msg'] = $msg_error;
                        header("Location: admin_libro.php");
                        exit();
                    }
                }
            } elseif (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
                $msg_error = "Error en la subida de archivo: Código " . $_FILES['imagen']['error'];
                $_SESSION['msg'] = $msg_error;
                header("Location: admin_libro.php");
                exit();
            }

            // Actualizar libro
            if (empty($msg_error)) {
                $stmt_update = $mysqli->prepare("UPDATE libros SET titulo = ?, autor = ?, anio = ?, editorial = ?, descripcion = ?, imagen = ?, estado = ? WHERE id = ?");
                $stmt_update->bind_param("sssssssi", $titulo, $autor, $anio, $editorial, $descripcion, $nueva_img, $estado, $libro_id);

                if ($stmt_update->execute()) {
                    $msg_exito = "Libro actualizado con éxito.";
                    $_SESSION['msg'] = $msg_exito;
                    header("Location: admin_libro.php");
                    exit();
                } else {
                    $msg_error = "Error al actualizar el libro: " . $stmt_update->error;
                    $_SESSION['msg'] = $msg_error;
                    header("Location: admin_libro.php");
                    exit();
                }
                $stmt_update->close();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' || !empty($msg_error)) {
    $libro_id_to_edit = $_GET['id'] ?? null;

    if (!$libro_id_to_edit && empty($msg_error)) {
        $msg_error = "ID del libro no especificado.";
        $_SESSION['msg'] = $msg_error;
        header("Location: admin_libro.php");
        exit();
    } elseif ($libro_id_to_edit) {
        $stmt_fetch = $mysqli->prepare("SELECT * FROM libros WHERE id = ?");
        $stmt_fetch->bind_param("i", $libro_id_to_edit);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();

        if ($result_fetch->num_rows === 1) {
            $libro = $result_fetch->fetch_assoc();
            revisar_prestamos($mysqli, $libro_id_to_edit, $prestamos_activos);
        } else {
            $msg_error = "Libro no encontrado.";
            $_SESSION['msg'] = $msg_error;
            header("Location: admin_libro.php");
            exit();
        }
        $stmt_fetch->close();
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html>

<head>

<style>
  .table tbody td {
    color: #000 !important;
  }
  .table tbody td::selection {
    color: #000;
    background: #b3d7ff;
  }
</style>
<style>
        img {
            display: block;
            width: 150px;
            height: 250px;
            margin: 0 auto;
        }

    </style>
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

  <title> Admin Editar libro </title>

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
			  
              <li class="nav-item active"><a class="nav-link" href="admin_libro.php">Volver a la lista de libros</a></li>
              
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
       <?php if ($libro): ?>
<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-10">
      <h2 class="text-center mb-4">
        <?php echo 'Editar Libro: ' . htmlspecialchars($libro['titulo']); ?>
      </h2>

      <?php if (!empty($msg_error)): ?>
        <div class="alert alert-danger" role="alert">
          <?php echo htmlspecialchars($msg_error); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($msg_exito)): ?>
        <div class="alert alert-success" role="alert">
          <?php echo htmlspecialchars($msg_exito); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($prestamos_activos)): ?>
        <div class="alert alert-warning">
          <strong>Advertencia:</strong> Este libro tiene <?php echo count($prestamos_activos); ?> préstamo(s) activo(s). Cambiar el estado a 'disponible' marcará estos préstamos como devueltos.
          <ul>
            <?php foreach ($prestamos_activos as $prestamo): ?>
              <li>Préstamo ID <?php echo $prestamo['id']; ?> por usuario <?php echo htmlspecialchars($prestamo['user']); ?> (ID <?php echo $prestamo['id_usuario']; ?>), vence el <?php echo htmlspecialchars($prestamo['fin_prestamo']); ?>.</li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form action="admin_editar_libro.php" method="post" enctype="multipart/form-data" class="p-4 border rounded bg-light">
        <input type="hidden" name="libro_id" value="<?php echo htmlspecialchars($libro['id']); ?>">

        <div class="mb-3">
          <label for="titulo" class="form-label">Título</label>
          <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($libro['titulo']); ?>" required>
        </div>

        <div class="mb-3">
          <label for="autor" class="form-label">Autor</label>
          <input type="text" class="form-control" id="autor" name="autor" value="<?php echo htmlspecialchars($libro['autor']); ?>" required>
        </div>

        <div class="mb-3">
          <label for="anio" class="form-label">Año de Publicación</label>
          <input type="date" class="form-control" id="anio" name="anio" value="<?php echo htmlspecialchars($libro['anio']); ?>" required>
        </div>

        <div class="mb-3">
          <label for="editorial" class="form-label">Editorial</label>
          <input type="text" class="form-control" id="editorial" name="editorial" value="<?php echo htmlspecialchars($libro['editorial']); ?>" required>
        </div>

        <div class="mb-3">
          <label for="descripcion" class="form-label">Descripción</label>
          <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required><?php echo htmlspecialchars($libro['descripcion']); ?></textarea>
        </div>

        <div class="mb-3">
          <label for="estado" class="form-label">Estado</label>
          <select class="form-select" id="estado" name="estado" required>
            <option value="disponible" <?php if ($libro['estado'] === 'disponible') echo 'selected'; ?>>Disponible</option>
            <option value="prestado" <?php if ($libro['estado'] === 'prestado') echo 'selected'; ?>>Prestado</option>
          </select>
        </div>

        <div class="mb-3">
          <label for="imagen" class="form-label">Imagen de Portada</label>
          <?php if (!empty($libro['imagen'])): ?>
            <div class="mb-2">
              <p>Imagen actual:</p>
              <img src="<?php echo htmlspecialchars($libro['imagen']); ?>" alt="Portada Actual" class="img-thumbnail" width="150">
            </div>
          <?php endif; ?>
          <input class="form-control" type="file" id="imagen" name="imagen" accept="image/*">
        </div>

        <p class="fw-bold">Propietario (ID): <?php echo htmlspecialchars($libro['id_usuario']); ?></p>

        <button type="submit" class="btn btn-primary">Actualizar Libro</button>
        <a href="admin_libro.php" class="btn btn-secondary ms-2">Cancelar</a>
      </form>
    </div>
  </div>
</div>
<?php else: ?>
  <div class="container mt-5">
    <div class="alert alert-danger" role="alert">
      No se pudo cargar la información del libro para editar.
    </div>
  </div>
<?php endif; ?>


        </div>
			
          </div>
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