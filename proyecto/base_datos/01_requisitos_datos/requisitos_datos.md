# Trazabilidad de Requerimientos a Datos
**Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español**
| Campo | Valor |
|---|---|
| **Agente utilizado** | `database-specialist` |
| **Skill utilizado** | `database-schema-designer` |
| **Paso completado** | Paso 01 — Lectura y trazabilidad |
| **Flujo** | `02_database_workflow` — Paso 01 / Checkpoint `database-workflow.json` |
| **Estado del paso** | Pendiente de validación humana |
| **Fechas de referencia** | Análisis realizado el 2026-10-02 |
## 0. Fuentes de información consultadas
- `proyecto/requisitos/RF.md` — Requerimientos funcionales (RF-01 a RF-22)
- `proyecto/requisitos/RNF.md` — Requerimientos no funcionales (RNF-01 a RNF-19)
- `proyecto/requisitos/criterios_aceptacion.md` — Criterios de aceptación (CA-001 a CA-043 y CA-RNF-01 a CA-RNF-20)
- `proyecto/contexto/reglas_negocio.md` — Reglas de negocio (RN-01 a RN-35)
- `proyecto/contexto/restricciones.md` — Supuestos y restricciones (S-01 a S-06, RT-01 a RT-07, RO-01 a RO-07, DP-01 a DP-08)
- `proyecto/resultados/02_decision_scope.md` — Alcance de decisiones (D-01 a D-23)
- `proyecto/resultados/04_database_analysis.md` — Análisis de persistencia
- `proyecto/configuracion/perfil_carga.yaml` — Perfil de carga
- `proyecto/resultados/08_recomendacion_final.md` — Recomendación arquitectónica final
**Notas de trazabilidad documental:**
1. Se aplica la **decisión D-23**: cuando exista diferencia entre un documento derivado y su fuente normativa, prevalece la fuente normativa.
2. No se han seleccionado aún: lenguaje, framework, motor de base de datos, proveedor de nube, tecnología de autenticación ni mecanismo técnico de concurrencia.
3. **Estrategia de persistencia recomendada provisionalmente:** persistencia relacional transaccional con capacidad de evolución posterior. No constituye selección de motor.
---
## 1. Resumen de entidades de datos identificadas
A continuación se listan las entidades conceptuales que emergen de los requisitos. **En esta etapa se describe el significado de negocio de cada entidad; no se definen columnas, tipos ni scripts SQL** (eso corresponde a la etapa de modelo lógico/físico).
### 1.1 Paciente
Representa la identidad clínica del usuario que solicita atención. Es la entidad central de la agenda, ya que una cita existe para prestarle atención.
| Aspecto | Descripción | Fuente |
|---|---|---|
| **Propósito** | Identificar a la persona que recibirá la atención médica | RF-01 |
| **Identificador único** | Identificador propio de la entidad (clave primaria de la entidad `Paciente`) | CA-001 |
| **Atributos clave** | Nombre, apellidos, documento de identidad, fecha de nacimiento, teléfono, correo electrónico | RF-01 |
| **Restricción de presencia** | Nombre, apellidos, documento de identidad y teléfono son obligatorios | RF-01, CA-002 |
| **Relaciones** | 1:C con `Cita`; se consulta para historial y consultas de citas (RF-14, RF-15) | RN-01 |
| **Ciclo de vida** | Los datos del paciente deben conservarse con sus citas históricas | D-20, RN-22 |
### 1.2 Usuario
Representa la identidad digital utilizada para autenticación, autorización, roles y acceso al sistema. **Es una entidad distinta de Médico** (decisión D-06, RN-33).
| Aspecto | Descripción | Fuente |
|---|---|---|
| **Propósito** | Gestionar la identidad de acceso al sistema | RF-21, D-06 |
| **Atributos clave** | Credenciales de acceso (protegidas), estado activo/inactivo, roles asignados | RF-21, RNF-06 |
| **Regla de modelo** | Credenciales no almacenadas en texto plano | RNF-06 |
| **Relación con Médico** | Un Médico puede tener cero o una cuenta de Usuario asociada; una cuenta con rol Médico corresponde a un único Médico (RN-33, D-06) | D-06, RN-33 |
| **Relaciones** | 1:0..1 con `Médico`; 1:N con `Registro de auditoría` (cada acción queda asociada a un usuario responsable) | D-06, RF-22 |
### 1.3 Médico
Entidad de identidad profesional del prestador. **Separada de Usuario**: un Médico puede existir sin cuenta de Usuario (D-06, RN-33).
| Aspecto | Descripción | Fuente |
|---|---|---|
| **Propósito** | Representar al profesional que realiza la atención | RF-02, D-06 |
| **Atributos clave** | Información profesional para gestionar especialidades, horarios, disponibilidad, citas y agenda | RN-33 |
| **Gestión de estado** | Activar/desactivar el registro profesional sin eliminarlo (RF-02, RN-35) | D-06 |
| **Relaciones** | N:M con `Especialidad` (asociación), 1:N con `Horario`, 1:N con `Cita`, 1:0..1 con `Usuario` | RN-02, RF-02 |
### 1.4 Especialidad
Unidad organizacional de la especialidad médica.
| Aspecto | Descripción | Fuente |
|---|---|---|
| **Propósito** | Referenciar la especialidad médica (médicos y pacientes la consultan) | RF-03, RF-06 |
| **Gestión de estado** | Estado activo/inactivo; solo se muestran las activas | RF-03, CA-007 |
| **Relaciones** | N:M con `Médico` (relación muchos-a-muchos) | RF-03, RF-04, CA-005 |
| **Requisito de modelado** | Una especialidad debe poder relacionarse con uno o más médicos y viceversa | CA-005 |
### 1.5 Relación Médico–Especialidad (entidad intermedia de asociación)
Tabla de unión que materializa la relación muchos-a-muchos entre médicos y especialidades.
| Aspecto | Descripción | Fuente |
|---|---|---|
| **Propósito** | Permitir que un médico pertenezca a varias especialidades y una especialidad tenga varios médicos | RF-04, CA-005 |
| **Regla** | Cada médico debe estar asociado a al menos una especialidad activa | RN-02 |
### 1.6 Horario (bloque de disponibilidad)
Un bloque de tiempo que un médico declara disponible para atender. Puede portar información sobre su modalidad (presencial/virtual) según la decisión pendiente D-09.
| Aspecto | Descripción | Fuente |
|---|---|---|
| **Propósito** | Representar una franja horaria en la que un médico puede recibir pacientes | RF-05, RF-08, CA-009 |
| **Atributos clave** | Médico propietario, día de la semana / fecha, franja horaria (inicio/fin), estado (disponible/reservado/libre), modalidad | RF-05, RF-08, D-08, D-09 |
| **Reglas** | Solo se pueden reservar citas en horarios disponibles para el médico (RN-03); al confirmar, el horario queda ocupado para reservas incompatibles (RN-10) | RN-03, RN-10 |
| **Relaciones** | N:1 con `Médico`; 1:0..1 con `Cita` | — |
### 1.7 Cita
Entidad transaccional central del sistema. Una cita une a un paciente, un médico, un horario y una modalidad, y sigue un ciclo de estados.
| Aspecto | Descripción | Fuente |
|---|---|---|
| **Propósito** | Registrar una atención programada | RF-09 |
| **Relaciones obligatorias** | Paciente y médico son obligatorios (RN-01) | RN-01 |
| **Relaciones** | N:1 con `Paciente`, `Médico`, `Especialidad` y `Horario` | RF-09 |
| **Atributos clave** | Fecha y hora de la cita, estado, modalidad, enlace/información de acceso (para virtual), fechas de inicio y finalización de la atención | RF-09, RF-18, RF-19, RF-20 |
| **Ciclo de estados** | Programada → Confirmada → En atención → Finalizada, más los estados intermedios Cancelada y No asistida (RF-13, RN-07) | RF-13, RN-07 |
| **Reglas críticas** | No doble reserva para mismo médico, fecha y horario (RN-04); no superposición entre citas de un paciente (RN-05); verificar disponibilidad antes de confirmar (RN-06) | RN-04, RN-05, RN-06 |
| **Histórico** | Citas finalizadas, canceladas y no asistidas se conservan en el historial | RN-22, D-20 |
### 1.8 Registro de auditoría / historial de modificaciones
Registro asociado a las modificaciones de una cita. **Se mantiene separado de los registros técnicos de observabilidad** (RNF-17 vs RNF-18).
| Aspecto | Descripción | Fuente |
|---|---|---|
| **Propósito** | Conservar el rastro de las modificaciones realizadas sobre una cita | RF-22, RNF-18, D-12 |
| **Campos mínimos** | Fecha, hora, usuario responsable, acción realizada, valor anterior, valor actualizado (seis campos) | RF-22, RNF-18, D-12, CA-037 |
| **Reglas de conservación** | No eliminar automáticamente al cancelar o finalizar la cita; conservar mínimo 5 años | RNF-18, D-20, RN-25, CA-039 |
| **Relaciones** | N:1 con `Cita`; N:1 con `Usuario` (responsable) | RF-22, RNF-18 |
| **Requisito de diseño** | Debe permitir identificar claramente el valor anterior y el nuevo valor | RN-24, CA-038 |
### 1.9 Información de atención virtual
Datos vinculados a una cita cuando la modalidad es virtual, incluyendo el enlace y la información de acceso al servicio externo.
| Aspecto | Descripción | Fuente |
|---|---|---|
| **Propósito** | Almacenar el enlace y las instrucciones de acceso al servicio externo de atención virtual | RF-19, RN-18, D-01 |
| **Atributos clave** | Identificador de la cita, identificador externo de sesión (cuando corresponda), enlace de acceso, estado de disponibilidad de la integración, información del incidente (fecha/hora, detalles) | D-01, D-19, RN-30 |
| **Regla de ausencia** | Una cita presencial no requiere información de acceso al servicio virtual | RN-19, CA-030 |
| **Contingencia** | Ante falla del servicio externo, conservar paciente, médico, fecha/hora, modalidad, estado, fecha/hora del incidente e información técnica | RN-30, D-19 |
### 1.10 Parámetros de configuración del sistema
Valores que condicionan el comportamiento de las reglas de negocio y que, según RN-28, deben ser configurables sin modificar la lógica principal.
| Aspecto | Descripción | Fuente |
|---|---|---|
| **Tipos de parámetros** | Tiempo mínimo para cancelar/reprogramar; tolerancia para marcar no asistida; anticipación máxima de reserva; tiempo de inactividad de sesión (configurable, 15 min iniciales) | RN-26, RN-27, RN-28, D-02, D-03, D-04, D-13 |
| **Implicación de modelo** | Deben almacenarse en una configuración accesible para las validaciones, sin endurecerlos en el código | RN-28, RNF-07 |
---
## 2. Trazabilidad RF/RNF → Necesidades de datos
Cada requerimiento se traduce en necesidades de persistencia: datos a almacenar, relaciones, restricciones y patrones de acceso.
### 2.1 Flujo de gestión de pacientes y referenciales
| Req. | Texto resumido | ¿Qué datos deben almacenarse? | Relaciones | Restricciones de integridad | Patrones de acceso |
|---|---|---|---|---|---|
| **RF-01** | Registro de pacientes | Paciente (nombre, apellidos, DNI, DOB, teléfono, email) | 1:N con Cita | DNI como dato identificable único; campos obligatorios no nulos | Buscar por paciente (consulta de historial) |
| **RF-02** | Registro/consulta/modificación/estado de médicos | Médico (info profesional, estado activo) + Especialidad (RF-03) | 1:N con Horarios, Citas; N:N con Especialidad | ON DELETE RESTRICT (RN-35 prohíbe borrar médico por citas/auditoría); estado activo/inactivo | Consultar médicos activos por especialidad (RF-07) |
| **RF-03** | Gestión de especialidades activas | Especialidad (estado) | N:M con Médico | Solo especialidades activas se consultan; CA-007 | Filtrar especialidades activas con médicos asociados (RF-06) |
| **RF-04** | Asociación médico–especialidad | Tabla de unión (Médico–Especialidad) | N:M | Cada médico debe tener ≥1 especialidad activa (RN-02); la tabla debe garantizar unicidad del par | Listado de médicos por especialidad (RF-07) |
| **RF-05** | Definición de días y bloques horarios | Horario (médico, día, franja, estado) | N:1 con Médico | Validar que el bloque no se solape con otro del mismo médico; modalidad según D-08/D-09 | Agenda del médico por días (RF-16), disponibilidad (RF-08) |
### 2.2 Flujo de gestión de citas
| Req. | Texto resumido | ¿Qué datos deben almacenarse? | Relaciones | Restricciones de integridad | Patrones de acceso |
|---|---|---|---|---|---|
| **RF-06** | Consultar especialidades activas con médicos | Especialidad + conteo/flag de médicos | N:M con Médico | Solo activas (estado) | `SELECT especialidades WHERE activo` |
| **RF-07** | Consultar médicos activos con disponibilidad | Médico + flag de disponibilidad | 1:N Horario, 1:N Cita | Estado activo + existencia de horarios | Agenda del médico (RF-08) |
| **RF-08** | Consultar horarios futuros disponibles | Horario (estado, fecha, horario) | 1:N con Cita | Excluir horarios ocupados por citas; ventana máxima de reserva pendiente (D-04) | Disponibilidad por médico + fecha (ver §7) |
| **RF-09** | Reservar cita | Cita (paciente, médico, especialidad, horario, modalidad) | N:1 con Paciente/Médico/Horario/Especialidad | **Doble reserva imposible**: único médico+fecha+horario (RN-04, RNF-11); no superposición de citas del paciente (RN-05, D-11) | Verificación de disponibilidad atómica (D-21) |
| **RF-10** | Registro de cita por admisión | Cita + referencia al usuario de admisión | 1:N con Usuario | Mismas reglas de disponibilidad/superposición/concurrencia que paciente (RN-16) | Registro con usuario responsable |
| **RF-11** | Reprogramar cita | Cita (nuevo horario, viejo horario liberado) | 1:N con Usuario | **Atomicidad**: reservar nuevo horario y liberar el anterior en una sola transacción (RN-12, D-21) | Lectura de agenda + confirmación atómica |
| **RF-12** | Cancelar cita | Cita (estado) + liberación del horario | 1:1 con Horario | Liberar el horario en la misma transacción (RN-13); políticas de tiempo pendientes (D-02) | Actualización de estado atómica |
| **RF-13** | Gestión de estados | Cita (estado) | Enumeración de estados | Transiciones permitidas: solo Programada→Confirmada, Programada→Cancelada, Confirmada→En atención, Confirmada→Cancelada, Confirmada→No asistida, En atención→Finalizada; canceladas/finalizadas no revierten (RF-13, RN-07, RN-08, RN-09, CA-019..CA-021) | Listado según estado (RF-14..RF-17) |
| **RF-14** | Consultar citas vigentes del paciente | Cita filtrada por paciente | N:1 con Paciente | Solo el propio paciente (RN-14) | `WHERE paciente_id = ? AND estado IN (programada, confirmada, en_atencion)` |
| **RF-15** | Historial del paciente | Cita + historial | N:1 con Paciente | Conservar 5 años (RN-22, D-20) | Filtrar por fecha y estado (RF-15, CA-024) |
| **RF-16** | Agenda del médico | Cita filtrada por médico | N:1 con Médico | Solo citas del médico (RN-15) | `WHERE medico_id = ?` con filtros por fecha/estado (CA-026) |
| **RF-17** | Disponibilidad para admisión | Horario + estado | 1:N con Cita | Especialidades habilitadas (RO-06) | Disponibilidad de médicos/horarios (RF-08) |
### 2.3 Atención presencial y virtual
| Req. | Texto resumido | ¿Qué datos deben almacenarse? | Relaciones | Restricciones de integridad | Patrones de acceso |
|---|---|---|---|---|---|
| **RF-18** | Identificar modalidad en la cita | Cita (modalidad) + habilitación por médico/especialidad | N:1 con Médico/Especialidad | Modalidad disponible solo si está habilitada para el médico y la especialidad (RO-06, RF-18); decisión pendiente D-08/D-09 | Filtrar citas por modalidad (RF-18, CA-028) |
| **RF-19** | Información de acceso virtual | Atención virtual (enlace, ID externo, estado) | 1:1 con Cita (condicional) | Presencial no requiere acceso (RN-19, CA-030) | Lectura de enlace para virtual (RF-19, CA-029) |
### 2.4 Registro de atención
| Req. | Texto resumido | ¿Qué datos deben almacenarse? | Relaciones | Restricciones de integridad | Patrones de acceso |
|---|---|---|---|---|---|
| **RF-20** | Inicio y finalización de la atención | Cita (inicio, finalización, estados intermedios) | N:1 con Médico (quien registra) | Orden obligatorio: Confirmada→En atención→Finalizada (RN-20, CA-031..033); conservar fechas | Consulta de citas en atención (RF-16) |
### 2.5 Usuarios, roles y auditoría
| Req. | Texto resumido | ¿Qué datos deben almacenarse? | Relaciones | Restricciones de integridad | Patrones de acceso |
|---|---|---|---|---|---|
| **RF-21** | Gestión de usuarios y roles | Usuario (credenciales hash, estado) + Roles + asignación | 1:0..1 con Médico; 1:N con Registro de auditoría | Roles: paciente, médico, admisión, administrador (RF-21, RNF-05); credenciales no en texto plano (RNF-06) | RBAC en cada operación (RNF-05) |
| **RF-22** | Historial de modificaciones | Registro de auditoría (6 campos mínimos) | N:1 con Cita; N:1 con Usuario | No borrar al cancelar/finalizar; 5 años (RNF-18, D-20) | Consulta del historial de una cita (RF-22, CA-037, CA-038) |
### 2.6 Requerimientos no funcionales → necesidades de persistencia
| Req. | Texto resumido | Necesidad de persistencia | Implicación de diseño |
|---|---|---|---|
| **RNF-01** | Respuesta ≤ 3 s hasta 100 concurrentes | Consultas de especialidades, médicos, horarios, reservas, reprogramación, cancelación | Índices en los patrones de acceso (§7); diseño de concurrencia con el menor bloqueo posible (R-DB-02) |
| **RNF-02** | 100 concurrentes, 500 t/h, pico 1.000 t/h | Volumen de escritura controlado; concurrencia en reserva | Modelado optimizado para la carga inicial (D-18) |
| **RNF-03** | Evolución a 5.000 t/h (10×) | Arquitectura que permita índices, réplicas, particionamiento, separación de cargas | Evolución progresiva; no CQRS/híbrido en v1 (04-DB, Alternativa D) |
| **RNF-04** | Autenticación para roles | Usuario autenticado antes de operaciones protegidas | Auditoría con `usuario_responsable` |
| **RNF-05** | Restricción por rol | Permisos por rol en cada operación | Tabla de roles/permisos o columnas de rol en Usuario |
| **RNF-06** | Protección de datos personales | Encriptación de credenciales, transmisión segura, restricción por roles | Hash de credenciales (no texto plano, CA-RNF-07); cifrado en tránsito (CA-RNF-08) |
| **RNF-07** | Sesión expira a los 15 min (configurable) | Parámetro de tiempo de inactividad | Parámetro de configuración configurable (D-13) |
| **RNF-08** | Disponibilidad ≥ 99 % | Redundancia, sin punto único de fallo (D-22) | Diseño de infraestructura, no de esquema |
| **RNF-09** | RPO ≤ 60 min | Respaldo periódico verificable | Política de backup (D-22) |
| **RNF-10** | RTO ≤ 120 min | Procedimiento de restauración documentado | Procedimiento de recuperación (D-22) |
| **RNF-11** | Sin doble reserva simultánea | Operación atómica de verificación + confirmación | Restricción de integridad declarativa como última línea de defensa; mecanismo pendiente D-21 |
| **RNF-12** | Validación previa de datos | Presencia, formato, consistencia | CHECKs/validación (D-14 pendiente) |
| **RNF-13** | Usabilidad multiplataforma | Sin impacto en persistencia | N/A (capa de presentación) |
| **RNF-14** | Prueba de usabilidad | Sin impacto en persistencia | N/A |
| **RNF-15** | Accesibilidad WCAG 2.1 AA | Sin impacto en persistencia | N/A |
| **RNF-16** | Modularidad | Límites claros entre módulos (pacientes, médicos, especialidades, horarios, citas, usuarios/roles, auditoría, atención virtual) | Separación lógica de capas/tablas por módulo (04-DB §2) |
| **RNF-17** | Observabilidad | Eventos operativos (errores, intentos fallidos, fallas críticas, errores de integración) | Tabla de logs técnica **separada conceptualmente** de auditoría (D-12) |
| **RNF-18** | Auditoría de modificaciones de citas | 6 campos mínimos; no eliminar al cancelar/finalizar; 5 años | Tabla de auditoría histórica independiente |
| **RNF-19** | Compatibilidad de navegadores | Sin impacto en persistencia | N/A |
---
## 3. Reglas de negocio que impactan el modelo de datos
Cada regla que afecta directamente al diseño de datos se detalla con su implicación y la restricción/constraint derivada.
| ID | Regla (resumen) | Implicación en el modelo de datos | Restricción / constraint derivada |
|---|---|---|---|
| **RN-01** | Cada cita asociada a paciente y médico | Cita tiene FK obligatorias a Paciente y Médico | `NOT NULL` en FKs; integridad referencial |
| **RN-02** | Cada médico ≥1 especialidad activa | Relación muchos-a-muchos Médico–Especialidad | Verificación antes de permitir médico sin especialidad |
| **RN-03** | Cita solo en horario disponible | Horario con estado; la cita solo apunta a horarios disponibles | Restricción que impide apuntar a horario no disponible |
| **RN-04** | Máximo una cita por médico, fecha y horario | Unicidad compuesta sobre horario (o sobre médico+fecha+horario) | **UNIQUE compuesta** (última línea de defensa) |
| **RN-05** | No superposición entre citas de un paciente | Verificación de solapamiento de rangos de fecha/hora entre citas del mismo paciente | Restricción CHECK o validación de aplicación |
| **RN-06** | Verificar disponibilidad antes de confirmar | Operación de reserva en transacción atómica | Transacción unitaria (D-21) |
| **RN-07** | Estados válidos de cita | Columna de estado con valores acotados | Enumeración: {Programada, Confirmada, En atención, Finalizada, Cancelada, No asistida} |
| **RN-08** | Transiciones válidas | Máquina de estados sobre la cita | Restricción de transición o validación de aplicación (RF-13) |
| **RN-09** | Cancelada/Finalizada no revierte | Restricción que bloquea retroceso | CHECK o validación de aplicación |
| **RN-10** | Horario ocupado al confirmar | Estado del horario cambia a ocupado | Actualización atómica de horario |
| **RN-11** | Reprogramación solo a horario disponible | Nuevo horario libre antes de confirmar | Verificación + transacción atómica |
| **RN-12** | Nuevo horario reservado, anterior liberado (todo o nada) | Dos recursos en una transacción | Transacción con rollback a la izquierda |
| **RN-13** | Cancelación libera el horario | Estado del horario libre en la misma transacción | Atomicidad de estado + liberación |
| **RN-14** | Paciente solo gestiona sus citas | `paciente_id` asociado al usuario autenticado | Filtrado obligatorio en consultas (CA-022) |
| **RN-15** | Médico solo agenda propia | `medico_id` del usuario autenticado | Filtrado obligatorio en consultas (CA-025) |
| **RN-16** | Admisión con permisos, mismas reglas | Operación de admisión registrada con usuario responsable | Roles/permisos; mismas restricciones de RN-03/05/06/11/12/13 |
| **RN-17** | Admin gestiona pacientes, médicos, especialidades, usuarios, roles, configuraciones | Tablas referenciales y de configuración accesibles | Roles de administrador |
| **RN-18** | Cita virtual identificada + acceso | Cita con campo modalidad; datos de acceso condicionales | Columna `modalidad` y tabla/atributo de acceso (D-01) |
| **RN-19** | Cita presencial no requiere acceso | Campo de acceso nulo/OPTIONAL para presencial | Condición sobre `modalidad` |
| **RN-20** | Finalizar solo tras En atención; registrar inicio/fin | Columnas `hora_inicio` y `hora_fin` en Cita | Transiciones estrictas (CA-031..033) |
| **RN-21** | No asistida por tolerancia; falla virtual no la causa | Campo de no asistida; no automático por falla externa | Parámetro de tolerancia (D-03); registro de incidente (RN-30) |
| **RN-22** | Conservar historial 5 años | Citas finalizadas/canceladas/no asistidas conservadas | Política D-20 |
| **RN-23..RN-25** | Auditoría de modificaciones (6 campos, no borrar, 5 años) | Tabla de auditoría con 6 campos mínimos | Restricción de los 6 campos; conservación D-20 |
| **RN-26..RN-28** | Políticas temporales configurables | Parámetros de configuración | Columnas de configuración sin endurecer en código |
| **RN-29..RN-32** | Contingencia virtual: no borrar/cancelar/auto-no-asistido; conservar incidente | Tabla/atributos para registro de incidente (D-19) | Conservación del estado; registro de incidente |
| **RN-33..RN-35** | Médico ≠ Usuario; 0..1 relación; desactivación no elimina | Tablas separadas; FK opcional; ON DELETE RESTRICT | Relación opcional y protecciones de integridad (D-06) |
**Relación explícita RF ↔ RN sobre integridad de cita:**
- RF-13 ↔ RN-07, RN-08, RN-09: ciclo de estados y restricciones de transición.
- RF-14, RF-15 ↔ RN-01, RN-14, RN-22: consulta y conservación del historial.
- RF-18 ↔ RN-18, RN-19: modalidad y acceso.
- RF-20 ↔ RN-20: inicio/fin de atención.
---
## 4. Requisitos de auditoría y conservación
### 4.1 Auditoría funcional (RF-22, RNF-18, D-12, RN-23..RN-25)
**Campo de trazabilidad:** RF-22 → RNF-18 → D-12 → CA-037, CA-038 → RN-23..RN-25 → 04-DB §8.1.
**Datos que deben auditarse:** Todo cambio relevante sobre una cita. **Los seis campos mínimos obligatorios** son:
1. Fecha del evento
2. Hora del evento
3. Usuario responsable (FK a Usuario)
4. Acción realizada
5. Valor anterior
6. Valor actualizado
**Implicaciones en el diseño:**
| Necesidad | Implicación en el modelo de datos |
|---|---|
| Identificar el responsable | FK `usuario_responsable_id` → Usuario |
| Separar auditoría de observabilidad | Tabla distinta de los logs técnicos de RNF-17 (04-DB §8.1, D-12) |
| Permitir identificar el cambio | Campos `valor_anterior` y `valor_actualizado` (JSON o texto según detalle requerido; D-12 define mínimos) |
| Evitar borrado accidental | La auditoría **no se elimina** al cancelar/finalizar la cita (RNF-18, RN-25, CA-039, D-20) |
### 4.2 Conservación / retención (RNF-18, D-20, RN-22, RN-25, CA-039)
**Campo de trazabilidad:** RNF-18 → D-20 → RN-22, RN-25 → CA-039 → 04-DB §8.5.
| Elemento | Detalle | Fuente |
|---|---|---|
| **Citas históricas** | Finalizadas, canceladas y no asistidas | RN-22, D-20 |
| **Registros de auditoría** | Historial de modificaciones de citas | RNF-18, D-20, RN-25 |
| **Periodo mínimo de conservación** | **5 años** desde la fecha correspondiente a la cita / fecha del evento | RNF-18, RN-22, RN-25, D-20 |
| **Durante el periodo** | No eliminación automática; disponibles para consulta autorizada; mantienen relación con la cita; conservan campos de D-12 | D-20 |
| **Finalizado el periodo** | Pueden mantenerse, archivarse o eliminarse mediante procedimiento autorizado | D-20 |
| **Carácter del valor** | Décisión académica; configurable y sustituible por política institucional | RNF-18, RN-25, D-20 |
| **Separación conceptual** | Auditoría ≠ logs de observabilidad ≠ respaldos (backup) | RNF-17, RNF-18, 04-DB §8 |
**Conclusión de conservación:** Las tablas de cita e historial de auditoría deben conservar todos los registros; no debe implementarse eliminación automática por fecha de vida, y el periodo de 5 años debe ser parametrizable.
---
## 5. Requisitos de concurrencia y atomicidad
### 5.1 Requisitos relacionados
- **RNF-11:** un mismo horario no reservado simultáneamente por más de un paciente para el mismo médico; verificación y confirmación atómicas.
- **D-21:** confirmación atómica; validación final + confirmación en una sola operación transaccional; rechazo con mensaje de "horario no disponible".
- **RN-04, RN-06:** una cita por médico+fecha+horario; verificación previa a la confirmación.
- **CA-012, CA-013, CA-RNF-13:** criterio de aceptación de concurrencia.
**Campo de trazabilidad:** RF-09 ↔ RNF-11 ↔ D-21 ↔ RN-04, RN-06 ↔ CA-012, CA-RNF-13 ↔ 04-DB §4, §6.
### 5.2 Operaciones que deben ser atómicas
| Operación | Acciones dentro de la misma transacción | Liberación/riesgo si falla |
|---|---|---|
| **Reserva (RF-09)** | 1. Verificar disponibilidad del horario; 2. Reservar la cita; 3. Marcar horario ocupado; 4. Registrar auditoría; 5. Confirmar | Rollback completo; el horario no queda ocupado |
| **Reprogramación (RF-11, RN-12)** | 1. Verificar nuevo horario disponible; 2. Reservar en nuevo horario; 3. Liberar horario anterior (solo si el nuevo se confirmó) | Rollback; la cita no queda sin horario ni con ambos ocupados |
| **Cancelación (RF-12, RN-13)** | 1. Cambiar estado a Cancelada; 2. Liberar horario; 3. Registrar auditoría | Rollback; el horario no se libera parcialmente |
| **Inicio/fin de atención (RF-20)** | 1. Cambio de estado; 2. Registrar fecha/hora; 3. Registrar auditoría | Rollback; estado consistente |
### 5.3 Prevención de dobles reservas
| Nivel | Mecanismo | Responsable |
|---|---|---|
| **Aplicación (capa de negocio)** | Verificación de disponibilidad antes de confirmar (RN-06, CA-011, D-21); verificación de no superposición (D-11, RN-05) | Rápida, mejora la experiencia |
| **Persistencia (última línea de defensa)** | Restricción de unicidad compuesta sobre el horario (médico + fecha + horario) + transacción | RNF-11, RN-04, R-DB-01 |
| **Bloqueo** | Mecanismo a definir: bloqueo de fila, control optimista, restricciones únicas, nivel de aislamiento (D-21, 04-DB §6) | Pendiente técnico |
**Regla crítica:** nunca dos citas incompatibles para el mismo médico, fecha y horario, incluso bajo concurrencia. El mecanismo técnico específico (lock pesimista vs optimista) se decidirá en el Paso 06 (selección de DBMS) y el Paso 12 (transacciones y concurrencia), validado bajo la carga de RNF-02.
---
## 6. Decisiones pendientes que afectan el modelo de datos
Las siguientes decisiones (del documento de decisiones de alcance) tienen implicación directa en el diseño del modelo. **Todas están marcadas como Pendientes (a excepción de D-06, D-11, D-12 que ya están resueltas y se documentaron en §1).**
| Decisión | Título | RF/RNF/RN relacionados | Implicación en el modelo de datos |
|---|---|---|---|
| **D-02** | Políticas de cancelación y reprogramación | RF-11, RF-12, RN-11, RN-12, RN-26, DP-05 | Definir tiempos mínimos configurables; el modelo necesita parámetros de tiempo mínimo para cancelar/reprogramar almacenados en configuración (no en código). |
| **D-03** | Tiempo de tolerancia para no asistencia | RF-13, RN-21, DP-06 | Parámetro de tolerancia configurado; el modelo necesita un mecanismo para declarar la cita como No asistida cuando transcurra el tiempo (quién autoriza el cambio). |
| **D-04** | Anticipación máxima para reservar | RF-08, RN-11, DP-08 | Restricción de ventana máxima de reserva; el modelo debe limitar las consultas y reservas al rango máximo configurado. |
| **D-07** | Desactivación de médicos con citas futuras | RF-02, RF-05, RN-02, RN-35 | Determinar ON DELETE y comportamiento al desactivar: impedir desactivación, mantener citas, o reprogramar previamente. Afecta la FK Médico–Cita y la política de estado. |
| **D-08** | Habilitación de modalidad virtual | RF-05, RF-18, RN-18, RN-19, RO-06 | Definir el nivel de habilitación (médico, especialidad, médico–especialidad, bloque horario o combinación). Afecta dónde se almacena el flag de modalidad disponible. |
| **D-09** | Modalidad asociada a los horarios | RF-05, RF-07, RF-08, RF-18, RN-18, RO-06 | Definir si un médico puede tener horarios de modalidades mixtas y si la modalidad es atributo del bloque horario. Afecta al modelo de `Horario`. |
| **D-10** | Permisos del personal de admisión | RF-10, RF-11, RF-12, RN-16, RO-02 | Definir el alcance exacto de permisos (registro, reprogramación, cancelación, consulta). Afecta al modelo de roles/permisos y a las restricciones de acceso. |
| **D-14** | Reglas de validación de datos | RF-01, RNF-12, CA-001, CA-002 | Definir formatos aceptados para documento de identidad, nombre/apellidos, teléfono, correo, fecha de nacimiento (obligatorios, opcionales, formatos). Afecta a CHECKs y a los tipos de columna. |
**Observación:** D-02, D-03, D-04, D-08, D-09, D-10 y D-14 permanecen **Pendientes** en `02_decision_scope.md`; por tanto, el modelo de datos debe ser lo suficientemente flexible para admitir sus resoluciones futuras (campos configurables, flags de modalidad, políticas temporales) sin reingeniería.
---
## 7. Patrones de acceso identificados
Los siguientes patrones de consulta frecuentes deben optimizarse con índices en la etapa de índices. Están listados en el análisis de persistencia (§10) y se derivan de RF-06 a RF-17.
| # | Patrón de acceso | Requerimientos asociados | Atributos candidatos para índice |
|---|---|---|---|
| 1 | **Consulta de disponibilidad por médico y fecha** | RF-08, CA-009, D-04 | `horario(medico_id, fecha)` + estado |
| 2 | **Agenda del médico** | RF-16, CA-026, RF-07, RF-17 | `cita(medico_id, fecha, estado)` |
| 3 | **Citas del paciente** | RF-14, CA-022, RF-15 | `cita(paciente_id, estado, fecha)` |
| 4 | **Historial por paciente y fecha** | RF-15, CA-024, RN-22 | `cita(paciente_id, fecha, estado)` + conservacion |
| 5 | **Búsqueda de horarios libres** | RF-08, RF-17, CA-027 | `horario(medico_id, fecha, estado=disponible)` |
**Consideraciones de indexación (sin generar SQL aún):**
- Índices en claves foráneas (FK) para acelerar JOINs: `cita.paciente_id`, `cita.medico_id`, `cita.horario_id`, `cita.usuario_responsable_id`, `horario.medico_id`, `cita_especialidad.*`.
- Índices compuestos ordenados según selectividad de la consulta.
- Índices parciales si la mayoría de consultas filtran por `estado = activo`.
- Evaluar particionamiento histórico para la tabla de citas y auditoría a medida que crezca el volumen bajo la política de 5 años (04-DB R-DB-03).
---
## Resumen de restricciones críticas del modelo
| Categoría | Restricción | Fuente |
|---|---|---|
| Integridad referencial | Cita: paciente y médico obligatorios; horario pertenece al médico | RN-01, RN-03 |
| Unicidad | Un horario (médico + fecha + hora) no puede tener dos citas incompatibles | RN-04, RNF-11, D-21 |
| Superposición | Un paciente no puede tener citas superpuestas | RN-05, D-11 |
| Transiciones | Ciclo de estados acotado (RF-13, RN-07..09) | RF-13, RN-08, CA-019 |
| Auditoría | 6 campos mínimos; conservación ≥ 5 años; no borrar al cancelar/finalizar | RF-22, RNF-18, D-12, D-20 |
| Separación conceptual | Auditoría ≠ observabilidad ≠ backup | RNF-17, RNF-18, 04-DB §8 |
| Modelo Médico–Usuario | Médico ≠ Usuario; relación 0..1/1; desactivación no elimina | D-06, RN-33..35 |
| Concurrencia | Operación atómica de reserva; última línea de defensa en persistencia | RNF-11, D-21 |
| Escalabilidad | Evolución 10× sin cambiar RF/RN/comportamiento | RNF-03, D-18 |
| Recuperación | RPO ≤ 60 min, RTO ≤ 120 min, disponibilidad ≥ 99 % | RNF-08..10, D-22 |
---
**Conclusión del paso.**
El Paso 01 concluye con un inventario de entidades conceptuales y su trazabilidad hacia los requerimientos normativos. Las decisiones de diseño detalladas se desarrollarán en los pasos posteriores correspondientes del workflow: modelo conceptual (Paso 02), diagrama E-R (Paso 03), modelo lógico (Paso 04), normalización (Paso 05), selección del DBMS (Paso 06), integridad (Paso 08), seguridad (Paso 09), auditoría y versionamiento (Paso 10), índices y rendimiento (Paso 11), transacciones y concurrencia (Paso 12), migraciones (Paso 13), revisión DBA (Paso 14) y generación SQL (Paso 15). En esta etapa se mantiene la trazabilidad RF/RNF/RN → necesidades de datos, sin generar SQL.
**Estado del checkpoint:** Paso 01 completado (pendiente validación humana).