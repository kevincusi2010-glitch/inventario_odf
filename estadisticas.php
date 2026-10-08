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

$sites = $pdo->query("SELECT id_site, nombre, departamento FROM Site WHERE activo = 1 ORDER BY nombre")->fetchAll();
$selected_site = $_GET['id_site'] ?? ($sites[0]['id_site'] ?? null);

$odfsForSite = [];
if ($selected_site) {
    $stmt = $pdo->prepare("SELECT id_odf, origen, destino, ubicacion_rack FROM ODF WHERE id_site = ? AND activo = 1 ORDER BY id_odf");
    $stmt->execute([$selected_site]);
    $odfsForSite = $stmt->fetchAll();
}
$selected_odf = $_GET['id_odf'] ?? null;

$stats = [
    'total_cables' => 0,
    'total_odfs' => 0,
    'capacidad_hilos' => 0,
    'puertos_totales' => 0,
    'estado_libre' => 0,
    'estado_ocupado' => 0,
    'estado_reservado' => 0,
    'estado_malogrado' => 0
];

if ($selected_site) {
    if ($selected_odf) {
        // Cables conectados a ESTE ODF (vista cuando se filtra por ODF)
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT c.id_cable) as c, SUM(DISTINCT c.capacidad_hilos) as h 
            FROM CableAlimentacion c 
            JOIN Puerto p ON c.id_cable = p.id_cable_conectado 
            WHERE p.id_odf = ? AND c.activo = 1
        ");
        $stmt->execute([$selected_odf]);
        $res = $stmt->fetch();
        $stats['total_cables'] = (int)$res['c'];
        
        // Si dos cables distintos tuvieran la misma capacidad_hilos, SUM(DISTINCT)
        // la contaría una sola vez y subestimaría el total; por eso la suma se
        // calcula sobre una subconsulta que deja un único registro por cable.
        $stmt2 = $pdo->prepare("
            SELECT SUM(capacidad_hilos) as h FROM (
                SELECT DISTINCT c.id_cable, c.capacidad_hilos 
                FROM CableAlimentacion c 
                JOIN Puerto p ON c.id_cable = p.id_cable_conectado 
                WHERE p.id_odf = ? AND c.activo = 1
            ) as distinct_cables
        ");
        $stmt2->execute([$selected_odf]);
        $stats['capacidad_hilos'] = (int)$stmt2->fetchColumn();

        // ODFs
        $stats['total_odfs'] = 1;
    } else {
        // Cables de toda la sede (vista agregada)
        $stmt = $pdo->prepare("SELECT COUNT(*) as c, SUM(capacidad_hilos) as h FROM CableAlimentacion WHERE id_site = ? AND activo = 1");
        $stmt->execute([$selected_site]);
        $res = $stmt->fetch();
        $stats['total_cables'] = (int)$res['c'];
        $stats['capacidad_hilos'] = (int)$res['h'];
        
        // ODFs de toda la sede (vista agregada)
        $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM ODF WHERE id_site = ? AND activo = 1");
        $stmt->execute([$selected_site]);
        $stats['total_odfs'] = (int)$stmt->fetch()['c'];
    }
    
    // Puertos
    if ($selected_odf) {
        $stmt = $pdo->prepare("
            SELECT p.estado, COUNT(p.id_puerto) as total 
            FROM Puerto p 
            WHERE p.id_odf = ?
            GROUP BY p.estado
        ");
        $stmt->execute([$selected_odf]);
    } else {
        $stmt = $pdo->prepare("
            SELECT p.estado, COUNT(p.id_puerto) as total 
            FROM Puerto p 
            JOIN ODF o ON p.id_odf = o.id_odf 
            WHERE o.id_site = ? AND o.activo = 1 
            GROUP BY p.estado
        ");
        $stmt->execute([$selected_site]);
    }
    $puertos = $stmt->fetchAll();
    
    foreach ($puertos as $p) {
        $stats['puertos_totales'] += $p['total'];
        if ($p['estado'] == 'Sin Servicio') $stats['estado_libre'] += $p['total'];
        if ($p['estado'] == 'En Servicio') $stats['estado_ocupado'] += $p['total'];
        if ($p['estado'] == 'Reservado') $stats['estado_reservado'] += $p['total'];
        if ($p['estado'] == 'Malogrado') $stats['estado_malogrado'] += $p['total'];
    }
}

require_once 'views/header.php';
?>

<div class="container-fluid py-4 bg-light min-vh-100">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-4 rounded-4 shadow-sm border-0" style="background: linear-gradient(to right, #ffffff, #f8fafc);">
                <div>
                    <h2 class="fw-bold text-dark mb-0"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Estadísticas y Reportes</h2>
                    <p class="text-muted mb-0 mt-1">Métricas de ocupación y capacidad instalada por sede.</p>
                </div>
                <div>
                    <form method="GET" class="d-flex shadow-sm rounded">
                        <span class="input-group-text bg-primary text-white border-0 rounded-start"><i class="bi bi-geo-alt-fill"></i></span>
                        <select name="id_site" class="form-select border-0 bg-white" onchange="this.form.id_odf.value=''; this.form.submit()" style="min-width: 250px;">
                            <?php foreach($sites as $s): ?>
                                <option value="<?= $s['id_site'] ?>" <?= $s['id_site']==$selected_site?'selected':'' ?>><?= htmlspecialchars($s['nombre']) ?> (<?= htmlspecialchars($s['departamento']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <select name="id_odf" class="form-select border-0 bg-white ms-2" onchange="this.form.submit()" style="min-width: 200px;">
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
    
    <!-- KPIs -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 bg-white h-100">
                <div class="card-body p-4 text-center">
                    <div class="display-4 text-primary fw-bold mb-2"><?= $stats['total_cables'] ?></div>
                    <div class="text-muted text-uppercase small fw-bold"><i class="bi bi-bezier2 me-1"></i> Cables Instalados</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 bg-white h-100">
                <div class="card-body p-4 text-center">
                    <div class="display-4 text-dark fw-bold mb-2"><?= $stats['capacidad_hilos'] ?></div>
                    <div class="text-muted text-uppercase small fw-bold"><i class="bi bi-layers me-1"></i> Hilos Disponibles</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 bg-white h-100">
                <div class="card-body p-4 text-center">
                    <div class="display-4 text-info fw-bold mb-2"><?= $stats['total_odfs'] ?></div>
                    <div class="text-muted text-uppercase small fw-bold"><i class="bi bi-motherboard me-1"></i> Equipos ODF</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 bg-white h-100">
                <div class="card-body p-4 text-center">
                    <div class="display-4 text-success fw-bold mb-2"><?= $stats['puertos_totales'] ?></div>
                    <div class="text-muted text-uppercase small fw-bold"><i class="bi bi-usb-plug me-1"></i> Puertos Totales</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ocupación de Puertos -->
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-5">
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
