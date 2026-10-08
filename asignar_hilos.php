<?php
/**
 * Mapeo lógico de hilos de un ODF (solo Administrador).
 *
 * Muestra una fila por puerto para asignar el cable de alimentación y el número
 * de hilo, con autocompletado por lote (Bulk Fill) y validación en vivo contra
 * tres errores típicos de campo: hilo mayor a la capacidad del cable, hilo ya
 * usado por otro ODF de la sede y hilo repetido dentro del mismo ODF.
 */
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Administrador') {
    header("Location: dashboard.php");
    exit;
}
require_once 'config/database.php';
require_once 'classes/Csrf.php';

$id_odf = $_GET['id_odf'] ?? null;
if (!$id_odf) { header("Location: admin_infraestructura.php"); exit; }

$stmt = $pdo->prepare("SELECT o.*, s.nombre as site_nombre FROM ODF o JOIN Site s ON o.id_site = s.id_site WHERE o.id_odf = ?");
$stmt->execute([$id_odf]);
$odf = $stmt->fetch();
if (!$odf) { header("Location: admin_infraestructura.php"); exit; }

$stmt = $pdo->prepare("SELECT *, CONCAT_WS(' → ', origen, destino) AS tipo_cable FROM CableAlimentacion WHERE id_site = ? AND activo = 1 ORDER BY origen");
$stmt->execute([$odf['id_site']]);
$cables = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM Puerto WHERE id_odf = ? ORDER BY posicion");
$stmt->execute([$id_odf]);
$puertos = $stmt->fetchAll();

// Hilos ya utilizados por OTROS ODFs de la misma sede (validación anti-colisión)
$stmtUsed = $pdo->prepare("
    SELECT p.id_cable_conectado, p.numero_hilo, p.id_odf 
    FROM Puerto p 
    JOIN ODF o ON p.id_odf = o.id_odf
    WHERE o.id_site = ? AND p.id_cable_conectado IS NOT NULL AND p.numero_hilo IS NOT NULL
");
$stmtUsed->execute([$odf['id_site']]);
$usedThreads = [];
foreach ($stmtUsed->fetchAll() as $row) {
    if ($row['id_odf'] != $id_odf) {
        $usedThreads[$row['id_cable_conectado']][] = (int)$row['numero_hilo'];
    }
}
$usedThreadsJson = json_encode($usedThreads);

require_once 'views/header.php';
?>

<div class="container-fluid py-4 bg-light min-vh-100">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-4 rounded-4 shadow-sm border-0" style="background: linear-gradient(to right, #ffffff, #f8fafc);">
                <div>
                    <h2 class="fw-bold text-dark mb-0"><i class="bi bi-link-45deg text-warning me-2"></i>Mapeo Lógico de Hilos</h2>
                    <p class="text-muted mb-0 mt-1">Configuración manual de empalmes para el <strong>ODF #<?= $odf['id_odf'] ?> (<?= htmlspecialchars($odf['origen'].' / '.$odf['destino']) ?>)</strong> en <strong><?= htmlspecialchars($odf['site_nombre']) ?></strong></p>
                </div>
                <div>
                    <a href="admin_infraestructura.php" class="btn btn-outline-secondary btn-rounded shadow-sm"><i class="bi bi-arrow-left me-1"></i>Volver</a>
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

    <div class="row">
        <!-- Herramienta de Mapeo en Lote -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 rounded-4 sticky-top" style="top: 20px;">
                <div class="card-body p-4">
                    <h5 class="fw-bold border-bottom pb-2 mb-3"><i class="bi bi-magic text-primary me-2"></i>Asignación por Lote (Rango)</h5>
                    <p class="small text-muted mb-3">Utilice esta herramienta para autocompletar rápidamente un rango de puertos hacia un mismo cable.</p>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Cable Destino</label>
                        <select id="bulkCable" class="form-select bg-light border-0 shadow-none">
                            <option value="">-- Seleccione un cable --</option>
                            <?php foreach($cables as $c): ?>
                                <option value="<?= $c['id_cable'] ?>"><?= htmlspecialchars($c['tipo_cable']) ?> (<?= $c['capacidad_hilos'] ?>H)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Del Puerto...</label>
                            <input type="number" id="bulkPortStart" class="form-control bg-light border-0 shadow-none" min="1" max="<?= $odf['capacidad_puertos'] ?>" value="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Al Puerto...</label>
                            <input type="number" id="bulkPortEnd" class="form-control bg-light border-0 shadow-none" min="1" max="<?= $odf['capacidad_puertos'] ?>" value="12">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Hilo Inicial del Cable</label>
                        <input type="number" id="bulkThreadStart" class="form-control bg-light border-0 shadow-none" min="1" value="1">
                        <small class="text-muted d-block mt-1">Los hilos se incrementarán automáticamente.</small>
                    </div>
                    
                    <button type="button" class="btn btn-primary w-100 btn-rounded shadow-sm" onclick="applyBulk()">Aplicar a la Tabla <i class="bi bi-arrow-right"></i></button>
                    
                    <div class="alert alert-info mt-3 small py-2 mb-0 border-0 rounded-3"><i class="bi bi-info-circle me-1"></i>Aún debes pulsar <strong>"Guardar Empalmes"</strong> en la tabla para registrar en la base de datos.</div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de Puertos Individuales -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-0">
                    <form action="api/crud_asignar_hilos.php" method="POST">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id_odf" value="<?= $id_odf ?>">
                        <div class="table-responsive" style="max-height: 700px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top shadow-sm" style="z-index: 10;">
                                    <tr>
                                        <th class="ps-4">Puerto ODF</th>
                                        <th>Cable Conectado</th>
                                        <th>Nro. Hilo</th>
                                        <th>Estado Actual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($puertos as $p): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><i class="bi bi-circle-fill text-secondary me-2" style="font-size:0.5rem"></i>Puerto <?= $p['posicion'] ?></td>
                                        <td>
                                            <select name="cables[<?= $p['posicion'] ?>]" id="cable_<?= $p['posicion'] ?>" class="form-select form-select-sm shadow-none border-0 bg-light" style="width: 250px;">
                                                <option value="">[ Vacío / Sin empalmar ]</option>
                                                <?php foreach($cables as $c): ?>
                                                    <option value="<?= $c['id_cable'] ?>" <?= $p['id_cable_conectado']==$c['id_cable']?'selected':'' ?>><?= htmlspecialchars($c['tipo_cable']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="hilos[<?= $p['posicion'] ?>]" id="hilo_<?= $p['posicion'] ?>" class="form-control form-control-sm shadow-none border-0 bg-light text-center" style="width: 80px;" value="<?= $p['numero_hilo'] ?>" min="1" placeholder="-">
                                        </td>
                                        <td>
                                            <span class="badge <?= $p['estado']=='En Servicio'?'bg-success':($p['estado']=='Malogrado'?'bg-danger':($p['estado']=='Reservado'?'bg-warning text-dark':'bg-secondary')) ?> shadow-sm"><?= $p['estado'] ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        </div>
                        <div class="p-3 bg-white border-top text-end rounded-bottom-4 sticky-bottom shadow-sm">
                            <button type="submit" id="btnGuardarEmpalmes" class="btn btn-success btn-lg btn-rounded shadow px-5"><i class="bi bi-save me-2"></i>Guardar Empalmes Lógicos</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function applyBulk() {
    const cableId = document.getElementById('bulkCable').value;
    const pStart = parseInt(document.getElementById('bulkPortStart').value);
    const pEnd = parseInt(document.getElementById('bulkPortEnd').value);
    const tStart = parseInt(document.getElementById('bulkThreadStart').value);
    
    if (!cableId) { alert("Debe seleccionar un cable destino."); return; }
    if (pStart > pEnd) { alert("El puerto inicial no puede ser mayor al final."); return; }
    
    let currentThread = tStart;
    
    for (let i = pStart; i <= pEnd; i++) {
        const selectEl = document.getElementById('cable_' + i);
        const inputEl = document.getElementById('hilo_' + i);
        
        if (selectEl && inputEl) {
            selectEl.value = cableId;
            inputEl.value = currentThread;
            currentThread++;
            
            // Resaltado temporal para señalar las filas completadas por el script
            selectEl.classList.add('bg-warning', 'bg-opacity-25');
            inputEl.classList.add('bg-warning', 'bg-opacity-25');
            setTimeout(() => {
                selectEl.classList.remove('bg-warning', 'bg-opacity-25');
                inputEl.classList.remove('bg-warning', 'bg-opacity-25');
            }, 1000);
            
            // Revalidar la fila que el script acaba de autocompletar
            validateRow(selectEl.closest('tr'));
        }
    }
}

// VALIDACIÓN EN TIEMPO REAL EN EL NAVEGADOR
// usedThreads guarda los hilos ya ocupados por otros ODF de la sede y
// cableCapacities la capacidad máxima de cada cable de alimentación.
const usedThreads = <?= $usedThreadsJson ?>;
const cableCapacities = {
    <?php foreach($cables as $c): ?>
    "<?= $c['id_cable'] ?>": <?= $c['capacidad_hilos'] ?>,
    <?php endforeach; ?>
};

function validateRow(tr) {
    let select = tr.querySelector('select[name^="cables"]');
    let input = tr.querySelector('input[type="number"][name^="hilos"]');
    if (!select || !input) return;
    
    let cableId = select.value;
    let thread = parseInt(input.value);
    
    input.classList.remove('is-invalid', 'border-danger');
    let errSpan = tr.querySelector('.thread-error');
    if (errSpan) errSpan.remove();
    
    if (cableId && !isNaN(thread)) {
        let cap = cableCapacities[cableId];
        if (thread > cap) {
            input.classList.add('is-invalid', 'border-danger');
            input.insertAdjacentHTML('afterend', `<div class="thread-error text-danger small fw-bold mt-1">Máx ${cap}</div>`);
            return;
        }
        
        if (usedThreads[cableId] && usedThreads[cableId].includes(thread)) {
            input.classList.add('is-invalid', 'border-danger');
            input.insertAdjacentHTML('afterend', '<div class="thread-error text-danger small fw-bold mt-1">Hilo en uso en otro ODF</div>');
            return;
        }
        
        let duplicates = 0;
        document.querySelectorAll('tr').forEach(otherTr => {
            let oSel = otherTr.querySelector('select[name^="cables"]');
            let oInp = otherTr.querySelector('input[type="number"][name^="hilos"]');
            if (oSel && oInp && oSel.value === cableId && parseInt(oInp.value) === thread) {
                duplicates++;
            }
        });
        
        if (duplicates > 1) {
             input.classList.add('is-invalid', 'border-danger');
             input.insertAdjacentHTML('afterend', '<div class="thread-error text-danger small fw-bold mt-1">Hilo duplicado aquí</div>');
        }
    }
}

document.querySelectorAll('input[type="number"][name^="hilos"]').forEach(input => {
    input.addEventListener('input', function() { validateRow(this.closest('tr')); });
});
document.querySelectorAll('select[name^="cables"]').forEach(select => {
    select.addEventListener('change', function() { validateRow(this.closest('tr')); });
});

document.querySelector('form[action="api/crud_asignar_hilos.php"]').addEventListener('submit', function(e) {
    let invalids = this.querySelectorAll('.is-invalid');
    if (invalids.length > 0) {
        e.preventDefault();
        alert('Hay errores en la asignación de hilos (campos en rojo). Por favor corríjalos antes de guardar.');
    }
});

// Validación inicial al cargar la página para los datos ya existentes
document.querySelectorAll('tr').forEach(tr => validateRow(tr));
</script>

<?php require_once 'views/footer.php'; ?>
