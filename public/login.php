<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Citas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 50px;
            max-width: 450px;
            width: 100%;
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .login-header .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .login-header .logo i {
            font-size: 40px;
            color: white;
        }
        .login-header h2 {
            color: #333;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #6c757d;
            font-size: 14px;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
            border-color: #dee2e6;
        }
        .form-control {
            border-left: none;
            border-color: #dee2e6;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            border-left: 1px solid #667eea;
        }
        .form-control:focus + .input-group-text {
            border-color: #667eea;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 14px;
            font-weight: 600;
            font-size: 16px;
            border-radius: 10px;
            margin-top: 25px;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #5568d3 0%, #63357d 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 25px;
            border: none;
        }
        .password-toggle {
            cursor: pointer;
            user-select: none;
            transition: color 0.3s ease;
        }
        .password-toggle:hover {
            color: #667eea;
        }
        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e9ecef;
        }
        .register-link a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .register-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        .spinner-border-sm {
            display: none;
        }
        .btn-login.loading .spinner-border-sm {
            display: inline-block;
        }
        .btn-login.loading .btn-text {
            display: none;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-calendar-check"></i>
            </div>
            <h2>Bienvenido</h2>
            <p>Ingresa tus credenciales para continuar</p>
        </div>

        <div id="alertContainer"></div>

        <form id="formLogin" method="POST">
            <!-- Usuario -->
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuario</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" 
                           class="form-control" 
                           id="usuario" 
                           name="usuario"
                           placeholder="Ingrese su usuario"
                           required
                           autocomplete="username"
                           autofocus>
                </div>
            </div>

            <!-- Contraseña -->
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password"
                           placeholder="Ingrese su contraseña"
                           required
                           autocomplete="current-password">
                    <span class="input-group-text password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </span>
                </div>
            </div>

            <!-- Recordarme -->
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="recordarme" name="recordarme">
                <label class="form-check-label" for="recordarme">
                    Recordar mi sesión
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-login w-100">
                <span class="btn-text"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</span>
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            </button>
        </form>

        <div class="register-link">
            <p class="mb-0">¿No tienes una cuenta? <a href="registro_empresa.php">Regístrate aquí</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const form = document.getElementById('formLogin');
        const btnLogin = document.querySelector('.btn-login');

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const usuario = document.getElementById('usuario').value.trim();
            const password = document.getElementById('password').value;
            
            if (!usuario || !password) {
                showAlert('Por favor, complete todos los campos.', 'danger');
                return;
            }
            
            // Mostrar loading
            btnLogin.classList.add('loading');
            btnLogin.disabled = true;
            
            const formData = new FormData(form);
            
            fetch('autenticar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btnLogin.classList.remove('loading');
                btnLogin.disabled = false;
                
                if (data.ok) {
                    showAlert('¡Inicio de sesión exitoso! Redirigiendo...', 'success');
                    
                    // Redireccionar al dashboard después de 1 segundo
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1000);
                } else {
                    showAlert(data.error || 'Error al iniciar sesión. Verifique sus credenciales.', 'danger');
                }
            })
            .catch(error => {
                btnLogin.classList.remove('loading');
                btnLogin.disabled = false;
                showAlert('Error de conexión: ' + error.message, 'danger');
            });
        });

        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.innerHTML = '';
            alertContainer.appendChild(alert);
            
            // Auto-cerrar después de 5 segundos
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Verificar si hay mensaje de sesión expirada en URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('sesion') === 'expirada') {
            showAlert('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.', 'warning');
        } else if (urlParams.get('logout') === 'success') {
            showAlert('Has cerrado sesión exitosamente.', 'success');
        }
    </script>
</body>
</html>
