-- Migración 001: Persistencia de Autoría y Trazabilidad
-- Agrega columnas de auditoría a Site, ODF y CableAlimentacion
-- para eliminar la dependencia volátil de $_SESSION['creados_en_sesion']

ALTER TABLE `site`
    ADD COLUMN IF NOT EXISTS `creado_por` INT NULL AFTER `activo`,
    ADD COLUMN IF NOT EXISTS `creado_en` DATETIME DEFAULT CURRENT_TIMESTAMP AFTER `creado_por`,
    ADD CONSTRAINT `fk_site_creado_por` FOREIGN KEY IF NOT EXISTS (`creado_por`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL;

ALTER TABLE `odf`
    ADD COLUMN IF NOT EXISTS `creado_por` INT NULL AFTER `activo`,
    ADD COLUMN IF NOT EXISTS `creado_en` DATETIME DEFAULT CURRENT_TIMESTAMP AFTER `creado_por`,
    ADD CONSTRAINT `fk_odf_creado_por` FOREIGN KEY IF NOT EXISTS (`creado_por`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL;

ALTER TABLE `cablealimentacion`
    ADD COLUMN IF NOT EXISTS `creado_por` INT NULL AFTER `activo`,
    ADD COLUMN IF NOT EXISTS `creado_en` DATETIME DEFAULT CURRENT_TIMESTAMP AFTER `creado_por`,
    ADD CONSTRAINT `fk_cable_creado_por` FOREIGN KEY IF NOT EXISTS (`creado_por`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL;
