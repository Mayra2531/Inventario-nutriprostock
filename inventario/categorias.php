<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header("Location: login.php");
    exit();
}

// Verificar que sea admin o subadmin
if (($_SESSION['rol'] ?? 'usuario') !== 'admin' && ($_SESSION['rol'] ?? 'usuario') !== 'subadmin') {
    header("Location: index.php");
    exit();
}

include("conexion.php");

// Crear tabla categorias si no existe
$conexion->query("CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$mensaje = '';
$tipo_mensaje = '';

// Procesar agregar categoría
if (isset($_POST['agregar'])) {
    $nombre = $conexion->real_escape_string(trim($_POST['nombre']));
    $descripcion = $conexion->real_escape_string(trim($_POST['descripcion']));
    
    if (!empty($nombre)) {
        // Verificar si ya existe
        $check = $conexion->query("SELECT id FROM categorias WHERE nombre = '$nombre'");
        if ($check->num_rows > 0) {
            $mensaje = "La categoría ya existe.";
            $tipo_mensaje = "error";
        } else {
            if ($conexion->query("INSERT INTO categorias (nombre, descripcion) VALUES ('$nombre', '$descripcion')")) {
                $mensaje = "Categoría <strong>$nombre</strong> agregada exitosamente.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al agregar: " . $conexion->error;
                $tipo_mensaje = "error";
            }
        }
    }
}

// Procesar eliminar categoría
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    if ($conexion->query("DELETE FROM categorias WHERE id = $id")) {
        $mensaje = "Categoría eliminada.";
        $tipo_mensaje = "success";
    }
}

// Obtener categorías
$categorias = $conexion->query("SELECT * FROM categorias ORDER BY nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriProStock - Categorías</title>
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
            max-width: 1000px;
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

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        .form-card, .table-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(69, 35, 73, 0.08);
        }

        .form-card h3, .table-card h3 {
            color: #452349;
            margin-bottom: 20px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #f0e6f5;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #fdf8fc;
        }

        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #ed1791;
            background: white;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: linear-gradient(135deg, #452349 0%, #5a3060 100%);
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
            font-size: 0.9rem;
        }

        thead th:first-child {
            border-radius: 10px 0 0 0;
        }

        thead th:last-child {
            border-radius: 0 10px 0 0;
        }

        tbody tr {
            border-bottom: 1px solid #f0e6f5;
        }

        tbody tr:hover {
            background: #fdf8fc;
        }

        tbody td {
            padding: 12px 15px;
            color: #333;
        }

        .btn-delete {
            background: #ffe6e6;
            color: #d63031;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background: #d63031;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #888;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
        }

        .categoria-badge {
            background: #f0e6f5;
            color: #452349;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .content-grid {
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
            <h1><i class="fas fa-tags"></i> Gestión de Categorías</h1>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
            <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="form-card">
                <h3><i class="fas fa-plus-circle"></i> Nueva Categoría</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="nombre">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" placeholder="Ej: Suplementos" required>
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" placeholder="Descripción opcional..."></textarea>
                    </div>
                    <button type="submit" name="agregar" class="btn-submit">
                        <i class="fas fa-save"></i> Guardar Categoría
                    </button>
                </form>
            </div>

            <div class="table-card">
                <h3><i class="fas fa-list"></i> Categorías Registradas</h3>
                <?php if ($categorias && $categorias->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($cat = $categorias->fetch_assoc()): ?>
                        <tr>
                            <td><span class="categoria-badge"><?php echo htmlspecialchars($cat['nombre']); ?></span></td>
                            <td><?php echo htmlspecialchars($cat['descripcion'] ?? '-'); ?></td>
                            <td>
                                <button class="btn-delete" onclick="eliminar(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['nombre']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No hay categorías</h3>
                    <p>Agrega tu primera categoría</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function eliminar(id, nombre) {
            if (confirm('¿Eliminar la categoría "' + nombre + '"?')) {
                window.location.href = 'categorias.php?eliminar=' + id;
            }
        }
    </script>
</body>
</html>
