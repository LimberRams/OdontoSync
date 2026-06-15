<?php
// dashboard.php
session_start();
require_once 'conexion.php';

// Control de Acceso Absoluto: Si no hay sesión, al Login de inmediato
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

$nombre_usuario = $_SESSION['nombre'] . ' ' . $_SESSION['apellido'];
$rol_usuario    = $_SESSION['rol'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - OdontoSync</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background: #f4f7f6; display: flex; min-height: 100vh; color: #334155; }
        
        /* Barra Lateral Izquierda */
        .sidebar { width: 280px; background: #003366; color: white; padding: 25px; display: flex; flex-direction: column; box-shadow: 4px 0 10px rgba(0,0,0,0.05); }
        .sidebar h2 { font-size: 24px; text-align: center; margin-bottom: 25px; border-bottom: 1px solid #004488; padding-bottom: 12px; font-weight: 700; letter-spacing: 0.5px; }
        .sidebar p { font-size: 14px; margin-bottom: 25px; color: #cbd5e1; text-align: center; line-height: 1.4; }
        .sidebar a { color: white; text-decoration: none; padding: 12px 15px; border-radius: 6px; margin-bottom: 8px; display: block; transition: all 0.2s; font-size: 15px; font-weight: 500; }
        .sidebar a:hover { background: #004488; padding-left: 20px; }
        .sidebar .logout { background: #dc2626; margin-top: auto; text-align: center; font-weight: bold; }
        .sidebar .logout:hover { background: #b91c1c; padding-left: 15px; }
        
        /* Contenedor Principal Plano (Limpio y Corporativo) */
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .header { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 35px; border-left: 5px solid #003366; }
        .header h1 { color: #0f172a; font-size: 26px; font-weight: 700; }
        .header p { color: #64748b; font-size: 14px; margin-top: 5px; }
        
        /* Malla Mapeada de Tarjetas Operativas */
        .grid-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-top: 4px solid #0066cc; display: flex; flex-direction: column; }
        .card h3 { margin-bottom: 10px; color: #003366; font-size: 18px; font-weight: 600; }
        .card p { color: #64748b; font-size: 14px; margin-bottom: 20px; line-height: 1.5; }
        .btn-action { display: block; text-align: center; margin-top: auto; padding: 11px 20px; background: #0066cc; color: white; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: bold; transition: background 0.2s; }
        .btn-action:hover { background: #0052a3; }
        
        .badge { background: #e2e8f0; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; margin-top: 8px; }
    </style>
</head>
<body>

    <!-- Menú de la Barra Lateral Dinámica -->
    <div class="sidebar">
        <h2>OdontoSync</h2>
        <p>Bienvenido de vuelta,<br><strong><?php echo htmlspecialchars($nombre_usuario); ?></strong> <br><span class="badge"><?php echo $rol_usuario; ?></span></p>
        
        <a href="dashboard.php">🏠 Inicio del Sistema</a>
        
        <!-- Enlaces Autorizados para el Administrador (Limber) -->
        <?php if ($rol_usuario === 'administrador'): ?>
            <a href="ver_citas.php">🗂️ Listado de Citas</a>
            <a href="administrar_clinica.php">💼 Cuadro Estadístico</a>
        <?php endif; ?>

        <!-- Enlaces Autorizados para el Paciente -->
<?php if ($rol_usuario === 'odontologo'): ?>
    <a href="ver_citas.php">🗂️ Revisar Mi Agenda</a>
    <a href="historial.php">📋 Registrar Diagnóstico</a> <!-- Añade esta línea -->
<?php endif; ?>

        <a href="logout.php" class="logout">🚪 Cerrar Sesión</a>
    </div>

    <!-- Espacio de Trabajo Principal Plano -->
    <div class="main-content">
        <div class="header">
            <h1>Panel de Control Institucional</h1>
            <p>Seleccione el módulo operativo autorizado para gestionar hoy.</p>
        </div>

        <!-- Módulos de Gestión Distribuidos Dinámicamente por Rol -->
        <div class="grid-options">
            
            <!-- VISTA EXCLUSIVA DEL PACIENTE -->
            <?php if ($rol_usuario === 'paciente'): ?>
                <div class="card">
                    <h3>Módulo de Reservas</h3>
                    <p>Agenda tus citas médicas directamente en el sistema seleccionando la fecha, hora, el odontólogo especialista y tu modalidad de pago.</p>
                    <a href="reservar.php" class="btn-action">Agendar Mi Turno</a>
                </div>

                <div class="card" style="border-top-color: #3b82f6;">
                    <h3>Mis Citas Médicas</h3>
                    <p>Revise el historial completo de sus turnos agendados, consulte los horarios médicos asignados y controle su plan de pagos seleccionado.</p>
                    <a href="mis_citas.php" class="btn-action" style="background:#3b82f6;">Revisar Mis Turnos</a>
                </div>
            <?php endif; ?>
<!-- VISTA EXCLUSIVA DEL ODONTÓLOGO -->
<?php if ($rol_usuario === 'odontologo'): ?>
    <div class="card" style="border-top-color: #3b82f6;">
        <h3>Mi Agenda y Pacientes</h3>
        <p>Revisa el listado completo de citas clínicas asignadas a tu nombre. Monitorea tus horarios y prepara las consultas del día.</p>
        <a href="ver_citas.php" class="btn-action" style="background: #3b82f6; margin-bottom: 8px;">Ver Mis Turnos</a>
        <a href="historial.php" class="btn-action" style="background: #003366;">Registrar Evolución Médica</a>
    </div>
<?php endif; ?>


            <!-- VISTA EXCLUSIVA DEL ADMINISTRADOR (LIMBER) -->
            <?php if ($rol_usuario === 'administrador'): ?>
                <div class="card">
                    <h3>Auditoría de Consultas</h3>
                    <p>Controla el listado completo de citas clínicas del consultorio. Permite al administrador validar la agenda y cambiar estados en tiempo real.</p>
                    <a href="ver_citas.php" class="btn-action">Ver Registro Clínico</a>
                </div>

                <div class="card" style="border-top-color: #16a34a;">
                    <h3>Administrar Clínica</h3>
                    <p>Acceda de forma segura al cuadro de mando contable, revise la recaudación bruta acumulada y gestione los aranceles institucionales.</p>
                    <a href="administrar_clinica.php" class="btn-action" style="background:#16a34a;">Ver Estadísticas Generales</a>
                </div>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>
