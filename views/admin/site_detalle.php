<?php
/**
 * Vista de detalle de una sede: árbol topológico de la infraestructura.
 *
 * Construye en el servidor la jerarquía Sede (Site) → Gabinete (Rack) →
 * Cable de Alimentación → ODF para pintarla como árbol plegable, y deja
 * disponibles los modales de alta/edición de Cable y ODF. Acceso restringido a
 * Administrador y Supervisor.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Administrador', 'Supervisor'])) {
    header("Location: dashboard.php");
    exit;
}
require_once dirname(__DIR__, 2) . '/config/database.php';

$id_site = $_GET['id_site'] ?? null;
if (!$id_site) {
    header("Location: admin_infraestructura.php");
    exit;
}

$siteDao = new \App\Daos\MySqlSiteDao($pdo);
$cableDao = new \App\Daos\MySqlCableDao($pdo);
$odfDao = new \App\Daos\MySqlOdfDao($pdo);
$puertoDao = new \App\Daos\MySqlPuertoDao($pdo);
$treeBuilder = new \App\Services\InfraestructuraTreeBuilder();

$site = $siteDao->findById((int)$id_site);
if (!$site) {
    header("Location: admin_infraestructura.php");
    exit;
}

$siteActual = $site->toArray();
$cables = $cableDao->findBySiteId((int)$id_site);
$odfsData = $odfDao->findBySiteId((int)$id_site);
$odfs = array_column($odfsData, 'raw');

// Obtener puertos de los ODFs de la sede mediante DAO
$odfIds = array_column($odfs, 'id_odf');
$ports = [];
foreach ($odfIds as $oId) {
    foreach ($puertoDao->findByOdfId((int)$oId) as $p) {
        $ports[] = $p->toArray();
    }
}

// Armado limpio del árbol jerárquico mediante el servicio de dominio
$tree = [$id_site => $treeBuilder->buildTree($siteActual, $odfs, $cables, $ports)];

require_once dirname(__DIR__, 2) . '/views/layouts/header.php';
?>



<div class="container-fluid py-4 bg-light min-vh-100">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-3 p-md-4 rounded-4 shadow-sm border-0 flex-wrap gap-2" style="background: linear-gradient(to right, #ffffff, #f8fafc);">
                <div>
                    <h5 class="fw-bold text-dark mb-0 fs-6"><i class="bi bi-diagram-3 text-primary me-1"></i>SITE: <?= htmlspecialchars($siteActual['nombre']) ?></h5>
                    <p class="text-muted mb-0 mt-1 small">Estructura de Racks, Cables y ODFs en <?= htmlspecialchars($siteActual['departamento'] . ', ' . $siteActual['distrito']) ?>.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="admin_infraestructura.php" class="btn btn-outline-secondary shadow-sm btn-rounded"><i class="bi bi-arrow-left me-1"></i>Volver a Sedes</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php if(isset($_SESSION['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-3"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['msg']); unset($_SESSION['msg']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-3"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-3 p-md-4">
            <?php foreach ($tree as $siteId => $siteData): $s = $siteData['info']; ?>
                <!-- NODO RAÍZ: SEDE (SITE) -->
                <div class="tree-node ms-0 ps-0 border-0 site-node-container">
                    <div class="node-content bg-dark text-white border-dark">
                        <div class="node-title" onclick="toggleNode(this)">
                            <i class="bi bi-chevron-down toggle-icon text-white-50"></i>
                            <i class="bi bi-building text-info"></i>
                            <span class="fw-bold fs-5 site-title-text"><?= htmlspecialchars($s['nombre']) ?></span>
                            <span class="badge bg-secondary ms-2 site-city-text"><?= htmlspecialchars($s['departamento'] . ', ' . $s['distrito']) ?></span>
                        </div>
                        <div class="node-actions d-flex gap-1 align-items-center">
                            <button class="btn btn-sm btn-outline-light fw-bold" style="border-radius:20px;" onclick="addCable(<?= $s['id_site'] ?>)"><i class="bi bi-bezier2"></i> + Cable</button>
                            <button class="btn btn-sm btn-outline-light fw-bold" style="border-radius:20px;" onclick="addODF(<?= $s['id_site'] ?>)"><i class="bi bi-motherboard"></i> + ODF</button>
                        </div>
                    </div>
                    
                    <div class="children-container">
                        <!-- NODOS DE GABINETE (RACK) -->
                        <?php foreach ($siteData['racks'] as $rack => $rackData): 
                            $rackDisplay = (is_numeric($rack) || !preg_match('/^rack/i', trim($rack))) ? 'Rack ' . htmlspecialchars($rack) : htmlspecialchars($rack);
                        ?>
                            <div class="tree-node">
                                <div class="node-content bg-light">
                                    <div class="node-title" onclick="toggleNode(this)">
                                        <i class="bi bi-chevron-down toggle-icon"></i>
                                        <i class="bi bi-hdd-rack-fill text-warning"></i>
                                        <span class="fw-bold text-dark"><?= $rackDisplay ?></span>
                                    </div>
                                </div>
                                <div class="children-container">
                                    <!-- NODOS DE CABLE DENTRO DEL RACK -->
                                    <?php foreach ($rackData['cables'] as $cid => $cableData): $c = $cableData['info']; ?>
                                        <div class="tree-node">
                                            <div class="node-content">
                                                <div class="node-title" onclick="toggleNode(this)">
                                                    <i class="bi bi-chevron-down toggle-icon"></i>
                                                    <i class="bi bi-bezier2 text-primary"></i>
                                                    <span class="fw-bold text-dark me-2"><?= htmlspecialchars($c['tipo_cable']) ?></span>
                                                    <?php if($cid !== 'none'): ?>
                                                        <span class="badge badge-soft"><?= $c['capacidad_hilos'] ?> Hilos</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if($cid !== 'none'): ?>
                                                <div class="node-actions">
                                                    <button class="btn btn-sm btn-outline-secondary btn-rounded me-1" onclick="editCable(<?= $c['id_cable'] ?>, <?= $c['id_site'] ?>, '<?= addslashes($c['origen']) ?>', '<?= addslashes($c['destino']) ?>', <?= $c['capacidad_hilos'] ?>, '<?= addslashes($c['rack']) ?>', '<?= $c['fecha_instalacion'] ?>')"><i class="bi bi-pencil"></i></button>
                                                    <form action="api/crud_infraestructura.php" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar Cable?');"><?= Csrf::field() ?><input type="hidden" name="action" value="delete_cable"><input type="hidden" name="id_cable" value="<?= $c['id_cable'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger btn-rounded"><i class="bi bi-trash"></i></button></form>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="children-container">
                                                <!-- NODOS DE ODF ASOCIADOS AL CABLE -->
                                                <?php foreach ($cableData['odfs'] as $o): ?>
                                                    <div class="tree-node border-left-dashed">
                                                        <div class="node-content border-primary" style="border-left: 4px solid #0d6efd !important;">
                                                            <div class="node-title" style="cursor: default;">
                                                                <i class="bi bi-motherboard-fill text-primary"></i>
                                                                <span class="fw-bold text-dark me-2">ODF <?= $o['local_index'] ?></span>
                                                                <span class="text-muted small me-2"><?= htmlspecialchars($o['origen'] . ' / ' . $o['destino']) ?></span>
                                                                <span class="badge bg-primary rounded-pill"><?= $o['capacidad_puertos'] ?>P</span>
                                                            </div>
                                                            <div class="node-actions d-flex gap-1">
                                                                <a href="asignar_hilos.php?id_odf=<?= $o['id_odf'] ?>" class="btn btn-sm btn-warning fw-bold shadow-sm" style="border-radius: 20px;"><i class="bi bi-link-45deg me-1"></i>Asignar Hilos</a>
                                                                <button class="btn btn-sm btn-light shadow-sm btn-rounded" onclick="editODF(<?= $o['id_odf'] ?>, <?= $o['id_site'] ?>, '<?= addslashes($o['origen']) ?>', '<?= addslashes($o['destino']) ?>', '<?= addslashes($o['ubicacion_rack']) ?>', <?= $o['capacidad_puertos'] ?>)"><i class="bi bi-pencil"></i></button>
                                                                <form action="api/crud_infraestructura.php" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar ODF? Los puertos se perderán.');"><?= Csrf::field() ?><input type="hidden" name="action" value="delete_odf"><input type="hidden" name="id_odf" value="<?= $o['id_odf'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger shadow-sm btn-rounded"><i class="bi bi-trash"></i></button></form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- CABLES DE LA SEDE SIN ODF ASOCIADO -->
                        <?php if(count($siteData['cables_sueltos']) > 0): ?>
                            <div class="tree-node">
                                <div class="node-content bg-light">
                                    <div class="node-title" onclick="toggleNode(this)">
                                        <i class="bi bi-chevron-down toggle-icon"></i>
                                        <i class="bi bi-bezier2 text-warning"></i>
                                        <span class="fw-bold text-dark">Cables Sin ODF Asignado</span>
                                    </div>
                                </div>
                                <div class="children-container">
                                    <?php foreach ($siteData['cables_sueltos'] as $c): ?>
                                        <div class="tree-node">
                                            <div class="node-content">
                                                <div class="node-title" style="cursor:default;">
                                                    <i class="bi bi-dash"></i>
                                                    <span class="text-dark me-2"><?= htmlspecialchars($c['tipo_cable']) ?></span>
                                                    <span class="badge badge-soft"><?= $c['capacidad_hilos'] ?> Hilos</span>
                                                </div>
                                                <div class="node-actions">
                                                    <button class="btn btn-sm btn-outline-secondary btn-rounded me-1" onclick="editCable(<?= $c['id_cable'] ?>, <?= $c['id_site'] ?>, '<?= addslashes($c['origen']) ?>', '<?= addslashes($c['destino']) ?>', <?= $c['capacidad_hilos'] ?>, '<?= addslashes($c['rack']) ?>', '<?= $c['fecha_instalacion'] ?>')"><i class="bi bi-pencil"></i></button>
                                                    <form action="api/crud_infraestructura.php" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar Cable?');"><?= Csrf::field() ?><input type="hidden" name="action" value="delete_cable"><input type="hidden" name="id_cable" value="<?= $c['id_cable'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger btn-rounded"><i class="bi bi-trash"></i></button></form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if(empty($tree)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-tree fs-1 text-light"></i>
                    <p class="mt-3">No hay infraestructura registrada. Comienza creando un SITE.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Formularios modales para crear y editar Cable y ODF -->
<!-- Modal de Cable de Alimentación -->
<div class="modal fade" id="modalCable" tabindex="-1">
    <div class="modal-dialog">
        <form action="api/crud_infraestructura.php" method="POST" class="modal-content border-0 shadow">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="create_cable" id="cableAction">

            <input type="hidden" name="id_cable" id="cableId">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title" id="cableModalTitle">Nuevo Cable</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">SITE de Instalación</label>
                    <select name="id_site" id="cableSite" class="form-select" required>
                        <option value="<?= $siteActual['id_site'] ?>"><?= htmlspecialchars($siteActual['nombre']) ?></option>
                    </select>
                    <small class="text-danger d-none" id="cableSiteWarning"><i class="bi bi-exclamation-circle"></i> Sede preasignada (no modificable).</small>
                </div>
                <div class="row">
                    <div class="col-6 mb-3"><label class="form-label small fw-bold">Origen</label><input type="text" name="origen" id="cableOrigen" class="form-control" required></div>
                    <div class="col-6 mb-3"><label class="form-label small fw-bold">Destino</label><input type="text" name="destino" id="cableDestino" class="form-control"></div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold">Capacidad Hilos</label>
                        <select name="capacidad_hilos" id="cableCapacidad" class="form-select" required>
                            <option value="12">12</option>
                            <option value="24" selected>24</option>
                            <option value="48">48</option>
                            <option value="96">96</option>
                            <option value="144">144</option>
                            <option value="288">288</option>
                        </select>
                    </div>
                    <div class="col-6 mb-3"><label class="form-label small fw-bold">Rack</label><input type="text" name="rack" id="cableRack" class="form-control"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">¿Cuenta con fecha de instalación?</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tiene_fecha" id="cableFechaSi" value="si">
                            <label class="form-check-label" for="cableFechaSi">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tiene_fecha" id="cableFechaNo" value="no" checked>
                            <label class="form-check-label" for="cableFechaNo">No</label>
                        </div>
                    </div>
                </div>
                <div class="mb-3 d-none" id="cableFechaGroup">
                    <label class="form-label small fw-bold">Fecha de Instalación</label><input type="date" name="fecha_instalacion" id="cableFecha" class="form-control">
                </div>
            </div>
            <div class="modal-footer bg-light border-0"><button type="submit" class="btn btn-primary btn-rounded px-4">Guardar Cable</button></div>
        </form>
    </div>
</div>

<!-- Modal de ODF -->
<div class="modal fade" id="modalODF" tabindex="-1">
    <div class="modal-dialog">
        <form action="api/crud_infraestructura.php" method="POST" class="modal-content border-0 shadow">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="create_odf" id="odfAction">

            <input type="hidden" name="id_odf" id="odfId">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title" id="odfModalTitle">Nuevo ODF</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">SITE de Instalación</label>
                    <select name="id_site" id="odfSite" class="form-select" required>
                        <option value="<?= $siteActual['id_site'] ?>"><?= htmlspecialchars($siteActual['nombre']) ?></option>
                    </select>
                    <small class="text-danger d-none" id="odfSiteWarning"><i class="bi bi-exclamation-circle"></i> Sede preasignada (no modificable).</small>
                </div>
                <div class="row">
                    <div class="col-6 mb-3"><label class="form-label small fw-bold">Origen</label><input type="text" name="origen" id="odfOrigen" class="form-control" required></div>
                    <div class="col-6 mb-3"><label class="form-label small fw-bold">Destino</label><input type="text" name="destino" id="odfDestino" class="form-control" required></div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-bold">Puertos (Físicos)</label>
                        <select name="capacidad_puertos" id="odfCapacidad" class="form-select" required>
                            <option value="12">12</option><option value="24" selected>24</option><option value="48">48</option><option value="96">96</option><option value="144">144</option>
                        </select>
                        <small class="text-info d-none" id="odfCapacidadInfo">Si reduce la capacidad, los puertos eliminados no deben estar en uso.</small>
                    </div>
                    <div class="col-6 mb-3"><label class="form-label small fw-bold">Ubicación / Rack</label><input type="text" name="ubicacion_rack" id="odfRack" class="form-control" required></div>
                </div>
                <div class="alert alert-info py-2 small mb-0"><i class="bi bi-info-circle me-1"></i>Para mapear hilos de fibra a los puertos del ODF, guarde el equipo y utilice el botón "Asignar Hilos".</div>
            </div>
            <div class="modal-footer bg-light border-0"><button type="submit" class="btn btn-primary btn-rounded px-4">Guardar ODF</button></div>
        </form>
    </div>
</div>

<script>
function toggleNode(element) {
    element.parentElement.nextElementSibling.classList.toggle('collapsed');
    element.classList.toggle('collapsed');
}

function editCable(id, id_site, origen, destino, cap, rack, fecha) {
    document.getElementById('cableAction').value = 'edit_cable';

    document.getElementById('cableId').value = id;
    
    let sel = document.getElementById('cableSite');
    sel.value = id_site;
    sel.style.pointerEvents = 'auto';
    sel.style.backgroundColor = '';
    document.getElementById('cableSiteWarning').classList.add('d-none');
    
    document.getElementById('cableOrigen').value = origen;
    document.getElementById('cableDestino').value = destino;
    document.getElementById('cableCapacidad').value = cap;
    document.getElementById('cableRack').value = rack;
    
    // Mostrar u ocultar la fecha según si el cable cuenta con ella
    const fechaGroup = document.getElementById('cableFechaGroup');
    const fechaInput = document.getElementById('cableFecha');
    if (fecha) {
        document.getElementById('cableFechaSi').checked = true;
        fechaGroup.classList.remove('d-none');
        fechaInput.value = fecha;
    } else {
        document.getElementById('cableFechaNo').checked = true;
        fechaGroup.classList.add('d-none');
        fechaInput.value = '';
    }
    
    document.getElementById('cableModalTitle').innerText = 'Editar Cable';
    new bootstrap.Modal(document.getElementById('modalCable')).show();
}

// Toggle de fecha de instalación: "No" oculta el campo y limpia su valor
document.querySelectorAll('input[name="tiene_fecha"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const fechaGroup = document.getElementById('cableFechaGroup');
        if (this.value === 'si' && this.checked) {
            fechaGroup.classList.remove('d-none');
        } else {
            fechaGroup.classList.add('d-none');
            document.getElementById('cableFecha').value = '';
        }
    });
});

function editODF(id, id_site, origen, destino, rack, cap) {
    document.getElementById('odfAction').value = 'edit_odf';
    document.getElementById('odfId').value = id;
    
    let sel = document.getElementById('odfSite');
    sel.value = id_site;
    sel.style.pointerEvents = 'auto';
    sel.style.backgroundColor = '';
    document.getElementById('odfSiteWarning').classList.add('d-none');
    
    document.getElementById('odfOrigen').value = origen;
    document.getElementById('odfDestino').value = destino;
    document.getElementById('odfRack').value = rack;
    document.getElementById('odfCapacidad').value = cap;
    
    document.getElementById('odfCapacidadInfo').classList.remove('d-none');
    
    document.getElementById('odfModalTitle').innerText = 'Editar ODF';
    new bootstrap.Modal(document.getElementById('modalODF')).show();
}

function addCable(id_site) {
    document.getElementById('cableAction').value = 'create_cable';
    document.getElementById('cableModalTitle').innerText = 'Nuevo Cable';
    
    let sel = document.getElementById('cableSite');
    sel.value = id_site;
    sel.style.pointerEvents = 'none';
    sel.style.backgroundColor = '#e9ecef';
    document.getElementById('cableSiteWarning').classList.remove('d-none');
    
    new bootstrap.Modal(document.getElementById('modalCable')).show();
}

function addODF(id_site) {
    document.getElementById('odfAction').value = 'create_odf';
    document.getElementById('odfModalTitle').innerText = 'Nuevo ODF';
    
    let sel = document.getElementById('odfSite');
    sel.value = id_site;
    sel.style.pointerEvents = 'none';
    sel.style.backgroundColor = '#e9ecef';
    document.getElementById('odfSiteWarning').classList.remove('d-none');
    
    document.getElementById('odfCapacidadInfo').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('modalODF')).show();
}

// Reset form actions on close
['modalCable', 'modalODF'].forEach(id => {
    document.getElementById(id).addEventListener('hidden.bs.modal', function () {
        this.querySelector('form').reset();
        if(id==='modalCable') { document.getElementById('cableAction').value='create_cable'; document.getElementById('cableModalTitle').innerText='Nuevo Cable'; document.getElementById('cableFechaGroup').classList.add('d-none'); }
        if(id==='modalODF') { 
            document.getElementById('odfAction').value='create_odf'; 
            document.getElementById('odfModalTitle').innerText='Nuevo ODF'; 
            document.getElementById('odfCapacidadInfo').classList.add('d-none');
        }
    });
});
</script>

<?php require_once dirname(__DIR__, 2) . '/views/layouts/footer.php'; ?>
