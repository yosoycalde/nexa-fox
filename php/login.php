<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['usuario_admin'])) {
    header('Location: admin_contactos.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        $conn = getConnection();
        
        if ($conn) {
            $sql = "SELECT * FROM usuarios_admin WHERE email = ? AND activo = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $usuario = $result->fetch_assoc();
                
                // Verificar contraseña
                if (password_verify($password, $usuario['password_hash'])) {
                    // Login exitoso
                    $_SESSION['usuario_admin'] = [
                        'id' => $usuario['id'],
                        'nombre' => $usuario['nombre'],
                        'email' => $usuario['email'],
                        'rol' => $usuario['rol']
                    ];
                    
                    // Actualizar último acceso
                    $updateSql = "UPDATE usuarios_admin SET ultimo_acceso = NOW() WHERE id = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param("i", $usuario['id']);
                    $updateStmt->execute();
                    
                    header('Location: admin_contactos.php');
                    exit;
                } else {
                    $error = 'Credenciales incorrectas';
                }
            } else {
                $error = 'Credenciales incorrectas';
            }
            
            $stmt->close();
            closeConnection($conn);
        } else {
            $error = 'Error de conexión con la base de datos';
        }
    } else {
        $error = 'Por favor completa todos los campos';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel Administrativo Nexa-Fox</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #004e89 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 100%;
            animation: fadeInUp 0.6s ease;
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
        
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo h1 {
            color: #ff6b35;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .login-logo p {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .form-control {
            padding: 0.75rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
            border-color: #ff6b35;
        }
        
        .btn-login {
            background: #ff6b35;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 10px;
            width: 100%;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: #ffa94d;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: #004e89;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: #ff6b35;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <h1>🦊 Nexa-Fox</h1>
            <p>Panel de Administración</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="admin@nexafox.com" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="••••••••" required>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember">
                <label class="form-check-label" for="remember">
                    Recordarme
                </label>
            </div>
            
            <button type="submit" class="btn btn-login">Iniciar Sesión</button>
        </form>
        
        <div class="back-link">
            <a href="index.html">← Volver al sitio web</a>
        </div>
        
        <div class="text-center mt-3">
            <small class="text-muted">
                Credenciales por defecto:<br>
                Email: admin@nexafox.com<br>
                Contraseña: admin123
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>