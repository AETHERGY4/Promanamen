<?php
session_start();
if (!isset($_SESSION['usuario_id'])) header("Location: login.html");

$proyecto_id = $_GET['id'];
$conn = new mysqli("localhost", "root", "", "promanage");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $stmt = $conn->prepare("UPDATE proyectos SET nombre=?, descripcion=? WHERE id=?");
    $stmt->bind_param("ssi", $nombre, $descripcion, $proyecto_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("SELECT nombre, descripcion FROM proyectos WHERE id=?");
$stmt->bind_param("i", $proyecto_id);
$stmt->execute();
$result = $stmt->get_result();
$proyecto = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Proyecto</title>
<link rel="stylesheet" href="/Promanagen/CSS/repositorio.css">
</head>
<body>
<header class="header">
    <div class="header-left">
        <h1><?php echo htmlspecialchars($_SESSION['usuario_correo']); ?></h1>
        <span>Editar Proyecto</span>
    </div>
    <div class="header-right">
        <a href="/Promanagen/HTML/index.php" class="btn">Volver</a>
    </div>
</header>

<main class="container">
    <div class="repositorio">
        <h2>Editar Proyecto</h2>
        <form method="post">
            <label>Nombre:</label><br>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($proyecto['nombre']); ?>" style="width:100%;padding:8px;margin-bottom:10px;"><br>
            <label>Descripción:</label><br>
            <textarea name="descripcion" style="width:100%;padding:8px;margin-bottom:10px;"><?php echo htmlspecialchars($proyecto['descripcion']); ?></textarea><br>
            <button type="submit" class="btn">Guardar Cambios</button>
        </form>
    </div>
</main>
</body>
</html>