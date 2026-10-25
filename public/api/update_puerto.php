<?php
/**
 * API de actualización del estado de un puerto óptico.
 *
 * Recibe por POST un cuerpo JSON con id_puerto, nuevo estado, descripción y
 * token CSRF. Delega las reglas de negocio (rol, puerto bloqueado, puerto en
 * servicio) en la clase Puerto y responde en JSON con el resultado.
 */
require_once dirname(__DIR__, 2) . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    http_response_code(401);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Protección CSRF: la petición JSON debe incluir el token de la sesión
Csrf::validateOrDie($data['csrf_token'] ?? null);

$id_usuario = $_SESSION['usuario']['id_usuario'];
$rol = $_SESSION['usuario']['rol'];

$id_puerto = $data['id_puerto'] ?? null;
$nuevo_estado = $data['estado'] ?? null;
$descripcion = $data['descripcion'] ?? null;

// Whitelist de estados válidos (coincide con el ENUM de la tabla Puerto)
$ESTADOS_VALIDOS = ['Sin Servicio', 'En Servicio', 'Reservado', 'Malogrado'];
if (!$id_puerto || !$nuevo_estado || !in_array($nuevo_estado, $ESTADOS_VALIDOS, true)) {
    echo json_encode(['error' => 'Datos inválidos']);
    http_response_code(400);
    exit;
}

try {
    $puertoDao = new \App\Daos\MySqlPuertoDao($pdo);
    $policy = \App\Policies\PolicyFactory::forRole($rol);

    \App\Core\DatabaseConnection::transaction($pdo, function () use ($puertoDao, $policy, $id_puerto, $nuevo_estado, $descripcion, $id_usuario) {
        $puerto = $puertoDao->lockForUpdate((int)$id_puerto);
        if (!$puerto) {
            throw new Exception("Puerto no encontrado", 404);
        }

        if (!$policy->canModifyPort($puerto)) {
            $msg = ((int)($puerto['bloqueado'] ?? 0) === 1)
                ? "El puerto está bloqueado y solo puede ser modificado por un Administrador."
                : "Los técnicos no tienen permisos para modificar un puerto que ya se encuentra 'En Servicio'.";
            throw new Exception($msg, 403);
        }

        $bloqueado = (int)$puerto['bloqueado'];
        if ($nuevo_estado === 'Malogrado') {
            $bloqueado = 1;
        } elseif ($policy->getRoleName() === 'Administrador') {
            $bloqueado = 0;
        }

        $puertoDao->updateEstado((int)$id_puerto, $nuevo_estado, $descripcion, $id_usuario, $bloqueado);
    });

    echo json_encode(['success' => true, 'mensaje' => 'Puerto actualizado correctamente']);

} catch (Exception $e) {
    // La transacción ya fue revertida por DatabaseConnection::transaction();
    // volver a llamar rollBack() aquí lanzaría "There is no active transaction"
    // y enmascararía el error real (403/404) como un 500 genérico.
    $code = $e->getCode() ?: 500;
    http_response_code(is_numeric($code) && $code >= 400 ? $code : 500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
