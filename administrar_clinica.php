<?php
// administrar_clinica.php
session_start();
require_once 'conexion.php';

// Seguridad: Solo el Administrador (Limber) puede acceder a la gestión global
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit;
}

try {
    // 1. Calcular la recaudación total estimada (Suma de precios de todas las citas)
    $stmt1 = $pdo->query("SELECT SUM(s.precio) as total FROM turnos t JOIN servicios s ON t.servicio_id = s.id_servicio WHERE t.estado != 'Cancelado'");
    $recaudacion = $stmt1->fetch()['total'] ?? 0;

    // 2. Contar cantidad total de citas registradas
    $stmt2 = $pdo->query("SELECT COUNT(id_turno) as total_citas FROM turnos");
    $total_citas = $stmt2->fetch()['total_citas'] ?? 0;

    // 3. Contar total de odontólogos en el staff
    $stmt3 = $pdo->query("SELECT COUNT(id_odontologo) as total_medicos FROM odontologos");
    $total_medicos = $stmt3->fetch()['total_medicos'] ?? 0;

    // 4. Listar el catálogo de servicios cargados para auditoría
    $servicios = $pdo->query("SELECT * FROM servicios ORDER BY precio DESC")->fetchAll();
} catch (\PDOException $e) {
    die("Error de auditoría interna: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OdontoSync - Administración General</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background: #f4f7f6; padding: 40px; color: #334155; }
        .admin-container { background: white; padding: 35px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 1100px; margin: 0 auto; }
        h2 { color: #003366; margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; }
        
        /* Contenedor de Tarjetas de Indicadores KPI */
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .kpi-card { background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 5px solid #003366; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .kpi-card h3 { font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
        .kpi-card p { font-size: 28px; font-weight: bold; color: #0f172a; }
        .kpi-money { border-left-color: #16a34a; }
        .kpi-money p { color: #16a34a; }

        /* Tabla de Control */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background-color: #003366; color: white; font-weight: 600; font-size: 13px; text-transform: uppercase; }
        tr:hover { background-color: #f8fafc; }
        .btn-back { display: inline-block; margin-bottom: 20px; color: #0066cc; text-decoration: none; font-weight: bold; font-size: 14px; }
        .btn-back:hover { text-decoration: underline; }
                /* 🖨️ Estilos Avanzados para Doble Reportabilidad PDF */
        .btn-reporte-ganancias { display: inline-block; background: #dc2626; color: white; padding: 10px 18px; border: none; border-radius: 6px; font-weight: bold; font-size: 14px; cursor: pointer; margin-right: 10px; margin-bottom: 20px; transition: background 0.2s; box-shadow: 0 4px 10px rgba(220,38,38,0.2); }
        .btn-reporte-ganancias:hover { background: #b91c1c; }
        
        .btn-reporte-tratamientos { display: inline-block; background: #2563eb; color: white; padding: 10px 18px; border: none; border-radius: 6px; font-weight: bold; font-size: 14px; cursor: pointer; margin-bottom: 20px; transition: background 0.2s; box-shadow: 0 4px 10px rgba(37,99,235,0.2); }
        .btn-reporte-tratamientos:hover { background: #1d4ed8; }

        .encabezado-pdf { display: none; border-bottom: 3px solid #003366; padding-bottom: 10px; margin-bottom: 25px; }
        .encabezado-pdf h1 { color: #003366; font-size: 22px; }

        @media print {
            body { background: white; padding: 0; color: black; }
            .admin-container { box-shadow: none; padding: 0; max-width: 100%; }
            .btn-back, .btn-reporte-ganancias, .btn-reporte-tratamientos { display: none !important; }
            .encabezado-pdf { display: block !important; }
            th { background-color: #003366 !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            
            /* Lógica selectiva de impresión por clases */
            body.imprimir-ganancias table, body.imprimir-ganancias h3 { display: none !important; }
            body.imprimir-tratamientos .kpi-grid { display: none !important; }
        }

    </style>
</head>
<body>
    <div class="admin-container">
    <a href="dashboard.php" class="btn-back">⬅️ Volver al Panel de Control</a>
<div class="admin-container">
    <a href="dashboard.php" class="btn-back">⬅️ Volver al Panel de Control</a>
    <h2>Cuadro de Mando y Configuración de la Clínica</h2>
    <!-- 📊 Botón 1: Exportar solo la Recaudación Contable -->
    <button onclick="imprimirGanancias()" class="btn-reporte-ganancias">🖨️ Descargar Reporte de Ganancias</button>

    <!-- 📋 Botón 2: Exportar solo la Lista de Precios -->
    <button onclick="imprimirTratamientos()" class="btn-reporte-tratamientos">📄 Descargar Lista de Tratamientos</button>

    <!-- Membrete oficial Dinámico para el PDF -->
    <div class="encabezado-pdf">
        <h1 id="titulo_reporte_pdf">OdontoSync - Reporte Institucional</h1>
        <p>Reporte Oficial Bajo Demanda • Auditado por: <strong>Limber Ramos Espinal</strong></p>
    </div>
    <!-- Tarjetas de Métricas en Tiempo Real (Prueba de Rendimiento e Integración) -->
    <div class="kpi-grid">
        <div class="kpi-card kpi-money">
            <h3>Recaudación Total Estimada</h3>
            <p>Bs. <?php echo number_format($recaudacion, 2); ?></p>
        </div>
        <div class="kpi-card">
            <h3>Volumen de Turnos Gestionados</h3>
            <p><?php echo $total_citas; ?> Citas</p>
        </div>
        <div class="kpi-card">
            <h3>Staff Profesional Activo</h3>
            <p><?php echo $total_medicos; ?> Especialistas</p>
        </div>
    </div>

    <h3>Catálogo Vigente de Servicios y Aranceles</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre del Tratamiento</th>
                <th>Descripción Clínica</th>
                <th>Costo Base</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($servicios as $s): ?>
                <tr>
                    <td><strong>#<?php echo $s['id_servicio']; ?></strong></td>
                    <td><strong><?php echo htmlspecialchars($s['nombre_servicio']); ?></strong></td>
                    <td style="color: #64748b; font-size: 13px;"><?php echo htmlspecialchars($s['descripcion']); ?></td>
                    <td style="font-weight: bold; color: #16a34a;">Bs. <?php echo number_format($s['precio'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
    // 💰 Función para generar el PDF únicamente de las Ganancias y KPIs
    function imprimirGanancias() {
        document.body.classList.add('imprimir-ganancias');
        document.getElementById('titulo_reporte_pdf').textContent = "OdontoSync - Reporte Ejecutivo de Recaudación y Finanzas";
        window.print();
        document.body.classList.remove('imprimir-ganancias');
    }

    // 🦷 Función para generar el PDF únicamente del Catálogo de Tratamientos
    function imprimirTratamientos() {
        document.body.classList.add('imprimir-tratamientos');
        document.getElementById('titulo_reporte_pdf').textContent = "OdontoSync - Catálogo Oficial de Aranceles y Servicios Dentales";
        window.print();
        document.body.classList.remove('imprimir-tratamientos');
    }
</script>

</body>
</html>
