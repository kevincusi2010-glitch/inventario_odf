<?php

namespace App\Services;

use App\Entities\Site;

/**
 * Servicio de Dominio: Ensambla el árbol topológico de la infraestructura física.
 *
 * Jerarquía resultante:
 * Sede (Site)
 *   ├── Racks (Gabinetes)
 *   │     ├── Cables de Alimentación
 *   │     │     └── ODFs empalmados
 *   │     └── [ODFs sin Cables asignados]
 *   └── Cables sueltos (registrados en la sede pero aún no conectados a puertos)
 */
class InfraestructuraTreeBuilder
{
    /**
     * Construye el árbol topológico jerárquico a partir de los datos recolectados.
     *
     * @param array|Site $site   Datos de la sede.
     * @param array      $odfs   Lista de ODFs registrados en la sede.
     * @param array      $cables Lista de cables de alimentación registrados en la sede.
     * @param array      $ports  Lista de puertos de los ODFs de la sede.
     * @return array Estructura anidada para renderizado.
     */
    public function buildTree(array|Site $site, array $odfs, array $cables, array $ports): array
    {
        $siteData = $site instanceof Site ? $site->toArray() : $site;
        $siteId = (int)($siteData['id_site'] ?? $siteData['idSite'] ?? 0);

        $node = [
            'info' => $siteData,
            'racks' => [],
            'cables_sueltos' => [],
        ];

        // 1. Obtener lista única de ubicaciones de Racks
        $siteOdfs = array_filter($odfs, fn($o) => (int)($o['id_site'] ?? 0) === $siteId);
        $rackList = array_unique(array_filter(array_column($siteOdfs, 'ubicacion_rack')));

        // 2. Procesar cada Rack
        foreach ($rackList as $rack) {
            $node['racks'][$rack] = ['cables' => []];
            $rackOdfs = array_filter($siteOdfs, fn($o) => ($o['ubicacion_rack'] ?? null) === $rack);

            $rackCableIds = [];
            $odfsConCable = [];

            foreach ($rackOdfs as $ro) {
                $odfId = (int)$ro['id_odf'];
                $odfPorts = array_filter($ports, fn($p) => (int)$p['id_odf'] === $odfId);
                $hasCable = false;

                foreach ($odfPorts as $p) {
                    if (!empty($p['id_cable_conectado'])) {
                        $rackCableIds[] = (int)$p['id_cable_conectado'];
                        $hasCable = true;
                    }
                }

                if ($hasCable) {
                    $odfsConCable[] = $odfId;
                }
            }

            $rackCableIds = array_unique($rackCableIds);

            // Asociar cables a ODFs en el rack
            foreach ($rackCableIds as $cid) {
                $cableInfo = array_values(array_filter($cables, fn($c) => (int)$c['id_cable'] === $cid))[0] ?? null;
                if ($cableInfo) {
                    $node['racks'][$rack]['cables'][$cid] = [
                        'info' => $cableInfo,
                        'odfs' => [],
                    ];

                    foreach ($rackOdfs as $ro) {
                        $odfId = (int)$ro['id_odf'];
                        $isConnected = count(array_filter(
                            $ports,
                            fn($p) => (int)$p['id_odf'] === $odfId && (int)($p['id_cable_conectado'] ?? 0) === $cid
                        )) > 0;

                        if ($isConnected) {
                            $node['racks'][$rack]['cables'][$cid]['odfs'][] = $ro;
                        }
                    }
                }
            }

            // ODFs del rack que aún no tienen cables conectados
            $odfsSinCable = array_values(array_filter($rackOdfs, fn($ro) => !in_array((int)$ro['id_odf'], $odfsConCable, true)));
            if (!empty($odfsSinCable)) {
                $node['racks'][$rack]['cables']['none'] = [
                    'info' => [
                        'id_cable' => 'none',
                        'origen' => '[ODFs sin Cables asignados]',
                        'destino' => null,
                        'tipo_cable' => '[ODFs sin Cables asignados]',
                    ],
                    'odfs' => $odfsSinCable,
                ];
            }
        }

        // 3. Cables registrados en la sede que aún no alimentan a ningún puerto
        $siteCables = array_filter($cables, fn($c) => (int)($c['id_site'] ?? 0) === $siteId);
        $connectedCableIds = array_unique(array_filter(array_column($ports, 'id_cable_conectado')));

        foreach ($siteCables as $sc) {
            $scId = (int)$sc['id_cable'];
            if (!in_array($scId, $connectedCableIds, true)) {
                $node['cables_sueltos'][] = $sc;
            }
        }

        return $node;
    }
}
