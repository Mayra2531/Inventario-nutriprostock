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

// Procesar formulario
if (isset($_POST['guardar'])) {
    $id = isset($_POST['id_producto']) && $_POST['id_producto'] !== '' ? intval($_POST['id_producto']) : 0;
    $cantidad = isset($_POST['cantidad']) && $_POST['cantidad'] !== '' ? intval($_POST['cantidad']) : 0;
    $sucursal = isset($_POST['sucursal']) && $_POST['sucursal'] !== '' ? $conexion->real_escape_string($_POST['sucursal']) : '';
    $nombre_usuario = isset($_POST['nombre_usuario']) && $_POST['nombre_usuario'] !== '' ? $conexion->real_escape_string(trim($_POST['nombre_usuario'])) : '';
    $id_sucursal = $_SESSION['id_sucursal'];

    if (!$id_sucursal) {
        $mensaje = "Admin debe tener una sucursal asignada para registrar salidas.";
        $tipo_mensaje = "error";
    } elseif ($id > 0 && $cantidad > 0 && !empty($sucursal) && !empty($nombre_usuario)) {
        // Obtener stock de la sucursal actual
        $stmt = $conexion->prepare("SELECT s.stock, p.nombre FROM stock_sucursales s 
                                     INNER JOIN productos p ON s.id_producto = p.id 
                                     WHERE s.id_producto = ? AND s.id_sucursal = ?");
        $stmt->bind_param("ii", $id, $id_sucursal);
        $stmt->execute();
        $buscar = $stmt->get_result();
        
        if ($buscar && $buscar->num_rows > 0) {
            $producto = $buscar->fetch_assoc();
            $stock_actual = intval($producto['stock']);
            
            // Verificar que haya suficiente stock en esta sucursal
            if ($cantidad > $stock_actual) {
                $mensaje = "No hay suficiente stock en tu sucursal. Solo hay <strong>$stock_actual unidades</strong> de {$producto['nombre']}.";
                $tipo_mensaje = "error";
            } else {
                // Actualizar stock de la sucursal
                $stmt2 = $conexion->prepare("UPDATE stock_sucursales SET stock = stock - ? WHERE id_producto = ? AND id_sucursal = ?");
                $stmt2->bind_param("iii", $cantidad, $id, $id_sucursal);
                
                if ($stmt2->execute()) {
                    // Actualizar total en productos
                    $total = $conexion->query("SELECT SUM(stock) as total FROM stock_sucursales WHERE id_producto = $id")->fetch_assoc()['total'];
                    $conexion->query("UPDATE productos SET cantidad = $total WHERE id = $id");
                    
                    // Registrar el movimiento
                    $stmt3 = $conexion->prepare("INSERT INTO movimientos (producto_id, tipo, cantidad, sucursal, nombre_usuario, id_sucursal, fecha) VALUES (?, 'salida', ?, ?, ?, ?, NOW())");
                    $stmt3->bind_param("iissi", $id, $cantidad, $sucursal, $nombre_usuario, $id_sucursal);
                    $stmt3->execute();
                    $stmt3->close();
                    
                    $nuevo_stock = $stock_actual - $cantidad;
                    $mensaje = "Salida registrada: -$cantidad unidades de <strong>{$producto['nombre']}</strong> para <strong>$sucursal</strong> por <strong>$nombre_usuario</strong>. Stock restante: $nuevo_stock";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al registrar la salida: " . $conexion->error;
                    $tipo_mensaje = "error";
                }
                $stmt2->close();
            }
        } else {
            $mensaje = "El producto no tiene stock en tu sucursal o no existe.";
            $tipo_mensaje = "error";
        }
        $stmt->close();
    } else {
        $mensaje = "Por favor completa todos los campos: producto, cantidad, sucursal y nombre.";
        $tipo_mensaje = "error";
    }
}

// Obtener lista de productos para el select
$id_sucursal = $_SESSION['id_sucursal'];
if ($id_sucursal) {
    // Usuario con sucursal: mostrar solo productos con stock en su sucursal
    $productos = $conexion->query("
        SELECT p.id, p.precio, p.nombre, COALESCE(st.stock, 0) as cantidad
        FROM productos p
        LEFT JOIN stock_sucursales st ON p.id = st.id_producto AND st.id_sucursal = $id_sucursal
        WHERE COALESCE(st.stock, 0) > 0
        ORDER BY p.nombre ASC
    ");
    // Obtener nombre de la sucursal del usuario
    $sucursal_usuario = $conexion->query("SELECT nombre_sucursal FROM sucursales WHERE id_sucursal = $id_sucursal")->fetch_assoc();
    $nombre_sucursal_usuario = $sucursal_usuario['nombre_sucursal'] ?? '';
} else {
    // Admin sin sucursal
    $productos = $conexion->query("SELECT id, precio, nombre, cantidad FROM productos ORDER BY nombre ASC");
    $nombre_sucursal_usuario = '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriProStock - Registrar Salida</title>
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

        /* Navbar */
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

        /* Main Content */
        .main-content {
            padding: 40px;
            max-width: 800px;
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

        .page-header p {
            color: #888;
            margin-top: 8px;
        }

        /* Alert Messages */
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

        /* Form Card */
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

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #f0e6f5;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #fdf8fc;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #ed1791;
            background: white;
            box-shadow: 0 0 0 4px rgba(237, 23, 145, 0.1);
        }

        .form-group .input-icon {
            position: relative;
        }

        .form-group .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #ed1791;
        }

        .form-group .input-icon input {
            padding-left: 45px;
        }

        .form-group .help-text {
            font-size: 0.85rem;
            color: #888;
            margin-top: 8px;
        }

        .product-info {
            background: #f8f4fc;
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
            display: none;
        }

        .product-info.active {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .product-info .stock-label {
            color: #666;
        }

        .product-info .stock-value {
            font-weight: 600;
            padding: 5px 15px;
            border-radius: 20px;
        }

        .product-info .stock-ok {
            background: #d4edda;
            color: #155724;
        }

        .product-info .stock-low {
            background: #fff3cd;
            color: #856404;
        }

        .product-info .stock-empty {
            background: #f8d7da;
            color: #721c24;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #ed1791 0%, #c41076 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(237, 23, 145, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(237, 23, 145, 0.4);
        }

        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }

            .main-content {
                padding: 20px;
            }

            .form-card {
                padding: 25px;
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
            <h1><i class="fas fa-arrow-up"></i> Registrar Salida</h1>
            <p>Retira productos del inventario para reducir el stock</p>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
            <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="" id="formSalida">
                <div class="form-group">
                    <label for="id_producto">Seleccionar Producto *</label>
                    <select id="id_producto" name="id_producto" required>
                        <option value="0">-- Selecciona un producto --</option>
                        <?php 
                        // Volver a consultar productos para el formulario
                        $id_sucursal = $_SESSION['id_sucursal'];
                        if ($id_sucursal) {
                            $productos2 = $conexion->query("
                                SELECT p.id, p.precio, p.nombre, COALESCE(st.stock, 0) as cantidad
                                FROM productos p
                                LEFT JOIN stock_sucursales st ON p.id = st.id_producto AND st.id_sucursal = $id_sucursal
                                WHERE COALESCE(st.stock, 0) > 0
                                ORDER BY p.nombre ASC
                            ");
                        } else {
                            $productos2 = $conexion->query("SELECT id, precio, nombre, cantidad FROM productos ORDER BY nombre ASC");
                        }
                        
                        if ($productos2 && $productos2->num_rows > 0): 
                            while ($prod = $productos2->fetch_assoc()): 
                        ?>
                            <option value="<?php echo intval($prod['id']); ?>" data-stock="<?php echo intval($prod['cantidad']); ?>">
                                <?php echo htmlspecialchars($prod['nombre']); ?> ($<?php echo number_format($prod['precio'], 2); ?>) - Stock: <?php echo intval($prod['cantidad']); ?>
                            </option>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                            <option value="0" disabled>No hay productos con stock en tu sucursal</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="cantidad">Cantidad a Retirar *</label>
                    <div class="input-icon">
                        <i class="fas fa-minus"></i>
                        <input type="number" id="cantidad" name="cantidad" min="1" max="99999" placeholder="Ej: 200" required>
                    </div>
                    <p class="help-text" id="helpText">Ingresa la cantidad de unidades que salen del inventario</p>
                </div>

                <div class="form-group">
                    <label for="sucursal">Sucursal *</label>
                    <?php if ($nombre_sucursal_usuario): ?>
                        <input type="text" id="sucursal" name="sucursal" value="<?php echo htmlspecialchars($nombre_sucursal_usuario); ?>" readonly style="background: #f5f5f5; cursor: not-allowed;">
                    <?php else: ?>
                        <select id="sucursal" name="sucursal" required>
                            <option value="">Seleccione una sucursal</option>
                            <?php
                            $sucursales = $conexion->query("SELECT nombre_sucursal FROM sucursales ORDER BY nombre_sucursal");
                            while($suc = $sucursales->fetch_assoc()):
                            ?>
                            <option value="<?php echo htmlspecialchars($suc['nombre_sucursal']); ?>">
                                <?php echo htmlspecialchars($suc['nombre_sucursal']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="nombre_usuario">Nombre de la persona que realiza la salida *</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="nombre_usuario" name="nombre_usuario" placeholder="Escriba su nombre" required>
                    </div>
                </div>

                <button type="submit" name="guardar" class="btn-submit">
                    <i class="fas fa-arrow-up"></i>
                    Registrar Salida
                </button>
            </form>
        </div>
    </main>
</body>
</html>
