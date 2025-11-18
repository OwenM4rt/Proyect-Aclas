<?php
// Requisitos: base de datos con tabla 'usuarios'
session_start();

// Configuración DB
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'DB_ACLAS';

// Mensajes para mostrar en la página al iniciar o al ocurrir un error
$message = '';
$message_type = ''; // 'success' | 'error'

// Procesar método POST - formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Conectar a BD (una sola vez) 
    $conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if ($conn->connect_error) {
        $message = 'Error: no se puede conectar a la base de datos.';
        $message_type = 'error';
    } else {
        $conn->set_charset('utf8mb4');

        if ($action === 'login') {
            // LOGIN
            $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
            $clave  = isset($_POST['clave']) ? $_POST['clave'] : '';

            if ($correo === '' || $clave === '') {
                $message = 'Complete correo y contraseña.';
                $message_type = 'error';
            } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $message = 'Correo inválido.';
                $message_type = 'error';
            } else {
                $stmt = $conn->prepare("SELECT id, correo, clave FROM usuarios WHERE correo = ?");
                $stmt->bind_param("s", $correo);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows === 1) {
                    $row = $res->fetch_assoc();
                    if (password_verify($clave, $row['clave'])) {
                        // Login correcto: crear sesión y redirigir a Google o a la página que requieras
                        $_SESSION['usuario'] = $row['correo'];
                        $_SESSION['usuario_id'] = $row['id'];
                        $stmt->close();
                        $conn->close();
                        header("Location: html/index.html"); //CAMBIAR POR SITIO A DIRECCIONAR
                        exit;
                    } else {
                        $message = 'Correo o contraseña incorrectos.';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Correo o contraseña incorrectos.';
                    $message_type = 'error';
                }
                if (isset($stmt)) $stmt->close();
            }

        } elseif ($action === 'register') {
            // REGISTRO DE NUEVO USUARIO
            $correo = isset($_POST['reg_correo']) ? trim($_POST['reg_correo']) : '';
            $clave1 = isset($_POST['reg_clave1']) ? $_POST['reg_clave1'] : '';
            $clave2 = isset($_POST['reg_clave2']) ? $_POST['reg_clave2'] : '';

            if ($correo === '' || $clave1 === '' || $clave2 === '') {
                $message = 'Complete todos los campos de registro.';
                $message_type = 'error';
            } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $message = 'Correo de registro inválido.';
                $message_type = 'error';
            } elseif ($clave1 !== $clave2) {
                $message = 'Las contraseñas no coinciden.';
                $message_type = 'error';
            } elseif (strlen($clave1) < 5) { //CAMBIAR POR EXTENSIÓN MÍNIMA
                $message = 'La contraseña debe tener al menos 5 caracteres.';
                $message_type = 'error';
            } else {
                // Verificar si ya existe el correo en la base de datos
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
                $stmt->bind_param("s", $correo);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $message = 'El correo ya está registrado.';
                    $message_type = 'error';
                    $stmt->close();
                } else {
                    if (isset($stmt)) $stmt->close();
                    // Insertar nuevo usuario con password_hash para encriptar la contraseña
                    $hash = password_hash($clave1, PASSWORD_DEFAULT);
                    $stmt2 = $conn->prepare("INSERT INTO usuarios (correo, clave) VALUES (?, ?)");
                    $stmt2->bind_param("ss", $correo, $hash);
                    if ($stmt2->execute()) {
                        $message = 'Registro exitoso. Ahora inicia sesión con tu correo.';
                        $message_type = 'success';
                    } else {
                        $message = 'Error al registrar: ' . $conn->error;
                        $message_type = 'error';
                    }
                    $stmt2->close();
                }
            }
        }

        $conn->close();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Auth - PIA (Login / Registro)</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  * {
    box-sizing: border-box;
  }

  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    margin: 0;
    padding: 20px;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }

  .header {
    text-align: center;
    margin-bottom: 30px;
    color: white;
  }

  .header h1 {
    margin: 0 0 10px 0;
    font-size: 2.5em;
    font-weight: 300;
    text-shadow: 0 2px 10px rgba(0,0,0,0.3);
  }

  .header p {
    margin: 0;
    font-size: 1.1em;
    opacity: 0.9;
    text-shadow: 0 1px 5px rgba(0,0,0,0.2);
  }

  .container {
    width: 100%;
    max-width: 900px;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    gap: 30px;
  }

  .card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 400px;
    border: 1px solid rgba(255,255,255,0.2);
  }

  h2 {
    margin: 0 0 25px 0;
    font-size: 1.8em;
    text-align: center;
    color: #333;
    font-weight: 400;
  }

  .form-group {
    margin-bottom: 20px;
  }

  label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    color: #555;
    font-weight: 500;
  }

  input {
    width: 100%;
    padding: 15px;
    border: 2px solid #e1e8f0;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: #fafbfc;
  }

  input:focus {
    outline: none;
    border-color: #667eea;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
  }

  .btn-primary {
    width: 100%;
    padding: 15px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
  }

  .btn-primary:active {
    transform: translateY(0);
  }

  .link-btn {
    display: block;
    width: 100%;
    background: transparent;
    color: #667eea;
    border: 2px solid #667eea;
    border-radius: 12px;
    padding: 12px;
    margin-top: 15px;
    text-decoration: none;
    cursor: pointer;
    text-align: center;
    font-weight: 500;
    transition: all 0.3s ease;
  }

  .link-btn:hover {
    background: #667eea;
    color: white;
    transform: translateY(-1px);
  }

  .msg {
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 500;
  }

  .success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
  }
  
  .error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }

  .form-toggle {
    text-align: center;
    margin-top: 20px;
  }

  /* Responsive */
  @media (max-width: 768px) {
    body {
      padding: 15px;
    }
    
    .container {
      flex-direction: column;
      align-items: center;
      gap: 20px;
    }

    .card {
      padding: 30px 25px;
      max-width: 100%;
    }

    .header h1 {
      font-size: 2em;
    }

    .header p {
      font-size: 1em;
    }
  }

  @media (max-width: 480px) {
    .card {
      padding: 25px 20px;
    }

    .header h1 {
      font-size: 1.8em;
    }
  }

  /* Animation for smooth transitions */
  .card {
    animation: fadeInUp 0.6s ease-out;
  }

  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

</style>

<script>
  function showRegister(show) {
    const reg = document.getElementById('registerBox');
    const login = document.getElementById('loginBox');
    if (show) {
      reg.style.display = 'block';
      login.style.display = 'none';
    } else {
      reg.style.display = 'none';
      login.style.display = 'block';
    }
  }
  
  // Mostrar registro si hubo un intento de registro con error/success
  window.addEventListener('DOMContentLoaded', () => {
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register'): ?>
      showRegister(true);
    <?php endif; ?>
  });
</script>
</head>
<body>

<div class="header">
<h1>!Bienvenido¡</h1>
  <p>Usa tu correo y contraseña.</p>
</div>

<div class="container">
  <!-- LOGIN -->
  <div class="card" id="loginBox">
    <h2>Iniciar Sesión</h2>

    <?php if ($message && $message_type === 'error' && isset($_POST['action']) && $_POST['action'] === 'login'): ?>
      <div class="msg error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <form method="post" action="">
      <input type="hidden" name="action" value="login">
      
      <div class="form-group">
        <label for="correo">Correo Electrónico</label>
        <input type="email" id="correo" name="correo" required 
               placeholder="usuario@ejemplo.com" 
               value="<?= isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : '' ?>">
      </div>

      <div class="form-group">
        <label for="clave">Contraseña</label>
        <input type="password" id="clave" name="clave" required placeholder="Contraseña">
      </div>

      <button type="submit" class="btn-primary">Iniciar Sesión</button>
    </form>
    
    <div class="form-toggle">
      <button class="link-btn" onclick="showRegister(true)">¿No tienes cuenta? Regístrate</button>
    </div>
  </div>
  

  <!-- REGISTER -->
  <div class="card" id="registerBox" style="display:none">
    <h2>Registro de Usuario</h2>

    <?php if ($message): ?>
      <div class="msg <?= $message_type === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>
    
    <form method="post" action="">
      <input type="hidden" name="action" value="register">
      
      <div class="form-group">
        <label for="reg_correo">Correo Electrónico</label>
        <input type="email" id="reg_correo" name="reg_correo" required 
               placeholder="nuevo@ejemplo.com" 
               value="<?= isset($_POST['reg_correo']) ? htmlspecialchars($_POST['reg_correo']) : '' ?>">
      </div>

      <div class="form-group">
        <label for="reg_clave1">Contraseña</label>
        <input type="password" id="reg_clave1" name="reg_clave1" required 
               placeholder="Mínimo 5 caracteres">
      </div>

      <div class="form-group">
        <label for="reg_clave2">Repetir Contraseña</label>
        <input type="password" id="reg_clave2" name="reg_clave2" required 
               placeholder="Repita la contraseña">
      </div>

      <button type="submit" class="btn-primary">Crear Cuenta</button>
    </form>
    
    <div class="form-toggle">
      <button class="link-btn" onclick="showRegister(false)">¿Ya tienes cuenta? Inicia sesión</button>
    </div>
  </div>
</div>

</body>
</html>