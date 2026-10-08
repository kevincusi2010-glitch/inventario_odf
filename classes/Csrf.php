<?php
/**
 * Utilidad para protección contra ataques CSRF (Cross-Site Request Forgery).
 * Genera un token único por sesión y valida que cada petición POST lo incluya.
 *
 * Uso en formularios:  <?= Csrf::field() ?>
 * Uso en APIs POST:    Csrf::validateOrDie();
 * Uso en APIs JSON:    Csrf::validateOrDie($data['csrf_token'] ?? null);
 */
class Csrf {

    private static function ensureToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Devuelve el token de la sesión (lo genera si aún no existe). */
    public static function token() {
        return self::ensureToken();
    }

    /** Devuelve el HTML de un campo oculto listo para incluir dentro de un <form>. */
    public static function field() {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token()) . '">';
    }

    /** Valida el token recibido. Detiene la ejecución con HTTP 403 si es inválido. */
    public static function validateOrDie($token = null) {
        $token = $token ?? ($_POST['csrf_token'] ?? '');
        if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            die("Petición rechazada: token CSRF inválido o ausente.");
        }
    }
}
?>
