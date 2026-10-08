<?php
/**
 * API de consulta de bandeja (GET).
 *
 * Devuelve en formato JSON todos los puertos de un ODF con su estado, el cable
 * e hilo empalmado y la bitácora de los últimos 15 cambios. La consume el
 * dashboard para pintar la matriz del chasis sin recargar la página.
 */
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    http_response_code(401);
    exit;
}

$id_odf = $_GET['id_odf'] ?? null;
if (!$id_odf) {
    echo json_encode(['error' => 'ID ODF requerido']);
    http_response_code(400);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT p.*, u.nombre as modificado_por_nombre, CONCAT_WS(\' → \', c.origen, c.destino) AS tipo_cable FROM Puerto p LEFT JOIN Usuario u ON p.modificado_por = u.id_usuario LEFT JOIN CableAlimentacion c ON p.id_cable_conectado = c.id_cable WHERE p.id_odf = ? ORDER BY p.posicion ASC');
    $stmt->execute([$id_odf]);
    $puertos = $stmt->fetchAll();
    
    $histStmt = $pdo->prepare('SELECT h.estado_nuevo, h.observacion, h.fecha_cambio, p.posicion, u.nombre as usuario FROM HistorialCambiosPuerto h JOIN Puerto p ON h.id_puerto = p.id_puerto JOIN Usuario u ON h.id_usuario = u.id_usuario WHERE p.id_odf = ? ORDER BY h.fecha_cambio DESC LIMIT 15');
    $histStmt->execute([$id_odf]);
    $actividad = $histStmt->fetchAll();
    
    echo json_encode(['puertos' => $puertos, 'actividad' => $actividad], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener bandeja: ' . $e->getMessage()]);
}
?>
