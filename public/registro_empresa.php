<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Empresa</title>
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
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h2 {
            color: #667eea;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .register-header p {
            color: #6c757d;
            font-size: 14px;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        .required::after {
            content: " *";
            color: #dc3545;
        }
        .password-requirements {
            background: #f8f9fa;
            border-left: 3px solid #667eea;
            padding: 15px;
            margin-top: 10px;
            border-radius: 5px;
            font-size: 13px;
        }
        .password-requirements h6 {
            color: #667eea;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }
        .password-requirements li {
            color: #6c757d;
            margin-bottom: 5px;
        }
        .password-requirements li.valid {
            color: #28a745;
        }
        .password-requirements li.invalid {
            color: #dc3545;
        }
        .password-requirements li i {
            margin-right: 8px;
            width: 15px;
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            font-size: 16px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #5568d3 0%, #63357d 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        .form-control {
            border-left: none;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .password-toggle {
            cursor: pointer;
            user-select: none;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="register-header">
            <h2><i class="fas fa-building"></i> Registro de Empresa</h2>
            <p>Complete el formulario para registrar su negocio</p>
        </div>

        <div id="alertContainer"></div>

        <form id="formRegistroEmpresa" method="POST">
            <!-- Nombre de Empresa -->
            <div class="mb-3">
                <label for="nombreEmpresa" class="form-label required">Empresa o negocio</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                    <input type="text" 
                           class="form-control" 
                           id="nombreEmpresa" 
                           name="nombre_empresa"
                           placeholder="Ingrese el nombre de la empresa"
                           required
                           maxlength="100">
                </div>
            </div>

            <!-- Teléfono de Contacto -->
            <div class="mb-3">
                <label for="telefonoContacto" class="form-label required">Teléfono de Contacto</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    <input type="text" 
                           class="form-control" 
                           id="telefonoContacto" 
                           name="telefono_contacto"
                           placeholder="Ej: +521234567890"
                           required
                           maxlength="40">
                </div>
                <small class="text-muted">Incluya el código de país (Ej: +52 para México)</small>
            </div>

            <!-- Dirección -->
            <div class="mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                    <input type="text" 
                           class="form-control" 
                           id="direccion" 
                           name="direccion"
                           placeholder="Ingrese la dirección (opcional)"
                           maxlength="150">
                </div>
            </div>

            <!-- Usuario -->
            <div class="mb-3">
                <label for="usuario" class="form-label required">Usuario</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" 
                           class="form-control" 
                           id="usuario" 
                           name="usuario"
                           placeholder="Ingrese el nombre de usuario"
                           required
                           maxlength="150"
                           pattern="[a-zA-Z0-9_-]+"
                           title="Solo letras, números, guiones y guiones bajos">
                    <span class="input-group-text" id="usuarioCheck" style="display: none;">
                        <i class="fas fa-spinner fa-spin" id="usuarioCheckIcon"></i>
                    </span>
                </div>
                <small class="text-muted">Solo letras, números, guiones (-) y guiones bajos (_)</small>
                <small id="usuarioFeedback" class="d-block mt-1" style="display: none;"></small>
            </div>

            <!-- Contraseña -->
            <div class="mb-3">
                <label for="password" class="form-label required">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password"
                           placeholder="Ingrese una contraseña segura"
                           required
                           maxlength="150">
                    <span class="input-group-text password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </span>
                </div>
                
                <div class="password-requirements">
                    <h6><i class="fas fa-shield-alt"></i> Requisitos de Seguridad:</h6>
                    <ul id="passwordChecks">
                        <li id="length"><i class="fas fa-circle"></i>Mínimo 8 caracteres</li>
                        <li id="uppercase"><i class="fas fa-circle"></i>Al menos una letra mayúscula (A-Z)</li>
                        <li id="lowercase"><i class="fas fa-circle"></i>Al menos una letra minúscula (a-z)</li>
                        <li id="number"><i class="fas fa-circle"></i>Al menos un número (0-9)</li>
                        <li id="special"><i class="fas fa-circle"></i>Al menos un carácter especial (!@#$%^&*)</li>
                    </ul>
                </div>
            </div>

            <!-- Confirmar Contraseña -->
            <div class="mb-3">
                <label for="confirmarPassword" class="form-label required">Confirmar Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" 
                           class="form-control" 
                           id="confirmarPassword" 
                           name="confirmar_password"
                           placeholder="Confirme su contraseña"
                           required
                           maxlength="150">
                    <span class="input-group-text password-toggle" onclick="toggleConfirmPassword()">
                        <i class="fas fa-eye" id="toggleConfirmIcon"></i>
                    </span>
                </div>
                <small id="passwordMatch" class="text-danger" style="display: none;">Las contraseñas no coinciden</small>
            </div>

            <button type="submit" class="btn btn-primary btn-register w-100">
                <i class="fas fa-save"></i> Registrar Empresa
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const passwordInput = document.getElementById('password');
        const confirmarPasswordInput = document.getElementById('confirmarPassword');
        const usuarioInput = document.getElementById('usuario');
        const form = document.getElementById('formRegistroEmpresa');
        
        let usuarioDisponible = false;
        let verificandoUsuario = false;
        let timeoutVerificacion = null;

        // Validación de usuario en tiempo real
        usuarioInput.addEventListener('input', function() {
            const usuario = this.value.trim();
            const usuarioCheck = document.getElementById('usuarioCheck');
            const usuarioCheckIcon = document.getElementById('usuarioCheckIcon');
            const usuarioFeedback = document.getElementById('usuarioFeedback');
            
            // Limpiar timeout anterior
            if (timeoutVerificacion) {
                clearTimeout(timeoutVerificacion);
            }
            
            // Si el usuario está vacío o no cumple el patrón, no verificar
            if (usuario.length === 0 || !/^[a-zA-Z0-9_-]+$/.test(usuario)) {
                usuarioCheck.style.display = 'none';
                usuarioFeedback.style.display = 'none';
                usuarioDisponible = false;
                usuarioInput.classList.remove('is-valid', 'is-invalid');
                return;
            }
            
            // Mostrar spinner
            usuarioCheck.style.display = 'block';
            usuarioCheckIcon.className = 'fas fa-spinner fa-spin';
            usuarioFeedback.style.display = 'none';
            usuarioInput.classList.remove('is-valid', 'is-invalid');
            
            // Esperar 500ms después de que el usuario deje de escribir
            timeoutVerificacion = setTimeout(() => {
                verificandoUsuario = true;
                
                fetch(`verificar_usuario.php?usuario=${encodeURIComponent(usuario)}`)
                    .then(response => response.json())
                    .then(data => {
                        verificandoUsuario = false;
                        
                        if (data.ok && data.disponible) {
                            // Usuario disponible
                            usuarioDisponible = true;
                            usuarioCheckIcon.className = 'fas fa-check-circle text-success';
                            usuarioFeedback.textContent = '✓ Usuario disponible';
                            usuarioFeedback.className = 'd-block mt-1 text-success';
                            usuarioFeedback.style.display = 'block';
                            usuarioInput.classList.remove('is-invalid');
                            usuarioInput.classList.add('is-valid');
                        } else {
                            // Usuario no disponible
                            usuarioDisponible = false;
                            usuarioCheckIcon.className = 'fas fa-times-circle text-danger';
                            usuarioFeedback.textContent = '✗ ' + (data.mensaje || 'El usuario ya está registrado');
                            usuarioFeedback.className = 'd-block mt-1 text-danger';
                            usuarioFeedback.style.display = 'block';
                            usuarioInput.classList.remove('is-valid');
                            usuarioInput.classList.add('is-invalid');
                            usuarioInput.setCustomValidity('Usuario no disponible');
                        }
                    })
                    .catch(error => {
                        verificandoUsuario = false;
                        usuarioCheck.style.display = 'none';
                        usuarioFeedback.textContent = 'Error al verificar usuario';
                        usuarioFeedback.className = 'd-block mt-1 text-warning';
                        usuarioFeedback.style.display = 'block';
                        usuarioDisponible = false;
                    });
            }, 500);
        });

        // Validación de contraseña en tiempo real
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            // Validar longitud
            validateRequirement('length', password.length >= 8);
            
            // Validar mayúscula
            validateRequirement('uppercase', /[A-Z]/.test(password));
            
            // Validar minúscula
            validateRequirement('lowercase', /[a-z]/.test(password));
            
            // Validar número
            validateRequirement('number', /[0-9]/.test(password));
            
            // Validar carácter especial
            validateRequirement('special', /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password));
            
            checkPasswordMatch();
        });

        confirmarPasswordInput.addEventListener('input', checkPasswordMatch);

        function validateRequirement(id, isValid) {
            const element = document.getElementById(id);
            const icon = element.querySelector('i');
            
            if (isValid) {
                element.classList.remove('invalid');
                element.classList.add('valid');
                icon.className = 'fas fa-check-circle';
            } else {
                element.classList.remove('valid');
                element.classList.add('invalid');
                icon.className = 'fas fa-times-circle';
            }
        }

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmar = confirmarPasswordInput.value;
            const matchElement = document.getElementById('passwordMatch');
            
            if (confirmar.length > 0) {
                if (password !== confirmar) {
                    matchElement.style.display = 'block';
                    confirmarPasswordInput.setCustomValidity('Las contraseñas no coinciden');
                } else {
                    matchElement.style.display = 'none';
                    confirmarPasswordInput.setCustomValidity('');
                }
            }
        }

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

        function toggleConfirmPassword() {
            const confirmInput = document.getElementById('confirmarPassword');
            const toggleIcon = document.getElementById('toggleConfirmIcon');
            
            if (confirmInput.type === 'password') {
                confirmInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                confirmInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        // Validación al enviar el formulario
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = passwordInput.value;
            const confirmar = confirmarPasswordInput.value;
            const usuario = usuarioInput.value.trim();
            
            // Verificar si hay una verificación de usuario en proceso
            if (verificandoUsuario) {
                showAlert('Por favor, espera mientras verificamos la disponibilidad del usuario.', 'warning');
                return;
            }
            
            // Validar que el usuario esté disponible
            if (!usuarioDisponible) {
                showAlert('El nombre de usuario ya está registrado. Por favor, elija otro.', 'danger');
                usuarioInput.focus();
                return;
            }
            
            // Validar requisitos de contraseña
            if (password.length < 8 || 
                !/[A-Z]/.test(password) || 
                !/[a-z]/.test(password) || 
                !/[0-9]/.test(password) || 
                !/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
                showAlert('Por favor, cumple con todos los requisitos de seguridad de la contraseña.', 'danger');
                return;
            }
            
            // Validar que las contraseñas coincidan
            if (password !== confirmar) {
                showAlert('Las contraseñas no coinciden.', 'danger');
                return;
            }
            
            // Enviar formulario
            const formData = new FormData(form);
            
            fetch('guardar_empresa.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    showAlert(
                        `¡Empresa registrada exitosamente!<br><br>
                        <strong>Tu código de empresa es: <span style="font-size: 1.3em; color: #667eea;">${data.codigo_empresa}</span></strong><br>
                        <small>Comparte este código con tus clientes para que puedan usar el servicio de WhatsApp.</small>`, 
                        'success'
                    );
                    form.reset();
                    resetPasswordValidation();
                    resetUsuarioValidation();
                    
                    // Scroll al inicio para ver el mensaje
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    showAlert(data.error || 'Error al registrar la empresa.', 'danger');
                }
            })
            .catch(error => {
                showAlert('Error de conexión: ' + error.message, 'danger');
            });
        });

        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
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

        function resetPasswordValidation() {
            const checks = ['length', 'uppercase', 'lowercase', 'number', 'special'];
            checks.forEach(id => {
                const element = document.getElementById(id);
                const icon = element.querySelector('i');
                element.classList.remove('valid', 'invalid');
                icon.className = 'fas fa-circle';
            });
            document.getElementById('passwordMatch').style.display = 'none';
        }

        function resetUsuarioValidation() {
            usuarioDisponible = false;
            usuarioInput.classList.remove('is-valid', 'is-invalid');
            usuarioInput.setCustomValidity('');
            document.getElementById('usuarioCheck').style.display = 'none';
            document.getElementById('usuarioFeedback').style.display = 'none';
        }
    </script>
</body>
</html>
