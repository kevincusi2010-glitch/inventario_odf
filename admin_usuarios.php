<?php
/**
 * Gestión de usuarios del sistema (solo Administrador).
 *
 * Lista las cuentas registradas con su rol y estado, y expone el modal de alta
 * y edición. Las operaciones se ejecutan en api/crud_usuarios.php.
 */
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Administrador') {
    header("Location: dashboard.php");
    exit;
}

require_once 'classes/Csrf.php';
require_once 'classes/Usuario.php';
$usuarioManager = new Usuario();
$usuarios = $usuarioManager->getAllUsuarios();

require_once 'views/header.php';
?>

<div class="container-fluid py-4 bg-light min-vh-100">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-4 rounded-4 shadow-sm border-0 mb-3" style="background: linear-gradient(to right, #ffffff, #f8fafc);">
                <div>
                    <h2 class="fw-bold text-dark mb-0"><i class="bi bi-people-fill text-primary me-2"></i>Gestión de Usuarios</h2>
                    <p class="text-muted mb-0 mt-1">Administración de cuentas, roles y accesos al sistema.</p>
                </div>
                <div>
                    <button class="btn btn-primary shadow-sm btn-rounded fw-bold" data-bs-toggle="modal" data-bs-target="#modalUsuario"><i class="bi bi-person-plus-fill me-1"></i>Nuevo Usuario</button>
                </div>
            </div>

            <?php if (isset($_SESSION['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['msg']); unset($_SESSION['msg']); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Nombre Completo</th>
                                    <th>Correo Electrónico</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th class="text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($usuarios) == 0): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No hay usuarios registrados.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><i class="bi bi-person-circle text-secondary me-2"></i><?= htmlspecialchars($u['nombre']) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($u['correo']) ?></td>
                                    <td>
                                        <?php if($u['rol'] == 'Administrador'): ?>
                                            <span class="badge bg-primary rounded-pill px-3"><i class="bi bi-shield-lock-fill me-1"></i>Administrador</span>
                                        <?php elseif($u['rol'] == 'Supervisor'): ?>
                                            <span class="badge bg-info rounded-pill px-3 text-dark"><i class="bi bi-eye-fill me-1"></i>Supervisor</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill px-3"><i class="bi bi-tools me-1"></i>Técnico</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($u['activo']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill"><i class="bi bi-x-circle-fill me-1"></i>Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-light btn-rounded me-1" onclick="editUsuario(<?= $u['id_usuario'] ?>, '<?= addslashes($u['nombre']) ?>', '<?= addslashes($u['correo']) ?>', '<?= $u['rol'] ?>')" title="Editar Datos"><i class="bi bi-pencil"></i></button>
                                        
                                        <form action="api/crud_usuarios.php" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Restablecer la contraseña a telefonica123?');">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="action" value="reset_password">
                                            <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                            <button type="submit" class="btn btn-sm btn-warning btn-rounded text-dark me-1" title="Restablecer Contraseña"><i class="bi bi-key-fill"></i></button>
                                        </form>

                                        <?php if($u['activo']): ?>
                                            <form action="api/crud_usuarios.php" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Desactivar esta cuenta? El usuario ya no podrá ingresar.');">
                                                <?= Csrf::field() ?>
                                                <input type="hidden" name="action" value="deactivate_user">
                                                <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger btn-rounded" title="Desactivar Cuenta"><i class="bi bi-person-x-fill"></i></button>
                                            </form>
                                        <?php else: ?>
                                            <form action="api/crud_usuarios.php" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Reactivar esta cuenta?');">
                                                <?= Csrf::field() ?>
                                                <input type="hidden" name="action" value="activate_user">
                                                <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success btn-rounded" title="Activar Cuenta"><i class="bi bi-person-check-fill"></i></button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if($u['id_usuario'] != $_SESSION['usuario']['id_usuario']): ?>
                                            <form action="api/crud_usuarios.php" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Eliminar definitivamente a <?= addslashes($u['nombre']) ?>? La cuenta se borrará de la base de datos y esta acción NO se puede deshacer.');">
                                                <?= Csrf::field() ?>
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger btn-rounded" title="Eliminar Usuario (borrado permanente en la base de datos)"><i class="bi bi-trash-fill"></i></button>
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
    </div>
</div>

<!-- Modal Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header bg-light border-0">
        <h5 class="modal-title fw-bold" id="usuarioModalTitle"><i class="bi bi-person-plus-fill text-primary me-2"></i>Nuevo Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="api/crud_usuarios.php" method="POST">
          <div class="modal-body p-4">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" id="usuarioAction" value="create_user">
            <input type="hidden" name="id_usuario" id="usuarioId">
            
            <div class="mb-3">
                <label class="form-label text-secondary fw-bold small">Nombre Completo</label>
                <input type="text" name="nombre" id="usuarioNombre" class="form-control bg-light border-0" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-secondary fw-bold small">Correo Electrónico</label>
                <input type="email" name="correo" id="usuarioCorreo" class="form-control bg-light border-0" required>
            </div>
            <div class="mb-3">
                <label class="form-label text-secondary fw-bold small">Rol en el Sistema</label>
                <select name="rol" id="usuarioRol" class="form-select bg-light border-0" required>
                    <option value="Tecnico">Técnico (Gestión de puertos en campo)</option>
                    <option value="Supervisor">Supervisor (Gestión de puertos y lectura total)</option>
                    <option value="Administrador">Administrador (Control total del sistema e infraestructura)</option>
                </select>
            </div>
            <div class="alert alert-info border-0 shadow-sm mt-4 small" id="passwordNotice">
                <i class="bi bi-info-circle-fill me-2"></i>La contraseña inicial será <strong>telefonica123</strong>.
            </div>
          </div>
          <div class="modal-footer border-0 bg-light">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">Guardar Usuario</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('modalUsuario').addEventListener('hidden.bs.modal', function () {
    this.querySelector('form').reset();
    document.getElementById('usuarioAction').value = 'create_user';
    document.getElementById('usuarioModalTitle').innerHTML = '<i class="bi bi-person-plus-fill text-primary me-2"></i>Nuevo Usuario';
    document.getElementById('usuarioId').value = '';
    document.getElementById('passwordNotice').style.display = 'block';
});

function editUsuario(id, nombre, correo, rol) {
    document.getElementById('usuarioAction').value = 'update_user';
    document.getElementById('usuarioId').value = id;
    document.getElementById('usuarioNombre').value = nombre;
    document.getElementById('usuarioCorreo').value = correo;
    document.getElementById('usuarioRol').value = rol;
    document.getElementById('usuarioModalTitle').innerHTML = '<i class="bi bi-pencil-square text-primary me-2"></i>Editar Usuario';
    document.getElementById('passwordNotice').style.display = 'none';
    
    var modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
    modal.show();
}
</script>

<?php require_once 'views/footer.php'; ?>
