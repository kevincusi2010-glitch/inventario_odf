<?php

namespace App\Services\Readers;

use App\Contracts\Readers\SpreadsheetReaderInterface;
use RuntimeException;
use ZipArchive;

/**
 * Estrategia de lectura ligera para libros OpenXML Excel (.xlsx).
 * Utiliza exclusivamente las extensiones nativas zip y SimpleXML sin dependencias de terceros.
 */
class XlsxReader implements SpreadsheetReaderInterface
{
    public function read(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new RuntimeException("El archivo Excel no existe o no tiene permisos de lectura: {$filePath}");
        }

        $zip = new ZipArchive();
        $code = $zip->open($filePath);
        if ($code !== true) {
            throw new RuntimeException("No se pudo abrir el archivo Excel (código {$code}). ¿Es un .xlsx válido?");
        }

        $shared = self::extractSharedStrings($zip);
        $hojas = self::extractSheetPaths($zip);

        if (empty($hojas)) {
            $zip->close();
            throw new RuntimeException("El archivo Excel no contiene hojas de cálculo.");
        }

        $rows = self::extractRowsFromSheet($zip, $hojas[0]['path'], $shared);
        $zip->close();

        return $rows;
    }

    private static function extractSharedStrings(ZipArchive $zip): array
    {
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $ss = simplexml_load_string($ssXml);
            if ($ss !== false) {
                foreach ($ss->si as $si) {
                    $texto = '';
                    if (isset($si->t)) {
                        $texto = (string)$si->t;
                    } else {
                        foreach ($si->r as $run) {
                            $texto .= (string)$run->t;
                        }
                    }
                    $sharedStrings[] = $texto;
                }
            }
        }
        return $sharedStrings;
    }

    private static function extractSheetPaths(ZipArchive $zip): array
    {
        $wbXml = $zip->getFromName('xl/workbook.xml');
        if ($wbXml === false) {
            throw new RuntimeException("El archivo no tiene la estructura de un .xlsx válido.");
        }
        $wb = simplexml_load_string($wbXml);

        $rels = [];
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relsXml !== false) {
            foreach (simplexml_load_string($relsXml)->Relationship as $rel) {
                $rels[(string)$rel['Id']] = (string)$rel['Target'];
            }
        }

        $hojas = [];
        foreach ($wb->sheets->sheet as $sheet) {
            $rid = (string)$sheet->attributes('r', true)->id;
            $target = $rels[$rid] ?? '';
            if ($target === '') {
                $path = 'xl/worksheets/sheet' . (count($hojas) + 1) . '.xml';
            } elseif ($target[0] === '/') {
                $path = ltrim($target, '/');
            } else {
                $path = 'xl/' . $target;
            }
            $hojas[] = ['nombre' => (string)$sheet['name'], 'path' => $path];
        }

        if (count($hojas) === 0) {
            $hojas[] = ['nombre' => 'Hoja1', 'path' => 'xl/worksheets/sheet1.xml'];
        }

        return $hojas;
    }

    private static function extractRowsFromSheet(ZipArchive $zip, string $sheetPath, array $sharedStrings): array
    {
        $sheetXml = $zip->getFromName($sheetPath);
        if ($sheetXml === false) {
            throw new RuntimeException("El archivo Excel no contiene la hoja de cálculo esperada ({$sheetPath}).");
        }

        $sheet = simplexml_load_string($sheetXml);
        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $colIndex = self::columnIndex((string)$cell['r']);
                $type = (string)$cell['t'];
                $value = '';
                if ($type === 's') {
                    $idx = (int)(string)$cell->v;
                    $value = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = isset($cell->is->t) ? (string)$cell->is->t : '';
                } elseif (isset($cell->v)) {
                    $value = (string)$cell->v;
                }
                $rowData[$colIndex] = trim($value);
            }
            
            // Normalizar celdas vacías intermedias
            $max = $rowData ? max(array_keys($rowData)) : -1;
            $normalizada = [];
            for ($i = 0; $i <= $max; $i++) {
                $normalizada[$i] = $rowData[$i] ?? '';
            }
            $rows[] = $normalizada;
        }
        return $rows;
    }

    private static function columnIndex(string $ref): int
    {
        $letras = preg_replace('/[^A-Z]/', '', strtoupper($ref));
        $indice = 0;
        $len = strlen($letras);
        for ($i = 0; $i < $len; $i++) {
            $indice = $indice * 26 + (ord($letras[$i]) - 64);
        }
        return $indice - 1;
    }
}
