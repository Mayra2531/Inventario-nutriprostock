<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header("Location: login.php");
    exit();
}

include("conexion.php");

$mensaje = '';
$tipo_mensaje = '';

// Obtener usuario_id de la sesión o buscarlo por nombre de usuario
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
} else {
    // Buscar ID por nombre de usuario si no está en sesión
    $nombre_usuario = $_SESSION['usuario'] ?? $_SESSION['nombre'];
    $stmt = $conexion->prepare("SELECT Id FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $nombre_usuario);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $usuario_id = $result['Id'] ?? 0;
    $_SESSION['usuario_id'] = $usuario_id; // Guardarlo para futuras visitas
}

// Obtener datos actuales del usuario
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE Id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

// Si no se encuentra el usuario, redirigir
if (!$usuario) {
    header("Location: logout.php");
    exit();
}

// Procesar actualización de perfil
if (isset($_POST['actualizar'])) {
    $nombre = $conexion->real_escape_string(trim($_POST['nombre']));
    $email = $conexion->real_escape_string(trim($_POST['email']));
    
    // Procesar foto si se subió una nueva
    $foto_nombre = $usuario['foto']; // Mantener foto actual por defecto
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['foto'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($extension, $extensiones_permitidas)) {
            if ($archivo['size'] <= 5000000) { // 5MB máximo
                $foto_nombre = 'perfil_' . $usuario_id . '_' . time() . '.' . $extension;
                $ruta_destino = 'uploads/' . $foto_nombre;
                
                // Eliminar foto anterior si existe
                if (!empty($usuario['foto']) && file_exists('uploads/' . $usuario['foto'])) {
                    unlink('uploads/' . $usuario['foto']);
                }
                
                if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                    $mensaje = "Error al subir la imagen.";
                    $tipo_mensaje = "error";
                    $foto_nombre = $usuario['foto'];
                }
            } else {
                $mensaje = "La imagen es muy grande. Máximo 5MB.";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Formato no permitido. Use JPG, PNG, GIF o WEBP.";
            $tipo_mensaje = "error";
        }
    }
    
    // Actualizar en base de datos si no hay errores
    if (empty($mensaje)) {
        $stmt = $conexion->prepare("UPDATE usuarios SET nombre_mostrar = ?, email = ?, foto = ? WHERE Id = ?");
        $stmt->bind_param("sssi", $nombre, $email, $foto_nombre, $usuario_id);
        
        if ($stmt->execute()) {
            $_SESSION['nombre'] = $nombre;
            $_SESSION['foto'] = $foto_nombre;
            $mensaje = "Perfil actualizado correctamente.";
            $tipo_mensaje = "success";
            
            // Recargar datos del usuario
            $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE Id = ?");
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $usuario = $stmt->get_result()->fetch_assoc();
        } else {
            $mensaje = "Error al actualizar: " . $conexion->error;
            $tipo_mensaje = "error";
        }
    }
}

// Procesar cambio de contraseña
if (isset($_POST['cambiar_password'])) {
    $password_actual = $_POST['password_actual'];
    $password_nuevo = $_POST['password_nuevo'];
    $password_confirmar = $_POST['password_confirmar'];
    
    // Verificar contraseña actual
    if (password_verify($password_actual, $usuario['password']) || $password_actual === $usuario['password']) {
        if ($password_nuevo === $password_confirmar) {
            if (strlen($password_nuevo) >= 6) {
                $password_hash = password_hash($password_nuevo, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare("UPDATE usuarios SET password = ? WHERE Id = ?");
                $stmt->bind_param("si", $password_hash, $usuario_id);
                
                if ($stmt->execute()) {
                    $mensaje = "Contraseña actualizada correctamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al cambiar la contraseña.";
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "La contraseña debe tener al menos 6 caracteres.";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Las contraseñas nuevas no coinciden.";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "La contraseña actual es incorrecta.";
        $tipo_mensaje = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriProStock - Mi Perfil</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #fdf2f8 0%, #f5e6ff 50%, #fce7f3 100%);
            min-height: 100vh;
        }

        .navbar {
            background: white;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 15px rgba(69, 35, 73, 0.08);
        }

        .navbar .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .navbar .brand-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #ed1791 0%, #452349 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
        }

        .navbar .brand-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #452349;
        }

        .navbar .btn-back {
            background: #f8e8f0;
            color: #ed1791;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar .btn-back:hover {
            background: #ed1791;
            color: white;
        }

        .main-content {
            padding: 40px;
            max-width: 900px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 1.8rem;
            color: #452349;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header h1 i {
            color: #ed1791;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        .profile-card, .form-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(69, 35, 73, 0.08);
        }

        .profile-card {
            text-align: center;
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            position: relative;
            overflow: hidden;
            border: 4px solid #f0e6f5;
        }

        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-photo .default-avatar {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #ed1791 0%, #452349 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
            font-weight: 600;
        }

        .profile-card h2 {
            color: #452349;
            margin-bottom: 5px;
        }

        .profile-card .role {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .profile-card .email {
            color: #666;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .form-card h3 {
            color: #452349;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0e6f5;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-card h3 i {
            color: #ed1791;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            color: #452349;
            margin-bottom: 8px;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #f0e6f5;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #fdf8fc;
        }

        .form-group input:focus {
            outline: none;
            border-color: #ed1791;
            background: white;
        }

        .form-group input[type="file"] {
            padding: 10px;
            border: 2px dashed #f0e6f5;
            border-radius: 10px;
            width: 100%;
            cursor: pointer;
            background: #fdf8fc;
        }

        .form-group input[type="file"]:hover {
            border-color: #ed1791;
        }

        .photo-preview {
            margin-top: 10px;
            text-align: center;
        }

        .photo-preview img {
            max-width: 100px;
            max-height: 100px;
            border-radius: 10px;
            border: 2px solid #f0e6f5;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #ed1791 0%, #c41076 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(237, 23, 145, 0.3);
        }

        .section-divider {
            margin: 30px 0;
            border: none;
            border-top: 2px solid #f0e6f5;
        }

        .password-section h3 {
            color: #452349;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }

            .navbar {
                padding: 15px 20px;
            }

            .main-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="brand">
            <div class="brand-icon">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <span class="brand-name">NutriProStock</span>
        </a>
        <a href="index.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Volver al Dashboard
        </a>
    </nav>

    <main class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-user-cog"></i> Mi Perfil</h1>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
            <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <div class="profile-grid">
            <div class="profile-card">
                <div class="profile-photo">
                    <?php if (!empty($usuario['foto']) && file_exists('uploads/' . $usuario['foto'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($usuario['foto']); ?>" alt="Foto de perfil">
                    <?php else: ?>
                        <div class="default-avatar">
                            <?php echo strtoupper(substr($usuario['nombre_mostrar'] ?? $usuario['usuario'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <h2><?php echo htmlspecialchars($usuario['nombre_mostrar'] ?? $usuario['usuario']); ?></h2>
                <p class="role"><i class="fas fa-shield-alt"></i> <?php echo ucfirst($usuario['rol'] ?? 'Usuario'); ?></p>
                <p class="email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($usuario['email'] ?? 'Sin email'); ?></p>
            </div>

            <div class="form-card">
                <h3><i class="fas fa-edit"></i> Editar Información</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nombre">Nombre para Mostrar</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre_mostrar'] ?? $usuario['usuario']); ?>" required>
                        <small style="color: #888; font-size: 0.8rem;">Este nombre se muestra en el sistema. Tu usuario de inicio de sesión no cambia.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" placeholder="correo@ejemplo.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="foto">Foto de Perfil</label>
                        <input type="file" id="foto" name="foto" accept="image/*" onchange="previewImage(this)">
                        <div class="photo-preview" id="preview"></div>
                    </div>
                    
                    <button type="submit" name="actualizar" class="btn-submit">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                </form>

                <hr class="section-divider">

                <div class="password-section">
                    <h3><i class="fas fa-lock"></i> Cambiar Contraseña</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label for="password_actual">Contraseña Actual</label>
                            <input type="password" id="password_actual" name="password_actual" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_nuevo">Nueva Contraseña</label>
                            <input type="password" id="password_nuevo" name="password_nuevo" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirmar">Confirmar Nueva Contraseña</label>
                            <input type="password" id="password_confirmar" name="password_confirmar" required minlength="6">
                        </div>
                        
                        <button type="submit" name="cambiar_password" class="btn-submit">
                            <i class="fas fa-key"></i>
                            Cambiar Contraseña
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Vista previa">';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.innerHTML = '';
            }
        }
    </script>
</body>
</html>
