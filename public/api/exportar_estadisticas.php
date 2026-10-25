<?php
/**
 * API de Exportación de Estadísticas a CSV (/api/exportar_estadisticas).
 *
 * Genera un archivo CSV con codificación UTF-8 BOM para descarga directa en Excel,
 * conteniendo el resumen por ODF y el detalle de cada puerto y cable.
 */

require_once dirname(__DIR__, 2) . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    die("No autorizado.");
}

$id_site = isset($_GET['id_site']) ? (int)$_GET['id_site'] : 0;
$id_odf = !empty($_GET['id_odf']) ? (int)$_GET['id_odf'] : null;

$statsService = new \App\Services\StatisticsService($pdo);
$siteDao = new \App\Daos\MySqlSiteDao($pdo);
$odfDao = new \App\Daos\MySqlOdfDao($pdo);
$puertoDao = new \App\Daos\MySqlPuertoDao($pdo);

$site = $siteDao->findById($id_site);
if (!$site) {
    http_response_code(404);
    die("Sede no encontrada.");
}

$siteName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $site->nombre);
$filename = "reporte_odf_" . $siteName . "_" . date('Y-m-d_His') . ".csv";

// Cabeceras HTTP para forzar descarga (si no han sido enviadas previamente)
if (!headers_sent()) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
}

$output = fopen('php://output', 'w');

// Escribir UTF-8 BOM para compatibilidad total con Microsoft Excel
fputs($output, "\xEF\xBB\xBF");

// 1. Resumen Ejecutivo de la Sede
fputcsv($output, ['REPORTE DE INFRAESTRUCTURA Y PUERTOS ODF']);
fputcsv($output, ['Sede / Site:', $site->nombre]);
fputcsv($output, ['Dirección:', $site->direccion ?: 'N/A']);
fputcsv($output, ['Ubicación:', trim(($site->departamento ?: '') . ' - ' . ($site->distrito ?: ''), ' -')]);
fputcsv($output, ['Tipo de Nodo:', $site->tipo]);
fputcsv($output, ['Generado por:', $_SESSION['usuario']['nombre'] . ' (' . $_SESSION['usuario']['rol'] . ')']);
fputcsv($output, ['Fecha de Emisión:', date('d/m/Y H:i:s')]);
fputcsv($output, []);

// 2. Métricas Generales
$stats = $statsService->calculateStats($id_site, $id_odf);
fputcsv($output, ['METRICAS GENERALES']);
fputcsv($output, ['Cables Instalados', 'Capacidad Hilos', 'Puertos Ocupados', 'Puertos Libres', 'Puertos Reservados', 'Puertos Malogrados', 'Total Puertos']);
fputcsv($output, [
    $stats['total_cables'],
    $stats['capacidad_hilos'],
    $stats['estado_ocupado'],
    $stats['estado_libre'],
    $stats['estado_reservado'],
    $stats['estado_malogrado'],
    $stats['puertos_totales']
]);
fputcsv($output, []);

// 3. Detalle de Puertos por ODF
fputcsv($output, ['DETALLE DE PUERTOS Y EMPALMES']);
fputcsv($output, [
    'ODF ID',
    'Origen ODF',
    'Destino ODF',
    'Gabinete / Rack',
    'Posición Puerto',
    'Estado',
    'Cable Conectado',
    'N° Hilo',
    'Bloqueado',
    'Última Modificación Por',
    'Observaciones'
]);

$siteOdfsRaw = $statsService->getOdfsForSite($id_site);
$localMap = [];
foreach ($siteOdfsRaw as $sOdf) {
    $localMap[$sOdf['id_odf']] = $sOdf['local_index'] ?? $sOdf['id_odf'];
}

$odfsList = $id_odf ? [$odfDao->findById($id_odf)] : array_map(fn($o) => $odfDao->findById($o['id_odf']), $siteOdfsRaw);

foreach ($odfsList as $odfItem) {
    if (!$odfItem) continue;
    $puertos = $puertoDao->findByOdfIdWithDetails($odfItem->idOdf);
    $odfLabel = 'ODF ' . ($localMap[$odfItem->idOdf] ?? $odfItem->idOdf);
    foreach ($puertos as $p) {
        $cableLabel = !empty($p['tipo_cable']) ? $p['tipo_cable'] : 'Sin cable';
        fputcsv($output, [
            $odfLabel,
            $odfItem->origen,
            $odfItem->destino ?: 'N/A',
            $odfItem->ubicacionRack ?: 'N/A',
            $p['posicion'],
            $p['estado'],
            $cableLabel,
            $p['numero_hilo'] ?: 'Sin hilo',
            $p['bloqueado'] ? 'SÍ' : 'NO',
            $p['modificado_por_nombre'] ?: 'Sistema',
            $p['descripcion'] ?: ''
        ]);
    }
}

fclose($output);
exit;
