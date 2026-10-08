<?php

namespace App\Services\Readers;

use App\Contracts\Readers\SpreadsheetReaderInterface;
use InvalidArgumentException;

/**
 * Fábrica de estrategias de lectura de hojas de cálculo.
 */
class ReaderFactory
{
    public static function createForExtension(string $extension): SpreadsheetReaderInterface
    {
        $ext = strtolower(trim($extension));

        return match ($ext) {
            'csv' => new CsvReader(),
            'xlsx' => new XlsxReader(),
            default => throw new InvalidArgumentException("Formato no soportado: '{$extension}'. Use .csv o .xlsx"),
        };
    }

    public static function createForFile(string $filePath): SpreadsheetReaderInterface
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        return self::createForExtension($extension);
    }
}
