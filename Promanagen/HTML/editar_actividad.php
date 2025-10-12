<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'No hay sesión iniciada']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Conexión a la base de datos
$conn = new mysqli("localhost", "root", "", "promanage");
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'msg' => 'Error de conexión']);
    exit;
}

// Recibir datos POST
$id = intval($_POST['id'] ?? 0);
$titulo = trim($_POST['titulo'] ?? '');
$miembro = trim($_POST['miembro'] ?? '');
$fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
$fecha_fin = trim($_POST['fecha_fin'] ?? '');

// Validar campos
if ($id <= 0 || empty($titulo) || empty($miembro) || empty($fecha_inicio)) {
    echo json_encode(['status' => 'error', 'msg' => 'Faltan datos o ID inválido']);
    exit;
}

// Validar que la actividad pertenece a un proyecto del usuario
$stmtCheck = $conn->prepare("
    SELECT a.id 
    FROM actividades a
    JOIN proyectos p ON a.proyecto_id = p.id
    WHERE a.id = ? AND p.usuario_id = ?
");
$stmtCheck->bind_param("ii", $id, $usuario_id);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();
if ($resultCheck->num_rows === 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Actividad no válida o no pertenece al usuario']);
    exit;
}
$stmtCheck->close();

// Actualizar actividad
$stmt = $conn->prepare("
    UPDATE actividades 
    SET nombre = ?, miembro = ?, fecha_inicio = ?, fecha_fin = ? 
    WHERE id = ?
");
$stmt->bind_param("ssssi", $titulo, $miembro, $fecha_inicio, $fecha_fin, $id);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'ok',
        'id' => $id,
        'title' => $titulo . " (" . $miembro . ")",
        'start' => $fecha_inicio,
        'end' => $fecha_fin
    ]);
} else {
    echo json_encode(['status' => 'error', 'msg' => 'No se pudo actualizar la actividad']);
}

$stmt->close();
$conn->close();