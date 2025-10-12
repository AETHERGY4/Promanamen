<?php
session_start();
if (!isset($_SESSION['usuario_id'])) exit;

header('Content-Type: application/json');

// Obtenemos el ID de la actividad a eliminar
$id = intval($_POST['id'] ?? 0);
if($id <= 0) {
    echo json_encode(['status'=>'error', 'msg'=>'ID inválido']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "promanage");
if ($conn->connect_error) {
    echo json_encode(['status'=>'error', 'msg'=>'Conexión fallida']);
    exit;
}

// Eliminamos la actividad
$stmt = $conn->prepare("DELETE FROM actividades WHERE id=?");
$stmt->bind_param("i", $id);

if($stmt->execute()) {
    echo json_encode(['status'=>'ok']);
} else {
    echo json_encode(['status'=>'error', 'msg'=>$stmt->error]);
}

$stmt->close();
$conn->close();
?>