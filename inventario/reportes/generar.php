<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header("Location: ../login.php");
    exit();
}

require('fpdf.php');

// Conexión a la base de datos
$mysqli = new mysqli("localhost", "root", "", "inventario_db");
if ($mysqli->connect_errno) {
    die("Error al conectar a la base de datos: " . $mysqli->connect_error);
}

// Obtener parámetros
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'inventario';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';

// Clase PDF personalizada
class PDF extends FPDF {
    function Header() {
        // Logo/Título
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(69, 35, 73); // #452349
        $this->Cell(0, 10, 'NutriProStock', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Sistema de Inventario', 0, 1, 'C');
        $this->Ln(5);
        
        // Línea decorativa
        $this->SetDrawColor(237, 23, 145); // #ed1791
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(8);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    
    function ReportTitle($title) {
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(69, 35, 73);
        $this->Cell(0, 10, $title, 0, 1, 'L');
        $this->Ln(2);
    }
    
    function DateRange($inicio, $fin) {
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 6, 'Periodo: ' . date('d/m/Y', strtotime($inicio)) . ' - ' . date('d/m/Y', strtotime($fin)), 0, 1, 'L');
        $this->Cell(0, 6, 'Generado: ' . date('d/m/Y H:i'), 0, 1, 'L');
        $this->Ln(5);
    }
    
    function TableHeader($headers, $widths) {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(69, 35, 73); // #452349
        $this->SetTextColor(255, 255, 255);
        for ($i = 0; $i < count($headers); $i++) {
            $this->Cell($widths[$i], 8, $headers[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetTextColor(0, 0, 0);
    }
    
    function TableRow($data, $widths, $fill = false) {
        $this->SetFont('Arial', '', 9);
        if ($fill) {
            $this->SetFillColor(253, 242, 248); // Light pink
        }
        for ($i = 0; $i < count($data); $i++) {
            $this->Cell($widths[$i], 7, $data[$i], 1, 0, 'L', $fill);
        }
        $this->Ln();
    }
}

// Crear PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

switch ($tipo) {
    case 'movimientos':
        // Reporte de movimientos
        $pdf->ReportTitle('Reporte de Movimientos de Inventario');
        $pdf->DateRange($fecha_inicio, $fecha_fin);
        
        $query = "SELECT p.nombre as producto, p.precio, m.cantidad, m.tipo, m.fecha 
                  FROM movimientos m 
                  INNER JOIN productos p ON m.producto_id = p.id 
                  WHERE DATE(m.fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'
                  ORDER BY m.fecha DESC";
        $result = $mysqli->query($query);
        
        $headers = array('Precio', 'Producto', 'Cantidad', 'Tipo', 'Fecha');
        $widths = array(25, 70, 25, 30, 40);
        $pdf->TableHeader($headers, $widths);
        
        $fill = false;
        $total_entradas = 0;
        $total_salidas = 0;
        
        while ($row = $result->fetch_assoc()) {
            $data = array(
                '$' . number_format($row['precio'], 2),
                substr($row['producto'], 0, 35),
                $row['cantidad'],
                ucfirst($row['tipo']),
                date('d/m/Y', strtotime($row['fecha']))
            );
            $pdf->TableRow($data, $widths, $fill);
            $fill = !$fill;
            
            if ($row['tipo'] == 'entrada') {
                $total_entradas += $row['cantidad'];
            } else {
                $total_salidas += $row['cantidad'];
            }
        }
        
        // Resumen
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, 'Resumen:', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(60, 7, 'Total Entradas: ' . $total_entradas . ' unidades', 0, 1);
        $pdf->Cell(60, 7, 'Total Salidas: ' . $total_salidas . ' unidades', 0, 1);
        break;
        
    case 'inventario':
        // Reporte de inventario actual
        $pdf->ReportTitle('Inventario Actual de Productos');
        $pdf->DateRange($fecha_inicio, $fecha_fin);
        
        $where = "";
        if (!empty($categoria)) {
            $where = " WHERE categoria = '" . $mysqli->real_escape_string($categoria) . "'";
        }
        
        $query = "SELECT precio, nombre, categoria, cantidad FROM productos $where ORDER BY nombre ASC";
        $result = $mysqli->query($query);
        
        $headers = array('Precio', 'Producto', 'Categoria', 'Stock');
        $widths = array(30, 75, 50, 35);
        $pdf->TableHeader($headers, $widths);
        
        $fill = false;
        $total_productos = 0;
        $total_stock = 0;
        
        while ($row = $result->fetch_assoc()) {
            $data = array(
                '$' . number_format($row['precio'], 2),
                substr($row['nombre'], 0, 40),
                $row['categoria'],
                $row['cantidad']
            );
            $pdf->TableRow($data, $widths, $fill);
            $fill = !$fill;
            $total_productos++;
            $total_stock += $row['cantidad'];
        }
        
        // Resumen
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, 'Resumen:', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(60, 7, 'Total Productos: ' . $total_productos, 0, 1);
        $pdf->Cell(60, 7, 'Total en Stock: ' . $total_stock . ' unidades', 0, 1);
        break;
        
    case 'entradas':
        // Reporte de entradas
        $pdf->ReportTitle('Reporte de Entradas');
        $pdf->DateRange($fecha_inicio, $fecha_fin);
        
        $query = "SELECT p.nombre as producto, p.precio, m.cantidad, m.fecha 
                  FROM movimientos m 
                  INNER JOIN productos p ON m.producto_id = p.id 
                  WHERE m.tipo = 'entrada' AND DATE(m.fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'
                  ORDER BY m.fecha DESC";
        $result = $mysqli->query($query);
        
        $headers = array('Precio', 'Producto', 'Cantidad', 'Fecha');
        $widths = array(30, 80, 30, 50);
        $pdf->TableHeader($headers, $widths);
        
        $fill = false;
        $total = 0;
        
        while ($row = $result->fetch_assoc()) {
            $data = array(
                '$' . number_format($row['precio'], 2),
                substr($row['producto'], 0, 45),
                $row['cantidad'],
                date('d/m/Y', strtotime($row['fecha']))
            );
            $pdf->TableRow($data, $widths, $fill);
            $fill = !$fill;
            $total += $row['cantidad'];
        }
        
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, 'Total de Entradas: ' . $total . ' unidades', 0, 1);
        break;
        
    case 'salidas':
        // Reporte de salidas
        $pdf->ReportTitle('Reporte de Salidas');
        $pdf->DateRange($fecha_inicio, $fecha_fin);
        
        $query = "SELECT p.nombre as producto, p.precio, m.cantidad, m.fecha 
                  FROM movimientos m 
                  INNER JOIN productos p ON m.producto_id = p.id 
                  WHERE m.tipo = 'salida' AND DATE(m.fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'
                  ORDER BY m.fecha DESC";
        $result = $mysqli->query($query);
        
        $headers = array('Precio', 'Producto', 'Cantidad', 'Fecha');
        $widths = array(30, 80, 30, 50);
        $pdf->TableHeader($headers, $widths);
        
        $fill = false;
        $total = 0;
        
        while ($row = $result->fetch_assoc()) {
            $data = array(
                '$' . number_format($row['precio'], 2),
                substr($row['producto'], 0, 45),
                $row['cantidad'],
                date('d/m/Y', strtotime($row['fecha']))
            );
            $pdf->TableRow($data, $widths, $fill);
            $fill = !$fill;
            $total += $row['cantidad'];
        }
        
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, 'Total de Salidas: ' . $total . ' unidades', 0, 1);
        break;
        
    case 'stock_bajo':
        // Reporte de stock bajo
        $pdf->ReportTitle('Productos con Stock Bajo');
        $pdf->DateRange($fecha_inicio, $fecha_fin);
        
        $query = "SELECT precio, nombre, categoria, cantidad FROM productos WHERE cantidad <= 10 ORDER BY cantidad ASC";
        $result = $mysqli->query($query);
        
        $headers = array('Precio', 'Producto', 'Categoria', 'Stock');
        $widths = array(30, 75, 50, 35);
        $pdf->TableHeader($headers, $widths);
        
        $fill = false;
        $count = 0;
        
        while ($row = $result->fetch_assoc()) {
            $pdf->SetFont('Arial', '', 9);
            // Resaltar críticos en rojo
            if ($row['cantidad'] <= 5) {
                $pdf->SetTextColor(214, 48, 49); // Rojo
            } else {
                $pdf->SetTextColor(0, 0, 0);
            }
            $data = array(
                '$' . number_format($row['precio'], 2),
                substr($row['nombre'], 0, 40),
                $row['categoria'],
                $row['cantidad'] . ' unidades'
            );
            $pdf->TableRow($data, $widths, $fill);
            $fill = !$fill;
            $count++;
        }
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, 'Total de Productos con Stock Bajo: ' . $count, 0, 1);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(214, 48, 49);
        $pdf->Cell(0, 6, '* Los productos en rojo tienen stock critico (5 o menos)', 0, 1);
        break;
        
    case 'categorias':
        // Reporte por categorías
        $pdf->ReportTitle('Reporte de Productos por Categoria');
        $pdf->DateRange($fecha_inicio, $fecha_fin);
        
        $query = "SELECT categoria, COUNT(*) as total_productos, SUM(cantidad) as total_stock 
                  FROM productos 
                  GROUP BY categoria 
                  ORDER BY categoria ASC";
        $result = $mysqli->query($query);
        
        $headers = array('Categoria', 'Total Productos', 'Stock Total');
        $widths = array(80, 50, 60);
        $pdf->TableHeader($headers, $widths);
        
        $fill = false;
        $total_prods = 0;
        $total_stock = 0;
        
        while ($row = $result->fetch_assoc()) {
            $data = array(
                $row['categoria'],
                $row['total_productos'] . ' productos',
                $row['total_stock'] . ' unidades'
            );
            $pdf->TableRow($data, $widths, $fill);
            $fill = !$fill;
            $total_prods += $row['total_productos'];
            $total_stock += $row['total_stock'];
        }
        
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, 'Resumen General:', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(60, 7, 'Total Productos: ' . $total_prods, 0, 1);
        $pdf->Cell(60, 7, 'Stock Total: ' . $total_stock . ' unidades', 0, 1);
        break;
}

// Generar nombre del archivo
$nombreArchivo = 'Reporte_' . ucfirst($tipo) . '_' . date('Y-m-d') . '.pdf';

// Descargar PDF
$pdf->Output('D', $nombreArchivo);
?>
