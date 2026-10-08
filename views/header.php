<?php
/**
 * Barra de navegación y encabezado común a todas las vistas internas
 * (dashboard, inventario, estadísticas y usuarios).
 *
 * Deja abierto el contenedor principal que cierra views/footer.php e incluye el
 * modal de cambio de contraseña del propio usuario.
 */
require_once __DIR__ . '/../classes/Csrf.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario ODF Telefónica</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-telefonica mb-4 shadow-sm">
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
                        <a class="nav-link text-white fw-medium px-3 <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard Operativo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white fw-medium px-3 <?= basename($_SERVER['PHP_SELF']) == 'admin_infraestructura.php' || basename($_SERVER['PHP_SELF']) == 'admin_site_detalle.php' ? 'active' : '' ?>" href="admin_infraestructura.php">Inventario de ODF</a>
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
                <div class="d-flex flex-wrap align-items-center gap-2 text-white pt-2 pt-lg-0 border-top border-lg-0 border-white-50 mt-2 mt-lg-0">
                    <span class="me-2 text-white-50 small"><i class="bi bi-person-circle me-1 text-white"></i><strong class="text-white"><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></strong> (<?= $_SESSION['usuario']['rol'] ?>)</span>
                    <button type="button" class="btn btn-outline-light btn-sm btn-rounded shadow-none" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="bi bi-key"></i> <span class="d-none d-sm-inline">Contraseña</span>
                    </button>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm btn-rounded shadow-none"><i class="bi bi-box-arrow-right"></i> <span class="d-none d-sm-inline">Salir</span></a>
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
