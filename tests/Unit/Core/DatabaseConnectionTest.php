<?php

namespace App\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\DatabaseConnection;
use PDO;
use RuntimeException;

class DatabaseConnectionTest extends TestCase
{
    private PDO $sqlitePdo;

    protected function setUp(): void
    {
        $this->sqlitePdo = new PDO('sqlite::memory:');
        $this->sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->sqlitePdo->exec("CREATE TABLE test_items (id INTEGER PRIMARY KEY, name TEXT)");
    }

    public function testTransactionConfirmaCambiosSiNoHayErrores(): void
    {
        $result = DatabaseConnection::transaction($this->sqlitePdo, function (PDO $pdo) {
            $pdo->exec("INSERT INTO test_items (name) VALUES ('ODF-Principal')");
            return 'completado';
        });

        $this->assertEquals('completado', $result);

        $stmt = $this->sqlitePdo->query("SELECT COUNT(*) FROM test_items");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    public function testTransactionRevierteCambiosSiOcurreExcepcion(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Fallo provocado en la transacción");

        try {
            DatabaseConnection::transaction($this->sqlitePdo, function (PDO $pdo) {
                $pdo->exec("INSERT INTO test_items (name) VALUES ('ODF-Que-Debe-Revertirse')");
                throw new RuntimeException("Fallo provocado en la transacción");
            });
        } finally {
            // Comprobamos que el rollback se ejecutó y la base de datos sigue vacía
            $stmt = $this->sqlitePdo->query("SELECT COUNT(*) FROM test_items");
            $this->assertEquals(0, (int)$stmt->fetchColumn());
        }
    }
}
