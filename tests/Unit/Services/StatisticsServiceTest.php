<?php

namespace App\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\StatisticsService;
use PDO;

class StatisticsServiceTest extends TestCase
{
    private PDO $sqlitePdo;
    private StatisticsService $service;

    protected function setUp(): void
    {
        $this->sqlitePdo = new PDO('sqlite::memory:');
        $this->sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Crear tablas simuladas en SQLite para testear agregaciones
        $this->sqlitePdo->exec("
            CREATE TABLE Site (id_site INTEGER PRIMARY KEY, nombre TEXT, departamento TEXT, activo INTEGER);
            CREATE TABLE ODF (id_odf INTEGER PRIMARY KEY, id_site INTEGER, origen TEXT, destino TEXT, ubicacion_rack TEXT, activo INTEGER);
            CREATE TABLE CableAlimentacion (id_cable INTEGER PRIMARY KEY, id_site INTEGER, capacidad_hilos INTEGER, activo INTEGER);
            CREATE TABLE Puerto (id_puerto INTEGER PRIMARY KEY, id_odf INTEGER, estado TEXT, id_cable_conectado INTEGER);
        ");

        $this->sqlitePdo->exec("
            INSERT INTO Site (id_site, nombre, departamento, activo) VALUES (1, 'Central Test', 'LIMA', 1);
            INSERT INTO ODF (id_odf, id_site, origen, destino, ubicacion_rack, activo) VALUES (10, 1, 'Orig', 'Dest', 'Rack 1', 1);
            INSERT INTO CableAlimentacion (id_cable, id_site, capacidad_hilos, activo) VALUES (100, 1, 48, 1);
            INSERT INTO Puerto (id_puerto, id_odf, estado, id_cable_conectado) VALUES (1, 10, 'En Servicio', 100);
            INSERT INTO Puerto (id_puerto, id_odf, estado, id_cable_conectado) VALUES (2, 10, 'Sin Servicio', 100);
        ");

        $this->service = new StatisticsService($this->sqlitePdo);
    }

    public function testGetSiteListRetornaSedesActivas(): void
    {
        $sites = $this->service->getSiteList();
        $this->assertCount(1, $sites);
        $this->assertEquals('Central Test', $sites[0]['nombre']);
    }

    public function testGetOdfsForSiteRetornaOdfsDeLaSede(): void
    {
        $odfs = $this->service->getOdfsForSite(1);
        $this->assertCount(1, $odfs);
        $this->assertEquals(10, $odfs[0]['id_odf']);
    }

    public function testCalculateStatsCalculaTotalesYEstadosCorrectamente(): void
    {
        $stats = $this->service->calculateStats(1);

        $this->assertEquals(1, $stats['total_cables']);
        $this->assertEquals(1, $stats['total_odfs']);
        $this->assertEquals(48, $stats['capacidad_hilos']);
        $this->assertEquals(2, $stats['puertos_totales']);
        $this->assertEquals(1, $stats['estado_ocupado']);
        $this->assertEquals(1, $stats['estado_libre']);
    }
}
