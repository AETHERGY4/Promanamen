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
$titulo = trim($_POST['titulo'] ?? '');
$miembro = trim($_POST['miembro'] ?? '');
$fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
$fecha_fin = trim($_POST['fecha_fin'] ?? '');
$proyecto_id = intval($_POST['proyecto_id'] ?? 0);

// Validar campos
if (empty($titulo) || empty($miembro) || empty($fecha_inicio) || $proyecto_id <= 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Faltan datos o proyecto inválido']);
    exit;
}

// Validar que el proyecto pertenece al usuario
$stmtCheck = $conn->prepare("SELECT id FROM proyectos WHERE id = ? AND usuario_id = ?");
$stmtCheck->bind_param("ii", $proyecto_id, $usuario_id);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();
if ($resultCheck->num_rows === 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Proyecto no válido o no pertenece al usuario']);
    exit;
}
$stmtCheck->close();

// Insertar actividad
$stmt = $conn->prepare("
    INSERT INTO actividades (proyecto_id, nombre, miembro, fecha_inicio, fecha_fin) 
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("issss", $proyecto_id, $titulo, $miembro, $fecha_inicio, $fecha_fin);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'ok',
        'id' => $stmt->insert_id,
        'title' => $titulo . " (" . $miembro . ")",
        'start' => $fecha_inicio,
        'end' => $fecha_fin
    ]);
} else {
    echo json_encode(['status' => 'error', 'msg' => 'No se pudo guardar la actividad']);
}

$stmt->close();
$conn->close();