<?php

namespace App\Tests\Unit\Daos;

use PHPUnit\Framework\TestCase;
use App\Daos\MySqlUsuarioDao;
use PDO;
use PDOStatement;

class MySqlUsuarioDaoTest extends TestCase
{
    public function testCountAdminsExcludingEjecutaConteoCorrecto(): void
    {
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->expects($this->once())
            ->method('execute')
            ->with([1]);
        $mockStmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(2);

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("WHERE rol = 'Administrador' AND id_usuario != ?"))
            ->willReturn($mockStmt);

        $dao = new MySqlUsuarioDao($mockPdo);
        $count = $dao->countAdminsExcluding(1);

        $this->assertEquals(2, $count);
    }

    public function testUnlinkUserReferencesDesvinculaPuertosEHistorial(): void
    {
        $mockStmtPuerto = $this->createMock(PDOStatement::class);
        $mockStmtPuerto->expects($this->once())
            ->method('execute')
            ->with([7]);

        $mockStmtHistorial = $this->createMock(PDOStatement::class);
        $mockStmtHistorial->expects($this->once())
            ->method('execute')
            ->with([7]);

        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function (string $sql) use ($mockStmtPuerto, $mockStmtHistorial) {
                if (str_contains($sql, 'HistorialCambiosPuerto')) {
                    return $mockStmtHistorial;
                }
                return $mockStmtPuerto;
            });

        $dao = new MySqlUsuarioDao($mockPdo);
        $dao->unlinkUserReferences(7);

        $this->assertTrue(true);
    }
}
