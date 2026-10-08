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
    $puertoDao = new \App\Daos\MySqlPuertoDao($pdo);
    $puertos = $puertoDao->findByOdfIdWithDetails((int)$id_odf);
    $actividad = $puertoDao->getRecentActivity((int)$id_odf, 15);
    
    echo json_encode(['puertos' => $puertos, 'actividad' => $actividad], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener bandeja: ' . $e->getMessage()]);
}
?>
