<?php

namespace App\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\Env;

class EnvTest extends TestCase
{
    public function testObtieneValorPorDefectoSiVariableNoExiste(): void
    {
        $valor = Env::get('VARIABLE_INEXISTENTE_XYZ_' . uniqid(), 'valor_default');
        $this->assertEquals('valor_default', $valor);
    }

    public function testParseaValoresBooleanosCorrectamente(): void
    {
        $_ENV['TEST_BOOL_TRUE'] = 'true';
        $_ENV['TEST_BOOL_FALSE'] = 'false';

        $this->assertTrue(Env::get('TEST_BOOL_TRUE'));
        $this->assertFalse(Env::get('TEST_BOOL_FALSE'));

        unset($_ENV['TEST_BOOL_TRUE'], $_ENV['TEST_BOOL_FALSE']);
    }

    public function testCargaArchivoEnvTemporal(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'env_test_');
        file_put_contents($tmpFile, "PRUEBA_SRE_KEY=resiliencia_activa\n# Comentario\nOTRA_KEY=123\n");

        Env::load($tmpFile);

        $this->assertEquals('resiliencia_activa', Env::get('PRUEBA_SRE_KEY'));
        $this->assertEquals('123', Env::get('OTRA_KEY'));

        @unlink($tmpFile);
        unset($_ENV['PRUEBA_SRE_KEY'], $_ENV['OTRA_KEY']);
    }
}
