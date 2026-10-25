# GUION TÉCNICO Y DIÁLOGO DE SUSTENTACIÓN ORAL — GRUPO 3
**Sistema de Inventario Digital de ODF — Telefónica del Perú**
*Curso Integrador I: Sistemas Software | Universidad Tecnológica del Perú (UTP)*
*Docente Asesor: Ing. Rudy Chapoñan Camarena*
*Integrantes: Rodrigo Layme Ureta, Kevin Smith Cusi Espinoza, Diana Greis Aguado Pacompia*
*Duración Estimada Total: 11 a 13 minutos (Ideal para ronda de sustentación universitaria)*

---

## ESTRUCTURA DE ROLES RECOMENDADA
* **Orador 1 (Rodrigo Layme)**: Diapositivas 1 a 4 (Contexto, Problemática, Objetivos y Arquitectura Multicapa).
* **Orador 2 (Kevin Cusi)**: Diapositivas 5 a 8 (Diseño UML, Principios SOLID/DAO, Patrón Strategy y Calidad TDD).
* **Orador 3 (Diana Aguado)**: Diapositivas 9 a 14 (Seguridad Defensiva OWASP, Interfaces UX/UI, Git/GitHub, Documentación y Conclusiones).

*(Nota: Si la sustentación es individual, el guion se lee de forma continua adaptando la primera persona del plural).*

---

### DIAPOSITIVA 1: PORTADA INSTITUCIONAL (0:00 - 0:45)
**Visual**: Portada formal azul marino de Telefónica y UTP con títulos y nombres del equipo.
**Orador 1 (Rodrigo Layme)**:
> *"Buenos días, estimado profesor Rudy Chapoñan y miembros del jurado evaluador. Somos el Grupo número 3, conformado por Kevin Cusi, Diana Aguado y mi persona, Rodrigo Layme. En esta oportunidad, tenemos el agrado de presentar y sustentar nuestro proyecto final para el Curso Integrador I: Sistemas Software, titulado: **Sistema de Inventario Digital de ODF para Telefónica del Perú**.
>
> Nuestro proyecto aborda la gobernanza física, la trazabilidad atómica y la digitalización de la infraestructura de distribución de fibra óptica de la empresa, implementando una arquitectura de software empresarial bajo los estándares de MVC, DAO, SOLID, TDD y seguridad defensiva en profundidad."*

---

### DIAPOSITIVA 2: CONTEXTO DEL NEGOCIO Y PROBLEMÁTICA (0:45 - 1:45)
**Visual**: Tarjetas con los 4 desafíos operativos y métricas de impacto cuantitativo (45 min, 28% inconsistencias).
**Orador 1 (Rodrigo Layme)**:
> *"*(Señalar el bloque izquierdo)* Telefónica del Perú gestiona miles de kilómetros de red óptica troncal y de acceso. Históricamente, el estado de ocupación de los bastidores ODF —los distribuidores físicos donde llegan los cables de fibra— se administraba mediante hojas de cálculo Excel compartidas y bitácoras en papel.
>
> Esta metodología manual provocaba graves fallas operativas:
> 1. **Colisiones de hilos ópticos**: Cuadrillas de técnicos distintas asignaban simultáneamente el mismo hilo de fibra a clientes corporativos diferentes, generando cortes imprevistos y penalizaciones regulatorias de OSIPTEL.
> 2. **Ceguera operativa**: El Centro de Operaciones de Red (NOC) no disponía de semáforos de saturación en tiempo real para saber qué centrales estaban al borde de su capacidad crítica (>90%).
> 3. **Pérdida de tiempo y falta de auditoría**: Un técnico tardaba en promedio 45 minutos en validar en campo si un puerto libre estaba realmente disponible, y ante una avería, existía un 0% de trazabilidad forense para determinar quién realizó la última desconexión."*

---

### DIAPOSITIVA 3: OBJETIVOS Y ALCANCE DE LA SOLUCIÓN (1:45 - 2:30)
**Visual**: 3 pilares con cajas de color (Digitalización, Transaccionalidad ACID y Seguridad/SRE).
**Orador 1 (Rodrigo Layme)**:
> *"Para resolver esta problemática de raíz, nos trazamos tres objetivos de ingeniería claros:
> * **Primer Pilar: Gemelo Digital de ODF**: Construir una interfaz interactiva fiel a los bastidores industriales de 19 pulgadas, permitiendo ver en tiempo real el estado de cada conector con el código cromático internacional ITU-T.
> * **Segundo Pilar: Transaccionalidad ACID y Cero Colisiones**: Desarrollar un motor backend transaccional con bloqueos pesimistas a nivel de base de datos (`SELECT ... FOR UPDATE`), impidiendo por software que un mismo hilo óptico sea duplicado en dos puertos.
> * **Tercer Pilar: Seguridad OWASP y Observabilidad SRE**: Blindar el sistema con control de acceso basado en roles (RBAC), tokens anti-CSRF, rate limiting contra ataques de fuerza bruta y endpoints de diagnóstico de salud operativa en tiempo real."*

---

### DIAPOSITIVA 4: ARQUITECTURA MULTICAPA MVC Y CAPAS DAO (2:30 - 3:30)
**Visual**: Diagrama de arquitectura a 300 DPI y panel con las 5 capas desacopladas.
**Orador 1 (Rodrigo Layme)**:
> *"*(Señalar el diagrama central)* La arquitectura del sistema fue construida en 5 capas rigurosamente desacopladas:
> 1. En la cúspide, la **Capa de Presentación**, combinando renderizado PHP y peticiones asíncronas con JavaScript moderno (Fetch / AJAX).
> 2. En la entrada web, la carpeta `public/` que contiene el **Front Controller** en `index.php` y los microcontroladores RESTful en `public/api/`, protegidos por `.htaccess` para que ningún archivo interno sea accesible vía web.
> 3. La **Capa de Servicios de Dominio**, donde residen las reglas puras del negocio de telecomunicaciones sin acoplamiento con vistas ni bases de datos.
> 4. La **Capa de Contratos DAO**, compuesta por interfaces abstractas aplicando el Principio de Inversión de Dependencias (DIP).
> 5. Y en la base, la **Persistencia Relacional MySQL (InnoDB)** en Tercera Forma Normal (3FN), reforzada con triggers de auditoría inmutables.
>
> A continuación, mi compañero Kevin Cusi detallará el diseño orientado a objetos y los patrones de ingeniería implementados."*

---

### DIAPOSITIVA 5: DIAGRAMA DE CLASES UML (3:30 - 4:45)
**Visual**: Diagrama UML completo con distribución por colores (verde Singleton, ámbar Strategy, púrpura Policy, azul Servicios).
**Orador 2 (Kevin Cusi)**:
> *"Gracias, Rodrigo. *(Señalar el diagrama UML)* En pantalla observamos el Diagrama de Clases UML que modela la estructura orientada a objetos de nuestra solución:
> * En la parte superior central *(verde)*, implementamos el patrón **Singleton** en `DatabaseConnection`, asegurando una única instancia del pool de conexiones PDO durante el ciclo de vida de la petición HTTP, minimizando el consumo de sockets en el servidor.
> * A la izquierda *(ámbar)*, el patrón **Strategy** desacopla la lectura de planillas masivas mediante `SpreadsheetReaderInterface`, con implementaciones concretas para archivos CSV y XLSX creadas por `ReaderFactory`.
> * A la derecha *(púrpura)*, el patrón **Policy** y la fábrica `PolicyFactory` implementan el control de acceso granular (RBAC) para perfiles Administrador, Supervisor y Técnico.
> * En el centro *(azul)*, se sitúan los servicios de dominio como `PuertoBatchImportService` y `StatisticsService`.
> * Y en la parte inferior *(verde y azul)*, observamos las **5 Interfaces DAO** (`PuertoDaoInterface`, `SiteDaoInterface`, etc.) y sus correspondientes implementaciones MySQL concretas."*

---

### DIAPOSITIVA 6: PRINCIPIOS SOLID E INVERSIÓN DE DEPENDENCIAS (4:45 - 5:45)
**Visual**: Captura de código de `PuertoDaoInterface` y `MySqlPuertoDao` con transacción PDO.
**Orador 2 (Kevin Cusi)**:
> *"*(Señalar el código de la izquierda)* Aquí evidenciamos la aplicación rigurosa de los principios SOLID:
> Observen el **Principio de Inversión de Dependencias (DIP)**: los servicios de dominio nunca instancian `new MySqlPuertoDao()`. En su lugar, reciben mediante inyección de dependencias el contrato abstracto `PuertoDaoInterface`.
>
> Esto nos otorga dos ventajas críticas:
> Primero, **Desacoplamiento total**: si el día de mañana Telefónica decide migrar de MySQL a PostgreSQL u Oracle, solo creamos una nueva clase DAO sin tocar una sola línea de la lógica de negocio.
> Segundo, **Atomicidad ACID** *(señalar línea 16)*: el método `updateStatus` ejecuta un bloque `$this->pdo->beginTransaction()`. Si la base de datos detecta una colisión de hilo óptico o un corte de red, se dispara la captura de excepción y se ejecuta un `$this->pdo->rollBack()`, garantizando que jamás existan estados corruptos en la red de fibra."*

---

### DIAPOSITIVA 7: PATRÓN STRATEGY PARA INGESTA MASIVA (5:45 - 6:45)
**Visual**: Captura de código de `SpreadsheetReaderInterface`, `ReaderFactory` y `PuertoBatchImportService`.
**Orador 2 (Kevin Cusi)**:
> *"*(Señalar el código)* En esta diapositiva demostramos el cumplimiento del **Principio Abierto / Cerrado (OCP)** mediante el patrón **Strategy**:
> Cuando las contratistas de Telefónica entregan planillas de nuevos empalmes de cientos de puertos, el archivo puede venir en formato `.csv` o en libro Excel nativo `.xlsx`.
>
> Mediante la interfaz `SpreadsheetReaderInterface`, desacoplamos el algoritmo de lectura. La factoría `ReaderFactory` evalúa la extensión del archivo y entrega en tiempo de ejecución la estrategia adecuada (`CsvReader` o `XlsxReader`).
> El servicio `PuertoBatchImportService` procesa las filas sin preocuparse por el formato interno, valida los rangos de puertos y ejecuta la inserción masiva en una única transacción segura. Si en el futuro se requiere admitir archivos XML, el sistema está abierto a la extensión y cerrado a la modificación."*

---

### DIAPOSITIVA 8: CALIDAD TDD Y PRUEBAS AUTOMATIZADAS (6:45 - 7:45)
**Visual**: Terminal de PHPUnit ejecutando 32 tests al 100% en verde con 128 aserciones.
**Orador 2 (Kevin Cusi)**:
> *"*(Señalar la terminal en verde)* Uno de los requisitos más exigentes de la rúbrica es el enfoque **TDD (Test-Driven Development)**.
> Como evidencia irrefutable de la robustez de nuestro código, implementamos una suite automatizada con el framework **PHPUnit 10.5**.
>
> Ejecutamos **32 casos de prueba unitaria con 128 aserciones activas, logrando un 100% de éxito en apenas 74 milisegundos**.
> Probamos mediante dobles de prueba (Mocks) y bases de datos en memoria SQLite:
> * La confirmación y reversión transaccional en `MySqlPuertoDao`.
> * La detección y bloqueo pesimista ante colisiones de hilo óptico.
> * Las políticas de autorización RBAC y la validación timing-safe del token anti-CSRF.
> Esta suite garantiza que cualquier cambio futuro no genere regresiones.
>
> Doy el pase a mi compañera Diana Aguado para exponer la seguridad defensiva, interfaces y control de versiones."*

---

### DIAPOSITIVA 9: SEGURIDAD DEFENSIVA EN PROFUNDIDAD (7:45 - 8:45)
**Visual**: Captura de código de `Csrf.php` y `auth_login.php` con rate limiting.
**Orador 3 (Diana Aguado)**:
> *"Muchas gracias, Kevin. En el ámbito de telecomunicaciones, la seguridad de la información es un aspecto no negociable. Siguiendo las directrices del estándar internacional **OWASP Top 10**, implementamos controles defensivos en profundidad:
> 1. **Cero Inyección SQL (A03:2021)**: Todas las consultas PDO utilizan parámetros vinculados con tipo explícito (`PDO::PARAM_INT`, `PDO::PARAM_STR`), habiendo desactivado la emulación de sentencias preparadas.
> 2. **Criptografía de Contraseñas (A02:2021)**: Ninguna credencial se guarda en texto claro; aplicamos el algoritmo **BCrypt** con salt criptográfico automático.
> 3. **Mitigación Anti-CSRF** *(señalar código superior)*: Generamos tokens pseudoaleatorios criptográficamente seguros de 32 bytes con `random_bytes()` y los validamos mediante la función nativa `hash_equals()`, anulando ataques de canal lateral basados en tiempo (Timing Attacks).
> 4. **Rate Limiting Anti-Fuerza Bruta** *(señalar código inferior)*: En `api/auth_login.php` incorporamos un limitador persistente que bloquea la dirección IP y la cuenta tras 5 intentos fallidos, devolviendo el código estándar `HTTP 429 Too Many Requests`."*

---

### DIAPOSITIVA 10: INTERFACES GRÁFICAS Y UX/UI (8:45 - 9:45)
**Visual**: Cuadrícula con los 6 prototipos de interfaz y los 3 reportes gerenciales.
**Orador 3 (Diana Aguado)**:
> *"*(Señalar las tarjetas de interfaz)* Para garantizar que la solución cubra el 100% del alcance comprometido, construimos una experiencia de usuario responsiva y moderna fundamentada en las heurísticas de Jakob Nielsen:
> * **WF-01 y WF-02**: Pantalla de autenticación y el **Dashboard Principal**, el cual renderiza la matriz física del bastidor ODF con semáforos de disponibilidad (Disponible en verde, En Servicio en azul, Reservado en ámbar y Malogrado en rojo).
> * **WF-03**: Modal asíncrono que permite a los operadores modificar el estado del puerto, vincular el cable de fibra y registrar la orden de trabajo sin recargar la página.
> * **WF-04 y WF-05**: Vistas topológicas de centrales telefónicas (Sites) y la herramienta interactiva de carga masiva por lote con reporte visual de errores.
> * **WF-06 y Reportes**: Módulo RBAC de gestión de usuarios y exportación de reportes gerenciales de capacidad óptica y planillas de fusión para cuadrillas de campo."*

---

### DIAPOSITIVA 11: CONTROL DE VERSIONES GIT Y GITHUB (9:45 - 10:30)
**Visual**: Captura del repositorio GitHub con ramas main/develop, commits y tag v1.0.0.
**Orador 3 (Diana Aguado)**:
> *"*(Señalar la captura de GitHub)* El desarrollo colaborativo fue gobernado al 100% mediante **Git y GitHub**.
> Adoptamos el flujo de trabajo **GitFlow simplificado**:
> * Una rama `main` protegida, reservada exclusivamente para entregas validadas.
> * Una rama `develop` para la integración continua.
> * Y ramas `feature/*` donde cada integrante desarrolló sus módulos de forma aislada.
>
> Estandarizamos los mensajes bajo la convención **Conventional Commits**, vinculando cada confirmación con los paquetes de trabajo de nuestra EDT. Como se aprecia en la captura, se evidencia la autoría real y colaborativa de Rodrigo Layme, Kevin Cusi y mi persona, cerrando el hito de este avance bajo el tag de release `v1.0.0`."*

---

### DIAPOSITIVA 12: DOCUMENTACIÓN VIVA: PHPDOC Y ANEXOS TÉCNICOS (10:30 - 11:15)
**Visual**: Captura del tooltip flotante de VS Code y mención de Anexos A y B.
**Orador 3 (Diana Aguado)**:
> *"*(Señalar el editor en pantalla)* Para cumplir con el requerimiento de documentación empresarial y homologación Javadoc / PHPDoc, el código fuente cuenta con tipado estricto `declare(strict_types=1)` y bloques docstring formales con `@param`, `@return` y `@throws`.
>
> Esto permite que cualquier editor moderno brinde autocompletado inteligente, validación estática de tipos e inspección contextual de firmas.
> Además, hemos consolidado dos anexos técnicos independientes en formato PDF:
> * **Anexo A**: El manual DevOps con requisitos de servidor, guía de despliegue paso a paso, arquitectura de carpetas, catálogo de APIs RESTful y protocolo de observabilidad SRE (`/api/health.php`).
> * **Anexo B**: El diccionario técnico completo de clases, contratos DAO, servicios y entidades."*

---

### DIAPOSITIVA 13: CONCLUSIONES E IMPACTO EN EL NEGOCIO (11:15 - 12:00)
**Visual**: 4 tarjetas de conclusiones cuantitativas (100% cero colisiones, 85% menos tiempo).
**Orador 3 (Diana Aguado)**:
> *"Para concluir, los resultados técnicos y de negocio alcanzados son contundentes:
> 1. **100% de Eliminación de Colisiones Ópticas**: Las transacciones ACID y bloqueos pesimistas imposibilitan la duplicidad de hilos de fibra en la red.
> 2. **85% de Reducción en Tiempos de Consulta**: Lo que antes tomaba 45 minutos en planillas Excel, hoy se consulta y actualiza en menos de 10 segundos desde cualquier navegador.
> 3. **Trazabilidad Forense Absoluta**: Cada cambio queda auditado con fecha, hora exacta e identidad del operador.
> 4. **Arquitectura Escalable y Mantenible**: El desacoplamiento logrado mediante MVC, DAO, Strategy y SOLID garantiza que la solución está lista para evolucionar y sostener el crecimiento de la red de fibra óptica de Telefónica del Perú."*

---

### DIAPOSITIVA 14: RONDA DE PREGUNTAS (12:00 - Cierre)
**Visual**: Pantalla final con agradecimiento y apertura a preguntas.
**Orador 1 (Rodrigo Layme)**:
> *"Agradecemos profundamente su tiempo y atención. Quedamos a total disposición del profesor Rudy Chapoñan y del jurado calificador para responder a sus preguntas técnicas y realizar la demostración del sistema en vivo. Muchas gracias."*

---

## BANCO DE PREGUNTAS Y RESPUESTAS TÉCNICAS PARA LA DEFENSA

### Pregunta 1 del Jurado: *"¿Por qué utilizaron el patrón DAO en lugar de un ORM comercial pesado como Eloquent o Doctrine?"*
* **Respuesta Ganadora**:
  > *"Excelente pregunta, profesor. En entornos de telecomunicaciones como Telefónica, la prioridad crítica es el control absoluto de la concurrencia y el rendimiento en milisegundos. Un ORM pesado introduce sobrecarga de memoria e hidratación de objetos innecesaria. Al implementar el patrón **Data Access Object (DAO)** artesanalmente con interfaces abstractas (`PuertoDaoInterface`) y PDO nativo, logramos tres objetivos clave:
  > 1. Control fino de transacciones ACID y sentencias de bloqueo (`SELECT ... FOR UPDATE`) que los ORMs abstraen de forma compleja.
  > 2. Cero dependencias externas pesadas, manteniendo el código ligero y auditable.
  > 3. Cumplimiento estricto del Principio de Inversión de Dependencias (DIP), lo que nos permitió mockear la base de datos con SQLite en memoria y ejecutar 32 pruebas TDD en apenas 74 milisegundos."*

### Pregunta 2 del Jurado: *"La rúbrica menciona librerías Java como Apache POI, Google Guava, Apache Commons y Logback. ¿Cómo justifica su proyecto este criterio si está desarrollado en PHP?"*
* **Respuesta Ganadora**:
  > *"Efectivamente, profesor. En el Capítulo 4 (Sección 4.1, Tabla 4.1) y en el Anexo A del informe presentamos una **Matriz de Homologación Arquitectónica Formal**. Demostramos que las capacidades exigidas se implementaron con sus equivalentes de primer nivel empresarial:
  > * **Apache POI**: Se homologó con `PhpOffice\PhpSpreadsheet` y `CsvReader` mediante el patrón Strategy para ingesta masiva de planillas XLSX/CSV, implementando límites de memoria y protección contra Zip Bombs.
  > * **Google Guava**: Se homologó con `vlucas/phpdotenv` para inmutabilidad de variables de entorno y colecciones tipadas nativas de PHP 8.1+.
  > * **Apache Commons**: Se homologó con los módulos criptográficos nativos `random_bytes()`, `hash_equals()` y `password_hash()` con algoritmo BCrypt.
  > * **Logback**: Se homologó con bitácoras estructuradas en formato JSON en `storage/logs/` y triggers transaccionales en MySQL.
  > De esta manera, garantizamos la misma robustez, seguridad y rendimiento exigidos en la rúbrica."*

### Pregunta 3 del Jurado: *"¿Cómo previene el sistema que dos técnicos en campo asignen el mismo hilo de fibra al mismo tiempo?"*
* **Respuesta Ganadora**:
  > *"Se previene mediante una defensa en tres capas:
  > 1. **A nivel de Dominio**: El servicio `PuertoBatchImportService` y el controlador `update_puerto.php` invocan a `findThreadConflict()`, validando si el par cable-hilo ya está asociado a otro ODF de la sede.
  > 2. **A nivel de Persistencia DAO**: `MySqlPuertoDao` inicia una transacción atómica con `$pdo->beginTransaction()` y ejecuta `lockForUpdate()`, bloqueando temporalmente la fila en MySQL para que ninguna otra petición concurrente pueda leerla en estado inconsistente.
  > 3. **A nivel de Motor de Base de Datos**: La tabla `PUERTO` cuenta con una restricción de unicidad (`UNIQUE KEY uq_cable_hilo (idCableAlimentacion, numeroHilo)`). Si dos peticiones simultáneas intentaran persistir el mismo hilo, el motor relacional aborta la segunda transacción por violación de llave única y el DAO ejecuta automáticamente un `rollBack()`, retornando un error controlado HTTP 422 al frontend."*
