<?php
/**
 * Estadísticas y Reportes de Ocupación e Infraestructura Óptica — Telefónica NOC
 *
 * Sistema de selección de sede mediante buscador interactivo en tiempo real
 * (sin selector desplegable nativo), métricas en formato ejecutivo
 * y visualización de ocupación de fibra.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}
require_once dirname(__DIR__, 2) . '/config/database.php';

$statsService = new \App\Services\StatisticsService($pdo);
$sites = $statsService->getSiteList();
$selected_site = $_GET['id_site'] ?? ($sites[0]['id_site'] ?? null);

$currentSite = null;
foreach ($sites as $s) {
    if ((int)$s['id_site'] === (int)$selected_site) {
        $currentSite = $s;
        break;
    }
}
if (!$currentSite && !empty($sites)) {
    $currentSite = $sites[0];
    $selected_site = $currentSite['id_site'];
}

$odfsForSite = $selected_site ? $statsService->getOdfsForSite((int)$selected_site) : [];
$selected_odf = !empty($_GET['id_odf']) ? (int)$_GET['id_odf'] : null;

$stats = $statsService->calculateStats((int)$selected_site, $selected_odf);

require_once dirname(__DIR__, 2) . '/views/layouts/header.php';
?>

<div class="container-fluid py-3 px-3 px-md-4 min-vh-100">
    <!-- BARRA SUPERIOR: Título, Buscador de Sedes en Tiempo Real y Acciones -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-3 p-md-4 rounded-4 shadow-sm border flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-graph-up-arrow text-primary fs-4"></i>
                        <h2 class="fw-bold text-dark mb-0 fs-4">Estadísticas & Reportes</h2>
                        <span class="badge" style="background: rgba(1, 157, 244, 0.12); color: #019DF4; font-size: 0.72rem; font-weight: 600;">TELEFÓNICA NOC</span>
                    </div>
                    <p class="text-muted mb-0 mt-1 small">Capacidad instalada, balance de hilos y ocupación óptica en vivo.</p>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-3 w-100 w-lg-auto">
                    <!-- BUSCADOR INTERACTIVO DE SEDE (NO SELECT / NO DESPLEGABLE TRADICIONAL) -->
                    <div class="search-site-wrapper position-relative flex-grow-1 flex-lg-grow-0" style="min-width: 320px; max-width: 440px;">
                        <div class="input-group input-group-sm shadow-sm rounded">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search text-primary"></i></span>
                            <input type="text" id="siteSearchInput" class="form-control border-start-0 ps-0 shadow-none" placeholder="Buscar sede (ej. Zapallal, Ventanilla)..." autocomplete="off" value="<?= htmlspecialchars($currentSite['nombre'] ?? '') ?>">
                            <?php if ($selected_site): ?>
                            <button class="btn btn-outline-secondary border-start-0 bg-white" type="button" id="clearSiteSearch" title="Limpiar y buscar otra sede">
                                <i class="bi bi-x-lg"></i>
                            </button>
                            <?php endif; ?>
                        </div>

                        <!-- Dropdown de resultados en vivo (Command Palette Style) -->
                        <div id="siteSearchResults" class="site-search-dropdown shadow-lg d-none custom-scrollbar">
                            <div class="p-2 border-bottom bg-light small fw-bold text-muted d-flex justify-content-between align-items-center">
                                <span>SEDES REGISTRADAS (<span id="matchCount"><?= count($sites) ?></span>)</span>
                                <span style="font-size: 0.65rem;" class="text-primary">Escribe para filtrar</span>
                            </div>
                            <div id="siteSearchList" class="list-group list-group-flush">
                                <?php foreach ($sites as $s): ?>
                                <a href="estadisticas.php?id_site=<?= $s['id_site'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 site-option <?= (int)$s['id_site'] === (int)$selected_site ? 'active-site' : '' ?>" data-name="<?= strtolower(htmlspecialchars($s['nombre'])) ?>" data-dept="<?= strtolower(htmlspecialchars($s['departamento'] ?? '')) ?>" data-id="<?= $s['id_site'] ?>">
                                    <div class="d-flex align-items-center min-w-0">
                                        <i class="bi bi-geo-alt-fill me-2 <?= (int)$s['id_site'] === (int)$selected_site ? 'text-white' : 'text-info' ?>"></i>
                                        <div class="text-truncate">
                                            <strong class="d-block text-truncate" style="font-size: 0.85rem;"><?= htmlspecialchars($s['nombre']) ?></strong>
                                            <small class="<?= (int)$s['id_site'] === (int)$selected_site ? 'text-white-50' : 'text-muted' ?>" style="font-size: 0.75rem;"><?= htmlspecialchars($s['departamento'] ?: 'Principal') ?></small>
                                        </div>
                                    </div>
                                    <?php if ((int)$s['id_site'] === (int)$selected_site): ?>
                                    <span class="badge bg-white text-primary rounded-pill" style="font-size: 0.65rem;"><i class="bi bi-check2"></i> Activa</span>
                                    <?php endif; ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Botón Exportar CSV -->
                    <a href="api/exportar_estadisticas.php?id_site=<?= urlencode((string)$selected_site) ?>&id_odf=<?= urlencode((string)$selected_odf) ?>" class="btn btn-outline-success btn-sm d-flex align-items-center gap-2 shadow-sm px-3 py-2 fw-semibold rounded" title="Descargar reporte en formato CSV (compatible con Excel)">
                        <i class="bi bi-file-earmark-spreadsheet-fill text-success"></i><span>Exportar CSV</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($selected_site): ?>

    <!-- SELECTOR DE ODF COMPACTO (Filtro por Bastidor) -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 bg-white p-2 px-3 rounded-3 border shadow-sm">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="text-secondary small fw-bold text-uppercase me-1"><i class="bi bi-funnel me-1 text-primary"></i>Filtrar ODF:</span>
            <a href="estadisticas.php?id_site=<?= $selected_site ?>" class="btn btn-sm <?= empty($selected_odf) ? 'btn-primary shadow-sm' : 'btn-outline-secondary' ?>">
                Todos los ODFs
            </a>
            <?php foreach ($odfsForSite as $idx => $o): 
                $localIndex = $o['local_index'] ?? ($idx + 1);
            ?>
            <a href="estadisticas.php?id_site=<?= $selected_site ?>&id_odf=<?= $o['id_odf'] ?>" class="btn btn-sm <?= (int)$selected_odf === (int)$o['id_odf'] ? 'btn-primary shadow-sm' : 'btn-outline-secondary' ?>">
                ODF <?= $localIndex ?>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="small text-muted">
            Sede: <strong class="text-dark"><?= htmlspecialchars($currentSite['nombre'] ?? '') ?></strong>
            <?php if ($selected_odf): 
                $activeOdfObj = null;
                foreach ($odfsForSite as $odfItem) {
                    if ((int)$odfItem['id_odf'] === (int)$selected_odf) {
                        $activeOdfObj = $odfItem;
                        break;
                    }
                }
                if ($activeOdfObj): ?>
                <span class="ms-2">| Filtro activo: <strong class="text-primary">ODF <?= $activeOdfObj['local_index'] ?></strong></span>
            <?php endif; endif; ?>
        </div>
    </div>
    
    <!-- METRIC CARDS (4 KPIs Principales) -->
    <div class="row g-2 g-md-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-label">Cables Troncales</div>
                    <div class="icon-badge" style="background: rgba(1, 157, 244, 0.12); color: #019DF4;"><i class="bi bi-bezier2"></i></div>
                </div>
                <div class="stat-value"><?= $stats['total_cables'] ?></div>
                <div class="small text-muted mt-1" style="font-size: 0.75rem;">Líneas de alimentación</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-label">Capacidad de Hilos</div>
                    <div class="icon-badge" style="background: rgba(100, 116, 139, 0.12); color: #475569;"><i class="bi bi-layers"></i></div>
                </div>
                <div class="stat-value"><?= $stats['capacidad_hilos'] ?></div>
                <div class="small text-muted mt-1" style="font-size: 0.75rem;">Fibras físicas instaladas</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-label">Bastidores ODF</div>
                    <div class="icon-badge" style="background: rgba(14, 165, 233, 0.12); color: #0284c7;"><i class="bi bi-motherboard"></i></div>
                </div>
                <div class="stat-value"><?= $stats['total_odfs'] ?></div>
                <div class="small text-muted mt-1" style="font-size: 0.75rem;">Chasis activos en sede</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-label">Puertos Totales</div>
                    <div class="icon-badge" style="background: rgba(16, 185, 129, 0.12); color: #10b981;"><i class="bi bi-usb-plug"></i></div>
                </div>
                <div class="stat-value"><?= $stats['puertos_totales'] ?></div>
                <div class="small text-muted mt-1" style="font-size: 0.75rem;">Acopladores disponibles</div>
            </div>
        </div>
    </div>
    
    <!-- DESGLOSE DE OCUPACIÓN Y TASA EN SERVICIO -->
    <div class="row g-3 g-md-4">
        <!-- Panel Distribución de Puertos -->
        <div class="col-lg-8">
            <div class="card shadow-sm border rounded-4 p-4 p-md-5 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold mb-1">Distribución Operativa de Puertos</h5>
                        <p class="text-muted small mb-0">Desglose de estados de conectividad según bitácora de red.</p>
                    </div>
                    <span class="badge" style="background: rgba(1, 157, 244, 0.1); color: #019DF4; font-size: 0.7rem; font-weight: 600;">MÉTRICA EN VIVO</span>
                </div>
                
                <?php 
                $t = $stats['puertos_totales'] > 0 ? $stats['puertos_totales'] : 1; 
                $pLibre = round(($stats['estado_libre'] / $t) * 100);
                $pOcupa = round(($stats['estado_ocupado'] / $t) * 100);
                $pReser = round(($stats['estado_reservado'] / $t) * 100);
                $pMalog = round(($stats['estado_malogrado'] / $t) * 100);
                ?>
                
                <!-- Barra Segmentada -->
                <div class="progress mb-4" style="height: 22px; border-radius: 6px; background-color: #e2e8f0; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.06);">
                    <div class="progress-bar" style="width: <?= $pLibre ?>%; background-color: #64748b;" title="Sin Servicio: <?= $pLibre ?>%"></div>
                    <div class="progress-bar" style="width: <?= $pOcupa ?>%; background-color: #22c55e;" title="En Servicio: <?= $pOcupa ?>%"></div>
                    <div class="progress-bar" style="width: <?= $pReser ?>%; background-color: #f59e0b;" title="Reservados: <?= $pReser ?>%"></div>
                    <div class="progress-bar" style="width: <?= $pMalog ?>%; background-color: #ef4444;" title="Malogrados: <?= $pMalog ?>%"></div>
                </div>
                
                <!-- 4 Cajas Métricas con Badges Semánticos -->
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 border" style="background: rgba(100, 116, 139, 0.05); border-color: rgba(100, 116, 139, 0.2) !important;">
                            <div class="fs-4 fw-bold text-dark mb-1"><?= $stats['estado_libre'] ?></div>
                            <span class="badge bg-secondary px-2 py-1"><i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>Sin Servicio</span>
                            <div class="small text-muted mt-1" style="font-size: 0.75rem;"><?= $pLibre ?>%</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 border" style="background: rgba(34, 197, 94, 0.05); border-color: rgba(34, 197, 94, 0.2) !important;">
                            <div class="fs-4 fw-bold text-dark mb-1"><?= $stats['estado_ocupado'] ?></div>
                            <span class="badge bg-success px-2 py-1"><i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>En Servicio</span>
                            <div class="small text-muted mt-1" style="font-size: 0.75rem;"><?= $pOcupa ?>%</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 border" style="background: rgba(245, 158, 11, 0.05); border-color: rgba(245, 158, 11, 0.2) !important;">
                            <div class="fs-4 fw-bold text-dark mb-1"><?= $stats['estado_reservado'] ?></div>
                            <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>Reservados</span>
                            <div class="small text-muted mt-1" style="font-size: 0.75rem;"><?= $pReser ?>%</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 border" style="background: rgba(239, 68, 68, 0.05); border-color: rgba(239, 68, 68, 0.2) !important;">
                            <div class="fs-4 fw-bold text-dark mb-1"><?= $stats['estado_malogrado'] ?></div>
                            <span class="badge bg-danger px-2 py-1"><i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>Malogrados</span>
                            <div class="small text-muted mt-1" style="font-size: 0.75rem;"><?= $pMalog ?>%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Panel Tasa de Ocupación -->
        <div class="col-lg-4">
            <div class="card p-4 h-100 d-flex flex-column justify-content-between text-center border shadow-sm rounded-4" style="background: linear-gradient(180deg, #003245 0%, #001f2b 100%);">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                        <span class="text-uppercase tracking-wider fw-bold text-white-50" style="font-size: 0.75rem;"><i class="bi bi-lightning-charge me-1 text-info"></i>FACTOR DE UTILIZACIÓN</span>
                        <span class="badge" style="background: rgba(1, 157, 244, 0.2); color: #019DF4; font-size: 0.65rem;">TELEFÓNICA KPI</span>
                    </div>
                    <div class="py-3">
                        <div class="display-2 fw-bold text-white mb-2" style="letter-spacing: -0.04em;">
                            <?= $pOcupa ?><span class="fs-4 text-info">%</span>
                        </div>
                        <div class="text-white-50 small text-uppercase tracking-wider">Tráfico Óptico Activo</div>
                    </div>
                </div>

                <div class="border-top border-secondary border-opacity-25 pt-3 text-start">
                    <p class="text-white-50 mb-2" style="font-size: 0.78rem; line-height: 1.4;">
                        <i class="bi bi-info-circle text-info me-1"></i> Porcentaje de puertos de fibra que se encuentran transmitiendo servicios activos en este nodo de telecomunicaciones.
                    </p>
                    <div class="d-flex justify-content-between text-white-50 small" style="font-size: 0.72rem;">
                        <span>DISPONIBILIDAD: <?= 100 - $pOcupa ?>%</span>
                        <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>ÓPTIMO</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <div class="text-center py-5">
        <i class="bi bi-bar-chart text-muted" style="font-size: 4rem;"></i>
        <h4 class="text-muted mt-3 fw-semibold">No hay sedes registradas en el sistema</h4>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('siteSearchInput');
    const searchDropdown = document.getElementById('siteSearchResults');
    const siteOptions = document.querySelectorAll('.site-option');
    const matchCount = document.getElementById('matchCount');
    const clearBtn = document.getElementById('clearSiteSearch');

    if (!searchInput || !searchDropdown) return;

    // Mostrar dropdown al enfocar
    searchInput.addEventListener('focus', function() {
        searchDropdown.classList.remove('d-none');
        this.select();
    });

    // Filtrar sedes en tiempo real
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        let visibleCount = 0;

        siteOptions.forEach(opt => {
            const name = opt.getAttribute('data-name') || '';
            const dept = opt.getAttribute('data-dept') || '';
            if (name.includes(query) || dept.includes(query)) {
                opt.style.display = 'flex';
                visibleCount++;
            } else {
                opt.style.display = 'none';
            }
        });

        if (matchCount) matchCount.innerText = visibleCount;
        searchDropdown.classList.remove('d-none');
    });

    // Cerrar dropdown si se hace clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-site-wrapper')) {
            searchDropdown.classList.add('d-none');
        }
    });

    // Limpiar búsqueda
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            searchInput.focus();
            siteOptions.forEach(opt => opt.style.display = 'flex');
            if (matchCount) matchCount.innerText = siteOptions.length;
            searchDropdown.classList.remove('d-none');
        });
    }

    // Tecla Escape para cerrar
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchDropdown.classList.add('d-none');
        } else if (e.key === 'Enter') {
            // Seleccionar el primer resultado visible si presiona Enter
            e.preventDefault();
            const firstVisible = Array.from(siteOptions).find(opt => opt.style.display !== 'none');
            if (firstVisible) {
                window.location.href = firstVisible.getAttribute('href');
            }
        }
    });
});
</script>

<?php require_once dirname(__DIR__, 2) . '/views/layouts/footer.php'; ?>
