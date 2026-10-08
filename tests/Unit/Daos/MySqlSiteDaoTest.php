<?php

namespace App\Tests\Unit\Daos;

use PHPUnit\Framework\TestCase;
use App\Daos\MySqlSiteDao;
use App\Entities\Site;
use PDO;
use PDOStatement;

class MySqlSiteDaoTest extends TestCase
{
    public function testFindByIdDevuelveEntidadSiteMapeada(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->expects($this->once())
            ->method('execute')
            ->with([10]);
        $mockStmt->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'id_site' => 10,
                'nombre' => 'Central Chorrillos',
                'direccion' => 'Av. Defensores 123',
                'departamento' => 'LIMA',
                'distrito' => 'CHORRILLOS',
                'tipo' => 'AGG',
                'activo' => 1,
                'creado_por' => 2,
                'creado_en' => '2026-10-08 12:00:00',
            ]);

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM Site WHERE id_site = ?'))
            ->willReturn($mockStmt);

        $dao = new MySqlSiteDao($mockPdo);
        $site = $dao->findById(10);

        $this->assertInstanceOf(Site::class, $site);
        $this->assertEquals(10, $site->idSite);
        $this->assertEquals('Central Chorrillos', $site->nombre);
        $this->assertEquals('AGG', $site->tipo);
        $this->assertEquals(2, $site->creadoPor);
    }

    public function testFindByIdDevuelveNullSiNoExiste(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('fetch')->willReturn(false);

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('prepare')->willReturn($mockStmt);

        $dao = new MySqlSiteDao($mockPdo);
        $site = $dao->findById(999);

        $this->assertNull($site);
    }

    public function testCreateInsertaYDevuelveUltimoId(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->expects($this->once())
            ->method('execute')
            ->with([
                'Nueva Sede Sur',
                'Panamericana Sur Km 20',
                'LIMA',
                'LURIN',
                'PAG',
                5
            ])
            ->willReturn(true);

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('prepare')->willReturn($mockStmt);
        $mockPdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('42');

        $dao = new MySqlSiteDao($mockPdo);
        $site = new Site(
            null,
            'Nueva Sede Sur',
            'Panamericana Sur Km 20',
            'LIMA',
            'LURIN',
            'PAG',
            1,
            5
        );

        $newId = $dao->create($site);
        $this->assertEquals(42, $newId);
    }
}
