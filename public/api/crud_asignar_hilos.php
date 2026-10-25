<?php
/**
 * API de mapeo lógico de hilos (asignación por lote) de un ODF.
 *
 * Recibe por POST el id_odf y dos arreglos paralelos indexados por posición de
 * puerto: cables[posicion] (id del cable de alimentación) e hilos[posicion]
 * (número de hilo dentro de ese cable). Sólo la puede usar el Administrador.
 *
 * Validaciones aplicadas a cada fila antes de guardar:
 *  - el número de hilo no puede superar la capacidad_hilos del cable;
 *  - un mismo hilo de un mismo cable no puede repetirse en dos puertos;
 *  - tampoco puede estar ya empalmado en otro ODF de la misma sede.
 * Todo se guarda en una sola transacción: si una fila falla, no se aplica nada.
 */
require_once dirname(__DIR__, 2) . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Core\DatabaseConnection;
use App\Daos\MySqlCableDao;
use App\Daos\MySqlPuertoDao;

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'Administrador') {
    die("No autorizado");
}

// Protección CSRF: esta acción mutante requiere un token válido
Csrf::validateOrDie();

$id_odf = $_POST['id_odf'] ?? null;
$cables = $_POST['cables'] ?? [];
$hilos = $_POST['hilos'] ?? [];

if (!$id_odf) {
    header("Location: ../admin_infraestructura.php");
    exit;
}

try {
    $pdo = DatabaseConnection::getSharedInstance();
    $cableDao = new MySqlCableDao($pdo);
    $puertoDao = new MySqlPuertoDao($pdo);

    DatabaseConnection::transaction($pdo, function () use ($cableDao, $puertoDao, $cables, $hilos, $id_odf) {
        $capMap = $cableDao->findCapacidadesMap();

        foreach ($cables as $posicion => $id_cable) {
            $id_cable = !empty($id_cable) ? (int)$id_cable : null;
            $hilo = !empty($hilos[$posicion]) ? (int)$hilos[$posicion] : null;

            if ($id_cable === null) {
                $hilo = null; // Sin cable no puede existir número de hilo
            }

            if ($id_cable !== null && $hilo !== null) {
                $capacidadMax = $capMap[$id_cable] ?? 0;
                if ($hilo > $capacidadMax) {
                    throw new Exception("El Hilo $hilo excede la capacidad máxima ($capacidadMax) del cable seleccionado.");
                }

                $conflict = $puertoDao->findThreadConflict($id_cable, $hilo, (int)$id_odf);
                if ($conflict) {
                    throw new Exception("El Hilo $hilo del cable seleccionado ya está siendo utilizado por otro ODF (ID Sistema: {$conflict['id_odf']}) en su Puerto {$conflict['posicion']}. Dos puertos no pueden empalmar el mismo hilo.");
                }
                
                // Validación local: dentro del mismo ODF un número de hilo no puede repetirse
                foreach ($cables as $p2 => $c2) {
                    if ($posicion !== $p2 && !empty($c2) && !empty($hilos[$p2]) && $c2 == $id_cable && $hilos[$p2] == $hilo) {
                        throw new Exception("Estás intentando asignar el mismo Hilo $hilo a los puertos $posicion y $p2 de este mismo ODF. Cada puerto debe tener un hilo único.");
                    }
                }
            }

            $puertoDao->assignThread((int)$id_odf, (int)$posicion, $id_cable, $hilo);
        }
    });

    $_SESSION['msg'] = "Mapeo de hilos guardado correctamente.";
} catch (Exception $e) {
    $_SESSION['error'] = "Error al guardar: " . $e->getMessage();
}

header("Location: ../asignar_hilos.php?id_odf=$id_odf");
exit;
?>
