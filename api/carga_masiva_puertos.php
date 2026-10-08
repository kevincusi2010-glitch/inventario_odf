<?php
/**
 * Carga masiva de puertos de un ODF desde un archivo Excel (.xlsx) o CSV.
 *
 * Estructura esperada del archivo (primera hoja, con encabezados):
 *   puerto | estado | descripcion | cable_origen | cable_destino | numero_hilo
 *
 * Reglas:
 *  - puerto y estado son obligatorios.
 *  - Si se indica cable (origen/destino) se requiere numero_hilo y viceversa.
 *  - Celdas vacías limpian la descripción o el empalme del puerto.
 *  - Aplica las mismas reglas de permisos que la edición manual.
 *  - Es "todo o nada": si cualquier fila falla, no se actualiza nada.
 */
session_start();
require_once '../config/database.php';
require_once '../classes/Csrf.php';
require_once '../classes/XlsxReader.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Protección CSRF (el token viaja en el formulario multipart)
Csrf::validateOrDie($_POST['csrf_token'] ?? '');

$id_usuario = $_SESSION['usuario']['id_usuario'];
$rol = $_SESSION['usuario']['rol'];
$ESTADOS = ['Sin Servicio', 'En Servicio', 'Reservado', 'Malogrado'];

$id_odf = (int)($_POST['id_odf'] ?? 0);
if (!$id_odf) {
    http_response_code(400);
    echo json_encode(['error' => 'ODF no especificado.']);
    exit;
}

if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibió el archivo o falló la subida.']);
    exit;
}

$archivo = $_FILES['archivo'];
if ($archivo['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'El archivo supera el límite de 5 MB.']);
    exit;
}

$ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['xlsx', 'csv'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato no permitido. Use un archivo .xlsx o .csv']);
    exit;
}

/** Lee un CSV (detecta delimitador , o ;) y devuelve filas normalizadas. */
function leerCsv($ruta) {
    $contenido = file_get_contents($ruta);
    $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido); // BOM UTF-8
    if (!mb_check_encoding($contenido, 'UTF-8')) {
        $contenido = mb_convert_encoding($contenido, 'UTF-8', 'ISO-8859-1');
    }
    $lineas = preg_split('/\r\n|\r|\n/', $contenido);
    $primera = $lineas[0] ?? '';
    $delimitador = (substr_count($primera, ';') > substr_count($primera, ',')) ? ';' : ',';
    $rows = [];
    foreach ($lineas as $linea) {
        if (trim($linea) === '') continue;
        $rows[] = array_map(function ($v) { return trim($v); }, str_getcsv($linea, $delimitador));
    }
    return $rows;
}

try {
    $pdo = Database::getInstance()->getConnection();

    // Datos del ODF y su sede
    $stmt = $pdo->prepare("SELECT o.id_odf, o.capacidad_puertos, o.id_site FROM ODF o WHERE o.id_odf = ?");
    $stmt->execute([$id_odf]);
    $odf = $stmt->fetch();
    if (!$odf) {
        throw new Exception("El ODF indicado no existe.");
    }
    $capacidadOdf = (int)$odf['capacidad_puertos'];
    $id_site = (int)$odf['id_site'];

    // Cables de la sede (índices por par origen|destino, por origen y por destino)
    $stmt = $pdo->prepare("SELECT id_cable, origen, destino, capacidad_hilos FROM CableAlimentacion WHERE id_site = ? AND activo = 1");
    $stmt->execute([$id_site]);
    $cablesPorPar = [];
    $cablesPorOrigen = [];
    $cablesPorDestino = [];
    foreach ($stmt->fetchAll() as $c) {
        $o = mb_strtolower(trim((string)$c['origen']));
        $d = mb_strtolower(trim((string)$c['destino']));
        $cablesPorPar[$o . '||' . $d] = $c;
        $cablesPorOrigen[$o][] = $c;
        if ($d !== '') { $cablesPorDestino[$d][] = $c; }
    }

    // Leer filas del archivo
    if ($ext === 'csv') {
        $rows = leerCsv($archivo['tmp_name']);
    } else {
        $rows = XlsxReader::read($archivo['tmp_name']);
    }
    if (count($rows) < 2) {
        throw new Exception("El archivo no contiene filas de datos (solo encabezados o está vacío).");
    }

    // Mapear encabezados
    $map = [];
    foreach ($rows[0] as $i => $h) {
        $clave = mb_strtolower(trim((string)$h));
        if ($clave !== '') { $map[$clave] = $i; }
    }
    foreach (['puerto', 'estado'] as $req) {
        if (!isset($map[$req])) {
            throw new Exception("Falta la columna obligatoria '$req' en el encabezado.");
        }
    }

    // Puertos actuales del ODF (indexados por posición)
    $stmt = $pdo->prepare("SELECT * FROM Puerto WHERE id_odf = ?");
    $stmt->execute([$id_odf]);
    $puertos = [];
    foreach ($stmt->fetchAll() as $p) {
        $puertos[(int)$p['posicion']] = $p;
    }

    $errores = [];
    $duplicadosArchivo = [];
    $updates = [];
    $filasDatos = 0;

    for ($i = 1; $i < count($rows); $i++) {
        $fila = $rows[$i];
        $numFila = $i + 1; // número de fila real en Excel

        // Saltar filas completamente vacías
        $hayDatos = false;
        foreach ($fila as $v) { if (trim((string)$v) !== '') { $hayDatos = true; break; } }
        if (!$hayDatos) continue;
        $filasDatos++;

        $get = function ($nombre) use ($fila, $map) {
            $idx = $map[$nombre] ?? null;
            return ($idx === null || !isset($fila[$idx])) ? '' : trim((string)$fila[$idx]);
        };

        // --- puerto ---
        $puertoNum = $get('puerto');
        if ($puertoNum === '' || !ctype_digit($puertoNum)) {
            $errores[] = "Fila $numFila: la columna 'puerto' debe ser un número (valor: '$puertoNum').";
            continue;
        }
        $puertoNum = (int)$puertoNum;
        if ($puertoNum < 1 || $puertoNum > $capacidadOdf) {
            $errores[] = "Fila $numFila: el puerto $puertoNum está fuera de rango (1 a $capacidadOdf).";
            continue;
        }
        if (!isset($puertos[$puertoNum])) {
            $errores[] = "Fila $numFila: el puerto $puertoNum no existe en este ODF.";
            continue;
        }

        // --- estado ---
        $estado = $get('estado');
        if (!in_array($estado, $ESTADOS, true)) {
            $errores[] = "Fila $numFila: estado inválido '$estado'. Valores permitidos: " . implode(', ', $ESTADOS) . ".";
            continue;
        }

        // --- descripcion ---
        $descripcion = $get('descripcion');
        if (mb_strlen($descripcion) > 255) {
            $errores[] = "Fila $numFila: la descripción supera los 255 caracteres.";
            continue;
        }

        // --- cable (origen/destino) ---
        $origen = $get('cable_origen');
        $destino = $get('cable_destino');
        $cable = null;
        if ($origen !== '' && $destino !== '') {
            $par = mb_strtolower($origen) . '||' . mb_strtolower($destino);
            if (!isset($cablesPorPar[$par])) {
                $errores[] = "Fila $numFila: no existe un cable '$origen' → '$destino' en la sede de este ODF.";
                continue;
            }
            $cable = $cablesPorPar[$par];
        } elseif ($origen !== '' || $destino !== '') {
            $candidatos = $origen !== ''
                ? ($cablesPorOrigen[mb_strtolower($origen)] ?? [])
                : ($cablesPorDestino[mb_strtolower($destino)] ?? []);
            if (count($candidatos) === 0) {
                $errores[] = "Fila $numFila: no existe un cable que coincida con los datos indicados en esta sede.";
                continue;
            }
            if (count($candidatos) > 1) {
                $errores[] = "Fila $numFila: hay varios cables que coinciden; indique cable_origen y cable_destino completos.";
                continue;
            }
            $cable = $candidatos[0];
        }

        // --- numero_hilo ---
        $hiloTxt = $get('numero_hilo');
        if ($cable) {
            if ($hiloTxt === '' || !ctype_digit($hiloTxt)) {
                $errores[] = "Fila $numFila: si indica el cable debe indicar también 'numero_hilo'.";
                continue;
            }
            $hilo = (int)$hiloTxt;
            if ($hilo < 1 || $hilo > (int)$cable['capacidad_hilos']) {
                $errores[] = "Fila $numFila: el hilo $hilo excede la capacidad ({$cable['capacidad_hilos']}) del cable.";
                continue;
            }
            $claveHilo = $cable['id_cable'] . '|' . $hilo;
            if (isset($duplicadosArchivo[$claveHilo])) {
                $errores[] = "Fila $numFila: el hilo $hilo de ese cable ya se usa en la fila {$duplicadosArchivo[$claveHilo]} del archivo.";
                continue;
            }
            // Conflicto con puertos de otros ODFs
            $stmtC = $pdo->prepare("SELECT posicion, id_odf FROM Puerto WHERE id_cable_conectado = ? AND numero_hilo = ? AND id_odf != ?");
            $stmtC->execute([$cable['id_cable'], $hilo, $id_odf]);
            $conflicto = $stmtC->fetch();
            if ($conflicto) {
                $errores[] = "Fila $numFila: el hilo $hilo de ese cable ya lo usa el puerto {$conflicto['posicion']} de otro ODF (ID {$conflicto['id_odf']}).";
                continue;
            }
            $duplicadosArchivo[$claveHilo] = $numFila;
        } else {
            if ($hiloTxt !== '') {
                $errores[] = "Fila $numFila: si indica 'numero_hilo' debe indicar también el cable (cable_origen/cable_destino).";
                continue;
            }
            $hilo = null;
        }

        // --- Reglas de permisos (mismas que la edición manual) ---
        $puerto = $puertos[$puertoNum];
        if ($puerto['bloqueado'] == 1 && $rol !== 'Administrador') {
            $errores[] = "Fila $numFila: el puerto $puertoNum está bloqueado y solo un Administrador puede modificarlo.";
            continue;
        }
        if ($rol === 'Tecnico' && $puerto['estado'] === 'En Servicio') {
            $errores[] = "Fila $numFila: los técnicos no pueden modificar el puerto $puertoNum porque está 'En Servicio'.";
            continue;
        }

        $bloqueado = $puerto['bloqueado'];
        if ($estado === 'Malogrado') {
            $bloqueado = 1;
        } else if ($rol === 'Administrador') {
            $bloqueado = 0;
        }

        $updates[] = [
            'id_puerto'   => $puerto['id_puerto'],
            'estado'      => $estado,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'id_cable'    => $cable ? $cable['id_cable'] : null,
            'hilo'        => $hilo,
            'bloqueado'   => $bloqueado,
        ];
    }

    if ($filasDatos === 0) {
        throw new Exception("No se encontraron filas de datos en el archivo.");
    }
    if (count($errores) > 0) {
        $resumen = implode(' | ', array_slice($errores, 0, 10));
        if (count($errores) > 10) {
            $resumen .= ' (+' . (count($errores) - 10) . ' errores más)';
        }
        throw new Exception("El archivo tiene errores, no se aplicó ningún cambio: " . $resumen);
    }

    // Aplicar todo en una transacción (todo o nada)
    $pdo->beginTransaction();
    $stmtU = $pdo->prepare("UPDATE Puerto SET estado = ?, descripcion = ?, id_cable_conectado = ?, numero_hilo = ?, bloqueado = ?, modificado_por = ?, ultima_modificacion = NOW() WHERE id_puerto = ?");
    foreach ($updates as $u) {
        $stmtU->execute([$u['estado'], $u['descripcion'], $u['id_cable'], $u['hilo'], $u['bloqueado'], $id_usuario, $u['id_puerto']]);
    }
    $pdo->commit();

    echo json_encode(['success' => true, 'mensaje' => "Carga masiva aplicada: $filasDatos puerto(s) actualizado(s)."], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
