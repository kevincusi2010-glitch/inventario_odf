<?php

namespace App\Services\Readers;

use App\Contracts\Readers\SpreadsheetReaderInterface;
use RuntimeException;

/**
 * Estrategia de lectura para archivos de texto delimitado (.csv).
 * Soporta auto-detección de delimitadores (, o ;), BOM UTF-8 y codificación ISO-8859-1.
 */
class CsvReader implements SpreadsheetReaderInterface
{
    public function read(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new RuntimeException("El archivo CSV no existe o no tiene permisos de lectura: {$filePath}");
        }

        $contenido = file_get_contents($filePath);
        if ($contenido === false) {
            throw new RuntimeException("Error al leer el contenido del archivo CSV.");
        }

        // Limpieza de Byte Order Mark (BOM) UTF-8
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);

        // Asegurar codificación UTF-8
        if (!mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'ISO-8859-1');
        }

        $lineas = preg_split('/\r\n|\r|\n/', $contenido);
        if (empty($lineas)) {
            return [];
        }

        // Auto-detección del delimitador examinando la primera línea
        $primera = $lineas[0] ?? '';
        $delimitador = (substr_count($primera, ';') > substr_count($primera, ',')) ? ';' : ',';

        $rows = [];
        foreach ($lineas as $linea) {
            if (trim($linea) === '') {
                continue;
            }
            $celdas = str_getcsv($linea, $delimitador);
            $rows[] = array_map(fn($v) => trim((string)$v), $celdas);
        }

        return $rows;
    }
}
