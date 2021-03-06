<!DOCTYPE html>
<html>
	<head>
		<meta charset='utf-8' />
		<title>setup - Autorización</title>
	</head>
	<style>
		body {font-family: 'helvetica neue', helvetica, sans-serif; font-size: 12px; line-height: 1.5; width: 980px; margin: 0 auto; color: #333;}
		.container {width: 860px; margin: auto; }
		.header {height: 40px; border-bottom: 1px solid #ccc;}
		.content {padding: 1em 0;}
		.footer {height: 40px; border-top: 1px solid #ccc; text-align: right; color: #777;} 
		ul {list-style: none;}	
	</style>
	<body>
		<div class='container'>
			<div class='header'>
				<h1>Autorización</h1>
			</div>
			<div class='content'>  

<?php         
$msg = "";

if (isset($_POST['autorizacion'])) {


$projectName = basename(dirname(dirname(__FILE__)));

// SALT
$salt = MD5(RAND());
$setup_file = <<<SOURCE
<?php 
  define('SALT', '$salt');
?>
SOURCE;
	$archivo = fopen("../lib/salt.php", 'w') or die("No se pudo crear el archivo salt.php");
	fwrite($archivo, $setup_file);
	fclose($archivo);
  
// Autenticación
$setup_file = <<<SOURCE
<?php session_start();
require 'root.php';
if (empty(\$_SESSION['uid'])) { 
		header("location: " . ROOT_PATH . "/index.php"); 
} 
?>
SOURCE;
	$archivo = fopen("../autenticacion.php", 'w') or die("No se pudo crear el archivo autenticacion.php");
	fwrite($archivo, $setup_file);
	fclose($archivo);
// Autorización
$setup_file = <<<SOURCE
<?php session_start();
require 'root.php';
if (!isset(\$_SESSION['admin']) || (\$_SESSION['admin'] != 1)) {
	header("location: " . ROOT_PATH . "/index.php");
}
?>
SOURCE;
	$archivo = fopen("../autorizacion.php", 'w') or die("No se pudo crear el archivo autorizacion.php");
	fwrite($archivo, $setup_file);
	fclose($archivo);
// Cerrar Sesión
$setup_file = <<<SOURCE
<?php
	session_start();
  \$_SESSION = array();  
	session_destroy();
	header("location: index.php");
?>
SOURCE;
	$archivo = fopen("../cerrar_sesion.php", 'w') or die("No se pudo crear el archivo cerrar_sesion.php");
	fwrite($archivo, $setup_file);
	fclose($archivo);
// Home
$setup_file = <<<SOURCE
<?php require 'autenticacion.php' ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset='utf-8' />
		<link rel="stylesheet" href="assets/css/$projectName.css" type="text/css" />
	</head>
	<body>
		<?php require 'config/conexion.php'; ?>
		<div class='container'>
			<div class='header'>
				<h1><a href='index.php'>$projectName</a></h1>
			</div>

			<div class='content'>
				<?php if (isset(\$_SESSION['admin']) && \$_SESSION['admin'] == 1) { ?>
					 <!-- Código solo para administradores -->
				<?php } ?>
					<a href='cerrar_sesion.php'>Cerrar Sesión</a><br />
			</div>
			<div class='footer'>
				<p>
					&copy; $projectName
				</p>
			</div>
		</div>
		<script src='assets/js/$projectName.js'></script>
	</body>
</html> 
SOURCE;
	$archivo = fopen("../home.php", 'w') or die("No se pudo crear el archivo home.php");
	fwrite($archivo, $setup_file);
	fclose($archivo);

// Cuenta
$setup_file = <<<SOURCE
<?php
session_start();
require "lib/salt.php";
require "config/conexion.php";

\$nombre = \$_POST['nombre'];
\$email = \$_POST['email'];
\$password = \$_POST['password'];
\$confirmacion = \$_POST['confirmacion'];
\$date = date('Y-m-d H:i:s');

if (\$password == \$confirmacion) {
    \$encrypted_password = hash('sha256', \$password . SALT);
    \$is_admin = 0;
    \$query = "INSERT INTO usuarios (nombre, email, password, admin, creado, actualizado) VALUES (?, ?, ?, ?, ?, ?)";
  if (\$stmt = \$conexion->prepare(\$query)) {
    \$stmt->bind_param('ssssss', \$nombre, \$email, \$encrypted_password, \$is_admin, \$date, \$date);
    \$completado = \$stmt->execute();          
    if (\$completado) {
      header("location: home.php");      
    }
    \$stmt->close();      
  }
} else {
		header("location: index.php");
}
  \$conexion->close();
?> 
SOURCE;
	$archivo = fopen("../cuenta.php", 'w') or die("No se pudo crear el archivo cuenta.php");
	fwrite($archivo, $setup_file);
	fclose($archivo);

// Sesión
$setup_file = <<<SOURCE
  <?php
  session_start();
  require "lib/salt.php";
  require "config/conexion.php";

  \$msg = "";

  if (isset(\$_POST['sesion'])) {
  	\$email = \$_POST['email'];
  	\$password = \$_POST['password'];
    \$encrypted_password = hash('sha256', \$password . SALT);  
  	\$query = "SELECT id, admin FROM usuarios WHERE email = ? AND password = ?";

    if (\$stmt = \$conexion->prepare(\$query)) {
      \$stmt->bind_param("ss", \$email, \$encrypted_password);
      \$stmt->execute();
      \$resultados = \$stmt->get_result();

    	while (\$usuario = \$resultados->fetch_array()) { 
    		\$_SESSION['uid'] = \$usuario['id'];
    		\$_SESSION['admin'] = \$usuario['admin'];
    	}    
      \$stmt->close();
    }

  	if (\$_SESSION['uid']) {
  			header("location: home.php");
  	} else {
  		\$msg = "El usuario o la contraseña no son correctas. Intenta de nuevo.";
  	}
    \$conexion->close();
  }                                               

  ?>
  <!DOCTYPE html>
  <html>
  	<head>
  		<meta charset='utf-8' />
  		<link rel="stylesheet" href="assets/css/$projectName.css" type="text/css" />
  	</head>
  	<body>
  		<div class='container'>
  			<div class='header'>
  				<h1><a href='index.php'>$projectName</a></h1>
  			</div>

  			<div class='content'>
  				<h3>Autorización</h3>
  				<fieldset>
  					<legend>Iniciar sesión</legend>
  				<form action="iniciar_sesion.php" method="post" accept-charset="utf-8">
  					<?php
  						if (\$msg <> "")  {
  							echo "<div style='width: 100%; display: block; height: 50px; color: red'>";
  							echo \$msg;
  							echo "</div>";
  						}
  					?>
  					<table>
  						<tr>
  							<td><label>email</label></td>
  							<td><input type="text" name="email"></td>
  						</tr>
  						<tr>
  							<td><label>password</label></td>
  							<td><input type="password" name="password"></td>
  						</tr>
  					</table>
  					<input type="submit" value="Iniciar sesión" name='sesion' />
  				</form>
  				</fieldset>
  				<fieldset>
  					<legend>Crear cuenta</legend>
  				<form action="cuenta.php" method="post" accept-charset="utf-8">
  					<table>
  						<tr>
  							<td><label>Nombre Completo:</label></td>
  							<td><input type="text" name="nombre"></td>
  						</tr>
  						<tr>
  							<td><label>Email:</label></td>
  							<td><input type="text" name="email"></td>
  						</tr>
  						<tr>
  							<td><label>Contraseña:</label></td>
  							<td><input type="password" name="password"></td>
  						</tr>
  						<tr>
  							<td><label>Confirmación de Contraseña:</label></td>
  							<td><input type="password" name="confirmacion"></td>
  						</tr>
  				</table>
  								<input type="submit" value="Crear cuenta">
  				</form>
  				</fieldset>
  			</div>
  			<div class='footer'>
  				<p>
  					&copy; $projectName
  				</p>
  			</div>
  		</div>
  		<script src='assets/js/$projectName.js'></script>
  	</body>
  </html>
SOURCE;
	$archivo = fopen("../iniciar_sesion.php", 'w') or die("No se pudo crear el archivo iniciar_sesion.php");
	fwrite($archivo, $setup_file);
	fclose($archivo);

// usuarios.sql
$setup_file = <<<SOURCE
USE $projectName;
CREATE TABLE IF NOT EXISTS usuarios (
id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
nombre varchar(255) NOT NULL,
email varchar(255) NOT NULL,
password varchar(255) NOT NULL,
admin int(11) UNSIGNED NOT NULL,
creado datetime,
actualizado datetime,
PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SOURCE;
	$archivo = fopen("../db/usuarios.sql", 'w') or die("No se pudo crear el archivo usuarios.sql");
	fwrite($archivo, $setup_file);
	fclose($archivo);   

	$msg = "<div style='color: red;'>¡Hecho!</div>";
}
?>            
	<form action='autorizacion.php' method='post'>
		<?php echo $msg; ?>
		<p>
			Este archivo se encarga de generar un proceso de autorización básico que consta de 7 archivos:
		</p>
		<ul>
			<li>autenticacion.php: Verifica que exista un valor establecido en la sesión uid. Si no hay valor establecido redirige el usuario a index.php</li>
			<li>autorizacion.php: Verifica que exista un valor establecido en la sesión admin. Si no hay valor establecido redirige el usuario a index.php</li>
			<li>cerrar_sesion.php: Destruye las sesiones del usuario que mande llamar el archivo y redirige al usuario a index.php</li>
			<li>cuenta.php: Crea una cuenta en el sistema. Si la logra crear redirige al usuario a home.php - si no lo redirige a index.php</li>
			<li>home.php: Archivo que funciona como panel de control para los usuarios (en específico para el administrador).</li>
			<li>iniciar_sesion.php: Formularios para crear usuarios o iniciar sesión (se pueden copiar e incluir en otro archivo si se quiere modificar la funcionalidad).</li>
			<li>usuarios.sql: Archivo sql para crear la tabla de usuarios.</li>                                                                                 
		</ul>
		<h2>Nota</h2>
		<p>
      Aunque se genera de manera dinámica el valor para la constante SALT - es recomendable modificarlo <b>ANTES</b> de crear usuarios.<br>
      Una vez que se tengan usuarios lo más probable es que sus contraseñas queden inservibles.<br>
		</p>
		   <input type='submit' name='autorizacion' value='Crear Archivos' />
	</form>
		</div>
		<div class='footer'>
			&nbsp;	
		</div> 
	</div>
	</body>
</html>