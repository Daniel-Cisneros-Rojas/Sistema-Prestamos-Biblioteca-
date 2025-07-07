
<?php
session_start();
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

  <title> Biblioteca </title>

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

<body>

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
            <span class=""> </span>
          </button>

          <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav  ">
              <li class="nav-item active">
                <a class="nav-link" href="index.php">Inicio <span class="sr-only">(current)</span></a>
              </li>
			  
			  <?php
            if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
                echo '<li class="nav-item"><a class="nav-link" href="cerrar_sesion.php">Cerrar sesión</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="registrar_libro.php">Registrar libro</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="prestamos.php">Préstamos</a></li>';
                if ($_SESSION['tipo'] === 'user') {
                    echo '<li class="nav-item"><a class="nav-link" href="usr_libro.php">Mis libros</a></li>';
                }
            } else {
                echo '<li class="nav-item"><a class="nav-link" href="iniciar_sesion.php"> <i class="fa fa-user" aria-hidden="true"></i> Iniciar sesión</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="registrar_usuario.php"> <i class="fa fa-user" aria-hidden="true"></i> Registrarse</a></li>';
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
    <!-- slider section -->
    <section class="slider_section ">
      <div id="customCarousel1" class="carousel slide" data-ride="carousel">
        <div class="carousel-inner">
          <div class="carousel-item active">
            <div class="container ">
              <div class="row">
                <div class="col-md-6 ">
                  <div class="detail-box">
                    <h1>Biblioteca <br> Virtual</h1>

                  <?php
                  if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
                      $tipo = htmlspecialchars($_SESSION['tipo']);
                      $usuario = htmlspecialchars($_SESSION['usuario']);
                      $nombre = htmlspecialchars($_SESSION['nombre']);
                      switch ($tipo) {
                          case 'admin':
                              echo "<p>Bienvenido administrador: <strong>$usuario</strong></p>";
                              break;
                          case 'user':
                              echo "<p>Bienvenido usuario: <strong>$usuario</strong></p>";
                              break;
                          default:
                              echo "<p>Hubo un problema!</p>";
                      }
                  } else {
                      echo "<p>Bienvenido al sitio!</p>";
                      echo "<p>Por favor <a href='registrar_usuario.php'>registrate</a> o <a href='iniciar_sesion.php'>inicia sesion</a> para obtener acceso a todas las funciones del sitio.</p>";
                  }
                  ?>

                  <div class="btn-box">
                    <a href="ver_libros.php" class="btn1">Ver libros</a>
                  </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="img-box">
                    <img src="imagenes/ima1.png" alt="" style="width: 500px; height: 273px; object-fit: cover;">
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="carousel-item ">
            <div class="container ">
              <div class="row">
                <div class="col-md-6 ">
                  <div class="detail-box">
                    <h1>Biblioteca <br> Virtual</h1>

                  <?php
                  if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
                      $tipo = htmlspecialchars($_SESSION['tipo']);
                      $usuario = htmlspecialchars($_SESSION['usuario']);
                      $nombre = htmlspecialchars($_SESSION['nombre']);
                      switch ($tipo) {
                          case 'admin':
                              echo "<p>Bienvenido administrador: <strong>$usuario</strong></p>";
                              break;
                          case 'user':
                              echo "<p>Bienvenido usuario: <strong>$usuario</strong></p>";
                              break;
                          default:
                              echo "<p>Hubo un problema!</p>";
                      }
                  } else {
                      echo "<p>Bienvenido al sitio!</p>";
                      echo "<p>Por favor <a href='registrar_usuario.php'>registrate</a> o <a href='iniciar_sesion.php'>inicia sesion</a> para obtener acceso a todas las funciones del sitio.</p>";
                  }
                  ?>

                  <div class="btn-box">
                    <a href="ver_libros.php" class="btn1">Ver libros</a>
                  </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="img-box">
                    <img src="imagenes/ima2.png" alt="" style="width: 500px; height: 273px; object-fit: cover;">
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="carousel-item">
            <div class="container ">
              <div class="row">
                <div class="col-md-6 ">
                  <div class="detail-box">
                    <h1>Biblioteca <br> Virtual</h1>

                  <?php
                  if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
                      $tipo = htmlspecialchars($_SESSION['tipo']);
                      $usuario = htmlspecialchars($_SESSION['usuario']);
                      $nombre = htmlspecialchars($_SESSION['nombre']);
                      switch ($tipo) {
                          case 'admin':
                              echo "<p>Bienvenido administrador: <strong>$usuario</strong></p>";
                              break;
                          case 'user':
                              echo "<p>Bienvenido usuario: <strong>$usuario</strong></p>";
                              break;
                          default:
                              echo "<p>Hubo un problema!</p>";
                      }
                  } else {
                      echo "<p>Bienvenido al sitio!</p>";
                      echo "<p>Por favor <a href='registrar_usuario.php'>registrate</a> o <a href='iniciar_sesion.php'>inicia sesion</a> para obtener acceso a todas las funciones del sitio.</p>";
                  }
                  ?>

                  <div class="btn-box">
                    <a href="ver_libros.php" class="btn1">Ver libros</a>
                  </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="img-box">
                    <img src="imagenes/ima3.png" alt="" style="width: 500px; height: 273px; object-fit: cover;">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <ol class="carousel-indicators">
          <li data-target="#customCarousel1" data-slide-to="0" class="active"></li>
          <li data-target="#customCarousel1" data-slide-to="1"></li>
          <li data-target="#customCarousel1" data-slide-to="2"></li>
        </ol>
      </div>

    </section>
    <!-- end slider section -->
  </div>


  
    <!-- service section -->

    <!-- end service section -->
    
    
      <!-- about section -->
    
 

  <!-- end about section -->

  <!-- why section -->

  

  <!-- end why section -->

  <!-- team section -->
  
  <!-- end team section -->


  <!-- client section -->

 

  <!-- end client section -->


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