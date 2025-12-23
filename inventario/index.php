<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header("Location: login.php");
    exit();
}

include("conexion.php");

$nombre = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$id_sucursal = $_SESSION['id_sucursal'];
$usuario_tipo = $_SESSION['rol'] ?? 'usuario';

// Obtener nombre de la sucursal
if ($id_sucursal) {
    $sucursal_info = $conexion->query("SELECT nombre_sucursal FROM sucursales WHERE id_sucursal = $id_sucursal")->fetch_assoc();
    $nombre_sucursal = $sucursal_info['nombre_sucursal'] ?? 'Sin sucursal';
} else {
    $nombre_sucursal = 'Todas las sucursales';
}

// Consultas para estadísticas
if ($id_sucursal) {
    // Usuario con sucursal asignada
    $total_productos = $conexion->query("SELECT COUNT(*) as total FROM stock_sucursales WHERE id_sucursal = $id_sucursal AND stock > 0")->fetch_assoc()['total'];
    $stock_bajo = $conexion->query("SELECT COUNT(*) as total FROM stock_sucursales WHERE id_sucursal = $id_sucursal AND stock > 0 AND stock <= 10")->fetch_assoc()['total'];
    
    $entradas_mes = $conexion->query("
        SELECT COALESCE(SUM(cantidad), 0) as total 
        FROM movimientos 
        WHERE tipo = 'entrada' 
        AND id_sucursal = $id_sucursal
        AND MONTH(fecha) = MONTH(CURRENT_DATE()) 
        AND YEAR(fecha) = YEAR(CURRENT_DATE())
    ")->fetch_assoc()['total'];
    
    $salidas_mes = $conexion->query("
        SELECT COALESCE(SUM(cantidad), 0) as total 
        FROM movimientos 
        WHERE tipo = 'salida' 
        AND id_sucursal = $id_sucursal
        AND MONTH(fecha) = MONTH(CURRENT_DATE()) 
        AND YEAR(fecha) = YEAR(CURRENT_DATE())
    ")->fetch_assoc()['total'];
} else {
    // Admin sin sucursal - ve totales globales
    $total_productos = $conexion->query("SELECT COUNT(DISTINCT id_producto) as total FROM stock_sucursales WHERE stock > 0")->fetch_assoc()['total'];
    $stock_bajo = $conexion->query("SELECT COUNT(*) as total FROM stock_sucursales WHERE stock > 0 AND stock <= 10")->fetch_assoc()['total'];
    
    $entradas_mes = $conexion->query("
        SELECT COALESCE(SUM(cantidad), 0) as total 
        FROM movimientos 
        WHERE tipo = 'entrada' 
        AND MONTH(fecha) = MONTH(CURRENT_DATE()) 
        AND YEAR(fecha) = YEAR(CURRENT_DATE())
    ")->fetch_assoc()['total'];
    
    $salidas_mes = $conexion->query("
        SELECT COALESCE(SUM(cantidad), 0) as total 
        FROM movimientos 
        WHERE tipo = 'salida' 
        AND MONTH(fecha) = MONTH(CURRENT_DATE()) 
        AND YEAR(fecha) = YEAR(CURRENT_DATE())
    ")->fetch_assoc()['total'];
}

// Alertas de productos con stock bajo
$limite = 10;
$usuario_rol = $_SESSION['rol'] ?? 'usuario';

if ($usuario_rol == 'admin' || $usuario_rol == 'subadmin') {
    // Admin y subadmin ven alertas de TODAS las sucursales
    $sql = "
        SELECT p.nombre, s.nombre_sucursal AS sucursal, st.stock, p.precio, p.categoria
        FROM stock_sucursales st
        JOIN productos p ON p.id = st.id_producto
        JOIN sucursales s ON s.id_sucursal = st.id_sucursal
        WHERE st.stock <= $limite
        ORDER BY st.stock ASC
    ";
    $alertas = $conexion->query($sql);
} else {
    // Usuario normal ve solo alertas de su sucursal
    $id_sucursal = $_SESSION['id_sucursal'];
    if ($id_sucursal) {
        $sql = "
            SELECT p.nombre, st.stock, p.precio, p.categoria
            FROM stock_sucursales st
            JOIN productos p ON p.id = st.id_producto
            WHERE st.id_sucursal = $id_sucursal AND st.stock <= $limite
            ORDER BY st.stock ASC
        ";
        $alertas = $conexion->query($sql);
    } else {
        $alertas = null;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriProStock - Panel de Inventario</title>
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

        .navbar .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .navbar .user-info {
            text-align: right;
        }

        .navbar .user-info .name {
            font-weight: 600;
            color: #452349;
        }

        .navbar .user-info .role {
            font-size: 0.8rem;
            color: #888;
        }

        .navbar .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #ed1791 0%, #c41076 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .navbar .btn-logout {
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

        .navbar .btn-logout:hover {
            background: #ed1791;
            color: white;
        }

        /* Main Content */
        .main-content {
            padding: 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .welcome-section {
            margin-bottom: 40px;
        }

        .welcome-section h1 {
            font-size: 2rem;
            color: #452349;
            margin-bottom: 8px;
        }

        .welcome-section p {
            color: #888;
            font-size: 1rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(69, 35, 73, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(69, 35, 73, 0.12);
        }

        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .stat-card.patients .icon {
            background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
            color: white;
        }

        .stat-card.appointments .icon {
            background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
            color: white;
        }

        .stat-card.plans .icon {
            background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
            color: white;
        }

        .stat-card.progress .icon {
            background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
            color: white;
        }

        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #452349;
            margin-bottom: 5px;
        }

        .stat-card .label {
            color: #888;
            font-size: 0.95rem;
        }

        /* Quick Actions */
        .section-title {
            font-size: 1.3rem;
            color: #452349;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(69, 35, 73, 0.06);
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 2px solid transparent;
        }

        .action-card:hover {
            border-color: #ed1791;
            transform: translateY(-3px);
        }

        .action-card i {
            font-size: 2rem;
            color: #ed1791;
            margin-bottom: 15px;
        }

        .action-card h3 {
            color: #452349;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .action-card p {
            color: #999;
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-wrap: wrap;
                gap: 15px;
            }

            .main-content {
                padding: 20px;
            }

            .welcome-section h1 {
                font-size: 1.5rem;
            }
        }

        /* Alertas de Stock */
        .alerts-section {
            margin-bottom: 30px;
        }

        .alerts-container {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(69, 35, 73, 0.06);
        }

        .alert-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background: #fff5f5;
            border-left: 4px solid #ed1791;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .alert-item:last-child {
            margin-bottom: 0;
        }

        .alert-item:hover {
            background: #ffe8f5;
        }

        .alert-item .alert-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #ed1791 0%, #c41076 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .alert-item .alert-content {
            flex: 1;
        }

        .alert-item .alert-content strong {
            color: #452349;
        }

        .alert-item .alert-content span {
            color: #666;
            font-size: 0.9rem;
        }

        .alert-item .stock-badge {
            background: #ed1791;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .no-alerts {
            text-align: center;
            padding: 20px;
            color: #888;
        }

        .no-alerts i {
            font-size: 2rem;
            color: #28a745;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="brand">
            <div class="brand-icon">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <span class="brand-name">NutriProStock</span>
        </div>
        <div class="user-menu">
            <div class="user-info">
                <div class="name"><?php echo htmlspecialchars($nombre); ?></div>
                <div class="role">Administrador</div>
            </div>
            <a href="perfil.php" class="user-avatar" title="Mi Perfil" style="text-decoration: none;">
                <?php if (!empty($_SESSION['foto']) && file_exists('uploads/' . $_SESSION['foto'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($_SESSION['foto']); ?>" alt="Foto" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <?php echo strtoupper(substr($nombre, 0, 1)); ?>
                <?php endif; ?>
            </a>
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i>
                Salir
            </a>
        </div>
    </nav>

    <main class="main-content">
        <div class="welcome-section">
            <h1>¡Hola, <?php echo htmlspecialchars($nombre); ?>! 👋</h1>
            <p>Bienvenido al panel de inventario de <strong><?php echo htmlspecialchars($nombre_sucursal); ?></strong>. Aquí tienes un resumen del estado actual.</p>
        </div>

        <?php if ($alertas && $alertas->num_rows > 0): ?>
        <div class="alerts-section">
            <h2 class="section-title">
                <i class="fas fa-bell"></i> 
                <?php echo ($usuario_tipo == 'admin' || $usuario_tipo == 'subadmin') ? 'Alertas Críticas de Todas las Sucursales (Stock < 5)' : 'Alertas de Stock Bajo'; ?>
            </h2>
            <div class="alerts-container">
                <?php while($fila = $alertas->fetch_assoc()): ?>
                <div class="alert-item">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation"></i>
                    </div>
                    <div class="alert-content">
                        <strong><?php echo htmlspecialchars($fila['nombre']); ?></strong>
                        <?php if ($usuario_tipo == 'admin' || $usuario_tipo == 'subadmin'): ?>
                            <br><span style="color: #ed1791; font-weight: 600;">📍 <?php echo htmlspecialchars($fila['sucursal']); ?></span>
                        <?php endif; ?>
                        <br><span>Precio: $<?php echo number_format($fila['precio'], 2); ?> | Categoría: <?php echo htmlspecialchars($fila['categoria']); ?></span>
                    </div>
                    <span class="stock-badge">
                        <?php echo $fila['stock']; ?> unidades
                    </span>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card patients">
                <div class="icon">
                    <i class="fas fa-cubes"></i>
                </div>
                <div class="number"><?php echo $total_productos; ?></div>
                <div class="label">Productos en stock</div>
            </div>
            <div class="stat-card appointments">
                <div class="icon">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                </div>
                <div class="number"><?php echo $entradas_mes; ?></div>
                <div class="label">Entradas este mes</div>
            </div>
            <div class="stat-card plans">
                <div class="icon">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                </div>
                <div class="number"><?php echo $salidas_mes; ?></div>
                <div class="label">Salidas este mes</div>
            </div>
            <div class="stat-card progress">
                <div class="icon">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div class="number"><?php echo $stock_bajo; ?></div>
                <div class="label">Stock bajo</div>
            </div>
        </div>

        <h2 class="section-title">Acciones rápidas</h2>
        <div class="actions-grid">
            <?php if ($usuario_tipo == 'admin' || $usuario_tipo == 'subadmin'): ?>
            <a href="nuevo_producto.php" class="action-card">
                <i class="fas fa-plus-circle"></i>
                <h3>Nuevo Producto</h3>
                <p>Agregar al inventario</p>
            </a>
            <a href="entrada.php" class="action-card">
                <i class="fas fa-arrow-down"></i>
                <h3>Registrar Entrada</h3>
                <p>Ingreso de productos</p>
            </a>
            <?php endif; ?>
            
            <a href="salida.php" class="action-card">
                <i class="fas fa-arrow-up"></i>
                <h3>Registrar Salida</h3>
                <p>Salida de productos</p>
            </a>
            <a href="productos.php" class="action-card">
                <i class="fas fa-boxes-stacked"></i>
                <h3>Ver Inventario</h3>
                <p>Lista de productos</p>
            </a>
            
            <?php if ($usuario_tipo == 'admin' || $usuario_tipo == 'subadmin'): ?>
            <a href="categorias.php" class="action-card">
                <i class="fas fa-tags"></i>
                <h3>Categorías</h3>
                <p>Gestionar categorías</p>
            </a>
            <a href="entradas_mes.php" class="action-card">
                <i class="fas fa-calendar-check"></i>
                <h3>Entradas del Mes</h3>
                <p>Historial de entradas</p>
            </a>
            <a href="salidas_mes.php" class="action-card">
                <i class="fas fa-calendar-alt"></i>
                <h3>Salidas del Mes</h3>
                <p>Historial de salidas</p>
            </a>
            <a href="reportes/" class="action-card">
                <i class="fas fa-chart-bar"></i>
                <h3>Reportes</h3>
                <p>Generar informes</p>
            </a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>


