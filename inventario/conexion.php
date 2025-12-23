<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "inventario_db";

// Crear conexión
$conexion = new mysqli($servername, $username, $password, $database);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Establecer charset
$conexion->set_charset("utf8mb4");
?>
