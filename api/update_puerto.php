<?php
/**
 * API de actualización del estado de un puerto óptico.
 *
 * Recibe por POST un cuerpo JSON con id_puerto, nuevo estado, descripción y
 * token CSRF. Delega las reglas de negocio (rol, puerto bloqueado, puerto en
 * servicio) en la clase Puerto y responde en JSON con el resultado.
 */
session_start();
require_once '../config/database.php';
require_once '../classes/Csrf.php';

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
    require_once '../classes/Puerto.php';
    $puertoManager = new Puerto();

    // La transacción envuelve al método porque updateEstado() ejecuta un
    // SELECT ... FOR UPDATE: así la fila del puerto queda bloqueada durante la
    // validación y se evita que dos técnicos empalmen el mismo hilo a la vez.
    $pdo = Database::getInstance()->getConnection();
    $pdo->beginTransaction();

    $puertoManager->updateEstado($id_puerto, $nuevo_estado, $descripcion, $id_usuario, $rol);

    $pdo->commit();
    echo json_encode(['success' => true, 'mensaje' => 'Puerto actualizado correctamente']);

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    $code = $e->getCode() ?: 500;
    http_response_code(is_numeric($code) && $code >= 400 ? $code : 500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
