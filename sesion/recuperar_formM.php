<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Recuperar Cuenta - Iron Producciones</title>

  <!-- Fuente igual que el login (Poppins). -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root { 
      scrollbar-gutter: stable both-edges; 
      --app-font: 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
    }
    html { overflow-y: scroll; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { height: 100%; }
    body { 
      display: flex; 
      min-height: 100vh; 
      background-color: #0f172a; 
      color: #fff; 
      font-family: var(--app-font);
      font-weight: 400;
    }

    .left {
      flex: 1;
      background-color: #1e293b;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .left img { width: 280px; max-width: 80%; height: auto; }

    .right {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 24px;
    }

    .contenedor {
      width: 360px;
      background: #1e293b;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 0 25px rgba(0,0,0,0.5);
      text-align: center;
      font-family: var(--app-font);
    }
    .contenedor h2 { 
      margin-bottom: 20px; 
      font-size: 22px; 
      font-weight: 600;
      color: #fff; 
    }

    input {
      width: 100%;
      padding: 12px;
      margin: 8px 0;
      border-radius: 8px;
      border: none;
      background: #334155;
      color: #fff;
      font-size: 14px;
      outline: none;
      font-family: var(--app-font);
      font-weight: 500;
    }
    input::placeholder { color: #94a3b8; }

    button {
      width: 100%;
      padding: 12px;
      margin-top: 10px;
      border: none;
      border-radius: 8px;
      background-color: #2563eb;
      color: #fff;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: background .3s ease;
      font-family: var(--app-font);
    }
    button:hover { background-color: #1d4ed8; }

    .btn-cancel {
      background: transparent;
      border: 1px solid #475569;
      color: #e2e8f0;
    }
    .btn-cancel:hover {
      background: #334155;
      border-color: #64748b;
    }

    #mensaje { color: #facc15; margin-top: 15px; min-height: 20px; font-size: 14px; }
    .swal2-container { z-index: 9999 !important; }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    const API_URL = "../apis/sesion/recuperar.php";

    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2200,
      timerProgressBar: true
    });

    function setMsg(texto) {
      document.getElementById("mensaje").innerText = texto || "";
    }

    async function verificarCorreo() {
      const correo = document.getElementById("correo").value.trim();
      setMsg("");

      if (!correo) {
        Toast.fire({ icon: 'warning', title: 'Ingresa tu correo' });
        setMsg("Ingresa tu correo.");
        return;
      }

      try {
        const res = await fetch(API_URL, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ correo })
        });
        const data = await res.json();

        if (data.success) {
          setMsg(data.success);
          Toast.fire({ icon: 'success', title: 'Contraseña enviada al correo' });
          await Swal.fire({
            icon: 'success',
            title: '¡Nueva contraseña enviada!',
            text: 'Revisa tu bandeja de entrada.',
            backdrop: false
          });
          window.location.href = "../sesion/login.php";
        } else {
          setMsg(data.error || "No se pudo procesar la solicitud.");
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.error || 'No se pudo enviar la nueva contraseña.',
            backdrop: false
          });
        }
      } catch (e) {
        setMsg("Error de conexión, intenta nuevamente.");
        Swal.fire({
          icon: 'error',
          title: 'Error de conexión',
          text: 'Intenta nuevamente.',
          backdrop: false
        });
      }
    }

    function irAlLogin() {
      window.location.href = "../sesion/login.php";
    }
  </script>
</head>
<body>
  <div class="left">
    <img src="../php/imagenes/logoempresa.png" alt="Iron Producciones">
  </div>

  <div class="right">
    <div class="contenedor">
      <h2>Recuperar Cuenta</h2>

      <input type="email" id="correo" placeholder="Correo Electrónico" required>
      <button onclick="verificarCorreo()">Verificar</button>
      <button type="button" class="btn-cancel" onclick="irAlLogin()">Cancelar y volver al login</button>

      <p id="mensaje"></p>
    </div>
  </div>
</body>
</html>
