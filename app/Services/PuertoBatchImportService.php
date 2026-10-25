<?php

namespace App\Services;

use App\Contracts\Daos\CableDaoInterface;
use App\Contracts\Daos\OdfDaoInterface;
use App\Contracts\Daos\PuertoDaoInterface;
use App\Contracts\Policies\RolePolicyInterface;
use App\Core\DatabaseConnection;
use App\Entities\Puerto;
use App\Services\Readers\ReaderFactory;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * Servicio de Dominio: Carga e importación masiva de puertos desde hojas de cálculo.
 *
 * Aplica el patrón Strategy para admitir múltiples formatos (CSV/XLSX),
 * valida permisos con políticas polimórficas y aplica transacciones atómicas "todo o nada".
 */
class PuertoBatchImportService
{
    public function __construct(
        private PuertoDaoInterface $puertoDao,
        private OdfDaoInterface $odfDao,
        private CableDaoInterface $cableDao,
        private PDO $pdo
    ) {}

    /**
     * Procesa e importa por lote un archivo de puertos (CSV/XLSX) para un ODF determinado.
     *
     * Aplica el patrón Strategy para la deserialización del archivo tabular,
     * valida reglas de negocio (rango de puertos, unicidad de hilos y permisos RBAC)
     * y ejecuta las actualizaciones de forma atómica bajo una transacción ACID.
     *
     * @param string              $filePath  Ruta absoluta del archivo temporal subido.
     * @param int                 $odfId     Identificador del distribuidor ODF destino.
     * @param int                 $userId    ID del operador que ejecuta la importación.
     * @param RolePolicyInterface $policy    Política de autorización del rol en sesión.
     * @return array{success: bool, updated_count: int, message: string} Resumen del proceso de importación.
     * @throws InvalidArgumentException Si el ODF no existe o el formato de archivo es inválido.
     * @throws RuntimeException          Si el archivo tiene errores estructurales o colisiones ópticas.
     */
    public function import(string $filePath, int $odfId, int $userId, RolePolicyInterface $policy): array
    {
        $odf = $this->odfDao->findById($odfId);
        if (!$odf) {
            throw new InvalidArgumentException("El ODF con ID {$odfId} no existe.");
        }

        $reader = ReaderFactory::createForFile($filePath);
        $rows = $reader->read($filePath);

        if (count($rows) < 2) {
            throw new RuntimeException("El archivo no contiene filas de datos (solo encabezados o está vacío).");
        }

        // Mapear encabezados
        $headerMap = [];
        foreach ($rows[0] as $i => $h) {
            $clave = mb_strtolower(trim((string)$h));
            if ($clave !== '') {
                $headerMap[$clave] = $i;
            }
        }

        foreach (['puerto', 'estado'] as $requiredCol) {
            if (!isset($headerMap[$requiredCol])) {
                throw new RuntimeException("Falta la columna obligatoria '{$requiredCol}' en el encabezado del archivo.");
            }
        }

        // Obtener puertos actuales y cables de la sede
        $puertosActuales = [];
        foreach ($this->puertoDao->findByOdfId($odfId) as $p) {
            $puertosActuales[$p->posicion] = $p;
        }

        $cablesSede = $this->cableDao->findBySiteId($odf->idSite);
        $cablesPorPar = [];
        foreach ($cablesSede as $c) {
            $o = mb_strtolower(trim((string)$c['origen']));
            $d = mb_strtolower(trim((string)$c['destino']));
            $cablesPorPar[$o . '||' . $d] = $c;
        }

        $errores = [];
        $updates = [];
        $filasDatos = 0;

        for ($i = 1; $i < count($rows); $i++) {
            $fila = $rows[$i];
            $numFila = $i + 1;

            // Ignorar filas totalmente vacías
            $hayDatos = false;
            foreach ($fila as $cell) {
                if (trim((string)$cell) !== '') {
                    $hayDatos = true;
                    break;
                }
            }
            if (!$hayDatos) {
                continue;
            }
            $filasDatos++;

            $getVal = function (string $col) use ($fila, $headerMap): string {
                $idx = $headerMap[$col] ?? null;
                return ($idx === null || !isset($fila[$idx])) ? '' : trim((string)$fila[$idx]);
            };

            // 1. Validar puerto
            $puertoNumStr = $getVal('puerto');
            if ($puertoNumStr === '' || !ctype_digit($puertoNumStr)) {
                $errores[] = "Fila {$numFila}: la columna 'puerto' debe ser un número entero (recibido: '{$puertoNumStr}').";
                continue;
            }

            $posicion = (int)$puertoNumStr;
            if ($posicion < 1 || $posicion > $odf->capacidadPuertos) {
                $errores[] = "Fila {$numFila}: el puerto {$posicion} está fuera del rango del ODF (1 a {$odf->capacidadPuertos}).";
                continue;
            }

            if (!isset($puertosActuales[$posicion])) {
                $errores[] = "Fila {$numFila}: el puerto {$posicion} no existe registrado en este ODF.";
                continue;
            }

            // 2. Validar estado
            $estado = $getVal('estado');
            if (!in_array($estado, Puerto::ESTADOS_VALIDOS, true)) {
                $errores[] = "Fila {$numFila}: estado '{$estado}' inválido. Permitidos: " . implode(', ', Puerto::ESTADOS_VALIDOS) . ".";
                continue;
            }

            $descripcion = $getVal('descripcion');
            $cableOrigen = mb_strtolower($getVal('cable_origen'));
            $cableDestino = mb_strtolower($getVal('cable_destino'));
            $hiloStr = $getVal('numero_hilo');

            // 3. Validar empalme de cable e hilo
            $cableId = null;
            $hiloNum = null;

            if ($cableOrigen !== '' || $cableDestino !== '') {
                $clavePar = $cableOrigen . '||' . $cableDestino;
                if (!isset($cablesPorPar[$clavePar])) {
                    $errores[] = "Fila {$numFila}: no se encontró un cable con origen '{$cableOrigen}' y destino '{$cableDestino}' en esta sede.";
                    continue;
                }
                $cableId = (int)$cablesPorPar[$clavePar]['id_cable'];

                if ($hiloStr === '' || !ctype_digit($hiloStr)) {
                    $errores[] = "Fila {$numFila}: se especificó cable pero falta un 'numero_hilo' válido.";
                    continue;
                }
                $hiloNum = (int)$hiloStr;
                $capacidadCable = (int)$cablesPorPar[$clavePar]['capacidad_hilos'];
                if ($hiloNum < 1 || $hiloNum > $capacidadCable) {
                    $errores[] = "Fila {$numFila}: el hilo {$hiloNum} excede la capacidad del cable (1 a {$capacidadCable}).";
                    continue;
                }
            } elseif ($hiloStr !== '') {
                $errores[] = "Fila {$numFila}: se indicó 'numero_hilo' pero falta especificar el cable.";
                continue;
            }

            // 4. Validar permisos polimórficos de rol
            $puertoActual = $puertosActuales[$posicion];
            if (!$policy->canModifyPort($puertoActual->toArray())) {
                $errores[] = "Fila {$numFila}: el rol {$policy->getRoleName()} no tiene permisos para modificar el puerto {$posicion} (bloqueado o en servicio).";
                continue;
            }

            $bloqueado = $puertoActual->bloqueado;
            if ($estado === 'Malogrado') {
                $bloqueado = 1;
            } elseif ($policy->getRoleName() === 'Administrador') {
                $bloqueado = 0;
            }

            $updates[] = [
                'id_puerto' => $puertoActual->idPuerto,
                'posicion' => $posicion,
                'estado' => $estado,
                'descripcion' => $descripcion !== '' ? $descripcion : null,
                'id_cable' => $cableId,
                'numero_hilo' => $hiloNum,
                'bloqueado' => $bloqueado,
            ];
        }

        if ($filasDatos === 0) {
            throw new RuntimeException("No se encontraron filas con datos en el archivo cargado.");
        }

        if (!empty($errores)) {
            $resumen = implode(' | ', array_slice($errores, 0, 8));
            if (count($errores) > 8) {
                $resumen .= ' (+' . (count($errores) - 8) . ' errores adicionales)';
            }
            throw new RuntimeException("El archivo contiene errores; ningún cambio fue aplicado: " . $resumen);
        }

        // Aplicar todas las actualizaciones en una sola transacción atómica
        DatabaseConnection::transaction($this->pdo, function () use ($updates, $userId, $odfId) {
            foreach ($updates as $u) {
                $this->puertoDao->updateEstado($u['id_puerto'], $u['estado'], $u['descripcion'], $userId, $u['bloqueado']);
                // assignThread() espera (id_odf, posicion); pasar id_puerto hacía que
                // el UPDATE no encontrara ninguna fila y el hilo nunca se empalmara.
                $this->puertoDao->assignThread($odfId, $u['posicion'], $u['id_cable'], $u['numero_hilo']);
            }
        });

        return [
            'success' => true,
            'updated_count' => count($updates),
            'message' => "Carga masiva aplicada con éxito: " . count($updates) . " puerto(s) actualizado(s).",
        ];
    }
}
