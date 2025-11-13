<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            background-color: #0F172A;
            color: #F9FAFB;
            overflow: hidden;
        }

        .split-container {
            display: flex;
            width: 100%;
            height: 100%;
        }

        .left-panel {
            flex: 1;
            background-color: #1E293B;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            box-shadow: 10px 0 20px rgba(0, 0, 0, 0.2);
        }

        .mountain-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            clip-path: polygon(0 40%, 50% 10%, 100% 40%, 100% 0, 0 0);
            background-color: #1E293B;
            z-index: -1;
            transition: all 0.5s ease-in-out;
        }

        .left-panel .logo {
            z-index: 1;
        }

        .left-panel .logo img {
            max-width: 300px;
            height: auto;
        }

        .right-panel {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
            background-color: #0F172A;
            overflow-y: auto;
        }

        .right-panel::-webkit-scrollbar {
            width: 0;
            background: transparent;
        }

        .right-panel {
            scrollbar-width: none;
        }

        .login-container {
            background-color: transparent;
            padding: 0;
            border-radius: 0;
            text-align: center;
            width: 100%;
            max-width: 400px;
            box-shadow: none;
            position: relative;
            z-index: 1;
            margin-top: 20px;
        }

        h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        p.subtitle {
            font-size: 16px;
            color: #64748B;
            margin-bottom: 30px;
        }

        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            display: block;
            color: #64748B;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .input-group input {
            width: 100%;
            padding: 15px;
            background-color: #1E293B;
            border: none;
            border-radius: 10px;
            color: #F9FAFB;
            font-size: 16px;
            box-sizing: border-box;
        }

        .input-group input:focus {
            outline: none;
            box-shadow: 0 0 0 2px #1D4ED8;
        }

        /* Estilos de JQuery Validation para el error */
        .input-group input.error {
            box-shadow: 0 0 0 2px #DC2626; /* Rojo fuerte */
        }
        
        label.error {
            color: #DC2626; /* Rojo para el mensaje de error */
            font-size: 12px;
            margin-top: 5px;
            display: block; /* Asegura que el mensaje se muestre debajo del input */
            font-weight: 600;
        }
        /* Fin de estilos de JQuery Validation */

        /* Se oculta el error-message nativo ya que JQuery Validation usará 'label.error' */
        .input-group .error-message {
            display: none !important;
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background-color: #1D4ED8;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #1E40AF;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: #64748B;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #334155;
        }

        .divider:not(:empty)::before {
            margin-right: .5em;
        }

        .divider:not(:empty)::after {
            margin-left: .5em;
        }

        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 15px;
            border: 1px solid #334155;
            background-color: transparent;
            color: #F9FAFB;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-google img {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }

        .btn-google:hover {
            background-color: #21324a;
        }

        .login-link {
            margin-top: 20px;
            font-size: 14px;
            color: #64748B;
        }

        .login-link a {
            color: #F9FAFB;
            font-weight: 600;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        #mensaje {
            color: #ffcc00;
            margin-top: 10px;
            font-size: 14px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .form-row .input-group {
            flex: 1;
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            .left-panel {
                display: none;
            }

            .right-panel {
                width: 100%;
            }

            .login-container {
                max-width: 90%;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .form-row .input-group {
                margin-bottom: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="split-container">
        <div class="left-panel">
            <div class="mountain-bg"></div>
            <div class="logo">
                <img src="../php/imagenes/logoempresa.png" alt="Logo de la empresa">
            </div>
        </div>
        <div class="right-panel">
            <div class="login-container">
                <h2>Crear Cuenta</h2>
                <p class="subtitle">Regístrate para continuar</p>
                <form id="crearCuentaForm">
                    <div class="form-row">
                        <div class="input-group">
                            <label for="nombre">Nombre</label>
                            <input type="text" name="nombre" id="nombre" placeholder=" " required>
                            <p class="error-message" id="nombre-error"></p>
                        </div>
                        <div class="input-group">
                            <label for="apellido">Apellido</label>
                            <input type="text" name="apellido" id="apellido" placeholder=" " required>
                            <p class="error-message" id="apellido-error"></p>
                        </div>
                    </div>
                    <div class="input-group">
                        <label for="correo">Correo Electrónico</label>
                        <input type="email" name="correo" id="correo" placeholder=" " required>
                        <p class="error-message" id="correo-error"></p>
                    </div>
                    <div class="form-row">
                        <div class="input-group">
                            <label for="contrasena">Contraseña</label>
                            <input type="password" name="contrasena" id="contrasena" placeholder=" " required>
                            <p class="error-message" id="contrasena-error"></p>
                        </div>
                        <div class="input-group">
                            <label for="confirmarContrasena">Confirmar Contraseña</label>
                            <input type="password" name="confirmarContrasena" id="confirmarContrasena" placeholder=" " required>
                            <p class="error-message" id="confirmarContrasena-error"></p>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="input-group">
                            <label for="celular">Celular</label>
                            <input type="tel" name="celular" id="celular" placeholder=" " required>
                            <p class="error-message" id="celular-error"></p>
                        </div>
                        <div class="input-group">
                            <label for="fecha_nac">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nac" id="fecha_nac" placeholder=" " required>
                            <p class="error-message" id="fecha_nac-error"></p>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Registrar</button>
                    <div class="divider">O con tu correo</div>
                    <button type="button" class="btn-google">
                        <img src="../php/imagenes/google.ico" alt="Logo de Google">
                        Google
                    </button>
                </form>
                <p id="mensaje"></p>
                <div class="login-link">
                    <span>¿Ya tienes una cuenta?</span>
                    <a href="login.php">Iniciar Sesión</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/localization/messages_es.min.js"></script>

    <script>
        // *** SE MANTIENE TU LÓGICA EXISTENTE Y SE ADAPTA PARA USAR JQUERY VALIDATION PRIMERO ***

        // Agregar método de validación para campos de solo letras y espacios (Nombre/Apellido)
        $.validator.addMethod("letras_espacios", function(value, element) {
            return this.optional(element) || /^[a-zA-Z\s]+$/.test(value);
        }, "Solo se permiten letras y espacios.");
        
        // Agregar método de validación para celular con el formato ^09\d{8}$
        $.validator.addMethod("celular_valido", function(value, element) {
            return this.optional(element) || /^09\d{8}$/.test(value);
        }, "Debe ser 10 dígitos, iniciando con 09.");

        // Agregar método de validación para correo (solo dominios específicos)
        $.validator.addMethod("correo_dominios", function(value, element) {
            const emailRegex = /^[a-zA-Z0-9._%+-]+@(gmail\.com|googlemail\.com|yahoo\.com|hotmail\.com|outlook\.com)$/;
            return this.optional(element) || emailRegex.test(value);
        }, "Solo @gmail, @hotmail/outlook o @yahoo.");

        // Aplicar jQuery Validation al formulario
        $("#crearCuentaForm").validate({
            rules: {
                "nombre": {
                    required: true,
                    minlength: 3,
                    maxlength: 50,
                    letras_espacios: true // Usa el método personalizado
                },
                "apellido": {
                    required: true,
                    minlength: 3,
                    maxlength: 50,
                    letras_espacios: true // Usa el método personalizado
                },
                "correo": {
                    required: true,
                    email: true,
                    correo_dominios: true // Usa el método personalizado
                },
                "contrasena": {
                    required: true,
                    minlength: 6 // Mínimo 6 caracteres
                },
                "confirmarContrasena": {
                    required: true,
                    equalTo: "#contrasena" // Debe ser igual al campo con ID 'contrasena'
                },
                "celular": {
                    required: true,
                    digits: true,
                    celular_valido: true // Usa el método personalizado
                },
                "fecha_nac": {
                    required: true
                }
            },
            messages: {
                "nombre": {
                    required: "Ingresa tu nombre.",
                    minlength: "El nombre debe tener al menos 3 caracteres.",
                    maxlength: "El nombre no puede exceder los 50 caracteres."
                },
                "apellido": {
                    required: "Ingresa tu apellido.",
                    minlength: "El apellido debe tener al menos 3 caracteres.",
                    maxlength: "El apellido no puede exceder los 50 caracteres."
                },
                "correo": {
                    required: "Correo obligatorio.",
                    email: "Ingresa un formato de correo válido."
                },
                "contrasena": {
                    required: "Contraseña obligatoria.",
                    minlength: "Mínimo 6 caracteres."
                },
                "confirmarContrasena": {
                    required: "Confirma tu contraseña.",
                    equalTo: "Las contraseñas no coinciden."
                },
                "celular": {
                    required: "Celular obligatorio.",
                    digits: "Solo se permiten números."
                },
                "fecha_nac": {
                    required: "Fecha obligatoria."
                }
            },
            // Función para resaltar el input con error (borde rojo)
            highlight: function(element, errorClass, validClass) {
                $(element).addClass(errorClass).removeClass(validClass);
                $(element).closest('.input-group').addClass('invalid').removeClass('valid'); // Mantiene el estilo base que tienes
            },
            // Función para quitar el resaltado cuando es válido
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass(errorClass).addClass(validClass);
                $(element).closest('.input-group').removeClass('invalid').addClass('valid');
            },
            // Coloca el mensaje de error dentro de su respectivo input-group
            errorPlacement: function(error, element) {
                error.insertAfter(element);
            },
            // Indica que se ejecute la lógica de submit del formulario solo si pasa la validación de jQuery
            submitHandler: function(form) {
                // Aquí se llama a la función de submit original
                handleFormSubmit();
            }
        });

        // Se mantienen las referencias a tus elementos DOM
        const form = document.getElementById('crearCuentaForm');
        const mensaje = document.getElementById('mensaje');

        const nombreInput = document.getElementById('nombre');
        const apellidoInput = document.getElementById('apellido');
        const correoInput = document.getElementById('correo');
        const contrasenaInput = document.getElementById('contrasena');
        const confirmarContrasenaInput = document.getElementById('confirmarContrasena');
        const celularInput = document.getElementById('celular');
        const fechaNacInput = document.getElementById('fecha_nac');

        const nombreError = document.getElementById('nombre-error');
        const apellidoError = document.getElementById('apellido-error');
        const correoError = document.getElementById('correo-error');
        const contrasenaError = document.getElementById('contrasena-error');
        const confirmarContrasenaError = document.getElementById('confirmarContrasena-error');
        const celularError = document.getElementById('celular-error');
        const fechaNacError = document.getElementById('fecha_nac-error');
        
        // Las funciones de validación nativa ahora son redundantes pero se mantienen
        // por si tuvieran alguna otra dependencia no obvia, aunque jQuery Validation
        // ya toma el control de la mayoría de las reglas.

        function validateNombre(value) {
            // JQuery Validation maneja la obligatoriedad, letras y espacios.
            // La lógica aquí puede eliminarse si solo se confía en JQ.
            return '';
        }
        function validateApellido(value) {
            return '';
        }
        function validateCorreo(value) {
            return '';
        }
        function validateContrasena(value) {
            return '';
        }
        function validateConfirmarContrasena(value) {
            return '';
        }
        function validateCelular(value) {
            return '';
        }
        function validateFechaNacimiento(value) {
            return '';
        }

        // Se mantiene la función validateField pero se deja vacía o se retira
        // ya que jQuery Validation lo maneja.

        function validateField(input, errorElement, validator) {
            // Si el formulario usa JQuery Validation, esta función ya no es necesaria
            // para el submitHandler, pero se deja si se usa en otros event listeners.
            return true; 
        }

        // Se quita el event listener 'input' para evitar conflictos con JQuery Validation.
        // JQuery Validation maneja la validación "on focus out" y "on keyup" por defecto.
        /*
        nombreInput.addEventListener('input', () => validateField(nombreInput, nombreError, validateNombre));
        apellidoInput.addEventListener('input', () => validateField(apellidoInput, apellidoError, validateApellido));
        correoInput.addEventListener('input', () => validateField(correoInput, correoError, validateCorreo));
        contrasenaInput.addEventListener('input', () => validateField(contrasenaInput, contrasenaError, validateContrasena));
        confirmarContrasenaInput.addEventListener('input', () => validateField(confirmarContrasenaInput, confirmarContrasenaError, validateConfirmarContrasena));
        celularInput.addEventListener('input', () => validateField(celularInput, celularError, validateCelular));
        fechaNacInput.addEventListener('input', () => validateField(fechaNacInput, fechaNacError, validateFechaNacimiento));
        */


        // Se renombra tu función original de submit para llamarla desde submitHandler de JQuery
        async function handleFormSubmit() {
            // Nota: Ya no se requiere la re-validación manual aquí,
            // ya que submitHandler solo se llama si JQuery Validation pasa.

            const formData = new FormData(form);
            const data = {
                nombre: formData.get('nombre'),
                apellido: formData.get('apellido'),
                correo: formData.get('correo'),
                contrasena: formData.get('contrasena'),
                fecha_nac: formData.get('fecha_nac'),
                celular: formData.get('celular')
            };

            try {
                const res = await fetch('../apis/usuario/crear_cuenta.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();

                if (result.success) {
                    mensaje.style.color = "lightgreen";
                    mensaje.textContent = result.success;
                    setTimeout(() => { window.location.href = 'login.php'; }, 2000);
                } else {
                    mensaje.style.color = "yellow";
                    mensaje.textContent = result.error;
                }
            } catch (err) {
                mensaje.style.color = "yellow";
                mensaje.textContent = "Error al conectar con la API.";
            }
        }
        
        // Se reemplaza tu listener de submit original con una que usa event.preventDefault()
        // solo para que el submitHandler de JQuery Validation tome el control.
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            // JQuery Validation manejará el submit
        });

    </script>
</body>

</html>