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
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}
require_once 'config/database.php';
require_once 'classes/Csrf.php';
require_once 'classes/Infraestructura.php';
require_once 'classes/Puerto.php';
require_once 'views/header.php';

$sites = [];
$dbError = null;
try {
    $siteDao = new \App\Daos\MySqlSiteDao($pdo);
    $odfDao = new \App\Daos\MySqlOdfDao($pdo);

    foreach ($siteDao->findAllActive() as $siteEntity) {
        $siteId = $siteEntity->idSite;
        $sites[$siteId]['nombre'] = $siteEntity->nombre;
        $sites[$siteId]['odfs'] = $odfDao->findBySiteIdWithConnectedCables($siteId);
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
    <small>Si ya tenías la base de datos instalada, ejecuta <code>database/migracion_supervisor_cascade.sql</code>.</small></p>

</div>
<?php else: ?>
<div class="container-fluid px-4 py-3">
    <div class="row g-4 h-100">
        <!-- BARRA LATERAL: buscador y acordeón de sedes con sus ODF -->
        <div class="col-xl-2 col-lg-3 col-md-4 ps-0">
            <div class="premium-sidebar p-3 h-100 w-100">
                <div class="d-flex align-items-center mb-3 text-white">
                    <i class="bi bi-router fs-4 me-2"></i>
                    <h5 class="mb-0 fw-bold">MONITOREO<br><small class="fw-normal opacity-75">Sedes & ODFs</small></h5>
                </div>
                
                <div class="mb-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent text-white border-secondary opacity-50"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchSite" class="form-control bg-transparent text-white border-secondary shadow-none" placeholder="Buscar Sede..." style="color: white !important;">
                    </div>
                </div>
                <div class="accordion accordion-flush" id="siteAccordion">
                    <?php foreach ($sites as $id_site => $site): ?>
                    <div class="accordion-item bg-transparent">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#site-<?= $id_site ?>">
                                <i class="bi bi-geo-alt-fill me-2"></i> <?= htmlspecialchars($site['nombre']) ?>
                            </button>
                        </h2>
                        <div id="site-<?= $id_site ?>" class="accordion-collapse collapse" data-bs-parent="#siteAccordion">
                            <div class="accordion-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php if(isset($site['odfs'])): foreach($site['odfs'] as $odf): ?>
                                    <button class="list-group-item list-group-item-action odf-selector px-4 py-3 border-bottom-0" data-id="<?= $odf['id_odf'] ?>" data-localindex="<?= $odf['local_index'] ?>" data-cable="<?= $odf['cables_conectados'] ? htmlspecialchars($odf['cables_conectados']) : 'Sin empalmes lógicos' ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong style="font-size: 0.9rem;"><i class="bi bi-motherboard-fill me-1"></i> ODF <?= $odf['local_index'] ?></strong>
                                        </div>
                                        <div class="small opacity-75"><i class="bi bi-hdd-rack"></i> Rack: <?= htmlspecialchars($odf['ubicacion_rack']) ?></div>
                                    </button>
                                    <?php endforeach; else: ?>
                                    <div class="p-3 text-muted small px-4 opacity-50">Sin ODFs asignados</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- PANEL CENTRAL: matriz de puertos del ODF e indicadores del sitio -->
        <div class="col-xl-7 col-lg-6 col-md-8">
            <!-- Estadísticas Arriba -->
            <div class="row g-3 mb-4">
                <div class="col-sm-3">
                    <div class="stat-card p-3 d-flex align-items-center">
                        <div class="icon-badge bg-primary bg-opacity-10 text-primary me-3"><i class="bi bi-cpu fs-5"></i></div>
                        <div><div class="text-muted small fw-bold">Total Puertos</div><h4 class="mb-0 fw-bold" id="stat-total">0</h4></div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="stat-card p-3 d-flex align-items-center">
                        <div class="icon-badge bg-secondary bg-opacity-10 text-secondary me-3"><i class="bi bi-circle-fill fs-5"></i></div>
                        <div><div class="text-muted small fw-bold">Sin Servicio</div><h4 class="mb-0 fw-bold" id="stat-sinservicio">0</h4></div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="stat-card p-3 d-flex align-items-center">
                        <div class="icon-badge bg-success bg-opacity-10 text-success me-3"><i class="bi bi-check-circle-fill fs-5"></i></div>
                        <div><div class="text-muted small fw-bold">En Servicio</div><h4 class="mb-0 fw-bold" id="stat-enservicio">0</h4></div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="stat-card p-3 d-flex align-items-center">
                        <div class="icon-badge bg-danger bg-opacity-10 text-danger me-3"><i class="bi bi-x-circle-fill fs-5"></i></div>
                        <div><div class="text-muted small fw-bold">Alertas (Malogrados)</div><h4 class="mb-0 fw-bold" id="stat-alertas">0</h4></div>
                    </div>
                </div>
            </div>

            <!-- Panel Vacío -->
            <div id="bandeja-empty" class="stat-card h-75 d-flex flex-column align-items-center justify-content-center text-muted" style="min-height: 400px;">
                <i class="bi bi-hdd-network display-1 mb-3 text-primary opacity-25"></i>
                <h4 class="fw-bold text-secondary">SELECCIONE UN ODF</h4>
                <p>Navegue por las sedes en el menú lateral para monitorear los puertos.</p>
            </div>

            <!-- Panel ODF Activo -->
            <div id="bandeja-panel" style="display: none;">
                <div class="stat-card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <h4 id="bandeja-titulo" class="mb-0 fw-bold text-dark">Bandeja ODF</h4>
                                <button type="button" class="btn btn-success btn-sm shadow-sm" id="btnCargaMasiva" title="Cargar datos de puertos desde un archivo Excel">
                                    <i class="bi bi-file-earmark-arrow-up me-1"></i>Subir
                                </button>
                            </div>
                            <div id="bandeja-subtitulo" class="text-muted small fw-bold mt-1"></div>
                        </div>
                        <div class="leyenda d-flex gap-3 small fw-bold text-secondary bg-light px-3 py-2 rounded-pill">
                            <span><i class="bi bi-square-fill text-secondary"></i> Sin Servicio</span>
                            <span><i class="bi bi-square-fill text-success"></i> En Servicio</span>
                            <span><i class="bi bi-square-fill text-warning"></i> Reservado</span>
                            <span><i class="bi bi-square-fill text-danger"></i> Malogrado</span>
                        </div>
                    </div>
                    
                    <div class="chassis-container">
                        <div class="chassis-ear left"></div>
                        <div class="chassis-ear right"></div>
                        <div id="puertos-grid" class="puerto-grid">
                            <!-- Los puertos se cargarán aquí -->
                        </div>
                        <div class="text-center mt-3 small fw-bold text-secondary opacity-75"><i class="bi bi-optical-audio"></i> FRONT PANEL</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANEL LATERAL (ACTIVIDAD) -->
        <div class="col-xl-3 col-lg-3 col-md-12">
            <div class="activity-panel p-4 h-100">
                <div class="d-flex align-items-center mb-4">
                    <i class="bi bi-clock-history fs-4 text-primary me-2"></i>
                    <h5 class="mb-0 fw-bold">Actividad Reciente</h5>
                </div>
                <div id="actividad-empty" class="text-center text-muted mt-5">
                    <i class="bi bi-wind fs-1 opacity-25 mb-2"></i>
                    <p class="small">No hay actividad registrada para este ODF.</p>
                </div>
                <div id="actividad-list" class="overflow-auto pe-2" style="max-height: 60vh;">
                    <!-- Actividad generada por JS -->
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
        btn.disabled = true;
        fetch('api/carga_masiva_puertos.php', { method: 'POST', body: new FormData(this) })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Error desconocido');
                return data;
            })
            .then(data => {
                modalCargaMasiva.hide();
                this.reset();
                mostrarToast(data.mensaje, 'success');
                cargarBandeja();
            })
            .catch(err => {
                mostrarToast(err.message, 'danger');
            })
            .finally(() => { btn.disabled = false; });
    });

    // Escapa caracteres especiales para prevenir XSS al insertar datos en el DOM
    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    document.getElementById('searchSite').addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('#siteAccordion .accordion-item').forEach(item => {
            const siteName = item.querySelector('.accordion-button').textContent.toLowerCase();
            if (siteName.includes(query)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });

    document.querySelectorAll('.odf-selector').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.odf-selector').forEach(b => b.classList.remove('active', 'border-start', 'border-info', 'border-4'));
            this.classList.add('active', 'border-start', 'border-info', 'border-4');
            
            currentOdfId = this.getAttribute('data-id');
            const localIndex = this.getAttribute('data-localindex');
            const cableName = this.getAttribute('data-cable');
            
            document.getElementById('bandeja-empty').classList.remove('d-flex');
            document.getElementById('bandeja-empty').classList.add('d-none');
            document.getElementById('bandeja-panel').style.display = 'block';
            document.getElementById('bandeja-titulo').innerText = `ODF ${localIndex}`;
            document.getElementById('bandeja-subtitulo').innerHTML = `<i class="bi bi-bezier2"></i> Cables Troncales: ${cableName}`;
            
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

    function renderStats(puertos) {
        let total = puertos.length;
        let libres = 0;
        let ocupados = 0;
        let alertas = 0;
        
        puertos.forEach(p => {
            if(p.estado === 'Sin Servicio') libres++;
            else if(p.estado === 'En Servicio') ocupados++;
            else if(p.estado === 'Malogrado') alertas++;
        });
        
        document.getElementById('stat-total').innerText = total;
        document.getElementById('stat-sinservicio').innerText = libres;
        document.getElementById('stat-enservicio').innerText = ocupados;
        document.getElementById('stat-alertas').innerText = alertas;
    }

    function renderActividad(actividades) {
        const list = document.getElementById('actividad-list');
        const empty = document.getElementById('actividad-empty');
        list.innerHTML = '';
        
        if (actividades.length === 0) {
            empty.style.display = 'block';
            list.style.display = 'none';
            return;
        }
        
        empty.style.display = 'none';
        list.style.display = 'block';
        
        actividades.forEach(a => {
            const timeDate = new Date(a.fecha_cambio);
            const timeStr = timeDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const dateStr = timeDate.toLocaleDateString();
            
            let color = '#64748b'; // plomo default
            if(a.estado_nuevo === 'En Servicio') color = '#22c55e';
            if(a.estado_nuevo === 'Reservado') color = '#f59e0b';
            if(a.estado_nuevo === 'Malogrado') color = '#ef4444';

            list.innerHTML += `
                <div class="timeline-item" style="border-left-color: ${color}">
                    <div class="timeline-time">${dateStr} ${timeStr}</div>
                    <div class="timeline-text fw-bold">Pto ${escapeHtml(a.posicion)} <i class="bi bi-arrow-right"></i> ${escapeHtml(a.estado_nuevo)}</div>
                    <div class="small text-muted"><i class="bi bi-person"></i> ${escapeHtml(a.usuario)}</div>
                    ${a.observacion ? `<div class="small fst-italic text-secondary mt-1 px-2 py-1 bg-light rounded border border-light">"${escapeHtml(a.observacion)}"</div>` : ''}
                </div>
            `;
        });
    }

    function renderGrid(puertos) {
        const grid = document.getElementById('puertos-grid');
        grid.innerHTML = '';
        
        puertos.forEach(p => {
            const btn = document.createElement('button');
            
            let colorClass = 'btn-plomo';
            if(p.estado === 'En Servicio') colorClass = 'btn-verde';
            if(p.estado === 'Reservado') colorClass = 'btn-naranja';
            if(p.estado === 'Malogrado') colorClass = 'btn-rojo';
            
            btn.className = `btn ${colorClass} puerto-btn`;
            btn.innerHTML = `<span class="fs-6">${p.posicion}</span>`;
            btn.innerHTML += `<i class="bi bi-usb-plug-fill mt-1"></i>`;
            
            if (p.bloqueado == 1) {
                btn.innerHTML += `<i class="bi bi-lock-fill position-absolute top-0 end-0 m-1 text-white shadow-sm" style="font-size: 0.6rem;"></i>`;
            }
            
            const desc = p.descripcion ? `\n\nDesc: ${p.descripcion}` : '';
            const tooltipContent = `<div class="text-start"><strong class="d-block border-bottom pb-1 mb-1">Puerto ${p.posicion}</strong><span class="badge ${colorClass} mb-1">${escapeHtml(p.estado)}</span>${p.descripcion ? '<br><small class="text-white-50 mt-1 d-block"><i class="bi bi-chat-text me-1"></i>'+escapeHtml(p.descripcion)+'</small>' : ''}</div>`;
            
            btn.setAttribute('data-bs-toggle', 'tooltip');
            btn.setAttribute('data-bs-html', 'true');
            btn.setAttribute('title', tooltipContent);
            
            new bootstrap.Tooltip(btn);
            
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
        document.getElementById('toast-mensaje').innerText = mensaje;
        toast.show();
    }
});
</script>

<?php require_once 'views/footer.php'; ?>
