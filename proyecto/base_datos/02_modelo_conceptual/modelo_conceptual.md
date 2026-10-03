# Modelo Conceptual

**Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español**

| Campo | Valor |

|---|---|

| **Agente utilizado** | `database-engineer` |

| **Skill utilizado** | `database-schema-designer` |

| **Paso completado** | Paso 02 — Modelo conceptual |

| **Flujo** | `02_database_workflow` — Paso 02 / Checkpoint `database-workflow.json` |

| **Estado del paso** | Pendiente de validación humana |

| **Fechas de referencia** | Análisis realizado el 2026-10-02 |

| **Artefacto de entrada principal** | `proyecto/base_datos/01_requisitos_datos/requisitos_datos.md` |

| **Fuentes normativas aplicadas** | RF-01..RF-22; RNF-01..RNF-19; RN-01..RN-35; D-01..D-23; S-01..S-06, RT-01..RT-07, RO-01..RO-07, DP-01..DP-08 |

| **Decisión de trazabilidad** | D-23: prevalece la fuente normativa ante diferencias con documentos derivados. |

---

## 0. Alcance y notas metodológicas

El presente documento define el **modelo conceptual** del sistema: entidades, atributos significativos, relaciones, cardinalidades y reglas de negocio que restringen el modelo.

Este modelo es **independiente del motor de datos**:

- No se definen tipos físicos de columnas (no se mencionan INT, VARCHAR, TIMESTAMP, etc.).

- No se genera SQL (no CREATE TABLE, INSERT, etc.).

- No se selecciona motor de base de datos.

El modelo conceptual sirve de base para el Paso 03 (diagrama E-R) y el Paso 04 (modelo lógico), que introducirán decisiones sobre tipo de dato, claves primarias, restricciones físicas y motor.

---

## 1. Reglas conceptuales de identidad y separación

Antes de detallar las entidades, se registran las decisiones que afectan la estructura del modelo:

| Regla | Decisión / Regla de negocio | Fuente |

|---|---|---|

| Médico ≠ Usuario | Médico y Usuario son conceptos separados. Un Médico puede existir sin cuenta de Usuario. Relación 0..1. | D-06, RN-33, RN-34 |

| Auditoría ≠ Observabilidad | El historial de auditoría de citas es conceptualmente distinto a los registros técnicos de observabilidad (RNF-17). | D-12, RNF-17, RNF-18 |

| Conservación histórica | Las citas finalizadas, canceladas y no asistidas, y los registros de auditoría, se conservan ≥ 5 años. No se eliminan al cancelar/finalizar. | D-20, RN-22, RN-25, RNF-18 |

| Concurrencia atómica | La confirmación de una reserva es una operación atómica: verificación + confirmación en una sola transacción. La última línea de defensa está en la persistencia. | D-21, RNF-11, RN-04, RN-06 |

---

## 2. Entidades principales

### 2.1 Paciente

**Propósito:** Representar la identidad clínica de la persona que solicita la atención médica. Es la entidad central de la agenda: toda cita existe para prestarle atención a un paciente.

| Aspecto | Descripción | Fuente |

|---|---|---|

| **Identificador** | Identificador propio de la entidad (clave primaria conceptual) | CA-001 |

| **Atributos significativos** | Nombre, apellidos, documento de identidad, fecha de nacimiento, teléfono, correo electrónico | RF-01 |

| **Restricción de presencia** | Nombre, apellidos, documento de identidad y teléfono son obligatorios | RF-01, CA-002 |

| **Regla de negocio** | El paciente solo puede gestionar sus propias citas | RN-14 |

| **Relación con Usuario** | 0..1:0..1: un paciente puede existir sin cuenta de usuario; si dispone de una cuenta con rol paciente, esta debe corresponder al paciente que gestiona sus citas | RN-14, RF-10, RF-14, RF-15 |

| **Conservación** | Se conservan con las citas históricas (no se borran las citas del paciente) | D-20, RN-22 |

### 2.2 Médico

**Propósito:** Identidad profesional del prestador de atención. Es la entidad que posee horarios, especialidades, agenda y que ejecuta la atención.

| Aspecto | Descripción | Fuente |

|---|---|---|

| **Identificador** | Identificador propio de la entidad (clave primaria conceptual) | — |

| **Atributos significativos** | Información profesional para gestionar especialidades, horarios, disponibilidad, citas y agenda | RN-33 |

| **Gestión de estado** | Activar/desactivar el registro profesional sin eliminarlo | RF-02, RN-35 |

| **Regla de negocio** | Cada médico debe estar asociado a ≥ 1 especialidad activa | RN-02 |

| **Relación con Usuario** | 0..1: un médico puede tener cero o una cuenta de usuario asociada | D-06, RN-33, RN-34 |

| **ON DELETE** | No se borra al médico si existen citas u auditoría referenciadas | RN-35, D-06 |

### 2.3 Usuario

**Propósito:** Identidad digital utilizada para la autenticación, autorización por roles y el acceso al sistema. Es la entidad responsable de realizar operaciones y queda registrada en la auditoría. **Es conceptualmente distinta de Médico** (decisión D-06, RN-33): un médico profesional puede existir sin una cuenta de usuario asociada.

| Aspecto | Descripción | Fuente |

|---|---|---|

| **Identificador** | Identificador propio de la entidad (clave primaria conceptual) | — |

| **Atributos significativos** | Credenciales de acceso (protegidas mediante hash, RNF-06), estado activo/inactivo, roles asignados | RF-21, RNF-06 |

| **Regla de modelo** | Las credenciales no se almacenan en texto plano | RNF-06 |

| **Relación con Médico** | 0..1:0..1: un médico puede existir sin cuenta; un usuario puede no corresponder a un médico. Si el usuario tiene rol médico, debe vincularse con un único Médico | D-06, RN-33, RN-34 |

| **Relación con Paciente** | 0..1:0..1: un paciente puede existir sin cuenta; un usuario puede no corresponder a un paciente. Si el usuario tiene rol paciente, debe vincularse con un único Paciente | RN-14, RF-10, RF-14, RF-15 |

| **Relación con Rol** | N:M: un usuario puede tener varios roles; un rol puede tener varios usuarios | RF-21, RNF-05 |

| **Relación con Registro de auditoría** | 1:N: un usuario puede ser responsable de múltiples registros de auditoría | D-12, RF-22 |

| **Regla de negocio** | El acceso a funciones protegidas requiere autenticación obligatoria | RF-21, RNF-04, D-13 |

### 2.4 Especialidad

**Propósito:** Unidad organizacional de una especialidad médica que permite agrupar y consultar médicos asociados. La ubicación de la habilitación de modalidad virtual permanece pendiente de la decisión D-08.

| Aspecto | Descripción | Fuente |

|---|---|---|

| **Identificador** | Identificador propio de la entidad | — |

| **Atributos significativos** | Nombre de la especialidad, estado activo/inactivo | RF-03, CA-007 |

| **Gestión de estado** | Solo especialidades activas se consultan | RF-03, CA-007 |

| **Regla de negocio** | Una especialidad puede tener varios médicos y un médico puede pertenecer a varias especialidades | RF-04, CA-005 |

### 2.5 Médico–Especialidad (entidad de asociación)

**Propósito:** Materializar la relación muchos-a-muchos entre médicos y especialidades. Es una entidad asociativa que permite que un médico pertenezca a varias especialidades y una especialidad tenga varios médicos.

| Aspecto | Descripción | Fuente |

|---|---|---|

| **Propósito** | Asociar médicos a especialidades | RF-04, CA-005 |

| **Restricción** | Garantiza la unicidad del par médico–especialidad | RN-02 |

| **Regla de negocio** | Cada médico debe tener al menos una especialidad activa | RN-02 |

| **Decisiones pendientes** | D-08 (quién habilita modalidad virtual) afecta si aquí se almacena el flag de modalidad disponible | D-08 |

### 2.6 Horario (bloque de disponibilidad)

**Propósito:** Representar una franja horaria en la que un médico puede recibir pacientes. Puede portar información sobre su modalidad (presencial/virtual) según la decisión pendiente D-09.

| Aspecto | Descripción | Fuente |

|---|---|---|

| **Propósito** | Representar una disponibilidad de atención del médico | RF-05, RF-08, CA-009 |

| **Atributos significativos** | Médico propietario, día de la semana/fecha, franja horaria inicio/fin, estado (disponible/reservado/ocupado) | RF-05, RF-08 |

| **Estado** | Disponible, reservado, ocupado | RN-03, RN-10 |

| **Regla** | Una cita solo puede reservarse en un horario disponible para el médico | RN-03 |

| **Regla** | Al confirmar la cita, el horario queda ocupado para nuevas reservas incompatibles | RN-10 |

| **Decisiones pendientes** | D-09 determina si la modalidad es atributo del bloque horario | D-09, D-08 |

| **Transacciones afectadas** | Reserva libera/ocupa el horario; reprogramación lo libera del horario anterior | RN-10, RN-12, RN-13 |

### 2.7 Cita

**Propósito:** Entidad transaccional central del sistema. Une a un paciente, un médico, un horario, una especialidad y una modalidad, y sigue un ciclo de estados.

| Aspecto | Descripción | Fuente |

|---|---|---|

| **Propósito** | Registrar una atención programada | RF-09 |

| **Relaciones obligatorias** | Paciente y Médico son obligatorios por RN-01; la reserva selecciona además Especialidad y Horario conforme a RF-09 | RN-01, RF-09 |

| **Atributos significativos** | Fecha y hora de la cita, estado, modalidad y fechas de inicio y finalización de la atención. La información de acceso virtual se mantiene en Atención virtual | RF-09, RF-18, RF-19, RF-20 |

| **Ciclo de estados** | {Programada, Confirmada, En atención, Finalizada, Cancelada, No asistida} | RF-13, RN-07 |

| **Transiciones válidas** | Programada→Confirmada, Programada→Cancelada, Confirmada→En atención, Confirmada→Cancelada, Confirmada→No asistida, En atención→Finalizada | RN-08, RN-09 |

| **Reglas de integridad** | No doble reserva para mismo médico+fecha+horario (RN-04); no superposición entre citas de un paciente (RN-05); verificar disponibilidad antes de confirmar (RN-06) | RN-04, RN-05, RN-06, RNF-11, D-21 |

| **Conservación** | Citas finalizadas, canceladas y no asistidas se conservan ≥ 5 años | RN-22, D-20 |

| **Auditoría** | Toda modificación relevante se audita en la entidad de auditoría | RF-22, RN-23..25, D-12 |

### 2.8 Atención virtual

**Propósito:** Almacenar la información vinculada a una cita cuando la modalidad es virtual, incluyendo el enlace y los datos de acceso al servicio externo.

| Aspecto | Descripción | Fuente |

|---|---|---|

| **Propósito** | Gestionar el acceso al servicio externo de atención virtual | RF-19, RN-18, D-01 |

| **Relación con Cita** | 1:0..1 (condicional): una cita virtual tiene información de acceso; una cita presencial no | RN-19, CA-030 |

| **Atributos significativos** | Identificador de la cita, identificador externo de sesión (cuando corresponda), enlace de acceso, estado de disponibilidad de la integración, información del incidente (fecha/hora, detalles) | D-01, D-19, RN-18, RN-30 |

| **Contingencia** | Ante falla del servicio externo, conservar paciente, médico, fecha/hora, modalidad, estado, fecha/hora del incidente e información técnica | RN-30, RN-29, D-19 |

| **Decisiones pendientes** | D-09: si la modalidad se asocia al bloque horario o a la cita; D-01/DP-01: proveedor externo específico | D-01, D-09, D-19 |

### 2.9 Registro de auditoría (historial de modificaciones)

**Propósito:** Conservar el rastro de las modificaciones relevantes realizadas sobre una cita, vinculando cada cambio al usuario responsable y a los valores anterior y nuevo.

| Aspecto | Descripción | Fuente |

|---|---|---|

| **Propósito** | Auditoría funcional de modificaciones sobre citas | RF-22, RN-23 |

| **Atributos significativos (mínimos)** | Fecha, hora, usuario responsable, acción realizada, valor anterior, valor actualizado | D-12, RN-23, CA-037, CA-038 |

| **Relaciones** | N:1 con Cita; N:1 con Usuario responsable | D-12 |

| **Regla de conservación** | No se elimina al cancelar/finalizar la cita; conservar ≥ 5 años desde la fecha del evento | RNF-18, RN-25, D-20, CA-039 |

| **Separación conceptual** | Independiente de los registros técnicos de observabilidad (RNF-17) | D-12, RNF-17 |

### 2.10 Parámetros de configuración del sistema

**Propósito:** Valores que condicionan el comportamiento de las reglas de negocio y que, según RN-28, deben ser configurables sin modificar la lógica principal.

| Aspecto | Descripción | Fuente |

|---|---|---|

| **Tipos de parámetros** | Tiempo mínimo para cancelar/reprogramar; tolerancia para declarar no asistida; anticipación máxima de reserva; tiempo de inactividad de sesión (15 min iniciales, configurable) | RN-26, RN-27, RN-28, D-02, D-03, D-04, D-13 |

| **Implicación de modelo** | Deben almacenarse en una configuración accesible para las validaciones, sin endurecerse en el código | RN-28, RNF-07 |

| **Decisiones pendientes** | D-02, D-03, D-04, D-13 (valores a definir) | — |

### 2.11 Rol

**Propósito:** Entidad de autorización que representa un rol del sistema. Los roles definidos son: paciente, médico, personal de admisión/recepción y administrador. Cada rol agrupa un conjunto de permisos que definen qué operaciones puede realizar un usuario con ese rol.

| Aspecto | Descripción | Fuente |

|---|---|---|

| **Identificador** | Identificador propio de la entidad (clave primaria conceptual) | — |

| **Atributos significativos** | Nombre del rol, descripción del rol | RF-21, RNF-05 |

| **Roles definidos** | Paciente, médico, personal de admisión/recepción, administrador | RF-21, RNF-05, RT-06 |

| **Relación con Usuario** | N:M (un usuario puede tener varios roles; un rol puede tener varios usuarios) | RF-21, RNF-05 |

| **Relación con Permiso** | N:M (un rol agrupa varios permisos) | RF-21, RNF-05 |

| **Regla de negocio** | El acceso se restringe según el rol asignado a cada usuario | RN-14, RN-15, RN-16, RN-17 |

### 2.12 Permiso

**Propósito:** Entidad de autorización que representa una operación o capacidad autorizada en el sistema. Los permisos se asocian a roles, definiendo la autorización basada en roles (RBAC). Cada permiso corresponde a una operación específica (ej: crear cita, reprogramar, cancelar, consultar historial, gestionar usuarios).

| Aspecto | Descripción | Fuente |

|---|---|---|

| **Identificador** | Identificador propio de la entidad (clave primaria conceptual) | — |

| **Atributos significativos** | Nombre del permiso, descripción de la operación autorizada | RF-21, RNF-05 |

| **Relación con Rol** | N:M (un rol agrupa varios permisos; un permiso puede estar en varios roles) | RF-21, RNF-05 |

| **Decisiones pendientes** | D-10: permisos exactos del personal de admisión (alcance de operaciones autorizadas) | D-10 |

| **Regla de negocio** | Cada operación protegida requiere que el usuario autenticado cuente con el permiso correspondiente | RN-14, RN-15, RN-16, RN-17, RNF-05 |

### 2.13 Observabilidad técnica (concepto técnico adicional)

| Aspecto | Descripción | Fuente |

|---|---|---|

| **Propósito** | Observabilidad operacional | RNF-17 |

| **Eventos** | Errores de aplicación, intentos fallidos de autenticación, fallas en operaciones críticas, errores de integración, cambios de estado diagnóstico | RNF-17 |

| **Campos mínimos** | Fecha/hora del evento, tipo/nivel, componente relacionado, descripción | RNF-17 |

| **Separación conceptual** | No constituye el historial de auditoría (D-12, RNF-18) | RNF-17, RNF-18, D-12 |

> **Nota:** Observabilidad técnica se menciona aquí exclusivamente por trazabilidad (RNF-17) y como concepto técnico. **No es una tabla de dominio**. Su persistencia técnica detallada se abordará en el Paso 09 (Seguridad) y/o Paso 10 (Auditoría) del workflow, cuando se decida el mecanismo concreto de logs. Su presencia aquí no implica un compromiso físico de tabla en el modelo conceptual ni lógico.

---

## 3. Relaciones entre entidades

El modelo conceptual se compone de las siguientes relaciones. Las cardinalidades se expresan como `(min, max)` sobre cada extremo.

### 3.1 Relaciones maestras

| # | Relación | Cardinalidad | Descripción | Fuente |

|---|---|---|---|---|

| R-01 | `Médico` — `Especialidad` | N:M | Un médico pertenece a una o más especialidades; una especialidad la componen varios médicos | RF-04, CA-005 |

| R-02 | `Médico` → `Médico-Especialidad` (asociación) | 1:N | Cada médico puede tener varias asociaciones | RN-02 |

| R-03 | `Especialidad` → `Médico-Especialidad` (asociación) | 1:N | Cada especialidad puede tener varios médicos asociados | RN-02 |

| R-04 | `Médico` — `Usuario` | 0..1:0..1 | Un médico puede existir sin cuenta y un usuario puede no ser médico; si el usuario tiene rol médico, se vincula con un único Médico | D-06, RN-33, RN-34 |

### 3.2 Relaciones de horarios y agenda

| # | Relación | Cardinalidad | Descripción | Fuente |

|---|---|---|---|---|

| R-05 | `Médico` → `Horario` | 1:N | Un médico define varios bloques de disponibilidad | RF-05 |

| R-06 | `Horario` → `Cita` | 0..1:1 | Un horario puede estar asociado a una sola cita (ocupado) o estar libre (disponible). Nunca más de una cita sobre el mismo bloque | RN-03, RN-04, RNF-11 |

| R-07 | `Cita` → `Horario` | 1:1 | Cada cita consume **exactamente un** horario del médico; ninguna cita queda sin horario; ningún horario alberga dos citas | RN-01, RN-04, RNF-11, D-21 |

### 3.3 Relaciones de citas

| # | Relación | Cardinalidad | Descripción | Fuente |

|---|---|---|---|---|

| R-08 | `Paciente` → `Cita` | 1:N | Un paciente puede tener múltiples citas a lo largo del tiempo | RN-01 |

| R-09 | `Médico` → `Cita` | 1:N | Un médico puede tener múltiples citas | RN-01 |

| R-10 | `Especialidad` → `Cita` | 1:N | Una cita se registra bajo una especialidad | RN-01, RF-09 |

| R-11 | `Cita` — `Atención virtual` | 0..1:1 | Una cita presencial no requiere información virtual; una cita virtual sí (condicional) | RN-18, RN-19, CA-030 |

| R-12 | `Cita` → `Registro de auditoría` | 1:N | Una cita puede tener múltiples registros de auditoría (uno por modificación) | RF-22, RN-23 |

| R-13 | `Atención virtual` → `Incidente virtual` (implícito en la entidad) | 0..1:0..1 | La atención virtual puede o no tener un incidente registrado asociado | RN-30, D-19 |

> **Nota R-13:** El "Incidente virtual" no se modela como entidad independiente en este paso. La información mínima del incidente forma parte conceptualmente de `Atención virtual`. La decisión sobre si debe normalizarse como entidad independiente se resolverá en el modelo lógico.

### 3.4 Relaciones de usuarios, roles y auditoría

| # | Relación | Cardinalidad | Descripción | Fuente |

|---|---|---|---|---|

| R-14 | `Usuario` — `Rol` | N:M | Un usuario puede tener varios roles; un rol puede tener varios usuarios | RF-21, RNF-05 |

| R-15 | `Rol` — `Permiso` | N:M | Un rol agrupa varios permisos | RF-21, RNF-05 |

| R-16 | `Usuario` → `Registro de auditoría` | 1:N | Un usuario puede ser responsable de múltiples registros de auditoría | D-12 |

| R-17 | `Usuario` → `Cita` (usuario registrador) | 1:N | Un usuario autenticado puede registrar múltiples citas; cada cita conserva el usuario que la registró. Las modificaciones posteriores se atribuyen al usuario responsable mediante `Registro de auditoría` | RF-09, RF-10, RN-16, D-12 |

### 3.5 Relación entre identidad digital y paciente

| # | Relación | Cardinalidad | Descripción | Fuente |
|---|---|---|---|---|
| R-18 | `Paciente` — `Usuario` | 0..1:0..1 | Un paciente puede existir sin cuenta y un usuario puede no representar a un paciente; si el usuario tiene rol paciente, debe vincularse con un único Paciente | RN-14, RF-10, RF-14, RF-15 |

**Configuración:** `Parámetros de configuración` se mantiene como entidad conceptual independiente asociada al comportamiento global de las reglas de negocio; no requiere modelar una entidad ficticia `Sistema`.

**Observabilidad:** `Observabilidad técnica` se conserva únicamente como concepto técnico de trazabilidad y no participa como entidad de dominio en las relaciones del modelo conceptual.

---

## 4. Restricciones e invariantes conceptuales

Las siguientes reglas deben mantenerse como invariantes del modelo. Cada una se trasladará a una restricción física (CHECK, UNIQUE, FK, NOT NULL) en la etapa de modelo lógico/físico, pero conceptualmente definen el comportamiento permitido.

### 4.1 Integridad referencial y presencia (RN-01)

- **RN-01:** Toda cita debe estar asociada a un paciente y a un médico obligatoriamente. → Cita tiene relación obligatoria (NOT NULL) con Paciente y Médico.

- **RN-03:** Una cita solo puede reservarse sobre un horario disponible del médico seleccionado. → El horario referenciado debe pertenecer al médico de la cita y estar en estado disponible.

- **RN-03 implícito:** El horario referenciado por la cita debe pertenecer al médico asociado a la cita (coherencia médico–horario).

### 4.2 Unicidad y doble reserva (RN-04, RNF-11, D-21)

- **RN-04 / RNF-11:** No puede existir más de una cita (en estados que impliquen reserva) para el mismo médico, fecha y horario. → Restricción de unicidad sobre (médico, fecha/hora de la cita / horario).

- **RNF-11 / D-21:** La verificación de disponibilidad y la confirmación de la reserva forman una única operación atómica. → Invariante transaccional: ningún estado intermedio debe exponer un horario ocupado por dos citas.

### 4.3 Superposición de citas para el paciente (RN-05, D-11)

- **RN-05 / D-11:** Un paciente no puede tener dos citas que se superpongan en el mismo horario, ya sea que la operación la realice el paciente o el personal de admisión. → Restricción de solapamiento de rangos de tiempo para el mismo paciente.

### 4.4 Ciclo de estados y transiciones (RN-07, RN-08, RN-09)

- **RN-07:** El estado de la cita pertenece al conjunto {Programada, Confirmada, En atención, Finalizada, Cancelada, No asistida}.

- **RN-08:** Las únicas transiciones válidas son:

  - Programada → Confirmada

  - Programada → Cancelada

  - Confirmada → En atención

  - Confirmada → Cancelada

  - Confirmada → No asistida

  - En atención → Finalizada

- **RN-09:** Una cita cancelada o finalizada no puede volver a un estado anterior. → No hay reversa desde Finalizada, Cancelada o No asistida.

### 4.5 Estado del horario (RN-10, RN-12, RN-13)

- **RN-10:** Al confirmarse una cita, el horario queda ocupado y no disponible para nuevas reservas incompatibles.

- **RN-12:** Al reprogramarse una cita, el nuevo horario se reserva y el anterior se libera atómicamente (todo o nada).

- **RN-13:** Al cancelarse una cita, el horario se libera en la misma transacción.

### 4.6 Regla de afiliación médico–especialidad (RN-02)

- **RN-02:** Todo médico debe estar asociado a al menos una especialidad activa. → Restricción de existencia sobre Médico-Especialidad.

### 4.7 Modalidad y atención virtual (RN-18, RN-19)

- **RN-18:** Una cita virtual debe contener la información de acceso al servicio externo.

- **RN-19:** Una cita presencial no requiere información de acceso. → Atención virtual es opcional condicionada a modalidad = virtual.

### 4.8 Inicio y finalización de la atención (RN-20)

- **RN-20:** Una cita solo puede pasar a "Finalizada" tras haber estado en "En atención". El médico registra inicio (Confirmada→En atención) y fin (En atención→Finalizada), conservando fechas y horas.

### 4.9 No asistencia (RN-21)

- **RN-21:** Una falla del servicio externo de atención virtual o un problema de conexión no determinan automáticamente el estado "No asistida". → No asistida es un cambio de estado autorizado, no derivado de la falla.

### 4.10 Auditoría y conservación (RN-22 a RN-25, RN-35, D-20)

- **RN-22 / D-20:** Las citas finalizadas, canceladas y no asistidas se conservan ≥ 5 años.

- **RN-25 / D-20:** El historial de auditoría no se elimina al cancelar/finalizar la cita; se conserva ≥ 5 años desde el evento.

- **RN-35:** La desactivación de un médico o su usuario no elimina su información profesional, sus citas históricas ni la auditoría asociada.

### 4.11 Credenciales y acceso (RNF-06)

- **RNF-06:** Las credenciales de acceso no se almacenan en texto plano. → La entidad Usuario protege las credenciales mediante mecanismo de hash (a definir en seguridad).

### 4.12 Coherencia entre Médico, Especialidad y Cita

- Toda Cita debe registrarse con una Especialidad que esté asociada al Médico seleccionado mediante `Médico–Especialidad`.
- No debe permitirse registrar una cita con una especialidad ajena a las asociaciones vigentes del médico.
- Esta regla deberá traducirse a una restricción verificable en el modelo lógico/físico sin resolver todavía el mecanismo específico.

---

## 5. Trazabilidad RF / RNF → entidades → relaciones

Cada requerimiento funcional se cruza con las entidades e invariantes que lo satisfacen.

| Req. | Entidad(es) involucradas | Relación(es) / Invariante(s) asociada(s) |

|---|---|---|

| **RF-01** | Paciente | Presencia de campos obligatorios (CA-002); 1:N con Cita (R-08) |

| **RF-02** | Médico | Estado activo/inactivo (RN-35); N:M Especialidad (R-01); 1:N Horario (R-05), Cita (R-09) |

| **RF-03** | Especialidad | Estado activo (CA-007); N:M Médico (R-01) |

| **RF-04** | Médico-Especialidad | Unicidad del par (R-02, R-03); RN-02 (≥1 especialidad activa) |

| **RF-05** | Horario | N:1 con Médico (R-05); estado disponible; D-08/D-09 pendientes |

| **RF-06** | Especialidad | Solo activas con médicos asociados (R-01) |

| **RF-07** | Médico | Activos con horarios disponibles (R-05) |

| **RF-08** | Horario | Disponibilidad futura dentro de la ventana (D-04); 1:0..1 Cita (R-06) |

| **RF-09** | Cita | N:1 Paciente (R-08), Médico (R-09), Especialidad (R-10), Horario (R-07); RN-04 unicidad médico+fecha+horario; RN-05 no superposición paciente; RN-06 verificación previa; D-21 atomicidad |

| **RF-10** | Cita, Usuario | Cita registrada con usuario responsable (R-17); RN-16 |

| **RF-11** | Cita | Reprogramar hacia horario disponible; RN-12 atomicidad nuevo+liberar anterior; D-21 |

| **RF-12** | Cita | Cancelar libera horario (RN-13); D-02 pendiente tiempos mínimos |

| **RF-13** | Cita | Ciclo de estados (RN-07); transiciones (RN-08); sin reversa (RN-09) |

| **RF-14** | Cita, Paciente, Usuario | Filtrar estados vigentes por paciente (RN-14) y vincular la identidad digital del paciente mediante R-18 cuando exista cuenta |

| **RF-15** | Cita, Paciente | Historial 1:N (R-08); 5 años (RN-22, D-20); filtro por fecha/estado |

| **RF-16** | Cita, Médico | Agenda 1:N (R-09); solo médico (RN-15) |

| **RF-17** | Horario | Disponibilidad de médicos/horarios (R-05) |

| **RF-18** | Cita, Médico, Especialidad | Modalidad identificada; RN-18/RN-19; D-08/D-09 pendientes |

| **RF-19** | Atención virtual, Cita | 1:0..1 (R-11); enlace y acceso (D-01); contingencia (RN-30, D-19) |

| **RF-20** | Cita | Inicio/fin de atención (RN-20); Confirmada→En atención→Finalizada |

| **RF-21** | Usuario, Rol, Permiso | N:M Rol (R-14), Permiso (R-15); credenciales hash (RNF-06) |

| **RF-22** | Registro de auditoría, Cita, Usuario | 6 campos mínimos (D-12); N:1 Cita (R-12), N:1 Usuario (R-16); 5 años (D-20) |

| Req. | Entidad(es) involucradas | Trazabilidad |

|---|---|---|

| **RNF-01** | — (rendimiento) | Patrones de acceso de §8; optimización con índices (Paso 11) |

| **RNF-02** | — (capacidad) | Volumen de escritura controlado (D-18) |

| **RNF-03** | — (escalabilidad) | Separación de cargas, particionamiento (Pasos 06, 11) |

| **RNF-04** | Usuario | Autenticación obligatoria (D-13) |

| **RNF-05** | Rol, Permiso, Usuario | RBAC (R-14, R-15) |

| **RNF-06** | Usuario | Hash de credenciales (nunca texto plano) |

| **RNF-07** | Parámetros de configuración | Tiempo de inactividad 15 min configurable (D-13); la entidad se mantiene independiente en el modelo conceptual |

| **RNF-08** | — (disponibilidad) | Infraestructura (D-22); no del esquema |

| **RNF-09** | — (RPO) | Respaldo periódico (D-22); no del esquema |

| **RNF-10** | — (RTO) | Procedimiento de restauración (D-22); no del esquema |

| **RNF-11** | Cita, Horario | Atomicidad reserva (D-21); RN-04 |

| **RNF-12** | — (validación) | CHECK/validación pendiente D-14 |

| **RNF-13..15** | — (usabilidad/accesibilidad) | Presentación/UI; no impacta esquema |

| **RNF-16** | — (modularidad) | Separación lógica por módulos y relaciones del modelo (R-01..R-18) |

| **RNF-17** | Observabilidad técnica | Logs técnicos separados de auditoría; concepto técnico sin relación de dominio propia en este paso |

| **RNF-18** | Registro de auditoría | 6 campos; 5 años; no borrar (R-12) |

| **RNF-19** | — (navegadores) | No impacta persistencia |

---

## 6. Reglas de negocio trazadas al modelo

| RN | Implicación en el modelo de datos | Invariante / Restricción derivada |

|---|---|---|

| **RN-01** | Cita obliga a Paciente y Médico | FK NOT NULL (R-07, R-08, R-09) |

| **RN-02** | Médico ≥1 especialidad activa | Restricción de existencia sobre Médico-Especialidad |

| **RN-03** | Cita solo en horario disponible del médico | El horario pertenece al médico; estado disponible (R-06) |

| **RN-04** | Una cita por médico+fecha+horario | UNIQUE sobre (médico, horario) — última línea de defensa |

| **RN-05** | No superposición de citas para un paciente | Restricción de rango de solapamiento para paciente |

| **RN-06** | Verificar disponibilidad antes de confirmar | Transacción atómica (D-21) |

| **RN-07** | Estados acotados de cita | Conjunto de valores (RN-07) |

| **RN-08** | Transiciones válidas | Máquina de estados (RN-08) |

| **RN-09** | Cancelada/Finalizada no revierte | Bloqueo de reversa (RN-09) |

| **RN-10** | Horario ocupado al confirmar | Atomicidad estado + reserva (D-21) |

| **RN-11** | Reprogramación solo a horario disponible | Verificación + atomicidad (D-21) |

| **RN-12** | Reprogramación: nuevo reservado, anterior liberado | Transacción todo o nada |

| **RN-13** | Cancelación libera el horario | Atomicidad estado + liberación |

| **RN-14** | Paciente solo gestiona sus citas | Filtrado por paciente y vínculo Usuario–Paciente cuando exista cuenta (R-08, R-18) |

| **RN-15** | Médico solo agenda propia | Filtrado médico_id (R-09) |

| **RN-16** | Admisión con permisos, mismas reglas | Rol + auditoría (R-17); mismo invariante RN-03/05/06 |

| **RN-17** | Admin gestiona todo | Rol administrador (R-14) |

| **RN-18** | Cita virtual identificada + acceso | Modalidad + Atención virtual (R-11) |

| **RN-19** | Presencial no requiere acceso | Atención virtual condicional |

| **RN-20** | Finalizar solo tras En atención; registrar inicio/fin | Transición estricta (RN-08); fechas/hora de inicio/fin |

| **RN-21** | Falla virtual no → No asistida automática | No asistida es cambio autorizado |

| **RN-22** | Historial de citas ≥ 5 años | Conservación D-20 |

| **RN-23** | Auditoría mínimo 6 campos | Registro de auditoría (§2.9) |

| **RN-24** | Historial identifica antes/después | valor_anterior + valor_actualizado |

| **RN-25** | Auditoría no se borra al cancelar/finalizar | Conservación D-20 |

| **RN-26** | Tiempo mínimo cancelar/reprogramar | Parámetro configurable (D-02, pendiente) |

| **RN-27** | Tolerancia No asistida | Parámetro configurable (D-03, pendiente) |

| **RN-28** | Anticipación máxima configurable | Parámetro configurable (D-04, pendiente) |

| **RN-29** | Falla virtual: no borrar/cancelar/auto-no-asistir | Conservación estado + incidente |

| **RN-30** | Incidente: conservar paciente, médico, fecha/hora, modalidad, estado, incidente, info | Atención virtual (R-11) |

| **RN-31** | Mensaje visible ante falla | Información visible (no de esquema) |

| **RN-32** | Reprogramar/cambiar modalidad con registro en auditoría | Auditoría (R-12) |

| **RN-33** | Médico ≠ Usuario | Entidades separadas (R-04) |

| **RN-34** | Médico 0..1 Usuario; Usuario médico 1 Médico | Cardinalidad 0..1:1 (R-04) |

| **RN-35** | Desactivación no elimina | ON DELETE RESTRICT / conservación (D-06) |

---

## 7. Decisiones pendientes que afectan el modelo

Las siguientes decisiones (D-01..D-23) están marcadas como **Pendientes** y afectan directamente decisiones que se pospusieron al modelo lógico/físico. El modelo conceptual incorpora flexibilidad para acomodarlas sin reingeniería.

| Decisión | Tema | Implicancia para el modelo |

|---|---|---|

| **D-02** | Políticas cancelación/reprogramación | Necesita parámetro de tiempo mínimo configurable (Parámetros de configuración, §2.10). Pendiente valor. |

| **D-03** | Tolerancia no asistencia | Necesita parámetro de tolerancia y quién autoriza el cambio (cita, auditoría). Pendiente. |

| **D-04** | Anticipación máxima reserva | Restringe consultas/reservas al rango configurado (Horario, Cita). Pendiente. |

| **D-07** | Desactivación médico con citas futuras | Afecta FK Médico–Cita y política de estado; alternativas: impedir, mantener, reprogramar. Pendiente. |

| **D-08** | Nivel de habilitación modalidad virtual | Define dónde se almacena el flag de modalidad: médico, especialidad, médico–especialidad, horario o combinación. Afecta Médico, Especialidad, Médico-Especialidad y/o Horario. Pendiente. |

| **D-09** | Modalidad asociada a los horarios | Define si un médico puede tener horarios mixtos y si la modalidad es atributo del bloque. Afecta Horario. Pendiente. |

| **D-10** | Permisos del personal de admisión | Afecta modelo de roles/permisos (§2.11, §2.12). Pendiente. |

| **D-14** | Reglas de validación de datos | Afecta CHECKs y tipos de columna; formatos DNI, nombre, teléfono, email, Fecha de nacimiento. Pendiente. |

| **D-01** | Servicio externo atención virtual | La información de acceso es proveedor-neutra (Atención virtual, §2.8). Proveedor específico pendiente (DP-01). |

| **D-19** | Contingencia falla virtual | Registro de incidente en Atención virtual (RN-30). |

| **D-21** | Control de concurrencia | Mecanismo técnico (lock optimista/pesimista, nivel aislamiento) pendiente; se pospuso al Paso 06/Paso 12. |

---

## 8. Patrones de acceso identificados (referencia)

Se listan los patrones de lectura/escritura frecuentes que deben optimizarse con índices en el Paso 11. Su presencia aquí solo es para trazabilidad; no constituyen restricciones físicas todavía.

| # | Patrón de acceso | Entidad(es) involucrada(s) | RF/RN asociados |

|---|---|---|---|

| 1 | Disponibilidad por médico y fecha | Horario | RF-08, CA-009, D-04 |

| 2 | Agenda del médico | Cita | RF-16, CA-026 |

| 3 | Citas del paciente | Cita | RF-14, CA-022 |

| 4 | Historial por paciente y fecha | Cita | RF-15, CA-024, RN-22 |

| 5 | Búsqueda de horarios libres | Horario | RF-08, RF-17 |

---

## 9. Resumen del modelo conceptual

**Entidades conceptuales de dominio identificadas:** 12

1. **Paciente** — Identidad clínica del solicitante (RF-01).
2. **Médico** — Identidad profesional del prestador (RF-02, D-06).
3. **Usuario** — Identidad digital de acceso, autenticación y roles (RF-21, RNF-06). Distinta de Médico (D-06, RN-33).
4. **Especialidad** — Unidad organizacional de la especialidad (RF-03).
5. **Médico–Especialidad** — Asociación muchos-a-muchos (RF-04, RN-02).
6. **Horario** — Bloque de disponibilidad del médico (RF-05, RF-08).
7. **Cita** — Entidad transaccional central (RF-09, RN-01..RN-10).
8. **Atención virtual** — Información de acceso a la sesión virtual (RF-19, RN-18, RN-30).
9. **Registro de auditoría** — Historial de modificaciones de citas (RF-22, RN-23..RN-25, D-12).
10. **Parámetros de configuración** — Políticas temporales configurables (RN-26..RN-28, D-02..D-04, D-13).
11. **Rol** — Entidad de autorización que agrupa permisos (RF-21, RNF-05).
12. **Permiso** — Entidad de autorización que representa una operación autorizada (RF-21, RNF-05).

**Concepto técnico adicional:**
- **Observabilidad técnica** — Trazabilidad operacional derivada de RNF-17; no se modela como tabla de dominio en este paso.

**Relaciones principales:**

- Médico N:M Especialidad (R-01, mediante asociación 2.5)

- Médico 0..1 Usuario (R-04, separación D-06/RN-33)

- Médico 1:N Horario (R-05)

- Horario 0..1 ↔ Cita (R-06, R-07, estado disponible/ocupado, RN-03/RN-04/RN-10/RNF-11)

- Paciente 1:N Cita (R-08)

- Médico 1:N Cita (R-09)

- Especialidad 1:N Cita (R-10)

- Cita 0..1 ↔ Atención virtual (R-11, condicional modalidad)

- Cita 1:N Registro de auditoría (R-12)

- Usuario N:M Rol (R-14); Rol N:M Permiso (R-15)

- Usuario 1:N Registro de auditoría (R-16, responsable)

- Usuario 1:N Cita como usuario registrador (R-17)

- Paciente 0..1 ↔ 0..1 Usuario (R-18; vínculo condicional cuando existe cuenta de paciente)

**Invariantes críticas trasladables a restricciones físicas (pendientes en Paso 08):**

- Unicidad médico + horario/fecha (RN-04, RNF-11).

- No superposición de citas para un paciente (RN-05).

- Transiciones de estado acotadas (RN-07..RN-09).

- Campos obligatorios (RN-01, RF-01, CA-002).

- Unicidad del par médico–especialidad y ≥1 especialidad activa por médico (RN-02).

---

## 10. Conclusiones del Paso 02

1. El modelo conceptual identifica **12 entidades conceptuales de dominio**, además de Observabilidad técnica como concepto técnico separado, todas trazables a RF-01..RF-22 y RNF-01..RNF-19.

2. Se documentan 18 referencias de relación (R-01..R-18), incluyendo la separación Médico ≠ Usuario, el vínculo condicional Usuario–Paciente y la independencia auditoría ≠ observabilidad.

3. Se documentan las invariantes de negocio (RN-01..RN-35) como restricciones a trasladar a la etapa física, sin definir tipos ni SQL.

4. Se mantiene flexibilidad frente a decisiones pendientes (D-02, D-03, D-04, D-07, D-08, D-09, D-10, D-14) mediante parámetros configurables, flags condicionales y cardinalidades abiertas.

5. No se genera SQL ni se selecciona motor: el modelo es independiente del SGBM.

6. Se preserva la trazabilidad completa RF/RNF/RN → entidad → relación → invariante, de acuerdo con D-23.

**Estado del checkpoint:** Paso 02 completado (pendiente validación humana).
