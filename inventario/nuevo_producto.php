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

$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario de nuevo producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_producto'])) {
    $precio = floatval($_POST['precio']);
    $nombre_prod = $conexion->real_escape_string(trim($_POST['nombre']));
    $categoria = $conexion->real_escape_string(trim($_POST['categoria']));
    $fecha_ingreso = date('Y-m-d');

    // Insertar producto
    $sql = "INSERT INTO productos (precio, nombre, categoria, cantidad, fecha_ingreso) 
            VALUES (?, ?, ?, 0, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("dsss", $precio, $nombre_prod, $categoria, $fecha_ingreso);

    if ($stmt->execute()) {
        $nuevo_id = $conexion->insert_id;
        
        // Admin: crear stock individual para cada sucursal
        $sucursales = $conexion->query("SELECT id_sucursal, nombre_sucursal FROM sucursales");
        $stmt2 = $conexion->prepare("INSERT INTO stock_sucursales (id_producto, id_sucursal, stock) VALUES (?, ?, ?)");
        $total_stock = 0;
        $num_sucursales = 0;
        
        while ($suc = $sucursales->fetch_assoc()) {
            $id_suc = $suc['id_sucursal'];
            $nombre_suc = str_replace(' ', '_', $suc['nombre_sucursal']);
            $cantidad_sucursal = isset($_POST['cantidad_' . $id_suc]) ? intval($_POST['cantidad_' . $id_suc]) : 0;
            
            $stmt2->bind_param("iii", $nuevo_id, $id_suc, $cantidad_sucursal);
            $stmt2->execute();
            $total_stock += $cantidad_sucursal;
            $num_sucursales++;
        }
        $stmt2->close();
        
        // Actualizar cantidad total en productos
        $conexion->query("UPDATE productos SET cantidad = $total_stock WHERE id = $nuevo_id");
        
        $mensaje = "Producto agregado exitosamente en $num_sucursales sucursales con un total de $total_stock unidades.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al agregar el producto: " . $conexion->error;
        $tipo_mensaje = "error";
    }
    $stmt->close();
}

// Obtener categorías
$categorias = $conexion->query("SELECT nombre FROM categorias ORDER BY nombre");

// Obtener sucursales para el formulario
$sucursales_form = $conexion->query("SELECT id_sucursal, nombre_sucursal FROM sucursales ORDER BY nombre_sucursal");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriProStock - Nuevo Producto</title>
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
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .navbar .btn-back:hover {
            background: #ed1791;
            color: white;
        }

        .main-content {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            color: #452349;
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .form-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(69, 35, 73, 0.08);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-size: 0.95rem;
            font-weight: 500;
            color: #452349;
            margin-bottom: 10px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 15px;
            border: 2px solid #f0e6f5;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #fdf8fc;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #ed1791;
            background: white;
            box-shadow: 0 0 0 4px rgba(237, 23, 145, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #ed1791 0%, #452349 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(237, 23, 145, 0.3);
        }

        .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .info-box i {
            color: #2196F3;
            margin-right: 8px;
        }

        .sucursales-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .sucursal-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }

        .sucursal-item label {
            font-size: 0.9rem;
            color: #452349;
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }

        .sucursal-item input {
            width: 100%;
            padding: 10px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .section-title {
            color: #452349;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
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
            <h1><i class="fas fa-plus-circle"></i> Agregar Nuevo Producto</h1>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
            <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>Nota:</strong> Ingresa la cantidad específica para cada sucursal.
        </div>

        <div class="form-card">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nombre">Nombre del Producto *</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Ej: Proteína Whey" required>
                </div>

                <div class="form-group">
                    <label for="precio">Precio ($) *</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label for="categoria">Categoría *</label>
                    <select id="categoria" name="categoria" required>
                        <option value="">Seleccione una categoría</option>
                        <?php 
                        if ($categorias && $categorias->num_rows > 0):
                            while ($cat = $categorias->fetch_assoc()): 
                        ?>
                            <option value="<?php echo htmlspecialchars($cat['nombre']); ?>">
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php 
                            endwhile;
                        endif; 
                        ?>
                    </select>
                </div>

                <div class="section-title">
                    <i class="fas fa-store"></i> Cantidad por Sucursal
                </div>
                
                <div class="sucursales-grid">
                    <?php 
                    if ($sucursales_form && $sucursales_form->num_rows > 0):
                        while ($suc = $sucursales_form->fetch_assoc()): 
                    ?>
                        <div class="sucursal-item">
                            <label for="cantidad_<?php echo $suc['id_sucursal']; ?>">
                                <?php echo htmlspecialchars($suc['nombre_sucursal']); ?>
                            </label>
                            <input type="number" 
                                   id="cantidad_<?php echo $suc['id_sucursal']; ?>" 
                                   name="cantidad_<?php echo $suc['id_sucursal']; ?>" 
                                   min="0" 
                                   value="0" 
                                   placeholder="0">
                        </div>
                    <?php 
                        endwhile;
                    endif; 
                    ?>
                </div>

                <button type="submit" name="agregar_producto" class="btn-submit">
                    <i class="fas fa-plus-circle"></i>
                    Agregar Producto
                </button>
            </form>
        </div>
    </main>
</body>
</html>
