<?php
/**
 * Cierre de sesión del operador.
 *
 * Se ejecuta desde el botón "Salir" de la barra de navegación. Destruye la
 * sesión en el servidor, limpia sus variables y elimina la cookie del
 * navegador antes de redirigir al formulario de acceso.
 */
session_start();

// 1) Vaciar las variables de sesión antes de destruirla
$_SESSION = [];

// 2) Destruir la sesión en el servidor
session_destroy();

// 3) Eliminar la cookie de sesión del navegador para que no pueda reutilizarse
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

header('Location: index.php');
exit;
?>
