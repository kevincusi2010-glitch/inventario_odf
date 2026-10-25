<?php
/**
 * Barra de navegación y encabezado común a todas las vistas internas
 * (dashboard, inventario, estadísticas y usuarios).
 *
 * Deja abierto el contenedor principal que cierra views/footer.php e incluye el
 * modal de cambio de contraseña del propio usuario.
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario ODF Telefónica</title>

    <!-- Google Fonts: Inter (Clean modern typography) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/app.css?v=<?= @filemtime(dirname(__DIR__, 2) . '/public/assets/css/app.css') ?: time() ?>">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-telefonica mb-4 shadow-sm" style="background-color: #003245 !important;">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="bi bi-diagram-3-fill me-2 text-info"></i>ODF Tracker - Telefónica
            </a>
            <?php if(isset($_SESSION['usuario'])): ?>
            <button class="navbar-toggler border-0 shadow-none px-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTelefonica" aria-controls="navbarTelefonica" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarTelefonica">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 pt-2 pt-lg-0">
                    <li class="nav-item">
                        <a class="nav-link text-white fw-medium px-3 <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-medium px-3 <?= basename($_SERVER['PHP_SELF']) == 'admin_infraestructura.php' || basename($_SERVER['PHP_SELF']) == 'admin_site_detalle.php' ? 'active' : '' ?>" href="admin_infraestructura.php">Gestión de Sedes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-medium px-3 <?= basename($_SERVER['PHP_SELF']) == 'estadisticas.php' ? 'active' : '' ?>" href="estadisticas.php">Estadísticas</a>
                    </li>
                    <?php if($_SESSION['usuario']['rol'] == 'Administrador'): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-medium px-3 <?= basename($_SERVER['PHP_SELF']) == 'admin_usuarios.php' ? 'active' : '' ?>" href="admin_usuarios.php">Gestión de Usuarios</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center gap-2 text-white pt-2 pt-lg-0 border-top border-lg-0 border-white-50 mt-2 mt-lg-0">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-admin-menu d-flex align-items-center gap-2 px-2 py-1 rounded-pill shadow-none" type="button" id="adminMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="<?= htmlspecialchars($_SESSION['usuario']['nombre']) ?> (<?= $_SESSION['usuario']['rol'] ?>)">
                            <i class="bi bi-person-circle text-info fs-5"></i>
                            <i class="bi bi-list fs-5 text-white"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2 py-2" aria-labelledby="adminMenuDropdown" style="min-width: 210px;">
                            <li class="px-3 py-1 mb-1 text-muted small border-bottom pb-2">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></div>
                                <div class="text-secondary small"><?= htmlspecialchars($_SESSION['usuario']['rol']) ?></div>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item d-flex align-items-center py-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="bi bi-key text-primary me-2 fs-6"></i>
                                    <span>Cambiar Contraseña</span>
                                </button>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center py-2 text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2 fs-6"></i>
                                    <span class="fw-semibold">Cerrar Sesión</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    <div class="container-fluid px-4">

    <!-- Modal: cambio de contraseña del propio usuario -->
    <?php if(isset($_SESSION['usuario'])): ?>
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="api/crud_usuarios.php" method="POST">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title"><i class="bi bi-shield-lock"></i> Cambiar Mi Contraseña</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="change_my_password">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">CONTRASEÑA ACTUAL</label>
                            <input type="password" name="old_password" class="form-control bg-light" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">NUEVA CONTRASEÑA</label>
                            <input type="password" name="new_password" class="form-control bg-light" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-dark">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
