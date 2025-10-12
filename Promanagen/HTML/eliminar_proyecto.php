<?php
session_start();
if (!isset($_SESSION['usuario_id'])) header("Location: login.html");

$proyecto_id = $_GET['id'];
$conn = new mysqli("localhost", "root", "", "promanage");
$conn->query("DELETE FROM proyectos WHERE id=$proyecto_id");
$conn->query("DELETE FROM archivos WHERE proyecto_id=$proyecto_id"); // borrar archivos también
$conn->close();

header("Location: /Promanagen/HTML/index.php");
exit;
