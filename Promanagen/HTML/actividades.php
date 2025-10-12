<?php
session_start();
if (!isset($_SESSION['usuario_id'])) exit;

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$usuario_id = $_SESSION['usuario_id'];

$conn = new mysqli("localhost", "root", "", "promanage");
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

// Traer actividades de todos los proyectos del usuario
$stmt = $conn->prepare("
    SELECT a.id, a.nombre, a.miembro, a.fecha_inicio, a.fecha_fin, a.estado, p.nombre AS proyecto
    FROM actividades a
    JOIN proyectos p ON a.proyecto_id = p.id
    WHERE p.usuario_id = ?
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$actividades = [];
while ($row = $result->fetch_assoc()) {
    $color = $row['estado'] === 'completada' ? '#238636' : '#d73a49';
    $actividades[] = [
        'id' => $row['id'],
        'title' => $row['nombre'] . " (" . $row['miembro'] . ")",
        'start' => $row['fecha_inicio'],
        'end' => $row['fecha_fin'],
        'project' => $row['proyecto'],
        'estado' => $row['estado'],
        'backgroundColor' => $color
    ];
}

$stmt->close();
$conn->close();

echo json_encode($actividades);
