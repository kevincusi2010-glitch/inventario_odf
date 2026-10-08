<?php
/**
 * API CRUD de la infraestructura física: Sitios (Site), Cables de alimentación y ODF.
 *
 * Atiende por POST las acciones create_site, update_site, delete_site,
 * create_cable, update_cable, delete_cable, create_odf, update_odf y delete_odf.
 * Acceso restringido a Administrador y Supervisor; este último sólo puede
 * modificar los registros que él mismo creó durante la sesión actual.
 * También traduce los errores de negocio en mensajes de sesión ($_SESSION['msg']
 * o $_SESSION['error']) y devuelve al usuario a la vista de donde vino.
 */
session_start();
require_once '../classes/Infraestructura.php';
require_once '../classes/Csrf.php';

if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], ['Administrador', 'Supervisor'])) {
    die("No autorizado");
}

// Protección CSRF: todas las acciones mutantes requieren un token válido
Csrf::validateOrDie();

// Asegurar la estructura de sesión usada por los Supervisores
if (!isset($_SESSION['creados_en_sesion']) || !is_array($_SESSION['creados_en_sesion'])) {
    $_SESSION['creados_en_sesion'] = ['site' => [], 'odf' => [], 'cable' => []];
}

$action = $_POST['action'] ?? '';
$infra = new Infraestructura();

// Una sola transacción por petición: el alta, la edición o la baja de Site,
// Cable y ODF se confirman juntos o se revierten por completo.
$pdo = Database::getInstance()->getConnection();

try {
    $pdo->beginTransaction();

    if ($action === 'create_site') {
        $infra->createSite($_POST['nombre'], $_POST['direccion'], $_POST['departamento'], $_POST['distrito'], $_POST['tipo']);
        $_SESSION['msg'] = "Site creado correctamente.";
    }
    else if ($action === 'edit_site') {
        $infra->updateSite($_POST['id_site'], $_POST['nombre'], $_POST['direccion'], $_POST['departamento'], $_POST['distrito'], $_POST['tipo']);
        $_SESSION['msg'] = "Site modificado correctamente.";
    }
    else if ($action === 'create_cable') {
        $infra->createCable($_POST['id_site'], $_POST['origen'], $_POST['destino'] ?? '', $_POST['capacidad_hilos'], $_POST['rack'] ?? '', $_POST['fecha_instalacion'] ?? '');
        $_SESSION['msg'] = "Cable creado correctamente.";
    }
    else if ($action === 'edit_cable') {
        $infra->updateCable($_POST['id_cable'], $_POST['id_site'], $_POST['origen'], $_POST['destino'] ?? '', $_POST['capacidad_hilos'], $_POST['rack'] ?? '', $_POST['fecha_instalacion'] ?? '');
        $_SESSION['msg'] = "Cable modificado correctamente.";
    }
    else if ($action === 'create_odf') {
        $infra->createOdf($_POST['id_site'], $_POST['origen'], $_POST['destino'], $_POST['capacidad_puertos'], $_POST['ubicacion_rack']);
        $_SESSION['msg'] = "ODF creado correctamente.";
    }
    else if ($action === 'edit_odf') {
        $infra->updateOdf($_POST['id_odf'], $_POST['id_site'], $_POST['origen'], $_POST['destino'], $_POST['capacidad_puertos'], $_POST['ubicacion_rack']);
        $_SESSION['msg'] = "ODF modificado correctamente.";
    }
    else if ($action === 'delete_site') {
        $infra->deleteSite($_POST['id_site']);
        $_SESSION['msg'] = "Site eliminado correctamente.";
    }
    else if ($action === 'delete_cable') {
        $infra->deleteCable($_POST['id_cable']);
        $_SESSION['msg'] = "Cable eliminado correctamente.";
    }
    else if ($action === 'delete_odf') {
        $infra->deleteOdf($_POST['id_odf']);
        $_SESSION['msg'] = "ODF eliminado correctamente.";
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
}

$id_site = $_POST['id_site'] ?? $_GET['id_site'] ?? '';
if ($id_site && in_array($action, ['create_odf', 'edit_odf', 'delete_odf', 'create_cable', 'edit_cable', 'delete_cable'])) {
    header("Location: ../admin_site_detalle.php?id_site=" . $id_site);
} else {
    header("Location: ../admin_infraestructura.php");
}
exit;
?>
