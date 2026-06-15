<?php
// reservar.php
session_start();
require_once 'conexion.php';

// Seguridad: Solo los pacientes pueden reservar citas de forma autónoma
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'paciente') {
    header("Location: login.php");
    exit;
}

$mensaje = "";

// Procesar la inserción de la cita en la base de datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $odontologo_id  = $_POST['odontologo_id'];
    $servicio_id    = $_POST['servicio_id'];
    $fecha_turno    = $_POST['fecha_turno'];
    $hora_turno     = $_POST['hora_turno'];
    $metodo_pago    = $_POST['metodo_pago']; // Captura de la nueva modalidad de pago
    $paciente_id    = $_SESSION['id_usuario']; 

    // Texto descriptivo que se inyectará en las observaciones para el reporte del administrador
    $observaciones_pago = "Modalidad elegida por el paciente: " . $metodo_pago;

    try {
        // Validación de Caja Blanca: Evitar duplicidad de citas
        $validar = $pdo->prepare("SELECT id_turno FROM turnos WHERE odontologo_id = :odo AND fecha_turno = :fec AND hora_turno = :hor AND estado != 'Cancelado'");
        $validar->execute(['odo' => $odontologo_id, 'fec' => $fecha_turno, 'hor' => $hora_turno]);
        
        if ($validar->fetch()) {
            $mensaje = "<div class='alert error'>⚠️ Conflicto de horario: El odontólogo ya está ocupado en ese bloque.</div>";
        } else {
            // Se inserta guardando la modalidad en la columna observaciones
            $sql = "INSERT INTO turnos (odontologo_id, paciente_id, servicio_id, fecha_turno, hora_turno, estado, observaciones) 
                    VALUES (:odo, :pac, :ser, :fec, :hor, 'Pendiente', :obs)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'odo' => $odontologo_id,
                'pac' => $paciente_id,
                'ser' => $servicio_id,
                'fec' => $fecha_turno,
                'hor' => $hora_turno,
                'obs' => $observaciones_pago
            ]);
            $mensaje = "<div class='alert success'>✅ ¡Turno registrado con éxito! Tu plan de pago ha sido procesado.</div>";
        }
    } catch (\PDOException $e) {
        $mensaje = "<div class='alert error'>Fallo crítico de persistencia: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Consultas dinámicas para la interfaz
$odontologos = $pdo->query("SELECT o.id_odontologo, u.nombre, u.apellido, o.especialidad FROM odontologos o JOIN usuarios u ON o.id_odontologo = u.id_usuario")->fetchAll();
$servicios   = $pdo->query("SELECT id_servicio, nombre_servicio, precio FROM servicios")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OdontoSync - Control de Reservas</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background: #f4f7f6; padding: 40px; display: flex; justify-content: center; }
        .container { background: white; padding: 35px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); width: 100%; max-width: 500px; }
        h2 { color: #003366; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; text-align: center; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 14px; }
        .form-group select, .form-group input { width: 100%; padding: 11px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none; }
        .form-group select:focus, .form-group input:focus { border-color: #0066cc; }
        
        /* Contenedor dinámico del precio */
        .precio-box { background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px dashed #0066cc; margin-bottom: 18px; text-align: center; }
        .precio-box p { font-size: 14px; color: #64748b; }
        .precio-box span { font-size: 24px; font-weight: bold; color: #16a34a; display: block; margin-top: 5px; }

        .btn-submit { width: 100%; padding: 12px; background: #0066cc; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 10px; transition: 0.2s; }
        .btn-submit:hover { background: #0052a3; }
        .btn-back { display: block; text-align: center; margin-top: 20px; color: #64748b; text-decoration: none; font-size: 14px; }
        .btn-back:hover { color: #1e293b; text-decoration: underline; }
        .alert { padding: 12px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .success { background: #dcfce7; color: #14532d; border: 1px solid #bbf7d0; }
        .error { background: #fee2e2; color: #7f1d1d; border: 1px solid #fecaca; }
    </style>
</head>
<body>

<div class="container">
    <h2>Agendar Mi Cita Médica</h2>
    
    <?php echo $mensaje; ?>

    <form action="reservar.php" method="POST">
        <div class="form-group">
            <label for="odontologo_id">Especialista Odontológico</label>
            <select name="odontologo_id" id="odontologo_id" required>
                <option value="">-- Seleccionar Médico --</option>
                <?php foreach ($odontologos as $odo): ?>
                    <option value="<?php echo $odo['id_odontologo']; ?>">
                        Dr. <?php echo htmlspecialchars($odo['nombre'] . ' ' . $odo['apellido'] . ' (' . $odo['especialidad'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Al cambiar este select, JavaScript leerá el precio en caliente -->
        <div class="form-group">
            <label for="servicio_id">Tratamiento Requerido</label>
            <select name="servicio_id" id="servicio_id" onchange="calcularMontoTratamiento()" required>
                <option value="" data-precio="0">-- Seleccionar Servicio --</option>
                <?php foreach ($servicios as $ser): ?>
                    <option value="<?php echo $ser['id_servicio']; ?>" data-precio="<?php echo $ser['precio']; ?>">
                        <?php echo htmlspecialchars($ser['nombre_servicio']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- 💰 NUEVO COMPONENTE: Visualizador de Montos en tiempo real -->
        <div class="precio-box">
            <p>Monto total asignado a pagar:</p>
            <span id="monto_visible">Bs. 0.00</span>
        </div>

        <!-- 💳 NUEVO COMPONENTE: Selección de Modalidad Financiera -->
        <div class="form-group">
            <label for="metodo_pago">¿Cómo desea realizar el pago?</label>
            <select name="metodo_pago" id="metodo_pago" onchange="calcularMontoTratamiento()" required>
                <option value="Todo de golpe (Pago Completo)">Todo de golpe (Pago Completo)</option>
                <option value="Por sesiones (Financiamiento)">Por sesiones (Financiamiento a 3 cuotas)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="fecha_turno">Fecha Programada</label>
            <input type="date" id="fecha_turno" name="fecha_turno" min="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="form-group">
            <label for="hora_turno">Bloque de Horario</label>
            <input type="time" id="hora_turno" name="hora_turno" required>
        </div>

        <button type="submit" class="btn-submit">Confirmar y Agendar Turno</button>
    </form>

    <a href="dashboard.php" class="btn-back">⬅️ Volver al Panel de Control</a>
</div>

<script>
// Lógica frontend en JavaScript para calcular los montos y las cuotas en vivo (Pruebas UX)
function calcularMontoTratamiento() {
    const selectServicio = document.getElementById('servicio_id');
    const selectPago = document.getElementById('metodo_pago');
    const montoVisible = document.getElementById('monto_visible');

    // Recuperamos el precio guardado en el atributo experimental "data-precio" de la opción seleccionada
    const opciónSeleccionada = selectServicio.options[selectServicio.selectedIndex];
    const precioBase = parseFloat(opciónSeleccionada.getAttribute('data-precio')) || 0;

    if (precioBase === 0) {
        montoVisible.textContent = "Bs. 0.00";
        return;
    }

    if (selectPago.value === "Por sesiones (Financiamiento)") {
        // Cálculo matemático de cuotas fraccionadas
        const cuota = (precioBase / 3).toFixed(2);
        montoVisible.innerHTML = `Bs. ${precioBase.toFixed(2)} <br><small style='font-size:12px; color:#3b82f6;'>O en 3 cuotas de Bs. ${cuota} por sesión</small>`;
    } else {
        montoVisible.textContent = `Bs. ${precioBase.toFixed(2)}`;
    }
}
</script>

</body>
</html>
