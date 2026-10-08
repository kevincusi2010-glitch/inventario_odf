<?php

namespace App\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\InfraestructuraTreeBuilder;
use App\Entities\Site;

class InfraestructuraTreeBuilderTest extends TestCase
{
    private InfraestructuraTreeBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new InfraestructuraTreeBuilder();
    }

    public function testConstruyeEstructuraArbolConRacksCablesYEmpalmes(): void
    {
        $site = new Site(1, 'Central Norte', 'Av. Tupac 100', 'LIMA', 'COMAS', 'PAG');

        $odfs = [
            ['id_odf' => 10, 'id_site' => 1, 'origen' => 'Central', 'destino' => 'Sub1', 'ubicacion_rack' => 'Rack A', 'capacidad_puertos' => 24],
            ['id_odf' => 11, 'id_site' => 1, 'origen' => 'Central', 'destino' => 'Sub2', 'ubicacion_rack' => 'Rack A', 'capacidad_puertos' => 24],
            ['id_odf' => 12, 'id_site' => 1, 'origen' => 'Central', 'destino' => 'Sub3', 'ubicacion_rack' => 'Rack B', 'capacidad_puertos' => 48],
        ];

        $cables = [
            ['id_cable' => 100, 'id_site' => 1, 'origen' => 'Central', 'destino' => 'Sub1', 'capacidad_hilos' => 24],
            ['id_cable' => 200, 'id_site' => 1, 'origen' => 'Central', 'destino' => 'Suelto', 'capacidad_hilos' => 48],
        ];

        $ports = [
            // Puerto en ODF 10 conectado al cable 100
            ['id_puerto' => 1, 'id_odf' => 10, 'posicion' => 1, 'id_cable_conectado' => 100, 'numero_hilo' => 1],
            // ODF 11 y 12 no tienen puertos conectados
        ];

        $tree = $this->builder->buildTree($site, $odfs, $cables, $ports);

        // Validar información del sitio
        $this->assertEquals(1, $tree['info']['id_site']);
        $this->assertEquals('Central Norte', $tree['info']['nombre']);

        // Validar existencia de Racks A y B
        $this->assertArrayHasKey('Rack A', $tree['racks']);
        $this->assertArrayHasKey('Rack B', $tree['racks']);

        // Rack A tiene el cable 100 con el ODF 10
        $this->assertArrayHasKey(100, $tree['racks']['Rack A']['cables']);
        $this->assertEquals(10, $tree['racks']['Rack A']['cables'][100]['odfs'][0]['id_odf']);

        // Rack A tiene ODF 11 en 'none' (sin cables)
        $this->assertArrayHasKey('none', $tree['racks']['Rack A']['cables']);
        $this->assertEquals(11, $tree['racks']['Rack A']['cables']['none']['odfs'][0]['id_odf']);

        // Rack B tiene ODF 12 en 'none'
        $this->assertArrayHasKey('none', $tree['racks']['Rack B']['cables']);
        $this->assertEquals(12, $tree['racks']['Rack B']['cables']['none']['odfs'][0]['id_odf']);

        // Cable 200 está registrado en la sede pero suelto (sin empalmes)
        $this->assertCount(1, $tree['cables_sueltos']);
        $this->assertEquals(200, $tree['cables_sueltos'][0]['id_cable']);
    }
}
