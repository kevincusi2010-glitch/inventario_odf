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
session_start();
require_once '../config/database.php';
require_once '../classes/Csrf.php';

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
    $pdo->beginTransaction();

    // Capacidades por cable [id_cable => capacidad_hilos]: se leen una sola vez
    // para validar cada fila sin repetir la consulta dentro del bucle.
    $stmtCap = $pdo->query("SELECT id_cable, capacidad_hilos FROM CableAlimentacion")->fetchAll(PDO::FETCH_KEY_PAIR);

    $stmtCheck = $pdo->prepare("SELECT id_odf, posicion FROM Puerto WHERE id_cable_conectado = ? AND numero_hilo = ? AND id_odf != ?");
    $stmtPto = $pdo->prepare("UPDATE Puerto SET id_cable_conectado = ?, numero_hilo = ? WHERE id_odf = ? AND posicion = ?");

    foreach ($cables as $posicion => $id_cable) {
        $id_cable = !empty($id_cable) ? (int)$id_cable : null;
        $hilo = !empty($hilos[$posicion]) ? (int)$hilos[$posicion] : null;

        if ($id_cable === null) $hilo = null; // Sin cable no puede existir número de hilo

        if ($id_cable !== null && $hilo !== null) {
            $capacidadMax = isset($stmtCap[$id_cable]) ? $stmtCap[$id_cable] : 0;
            if ($hilo > $capacidadMax) {
                throw new Exception("El Hilo $hilo excede la capacidad máxima ($capacidadMax) del cable seleccionado.");
            }

            $stmtCheck->execute([$id_cable, $hilo, $id_odf]);
            $conflict = $stmtCheck->fetch();
            if ($conflict) {
                // Se informa el ID de sistema del ODF (no el índice local) para poder ubicar el conflicto.
                throw new Exception("El Hilo $hilo del cable seleccionado ya está siendo utilizado por otro ODF (ID Sistema: {$conflict['id_odf']}) en su Puerto {$conflict['posicion']}. Dos puertos no pueden empalmar el mismo hilo.");
            }
            
            // Validación local: dentro del mismo ODF un número de hilo no puede repetirse
            foreach ($cables as $p2 => $c2) {
                if ($posicion !== $p2 && !empty($c2) && !empty($hilos[$p2]) && $c2 == $id_cable && $hilos[$p2] == $hilo) {
                    throw new Exception("Estás intentando asignar el mismo Hilo $hilo a los puertos $posicion y $p2 de este mismo ODF. Cada puerto debe tener un hilo único.");
                }
            }
        }

        $stmtPto->execute([$id_cable, $hilo, $id_odf, $posicion]);
    }

    $pdo->commit();
    $_SESSION['msg'] = "Mapeo de hilos guardado correctamente.";
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Error al guardar: " . $e->getMessage();
}

header("Location: ../asignar_hilos.php?id_odf=$id_odf");
exit;
?>
