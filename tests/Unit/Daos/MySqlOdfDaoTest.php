<?php

namespace App\Tests\Unit\Daos;

use PHPUnit\Framework\TestCase;
use App\Daos\MySqlOdfDao;
use App\Entities\Odf;
use PDO;
use PDOStatement;

class MySqlOdfDaoTest extends TestCase
{
    public function testFindByIdDevuelveEntidadOdfMapeada(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->expects($this->once())
            ->method('execute')
            ->with([5]);
        $mockStmt->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'id_odf' => 5,
                'id_site' => 1,
                'origen' => 'LIMA',
                'destino' => 'CHORRILLOS',
                'capacidad_puertos' => 48,
                'ubicacion_rack' => 'Rack A',
                'activo' => 1,
                'creado_por' => 3,
                'creado_en' => '2026-10-08 10:00:00',
            ]);

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM ODF WHERE id_odf = ?'))
            ->willReturn($mockStmt);

        $dao = new MySqlOdfDao($mockPdo);
        $odf = $dao->findById(5);

        $this->assertInstanceOf(Odf::class, $odf);
        $this->assertEquals(5, $odf->idOdf);
        $this->assertEquals(1, $odf->idSite);
        $this->assertEquals('LIMA', $odf->origen);
        $this->assertEquals('CHORRILLOS', $odf->destino);
        $this->assertEquals(48, $odf->capacidadPuertos);
        $this->assertEquals('Rack A', $odf->ubicacionRack);
        $this->assertEquals(3, $odf->creadoPor);
    }

    public function testFindByIdDevuelveNullSiNoExiste(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('fetch')->willReturn(false);

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('prepare')->willReturn($mockStmt);

        $dao = new MySqlOdfDao($mockPdo);
        $this->assertNull($dao->findById(999));
    }

    public function testCreateInsertaYDevuelveUltimoId(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->expects($this->once())
            ->method('execute')
            ->with([
                1,
                'SURCO',
                'BARRANCO',
                72,
                'Rack B',
                4
            ])
            ->willReturn(true);

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('prepare')->willReturn($mockStmt);
        $mockPdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('15');

        $dao = new MySqlOdfDao($mockPdo);
        $odf = new Odf(
            null,
            1,
            'SURCO',
            'BARRANCO',
            72,
            'Rack B',
            1,
            4
        );

        $newId = $dao->create($odf);
        $this->assertEquals(15, $newId);
    }
}
