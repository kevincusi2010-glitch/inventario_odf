<?php

namespace App\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\PuertoBatchImportService;
use App\Contracts\Daos\PuertoDaoInterface;
use App\Contracts\Daos\OdfDaoInterface;
use App\Contracts\Daos\CableDaoInterface;
use App\Policies\AdminPolicy;
use App\Policies\TecnicoPolicy;
use App\Entities\Odf;
use App\Entities\Puerto;
use PDO;
use RuntimeException;

class PuertoBatchImportServiceTest extends TestCase
{
    private PDO $sqlitePdo;

    protected function setUp(): void
    {
        $this->sqlitePdo = new PDO('sqlite::memory:');
    }

    public function testFallaSiOdfNoExiste(): void
    {
        $mockOdfDao = $this->createMock(OdfDaoInterface::class);
        $mockOdfDao->method('findById')->willReturn(null);

        $mockPuertoDao = $this->createMock(PuertoDaoInterface::class);
        $mockCableDao = $this->createMock(CableDaoInterface::class);

        $service = new PuertoBatchImportService($mockPuertoDao, $mockOdfDao, $mockCableDao, $this->sqlitePdo);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("El ODF con ID 999 no existe.");

        $service->import('dummy.csv', 999, 1, new AdminPolicy());
    }

    public function testImportaPlantillaCsvCorrectamenteConAdminPolicy(): void
    {
        $csvPath = dirname(__DIR__, 3) . '/plantillas/plantilla_carga_masiva_puertos.csv';

        $odf = new Odf(10, 6, '0100374_LM_Zapallal', '0100512_LM_Ventanilla_Pachacutec', 24, 'Rack 1');

        $mockOdfDao = $this->createMock(OdfDaoInterface::class);
        $mockOdfDao->method('findById')->with(10)->willReturn($odf);

        $puertosActuales = [
            1 => new Puerto(1, 10, 1, 'Sin Servicio', null, null, null, 0),
            2 => new Puerto(2, 10, 2, 'Sin Servicio', null, null, null, 0),
            3 => new Puerto(3, 10, 3, 'Sin Servicio', null, null, null, 0),
            5 => new Puerto(5, 10, 5, 'Sin Servicio', null, null, null, 0),
        ];

        $mockPuertoDao = $this->createMock(PuertoDaoInterface::class);
        $mockPuertoDao->method('findByOdfId')->with(10)->willReturn($puertosActuales);
        $mockPuertoDao->expects($this->exactly(4))->method('updateEstado')->willReturn(true);
        $mockPuertoDao->expects($this->exactly(4))->method('assignThread')->willReturn(true);

        $cablesSede = [
            [
                'id_cable' => 14,
                'origen' => '0100374_LM_Zapallal',
                'destino' => '0100512_LM_Ventanilla_Pachacutec',
                'capacidad_hilos' => 144
            ]
        ];

        $mockCableDao = $this->createMock(CableDaoInterface::class);
        $mockCableDao->method('findBySiteId')->with(6)->willReturn($cablesSede);

        $service = new PuertoBatchImportService($mockPuertoDao, $mockOdfDao, $mockCableDao, $this->sqlitePdo);

        $result = $service->import($csvPath, 10, 1, new AdminPolicy());

        $this->assertTrue($result['success']);
        $this->assertEquals(4, $result['updated_count']);
    }

    public function testRechazaModificacionSiTecnicoIntentaModificarPuertoEnServicio(): void
    {
        $tmpCsv = sys_get_temp_dir() . '/test_tec_' . uniqid() . '.csv';
        file_put_contents($tmpCsv, "puerto,estado,descripcion,cable_origen,cable_destino,numero_hilo\n1,Sin Servicio,Prueba,,,\n");

        $odf = new Odf(10, 6, 'Origen', 'Destino', 24, 'Rack 1');

        $mockOdfDao = $this->createMock(OdfDaoInterface::class);
        $mockOdfDao->method('findById')->willReturn($odf);

        // Puerto 1 ya está 'En Servicio'
        $puertos = [
            1 => new Puerto(1, 10, 1, 'En Servicio', 'Activo', null, null, 0),
        ];

        $mockPuertoDao = $this->createMock(PuertoDaoInterface::class);
        $mockPuertoDao->method('findByOdfId')->willReturn($puertos);

        $mockCableDao = $this->createMock(CableDaoInterface::class);
        $mockCableDao->method('findBySiteId')->willReturn([]);

        $service = new PuertoBatchImportService($mockPuertoDao, $mockOdfDao, $mockCableDao, $this->sqlitePdo);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("el rol Tecnico no tiene permisos para modificar el puerto 1");

        try {
            $service->import($tmpCsv, 10, 5, new TecnicoPolicy());
        } finally {
            @unlink($tmpCsv);
        }
    }
}
