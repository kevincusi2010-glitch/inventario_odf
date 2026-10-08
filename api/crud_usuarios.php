<?php
/**
 * API de gestión de usuarios y contraseñas.
 *
 * Atiende por POST las acciones change_my_password (disponible para cualquier
 * usuario con sesión), create_user, update_user, deactivate_user,
 * activate_user, delete_user y reset_password (exclusivas del Administrador).
 * Los resultados se comunican mediante $_SESSION['msg'] / $_SESSION['error'] y
 * el usuario siempre es devuelto a la vista correspondiente.
 */
session_start();
require_once '../config/database.php';
require_once '../classes/Csrf.php';

// Accesible para cualquier usuario con sesión iniciada (por ejemplo, para cambiar su propia contraseña)
if (!isset($_SESSION['usuario'])) {
    die("Acceso denegado.");
}

// Protección CSRF: todas las acciones mutantes requieren un token válido
Csrf::validateOrDie();

$action = $_POST['action'] ?? '';

require_once '../classes/Usuario.php';
$usuarioObj = new Usuario();

try {
    if ($action === 'change_my_password') {
        $usuarioObj->changeUserPassword($_SESSION['usuario']['id_usuario'], $_POST['old_password'] ?? '', $_POST['new_password'] ?? '');
        $_SESSION['msg'] = "Tu contraseña ha sido cambiada exitosamente.";
        header("Location: ../dashboard.php");
        exit;
    }

    // A partir de aquí todas las acciones exigen el rol Administrador
    if ($_SESSION['usuario']['rol'] !== 'Administrador') {
        throw new Exception("No tienes permisos de Administrador para realizar esta acción.");
    }

    if ($action === 'create_user') {
        $usuarioObj->createUsuario($_POST['nombre'] ?? '', $_POST['correo'] ?? '', $_POST['rol'] ?? '');
        $_SESSION['msg'] = "Usuario creado correctamente. La contraseña temporal es: telefonica123";
    } 
    else if ($action === 'update_user') {
        $usuarioObj->updateUsuario($_POST['id_usuario'] ?? '', $_POST['nombre'] ?? '', $_POST['correo'] ?? '', $_POST['rol'] ?? '');
        $_SESSION['msg'] = "Datos de usuario actualizados correctamente.";
    } 
    else if ($action === 'deactivate_user') {
        $id_usuario = $_POST['id_usuario'] ?? '';
        if ($id_usuario == $_SESSION['usuario']['id_usuario']) {
            throw new Exception("No puedes desactivar tu propia cuenta.");
        }
        $usuarioObj->changeStatus($id_usuario, 0);
        $_SESSION['msg'] = "La cuenta del usuario ha sido desactivada. Ya no podrá iniciar sesión.";
    }
    else if ($action === 'activate_user') {
        $usuarioObj->changeStatus($_POST['id_usuario'] ?? '', 1);
        $_SESSION['msg'] = "La cuenta del usuario ha sido reactivada.";
    }
    else if ($action === 'delete_user') {
        $id_usuario = $_POST['id_usuario'] ?? '';
        if ($id_usuario == $_SESSION['usuario']['id_usuario']) {
            throw new Exception("No puedes eliminar tu propia cuenta.");
        }
        $usuarioObj->deleteUsuario($id_usuario);
        $_SESSION['msg'] = "El usuario ha sido eliminado definitivamente de la base de datos.";
    }
    else if ($action === 'reset_password') {
        $usuarioObj->resetPassword($_POST['id_usuario'] ?? '');
        $_SESSION['msg'] = "Contraseña restablecida correctamente. La nueva contraseña es: telefonica123";
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header("Location: ../admin_usuarios.php");
exit;
?>
