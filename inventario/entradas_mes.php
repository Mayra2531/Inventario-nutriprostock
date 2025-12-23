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

// Entradas del mes actual
$entradas = $conexion->query("
    SELECT p.nombre, p.precio, m.cantidad, m.fecha 
    FROM movimientos m
    INNER JOIN productos p ON p.id = m.producto_id
    WHERE m.tipo = 'entrada'
    AND MONTH(m.fecha) = MONTH(CURRENT_DATE())
    AND YEAR(m.fecha) = YEAR(CURRENT_DATE())
    ORDER BY m.fecha DESC
");

// Total de entradas del mes
$total_entradas = $conexion->query("
    SELECT SUM(cantidad) as total 
    FROM movimientos 
    WHERE tipo = 'entrada'
    AND MONTH(fecha) = MONTH(CURRENT_DATE())
    AND YEAR(fecha) = YEAR(CURRENT_DATE())
")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriProStock - Entradas del Mes</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            color: #28a745;
        }

        .stat-badge {
            background: linear-gradient(135deg, #28a745 0%, #20863a 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 15px;
            text-align: center;
        }

        .stat-badge .number {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .stat-badge .label {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .table-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(69, 35, 73, 0.08);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: linear-gradient(135deg, #28a745 0%, #20863a 100%);
            color: white;
            padding: 15px;
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
            transition: background 0.3s ease;
        }

        tbody tr:hover {
            background: #f0fff4;
        }

        tbody td {
            padding: 15px;
            color: #333;
        }

        .badge-entrada {
            background: #d4edda;
            color: #155724;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #452349;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }

            .main-content {
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
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
            <h1><i class="fas fa-arrow-down"></i> Entradas del Mes</h1>
            <div class="stat-badge">
                <div class="number">+<?php echo $total_entradas; ?></div>
                <div class="label">unidades ingresadas</div>
            </div>
        </div>

        <div class="table-container">
            <?php if ($entradas && $entradas->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($fila = $entradas->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($fila['nombre']); ?></strong></td>
                        <td>$<?php echo number_format($fila['precio'], 2); ?></td>
                        <td><span class="badge-entrada">+<?php echo $fila['cantidad']; ?> unidades</span></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($fila['fecha'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No hay entradas este mes</h3>
                <p>Aún no se han registrado entradas de productos en el mes actual</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
