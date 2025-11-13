<?php
session_start();
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'usuario' || !isset($_SESSION['id'])) {
    header("Location: ../../sesion/login.php");
    exit;
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../../sesion/login.php");
    exit;
}

include 'plantilla/header.php';
?>
<style>
    :root {
        --primary-color: #F94144;
        --secondary-color: #F3722C;
        --dark-bg: #0e172b;
        --card-bg: #2C2C2C;
        --text-color: white;
        --light-text: #B0B0B0;
    }

    /* Ajuste de main para un contenido más centrado y no tan ancho */
    main {
        flex-grow: 1;
        width: 100%;
        padding: 15px; /* Reducido */
        max-width: 700px; /* Ancho máximo reducido para el perfil */
        box-sizing: border-box;
        animation: fadeIn 1.5s ease;
        margin-top: 20px;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .profile-container {
        background: var(--card-bg);
        padding: 30px; /* Padding reducido */
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .profile-header {
        display: flex;
        align-items: center;
        gap: 15px; /* Espacio reducido */
        margin-bottom: 25px; /* Margen reducido */
        width: 100%;
    }

    .profile-avatar {
        width: 80px; /* Tamaño del avatar reducido */
        height: 80px; /* Tamaño del avatar reducido */
        border-radius: 50%;
        overflow: hidden;
        box-shadow: 0 0 10px var(--primary-color); /* Sombra ajustada */
        animation: pulse 2s infinite ease-in-out;
    }

    @keyframes pulse {
        0% { transform: scale(1); box-shadow: 0 0 10px var(--primary-color); }
        50% { transform: scale(1.03); box-shadow: 0 0 18px var(--primary-color); } /* Pulsación más sutil */
        100% { transform: scale(1); box-shadow: 0 0 10px var(--primary-color); }
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-info {
        text-align: left;
    }

    .profile-info h1 {
        color: var(--primary-color);
        margin: 0;
        font-size: 2em; /* Tamaño de título reducido */
    }

    .profile-info p {
        color: var(--light-text);
        margin: 3px 0 0; /* Margen reducido */
        font-size: 1em; /* Tamaño de texto reducido */
    }

    #message {
        margin: 15px 0; /* Margen reducido */
        padding: 12px; /* Padding reducido */
        border-radius: 5px;
        font-weight: bold;
        display: none;
        width: 100%;
        box-sizing: border-box;
        font-size: 0.9em; /* Tamaño de fuente reducido */
    }

    #message.success {
        background-color: #28a745;
        color: white;
    }

    #message.error {
        background-color: #dc3545;
        color: white;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px; /* Espacio reducido entre campos */
        width: 100%;
    }

    .form-group {
        text-align: left;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 6px; /* Margen reducido */
        font-weight: bold;
        color: var(--light-text);
        font-size: 0.9em; /* Tamaño de fuente reducido */
    }

    .form-group input {
        width: 100%;
        padding: 10px; /* Padding reducido */
        border-radius: 5px;
        border: 1px solid #555;
        background-color: #333;
        color: white;
        font-size: 0.9em; /* Tamaño de fuente reducido */
        box-sizing: border-box;
        transition: all 0.3s ease;
    }
    
    .form-group input:disabled {
        cursor: not-allowed;
        background-color: #444;
        color: #888;
    }

    .form-group input:focus:not(:disabled) {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 6px rgba(249, 65, 68, 0.5); /* Sombra ajustada */
    }

    .button-group {
        display: flex;
        justify-content: flex-end;
        gap: 15px; /* Espacio reducido entre botones */
        margin-top: 25px; /* Margen reducido */
        width: 100%;
    }

    .button-group button {
        padding: 12px 25px; /* Padding reducido */
        border-radius: 50px;
        border: none;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
        font-size: 0.9em; /* Tamaño de fuente reducido */
    }

    .btn-guardar {
        background-color: var(--primary-color);
        color: white;
    }

    .btn-guardar:hover {
        background-color: var(--secondary-color);
        transform: translateY(-2px); /* Transformación más sutil */
        box-shadow: 0 3px 8px rgba(243, 114, 44, 0.4); /* Sombra ajustada */
    }

    .btn-cancelar {
        background-color: #555;
        color: white;
    }

    .btn-cancelar:hover {
        background-color: #777;
        transform: translateY(-2px); /* Transformación más sutil */
        box-shadow: 0 3px 8px rgba(85, 85, 85, 0.4); /* Sombra ajustada */
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        main {
            padding: 10px;
        }
        .profile-container {
            padding: 20px;
        }
        .profile-header {
            flex-direction: column;
            text-align: center;
        }
        .profile-info {
            text-align: center;
        }
    }
</style>
<main>
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <img src="../imagenes/gif/cuenta.gif" alt="Avatar de usuario">
            </div>
            <div class="profile-info">
                <h1>Mi Perfil</h1>
                <p id="user-greeting"></p>
            </div>
        </div>
        <div id="message"></div>
        <form id="profile-form">
            <input type="hidden" id="user-id" name="id" value="<?php echo htmlspecialchars($_SESSION['id']); ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" required disabled>
                </div>
                <div class="form-group">
                    <label for="apellido">Apellido</label>
                    <input type="text" id="apellido" name="apellido" required disabled>
                </div>
                <div class="form-group">
                    <label for="celular">Celular</label>
                    <input type="tel" id="celular" name="celular" disabled>
                </div>
                <div class="form-group">
                    <label for="correo">Correo</label>
                    <input type="email" id="correo" name="correo" required disabled>
                </div>
                <div class="form-group">
                    <label for="fecha_nac">Fecha de Nacimiento</label>
                    <input type="date" id="fecha_nac" name="fecha_nac" required disabled>
                </div>
                <div class="form-group" id="password-group" style="display:none;">
                    <label for="contrasena">Nueva Contraseña (opcional)</label>
                    <input type="password" id="contrasena" name="contrasena">
                </div>
            </div>
            <div class="button-group">
                <button type="button" id="edit-btn" class="btn-guardar">Editar Perfil</button>
                <button type="submit" id="save-btn" class="btn-guardar" style="display:none;">Guardar Cambios</button>
                <button type="button" id="cancel-btn" class="btn-cancelar" style="display:none;">Cancelar</button>
            </div>
        </form>
    </div>
</main>
<?php
include 'plantilla/footer.php';
?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const userId = document.getElementById('user-id').value;
        const form = document.getElementById('profile-form');
        const messageDiv = document.getElementById('message');
        const editBtn = document.getElementById('edit-btn');
        const saveBtn = document.getElementById('save-btn');
        const cancelBtn = document.getElementById('cancel-btn');
        const passwordGroup = document.getElementById('password-group');
        const inputs = form.querySelectorAll('input:not([type="hidden"])');
        const userGreeting = document.getElementById('user-greeting');

        const setFormState = (enabled) => {
            inputs.forEach(input => {
                input.disabled = !enabled;
            });
            passwordGroup.style.display = enabled ? 'block' : 'none';
            editBtn.style.display = enabled ? 'none' : 'block';
            saveBtn.style.display = enabled ? 'block' : 'none';
            cancelBtn.style.display = enabled ? 'block' : 'none';
        };

        const fetchUserData = async () => {
            try {
                const response = await fetch('../../apis/usuario/consultar_usuario.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: userId })
                });

                if (!response.ok) {
                    throw new Error('Error al obtener los datos del usuario.');
                }

                const data = await response.json();
                if (data.error) {
                    showMessage(data.error, 'error');
                } else {
                    document.getElementById('nombre').value = data.usr_nom;
                    document.getElementById('apellido').value = data.usr_ape;
                    document.getElementById('celular').value = data.usr_cel;
                    document.getElementById('correo').value = data.usr_cor;
                    document.getElementById('fecha_nac').value = data.usr_fec_nac;
                    userGreeting.textContent = `Hola, ${data.usr_nom}!`;
                }
            } catch (error) {
                showMessage('Error al conectar con el servidor: ' + error.message, 'error');
            }
        };

        editBtn.addEventListener('click', () => {
            setFormState(true);
        });

        cancelBtn.addEventListener('click', () => {
            setFormState(false);
            fetchUserData();
            document.getElementById('contrasena').value = '';
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = {
                id: userId,
                nombre: document.getElementById('nombre').value,
                apellido: document.getElementById('apellido').value,
                celular: document.getElementById('celular').value,
                correo: document.getElementById('correo').value,
                fecha_nac: document.getElementById('fecha_nac').value,
                contrasena: document.getElementById('contrasena').value
            };

            try {
                const response = await fetch('../../apis/usuario/actualizar_usuario.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                if (!response.ok) {
                    throw new Error('Error al actualizar los datos.');
                }

                const result = await response.json();
                if (result.success) {
                    await fetch('../../apis/usuario/actualizar_sesion.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ nombre: formData.nombre })
                    });
                    
                    showMessage(result.success, 'success');
                    setFormState(false);
                    document.getElementById('contrasena').value = '';
                    
                    window.location.reload(); 
                    
                } else if (result.error) {
                    showMessage(result.error, 'error');
                }
            } catch (error) {
                showMessage('Error al conectar con el servidor: ' + error.message, 'error');
            }
        });

        const showMessage = (message, type) => {
            messageDiv.textContent = message;
            messageDiv.className = '';
            messageDiv.classList.add('message', type);
            messageDiv.style.display = 'block';
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        };

        fetchUserData();
    });
</script>
</body>
</html>