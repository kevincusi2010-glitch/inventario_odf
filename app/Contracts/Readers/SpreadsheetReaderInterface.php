<?php

namespace App\Contracts\Readers;

/**
 * Contrato polimórfico para lectores de hojas de cálculo (CSV, XLSX, etc.).
 */
interface SpreadsheetReaderInterface
{
    /**
     * Lee un archivo y devuelve una matriz de filas normalizadas.
     *
     * @param string $filePath Ruta absoluta o relativa al archivo en disco.
     * @return array<int, array<int, string>>
     */
    public function read(string $filePath): array;
}
