<?php
session_start();
if (!isset($_SESSION['usuario_id'])) header("Location: login.html");

$proyecto_id = $_GET['id'];
$usuario_correo = $_SESSION['usuario_correo'];

$conn = new mysqli("localhost", "root", "", "promanage");

// Subir archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $uploadDir = "../uploads/".$_SESSION['usuario_id']."/$proyecto_id/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $fileName = basename($_FILES['archivo']['name']);
    $targetFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['archivo']['tmp_name'], $targetFile)) {
        $stmt = $conn->prepare("INSERT INTO archivos (proyecto_id, nombre_archivo, ruta) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $proyecto_id, $fileName, $targetFile);
        $stmt->execute();
        $stmt->close();

        $conn->query("UPDATE proyectos SET ultimo_commit = NOW() WHERE id = $proyecto_id");
    }
}

// Traer archivos
$stmt = $conn->prepare("SELECT nombre_archivo, ruta, fecha_subida FROM archivos WHERE proyecto_id = ?");
$stmt->bind_param("i", $proyecto_id);
$stmt->execute();
$result = $stmt->get_result();
$archivos = [];
while ($row = $result->fetch_assoc()) $archivos[] = $row;
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Archivos del Proyecto</title>
<link rel="stylesheet" href="/Promanagen/CSS/repositorio.css">
</head>
<body>
<header class="header">
    <div class="header-left">
        <h1><?php echo htmlspecialchars($usuario_correo); ?></h1>
        <span>Archivos del Proyecto</span>
    </div>
    <div class="header-right">
        <a href="/Promanagen/HTML/index.php" class="btn">Volver</a>
    </div>
</header>

<main class="container">
    <div class="repositorio">
    <h2>Subir Archivo</h2>
      <form method="post" enctype="multipart/form-data">
        <!-- Input oculto -->
        <input type="file" name="archivo" id="archivo" required style="display:none;">
        
        <!-- Label estilizado como botón -->
        <label for="archivo" class="btn">Seleccionar Archivo</label><br><br>
        
        <!-- Botón de enviar -->
        <button type="submit" class="btn">Subir Archivo</button>
      </form>
    </div>

    <div class="repositorio">
        <h2>Archivos Subidos</h2>
        <?php if (empty($archivos)): ?>
            <p>No hay archivos en este proyecto.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($archivos as $archivo): ?>
                    <li><a href="<?php echo $archivo['ruta']; ?>" target="_blank"><?php echo $archivo['nombre_archivo']; ?></a> - <?php echo $archivo['fecha_subida']; ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
