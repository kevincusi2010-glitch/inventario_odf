<?php

namespace App\Tests\Unit\Services\Readers;

use PHPUnit\Framework\TestCase;
use App\Services\Readers\CsvReader;
use App\Services\Readers\XlsxReader;
use App\Services\Readers\ReaderFactory;

class ReaderTest extends TestCase
{
    public function testCsvReaderLeePlantillaCorrectamente(): void
    {
        $csvPath = dirname(__DIR__, 4) . '/plantillas/plantilla_carga_masiva_puertos.csv';
        $this->assertFileExists($csvPath);

        $reader = new CsvReader();
        $rows = $reader->read($csvPath);

        $this->assertCount(5, $rows);
        $this->assertEquals(['puerto', 'estado', 'descripcion', 'cable_origen', 'cable_destino', 'numero_hilo'], $rows[0]);
        $this->assertEquals('1', $rows[1][0]);
        $this->assertEquals('En Servicio', $rows[1][1]);
        $this->assertEquals('Servicio EMPRESA A', $rows[1][2]);
    }

    public function testReaderFactoryCreaEstrategiaSegunExtension(): void
    {
        $csvReader = ReaderFactory::createForExtension('csv');
        $this->assertInstanceOf(CsvReader::class, $csvReader);

        $xlsxReader = ReaderFactory::createForExtension('xlsx');
        $this->assertInstanceOf(XlsxReader::class, $xlsxReader);
    }
}
