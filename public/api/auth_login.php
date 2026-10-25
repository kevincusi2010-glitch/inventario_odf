<?php
/**
 * API de autenticación: valida las credenciales del formulario de login.
 *
 * Recibe correo y contraseña por POST (con token CSRF obligatorio). Si el
 * usuario existe, está activo y la contraseña coincide, registra sus datos en
 * la sesión (regenerando el ID para evitar fijación de sesión) y redirige al
 * dashboard; en cualquier otro caso vuelve al login con un mensaje de error.
 */
require_once dirname(__DIR__, 2) . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::validateOrDie($_POST['csrf_token'] ?? '');

    // 1. Defensa contra ataques de fuerza bruta (Rate Limiting en servidor: máx 5 intentos en 10 min)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $rateFile = sys_get_temp_dir() . '/odf_rate_' . md5($ip) . '.json';
    $rateData = ['attempts' => 0, 'first_attempt' => time()];
    if (is_file($rateFile)) {
        $decoded = json_decode(@file_get_contents($rateFile), true);
        if (is_array($decoded)) {
            $rateData = $decoded;
        }
    }

    if ($rateData['attempts'] >= 5) {
        $elapsed = time() - $rateData['first_attempt'];
        if ($elapsed < 600) {
            $remaining = ceil((600 - $elapsed) / 60);
            header("Location: ../index.php?error=blocked&wait=" . $remaining);
            exit;
        }
        $rateData = ['attempts' => 0, 'first_attempt' => time()];
    }

    $correo = $_POST['correo'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($correo) || empty($password)) {
        header("Location: ../index.php?error=empty");
        exit;
    }

    $usuarioObj = new Usuario();
    $user = $usuarioObj->authenticate($correo, $password);

    if ($user) {
        // Éxito: limpiar contador de intentos fallidos
        @unlink($rateFile);

        // Regenerar el ID de sesión para prevenir fijación de sesión
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id_usuario' => $user['id_usuario'],
            'nombre' => $user['nombre'],
            'correo' => $user['correo'],
            'rol' => $user['rol']
        ];
        
        $_SESSION['creados_en_sesion'] = [
            'site' => [],
            'odf' => [],
            'cable' => []
        ];
        
        header("Location: ../dashboard.php");
        exit;
    }
    
    // Intento fallido: incrementar contador en almacenamiento del servidor
    $rateData['attempts']++;
    if ($rateData['attempts'] === 1) {
        $rateData['first_attempt'] = time();
    }
    @file_put_contents($rateFile, json_encode($rateData), LOCK_EX);

    header("Location: ../index.php?error=invalid");
    exit;
}
?>
