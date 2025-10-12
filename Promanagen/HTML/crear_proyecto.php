<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: Inicio.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $usuario_id = (int)$_SESSION['usuario_id']; // creador
    $maestro_id = isset($_POST['maestro_id']) ? (int)$_POST['maestro_id'] : null;
    // integrantes (array de ids) - podría no venir
    $integrantes = isset($_POST['integrantes']) && is_array($_POST['integrantes']) ? $_POST['integrantes'] : [];

    $conn = new mysqli("localhost", "root", "", "promanage");
    if ($conn->connect_errno) {
        die("Error de conexión: " . $conn->connect_error);
    }

    // Usar transacción para atomicidad
    $conn->autocommit(false);
    try {
        // 1) Insertar proyecto
        $stmt = $conn->prepare("INSERT INTO proyectos (usuario_id, maestro_id, nombre, descripcion) VALUES (?, ?, ?, ?)");
        if (!$stmt) throw new Exception("Prepare proyectos: " . $conn->error);
        $stmt->bind_param("iiss", $usuario_id, $maestro_id, $nombre, $descripcion);
        if (!$stmt->execute()) throw new Exception("Execute proyectos: " . $stmt->error);
        $project_id = $conn->insert_id;
        $stmt->close();

        // 2) Insertar integrantes (incluye el creador siempre)
        $stmtIns = $conn->prepare("INSERT INTO integrantes (proyecto_id, usuario_id) VALUES (?, ?)");
        if (!$stmtIns) throw new Exception("Prepare integrantes: " . $conn->error);

        // Insertar creador como integrante
        $stmtIns->bind_param("ii", $project_id, $usuario_id);
        if (!$stmtIns->execute()) throw new Exception("Execute integrante creador: " . $stmtIns->error);

        // Insertar otros integrantes seleccionados (evitar duplicados)
        $inserted = [$usuario_id => true]; // para no reinsertar al creador
        foreach ($integrantes as $i_uid) {
            $i_uid = (int)$i_uid;
            if ($i_uid <= 0) continue;
            if (isset($inserted[$i_uid])) continue;
            $stmtIns->bind_param("ii", $project_id, $i_uid);
            if (!$stmtIns->execute()) throw new Exception("Execute integrante ($i_uid): " . $stmtIns->error);
            $inserted[$i_uid] = true;
        }
        $stmtIns->close();

        // Commit
        $conn->commit();
        $conn->autocommit(true);
        $conn->close();

        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $conn->autocommit(true);
        $conn->close();
        // Manejo simple de error — en producción muestra un mensaje amigable / loguear el error
        die("Error al crear proyecto: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<img src="../IMAGEN/ChatGPT Image 24 sept 2025, 12_40_01 p.m..png" alt="Logo AdoptaFeliz">
<title>Crear Proyecto</title>
<link rel="stylesheet" href="/Promanagen/CSS/repositorio.css">
</head>
<body>
<header class="header">
    <div class="header-left">
        <h1><?php echo htmlspecialchars($_SESSION['usuario_correo']); ?></h1>
        <span>Crear proyecto</span>
    </div>
    <div class="header-right">
        <a href="index.php" class="btn">Volver</a>
    </div>
</header>

<main class="container">
    <div class="repositorio">
        <h2>Nuevo Proyecto</h2>
        <form method="post">
            <label>Nombre:</label><br>
            <input type="text" name="nombre" required style="width:100%;padding:8px;margin-bottom:10px;"><br>

            <label>Descripción:</label><br>
            <textarea name="descripcion" style="width:100%;padding:8px;margin-bottom:10px;"></textarea><br>

            <label>Maestro que administra:</label><br>
            <select name="maestro_id" required style="width:100%;padding:8px;margin-bottom:10px;">
                <option value="">Selecciona un maestro</option>
                <?php
                // Cargar maestros
                $conn = new mysqli("localhost", "root", "", "promanage");
                if ($conn->connect_errno === 0) {
                    $res = $conn->query("SELECT id, nombre FROM maestros");
                    while($row = $res->fetch_assoc()){
                        echo "<option value='".(int)$row['id']."'>".htmlspecialchars($row['nombre'])."</option>";
                    }
                    $res->free();
                }
                $conn->close();
                ?>
            </select><br>

            <label>Agregar integrantes:</label><br>
            <small>Selecciona usuarios que formarán parte del proyecto (el creador se agregará automáticamente)</small><br>
            <select name="integrantes[]" multiple size="6" style="width:100%;padding:8px;margin-bottom:10px;">
                <?php
                // Cargar posibles integrantes (usuarios)
                $conn2 = new mysqli("localhost", "root", "", "promanage");
                if ($conn2->connect_errno === 0) {
                    $res2 = $conn2->query("SELECT id, nombre, correo FROM usuarios");
                    while($u = $res2->fetch_assoc()){
                        // opcional: excluir al usuario actual de la lista, o mostrarlo marcado
                        if ((int)$u['id'] === (int)$_SESSION['usuario_id']) {
                            // mostrar pero quizá deshabilitado/indicado
                            echo "<option value='".(int)$u['id']."' selected>".htmlspecialchars($u['nombre'])." (tú)</option>";
                        } else {
                            echo "<option value='".(int)$u['id']."'>".htmlspecialchars($u['nombre'])." - ".htmlspecialchars($u['correo'])."</option>";
                        }
                    }
                    $res2->free();
                }
                $conn2->close();
                ?>
            </select><br>

            <button type="submit" class="btn">Crear Proyecto</button>
        </form>
    </div>
</main>
</body>
</html>