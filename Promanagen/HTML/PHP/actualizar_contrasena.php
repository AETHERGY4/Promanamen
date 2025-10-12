<?php
// actualizar_contrasena.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Config DB - ajusta si tu host/usuario/clave son distintos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "promanage";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // si se accede por GET, redirigir (opcional)
    header("Location: /Promanagen/HTML/tu_formulario.html");
    exit;
}

// Recoger y sanitizar
$correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
$contrasena = isset($_POST['contrasena']) ? trim($_POST['contrasena']) : '';
$confirmar = isset($_POST['confirmar']) ? trim($_POST['confirmar']) : '';

if (empty($correo) || empty($contrasena) || empty($confirmar)) {
    echo "Por favor completa todos los campos.";
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo "Correo inválido.";
    exit;
}

if ($contrasena !== $confirmar) {
    header("Location: /Promanagen/HTML/contra_no_igual.html");
    exit;
}

// Opcional: validación fuerte de contraseña (mínima)
// if (strlen($contrasena) < 8) { echo "La contraseña debe tener al menos 8 caracteres."; exit; }

// Verificar que el correo exista
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
if (!$stmt) { die("Prepare failed: " . $conn->error); }
$stmt->bind_param("s", $correo);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    header("Location: /Promanagen/HTML/correo_rer.html");
    exit;
}
$stmt->close();

// Hashear la contraseña con password_hash()
$hash = password_hash($contrasena, PASSWORD_DEFAULT); // bcrypt por defecto

// Actualizar en DB
$stmt = $conn->prepare("UPDATE usuarios SET contrasena = ? WHERE correo = ?");
if (!$stmt) { die("Prepare failed: " . $conn->error); }
$stmt->bind_param("ss", $hash, $correo);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: /Promanagen/HTML/Restablecido.html");
    exit;
} else {
    // Para debug local
    echo "Error al actualizar la contraseña: " . $stmt->error;
    $stmt->close();
    $conn->close();
    exit;
}
?>

