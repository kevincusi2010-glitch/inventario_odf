<?php

namespace App\Tests\Unit\Daos;

use PHPUnit\Framework\TestCase;
use App\Daos\MySqlPuertoDao;
use PDO;
use PDOStatement;

class MySqlPuertoDaoTest extends TestCase
{
    public function testLockForUpdateEjecutaSelectConForUpdate(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->expects($this->once())
            ->method('execute')
            ->with([15]);
        $mockStmt->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'id_puerto' => 15,
                'id_odf' => 2,
                'posicion' => 8,
                'estado' => 'Sin Servicio',
                'bloqueado' => 0
            ]);

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('FOR UPDATE'))
            ->willReturn($mockStmt);

        $dao = new MySqlPuertoDao($mockPdo);
        $locked = $dao->lockForUpdate(15);

        $this->assertIsArray($locked);
        $this->assertEquals(15, $locked['id_puerto']);
        $this->assertEquals(8, $locked['posicion']);
    }

    public function testFindThreadConflictDetectaConflictoDeHiloEnOtroOdf(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->expects($this->once())
            ->method('execute')
            ->with([10, 4, 1]); // id_cable = 10, hilo = 4, excludeOdf = 1
        $mockStmt->expects($this->once())
            ->method('fetch')
            ->willReturn(['id_odf' => 3, 'posicion' => 12]);

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('id_cable_conectado = ? AND numero_hilo = ? AND id_odf != ?'))
            ->willReturn($mockStmt);

        $dao = new MySqlPuertoDao($mockPdo);
        $conflict = $dao->findThreadConflict(10, 4, 1);

        $this->assertNotNull($conflict);
        $this->assertEquals(3, $conflict['id_odf']);
        $this->assertEquals(12, $conflict['posicion']);
    }

    public function testUpdateEstadoActualizaCamposEsperados(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->expects($this->once())
            ->method('execute')
            ->with(['En Servicio', 0, 1, 'Fibra cliente corporativo', 25])
            ->willReturn(true);

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('UPDATE Puerto'))
            ->willReturn($mockStmt);

        $dao = new MySqlPuertoDao($mockPdo);
        $result = $dao->updateEstado(25, 'En Servicio', 'Fibra cliente corporativo', 1, 0);

        $this->assertTrue($result);
    }
}
