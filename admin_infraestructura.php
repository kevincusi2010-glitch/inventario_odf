<?php
/**
 * Inventario de Sedes (Sites) e infraestructura física.
 *
 * Lista las sedes activas con el conteo de ODFs y cables registrados, y ofrece
 * el alta de nuevas sedes. Acceso restringido a Administrador y Supervisor.
 */
session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Administrador', 'Supervisor'])) {
    header("Location: dashboard.php");
    exit;
}
require_once 'config/database.php';
require_once 'classes/Csrf.php';

$siteDao = new \App\Daos\MySqlSiteDao($pdo);
$sitesWithCounts = $siteDao->findAllActiveWithCounts();
$sites = [];
foreach ($sitesWithCounts as $item) {
    $row = $item['site']->toArray();
    $row['total_odfs'] = $item['total_odfs'];
    $row['total_cables'] = $item['total_cables'];
    $sites[] = $row;
}

// Colores de badge por tipo de SITE
$tiposSite = ['PAG' => 'bg-danger', 'AGG' => 'bg-warning text-dark', 'SITE' => 'bg-info text-dark', 'AGG/PAG' => 'bg-primary'];

require_once 'views/header.php';
?>

<div class="container-fluid py-4 bg-light min-vh-100">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-3 p-md-4 rounded-4 shadow-sm border-0 mb-3 flex-wrap gap-3" style="background: linear-gradient(to right, #ffffff, #f8fafc);">
                <div>
                    <h2 class="fw-bold text-dark mb-0 fs-3 fs-md-2"><i class="bi bi-building-gear text-primary me-2"></i>Inventario de ODF</h2>
                    <p class="text-muted mb-0 mt-1 small">Gestión de Sedes (Sites) e Infraestructura Física.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary shadow-sm btn-rounded" data-bs-toggle="modal" data-bs-target="#modalSite"><i class="bi bi-building-add me-1"></i>Nuevo SITE</button>
                </div>
            </div>
            
            <div class="input-group shadow-sm rounded-pill overflow-hidden bg-white mb-4">
                <span class="input-group-text bg-white border-0 ps-4 text-primary"><i class="bi bi-search"></i></span>
                <input type="text" id="siteSearch" class="form-control border-0 py-2" placeholder="Buscar Sede por nombre..." oninput="filterSites()">
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
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre de la Sede</th>
                            <th class="text-center">Tipo</th>
                            <th>Departamento</th>
                            <th>Distrito</th>
                            <th class="text-center">Total Cables</th>
                            <th class="text-center">Total ODFs</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="siteTableBody">
                        <?php if (count($sites) == 0): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No hay sedes registradas.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($sites as $s): ?>
                        <tr class="site-row">
                            <td class="fw-bold site-name-cell"><i class="bi bi-building text-info me-2"></i><?= htmlspecialchars($s['nombre']) ?></td>
                            <td class="text-center site-type-cell"><span class="badge <?= $tiposSite[$s['tipo']] ?? 'bg-secondary' ?> rounded-pill px-3"><?= htmlspecialchars($s['tipo']) ?></span></td>
                            <td><?= htmlspecialchars($s['departamento']) ?></td>
                            <td><?= htmlspecialchars($s['distrito']) ?></td>
                            <td class="text-center"><span class="badge bg-secondary rounded-pill px-3"><?= $s['total_cables'] ?></span></td>
                            <td class="text-center"><span class="badge bg-secondary rounded-pill px-3"><?= $s['total_odfs'] ?></span></td>
                            <td class="text-end text-nowrap">
                                <a href="admin_site_detalle.php?id_site=<?= $s['id_site'] ?>" class="btn btn-sm btn-primary btn-rounded fw-bold shadow-sm me-2"><i class="bi bi-box-arrow-in-right me-1"></i>Ingresar a Sede</a>
                                <button class="btn btn-sm btn-light btn-rounded me-1" onclick="editSite(<?= $s['id_site'] ?>, '<?= addslashes($s['nombre']) ?>', '<?= addslashes($s['direccion']) ?>', '<?= addslashes($s['departamento']) ?>', '<?= addslashes($s['distrito']) ?>', '<?= addslashes($s['tipo']) ?>')"><i class="bi bi-pencil"></i></button>
                                <?php if ($s['total_cables'] > 0 || $s['total_odfs'] > 0): ?>
                                    <button type="button" class="btn btn-sm btn-secondary btn-rounded" title="No se puede eliminar la sede mientras contenga Cables u ODFs." onclick="alert('No se puede eliminar la Sede porque aún contiene ODFs o Cables registrados. Por favor, elimine la infraestructura asociada primero.')"><i class="bi bi-trash"></i></button>
                                <?php else: ?>
                                    <form action="api/crud_infraestructura.php" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Eliminar SITE completamente?');">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="action" value="delete_site">
                                        <input type="hidden" name="id_site" value="<?= $s['id_site'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger btn-rounded"><i class="bi bi-trash"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de alta y edición de una Sede (Site) -->
<div class="modal fade" id="modalSite" tabindex="-1">
    <div class="modal-dialog">
        <form action="api/crud_infraestructura.php" method="POST" class="modal-content border-0 shadow">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="create_site" id="siteAction">

            <input type="hidden" name="id_site" id="siteId">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title" id="siteModalTitle">Nuevo SITE</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3"><label class="form-label small fw-bold">Nombre del SITE</label><input type="text" name="nombre" id="siteNombre" class="form-control" required></div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Tipo</label>
                    <select name="tipo" id="siteTipo" class="form-select" required>
                        <option value="PAG">PAG</option>
                        <option value="AGG">AGG</option>
                        <option value="SITE">SITE</option>
                        <option value="AGG/PAG">AGG/PAG</option>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label small fw-bold">Dirección</label><input type="text" name="direccion" id="siteDireccion" class="form-control"></div>
                <div class="row">
                    <div class="col-6 mb-3"><label class="form-label small fw-bold">Departamento</label><input type="text" name="departamento" id="siteDepartamento" class="form-control"></div>
                    <div class="col-6 mb-3"><label class="form-label small fw-bold">Distrito</label><input type="text" name="distrito" id="siteDistrito" class="form-control"></div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0"><button type="submit" class="btn btn-primary btn-rounded px-4">Guardar SITE</button></div>
        </form>
    </div>
</div>

<script>
function editSite(id, nombre, direccion, departamento, distrito, tipo) {
    document.getElementById('siteAction').value = 'edit_site';
    document.getElementById('siteId').value = id;
    document.getElementById('siteNombre').value = nombre;
    document.getElementById('siteDireccion').value = direccion;
    document.getElementById('siteDepartamento').value = departamento;
    document.getElementById('siteDistrito').value = distrito;
    document.getElementById('siteTipo').value = tipo;
    document.getElementById('siteModalTitle').innerText = 'Editar SITE';
    var modal = new bootstrap.Modal(document.getElementById('modalSite'));
    modal.show();
}

document.getElementById('modalSite').addEventListener('hidden.bs.modal', function () {
    this.querySelector('form').reset();
    document.getElementById('siteAction').value = 'create_site';
    document.getElementById('siteModalTitle').innerText = 'Nuevo SITE';
    document.getElementById('siteId').value = '';
});

function filterSites() {
    const term = document.getElementById('siteSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#siteTableBody .site-row');
    
    rows.forEach(row => {
        const name = row.querySelector('.site-name-cell').innerText.toLowerCase();
        const tipo = row.querySelector('.site-type-cell').innerText.toLowerCase();
        if (name.includes(term) || tipo.includes(term)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

</script>

<?php require_once 'views/footer.php'; ?>
