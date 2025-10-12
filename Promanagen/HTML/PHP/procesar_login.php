<?php
// login.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "promanage";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Recoger y sanitizar entrada
$correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
$contrasena = isset($_POST['contrasena']) ? trim($_POST['contrasena']) : '';

if (empty($correo) || empty($contrasena)) {
    // Puedes redirigir o mostrar mensaje; aquí redirijo a la página de error
    header("Location: /Promanagen/HTML/contra_falla.html");
    exit;
}

// Preparar y ejecutar
$stmt = $conn->prepare("SELECT id, correo, contrasena FROM usuarios WHERE correo = ?");
if (!$stmt) {
    // Error en prepare
    error_log("Prepare failed: " . $conn->error);
    header("Location: /Promanagen/HTML/correo_falla.html");
    exit;
}
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $usuario = $result->fetch_assoc();
    $hashAlmacenado = $usuario['contrasena'];

    // Verificar con password_verify
    if (password_verify($contrasena, $hashAlmacenado)) {

        // Opcional: re-hash si el algoritmo/cost cambió
        if (password_needs_rehash($hashAlmacenado, PASSWORD_DEFAULT)) {
            $nuevoHash = password_hash($contrasena, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE usuarios SET contrasena = ? WHERE id = ?");
            if ($upd) {
                $upd->bind_param("si", $nuevoHash, $usuario['id']);
                $upd->execute();
                $upd->close();
            }
        }

        // Login exitoso
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_correo'] = $usuario['correo'];
        $stmt->close();
        $conn->close();
        header("Location: /Promanagen/HTML/index.php");
        exit;
    } else {
        // Contraseña incorrecta
        $stmt->close();
        $conn->close();
        header("Location: /Promanagen/HTML/contra_falla.html");
        exit;
    }
} else {
    // Correo no registrado
    if ($stmt) $stmt->close();
    $conn->close();
    header("Location: /Promanagen/HTML/correo_falla.html");
    exit;
}
?>

