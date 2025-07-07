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

  <title> Prestamos </title>
  <script LANGUAGE="JavaScript">
     function confirmSubmit()
    {
     var eli=confirm("Está seguro de devolver el libro ?");
     if (eli) return true ;
     else return false ;
     }
    </script>

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
<?php
session_start();
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: index.php");
    exit();
}
include_once 'configuracion.php';   
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
        if ($mysqli->connect_error) {
            die("Error de conexión: " . $mysqli->connect_error);
        }
?>

  <div class="hero_area">

    <div class="hero_bg_box">
      <div class="bg_img_box">
        <img src="images/hero-bg.png" alt="">
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
            <span class=""> </span>
          </button>

          <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav  ">
              <li class="nav-item ">
                <a class="nav-link" href="index.php">Inicio </a>
              </li>
			  
			  <?php
              if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
                  echo '<li class="nav-item"><a class="nav-link" href="cerrar_sesion.php">Cerrar sesión</a></li>';
                  echo '<li class="nav-item"><a class="nav-link" href="registrar_libro.php">Registrar libro</a></li>';
                  echo '<li class="nav-item active"><a class="nav-link" href="prestamos.php">Préstamos</a></li>';
                  if ($_SESSION['tipo'] === 'user') {
                      echo '<li class="nav-item"><a class="nav-link" href="usr_libro.php">Mis libros</a></li>';
                  }
              } else {
                  echo '<li class="nav-item"><a class="nav-link" href="iniciar_sesion.php">Iniciar sesión</a></li>';
                  echo '<li class="nav-item"><a class="nav-link" href="registrar_usuario.php">Registrarse</a></li>';
              }
			  if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin') {
            	echo '<li class="nav-item"><a class="nav-link" href="admin_libro.php">Administrar Libros</a></li>';
            	echo '<li class="nav-item"><a class="nav-link" href="reporte_libro.php">Reporte libros</a></li>';
        		}
              ?>
              <li class="nav-item"><a class="nav-link" href="ver_libros.php">Ver libros</a></li>
            </ul>
          </div>
        </nav>
      </div>
    </header>
    <!-- end header section -->
  </div>

  <!-- about section -->

  <section class="about_section layout_padding">
    <div class="container  ">
      <div class="heading_container heading_center">
        <h2>
          Prestamos<span>Libros</span>
        </h2>
		<?php
if (isset($_SESSION['msg_prestamo'])) {
    $tipo = $_SESSION['msg_prestamo']['tipo'];
    $texto = htmlspecialchars($_SESSION['msg_prestamo']['texto']);
    echo "<div class='alert alert-$tipo mt-3'>$texto</div>";
    unset($_SESSION['msg_prestamo']);
}
?>
		<?php
$consulta = "SELECT id FROM usuarios WHERE user = ?";
$stmt = $mysqli->prepare($consulta);
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$resultado = $stmt->get_result();
if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();
    $id_cliente = $usuario['id'];
} else {
    echo "<div class='alert alert-danger'>No se encontró el usuario.</div>";
    exit();
}
$stmt->close();

$consulta = "SELECT prestamos.id as id_prestamo, libros.id as libro_id, prestamos.inicio_prestamo as inicio, prestamos.fin_prestamo as fin, 
libros.titulo as titulo, libros.anio as anio, libros.imagen as imagen 
FROM libros, prestamos, usuarios 
WHERE prestamos.id_usuario = ? AND libros.estado = 'prestado' 
AND prestamos.id_libro = libros.id AND prestamos.id_usuario = usuarios.id 
AND prestamos.estado = 'prestado'";

$stmt = $mysqli->prepare($consulta);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    echo "<div class='table-responsive mt-4'>";
    echo "<table class='table table-bordered'>";
    echo "<thead class='bg-primary text-white'><tr><th colspan='6' class='text-center'>Libros prestados</th></tr></thead>";
    echo "<thead class='bg-primary text-white'><tr><th>Título</th><th>Año</th><th>Imagen</th><th>Fecha inicio prestamo</th><th>Fecha limite devolucion</th><th>Devolver</th></tr></thead>";
    echo "<tbody class='text-white'>";
    while ($row = $resultado->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['titulo']) . "</td>";
        echo "<td>" . htmlspecialchars($row['anio']) . "</td>";
        echo "<td><img src='" . htmlspecialchars($row['imagen']) . "' alt='Imagen' width='100'></td>";
        echo "<td>" . htmlspecialchars($row['inicio']) . "</td>";
        echo "<td>" . htmlspecialchars($row['fin']) . "</td>";
        echo "<td><a onclick='return confirmSubmit()' href='devolver_libro.php?id=" . $row['libro_id'] . "&id_prestamo=" . $row['id_prestamo'] . "'><img src='imagenes/check.png' alt='Imagen de check' width='100' height='100'></a></td>";
        echo "</tr>";
    }
    echo "</tbody></table></div>";
} else {
    echo "<div class='alert alert-info mt-4'>No hay libros prestados actualmente.</div>";
}
$stmt->close();
$mysqli->close();
?>

        
              
			 

            
            
            
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- end about section -->

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