# 🤖 Marco de Orquestación Multi-Agente - Inventario ODF

Este documento establece el equipo de agentes especializados, sus responsabilidades, reglas de interacción y el protocolo de orquestación para la refactorización profesional del sistema **Inventario ODF** hacia una arquitectura con **SOLID, DAO, Resiliencia, Polimorfismo y TDD**.

---

## 🎯 Rol del Orquestador General (Antigravity)
* **Función:** Actuar como el director técnico y coordinador de ejecución.
* **Responsabilidades:**
  1. Recibir las indicaciones y directrices directas del usuario.
  2. Analizar el alcance y descomponer la tarea en responsabilidades concretas.
  3. Asignar y activar al agente especializado correspondiente.
  4. Garantizar la coherencia entre agentes (que los DAOs coincidan con las Entidades, que los Servicios cumplan las Interfaces y que los Tests validen los casos límite).
  5. Entregar al usuario el informe consolidado con el estado del sistema.

---

## 👥 Equipo de Agentes Especializados

### 1. 📐 Agente Arquitecto & Tech Lead (`@Architect`)
* **Misión:** Garantizar los principios de diseño de software (SOLID, Inversión de Dependencias, PSR-4).
* **Entregables:**
  - Interfaces y contratos (`Contracts/Daos/`, `Contracts/Policies/`, `Contracts/Readers/`).
  - Configuración del proyecto (`composer.json`, espacios de nombres `App\`).
  - Definición de límites arquitectónicos (prohibir acoplamientos prohibidos entre capas).

---

### 2. 💾 Agente DAO & Persistencia (`@DaoEngineer`)
* **Misión:** Centralizar el acceso a la base de datos y eliminar el 100% de consultas SQL dispersas.
* **Entregables:**
  - Definición de Entidades de dominio tipadas (`Entities/Site.php`, `Entities/Puerto.php`, etc.).
  - Implementaciones concretas de DAO (`Daos/MySqlSiteDao.php`, `Daos/MySqlPuertoDao.php`, etc.).
  - Migraciones de base de datos (columnas `creado_por`, `creado_en` e índices de optimización).

---

### 3. ⚙️ Agente de Lógica de Dominio & Servicios (`@DomainLogic`)
* **Misión:** Implementar las reglas del negocio, algoritmos y polimorfismo puros, sin acoplamiento a HTTP o SQL.
* **Entregables:**
  - `Services/InfraestructuraTreeBuilder.php` (armado del árbol topológico).
  - Implementación del Patrón Strategy (`CsvReader.php`, `XlsxReader.php`).
  - Políticas polimórficas de autorización (`AdminPolicy.php`, `TecnicoPolicy.php`, `SupervisorPolicy.php`).
  - `Services/PuertoBatchImportService.php` (lógica de validación de importación).

---

### 4. 🛡️ Agente de Resiliencia & Seguridad (`@ResilienceSre`)
* **Misión:** Garantizar la tolerancia a fallos, auditoría incorruptible y manejo seguro de estados.
* **Entregables:**
  - `Core/ErrorHandler.php` (capturador global de excepciones con registro a `logs/app.log`).
  - Conexión PDO con política de reintentos (*Retry Policy*) y transacciones seguras.
  - Eliminación de la volatilidad de `$_SESSION['creados_en_sesion']`, sustituyéndola por persistencia en BD.
  - Carga de variables de entorno (`.env`).

---

### 5. 🧪 Agente QA & TDD Engineer (`@TddQa`)
* **Misión:** Diseñar la red de seguridad de pruebas automatizadas mediante el ciclo Red-Green-Refactor.
* **Entregables:**
  - Configuración de **PHPUnit** (`phpunit.xml`).
  - Mocks y Stubs de DAOs para pruebas unitarias sin dependencia de MySQL.
  - Tests unitarios de:
    - Validación de conflicto de hilos de fibra óptica.
    - Políticas de permisos por rol en puertos ópticos.
    - Algoritmo de estructuración del árbol topológico.

---

### 6. 🎨 Agente Integrador de Vistas (`@ViewIntegrator`)
* **Misión:** Limpiar la capa de presentación manteniendo intacta la experiencia de usuario (UI/UX).
* **Entregables:**
  - Erradicación del código SQL dentro de las vistas (`admin_site_detalle.php`, `dashboard.php`, `estadisticas.php`).
  - Conexión de las vistas con los datos preparados por los Servicios y DAOs.
  - Verificación de que los componentes interactivos (Bootstrap, modales, AJAX, selectores) operen sin alteraciones visuales o de rendimiento.

---

## 🔄 Protocolo de Operación

1. **Instrucción del Usuario:** El usuario envía la orden o requerimiento.
2. **Evaluación del Orquestador:** El Orquestador determina qué agente(s) deben intervenir y en qué orden secuencial.
3. **Ejecución Asignada:** El agente en turno ejecuta sus archivos, cambios o pruebas.
4. **Validación Cruzada:** El Agente `@TddQa` valida con pruebas y el Orquestador audita el cumplimiento de SOLID/DAO.
5. **Cierre de Ciclo:** Se presenta el resultado al usuario para su retroalimentación y siguiente instrucción.
