<?php
// login.php 
session_start();
if (isset($_SESSION['id_usuario'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OdontoSync - Iniciar Sesión</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body { background: #f0f4f8; display: flex; height: 100vh; overflow: hidden; }
        
        .login-wrapper { display: flex; width: 100%; height: 100vh; }
        
        /* 🔐 PANEL IZQUIERDO: Formulario de Autenticación */
        .login-form-side { 
            flex: 1; 
            background: #ffffff; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            padding: 40px; 
            box-shadow: 10px 0 30px rgba(0,0,0,0.05);
            z-index: 3;
        }
        
        .login-container { width: 100%; max-width: 380px; }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header h1 { color: #003366; font-size: 32px; font-weight: 800; letter-spacing: -0.5px; }
        .login-header p { color: #64748b; font-size: 14px; margin-top: 5px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #334155; font-weight: 600; font-size: 14px; }
        
        /* Caja Autocompletada */
        .user-input-box { display: flex; align-items: center; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; transition: border 0.2s; }
        .user-input-box:focus-within { border-color: #0066cc; box-shadow: 0 0 0 3px rgba(0,102,204,0.1); }
        .user-input-box input { border: none; flex: 1; padding: 12px; font-size: 14px; outline: none; }
        .user-domain { background: #f1f5f9; color: #475569; padding: 12px; font-weight: bold; font-size: 14px; border-left: 1px solid #cbd5e1; user-select: none; }
        
        /* Caja con Ojito */
        .password-input-box { display: flex; align-items: center; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; transition: border 0.2s; }
        .password-input-box:focus-within { border-color: #0066cc; box-shadow: 0 0 0 3px rgba(0,102,204,0.1); }
        .password-input-box input { border: none; flex: 1; padding: 12px; font-size: 14px; outline: none; }
        .toggle-password { cursor: pointer; padding: 0 15px; user-select: none; font-size: 18px; color: #64748b; font-weight: bold; }
        .toggle-password:hover { color: #0066cc; }

        .btn-login { width: 100%; padding: 13px; background: #0066cc; border: none; border-radius: 6px; color: #fff; font-size: 16px; font-weight: bold; cursor: pointer; transition: background 0.2s; margin-top: 5px; box-shadow: 0 4px 12px rgba(0,102,204,0.2); }
        .btn-login:hover { background: #0052a3; transform: translateY(-1px); }
        
        .alert { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; border: 1px solid #fca5a5; text-align: center; font-weight: 500; }
        .reg-link { text-align: center; margin-top: 25px; font-size: 14px; color: #64748b; }
        .reg-link a { color: #0066cc; text-decoration: none; font-weight: bold; }
        .reg-link a:hover { text-decoration: underline; }

        /* 📸 PANEL DERECHO: Imagen Corporativa e Información Institucional */
        .login-image-side { 
            flex: 1.4; 
            background-image: url('https://unsplash.com'); 
            background-size: cover; 
            background-position: center; 
            position: relative;
            display: flex;
            align-items: center;
            padding: 60px;
        }
        
        .login-image-side::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, rgba(0, 31, 102, 0.92), rgba(0, 75, 150, 0.75));
        }

        /* Bloque de Información Corporativa Integrada */
        .brand-info-container {
            position: relative;
            color: white;
            z-index: 2;
            max-width: 650px;
        }
        .brand-info-container h2 { font-size: 42px; font-weight: 800; margin-bottom: 25px; text-shadow: 0 4px 10px rgba(0,0,0,0.3); letter-spacing: -0.5px; }
        
        .info-section { margin-bottom: 22px; background: rgba(255, 255, 255, 0.08); padding: 15px 20px; border-radius: 8px; border-left: 4px solid #3b82f6; backdrop-filter: blur(4px); }
        .info-section h3 { font-size: 15px; text-transform: uppercase; letter-spacing: 1px; color: #93c5fd; margin-bottom: 5px; font-weight: 700; }
        .info-section p { font-size: 14px; color: #f1f5f9; line-height: 1.4; }

        /* Sección de Médicos Destacados */
        .doctors-strip { display: flex; gap: 15px; margin-top: 10px; }
        .doc-badge { display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.15); padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; color: #fff; }

        @media (max-width: 900px) {
            .login-image-side { display: none; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    
    <!-- 🔐 LADO IZQUIERDO: Formulario Avanzado -->
    <div class="login-form-side">
        <div class="login-container">
            <div class="login-header">
                <h1>Iniciar Sesión</h1>
                <p>Bienvenido al Sistema de Gestión Operativa</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert">
                    <?php
                        switch ($_GET['error']) {
                            case 'campos_vacios': echo "Por favor, llene todos los campos requeridos."; break;
                            case 'password_incorrecto': echo "Contraseña incorrecta. Intente nuevamente."; break;
                            case 'usuario_no_encontrado': echo "El nombre de usuario ingresado no existe."; break;
                            case 'error_servidor': echo "Error de conexión interna en el servidor de datos."; break;
                            default: echo "Intento de acceso no autorizado."; break;
                        }
                    ?>
                </div>
            <?php endif; ?>

            <form action="login_process.php" method="POST">
                <div class="form-group">
                    <label for="usuario_alias">Nombre de Usuario</label>
                    <div class="user-input-box">
                        <input type="text" id="usuario_alias" name="usuario_alias" placeholder="Ej: admin o carlos" required>
                        <span class="user-domain">@odontosync.com</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="password-input-box">
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                        <span id="btnToggle" class="toggle-password" onclick="togglePasswordVisibility()">👁️</span>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">Ingresar al Sistema</button>
            </form>

            <div class="reg-link">
                ¿Es un paciente nuevo? <a href="registro.php">Regístrate aquí</a>
            </div>
        </div>
    </div>

    <!-- 📸 LADO DERECHO: Misión, Visión y Staff Médico -->
    <div class="login-image-side">
        <div class="brand-info-container">
            <h2>OdontoSync</h2>
            
            <div class="info-section">
                <h3>Nuestra Misión</h3>
                <p>Optimizar la gestión odontológica integral mediante soluciones tecnológicas innovadoras, garantizando la eficiencia en el control de turnos y la máxima excelencia en la atención clínica de nuestros pacientes.</p>
            </div>

            <div class="info-section">
                <h3>Nuestra Visión</h3>
                <p>Consolidarnos como la plataforma líder en digitalización clínica odontológica a nivel nacional, reconocidos por nuestra seguridad de datos por roles, escalabilidad y mejora continua del servicio de salud.</p>
            </div>

            <div class="info-section" style="border-left-color: #10b981;">
                <h3>Especialistas de Turno Hoy</h3>
                <div class="doctors-strip">
                    <span class="doc-badge">👨‍⚕️ Dr. Carlos Mendoza (Ortodoncia)</span>
                    <span class="doc-badge">👩‍⚕️ Dra. Beatriz Luna (Odontopediatría)</span>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    document.getElementById('usuario_alias').addEventListener('input', function(e) {
        this.value = this.value.replace(/@/g, '');
    });

    function togglePasswordVisibility() {
        const passwordField = document.getElementById('password');
        const toggleButton = document.getElementById('btnToggle');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleButton.textContent = '🔒';
        } else {
            passwordField.type = 'password';
            toggleButton.textContent = '👁️';
        }
    }
</script>

</body>
</html>
