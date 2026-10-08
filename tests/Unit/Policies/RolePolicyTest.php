<?php

namespace App\Tests\Unit\Policies;

use PHPUnit\Framework\TestCase;
use App\Policies\AdminPolicy;
use App\Policies\SupervisorPolicy;
use App\Policies\TecnicoPolicy;
use App\Policies\PolicyFactory;

class RolePolicyTest extends TestCase
{
    public function testAdminPolicyTieneAccesoTotal(): void
    {
        $policy = new AdminPolicy();

        $puertoBloqueado = ['id_puerto' => 1, 'estado' => 'En Servicio', 'bloqueado' => 1];
        $this->assertTrue($policy->canModifyPort($puertoBloqueado));
        $this->assertTrue($policy->canEditInfrastructure(99, 1));
        $this->assertTrue($policy->canDeleteInfrastructure(99, 1));
        $this->assertTrue($policy->canManageUsers());
        $this->assertEquals('Administrador', $policy->getRoleName());
    }

    public function testSupervisorPolicySoloEditaLoQueCreoPersistenteEnBd(): void
    {
        $policy = new SupervisorPolicy();

        $puertoNormal = ['id_puerto' => 1, 'estado' => 'Sin Servicio', 'bloqueado' => 0];
        $puertoBloqueado = ['id_puerto' => 2, 'estado' => 'Sin Servicio', 'bloqueado' => 1];

        $this->assertTrue($policy->canModifyPort($puertoNormal));
        $this->assertFalse($policy->canModifyPort($puertoBloqueado));

        // Creado por el mismo supervisor (id = 5)
        $this->assertTrue($policy->canEditInfrastructure(5, 5));
        $this->assertTrue($policy->canDeleteInfrastructure(5, 5));

        // Creado por otro usuario (id = 2)
        $this->assertFalse($policy->canEditInfrastructure(2, 5));
        $this->assertFalse($policy->canDeleteInfrastructure(2, 5));

        // No puede gestionar usuarios
        $this->assertFalse($policy->canManageUsers());
    }

    public function testTecnicoPolicyNoModificaPuertosEnServicioNiBloqueados(): void
    {
        $policy = new TecnicoPolicy();

        $puertoLibre = ['id_puerto' => 1, 'estado' => 'Sin Servicio', 'bloqueado' => 0];
        $puertoEnServicio = ['id_puerto' => 2, 'estado' => 'En Servicio', 'bloqueado' => 0];
        $puertoBloqueado = ['id_puerto' => 3, 'estado' => 'Sin Servicio', 'bloqueado' => 1];

        $this->assertTrue($policy->canModifyPort($puertoLibre));
        $this->assertFalse($policy->canModifyPort($puertoEnServicio));
        $this->assertFalse($policy->canModifyPort($puertoBloqueado));

        $this->assertFalse($policy->canEditInfrastructure(5, 5));
        $this->assertFalse($policy->canDeleteInfrastructure(5, 5));
        $this->assertFalse($policy->canManageUsers());
    }

    public function testPolicyFactoryCreaInstanciaCorrectaSegunRol(): void
    {
        $this->assertInstanceOf(AdminPolicy::class, PolicyFactory::forRole('Administrador'));
        $this->assertInstanceOf(SupervisorPolicy::class, PolicyFactory::forRole('Supervisor'));
        $this->assertInstanceOf(TecnicoPolicy::class, PolicyFactory::forRole('Tecnico'));
    }
}
