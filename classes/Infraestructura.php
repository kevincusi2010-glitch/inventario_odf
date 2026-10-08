<?php
require_once __DIR__ . '/Database.php';

/**
 * Modelo/DAO de la infraestructura física del inventario ODF.
 *
 * Gestiona la jerarquía Sede (Site) -> Cable de Alimentación -> Distribuidor
 * ODF, y aplica dos reglas transversales:
 *   1) Los Supervisores solo pueden editar o eliminar lo que crearon en su
 *      sesión actual (control guardado en $_SESSION['creados_en_sesion']).
 *   2) La capacidad de puertos del ODF es la misma que su número de conectores:
 *      al crear o ampliar el ODF se aprovisionan automáticamente las posiciones.
 */
class Infraestructura {
    private $pdo;

    /** Tipos válidos de SITE (debe coincidir con el ENUM de la tabla Site). */
    const TIPOS_SITE = ['PAG', 'AGG', 'SITE', 'AGG/PAG'];

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    private function rolActual() {
        return $_SESSION['usuario']['rol'] ?? '';
    }

    private function trackCreacion($tipo, $id) {
        if ($this->rolActual() === 'Supervisor') {
            if (!isset($_SESSION['creados_en_sesion']) || !is_array($_SESSION['creados_en_sesion'])) {
                $_SESSION['creados_en_sesion'] = ['site' => [], 'odf' => [], 'cable' => []];
            }
            if (!isset($_SESSION['creados_en_sesion'][$tipo])) {
                $_SESSION['creados_en_sesion'][$tipo] = [];
            }
            $_SESSION['creados_en_sesion'][$tipo][] = $id;
        }
    }

    private function supervisorPuede($tipo, $id) {
        if ($this->rolActual() !== 'Supervisor') {
            return true;
        }
        $userId = $_SESSION['usuario']['id_usuario'] ?? null;
        if ($userId !== null) {
            $table = match($tipo) {
                'site' => 'Site',
                'cable' => 'CableAlimentacion',
                'odf' => 'ODF',
                default => null
            };
            $pk = match($tipo) {
                'site' => 'id_site',
                'cable' => 'id_cable',
                'odf' => 'id_odf',
                default => null
            };
            if ($table && $pk) {
                $stmt = $this->pdo->prepare("SELECT creado_por FROM {$table} WHERE {$pk} = ?");
                $stmt->execute([$id]);
                $creador = $stmt->fetchColumn();
                if ($creador !== false && (int)$creador === (int)$userId) {
                    return true;
                }
            }
        }
        return in_array($id, $_SESSION['creados_en_sesion'][$tipo] ?? []);
    }

    // --- SITES ---
    
    public function getSitesWithCounts() {
        $stmt = $this->pdo->query("
            SELECT s.*, 
                   (SELECT COUNT(*) FROM ODF WHERE id_site = s.id_site AND activo = 1) as total_odfs,
                   (SELECT COUNT(*) FROM CableAlimentacion WHERE id_site = s.id_site AND activo = 1) as total_cables
            FROM Site s 
            WHERE s.activo = 1 
            ORDER BY s.nombre
        ");
        return $stmt->fetchAll();
    }
    
    public function createSite($nombre, $direccion, $departamento, $distrito, $tipo) {
        if (!in_array($tipo, self::TIPOS_SITE, true)) {
            throw new Exception("El tipo de SITE seleccionado no es válido.");
        }
        $userId = $_SESSION['usuario']['id_usuario'] ?? null;
        $stmt = $this->pdo->prepare('INSERT INTO Site (nombre, direccion, departamento, distrito, tipo, creado_por) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$nombre, $direccion, $departamento, $distrito, $tipo, $userId]);
        $id = $this->pdo->lastInsertId();
        
        $this->trackCreacion('site', $id);
        return $id;
    }

    public function updateSite($id_site, $nombre, $direccion, $departamento, $distrito, $tipo) {
        if (!$this->supervisorPuede('site', $id_site)) {
            throw new Exception("Los supervisores solo pueden editar las sedes que hayan creado durante su sesión actual.");
        }
        if (!in_array($tipo, self::TIPOS_SITE, true)) {
            throw new Exception("El tipo de SITE seleccionado no es válido.");
        }
        $stmt = $this->pdo->prepare('UPDATE Site SET nombre=?, direccion=?, departamento=?, distrito=?, tipo=? WHERE id_site=?');
        return $stmt->execute([$nombre, $direccion, $departamento, $distrito, $tipo, $id_site]);
    }

    public function deleteSite($id_site) {
        if (!$this->supervisorPuede('site', $id_site)) {
            throw new Exception("Los supervisores solo pueden eliminar las sedes que hayan creado durante su sesión actual. Comuníquese con el Gestor del Sistema.");
        }
        
        $stmtOdf = $this->pdo->prepare("SELECT COUNT(*) FROM ODF WHERE id_site = ?");
        $stmtOdf->execute([$id_site]);
        if ($stmtOdf->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar la Sede porque aún contiene ODFs registrados.");
        }

        $stmtCable = $this->pdo->prepare("SELECT COUNT(*) FROM CableAlimentacion WHERE id_site = ?");
        $stmtCable->execute([$id_site]);
        if ($stmtCable->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar la Sede porque aún contiene Cables registrados.");
        }

        $stmt = $this->pdo->prepare('DELETE FROM Site WHERE id_site = ?');
        return $stmt->execute([$id_site]);
    }

    // --- CABLES ---

    public function createCable($id_site, $origen, $destino, $capacidad_hilos, $rack, $fecha_instalacion) {
        $origen = trim($origen);
        $destino = trim($destino);
        if ($destino === '') { $destino = null; }
        $rack = trim($rack);
        if ($rack === '') { $rack = null; }
        if ($origen === '') {
            throw new Exception("El campo Origen es obligatorio.");
        }

        // Un mismo cable (origen → destino) no puede pertenecer a dos Sedes distintas
        $stmtCheck = $this->pdo->prepare("SELECT id_site FROM CableAlimentacion WHERE origen = ? AND destino <=> ? LIMIT 1");
        $stmtCheck->execute([$origen, $destino]);
        $existingSite = $stmtCheck->fetchColumn();
        
        if ($existingSite !== false && $existingSite != $id_site) {
            $etiqueta = $destino ? "$origen → $destino" : $origen;
            throw new Exception("El Cable '$etiqueta' ya pertenece a otra Sede.");
        }

        $userId = $_SESSION['usuario']['id_usuario'] ?? null;
        $stmt = $this->pdo->prepare('INSERT INTO CableAlimentacion (id_site, origen, destino, capacidad_hilos, rack, fecha_instalacion, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$id_site, $origen, $destino, $capacidad_hilos, $rack, $fecha_instalacion ?: null, $userId]);
        $id = $this->pdo->lastInsertId();
        
        $this->trackCreacion('cable', $id);
        return $id;
    }

    public function updateCable($id_cable, $id_site, $origen, $destino, $capacidad_hilos, $rack, $fecha_instalacion) {
        if (!$this->supervisorPuede('cable', $id_cable)) {
            throw new Exception("Los supervisores solo pueden editar los cables que hayan creado durante su sesión actual.");
        }
        $origen = trim($origen);
        $destino = trim($destino);
        if ($destino === '') { $destino = null; }
        $rack = trim($rack);
        if ($rack === '') { $rack = null; }
        if ($origen === '') {
            throw new Exception("El campo Origen es obligatorio.");
        }

        $stmtCheck = $this->pdo->prepare("SELECT id_site FROM CableAlimentacion WHERE origen = ? AND destino <=> ? AND id_cable != ? LIMIT 1");
        $stmtCheck->execute([$origen, $destino, $id_cable]);
        $existingSite = $stmtCheck->fetchColumn();
        
        if ($existingSite !== false && $existingSite != $id_site) {
            $etiqueta = $destino ? "$origen → $destino" : $origen;
            throw new Exception("El Cable '$etiqueta' ya pertenece a otra Sede.");
        }

        $stmt = $this->pdo->prepare('UPDATE CableAlimentacion SET id_site=?, origen=?, destino=?, capacidad_hilos=?, rack=?, fecha_instalacion=? WHERE id_cable=?');
        return $stmt->execute([$id_site, $origen, $destino, $capacidad_hilos, $rack, $fecha_instalacion ?: null, $id_cable]);
    }

    public function deleteCable($id_cable) {
        if (!$this->supervisorPuede('cable', $id_cable)) {
            throw new Exception("Los supervisores solo pueden eliminar los cables que hayan creado durante su sesión actual.");
        }
        $stmt = $this->pdo->prepare('UPDATE Puerto SET id_cable_conectado = NULL WHERE id_cable_conectado = ?');
        $stmt->execute([$id_cable]);
        $stmt = $this->pdo->prepare('DELETE FROM CableAlimentacion WHERE id_cable = ?');
        return $stmt->execute([$id_cable]);
    }

    // --- ODFs ---
    
    public function createOdf($id_site, $origen, $destino, $capacidad_puertos, $ubicacion_rack) {
        $ubicacion_rack = trim($ubicacion_rack);
        // Nota: el rack es una etiqueta POR SEDE (los cuadros de hilos numeran
        // "Rack 1", "Rack 2"... en cada sede), por lo que puede repetirse entre sedes.

        $userId = $_SESSION['usuario']['id_usuario'] ?? null;
        $stmt = $this->pdo->prepare("INSERT INTO ODF (id_site, origen, destino, capacidad_puertos, ubicacion_rack, creado_por) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_site, $origen, $destino, $capacidad_puertos, $ubicacion_rack, $userId]);
        $id_odf = $this->pdo->lastInsertId();
        
        $this->trackCreacion('odf', $id_odf);
        
        // Aprovisionamiento automático de puertos
        $cap = (int)$capacidad_puertos;
        $stmtPto = $this->pdo->prepare("INSERT INTO Puerto (id_odf, posicion, estado) VALUES (?, ?, 'Sin Servicio')");
        for ($i = 1; $i <= $cap; $i++) {
            $stmtPto->execute([$id_odf, $i]);
        }
        
        return $id_odf;
    }

    public function updateOdf($id_odf, $id_site, $origen, $destino, $nueva_capacidad, $ubicacion_rack) {
        if (!$this->supervisorPuede('odf', $id_odf)) {
            throw new Exception("Los supervisores solo pueden editar los equipos ODF que hayan creado durante su sesión actual.");
        }
        
        $ubicacion_rack = trim($ubicacion_rack);
        // Nota: el rack es una etiqueta POR SEDE (ver createOdf), puede repetirse entre sedes.
        
        $stmtCap = $this->pdo->prepare("SELECT capacidad_puertos FROM ODF WHERE id_odf = ?");
        $stmtCap->execute([$id_odf]);
        $currentCap = $stmtCap->fetchColumn();
        
        if ($nueva_capacidad < $currentCap) {
            $stmtCheck2 = $this->pdo->prepare("SELECT COUNT(*) FROM Puerto WHERE id_odf = ? AND posicion > ? AND (estado != 'Sin Servicio' OR id_cable_conectado IS NOT NULL)");
            $stmtCheck2->execute([$id_odf, $nueva_capacidad]);
            if ($stmtCheck2->fetchColumn() > 0) {
                throw new Exception("No se puede reducir la capacidad porque hay puertos superiores que están en uso.");
            }
            $stmtDel = $this->pdo->prepare("DELETE FROM Puerto WHERE id_odf = ? AND posicion > ?");
            $stmtDel->execute([$id_odf, $nueva_capacidad]);
        } else if ($nueva_capacidad > $currentCap) {
            $stmtIns = $this->pdo->prepare("INSERT INTO Puerto (id_odf, posicion, estado) VALUES (?, ?, 'Sin Servicio')");
            for ($i = $currentCap + 1; $i <= $nueva_capacidad; $i++) {
                $stmtIns->execute([$id_odf, $i]);
            }
        }

        $stmt = $this->pdo->prepare("UPDATE ODF SET id_site=?, origen=?, destino=?, ubicacion_rack=?, capacidad_puertos=? WHERE id_odf=?");
        return $stmt->execute([$id_site, $origen, $destino, $ubicacion_rack, $nueva_capacidad, $id_odf]);
    }

    public function deleteOdf($id_odf) {
        if (!$this->supervisorPuede('odf', $id_odf)) {
            throw new Exception("Los supervisores solo pueden eliminar los equipos ODF que hayan creado durante su sesión actual.");
        }
        $stmt = $this->pdo->prepare('DELETE FROM Puerto WHERE id_odf = ?');
        $stmt->execute([$id_odf]);
        $stmt = $this->pdo->prepare('DELETE FROM ODF WHERE id_odf = ?');
        return $stmt->execute([$id_odf]);
    }
}
?>
