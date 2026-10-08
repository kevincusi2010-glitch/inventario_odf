<?php
/**
 * Estadísticas de ocupación y capacidad de la infraestructura óptica.
 *
 * Permite filtrar por sede y, opcionalmente, por ODF, y calcula el total de
 * cables, hilos disponibles y puertos por estado para las vistas agregada y
 * detallada.
 */
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}
require_once 'config/database.php';

$statsService = new \App\Services\StatisticsService($pdo);
$sites = $statsService->getSiteList();
$selected_site = $_GET['id_site'] ?? ($sites[0]['id_site'] ?? null);

$odfsForSite = $selected_site ? $statsService->getOdfsForSite((int)$selected_site) : [];
$selected_odf = $_GET['id_odf'] ?? null;

$stats = $statsService->calculateStats((int)$selected_site, $selected_odf ? (int)$selected_odf : null);

require_once 'views/header.php';
?>

<div class="container-fluid py-4 bg-light min-vh-100">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-3 p-md-4 rounded-4 shadow-sm border-0 flex-wrap gap-3" style="background: linear-gradient(to right, #ffffff, #f8fafc);">
                <div>
                    <h2 class="fw-bold text-dark mb-0 fs-3 fs-md-2"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Estadísticas y Reportes</h2>
                    <p class="text-muted mb-0 mt-1 small">Métricas de ocupación y capacidad instalada por sede.</p>
                </div>
                <div class="w-100 w-lg-auto">
                    <form method="GET" class="d-flex flex-column flex-sm-row gap-2 shadow-sm rounded p-1 bg-white border">
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white border-0"><i class="bi bi-geo-alt-fill"></i></span>
                            <select name="id_site" class="form-select border-0 bg-white" onchange="this.form.id_odf.value=''; this.form.submit()">
                                <?php foreach($sites as $s): ?>
                                    <option value="<?= $s['id_site'] ?>" <?= $s['id_site']==$selected_site?'selected':'' ?>><?= htmlspecialchars($s['nombre']) ?> (<?= htmlspecialchars($s['departamento']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <select name="id_odf" class="form-select border-0 bg-light" onchange="this.form.submit()">
                            <option value="">Todos los ODFs</option>
                            <?php foreach($odfsForSite as $o): ?>
                                <option value="<?= $o['id_odf'] ?>" <?= $o['id_odf']==$selected_odf?'selected':'' ?>>ODF: <?= htmlspecialchars($o['origen'] . ' ' . $o['destino']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($selected_site): ?>
    
    <!-- KPIs (2x2 en móviles, 4 en desktop) -->
    <div class="row g-2 g-md-4 mb-4">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 rounded-4 bg-white h-100">
                <div class="card-body p-3 p-md-4 text-center">
                    <div class="display-6 display-md-4 text-primary fw-bold mb-2"><?= $stats['total_cables'] ?></div>
                    <div class="text-muted text-uppercase small fw-bold"><i class="bi bi-bezier2 me-1"></i> Cables</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 rounded-4 bg-white h-100">
                <div class="card-body p-3 p-md-4 text-center">
                    <div class="display-6 display-md-4 text-dark fw-bold mb-2"><?= $stats['capacidad_hilos'] ?></div>
                    <div class="text-muted text-uppercase small fw-bold"><i class="bi bi-layers me-1"></i> Hilos</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 rounded-4 bg-white h-100">
                <div class="card-body p-3 p-md-4 text-center">
                    <div class="display-6 display-md-4 text-info fw-bold mb-2"><?= $stats['total_odfs'] ?></div>
                    <div class="text-muted text-uppercase small fw-bold"><i class="bi bi-motherboard me-1"></i> ODFs</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 rounded-4 bg-white h-100">
                <div class="card-body p-3 p-md-4 text-center">
                    <div class="display-6 display-md-4 text-success fw-bold mb-2"><?= $stats['puertos_totales'] ?></div>
                    <div class="text-muted text-uppercase small fw-bold"><i class="bi bi-usb-plug me-1"></i> Puertos</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ocupación de Puertos -->
    <div class="row g-3 g-md-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-3 p-sm-4 p-md-5">
                    <h5 class="fw-bold mb-4">Distribución de Puertos ODF</h5>
                    
                    <?php 
                    $t = $stats['puertos_totales'] > 0 ? $stats['puertos_totales'] : 1; 
                    $pLibre = round(($stats['estado_libre']/$t)*100);
                    $pOcupa = round(($stats['estado_ocupado']/$t)*100);
                    $pReser = round(($stats['estado_reservado']/$t)*100);
                    $pMalog = round(($stats['estado_malogrado']/$t)*100);
                    ?>
                    
                    <!-- Barra de progreso combinada -->
                    <div class="progress mb-4" style="height: 30px; border-radius: 15px;">
                        <div class="progress-bar bg-success" style="width: <?= $pLibre ?>%;" title="Libres: <?= $pLibre ?>%"></div>
                        <div class="progress-bar bg-primary" style="width: <?= $pOcupa ?>%;" title="Ocupados: <?= $pOcupa ?>%"></div>
                        <div class="progress-bar bg-warning" style="width: <?= $pReser ?>%;" title="Reservados: <?= $pReser ?>%"></div>
                        <div class="progress-bar bg-danger" style="width: <?= $pMalog ?>%;" title="Malogrados: <?= $pMalog ?>%"></div>
                    </div>
                    
                    <div class="row mt-4 text-center g-4">
                        <div class="col-6 col-md-3">
                            <h3 class="text-success fw-bold mb-1"><?= $stats['estado_libre'] ?></h3>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill"><i class="bi bi-check-circle me-1"></i> Libres</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <h3 class="text-primary fw-bold mb-1"><?= $stats['estado_ocupado'] ?></h3>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill"><i class="bi bi-plug-fill me-1"></i> Ocupados</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <h3 class="text-warning fw-bold mb-1"><?= $stats['estado_reservado'] ?></h3>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill"><i class="bi bi-clock-history me-1"></i> Reservados</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <h3 class="text-danger fw-bold mb-1"><?= $stats['estado_malogrado'] ?></h3>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill"><i class="bi bi-x-octagon-fill me-1"></i> Malogrados</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 h-100 bg-dark text-white" style="background: linear-gradient(145deg, #1e293b, #0f172a);">
                <div class="card-body p-4 d-flex flex-column justify-content-center text-center">
                    <i class="bi bi-lightning-charge-fill text-warning mb-3" style="font-size: 3rem;"></i>
                    <h4 class="fw-bold mb-3">Tasa de Ocupación</h4>
                    <div class="display-3 fw-bold text-white mb-2"><?= $pOcupa ?>%</div>
                    <p class="text-white-50 small px-3">Porcentaje de puertos ODF que se encuentran actualmente transmitiendo servicio de fibra en esta sede.</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <div class="text-center py-5">
        <i class="bi bi-bar-chart text-muted" style="font-size: 4rem;"></i>
        <h4 class="text-muted mt-3">No hay sedes registradas</h4>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'views/footer.php'; ?>
