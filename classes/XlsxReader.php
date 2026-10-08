<?php
/**
 * Lector ligero de archivos .xlsx (Excel) sin dependencias externas.
 * Usa las extensiones zip y SimpleXML (incluidas en XAMPP).
 *
 *  - read()    : lee SOLO la primera hoja del libro (comportamiento original).
 *  - readAll() : lee TODAS las hojas del libro (por ejemplo el "CUADRO DE HILOS",
 *                que trae una hoja por ODF) y salta los encabezados repetidos.
 */
class XlsxReader {

    public static function read($filePath) {
        $zip = self::open($filePath);
        $shared = self::sharedStrings($zip);
        $hojas = self::sheetPaths($zip);
        $rows = self::rowsFromSheet($zip, $hojas[0]['path'], $shared);
        $zip->close();
        return $rows;
    }

    public static function readAll($filePath) {
        $zip = self::open($filePath);
        $shared = self::sharedStrings($zip);
        $hojas = self::sheetPaths($zip);
        $todas = [];
        $encabezado = null;
        foreach ($hojas as $hoja) {
            foreach (self::rowsFromSheet($zip, $hoja['path'], $shared) as $fila) {
                $plano = implode('|', array_map('trim', $fila));
                if ($plano === '' || trim($plano, '|') === '') { continue; } // fila vacía
                if ($encabezado === null) { $encabezado = $plano; }
                elseif ($plano === $encabezado) { continue; }                // encabezado repetido en otra hoja
                $todas[] = $fila;
            }
        }
        $zip->close();
        return $todas;
    }

    private static function open($filePath) {
        $zip = new ZipArchive();
        $code = $zip->open($filePath);
        if ($code !== true) {
            throw new Exception("No se pudo abrir el archivo Excel (código $code). ¿Es un .xlsx válido?");
        }
        return $zip;
    }

    /** Tabla de cadenas compartidas del libro. */
    private static function sharedStrings($zip) {
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $ss = simplexml_load_string($ssXml);
            if ($ss !== false) {
                foreach ($ss->si as $si) {
                    $texto = '';
                    if (isset($si->t)) {
                        // <si><t>texto</t></si>
                        $texto = (string)$si->t;
                    } else {
                        // Texto enriquecido: <si><r><t>..</t></r><r>..</r></si>
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

    /** Devuelve [nombre, ruta interna] de cada hoja del libro. */
    private static function sheetPaths($zip) {
        $wbXml = $zip->getFromName('xl/workbook.xml');
        if ($wbXml === false) {
            throw new Exception("El archivo no tiene la estructura de un .xlsx válido.");
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

    /** Lee las filas de una hoja concreta. */
    private static function rowsFromSheet($zip, $sheetPath, $sharedStrings) {
        $sheetXml = $zip->getFromName($sheetPath);
        if ($sheetXml === false) {
            throw new Exception("El archivo Excel no contiene la hoja de cálculo esperada ($sheetPath).");
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
            // Normalizar índices faltantes (celdas vacías)
            $max = $rowData ? max(array_keys($rowData)) : -1;
            $normalizada = [];
            for ($i = 0; $i <= $max; $i++) {
                $normalizada[$i] = isset($rowData[$i]) ? $rowData[$i] : '';
            }
            $rows[] = $normalizada;
        }
        return $rows;
    }

    /** Convierte la referencia de una celda (ej. 'C5') a índice de columna base 0. */
    private static function columnIndex($ref) {
        $letras = preg_replace('/[^A-Z]/', '', strtoupper($ref));
        $indice = 0;
        $len = strlen($letras);
        for ($i = 0; $i < $len; $i++) {
            $indice = $indice * 26 + (ord($letras[$i]) - 64);
        }
        return $indice - 1;
    }
}
?>
