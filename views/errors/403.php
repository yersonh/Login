<?php
session_start();
$nombreCompleto = isset($_SESSION['nombres']) ? 
                  $_SESSION['nombres'] . ' ' . ($_SESSION['apellidos'] ?? '') : 
                  'Usuario';
$rolActual = $_SESSION['tipo_usuario'] ?? 'No definido';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - SGEA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        
        .error-container {
            text-align: center;
            background: white;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 600px;
        }
        
        .error-icon {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #004a8d;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }
        
        .btn {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 30px;
            background: #004a8d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .btn:hover {
            background: #003366;
        }
        
        .btn-logout {
            background: #6c757d;
        }
        
        .btn-logout:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-ban"></i>
        </div>
        <h1>Acceso Denegado</h1>
        
        <div class="info-box">
            <p><strong>Usuario:</strong> <?php echo htmlspecialchars($nombreCompleto); ?></p>
            <p><strong>Rol actual:</strong> <?php echo htmlspecialchars($rolActual); ?></p>
            <p><strong>Acción solicitada:</strong> Acceso al panel de administración</p>
        </div>
        
        <p>Esta área está restringida únicamente a usuarios con rol de <strong>Administrador</strong>.</p>
        <p>Si necesitas acceso a esta funcionalidad, contacta al administrador del sistema.</p>
        
        <a href="javascript:history.back()" class="btn">
            <i class="fas fa-arrow-left"></i> Volver Atrás
        </a>
        
        <?php if (isset($_SESSION['tipo_usuario'])): ?>
            <a href="<?php echo $_SESSION['tipo_usuario'] === 'asistente' ? 'menuAsistente.php' : 'menu.php'; ?>" 
               class="btn">
                <i class="fas fa-home"></i> Ir al Menú Principal
            </a>
        <?php endif; ?>
        
        <a href="../../logout.php" class="btn btn-logout">
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </a>
    </div>
</body>
</html>