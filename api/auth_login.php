<?php
/**
 * API de autenticación: valida las credenciales del formulario de login.
 *
 * Recibe correo y contraseña por POST (con token CSRF obligatorio). Si el
 * usuario existe, está activo y la contraseña coincide, registra sus datos en
 * la sesión (regenerando el ID para evitar fijación de sesión) y redirige al
 * dashboard; en cualquier otro caso vuelve al login con un mensaje de error.
 */
session_start();
require_once '../config/database.php';
require_once '../classes/Csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::validateOrDie($_POST['csrf_token'] ?? '');

    $correo = $_POST['correo'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($correo) || empty($password)) {
        header("Location: ../index.php?error=empty");
        exit;
    }

    require_once '../classes/Usuario.php';
    $usuarioObj = new Usuario();
    $user = $usuarioObj->authenticate($correo, $password);

    if ($user) {
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
    
    header("Location: ../index.php?error=invalid");
    exit;
}
?>
