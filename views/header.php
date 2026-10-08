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
    <style>
        body { background-color: #f4f6f9; }
        .navbar-telefonica { background-color: #003245; }
        .puerto-btn { width: 100%; height: 50px; font-weight: bold; position: relative;}
        .puerto-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 10px; }
        .industrial-panel { background: #fff; border: 1px solid #dee2e6; border-radius: 4px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-telefonica mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-diagram-3-fill me-2"></i>ODF Tracker - Telefónica
            </a>
            <?php if(isset($_SESSION['usuario'])): ?>
            <ul class="navbar-nav me-auto">
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
            <div class="d-flex align-items-center text-white">
                <span class="me-3"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['usuario']['nombre']) ?> (<?= $_SESSION['usuario']['rol'] ?>)</span>
                <button type="button" class="btn btn-outline-light btn-sm me-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    <i class="bi bi-key"></i> Cambiar Contraseña
                </button>
                <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Salir</a>
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
