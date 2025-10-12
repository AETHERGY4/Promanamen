<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "promanage";


$conn = new mysqli($servername, $username, $password, $dbname, 3306);

if ($conn->connect_error) {
  die("Conexión fallida: " . $conn->connect_error);
}


$correo    = isset($_POST['correo']) ? trim($_POST['correo']) : '';
$contrasena = isset($_POST['contrasena']) ? trim($_POST['contrasena']) : '';
$confirmar  = isset($_POST['confirmar']) ? trim($_POST['confirmar']) : '';
$nombre     = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$telefono   = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
$ubicacion  = isset($_POST['ubicacion']) ? trim($_POST['ubicacion']) : '';
$bio        = isset($_POST['bio']) ? trim($_POST['bio']) : '';


if (empty($correo) || empty($contrasena) || empty($confirmar) || empty($nombre) || empty($telefono) || empty($ubicacion) || empty($bio)){
  echo "Por favor, completa todos los campos.";
  exit;
}

if ($contrasena !== $confirmar) {
  header("Location: /Promanagen/HTML/conta_no_con.html");
  echo "Las contraseñas no coinciden.";
  exit;
}

// Hashear la contraseña
$contrasena = password_hash($contrasena, PASSWORD_DEFAULT);


$stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
  header("Location: /Promanagen/HTML/correo_ya_regis.html");
  echo "El correo ya está registrado.";
  exit;
}
$stmt->close();


$stmt = $conn->prepare("INSERT INTO usuarios (correo, contrasena, nombre, telefono, ubicacion, bio) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $correo, $contrasena, $nombre, $telefono, $ubicacion, $bio);

if ($stmt->execute()) {
  
  header("Location: /Promanagen/HTML/Exito.html");
  exit;
} else {
  echo "Error al registrar: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>