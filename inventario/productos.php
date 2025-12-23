<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header("Location: login.php");
    exit();
}

include("conexion.php");

$nombre = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';

// Procesar eliminación
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $mensaje = "Producto eliminado exitosamente.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al eliminar el producto.";
        $tipo_mensaje = "error";
    }
    $stmt->close();
}

// Procesar edición
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_producto'])) {
    $id = intval($_POST['id']);
    $precio = floatval($_POST['precio']);
    $nombre_prod = $conexion->real_escape_string(trim($_POST['nombre']));
    $categoria = $conexion->real_escape_string(trim($_POST['categoria']));
    $cantidad = intval($_POST['cantidad']);
    $id_sucursal = $_SESSION['id_sucursal'];

    if ($id_sucursal) {
        // Usuario con sucursal: actualizar stock de su sucursal
        $check = $conexion->query("SELECT stock FROM stock_sucursales WHERE id_producto = $id AND id_sucursal = $id_sucursal");
        if ($check && $check->num_rows > 0) {
            $conexion->query("UPDATE stock_sucursales SET stock = $cantidad WHERE id_producto = $id AND id_sucursal = $id_sucursal");
        } else {
            $conexion->query("INSERT INTO stock_sucursales (id_producto, id_sucursal, stock) VALUES ($id, $id_sucursal, $cantidad)");
        }
        
        // Actualizar total en productos
        $total = $conexion->query("SELECT SUM(stock) as total FROM stock_sucursales WHERE id_producto = $id")->fetch_assoc()['total'];
    } else {
        // Admin sin sucursal: solo actualiza datos del producto
        $total = $cantidad;
    }
    
    $stmt = $conexion->prepare("UPDATE productos SET precio = ?, nombre = ?, categoria = ?, cantidad = ? WHERE id = ?");
    $stmt->bind_param("dssii", $precio, $nombre_prod, $categoria, $total, $id);

    if ($stmt->execute()) {
        $mensaje = "Producto actualizado exitosamente.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar el producto: " . $conexion->error;
        $tipo_mensaje = "error";
    }
    $stmt->close();
}

// Procesar formulario de nuevo producto
$mensaje = $mensaje ?? '';
$tipo_mensaje = $tipo_mensaje ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_producto'])) {
    $precio = floatval($_POST['precio']);
    $nombre_prod = $conexion->real_escape_string(trim($_POST['nombre']));
    $categoria = $conexion->real_escape_string(trim($_POST['categoria']));
    $cantidad = intval($_POST['cantidad']);
    $fecha_ingreso = date('Y-m-d');
    $id_sucursal = $_SESSION['id_sucursal'];
    $usuario_tipo = $_SESSION['rol'] ?? 'usuario';

    // Insertar producto
    $sql = "INSERT INTO productos (precio, nombre, categoria, cantidad, fecha_ingreso) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("dssis", $precio, $nombre_prod, $categoria, $cantidad, $fecha_ingreso);

    if ($stmt->execute()) {
        $nuevo_id = $conexion->insert_id;
        
        // Si es admin, crear stock para TODAS las sucursales
        if ($usuario_tipo == 'admin') {
            $sucursales = $conexion->query("SELECT id_sucursal FROM sucursales");
            $stmt2 = $conexion->prepare("INSERT INTO stock_sucursales (id_producto, id_sucursal, stock) VALUES (?, ?, ?)");
            while ($suc = $sucursales->fetch_assoc()) {
                $id_suc = $suc['id_sucursal'];
                $stmt2->bind_param("iii", $nuevo_id, $id_suc, $cantidad);
                $stmt2->execute();
            }
            $stmt2->close();
            
            // Actualizar cantidad total en productos
            $total_stock = $cantidad * $sucursales->num_rows;
            $conexion->query("UPDATE productos SET cantidad = $total_stock WHERE id = $nuevo_id");
        } 
        // Si tiene sucursal asignada, crear stock solo para esa sucursal
        elseif ($id_sucursal) {
            $stmt2 = $conexion->prepare("INSERT INTO stock_sucursales (id_producto, id_sucursal, stock) VALUES (?, ?, ?)");
            $stmt2->bind_param("iii", $nuevo_id, $id_sucursal, $cantidad);
            $stmt2->execute();
            $stmt2->close();
        }
        
        $mensaje = "Producto agregado exitosamente.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al agregar el producto: " . $conexion->error;
        $tipo_mensaje = "error";
    }
    $stmt->close();
}

// Obtener productos
$id_sucursal = $_SESSION['id_sucursal'];
$usuario_tipo = $_SESSION['rol'] ?? 'usuario';

if (!$id_sucursal && $usuario_tipo == 'admin') {
    // Admin sin sucursal ve desglose completo por sucursal
    $query = "SELECT p.nombre, p.precio, p.categoria, p.fecha_ingreso, p.id,
              suc.nombre_sucursal, 
              COALESCE(st.stock, 0) as stock,
              suc.id_sucursal
              FROM productos p
              CROSS JOIN sucursales suc
              LEFT JOIN stock_sucursales st ON p.id = st.id_producto AND suc.id_sucursal = st.id_sucursal
              ORDER BY p.nombre, suc.nombre_sucursal";
    $es_admin_global = true;
} elseif ($id_sucursal) {
    // Usuario con sucursal asignada
    $query = "SELECT p.*, 
              COALESCE(s.stock, 0) as stock_sucursal,
              p.cantidad as stock_total
              FROM productos p
              LEFT JOIN stock_sucursales s ON p.id = s.id_producto AND s.id_sucursal = $id_sucursal
              ORDER BY p.id DESC";
    $es_admin_global = false;
} else {
    // Fallback
    $query = "SELECT p.*, 
              0 as stock_sucursal,
              p.cantidad as stock_total
              FROM productos p
              ORDER BY p.id DESC";
    $es_admin_global = false;
}
$resultado = $conexion->query($query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriProStock - Productos</title>
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

        .navbar .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
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
            max-width: 1400px;
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
        }

        .btn-primary {
            background: linear-gradient(135deg, #ed1791 0%, #c41076 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(237, 23, 145, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(237, 23, 145, 0.4);
        }

        /* Alert Messages */
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

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(69, 35, 73, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(69, 35, 73, 0.2);
            animation: modalIn 0.3s ease;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .modal-header h2 {
            color: #452349;
            font-size: 1.5rem;
        }

        .btn-close {
            background: #f0e6f5;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.2rem;
            color: #452349;
            transition: all 0.3s ease;
        }

        .btn-close:hover {
            background: #ed1791;
            color: white;
        }

        /* Form Styles */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            color: #452349;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #f0e6f5;
            border-radius: 10px;
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
        }

        .modal-footer {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .btn-secondary {
            background: #f0e6f5;
            color: #452349;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #e0d0e8;
        }

        /* Table */
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
            background: linear-gradient(135deg, #452349 0%, #5a3060 100%);
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
            background: #fdf8fc;
        }

        tbody td {
            padding: 15px;
            color: #333;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-stock-ok {
            background: #d4edda;
            color: #155724;
        }

        .badge-stock-low {
            background: #fff3cd;
            color: #856404;
        }

        .badge-stock-empty {
            background: #f8d7da;
            color: #721c24;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-edit {
            background: #e8f4fd;
            color: #0077cc;
        }

        .btn-edit:hover {
            background: #0077cc;
            color: white;
        }

        .btn-delete {
            background: #ffe6e6;
            color: #d63031;
        }

        .btn-delete:hover {
            background: #d63031;
            color: white;
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

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }

            .main-content {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
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
        <div class="user-menu">
            <a href="index.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Volver al Dashboard
            </a>
        </div>
    </nav>

    <main class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-cubes"></i> Gestión de Productos</h1>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
            <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <?php if ($resultado && $resultado->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <?php if ($es_admin_global): ?>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Categoría</th>
                        <th>Sucursal</th>
                        <th>Stock</th>
                        <?php else: ?>
                        <th>Precio</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Stock Mi Sucursal</th>
                        <th>Stock Total</th>
                        <th>Fecha Ingreso</th>
                        <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($es_admin_global): ?>
                        <?php while ($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                            <td>$<?php echo number_format($row['precio'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre_sucursal']); ?></td>
                            <td>
                                <?php
                                $stock = intval($row['stock']);
                                $badgeClass = 'badge-stock-ok';
                                if ($stock == 0) {
                                    $badgeClass = 'badge-stock-empty';
                                } elseif ($stock <= 10) {
                                    $badgeClass = 'badge-stock-low';
                                }
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>"><?php echo $stock; ?> unidades</span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <?php while ($row = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><strong>$<?php echo number_format($row['precio'], 2); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                        <td>
                            <?php
                            $stock_sucursal = intval($row['stock_sucursal']);
                            $badgeClass = 'badge-stock-ok';
                            if ($stock_sucursal == 0) {
                                $badgeClass = 'badge-stock-empty';
                            } elseif ($stock_sucursal <= 10) {
                                $badgeClass = 'badge-stock-low';
                            }
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>"><?php echo $stock_sucursal; ?> unidades</span>
                        </td>
                        <td>
                            <?php
                            $stock_total = intval($row['stock_total']);
                            $badgeClass2 = 'badge-stock-ok';
                            if ($stock_total == 0) {
                                $badgeClass2 = 'badge-stock-empty';
                            } elseif ($stock_total <= 10) {
                                $badgeClass2 = 'badge-stock-low';
                            }
                            ?>
                            <span class="badge <?php echo $badgeClass2; ?>"><?php echo $stock_total; ?> unidades</span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($row['fecha_ingreso'])); ?></td>
                        <td>
                            <div class="actions">
                                <button class="btn-action btn-edit" title="Editar" onclick="editarProducto(<?php echo $row['id']; ?>, <?php echo $row['precio']; ?>, '<?php echo addslashes($row['nombre']); ?>', '<?php echo addslashes($row['categoria']); ?>', <?php echo $stock_sucursal; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action btn-delete" title="Eliminar" onclick="eliminarProducto(<?php echo $row['id']; ?>, '<?php echo addslashes($row['nombre']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>No hay productos registrados</h3>
                <p>Comienza agregando tu primer producto al inventario</p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Nuevo Producto -->
    <div class="modal-overlay" id="modalProducto">
        <div class="modal">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Nuevo Producto</h2>
                <button class="btn-close" onclick="cerrarModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="precio">Precio del Producto *</label>
                        <input type="number" step="0.01" id="precio" name="precio" placeholder="0.00" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="nombre">Nombre del Producto *</label>
                        <input type="text" id="nombre" name="nombre" placeholder="Nombre del producto" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="categoria">Categoría *</label>
                        <select id="categoria" name="categoria" required>
                            <option value="">Seleccionar categoría</option>
                            <option value="Suplementos">Suplementos</option>
                            <option value="Vitaminas">Vitaminas</option>
                            <option value="Proteínas">Proteínas</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cantidad">Cantidad Inicial *</label>
                        <input type="number" id="cantidad" name="cantidad" placeholder="0" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" name="agregar_producto" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Guardar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Producto -->
    <div class="modal-overlay" id="modalEditar">
        <div class="modal">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Editar Producto</h2>
                <button class="btn-close" onclick="cerrarModalEditar()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_precio">Precio del Producto *</label>
                        <input type="number" step="0.01" id="edit_precio" name="precio" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_nombre">Nombre del Producto *</label>
                        <input type="text" id="edit_nombre" name="nombre" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_categoria">Categoría *</label>
                        <select id="edit_categoria" name="categoria" required>
                            <option value="">Seleccionar categoría</option>
                            <option value="Suplementos">Suplementos</option>
                            <option value="Vitaminas">Vitaminas</option>
                            <option value="Proteínas">Proteínas</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_cantidad">Cantidad *</label>
                        <input type="number" id="edit_cantidad" name="cantidad" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="cerrarModalEditar()">Cancelar</button>
                    <button type="submit" name="editar_producto" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Actualizar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModal() {
            document.getElementById('modalProducto').classList.add('active');
        }

        function cerrarModal() {
            document.getElementById('modalProducto').classList.remove('active');
        }

        function editarProducto(id, precio, nombre, categoria, cantidad) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_precio').value = precio;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_categoria').value = categoria;
            document.getElementById('edit_cantidad').value = cantidad;
            document.getElementById('modalEditar').classList.add('active');
        }

        function cerrarModalEditar() {
            document.getElementById('modalEditar').classList.remove('active');
        }

        function eliminarProducto(id, nombre) {
            if (confirm('¿Estás seguro de eliminar el producto "' + nombre + '"?')) {
                window.location.href = 'productos.php?eliminar=' + id;
            }
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('modalProducto').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });

        document.getElementById('modalEditar').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalEditar();
            }
        });

        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
                cerrarModalEditar();
            }
        });
    </script>
</body>
</html>
