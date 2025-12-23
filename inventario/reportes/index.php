<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Verificar que sea admin o subadmin
if (($_SESSION['rol'] ?? 'usuario') !== 'admin' && ($_SESSION['rol'] ?? 'usuario') !== 'subadmin') {
    header("Location: ../index.php");
    exit();
}

include("../conexion.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriStock - Reportes</title>
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
            color: #666;
            margin-top: 5px;
        }

        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .report-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(69, 35, 73, 0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(237, 23, 145, 0.15);
            border-color: #ed1791;
        }

        .report-card .icon {
            width: 70px;
            height: 70px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 20px;
        }

        .report-card.movimientos .icon {
            background: linear-gradient(135deg, #ed1791 0%, #c41076 100%);
            color: white;
        }

        .report-card.inventario .icon {
            background: linear-gradient(135deg, #452349 0%, #5a3060 100%);
            color: white;
        }

        .report-card.entradas .icon {
            background: linear-gradient(135deg, #00b894 0%, #00a383 100%);
            color: white;
        }

        .report-card.salidas .icon {
            background: linear-gradient(135deg, #e17055 0%, #d63031 100%);
            color: white;
        }

        .report-card.stock .icon {
            background: linear-gradient(135deg, #fdcb6e 0%, #f39c12 100%);
            color: white;
        }

        .report-card.categorias .icon {
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: white;
        }

        .report-card h3 {
            color: #452349;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .report-card p {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .report-card .btn-generate {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #ed1791 0%, #c41076 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .report-card .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(237, 23, 145, 0.3);
        }

        .filters-section {
            background: white;
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(69, 35, 73, 0.08);
        }

        .filters-section h3 {
            color: #452349;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filters-section h3 i {
            color: #ed1791;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .filter-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            color: #452349;
            margin-bottom: 8px;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #f0e6f5;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #fdf8fc;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #ed1791;
            background: white;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }

            .main-content {
                padding: 20px;
            }

            .reports-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="../index.php" class="brand">
            <div class="brand-icon">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <span class="brand-name">NutriStock</span>
        </a>
        <a href="../index.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Volver al Dashboard
        </a>
    </nav>

    <main class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-chart-bar"></i> Centro de Reportes</h1>
            <p>Genera reportes PDF de tu inventario</p>
        </div>

        <div class="filters-section">
            <h3><i class="fas fa-filter"></i> Filtros de Fecha</h3>
            <form id="filterForm" class="filters-grid">
                <div class="filter-group">
                    <label for="fecha_inicio">Fecha Inicio</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="filter-group">
                    <label for="fecha_fin">Fecha Fin</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="filter-group">
                    <label for="categoria">Categoría</label>
                    <select id="categoria" name="categoria">
                        <option value="">Todas las categorías</option>
                        <?php
                        $cats = $conexion->query("SELECT * FROM categorias ORDER BY nombre");
                        if ($cats && $cats->num_rows > 0) {
                            while($cat = $cats->fetch_assoc()) {
                                echo '<option value="'.htmlspecialchars($cat['nombre']).'">'.htmlspecialchars($cat['nombre']).'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="reports-grid">
            <!-- Reporte de Movimientos -->
            <div class="report-card movimientos">
                <div class="icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <h3>Movimientos de Inventario</h3>
                <p>Genera un reporte con todas las entradas y salidas de productos en el período seleccionado.</p>
                <a href="#" onclick="generarReporte('movimientos')" class="btn-generate">
                    <i class="fas fa-file-pdf"></i>
                    Generar PDF
                </a>
            </div>

            <!-- Reporte de Inventario Actual -->
            <div class="report-card inventario">
                <div class="icon">
                    <i class="fas fa-boxes-stacked"></i>
                </div>
                <h3>Inventario Actual</h3>
                <p>Lista completa de todos los productos con su stock actual, código y categoría.</p>
                <a href="#" onclick="generarReporte('inventario')" class="btn-generate">
                    <i class="fas fa-file-pdf"></i>
                    Generar PDF
                </a>
            </div>

            <!-- Reporte de Entradas -->
            <div class="report-card entradas">
                <div class="icon">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <h3>Reporte de Entradas</h3>
                <p>Detalle de todas las entradas de productos realizadas en el período seleccionado.</p>
                <a href="#" onclick="generarReporte('entradas')" class="btn-generate">
                    <i class="fas fa-file-pdf"></i>
                    Generar PDF
                </a>
            </div>

            <!-- Reporte de Salidas -->
            <div class="report-card salidas">
                <div class="icon">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <h3>Reporte de Salidas</h3>
                <p>Detalle de todas las salidas de productos realizadas en el período seleccionado.</p>
                <a href="#" onclick="generarReporte('salidas')" class="btn-generate">
                    <i class="fas fa-file-pdf"></i>
                    Generar PDF
                </a>
            </div>

            <!-- Reporte de Stock Bajo -->
            <div class="report-card stock">
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Productos con Stock Bajo</h3>
                <p>Lista de productos que tienen stock bajo o necesitan reabastecimiento urgente.</p>
                <a href="#" onclick="generarReporte('stock_bajo')" class="btn-generate">
                    <i class="fas fa-file-pdf"></i>
                    Generar PDF
                </a>
            </div>

            <!-- Reporte por Categoría -->
            <div class="report-card categorias">
                <div class="icon">
                    <i class="fas fa-tags"></i>
                </div>
                <h3>Reporte por Categoría</h3>
                <p>Productos agrupados por categoría con totales y estadísticas por grupo.</p>
                <a href="#" onclick="generarReporte('categorias')" class="btn-generate">
                    <i class="fas fa-file-pdf"></i>
                    Generar PDF
                </a>
            </div>
        </div>
    </main>

    <script>
        function generarReporte(tipo) {
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;
            const categoria = document.getElementById('categoria').value;
            
            let url = 'generar.php?tipo=' + tipo;
            url += '&fecha_inicio=' + fechaInicio;
            url += '&fecha_fin=' + fechaFin;
            if (categoria) {
                url += '&categoria=' + encodeURIComponent(categoria);
            }
            
            window.open(url, '_blank');
        }
    </script>
</body>
</html>
