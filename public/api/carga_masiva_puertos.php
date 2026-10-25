<?php
/**
 * API de carga masiva de puertos de un ODF desde un archivo Excel (.xlsx) o CSV.
 *
 * Delega la lectura (patrón Strategy), validaciones y persistencia atómica
 * en el servicio de dominio PuertoBatchImportService.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 2) . '/config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Protección CSRF (el token viaja en el formulario multipart)
Csrf::validateOrDie($_POST['csrf_token'] ?? '');

$id_usuario = (int)$_SESSION['usuario']['id_usuario'];
$rol = (string)$_SESSION['usuario']['rol'];

$id_odf = (int)($_POST['id_odf'] ?? 0);
if (!$id_odf) {
    http_response_code(400);
    echo json_encode(['error' => 'ODF no especificado.']);
    exit;
}

if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibió el archivo o falló la subida.']);
    exit;
}

$archivo = $_FILES['archivo'];
if ($archivo['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'El archivo supera el límite de 5 MB.']);
    exit;
}

$ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['xlsx', 'csv'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato no permitido. Use un archivo .xlsx o .csv']);
    exit;
}

try {
    $puertoDao = new \App\Daos\MySqlPuertoDao($pdo);
    $odfDao = new \App\Daos\MySqlOdfDao($pdo);
    $cableDao = new \App\Daos\MySqlCableDao($pdo);
    $importService = new \App\Services\PuertoBatchImportService($puertoDao, $odfDao, $cableDao, $pdo);

    $policy = \App\Policies\PolicyFactory::forRole($rol);

    // Conservar la extensión original en el archivo temporal para que ReaderFactory la reconozca
    $tmpWithExt = sys_get_temp_dir() . '/' . uniqid('import_', true) . '.' . $ext;
    if (!copy($archivo['tmp_name'], $tmpWithExt)) {
        throw new Exception("No se pudo preparar el archivo temporal para procesamiento.");
    }

    try {
        $result = $importService->import($tmpWithExt, $id_odf, $id_usuario, $policy);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } finally {
        @unlink($tmpWithExt);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
