<?php
/**
 * Ejecutor de migraciones de base de datos.
 * Aplica cambios de esquema de forma segura e idempotente.
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\DatabaseConnection;

$pdo = DatabaseConnection::getSharedInstance();

echo "=== Iniciando Migraciones de Base de Datos ===\n";

$tables = ['site', 'odf', 'cablealimentacion'];

foreach ($tables as $table) {
    echo "Verificando columnas de autoría en tabla '{$table}'...\n";
    
    // Verificar si la columna creado_por ya existe
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = ? 
          AND COLUMN_NAME = 'creado_por'
    ");
    $stmt->execute([$table]);
    $hasCreadoPor = (int)$stmt->fetchColumn() > 0;

    if (!$hasCreadoPor) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `creado_por` INT NULL AFTER `activo`");
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `creado_en` DATETIME DEFAULT CURRENT_TIMESTAMP AFTER `creado_por`");
        
        // Agregar constraint de clave foránea si no existe
        try {
            $pdo->exec("ALTER TABLE `{$table}` ADD CONSTRAINT `fk_{$table}_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL");
        } catch (\Throwable $e) {
            // Ignorar si la FK ya existía
        }
        echo " -> Columnas 'creado_por' y 'creado_en' agregadas a '{$table}'.\n";
    } else {
        echo " -> Columnas ya existen en '{$table}'. Omitiendo.\n";
    }
}

echo "=== Migraciones Finalizadas Exitosamente ===\n";
