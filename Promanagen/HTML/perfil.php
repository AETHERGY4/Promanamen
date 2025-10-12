<?php
session_start();
if (!isset($_SESSION['usuario_id'])) header("Location: login.html");

$usuario_id = $_SESSION['usuario_id'];
$usuario_correo = $_SESSION['usuario_correo'];

$uploadDir = __DIR__ . '/../../Promanagen/uploads/backgrounds/'; // ajusta si tu estructura es distinta
$publicUploadDir = '/Promanagen/uploads/backgrounds/'; // ruta pública usada en HTML

// Asegurar que exista la carpeta de uploads
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$conn = new mysqli("localhost", "root", "", "promanage");
if ($conn->connect_error) die("Conexión fallida: " . $conn->connect_error);

// Manejar POST (guardar cambios y subir/eliminar fondo si existe)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $theme = in_array($_POST['theme'] ?? '', ['dark','light','custom']) ? $_POST['theme'] : 'dark';

    // Validar colores hex
    $btn_color = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['btn_color'] ?? '') ? $_POST['btn_color'] : null;
    $header_color = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['header_color'] ?? '') ? $_POST['header_color'] : null;
    $text_color = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['text_color'] ?? '') ? $_POST['text_color'] : null;

    // Primero obtener bg_url actual para posible borrado
    $stmtOld = $conn->prepare("SELECT bg_url FROM usuarios WHERE id=? LIMIT 1");
    $stmtOld->bind_param("i", $usuario_id);
    $stmtOld->execute();
    $r = $stmtOld->get_result()->fetch_assoc();
    $stmtOld->close();
    $existingBg = !empty($r['bg_url']) ? $r['bg_url'] : null;

    // Manejar eliminación explícita de fondo
    $removeBgRequested = isset($_POST['remove_bg']) && $_POST['remove_bg'] == '1';
    if ($removeBgRequested && $existingBg) {
        $oldPath = $_SERVER['DOCUMENT_ROOT'] . $existingBg;
        if (is_file($oldPath)) @unlink($oldPath);
        $existingBg = null; // mark removed
    }

    // Manejar subida de fondo (opcional) — si suben archivo se reemplaza
    $bg_url_db = null;
    if (!empty($_FILES['bg_file']['name'])) {
        $file = $_FILES['bg_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            if ($file['size'] > 3 * 1024 * 1024) {
                $error = "El archivo es demasiado grande (máx 3MB).";
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                $allowed = ['image/jpeg','image/png','image/webp'];
                if (!in_array($mime, $allowed)) {
                    $error = "Formato no permitido. Usa JPG, PNG o WEBP.";
                } else {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'bg_u' . $usuario_id . '_' . time() . '.' . $ext;
                    $dest = $uploadDir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $bg_url_db = $publicUploadDir . $filename;
                        // eliminar anterior
                        if (!empty($existingBg)) {
                            $oldPath = $_SERVER['DOCUMENT_ROOT'] . $existingBg;
                            if (is_file($oldPath)) @unlink($oldPath);
                        }
                    } else {
                        $error = "No se pudo mover el archivo subido.";
                    }
                }
            }
        } else {
            $error = "Error al subir archivo (código {$file['error']}).";
        }
    }

    // Si el usuario solicitó eliminar y no subió nuevo fondo, dejamos bg_url vacío
    if ($removeBgRequested && !$bg_url_db) {
        $bg_url_db = ''; // significa borrar en DB
    }

    // Construir UPDATE dinámico
    $fields = "nombre=?, telefono=?, ubicacion=?, bio=?, theme=?";
    $types = "sssss";
    $values = [$nombre, $telefono, $ubicacion, $bio, $theme];

    if ($btn_color !== null) { $fields .= ", btn_color=?"; $types .= "s"; $values[] = $btn_color; }
    if ($header_color !== null) { $fields .= ", header_color=?"; $types .= "s"; $values[] = $header_color; }
    if ($text_color !== null) { $fields .= ", text_color=?"; $types .= "s"; $values[] = $text_color; }
    if ($bg_url_db !== null) { $fields .= ", bg_url=?"; $types .= "s"; $values[] = ($bg_url_db === '' ? null : $bg_url_db); /* null to set NULL in DB */ }

    $values[] = $usuario_id;
    $types .= "i";

    $sql = "UPDATE usuarios SET $fields WHERE id=?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) die("Error prepare: " . $conn->error);

    // bind_param — PHP tratará nulls correctamente si usamos variables PHP null
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $stmt->close();

    // actualizar sesión (opcional)
    $_SESSION['theme'] = $theme;
    if ($btn_color !== null) $_SESSION['btn_color'] = $btn_color;
    if ($header_color !== null) $_SESSION['header_color'] = $header_color;
    if ($text_color !== null) $_SESSION['text_color'] = $text_color;
    if ($bg_url_db) $_SESSION['bg_url'] = $bg_url_db;
    if ($bg_url_db === '') unset($_SESSION['bg_url']); // si borrado

    header("Location: perfil.php");
    exit;
}

// Traer datos actuales
$stmt = $conn->prepare("SELECT nombre, telefono, ubicacion, bio, theme, custom_color, bg_url, btn_color, header_color, text_color FROM usuarios WHERE id=?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc() ?: [];
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<img src="../IMAGEN/ChatGPT Image 24 sept 2025, 12_40_01 p.m..png" alt="Logo AdoptaFeliz">
<title>Perfil de <?php echo htmlspecialchars($usuario_correo); ?></title>
<link rel="stylesheet" href="/Promanagen/CSS/repositorio.css">
<style>
.theme-row { display:flex; gap:10px; align-items:center; margin-bottom:12px; }
.theme-row label { margin-right:8px; }
.color-preview { width:28px; height:28px; border-radius:6px; border:1px solid #222; display:inline-block; vertical-align:middle; }
.bg-preview { width:100%; height:120px; background-size:cover; background-position:center; border-radius:8px; border:1px solid #333; margin-top:8px; }
.note { font-size:0.9rem; color:#999; margin-top:6px; }
.remove-row { display:flex; gap:8px; align-items:center; margin-top:8px; }
</style>
</head>
<body>
<header class="header">
    <div class="header-left">
        <h1><?php echo htmlspecialchars($usuario_correo); ?></h1>
        <span>Perfil de Usuario</span>
    </div>
    <div class="header-right">
        <a href="/Promanagen/HTML/index.php" class="btn">Volver</a>
    </div>
</header>

<main class="container">
    <div class="repositorio">
        <h2>Información del Perfil</h2>
        <form method="post" enctype="multipart/form-data">
            <label>Nombre:</label><br>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" class="input-field"><br><br>

            <label>Teléfono:</label><br>
            <input type="text" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>" class="input-field"><br><br>

            <label>Ubicación:</label><br>
            <input type="text" name="ubicacion" value="<?php echo htmlspecialchars($usuario['ubicacion'] ?? ''); ?>" class="input-field"><br><br>

            <label>Bio:</label><br>
            <textarea name="bio" class="input-field"><?php echo htmlspecialchars($usuario['bio'] ?? ''); ?></textarea><br><br>

            <h3>Preferencias de tema</h3>
            <div class="theme-row">
                <label><input type="radio" name="theme" value="dark" <?php echo (($usuario['theme'] ?? 'dark') === 'dark') ? 'checked' : ''; ?>> Oscuro</label>
                <label><input type="radio" name="theme" value="light" <?php echo (($usuario['theme'] ?? '') === 'light') ? 'checked' : ''; ?>> Claro</label>
                <label><input type="radio" name="theme" value="custom" <?php echo (($usuario['theme'] ?? '') === 'custom') ? 'checked' : ''; ?>> Personalizado</label>
            </div>

            <div class="mb-3">
                <label for="btn_color">Color de botones</label><br>
                <input type="color" id="btn_color" name="btn_color" value="<?php echo htmlspecialchars($usuario['btn_color'] ?? '#238636'); ?>">
                <span class="color-preview" id="previewBtn" style="background: <?php echo htmlspecialchars($usuario['btn_color'] ?? '#238636'); ?>"></span>
            </div>

            <div class="mb-3">
                <label for="header_color">Color del header</label><br>
                <input type="color" id="header_color" name="header_color" value="<?php echo htmlspecialchars($usuario['header_color'] ?? '#161b22'); ?>">
                <span class="color-preview" id="previewHeader" style="background: <?php echo htmlspecialchars($usuario['header_color'] ?? '#161b22'); ?>"></span>
            </div>

            <div class="mb-3">
                <label for="text_color">Color del texto</label><br>
                <input type="color" id="text_color" name="text_color" value="<?php echo htmlspecialchars($usuario['text_color'] ?? '#c9d1d9'); ?>">
                <span class="color-preview" id="previewText" style="background: <?php echo htmlspecialchars($usuario['text_color'] ?? '#c9d1d9'); ?>"></span>
            </div>

            <div class="mb-3">
                <label for="bg_file">Fondo de página (opcional) — JPG / PNG / WEBP (máx 3MB)</label><br>
                <input type="file" id="bg_file" name="bg_file" accept="image/*"><br>

                <?php if(!empty($usuario['bg_url'])): ?>
                    <div class="bg-preview" id="bgPreview" style="background-image:url('<?php echo htmlspecialchars($usuario['bg_url']); ?>')"></div>
                    <div class="remove-row">
                        <label><input type="checkbox" name="remove_bg" value="1"> Eliminar fondo actual</label>
                        <span class="note">Marca y guarda para quitar el fondo del perfil.</span>
                    </div>
                <?php else: ?>
                    <div class="note">No has subido un fondo aún.</div>
                <?php endif; ?>
            </div>

            <br>
            <button type="submit" class="btn">Guardar Cambios</button>
        </form>
    </div>
</main>

<script>
// previews
const btnColor = document.getElementById('btn_color');
const headerColor = document.getElementById('header_color');
const textColor = document.getElementById('text_color');
const previewBtn = document.getElementById('previewBtn');
const previewHeader = document.getElementById('previewHeader');
const previewText = document.getElementById('previewText');
btnColor?.addEventListener('input', e => previewBtn.style.background = e.target.value);
headerColor?.addEventListener('input', e => previewHeader.style.background = e.target.value);
textColor?.addEventListener('input', e => previewText.style.background = e.target.value);

// preview local del fondo antes de subir
const bgFile = document.getElementById('bg_file');
const bgPreviewEl = document.getElementById('bgPreview');
bgFile?.addEventListener('change', e=>{
    const f = e.target.files[0];
    if(!f) return;
    if(f.size > 3*1024*1024){ alert('El archivo supera 3MB'); bgFile.value=''; return; }
    const reader = new FileReader();
    reader.onload = function(ev){
        if(bgPreviewEl) bgPreviewEl.style.backgroundImage = `url('${ev.target.result}')`;
        else {
            const d = document.createElement('div');
            d.className = 'bg-preview';
            d.style.backgroundImage = `url('${ev.target.result}')`;
            bgFile.parentNode.appendChild(d);
        }
    };
    reader.readAsDataURL(f);
});
</script>
</body>
</html>