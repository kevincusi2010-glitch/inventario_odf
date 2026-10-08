# 📊 Bitácora de Auditoría y Comparativo de Refactorización

Este documento registra formalmente el estado del sistema en cada etapa del proyecto para generar el **Informe Comparativo Final (Antes vs. Después)** al culminar todas las fases.

---

## 🏛️ Línea Base: Estado Original del Sistema (Antes de la Refactorización)

| Criterio | Situación Inicial (Línea Base) | Nivel de Riesgo / Deuda Técnica |
| :--- | :--- | :---: |
| **Arquitectura** | Scripts de página (*Page Controller*) y monolitos híbridos con PHP, SQL, HTML, CSS y JS embebidos en el mismo archivo. | **Alto** |
| **Principio SOLID** | Clases "Dios" (`Infraestructura.php`, `Usuario.php`) que mezclan lógica de datos, seguridad, roles y acceso a sesión HTTP. Acoplamiento estático a `Database`. | **Alto** |
| **Patrón DAO** | Incumplido. Más de 15 consultas SQL complejas dispersas dentro de las vistas (`admin_site_detalle.php`, `dashboard.php`, `estadisticas.php`) y en APIs. | **Crítico** |
| **Resiliencia & SRE** | Credenciales quemadas en código (`root`, `''`). Excepciones no capturadas muestran pantallas blancas/500 al usuario. Sin logs en disco. Pérdida definitiva de permisos de supervisores al vencerse `$_SESSION`. | **Crítico** |
| **Polimorfismo** | 0 interfaces, 0 clases abstractas. Toda la variabilidad se maneja con cadenas de texto y condicionales `if / else` (`if ($rol === 'Tecnico')`, `if ($ext === 'csv')`). | **Alto** |
| **Testing / TDD** | **0 tests automatizados**. 0% de cobertura. Sin Composer ni PHPUnit. Pruebas 100% manuales propensas a errores humanos. | **Crítico** |

---

## 📈 Registro de Mejoras por Fases

---

### 🟢 FASE 1: Cimientos del Proyecto, Resiliencia Base y Entorno TDD
* **Fecha de Ejecución:** 2026-10-08
* **Agentes Intervinientes:** `@Architect`, `@ResilienceSre`, `@TddQa`

#### 1. Transformaciones Implementadas
1. **Autoloading Estándar PSR-4:**
   - Creado `composer.json` mapeando `App\` a `app/` y `App\Tests\` a `tests/`.
   - Eliminada la necesidad de dependencias circulares mediante `require_once` desordenados.
2. **Resiliencia de Configuración (.env):**
   - Creados `.env` y `.env.example`.
   - Implementado `app/Core/Env.php`: lector nativo tipado que elimina las contraseñas quemadas en el código fuente.
3. **Resiliencia ante Fallos y Telemetría (ErrorHandler):**
   - Implementado `app/Core/ErrorHandler.php`: capturador global de errores nativos, excepciones y paradas fatales.
   - Registro automático con marca de tiempo en `logs/app.log`.
   - Respuestas amigables protegidas: en web muestra pantalla institucional segura; en endpoints responde JSON estructurado.
4. **Resiliencia de Conexión (DatabaseConnection):**
   - Implementado `app/Core/DatabaseConnection.php`: conexión PDO con *Retry Policy* (hasta 3 reintentos con retraso progresivo ante micro-cortes con MySQL).
   - Gestor atómico de transacciones (`DatabaseConnection::transaction()`) para asegurar atomicidad sin fugas de transacciones.
   - Retrocompatibilidad al 100% conectada en `classes/Database.php`.
5. **Infraestructura de Pruebas Automatizadas (PHPUnit):**
   - Creado `phpunit.xml` configurando suites *Unit* e *Integration*.
   - Implementados los primeros 5 tests unitarios en verde ejecutándose en memoria.

#### 2. Comparativo Cuantitativo y Cualitativo - Fase 1

| Dimensión | Antes de Fase 1 | Después de Fase 1 | Mejora Lograda |
| :--- | :--- | :--- | :---: |
| **Pruebas Automatizadas** | 0 tests | 5 tests unitarios (10 assertions) | **+100% cobertura base** (0.012s tiempo) |
| **Gestión de Errores** | Sin capturador global; caída en pantalla blanca / traza expuesta. | `ErrorHandler` activo capturando errores y guardando traza en `logs/app.log`. | **Disponibilidad y seguridad blindadas** |
| **Credenciales de BD** | Fijas en código duro en `Database.php`. | Variables de entorno aisladas en `.env`. | **Seguridad de credenciales aprobada** |
| **Tolerancia a micro-cortes** | 1 intento directo; si falla, la petición muere. | 3 reintentos automáticos con retardo exponencial (*Backoff*). | **Tolerancia a fallos transitorios activa** |
| **Atomicidad de transacciones** | Manual con riesgo de olvidar rollback. | Helper `DatabaseConnection::transaction()` con rollback garantizado. | **Integridad de datos asegurada** |

---

### ⏳ FASE 2: Capa de Persistencia, DAO y Resiliencia de Autoría
*(Pendiente de ejecución - Próxima fase)*
* **Objetivos a auditar:**
  - Migración SQL de persistencia (`creado_por` y `creado_en`) para eliminar dependencia volátil de `$_SESSION`.
  - Contratos DAO (`SiteDaoInterface`, `OdfDaoInterface`, `CableDaoInterface`, `PuertoDaoInterface`, `UsuarioDaoInterface`).
  - Entidades DTO tipadas.
  - Implementaciones concretas `MySql*Dao`.
  - Tests unitarios de DAO con Mocks.

---

### ⏳ FASE 3: Lógica de Dominio, Polimorfismo y Patrón Strategy
*(Pendiente de ejecución)*
* **Objetivos a auditar:**
  - Extracción del árbol topológico de `admin_site_detalle.php` a `InfraestructuraTreeBuilder`.
  - `SpreadsheetReaderInterface` (CSV y XLSX polimórficos).
  - `RolePolicyInterface` (Políticas de rol sin condicionales).
  - `PuertoBatchImportService`.

---

### ⏳ FASE 4: Refactorización de Vistas y APIs (MVC Limpio)
*(Pendiente de ejecución)*
* **Objetivos a auditar:**
  - Erradicación del 100% de consultas SQL en `admin_site_detalle.php`, `dashboard.php` y `estadisticas.php`.
  - Conexión a Servicios y DAOs.
  - Verificación de cero impacto en UI/UX.

---

### ⏳ FASE 5: Auditoría, Suite TDD Completa y Consolidación
*(Pendiente de ejecución)*
* **Objetivos a auditar:**
  - Cobertura completa de pruebas automáticas.
  - Reporte consolidado Antes vs. Después para la entrega final.
