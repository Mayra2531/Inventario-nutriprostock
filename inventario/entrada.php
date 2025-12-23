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

// Procesar formulario
if (isset($_POST['guardar'])) {
    $id = intval($_POST['id_producto']);
    $cantidad = intval($_POST['cantidad']);
    $id_sucursal = $_SESSION['id_sucursal'];

    if (!$id_sucursal) {
        $mensaje = "Admin debe tener una sucursal asignada para registrar entradas.";
        $tipo_mensaje = "error";
    } elseif ($id > 0 && $cantidad > 0) {
        // Verificar que el producto existe
        $check = $conexion->query("SELECT nombre FROM productos WHERE id = $id");
        
        if ($check && $check->num_rows > 0) {
            $producto = $check->fetch_assoc();
            
            // Actualizar stock en stock_sucursales
            $check_stock = $conexion->query("SELECT id_stock, stock FROM stock_sucursales WHERE id_producto = $id AND id_sucursal = $id_sucursal");
            
            if ($check_stock && $check_stock->num_rows > 0) {
                // Ya existe, actualizar
                $sql = "UPDATE stock_sucursales SET stock = stock + $cantidad WHERE id_producto = $id AND id_sucursal = $id_sucursal";
            } else {
                // No existe, crear
                $sql = "INSERT INTO stock_sucursales (id_producto, id_sucursal, stock) VALUES ($id, $id_sucursal, $cantidad)";
            }
            
            if ($conexion->query($sql)) {
                // Actualizar total en productos (suma de todas las sucursales)
                $total = $conexion->query("SELECT SUM(stock) as total FROM stock_sucursales WHERE id_producto = $id")->fetch_assoc()['total'];
                $conexion->query("UPDATE productos SET cantidad = $total WHERE id = $id");
                
                // Registrar el movimiento
                $conexion->query("INSERT INTO movimientos (producto_id, tipo, cantidad, id_sucursal, fecha) 
                                  VALUES ($id, 'entrada', $cantidad, $id_sucursal, NOW())");
                
                $mensaje = "Entrada registrada: +$cantidad unidades de <strong>{$producto['nombre']}</strong>";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al registrar la entrada: " . $conexion->error;
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "El producto con ID $id no existe.";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "Por favor ingresa valores válidos.";
        $tipo_mensaje = "error";
    }
}

// Obtener lista de productos para el select
$productos = $conexion->query("SELECT id, precio, nombre, cantidad FROM productos ORDER BY nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriProStock - Registrar Entrada</title>
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
            display: block;
        }

        .product-info span {
            color: #452349;
            font-weight: 500;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #28a745 0%, #20863a 100%);
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
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
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
            <h1><i class="fas fa-arrow-down"></i> Registrar Entrada</h1>
            <p>Ingresa productos al inventario para aumentar el stock</p>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
            <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <div class="form-group">
                    <label for="id_producto">Seleccionar Producto *</label>
                    <select id="id_producto" name="id_producto" required onchange="mostrarInfo()">
                        <option value="">-- Selecciona un producto --</option>
                        <?php if ($productos && $productos->num_rows > 0): ?>
                            <?php while ($prod = $productos->fetch_assoc()): ?>
                            <option value="<?php echo $prod['id']; ?>" data-stock="<?php echo $prod['cantidad']; ?>" data-precio="<?php echo $prod['precio']; ?>">
                                <?php echo htmlspecialchars($prod['nombre']); ?> ($<?php echo number_format($prod['precio'], 2); ?>)
                            </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                    <div class="product-info" id="productInfo">
                        <i class="fas fa-info-circle"></i> Stock actual: <span id="stockActual">0</span> unidades
                    </div>
                </div>

                <div class="form-group">
                    <label for="cantidad">Cantidad a Ingresar *</label>
                    <div class="input-icon">
                        <i class="fas fa-plus"></i>
                        <input type="number" id="cantidad" name="cantidad" min="1" placeholder="Ej: 50" required>
                    </div>
                    <p class="help-text">Ingresa la cantidad de unidades que entran al inventario</p>
                </div>

                <button type="submit" name="guardar" class="btn-submit">
                    <i class="fas fa-check"></i>
                    Registrar Entrada
                </button>
            </form>
        </div>
    </main>

    <script>
        function mostrarInfo() {
            const select = document.getElementById('id_producto');
            const info = document.getElementById('productInfo');
            const stockSpan = document.getElementById('stockActual');
            
            if (select.value) {
                const option = select.options[select.selectedIndex];
                stockSpan.textContent = option.dataset.stock;
                info.classList.add('active');
            } else {
                info.classList.remove('active');
            }
        }
    </script>
</body>
</html>
