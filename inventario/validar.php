<?php
session_start();
include("conexion.php");

// Sanitizar entrada
$usuario = $conexion->real_escape_string(trim($_POST['usuario']));
$password = $_POST['password'];

// Consulta preparada para mayor seguridad
$sql = "SELECT * FROM usuarios WHERE usuario = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();
    
    // Verificar contraseña (usar password_verify si está hasheada)
    // Si la contraseña está en texto plano (no recomendado):
    if ($password === $fila['password'] || password_verify($password, $fila['password']) || (isset($fila['contrasena']) && $password === $fila['contrasena'])) {
        // Crear sesión
        $_SESSION['usuario_id'] = $fila['Id'];
        $_SESSION['usuario'] = $fila['usuario'];
        $_SESSION['logueado'] = true;
        $_SESSION['nombre'] = !empty($fila['nombre_mostrar']) ? $fila['nombre_mostrar'] : $fila['usuario'];
        $_SESSION['foto'] = $fila['foto'] ?? '';
        $_SESSION['rol'] = $fila['rol'] ?? 'usuario';
        $_SESSION['id_sucursal'] = $fila['id_sucursal'] ?? null;
        
        // Redirigir al dashboard
        header("Location: index.php");
        exit();
    } else {
        // Contraseña incorrecta
        header("Location: login.php?error=1");
        exit();
    }
} else {
    // Usuario no encontrado
    header("Location: login.php?error=1");
    exit();
}

$stmt->close();
$conexion->close();
?>
