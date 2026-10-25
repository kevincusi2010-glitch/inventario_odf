<?php
/**
 * Dashboard operativo: monitoreo en vivo de los puertos del ODF.
 *
 * Presenta el buscador y el acordeón de sedes con sus ODFs, las tarjetas de
 * indicadores (total, en servicio, reservados y malogrados), la matriz gráfica
 * del chasis y la bitácora de cambios recientes. Carga el contenido de cada
 * bandeja por AJAX (api/get_bandeja.php) y guarda los cambios de estado por
 * POST JSON (api/update_puerto.php).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/views/layouts/header.php';

$sites = [];
$dbError = null;
try {
    $siteDao = new \App\Daos\MySqlSiteDao($pdo);
    $odfDao = new \App\Daos\MySqlOdfDao($pdo);

    foreach ($siteDao->findAllActive() as $siteEntity) {
        $siteId = $siteEntity->idSite;
        $sites[$siteId]['nombre'] = $siteEntity->nombre;
        $odfs = $odfDao->findBySiteIdWithConnectedCables($siteId);
        $sites[$siteId]['odfs'] = $odfs;

        $racks = [];
        foreach ($odfs as $odf) {
            $rackKey = !empty($odf['ubicacion_rack']) ? trim($odf['ubicacion_rack']) : 'Rack 01';
            if (!isset($racks[$rackKey])) {
                $racks[$rackKey] = [];
            }
            $racks[$rackKey][] = $odf;
        }
        $sites[$siteId]['racks'] = $racks;
    }
} catch (Exception $e) {
    $dbError = "La base de datos no está configurada o está vacía. Por favor, ejecuta la instalación inicial.";
}
?>



<?php if ($dbError): ?>
<div class="alert alert-danger mt-4">
    <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Error de Base de Datos</h4>
    <p><?= htmlspecialchars($dbError) ?></p>
    <hr>
    <p class="mb-0">Importa el esquema ejecutando el archivo <code>database/inventario_odf.sql</code> del proyecto.<br>
    <small>Si ya tenías la base de datos instalada, ejecuta <code>php database/migrate.php</code> para aplicar las migraciones pendientes.</small></p>

</div>
<?php else: ?>
<div class="container-fluid px-2 px-sm-3 px-md-4 py-3">
    <div class="row g-3 g-md-4 h-100">
        <!-- BARRA LATERAL: Árbol Jerárquico de Sedes, Racks y ODFs (Folder Tree) -->
        <div class="col-xl-3 col-lg-3 col-md-4 ps-md-0 mb-3 mb-md-0">
            <div class="premium-sidebar p-3 h-100 w-100" style="min-width: 280px;">
                <div class="d-flex align-items-center mb-3 text-white">
                    <i class="bi bi-diagram-3-fill fs-4 me-2 text-info"></i>
                    <div>
                        <h6 class="mb-0 fw-bold text-uppercase" style="letter-spacing: 0.05em;">SITE</h6>
                        <small class="text-white-50" style="font-size: 0.72rem;">Sedes · Racks · ODFs</small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent text-white-50 border-secondary"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchSite" class="form-control bg-transparent text-white border-secondary shadow-none" placeholder="Filtrar sede, rack u ODF..." style="color: white !important;">
                    </div>
                </div>

                <!-- Contenedor del Folder Tree con scroll -->
                <div class="tree-root custom-scrollbar overflow-auto pe-1" id="treeRoot" style="max-height: calc(100vh - 210px);">
                    <?php $siteIdx = 0; foreach ($sites as $id_site => $site): $siteIdx++; ?>
                    <div class="tree-node-site mb-2" data-site-name="<?= strtolower(htmlspecialchars($site['nombre'])) ?>">
                        <!-- 🏢 Sede con truncate y title -->
                        <button class="tree-site-btn btn btn-sm w-100 text-start d-flex align-items-center justify-content-between p-2 rounded text-truncate <?= $siteIdx === 1 ? '' : 'collapsed' ?>" 
                                type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#tree-site-<?= $id_site ?>" 
                                aria-expanded="<?= $siteIdx === 1 ? 'true' : 'false' ?>" 
                                title="<?= htmlspecialchars($site['nombre']) ?>">
                            <div class="d-flex align-items-center text-truncate me-2">
                                <span class="tree-icon me-2 text-warning fs-6"><i class="bi bi-building"></i></span>
                                <span class="text-truncate fw-semibold text-white site-label" style="font-size: 0.83rem;"><?= htmlspecialchars($site['nombre']) ?></span>
                            </div>
                            <div class="d-flex align-items-center flex-shrink-0">
                                <span class="badge rounded-pill bg-dark bg-opacity-75 text-white-50 border border-secondary border-opacity-25 px-2 me-1" style="font-size: 0.65rem;"><?= count($site['odfs']) ?> ODF</span>
                                <i class="bi bi-chevron-down text-white-50 tree-chevron" style="font-size: 0.7rem;"></i>
                            </div>
                        </button>
                        
                        <div id="tree-site-<?= $id_site ?>" class="collapse <?= $siteIdx === 1 ? 'show' : '' ?> ps-3 mt-1 tree-racks-wrapper">
                            <?php if (!empty($site['racks'])): ?>
                                <?php foreach ($site['racks'] as $rackName => $rackOdfs): ?>
                                <div class="tree-node-rack mb-2" data-rack-name="<?= strtolower(htmlspecialchars($rackName)) ?>">
                                    <!-- 🗄️ Rack -->
                                    <div class="tree-rack-header d-flex align-items-center text-white-50 py-1 px-2 rounded small text-truncate" title="Rack: <?= htmlspecialchars($rackName) ?>">
                                        <i class="bi bi-hdd-rack me-2 text-info flex-shrink-0"></i>
                                        <span class="text-truncate fw-semibold text-slate-300 rack-label" style="font-size: 0.78rem;"><?= htmlspecialchars($rackName) ?></span>
                                        <span class="ms-auto badge bg-white bg-opacity-10 text-white-50" style="font-size: 0.62rem;"><?= count($rackOdfs) ?></span>
                                    </div>
                                    
                                    <!-- 📟 ODFs -->
                                    <div class="tree-odfs-branch ps-3 border-start border-secondary border-opacity-25 ms-2 mt-1">
                                        <?php foreach ($rackOdfs as $odf): 
                                            $cableDesc = $odf['cables_conectados'] ? htmlspecialchars($odf['cables_conectados']) : (!empty($odf['destino']) ? htmlspecialchars($odf['destino']) : 'Sin troncales');
                                            $fullTitle = "ODF {$odf['local_index']} ({$odf['capacidad_puertos']} puertos) - {$cableDesc}";
                                        ?>
                                        <button class="tree-odf-item odf-selector btn btn-sm w-100 text-start d-flex align-items-center py-1 px-2 mb-1 rounded text-truncate" 
                                                data-id="<?= $odf['id_odf'] ?>" 
                                                data-localindex="<?= $odf['local_index'] ?>" 
                                                data-cable="<?= htmlspecialchars($cableDesc) ?>"
                                                data-rack="<?= htmlspecialchars($rackName) ?>"
                                                data-odf-text="<?= strtolower(htmlspecialchars("odf {$odf['local_index']} {$odf['capacidad_puertos']} {$cableDesc}")) ?>"
                                                title="<?= $fullTitle ?>">
                                            <i class="bi bi-motherboard me-2 text-cyan-telefonica flex-shrink-0" style="font-size: 0.8rem;"></i>
                                            <div class="text-truncate" style="font-size: 0.77rem;">
                                                <strong class="text-white">ODF <?= $odf['local_index'] ?></strong>
                                                <span class="text-white-50 ms-1">(<?= $odf['capacidad_puertos'] ?>p - <?= $cableDesc ?>)</span>
                                            </div>
                                        </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-2 text-white-50 small opacity-50 fst-italic">Sin ODFs asignados</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- PANEL CENTRAL: Matriz Física del ODF e Indicadores Tremor / shadcn -->
        <div class="col-xl-6 col-lg-6 col-md-8">
            <!-- TARJETAS DE MÉTRICAS (KPIs): Horizontales, compactas (68px) con barras de progreso de 4px -->
            <div class="row g-2 mb-3">
                <!-- Card 1: Total Capacidad -->
                <div class="col-6 col-xl-3">
                    <div class="kpi-card p-2 px-3 h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="kpi-label">Total Puertos</span>
                            <span class="badge kpi-badge-neutral">ODF Activo</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-baseline">
                            <div class="kpi-val"><span id="stat-total">0</span> <small class="text-muted fw-normal" style="font-size: 0.75rem;">pts</small></div>
                            <span class="kpi-sub text-muted" id="stat-total-sub">100% físico</span>
                        </div>
                        <div class="progress kpi-progress">
                            <div class="progress-bar bg-secondary" style="width: 100%"></div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: En Servicio (96 / 144 con barra de 4px emerald) -->
                <div class="col-6 col-xl-3">
                    <div class="kpi-card p-2 px-3 h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="kpi-label">En Servicio</span>
                            <span class="badge kpi-badge-emerald"><i class="bi bi-circle-fill me-1" style="font-size: 0.4rem;"></i>Activo</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-baseline">
                            <div class="kpi-val text-success">
                                <span id="stat-enservicio">0</span> <span class="text-muted fw-normal" style="font-size: 0.78rem;">/ <span id="stat-total-ref1">0</span></span>
                            </div>
                            <span class="kpi-sub text-success fw-semibold" id="stat-enservicio-pct">0% ocupación</span>
                        </div>
                        <div class="progress kpi-progress">
                            <div id="kpi-bar-enservicio" class="progress-bar" style="width: 0%; background-color: #10b981;"></div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Disponibilidad (Libres con barra de 4px cyan) -->
                <div class="col-6 col-xl-3">
                    <div class="kpi-card p-2 px-3 h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="kpi-label">Disponibilidad</span>
                            <span class="badge kpi-badge-cyan"><i class="bi bi-circle me-1" style="font-size: 0.4rem;"></i>Libre</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-baseline">
                            <div class="kpi-val text-dark">
                                <span id="stat-sinservicio">0</span> <span class="text-muted fw-normal" style="font-size: 0.78rem;">/ <span id="stat-total-ref2">0</span></span>
                            </div>
                            <span class="kpi-sub text-cyan fw-semibold" id="stat-sinservicio-pct" style="color: #0284c7;">0% libre</span>
                        </div>
                        <div class="progress kpi-progress">
                            <div id="kpi-bar-sinservicio" class="progress-bar" style="width: 0%; background-color: #0ea5e9;"></div>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Alertas & Reservas (Malogrados / Reservados) -->
                <div class="col-6 col-xl-3">
                    <div class="kpi-card p-2 px-3 h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="kpi-label">Alertas & Res.</span>
                            <span class="badge kpi-badge-rose"><i class="bi bi-exclamation-triangle-fill me-1" style="font-size: 0.4rem;"></i>Atención</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-baseline">
                            <div class="kpi-val">
                                <span id="stat-alertas" class="text-danger">0</span> <small class="text-muted" style="font-size: 0.7rem;">mal.</small> · <span id="stat-reservados" class="text-warning">0</span> <small class="text-muted" style="font-size: 0.7rem;">res.</small>
                            </div>
                            <span class="kpi-sub text-muted" id="stat-alertas-pct">0% retenido</span>
                        </div>
                        <div class="progress kpi-progress">
                            <div id="kpi-bar-alertas" class="progress-bar" style="width: 0%; background-color: #f43f5e;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel Vacío -->
            <div id="bandeja-empty" class="stat-card h-75 d-flex flex-column align-items-center justify-content-center text-muted p-5" style="min-height: 400px;">
                <i class="bi bi-hdd-network display-1 mb-3 text-primary opacity-25"></i>
                <h5 class="fw-bold text-secondary">SELECCIONE UN ODF DEL ÁRBOL</h5>
                <p class="small text-secondary mb-0">Haga clic en un bastidor en el menú izquierdo para visualizar la bandeja física y sus 144 puertos.</p>
            </div>

            <!-- Panel ODF Activo: Bandeja Física del Distribuidor -->
            <div id="bandeja-panel" style="display: none;">
                <div class="stat-card p-3 p-md-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <h4 id="bandeja-titulo" class="mb-0 fw-bold text-dark">Bandeja ODF</h4>
                                <span id="bandeja-rack-tag" class="badge bg-light text-secondary border">Rack 01</span>
                                <button type="button" class="btn btn-success btn-sm shadow-sm" id="btnCargaMasiva" title="Cargar datos de puertos desde un archivo Excel">
                                    <i class="bi bi-file-earmark-arrow-up me-1"></i>Subir Excel
                                </button>
                            </div>
                            <div id="bandeja-subtitulo" class="text-muted small fw-medium mt-1"></div>
                        </div>
                        <div class="leyenda d-flex gap-2 gap-md-3 small fw-semibold text-secondary bg-light px-3 py-1 rounded-pill border">
                            <span><i class="bi bi-circle-fill text-secondary"></i> Sin Servicio</span>
                            <span><i class="bi bi-circle-fill text-success"></i> En Servicio</span>
                            <span><i class="bi bi-circle-fill text-warning"></i> Reservado</span>
                            <span><i class="bi bi-circle-fill text-danger"></i> Malogrado</span>
                        </div>
                    </div>
                    
                    <!-- Chasis Realista de ODF (Bandeja Física de Distribuidor) -->
                    <div class="chassis-container">
                        <div class="chassis-ear left"></div>
                        <div class="chassis-ear right"></div>
                        
                        <!-- Header del Chasis -->
                        <div class="d-flex justify-content-between align-items-center px-2 mb-2 text-white-50 small" style="font-size: 0.7rem;">
                            <span><i class="bi bi-cpu me-1"></i>PATCH PANEL 144 PUERTOS</span>
                            <span>TELEFÓNICA FIBER DISTRIBUTION UNIT</span>
                        </div>

                        <!-- Matriz de Conectores (6 filas de 24 puertos) -->
                        <div id="puertos-grid" class="puerto-grid">
                            <!-- Los 144 puertos se cargarán aquí -->
                        </div>
                        
                        <div class="text-center mt-3 small fw-bold text-secondary opacity-75" style="font-size: 0.72rem;">
                            <i class="bi bi-optical-audio me-1"></i>FRONT PATCHING CASSETTES (1-144)
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANEL LATERAL DERECHO: Historial & Auditoría (Timeline) -->
        <div class="col-xl-3 col-lg-3 col-md-12">
            <div class="activity-panel p-3 p-md-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-clock-history fs-5 text-primary me-2"></i>
                        <h6 class="mb-0 fw-bold">HISTORIAL & AUDITORÍA</h6>
                    </div>
                    <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">LIVE</span>
                </div>
                
                <!-- Estado Vacío Mejorado (Sin ícono genérico de viento) -->
                <div id="actividad-empty" class="text-center py-5 px-3">
                    <div class="empty-timeline-icon mb-3 mx-auto">
                        <i class="bi bi-clock-history fs-3 text-secondary opacity-50"></i>
                    </div>
                    <h6 class="fw-bold text-secondary mb-1" style="font-size: 0.85rem;">Sin historial registrado</h6>
                    <p class="small text-muted mb-0" style="font-size: 0.75rem;">Las transiciones de estado y bitácora de este ODF aparecerán aquí en tiempo real.</p>
                </div>
                
                <!-- Lista de Auditoría Tipo Timeline -->
                <div id="actividad-list" class="timeline-container overflow-auto pe-2 custom-scrollbar" style="max-height: 65vh; display: none;">
                    <!-- Eventos generados por JS -->
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
</div>
<?php endif; ?>

<!-- MODAL INTERACCIÓN -->
<div class="modal fade" id="accionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-dark text-white border-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-plugin"></i> Configurar Puerto <span id="modal-posicion" class="text-info"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4 bg-light">
        <div class="alert alert-info border-0 rounded-3 mb-4 d-flex align-items-center">
            <i class="bi bi-info-circle-fill fs-4 me-3"></i>
            <div>
                <div class="fw-bold mb-1">Mapeo de Fibra (Solo lectura)</div>
                <div id="modalCableInfo" class="small">--</div>
            </div>
        </div>
        <label class="form-label fw-bold text-secondary small">DESCRIPCIÓN DE LA ACTIVIDAD</label>
        <input type="hidden" id="modal-id-puerto">
        <textarea id="modal-descripcion" class="form-control mb-4 border-0 shadow-sm" placeholder="Opcional: Detalles del cambio de estado, ticket, o motivo de reserva..." rows="2"></textarea>
        
        <label class="form-label fw-bold text-secondary small">NUEVO ESTADO</label>
        <div class="d-grid gap-2">
            <button class="btn btn-outline-secondary btn-estado text-start fw-bold shadow-sm" data-estado="Sin Servicio"><i class="bi bi-circle me-2"></i>Sin Servicio / Desconectar</button>
            <button class="btn btn-outline-success btn-estado text-start fw-bold shadow-sm" data-estado="En Servicio"><i class="bi bi-check-circle-fill me-2"></i>En Servicio / Conectar</button>
            <button class="btn btn-outline-warning btn-estado text-start fw-bold shadow-sm" data-estado="Reservado"><i class="bi bi-bookmark-fill me-2"></i>Reservar Puerto</button>
            <button class="btn btn-outline-danger btn-estado text-start fw-bold shadow-sm" data-estado="Malogrado"><i class="bi bi-x-circle-fill me-2"></i>Reportar Malogrado</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="liveToast" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body fw-bold" id="toast-mensaje"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<!-- Modal Carga Masiva de Puertos -->
<div class="modal fade" id="modalCargaMasiva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formCargaMasiva" class="modal-content border-0 shadow">
            <?= Csrf::field() ?>
            <input type="hidden" name="id_odf" id="cargaMasivaIdOdf">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2"></i>Carga Masiva de Puertos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-2">Actualiza los puertos de <strong>este ODF</strong> en lote. La primera fila del archivo debe ser el encabezado con estas columnas:</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered small mb-2">
                        <thead class="table-light"><tr><th>puerto*</th><th>estado*</th><th>descripcion</th><th>cable_origen</th><th>cable_destino</th><th>numero_hilo</th></tr></thead>
                        <tbody>
                            <tr><td>1</td><td>En Servicio</td><td>Servicio cliente A</td><td>0100374_LM_Zapallal</td><td>0100512_LM_Ventanilla_Pachacutec</td><td>1</td></tr>
                            <tr><td>2</td><td>Reservado</td><td></td><td></td><td></td><td></td></tr>
                            <tr><td>5</td><td>Malogrado</td><td>Fibra dañada</td><td></td><td></td><td></td></tr>
                        </tbody>
                    </table>
                </div>
                <ul class="small text-muted ps-3 mb-3">
                    <li><strong>puerto</strong> y <strong>estado</strong> son obligatorios. Estado: Sin Servicio, En Servicio, Reservado o Malogrado.</li>
                    <li><strong>cable_origen / cable_destino</strong> + <strong>numero_hilo</strong> son opcionales: empalman el puerto al cable (el hilo debe estar libre).</li>
                    <li>Celdas vacías limpian la descripción o el empalme del puerto.</li>
                    <li><strong>Todo o nada:</strong> si alguna fila tiene errores, no se aplica ningún cambio.</li>
                </ul>
                <a href="plantillas/plantilla_carga_masiva_puertos.csv" download class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-download me-1"></i>Descargar plantilla</a>
                <div class="mb-0">
                    <label class="form-label small fw-bold">ARCHIVO (.xlsx o .csv)</label>
                    <input type="file" name="archivo" class="form-control" accept=".xlsx,.csv" required>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success px-4 fw-bold">Cargar Datos</button>
            </div>
        </form>
    </div>
</div>

<script>
const USER_ROLE = "<?= $_SESSION['usuario']['rol'] ?>";
const CSRF_TOKEN = "<?= Csrf::token() ?>";

document.addEventListener('DOMContentLoaded', function() {
    let currentOdfId = null;
    const modal = new bootstrap.Modal(document.getElementById('accionModal'));
    const toast = new bootstrap.Toast(document.getElementById('liveToast'));
    const modalCargaMasiva = new bootstrap.Modal(document.getElementById('modalCargaMasiva'));

    // --- Carga Masiva de Puertos ---
    document.getElementById('btnCargaMasiva').addEventListener('click', function() {
        if (!currentOdfId) {
            mostrarToast('Primero seleccione un ODF del panel izquierdo.', 'warning');
            return;
        }
        document.getElementById('cargaMasivaIdOdf').value = currentOdfId;
        modalCargaMasiva.show();
    });

    document.getElementById('formCargaMasiva').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Procesando...';

        fetch('api/carga_masiva_puertos.php', { method: 'POST', body: new FormData(this) })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Error desconocido');
                return data;
            })
            .then(data => {
                modalCargaMasiva.hide();
                this.reset();
                const textoExito = data.message || data.mensaje || 'Carga masiva aplicada con éxito';
                mostrarToast(`<i class="bi bi-check-circle-fill me-2"></i>${escapeHtml(textoExito)}`, 'success');
                cargarBandeja();
            })
            .catch(err => {
                mostrarToast(`<i class="bi bi-exclamation-triangle-fill me-2"></i>${escapeHtml(err.message)}`, 'danger');
            })
            .finally(() => { 
                btn.disabled = false; 
                btn.innerHTML = originalHtml;
            });
    });

    // Escapa caracteres especiales para prevenir XSS al insertar datos en el DOM
    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    // Helper para cálculo de tiempo relativo
    function formatRelativeTime(dateString) {
        if (!dateString) return 'hace un momento';
        const date = new Date(dateString.replace(/-/g, "/"));
        if (isNaN(date.getTime())) return 'reciente';
        const now = new Date();
        const diffInSeconds = Math.max(0, Math.floor((now - date) / 1000));
        
        if (diffInSeconds < 60) return 'hace unos seg';
        const diffInMinutes = Math.floor(diffInSeconds / 60);
        if (diffInMinutes < 60) return `hace ${diffInMinutes} min`;
        const diffInHours = Math.floor(diffInMinutes / 60);
        if (diffInHours < 24) return `hace ${diffInHours} h`;
        const diffInDays = Math.floor(diffInHours / 24);
        if (diffInDays < 30) return `hace ${diffInDays} d`;
        return date.toLocaleDateString();
    }

    // Buscador interactivo en el Árbol de Sedes y Racks
    document.getElementById('searchSite').addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        document.querySelectorAll('.tree-node-site').forEach(siteItem => {
            const siteName = siteItem.getAttribute('data-site-name') || '';
            let siteHasMatch = siteName.includes(query);
            
            siteItem.querySelectorAll('.tree-node-rack').forEach(rackItem => {
                const rackName = rackItem.getAttribute('data-rack-name') || '';
                let rackHasMatch = siteHasMatch || rackName.includes(query);
                
                rackItem.querySelectorAll('.tree-odf-item').forEach(odfItem => {
                    const odfText = odfItem.getAttribute('data-odf-text') || '';
                    if (rackHasMatch || odfText.includes(query)) {
                        odfItem.style.display = 'flex';
                        rackHasMatch = true;
                    } else {
                        odfItem.style.display = 'none';
                    }
                });
                
                if (rackHasMatch) {
                    rackItem.style.display = 'block';
                    siteHasMatch = true;
                } else {
                    rackItem.style.display = 'none';
                }
            });
            
            if (siteHasMatch) {
                siteItem.style.display = 'block';
                const collapseEl = siteItem.querySelector('.tree-racks-wrapper');
                if (query.length > 0 && collapseEl && !collapseEl.classList.contains('show')) {
                    new bootstrap.Collapse(collapseEl, { toggle: false }).show();
                }
            } else {
                siteItem.style.display = 'none';
            }
        });
    });

    document.querySelectorAll('.odf-selector').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.odf-selector').forEach(b => b.classList.remove('active-odf-tree'));
            this.classList.add('active-odf-tree');
            
            currentOdfId = this.getAttribute('data-id');
            const localIndex = this.getAttribute('data-localindex');
            const cableName = this.getAttribute('data-cable');
            const rackName = this.getAttribute('data-rack') || 'Rack 01';
            
            document.getElementById('bandeja-empty').classList.remove('d-flex');
            document.getElementById('bandeja-empty').classList.add('d-none');
            document.getElementById('bandeja-panel').style.display = 'block';
            document.getElementById('bandeja-titulo').innerText = `ODF ${localIndex}`;
            document.getElementById('bandeja-rack-tag').innerText = rackName;
            document.getElementById('bandeja-subtitulo').innerHTML = `<i class="bi bi-bezier2 me-1 text-info"></i> Troncales: ${escapeHtml(cableName)}`;
            
            cargarBandeja();
        });
    });

    function cargarBandeja() {
        if(!currentOdfId) return;
        
        fetch(`api/get_bandeja.php?id_odf=${currentOdfId}`)
            .then(res => res.json())
            .then(data => {
                if(data.error) {
                    mostrarToast(data.error, 'danger');
                    return;
                }
                renderGrid(data.puertos);
                renderStats(data.puertos);
                renderActividad(data.actividad || []);
            })
            .catch(err => console.error(err));
    }

    // B. Tarjetas de Métricas (KPIs): Horizontales y compactas con porcentaje y barra de 4px
    function renderStats(puertos) {
        const total = puertos.length || 1;
        let libres = 0;
        let ocupados = 0;
        let reservados = 0;
        let alertas = 0;
        
        puertos.forEach(p => {
            if(p.estado === 'Sin Servicio') libres++;
            else if(p.estado === 'En Servicio') ocupados++;
            else if(p.estado === 'Reservado') reservados++;
            else if(p.estado === 'Malogrado') alertas++;
        });
        
        const pctOcupados = Math.round((ocupados / total) * 100);
        const pctLibres = Math.round((libres / total) * 100);
        const pctRetenido = Math.round(((alertas + reservados) / total) * 100);
        
        // Card 1: Total Puertos
        document.getElementById('stat-total').innerText = total;
        document.getElementById('stat-total-ref1').innerText = total;
        document.getElementById('stat-total-ref2').innerText = total;
        
        // Card 2: En Servicio (ej. 96 / 144 con barra de 4px bg-emerald-500)
        document.getElementById('stat-enservicio').innerText = ocupados;
        document.getElementById('stat-enservicio-pct').innerText = `${pctOcupados}% ocupación`;
        const barOcupados = document.getElementById('kpi-bar-enservicio');
        if (barOcupados) barOcupados.style.width = `${pctOcupados}%`;

        // Card 3: Disponibilidad (ej. 33% libre con barra de 4px)
        document.getElementById('stat-sinservicio').innerText = libres;
        document.getElementById('stat-sinservicio-pct').innerText = `${pctLibres}% libre`;
        const barLibres = document.getElementById('kpi-bar-sinservicio');
        if (barLibres) barLibres.style.width = `${pctLibres}%`;

        // Card 4: Alertas & Reservas
        document.getElementById('stat-alertas').innerText = alertas;
        document.getElementById('stat-reservados').innerText = reservados;
        document.getElementById('stat-alertas-pct').innerText = `${pctRetenido}% retenido`;
        const barAlertas = document.getElementById('kpi-bar-alertas');
        if (barAlertas) barAlertas.style.width = `${pctRetenido}%`;
    }

    // D. Panel Derecho: Historial & Auditoría (Timeline continuo sin ícono de viento)
    function renderActividad(actividades) {
        const list = document.getElementById('actividad-list');
        const empty = document.getElementById('actividad-empty');
        list.innerHTML = '';
        
        if (!actividades || actividades.length === 0) {
            empty.style.display = 'block';
            list.style.display = 'none';
            return;
        }
        
        empty.style.display = 'none';
        list.style.display = 'block';
        
        actividades.forEach(a => {
            const relTime = formatRelativeTime(a.fecha_cambio);
            const userStr = escapeHtml(a.usuario || 'Admin');
            const enlaceName = a.cable_enlace || a.observacion || `Enlace Puerto #${a.posicion}`;
            
            let dotColor = 'slate';
            let toColorBadge = 'badge-port-slate';
            if (a.estado_nuevo === 'En Servicio') { dotColor = 'emerald'; toColorBadge = 'badge-port-emerald'; }
            else if (a.estado_nuevo === 'Reservado') { dotColor = 'amber'; toColorBadge = 'badge-port-amber'; }
            else if (a.estado_nuevo === 'Malogrado') { dotColor = 'rose'; toColorBadge = 'badge-port-rose'; }

            list.innerHTML += `
                <div class="timeline-event-item">
                    <span class="timeline-event-dot ${dotColor}"></span>
                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                        <span class="badge-status-transition">
                            <span class="text-secondary">${escapeHtml(a.estado_anterior || 'Sin Servicio')}</span>
                            <i class="bi bi-arrow-right mx-1 text-muted" style="font-size: 0.65rem;"></i>
                            <span class="badge ${toColorBadge} py-0 px-1" style="font-size: 0.68rem;">${escapeHtml(a.estado_nuevo)}</span>
                        </span>
                        <span class="text-muted fw-bold" style="font-size: 0.72rem;">Pto #${escapeHtml(a.posicion)}</span>
                    </div>
                    
                    <div class="timeline-enlace-name text-truncate fw-semibold" title="${escapeHtml(enlaceName)}">
                        <i class="bi bi-link-45deg text-info me-1"></i>${escapeHtml(enlaceName)}
                    </div>
                    
                    <div class="d-flex align-items-center justify-content-between mt-1 text-muted" style="font-size: 0.7rem;">
                        <span><i class="bi bi-clock me-1"></i>${relTime} por <strong>${userStr}</strong></span>
                    </div>
                    
                    ${a.observacion && a.observacion !== enlaceName ? `<div class="timeline-obs-quote mt-1 px-2 py-1 rounded small">"${escapeHtml(a.observacion)}"</div>` : ''}
                </div>
            `;
        });
    }

    // C. Área Central: Matriz Visual del ODF (144 conectores con color de estado y tooltip enriquecido)
    function renderGrid(puertos) {
        const grid = document.getElementById('puertos-grid');
        grid.innerHTML = '';
        
        puertos.forEach(p => {
            const btn = document.createElement('button');
            
            let colorClass = 'btn-plomo';
            let badgeBg = 'bg-secondary';
            if (p.estado === 'En Servicio') { colorClass = 'btn-verde'; badgeBg = 'bg-success'; }
            else if (p.estado === 'Reservado') { colorClass = 'btn-naranja'; badgeBg = 'bg-warning text-dark'; }
            else if (p.estado === 'Malogrado') { colorClass = 'btn-rojo'; badgeBg = 'bg-danger'; }
            
            btn.className = `btn ${colorClass} puerto-btn`;
            btn.innerHTML = `<span class="port-num fw-bold">${p.posicion}</span><i class="bi bi-usb-plug-fill"></i>`;
            
            if (p.bloqueado == 1) {
                btn.innerHTML += `<i class="bi bi-lock-fill position-absolute top-0 end-0 m-1 text-white shadow-sm" style="font-size: 0.55rem;"></i>`;
            }
            
            const destinoStr = p.tipo_cable ? escapeHtml(p.tipo_cable) : 'Sin empalme asignado';
            const hiloStr = p.numero_hilo ? '#' + escapeHtml(p.numero_hilo) : 'Libre';
            const modifStr = p.ultima_modificacion ? `${formatRelativeTime(p.ultima_modificacion)} ${p.modificado_por_nombre ? 'por ' + escapeHtml(p.modificado_por_nombre) : ''}` : 'Sin cambios';
            
            // Tooltip interactivo tipo Popover shadcn sin recargar página
            const tooltipContent = `
                <div class="p-1 font-sans text-start" style="min-width: 170px;">
                    <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-50 pb-1 mb-2">
                        <strong class="text-white">Puerto #${p.posicion}</strong>
                        <span class="badge ${badgeBg} py-0 px-2" style="font-size: 0.65rem;">${escapeHtml(p.estado)}</span>
                    </div>
                    <div class="small" style="font-size: 0.75rem; line-height: 1.4;">
                        <div class="text-white-50"><span class="text-muted">Destino:</span> <strong class="text-info">${destinoStr}</strong></div>
                        <div class="text-white-50"><span class="text-muted">Hilo:</span> <strong class="text-white">${hiloStr}</strong></div>
                        <div class="text-white-50"><span class="text-muted">Modificado:</span> <span class="text-light">${modifStr}</span></div>
                        ${p.descripcion ? `<div class="mt-1 pt-1 border-top border-secondary border-opacity-25 fst-italic text-white-50">"${escapeHtml(p.descripcion)}"</div>` : ''}
                    </div>
                </div>
            `;
            
            btn.setAttribute('data-bs-toggle', 'tooltip');
            btn.setAttribute('data-bs-html', 'true');
            btn.setAttribute('data-bs-custom-class', 'shadcn-tooltip');
            btn.setAttribute('title', tooltipContent);
            
            new bootstrap.Tooltip(btn, { boundary: 'window' });
            
            btn.addEventListener('click', () => abrirModal(p));
            grid.appendChild(btn);
        });
    }

    function abrirModal(puerto) {
        if (USER_ROLE === 'Tecnico' && puerto.estado === 'En Servicio') {
            mostrarToast("Los técnicos no pueden modificar puertos que ya están En Servicio.", "danger");
            return;
        }

        document.getElementById('modal-posicion').innerText = puerto.posicion;
        document.getElementById('modal-id-puerto').value = puerto.id_puerto;
        document.getElementById('modal-descripcion').value = puerto.descripcion || '';
        
        let cableInfoText = puerto.tipo_cable ? `<strong>Cable:</strong> ${escapeHtml(puerto.tipo_cable)} <br><strong>Hilo Asignado:</strong> #${escapeHtml(puerto.numero_hilo)}` : 'Este puerto no está empalmado a ningún cable troncal.';
        document.getElementById('modalCableInfo').innerHTML = cableInfoText;
        
        modal.show();
    }

    document.querySelectorAll('.btn-estado').forEach(btn => {
        btn.addEventListener('click', function() {
            const estado = this.getAttribute('data-estado');
            const id_puerto = document.getElementById('modal-id-puerto').value;
            const desc = document.getElementById('modal-descripcion').value;
            
            fetch('api/update_puerto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_puerto: id_puerto, estado: estado, descripcion: desc, csrf_token: CSRF_TOKEN })
            })
            .then(async res => {
                const data = await res.json();
                if(!res.ok) {
                    throw new Error(data.error || 'Error desconocido');
                }
                return data;
            })
            .then(data => {
                modal.hide();
                mostrarToast(data.mensaje, 'success');
                cargarBandeja(); 
            })
            .catch(err => {
                modal.hide();
                mostrarToast(err.message, 'danger');
            });
        });
    });

    function mostrarToast(mensaje, tipo) {
        const toastEl = document.getElementById('liveToast');
        toastEl.className = `toast align-items-center text-bg-${tipo} border-0 shadow-lg`;
        document.getElementById('toast-mensaje').innerHTML = mensaje;
        toast.show();
    }
});
</script>

<?php require_once dirname(__DIR__, 2) . '/views/layouts/footer.php'; ?>
