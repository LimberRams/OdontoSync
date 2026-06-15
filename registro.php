<?php
// registro.php
session_start();
require_once 'conexion.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre           = trim($_POST['nombre']);
    $apellido         = trim($_POST['apellido']);
    $usuario_alias    = trim($_POST['usuario_alias']); // Nombre de usuario sin arroba
    $password         = trim($_POST['password']);
    $dni              = trim($_POST['dni']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $telefono         = trim($_POST['telefono']);

    // Se autocompleta el dominio automáticamente para la persistencia en MySQL
    $email_completo = $usuario_alias . "@odontosync.com";

    if (!empty($nombre) && !empty($apellido) && !empty($usuario_alias) && !empty($password) && !empty($dni)) {
        try {
            $pdo->beginTransaction();

            // 1. Validar duplicidad de usuario
            $checkEmail = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = :email");
            $checkEmail->execute(['email' => $email_completo]);
            
            // 2. Validar duplicidad de documento de identidad
            $checkDNI = $pdo->prepare("SELECT id_paciente FROM pacientes WHERE dni = :dni");
            $checkDNI->execute(['dni' => $dni]);

            if ($checkEmail->fetch()) {
                $mensaje = "<div class='alert error'>⚠️ El nombre de usuario '$usuario_alias' ya está ocupado. Intente con otro.</div>";
                $pdo->rollBack();
            } elseif ($checkDNI->fetch()) {
                $mensaje = "<div class='alert error'>⚠️ El documento DNI/CI '$dni' ya está registrado en el sistema.</div>";
                $pdo->rollBack();
            } else {
                // 3. Insertar en tabla maestra usuarios
                $sqlUser = "INSERT INTO usuarios (nombre, apellido, email, password, rol) VALUES (:nom, :ape, :ema, :pas, 'paciente')";
                $stmtUser = $pdo->prepare($sqlUser);
                $stmtUser->execute([
                    'nom' => $nombre,
                    'ape' => $apellido,
                    'ema' => $email_completo,
                    'pas' => $password
                ]);

                $id_nuevo_usuario = $pdo->lastInsertId();

                // 4. Insertar en tabla hija pacientes
                $sqlPac = "INSERT INTO pacientes (id_paciente, dni, fecha_nacimiento, telefono) VALUES (:id, :dni, :fec, :tel)";
                $stmtPac = $pdo->prepare($sqlPac);
                $stmtPac->execute([
                    'id'  => $id_nuevo_usuario,
                    'dni' => $dni,
                    'fec' => $fecha_nacimiento,
                    'tel' => $telefono
                ]);

                $pdo->commit();
                $mensaje = "<div class='alert success'>🎉 ¡Cuenta registrada con éxito! Tu usuario para ingresar es: <strong>$email_completo</strong>. <a href='login.php'>Iniciar Sesión aquí</a></div>";
            }
        } catch (\PDOException $e) {
            $pdo->rollBack();
            $mensaje = "<div class='alert error'>Fallo crítico en el motor de base de datos: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $mensaje = "<div class='alert error'>⚠️ Todos los campos marcados con asterisco (*) son obligatorios.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OdontoSync - Registro de Nuevos Pacientes</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background: #f0f4f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .reg-container { background: white; padding: 35px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); width: 100%; max-width: 550px; }
        h2 { color: #003366; text-align: center; margin-bottom: 5px; font-weight: 700; }
        p.subtitle { text-align: center; color: #64748b; font-size: 14px; margin-bottom: 25px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .full-width { grid-column: span 2; }
        label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #334155; }
        input { width: 100%; padding: 11px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none; }
        input:focus { border-color: #0066cc; }
        
        /* Contenedor de Nombre de Usuario Autocompletado */
        .email-input-box { display: flex; align-items: center; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; }
        .email-input-box input { border: none; flex: 1; padding: 11px; }
        .email-domain { background: #e2e8f0; color: #475569; padding: 11px 12px; font-weight: bold; font-size: 14px; border-left: 1px solid #cbd5e1; user-select: none; }
        
        /* Contenedor del Ojito de Contraseña */
        .password-input-box { display: flex; align-items: center; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; position: relative; }
        .password-input-box input { border: none; flex: 1; padding: 11px; }
        .toggle-password { cursor: pointer; padding: 0 15px; user-select: none; font-size: 18px; color: #64748b; font-weight: bold; }
        .toggle-password:hover { color: #0066cc; }

        .btn-reg { width: 100%; padding: 13px; background: #0066cc; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 15px; transition: background 0.2s; }
        .btn-reg:hover { background: #0052a3; }
        .alert { padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .success { background: #dcfce7; color: #14532d; border: 1px solid #bbf7d0; }
        .error { background: #fee2e2; color: #7f1d1d; border: 1px solid #fecaca; }
        .login-link { text-align: center; margin-top: 20px; font-size: 14px; }
        .login-link a { color: #0066cc; text-decoration: none; font-weight: bold; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="reg-container">
    <h2>Crear Cuenta de Paciente</h2>
    <p class="subtitle">OdontoSync - Registro de Usuarios</p>

    <?php echo $mensaje; ?>

    <form action="registro.php" method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label for="nombre">Nombre(s) *</label>
                <input type="text" id="nombre" name="nombre" placeholder="Ej: Ana" required>
            </div>
            <div class="form-group">
                <label for="apellido">Apellido(s) *</label>
                <input type="text" id="apellido" name="apellido" placeholder="Ej: Gomez" required>
            </div>
            
            <!-- Campo modificado: Nombre de usuario con dominio adjunto -->
            <div class="form-group full-width">
                <label for="usuario_alias">Cree su Nombre de Usuario *</label>
                <div class="email-input-box">
                    <input type="text" id="usuario_alias" name="usuario_alias" placeholder="Ej: ana.gomez" required>
                    <span class="email-domain">@odontosync.com</span>
                </div>
            </div>

            <!-- Campo modificado: Contraseña interactiva con el "Ojito" -->
            <div class="form-group full-width">
                <label for="password">Contraseña de Acceso *</label>
                <div class="password-input-box">
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <span id="btnToggle" class="toggle-password" onclick="togglePasswordVisibility()">👁️</span>
                </div>
            </div>

            <div class="form-group">
                <label for="dni">Documento DNI / CI *</label>
                <input type="text" id="dni" name="dni" placeholder="Ej: 87654321" required>
            </div>
            <div class="form-group">
                <label for="fecha_nacimiento">Fecha de Nacimiento *</label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required>
            </div>
            <div class="form-group full-width">
                <label for="telefono">Número de Celular / WhatsApp *</label>
                <input type="tel" id="telefono" name="telefono" placeholder="Ej: 71234567" required>
            </div>
        </div>

        <button type="submit" class="btn-reg">🎯 Registrarme e Ingresar</button>
    </form>

    <div class="login-link">
        ¿Ya tienes una cuenta activa? <a href="login.php">Iniciar Sesión</a>
    </div>
</div>

<script>
    // 1. Script para limpiar arrobas si el usuario intenta meterlas manualmente
    document.getElementById('usuario_alias').addEventListener('input', function(e) {
        this.value = this.value.replace(/@/g, '');
    });

    // 2. Script interactivo del ojito para alternar la visibilidad de la clave (Caja Negra UX)
    function togglePasswordVisibility() {
        const passwordField = document.getElementById('password');
        const toggleButton = document.getElementById('btnToggle');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleButton.textContent = '🔒'; // Cambia el icono cuando se ve el texto
        } else {
            passwordField.type = 'password';
            toggleButton.textContent = '👁️'; // Vuelve a poner el ojito
        }
    }
</script>

</body>
</html>
