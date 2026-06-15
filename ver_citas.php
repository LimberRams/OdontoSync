<?php
// ver_citas.php
session_start();
require_once 'conexion.php';

// Seguridad: Solo el Administrador (Limber) o los Odontólogos pueden auditar todas las citas
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] === 'paciente') {
    header("Location: login.php");
    exit;
}

try {
    // Consulta relacional avanzada con alias limpios para evitar colisiones de datos
    $sql = "SELECT t.id_turno, t.fecha_turno, t.hora_turno, t.estado, t.observaciones AS historial_clinico,
                   u_pac.nombre AS pac_nombre, u_pac.apellido AS pac_apellido,
                   u_odo.nombre AS odo_nombre, u_odo.apellido AS odo_apellido, o.especialidad,
                   s.nombre_servicio, s.precio
            FROM turnos t
            JOIN pacientes p ON t.paciente_id = p.id_paciente
            JOIN usuarios u_pac ON p.id_paciente = u_pac.id_usuario
            JOIN odontologos o ON t.odontologo_id = o.id_odontologo
            JOIN usuarios u_odo ON o.id_odontologo = u_odo.id_usuario
            JOIN servicios s ON t.servicio_id = s.id_servicio
            ORDER BY t.fecha_turno DESC, t.hora_turno DESC"; // Ordenado por lo más reciente arriba
            
    $stmt = $pdo->query($sql);
    $citas = $stmt->fetchAll();
} catch (\PDOException $e) {
    die("Error crítico en la consulta de auditoría: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OdontoSync - Auditoría de Citas e Historias Clínicas</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f4f7f6; padding: 40px; color: #334155; }
        .table-container { background: white; padding: 35px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 1350px; margin: 0 auto; }
        h2 { color: #003366; margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; font-size: 24px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 14px 18px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; vertical-align: middle; }
        th { background-color: #003366; color: white; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        tr:hover { background-color: #f8fafc; transition: background 0.2s; }
        
        /* Insignias de Estado Dinámicas */
        .status-badge { padding: 6px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; text-align: center; }
        .pendiente { background-color: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .confirmado { background-color: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        .completo { background-color: #dbeafe; color: #2563eb; border: 1px solid #bfdbfe; }
        .cancelado { background-color: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        
        .btn-back { display: inline-block; margin-bottom: 20px; color: #0066cc; text-decoration: none; font-weight: bold; font-size: 14px; transition: color 0.2s; }
        .btn-back:hover { color: #004488; text-decoration: underline; }
        
        .action-container { margin-top: 8px; display: flex; gap: 6px; }
        .btn-action-status { font-size: 11px; text-decoration: none; font-weight: bold; padding: 3px 6px; border-radius: 4px; transition: all 0.2s; }
        .btn-confirm { color: #16a34a; background: #f0fdf4; border: 1px solid #bbf7d0; }
        .btn-confirm:hover { background: #16a34a; color: white; }
        .btn-complete { color: #2563eb; background: #eff6ff; border: 1px solid #bfdbfe; }
        .btn-complete:hover { background: #2563eb; color: white; }
        .btn-cancel { color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; }
        .btn-cancel:hover { background: #dc2626; color: white; }
        
        /* Bloque estético de Historia Clínica */
        .historial-box { background: #f0fdf4; border: 1px solid #bbf7d0; padding: 10px 14px; border-radius: 8px; font-size: 13px; max-width: 300px; color: #14532d; font-weight: 500; word-wrap: break-word; line-height: 1.4; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .historial-empty { color: #94a3b8; font-style: italic; font-size: 12px; }
            /* 🖨️ Estilos Avanzados para Doble Reportabilidad PDF en Citas */
        .btn-pdf { display: inline-block; color: white; padding: 10px 18px; border: none; border-radius: 6px; font-weight: bold; font-size: 14px; cursor: pointer; float: right; margin-bottom: 20px; transition: background 0.2s; }
        
        .encabezado-pdf-pacientes { display: none; border-bottom: 3px solid #003366; padding-bottom: 10px; margin-bottom: 25px; }
        .encabezado-pdf-pacientes h1 { color: #003366; font-size: 22px; }

        @media print {
            body { background: white; padding: 0; color: black; }
            .table-container { box-shadow: none; padding: 0; max-width: 100%; }
            .btn-back, .btn-pdf, .action-container, .btn-action-status { display: none !important; }
            .encabezado-pdf-pacientes { display: block !important; }
            th { background-color: #003366 !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    @media print {
            body { background: white; padding: 0; color: black; }
            .table-container { box-shadow: none; padding: 0; max-width: 100%; border: none; }
            
            /* 🛑 Oculta botones, menús y la columna de acciones del admin de golpe en el PDF */
            .btn-back, .btn-pdf, .action-container, button, .col-acciones { 
                display: none !important; 
            }
            
            th { background-color: #003366 !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            
            /* 🔴 LÓGICA DEL BOTÓN ROJO: Si NO se imprime pacientes, borra la columna del historial médico */
            body:not(.imprimir-pacientes) .col-historial, 
            body:not(.imprimir-pacientes) .encabezado-pdf-pacientes { 
                display: none !important; 
            }
            
            /* 🔵 LÓGICA DEL BOTÓN AZUL: Si SE imprime pacientes, activa el membrete médico de Limber y expande la celda */
            body.imprimir-pacientes .encabezado-pdf-pacientes { 
                display: block !important; 
            }
            body.imprimir-pacientes td { font-size: 13px !important; }
            body.imprimir-pacientes .historial-box { max-width: 100% !important; background: #fff !important; color: #000 !important; border: 1px dashed #cbd5e1 !important; }
        }

    </style>
</head>
<body>

<div class="table-container">
    <a href="dashboard.php" class="btn-back">⬅️ Volver al Panel de Control</a>
    <h2>Reporte de Turnos e Historias Clínicas Digitales</h2>
        <!-- 📊 Botón 1: Descargar Agenda General (Rojo) -->
    <button onclick="exportarReportePDF()" class="btn-pdf" style="background: #dc2626; color: white; padding: 10px 18px; border: none; border-radius: 6px; font-weight: bold; font-size: 14px; cursor: pointer; float: right; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(220,38,38,0.2);">🖨️ Descargar Agenda General</button>
    
    <!-- 📋 Botón 2: Exportar el Historial Clínico de Pacientes (Azul) -->
    <button onclick="exportarReportePacientesPDF()" class="btn-pdf" style="background: #2563eb; color: white; padding: 10px 18px; border: none; border-radius: 6px; font-weight: bold; font-size: 14px; cursor: pointer; float: right; margin-bottom: 20px; margin-right: 10px; box-shadow: 0 4px 10px rgba(37,99,235,0.2);">📋 Reporte Clínico de Pacientes</button>

    <!-- Membrete Dinámico Oculto que se activa solo en el PDF descargado -->
    <div class="encabezado-pdf-pacientes" style="display: none; border-bottom: 3px solid #003366; padding-bottom: 10px; margin-bottom: 25px;">
        <h1 style="color: #003366; font-size: 22px;">OdontoSync - Reporte Consolidado de Expedientes Clínicos</h1>
        <p>Historial General de Evolución y Diagnósticos Odontológicos • Auditado por: <strong>Limber Ramos Espinal</strong></p>
    </div>

    <?php if (count($citas) === 0): ?>
        <p style="color: #64748b; margin-top: 10px;">No existen turnos agendados en la base de datos actualmente.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha / Hora</th>
                    <th>Paciente</th>
                    <th>Odontólogo / Especialidad</th>
                    <th>Tratamiento</th>
                    <th>Costo</th>
                   <th class="col-historial">Evolución / Historia Clínica (Doctor)</th>
                   <th class="col-acciones">Estado / Acciones</th>

                </tr>
            </thead>
            <tbody>
                <?php foreach ($citas as $c): ?>
                    <tr>
                        <td><strong>#<?php echo $c['id_turno']; ?></strong></td>
                        <td><?php echo date('d/m/Y', strtotime($c['fecha_turno'])) . ' - ' . substr($c['hora_turno'], 0, 5); ?></td>
                        <td><strong><?php echo htmlspecialchars($c['pac_nombre'] . ' ' . $c['pac_apellido']); ?></strong></td>
                        <td>
                            <span style="font-weight: 600;">Dr. <?php echo htmlspecialchars($c['odo_nombre'] . ' ' . $c['odo_apellido']); ?></span>
                            <br><small style="color: #64748b; font-size:11px;"><?php echo htmlspecialchars($c['especialidad']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($c['nombre_servicio']); ?></td>
                        <td style="font-weight: bold; color: #16a34a;">Bs. <?php echo number_format($c['precio'], 2); ?></td>
                        
                        <!-- 🩺 CELDA DE HISTORIAL CLÍNICO CORREGIDA -->
                        <td class="col-historial">
                            <?php if (!empty($c['historial_clinico'])): ?>
                                <div class="historial-box">
                                    📝 <?php echo htmlspecialchars($c['historial_clinico']); ?>
                                </div>
                            <?php else: ?>
                                <span class="historial-empty">⏳ En espera de atención médica</span>
                            <?php endif; ?>
                          <td class="col-acciones">
                            <?php 
                                $color = 'pendiente';
                                if ($c['estado'] === 'Confirmado') $color = 'confirmado';
                                if ($c['estado'] === 'Completo') $color = 'completo';
                                if ($c['estado'] === 'Cancelado') $color = 'cancelado';
                            ?>
                            <span class="status-badge <?php echo $color; ?>"><?php echo $c['estado']; ?></span>
                            
                            <?php if ($_SESSION['rol'] === 'administrador'): ?>
                                <div class="action-container">
                                    <a href="actualizar_estado.php?id=<?php echo $c['id_turno']; ?>&nuevo_estado=Confirmado" class="btn-action-status btn-confirm">✓ Confirmar</a>
                                    <a href="actualizar_estado.php?id=<?php echo $c['id_turno']; ?>&nuevo_estado=Completo" class="btn-action-status btn-complete">⚡ Terminar</a>
                                    <a href="actualizar_estado.php?id=<?php echo $c['id_turno']; ?>&nuevo_estado=Cancelado" class="btn-action-status btn-cancel">✕ Cancelar</a>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<script>
    // 📊 Función para generar el PDF de la Agenda General (Botón Rojo)
    function exportarReportePDF() {
        const tituloOriginal = document.title;
        document.title = "Reporte_General_de_Turnos_Clinicos_OdontoSync";
        window.print(); // Dispara la ventana de guardado
        document.title = tituloOriginal;
    }

    // 🩺 Función para generar el PDF del Historial Clínico de Pacientes (Botón Azul)
    function exportarReportePacientesPDF() {
        document.body.classList.add('imprimir-pacientes');
        const tituloOriginal = document.title;
        document.title = "Reporte_Consolidado_Historias_Clinicas_Pacientes_OdontoSync";
        
        window.print(); // Dispara la ventana de guardado
        
        document.body.classList.remove('imprimir-pacientes');
        document.title = tituloOriginal;
    }
</script>

</body>
</html>
