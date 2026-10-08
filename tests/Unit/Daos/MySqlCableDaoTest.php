<?php

namespace App\Tests\Unit\Daos;

use PHPUnit\Framework\TestCase;
use App\Daos\MySqlCableDao;
use App\Entities\CableAlimentacion;
use PDO;
use PDOStatement;

class MySqlCableDaoTest extends TestCase
{
    public function testFindByIdDevuelveEntidadCableMapeada(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->expects($this->once())
            ->method('execute')
            ->with([101]);
        $mockStmt->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'id_cable' => 101,
                'id_site' => 2,
                'origen' => 'Sede Central',
                'destino' => 'Sede Norte',
                'capacidad_hilos' => 24,
                'rack' => 'Rack Principal',
                'fecha_instalacion' => '2026-01-15',
                'activo' => 1,
                'creado_por' => 1,
                'creado_en' => '2026-10-08 11:00:00',
            ]);

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM CableAlimentacion WHERE id_cable = ?'))
            ->willReturn($mockStmt);

        $dao = new MySqlCableDao($mockPdo);
        $cable = $dao->findById(101);

        $this->assertInstanceOf(CableAlimentacion::class, $cable);
        $this->assertEquals(101, $cable->idCable);
        $this->assertEquals(24, $cable->capacidadHilos);
        $this->assertEquals('Sede Central', $cable->origen);
        $this->assertEquals('Rack Principal', $cable->rack);
        $this->assertEquals(1, $cable->creadoPor);
    }

    public function testFindCapacidadesMapDevuelveArregloIdCapacidad(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_KEY_PAIR)
            ->willReturn([
                1 => 24,
                2 => 48,
                3 => 96
            ]);

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->expects($this->once())
            ->method('query')
            ->willReturn($mockStmt);

        $dao = new MySqlCableDao($mockPdo);
        $map = $dao->findCapacidadesMap();

        $this->assertEquals([1 => 24, 2 => 48, 3 => 96], $map);
        $this->assertEquals(48, $map[2]);
    }

    public function testCreateInsertaYDevuelveUltimoId(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->expects($this->once())
            ->method('execute')
            ->with([
                2,
                'Sede A',
                'Sede B',
                48,
                'Rack 01',
                '2026-05-10',
                1
            ])
            ->willReturn(true);

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('prepare')->willReturn($mockStmt);
        $mockPdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('55');

        $dao = new MySqlCableDao($mockPdo);
        $cable = new CableAlimentacion(
            null,
            2,
            'Sede A',
            'Sede B',
            48,
            'Rack 01',
            '2026-05-10',
            1,
            1
        );

        $newId = $dao->create($cable);
        $this->assertEquals(55, $newId);
    }
}
