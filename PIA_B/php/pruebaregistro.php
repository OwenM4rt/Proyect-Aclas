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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Conectar a BD (una sola vez) 
    $conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if ($conn->connect_error) {
        $message = 'Error: no se puede conectar a la base de datos.';
        $message_type = 'error';
    } else {
        $conn->set_charset('utf8mb4');

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
                    // Login correcto: crear sesión
                    $_SESSION['usuario'] = $row['correo'];
                    $_SESSION['usuario_id'] = $row['id'];
                    $stmt->close();
                    $conn->close();
                    header("Location: ../html/index.html"); 
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

        $conn->close();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Login</title>
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

  .msg {
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 500;
  }

  .error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }
</style>
</head>
<body>

<div class="header">
<h1>¡Bienvenido!</h1>
  <p>Solo usuarios autorizados.</p>
</div>

<div class="card" id="loginBox">
  <h2>Iniciar Sesión</h2>

  <?php if ($message && $message_type === 'error'): ?>
    <div class="msg error"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  
  <form method="post" action="">
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
</div>

</body>
</html>
