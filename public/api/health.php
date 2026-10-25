<?php
/**
 * Health Check API Endpoint (/api/health o /health).
 *
 * SRE & Resiliencia (gstack):
 * Monitorea el estado operativo de la base de datos MySQL,
 * permisos del directorio de logs, consumo de memoria y latencia.
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$startTime = microtime(true);
$checks = [];
$isHealthy = true;

// 1. Verificación de Conexión a Base de Datos MySQL
try {
    $pdo = \App\Core\DatabaseConnection::getSharedInstance();
    $stmt = $pdo->query("SELECT 1");
    $dbOk = $stmt && $stmt->fetchColumn() == 1;
    $checks['database'] = [
        'status' => $dbOk ? 'UP' : 'DOWN',
        'driver' => 'mysql',
    ];
    if (!$dbOk) {
        $isHealthy = false;
    }
} catch (Throwable $e) {
    error_log("Health check database failure: " . $e->getMessage());
    $checks['database'] = [
        'status' => 'DOWN',
        'error'  => 'Database connection unavailable'
    ];
    $isHealthy = false;
}

// 2. Verificación de Directorio de Logs y Escritura
$logDir = dirname(__DIR__, 2) . '/logs';
$logWritable = is_dir($logDir) && is_writable($logDir);
$checks['storage'] = [
    'logs_directory' => $logWritable ? 'WRITABLE' : 'READ_ONLY_OR_MISSING',
    'status'         => $logWritable ? 'UP' : 'DEGRADED'
];
if (!$logWritable) {
    $isHealthy = false;
}

// 3. Métricas de Rendimiento del Servidor
$responseTimeMs = round((microtime(true) - $startTime) * 1000, 2);
$memoryUsageMb = round(memory_get_usage(true) / 1024 / 1024, 2);

$responseCode = $isHealthy ? 200 : 503;
http_response_code($responseCode);

echo json_encode([
    'status'           => $isHealthy ? 'HEALTHY' : 'UNHEALTHY',
    'timestamp'        => date('Y-m-d\TH:i:sP'),
    'latency_ms'       => $responseTimeMs,
    'memory_usage_mb'  => $memoryUsageMb,
    'checks'           => $checks
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit;
