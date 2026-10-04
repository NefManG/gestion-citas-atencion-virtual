# Modelo Lógico — Sistema de Gestión de Citas y Atención Virtual

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español  
**Generado:** Paso 04 — Modelo lógico  
**Agente utilizado:** `database-engineer`  
**Skill utilizado:** `database-schema-designer`  
**Ruta del skill:** `.agents/skills/database-schema-designer/SKILL.md`  
**Workflow:** `02_database_workflow`  
**Fuente principal:** `proyecto/base_datos/02_modelo_conceptual/modelo_conceptual.md` + `proyecto/base_datos/02_modelo_conceptual/diagrama_er.md`  
**Estado:** Corregido posteriormente para propagar la normalización aprobada en el Paso 05 y los hallazgos del Paso 14 — Revisión DBA. Pendiente de nueva validación DBA.

---

## 1. Objetivo del Paso 04

El presente documento transforma el modelo conceptual aprobado y su diagrama E-R en un **modelo lógico relacional**, definiendo tablas, atributos lógicos, identificadores, claves primarias, claves foráneas, nulabilidad, unicidad y relaciones.

El modelo se mantiene independiente de un sistema gestor de base de datos específico.

En este paso se establece la estructura lógica de persistencia sin entrar todavía en decisiones propias del modelo físico.

### Este paso NO:

- selecciona PostgreSQL, MySQL, MariaDB o SQL Server;
- genera sentencias SQL;
- define índices físicos;
- define estrategias de particionamiento;
- selecciona mecanismos específicos de bloqueo;
- define triggers concretos;
- selecciona funciones particulares de un SGBD;
- resuelve técnicamente las reglas de concurrencia;
- materializa las decisiones pendientes D-08 y D-09.

### Nota de actualización posterior

Durante el **Paso 05 — Normalización** se detectó que almacenar simultáneamente:

`cita.id_horario`

e:

`cita.id_medico`

producía la dependencia funcional:

`id_horario → id_medico`

y, por tanto:

`id_cita → id_horario → id_medico`

Como resultado, el modelo lógico fue corregido para eliminar `cita.id_medico`.

El médico correspondiente a una cita se determina ahora mediante:

`cita.id_horario → horario.id_medico`

Esta corrección se incorpora en el presente documento para mantener consistencia con los pasos posteriores del workflow.

---

## 2. Principios aplicados

| Principio | Aplicación |
|---|---|
| Separación conceptual | Paciente, Médico y Usuario continúan siendo entidades diferentes |
| Integridad lógica | Se identifican PK, FK, unicidad, obligatoriedad y reglas de dominio |
| Relaciones N:M | Se resuelven mediante relaciones asociativas |
| Independencia del SGBD | No se utilizan mecanismos exclusivos de un motor |
| Trazabilidad | Las estructuras conservan relación con RF, RNF, RN y decisiones |
| Decisiones pendientes | No se materializan decisiones todavía no aprobadas |
| Normalización | Se incorpora la corrección aprobada en el Paso 05 |
| Conservación histórica | No se eliminan citas históricas para simplificar restricciones de unicidad |

---

# 3. Tablas del modelo lógico

El modelo lógico está compuesto por **14 tablas**:

- 12 derivadas de las entidades conceptuales aprobadas;
- 2 tablas asociativas adicionales para resolver las relaciones N:M del modelo RBAC.

Las tablas son:

1. `paciente`
2. `medico`
3. `usuario`
4. `especialidad`
5. `medico_especialidad`
6. `horario`
7. `cita`
8. `atencion_virtual`
9. `registro_auditoria`
10. `parametros_configuracion`
11. `rol`
12. `permiso`
13. `usuario_rol`
14. `rol_permiso`

---

# 3.1 Paciente

**Propósito:** representar la identidad clínica de la persona que solicita una atención médica.

| Atributo | Tipo lógico | Restricción lógica | Descripción |
|---|---|---|---|
| id_paciente | Identificador | PK, obligatorio | Identificador único |
| nombre | Texto | Obligatorio | Nombre o nombres |
| apellidos | Texto | Obligatorio | Apellidos |
| documento_identidad | Texto | Obligatorio, único | Documento de identidad |
| fecha_nacimiento | Fecha | Obligatorio | Fecha de nacimiento |
| telefono | Texto | Obligatorio | Número de contacto |
| correo_electronico | Texto | Opcional | Correo electrónico |

### Reglas lógicas

- `documento_identidad` debe identificar de forma única a cada paciente.
- `fecha_nacimiento` no puede representar una fecha futura.
- Los formatos específicos del documento, teléfono y correo permanecen sujetos a D-14.
- Un paciente puede existir sin disponer de una cuenta de usuario.
- Un paciente puede tener múltiples citas a lo largo del tiempo.

### Trazabilidad

RF-01, RF-14, RF-15, RN-14.

---

# 3.2 Médico

**Propósito:** representar la identidad profesional del prestador de atención médica.

| Atributo | Tipo lógico | Restricción lógica | Descripción |
|---|---|---|---|
| id_medico | Identificador | PK, obligatorio | Identificador único |
| nombre_completo | Texto | Obligatorio | Nombre del profesional |
| numero_colegiado | Texto | Obligatorio, único | Registro profesional |
| activo | Booleano | Obligatorio | Estado lógico del médico |

### Reglas lógicas

- Un médico puede estar activo o inactivo.
- La desactivación del médico no elimina su información histórica.
- Cada médico debe estar asociado al menos a una especialidad activa.
- Un médico puede existir sin una cuenta de usuario.
- Un médico puede definir múltiples bloques de horario.
- Un médico puede atender múltiples citas a través de sus horarios.

### Trazabilidad

RF-02, RF-04, RF-07, RF-16, RN-02, RN-15, RN-33, RN-34, RN-35.

---

# 3.3 Usuario

**Propósito:** representar la identidad digital utilizada para autenticación, autorización y acceso al sistema.

| Atributo | Tipo lógico | Restricción lógica | Descripción |
|---|---|---|---|
| id_usuario | Identificador | PK, obligatorio | Identificador único |
| username | Texto | Obligatorio, único | Identificador utilizado para iniciar sesión |
| password_hash | Texto | Obligatorio | Hash de la contraseña |
| email | Texto | Obligatorio, único | Correo de la cuenta |
| activo | Booleano | Obligatorio | Estado de la cuenta |
| id_paciente | Identificador | Opcional, único, FK → paciente.id_paciente | Vinculación opcional con Paciente |
| id_medico | Identificador | Opcional, único, FK → medico.id_medico | Vinculación opcional con Médico |

### Regla de credenciales

`password_hash` representa únicamente la versión protegida de la credencial.

Nunca debe almacenarse la contraseña en texto plano.

La selección del algoritmo concreto de protección corresponde al Paso 09 — Seguridad.

### Usuario–Paciente

Relación:

`usuario.id_paciente → paciente.id_paciente`

Reglas:

- `id_paciente` es opcional;
- debe ser único cuando exista;
- un paciente puede existir sin usuario;
- una cuenta vinculada a un paciente representa su identidad digital.

Cardinalidad:

**Paciente 0..1 ↔ 0..1 Usuario**

### Usuario–Médico

Relación:

`usuario.id_medico → medico.id_medico`

Reglas:

- `id_medico` es opcional;
- debe ser único cuando exista;
- un médico puede existir sin cuenta;
- una cuenta vinculada a un médico representa su identidad digital.

Cardinalidad:

**Médico 0..1 ↔ 0..1 Usuario**

No se establece una regla que impida que una cuenta pueda relacionarse simultáneamente con Paciente y Médico, ya que el modelo de roles admite múltiples roles y no existe una decisión aprobada que establezca exclusividad.

### Trazabilidad

RF-21, RNF-04, RNF-05, RNF-06, RN-14, RN-15, RN-33, RN-34.

---

# 3.4 Especialidad

**Propósito:** representar las especialidades médicas disponibles.

| Atributo | Tipo lógico | Restricción lógica | Descripción |
|---|---|---|---|
| id_especialidad | Identificador | PK, obligatorio | Identificador único |
| nombre | Texto | Obligatorio, único | Nombre de la especialidad |
| activo | Booleano | Obligatorio | Estado de la especialidad |

### Reglas lógicas

- Solo las especialidades activas participan normalmente en nuevas operaciones.
- Una especialidad puede tener varios médicos asociados.
- Un médico puede pertenecer a varias especialidades.

### Decisión D-08

La especialidad **no contiene todavía ningún atributo que determine la habilitación de modalidad virtual**.

D-08 continúa pendiente.

### Trazabilidad

RF-03, RF-04, RF-06, RN-02, D-08.

---

# 3.5 Medico_Especialidad

**Propósito:** resolver la asociación N:M entre Médico y Especialidad.

| Atributo | Tipo lógico | Restricción lógica | Descripción |
|---|---|---|---|
| id_medico | Identificador | PK compuesta, FK → medico.id_medico, obligatorio | Médico asociado |
| id_especialidad | Identificador | PK compuesta, FK → especialidad.id_especialidad, obligatorio | Especialidad asociada |
| fecha_asociacion | Fecha | Opcional | Fecha desde la cual existe la asociación |

### Clave primaria lógica

`(id_medico, id_especialidad)`

Esto garantiza que la combinación Médico–Especialidad no se repita.

### Relaciones

`medico_especialidad.id_medico → medico.id_medico`

`medico_especialidad.id_especialidad → especialidad.id_especialidad`

### Regla RN-02

Cada médico debe estar relacionado con al menos una especialidad activa.

La forma concreta de garantizar esta regla corresponde a las etapas de integridad y diseño físico.

### D-08 pendiente

No se incluye:

`habilitada_modalidad_virtual`

porque todavía no se ha decidido si la habilitación virtual corresponde a:

- Médico;
- Especialidad;
- Médico–Especialidad;
- Horario;
- o una combinación.

### Trazabilidad

RF-04, RN-02, D-08.

---

# 3.6 Horario

**Propósito:** representar los bloques de disponibilidad definidos por los médicos.

| Atributo | Tipo lógico | Restricción lógica | Descripción |
|---|---|---|---|
| id_horario | Identificador | PK, obligatorio | Identificador del bloque |
| id_medico | Identificador | FK → medico.id_medico, obligatorio | Médico propietario |
| dia_semana | Entero | Opcional | Día recurrente de la semana |
| fecha_especifica | Fecha | Opcional | Fecha específica cuando el horario no es recurrente |
| hora_inicio | Hora | Obligatorio | Inicio del bloque |
| hora_fin | Hora | Obligatorio | Fin del bloque |
| estado | Dominio de estado | Obligatorio | Estado actual del horario |

### Valores conceptuales de estado

El estado debe pertenecer a:

- disponible;
- reservado;
- ocupado.

### Reglas lógicas

- `hora_fin` debe ser posterior a `hora_inicio`.
- Cada horario pertenece exactamente a un médico.
- `id_horario → id_medico`.
- Debe existir información suficiente para determinar cuándo ocurre el bloque.
- Cuando el diseño utilice `dia_semana`, representa disponibilidad recurrente.
- Cuando utilice `fecha_especifica`, representa disponibilidad puntual.

### D-09 pendiente

No se incluye un atributo `modalidad` en `horario`.

Continúa pendiente decidir si la modalidad pertenece al horario, a la cita o a otra combinación del modelo.

### Trazabilidad

RF-05, RF-08, RF-17, RN-03, RN-10, RN-12, RN-13, D-09.

---

# 3.7 Cita

**Propósito:** representar la entidad transaccional central del sistema.

## Estructura normalizada

| Atributo | Tipo lógico | Restricción lógica | Descripción |
|---|---|---|---|
| id_cita | Identificador | PK, obligatorio | Identificador de la cita |
| id_paciente | Identificador | FK → paciente.id_paciente, obligatorio | Paciente |
| id_especialidad | Identificador | FK lógica, obligatorio | Especialidad seleccionada |
| id_horario | Identificador | FK → horario.id_horario, obligatorio | Bloque utilizado |
| id_usuario_registrador | Identificador | FK → usuario.id_usuario, obligatorio | Usuario que registra la cita |
| estado | Dominio de estado | Obligatorio | Estado de la cita |
| modalidad | Dominio de modalidad | Obligatorio | Presencial o virtual |
| fecha_hora_programada | FechaHora | Obligatorio | Fecha y hora programada |
| fecha_hora_inicio_atencion | FechaHora | Opcional | Inicio real de la atención |
| fecha_hora_fin_atencion | FechaHora | Opcional | Fin real de la atención |

### Cambio de normalización

`cita` **no almacena `id_medico` directamente**.

La relación con Médico se obtiene mediante:

`cita.id_horario → horario.id_horario`

y:

`horario.id_medico → medico.id_medico`

Esto evita mantener dos representaciones del mismo médico en una cita.

---

## Estados permitidos

La cita debe encontrarse en uno de los siguientes estados:

- Programada;
- Confirmada;
- En atención;
- Finalizada;
- Cancelada;
- No asistida.

---

## Modalidades

La modalidad identifica si la cita es:

- presencial;
- virtual.

La existencia de `cita.modalidad` no resuelve D-09.

D-09 continúa determinando si el **Horario** también debe contener información de modalidad.

---

## Relación Paciente–Cita

`cita.id_paciente → paciente.id_paciente`

Cardinalidad:

**Paciente 1:N Cita**

Toda cita debe corresponder a un paciente.

---

## Relación Horario–Cita

`cita.id_horario → horario.id_horario`

Cada cita debe utilizar exactamente un horario.

El horario utilizado determina el médico responsable de la cita.

---

## Relación Médico–Cita

La relación conceptual continúa siendo:

**Médico 1:N Cita**

pero su implementación lógica es **indirecta**.

Ruta:

`CITA`

→ `HORARIO`

→ `MEDICO`

Formalmente:

`cita.id_horario → horario.id_horario`

`horario.id_medico → medico.id_medico`

Por tanto, no se mantiene:

`cita.id_medico`

como atributo redundante.

---

## Regla Horario–Cita y conservación histórica

Un horario no puede mantener simultáneamente más de una reserva activa incompatible.

No se establece una restricción lógica de unicidad absoluta sobre:

`id_horario`

porque las citas canceladas, reprogramadas, finalizadas o históricas deben conservarse según las reglas del sistema.

La regla correcta es:

> En un momento determinado no pueden coexistir dos citas activas incompatibles asociadas al mismo horario.

Una cita histórica no debe eliminarse únicamente para permitir la reutilización lógica del horario.

El mecanismo concreto para garantizar esta regla corresponde a los pasos de integridad y transacciones/concurrencia.

---

## Usuario registrador

`cita.id_usuario_registrador → usuario.id_usuario`

Cardinalidad:

**Usuario 1:N Cita**

Representa al usuario que registró originalmente la cita.

Las modificaciones posteriores deben identificarse mediante `registro_auditoria`.

---

## Coherencia Médico–Especialidad–Cita

`cita` conserva:

`id_especialidad`

porque un médico puede pertenecer a múltiples especialidades y la especialidad elegida constituye información propia de la cita.

El médico se obtiene a través del horario:

`cita.id_horario → horario.id_medico`

Para una cita determinada debe cumplirse que:

`(horario.id_medico, cita.id_especialidad)`

corresponda a una asociación válida existente en:

`medico_especialidad(id_medico, id_especialidad)`

Por tanto, la regla lógica es:

`cita.id_horario`

→ `horario.id_medico`

junto con:

`cita.id_especialidad`

→ asociación válida en `medico_especialidad`.

La forma física concreta de garantizar esta regla se define en los pasos de integridad y diseño físico.

---

## Reglas de estado

Las transiciones permitidas son:

- Programada → Confirmada
- Programada → Cancelada
- Confirmada → En atención
- Confirmada → Cancelada
- Confirmada → No asistida
- En atención → Finalizada

Una cita:

- Finalizada;
- Cancelada;
- No asistida;

no debe volver a un estado anterior.

---

## Fechas de atención

Si la cita se encuentra en estado Finalizada:

`fecha_hora_inicio_atencion`

y:

`fecha_hora_fin_atencion`

deben encontrarse registradas.

El fin de la atención debe ser posterior a su inicio.

### Trazabilidad

RF-09..RF-20, RN-01..RN-22, RNF-11, D-21.

---

# 3.8 Atencion_Virtual

**Propósito:** representar la información adicional requerida para una cita virtual.

| Atributo | Tipo lógico | Restricción lógica | Descripción |
|---|---|---|---|
| id_cita | Identificador | PK, FK → cita.id_cita, obligatorio | Cita virtual |
| id_sesion_externa | Texto | Opcional | Identificador proporcionado por el servicio externo |
| enlace_acceso | Texto | Obligatorio cuando existe el registro | Enlace de acceso |
| estado_disponibilidad | Dominio de estado | Obligatorio | Estado de disponibilidad del servicio |
| fecha_hora_incidente | FechaHora | Opcional | Momento de una falla |
| detalles_incidente | Texto | Opcional | Información del incidente |

### Cardinalidad

**Cita 1 : 0..1 Atención Virtual**

Una cita puede:

- no poseer información virtual;
- poseer un único registro de atención virtual.

### Regla RN-18

Cuando:

`cita.modalidad = virtual`

debe existir la información necesaria para la atención virtual.

### Regla RN-19

Cuando:

`cita.modalidad = presencial`

no se requiere información de atención virtual.

### Incidentes

Con el alcance aprobado, la información opcional del incidente continúa formando parte de `atencion_virtual`.

No se crea una entidad independiente mientras el modelo no requiera múltiples incidentes por una misma atención virtual.

Si en una futura decisión se permiten múltiples incidentes históricos por atención, deberá reevaluarse una entidad `incidente_virtual`.

### Trazabilidad

RF-18, RF-19, RN-18, RN-19, RN-29, RN-30, D-01, D-19.

---

# 3.9 Registro_Auditoria

**Propósito:** mantener el historial funcional de modificaciones relevantes realizadas sobre una cita.

| Atributo | Tipo lógico | Restricción lógica | Descripción |
|---|---|---|---|
| id_registro | Identificador | PK, obligatorio | Identificador del evento |
| id_cita | Identificador | FK → cita.id_cita, obligatorio | Cita afectada |
| id_usuario_responsable | Identificador | FK → usuario.id_usuario, obligatorio | Usuario responsable |
| fecha_evento | Fecha | Obligatorio | Fecha del cambio |
| hora_evento | Hora | Obligatorio | Hora del cambio |
| accion | Texto/Dominio | Obligatorio | Acción ejecutada |
| valor_anterior | Texto estructurado | Opcional según operación | Información previa |
| valor_actual | Texto estructurado | Obligatorio | Información posterior |

### Relación Cita–Auditoría

`registro_auditoria.id_cita → cita.id_cita`

Cardinalidad:

**Cita 1:N Registro Auditoría**

Una cita puede generar múltiples eventos.

### Relación Usuario–Auditoría

`registro_auditoria.id_usuario_responsable → usuario.id_usuario`

Cardinalidad:

**Usuario 1:N Registro Auditoría**

Diferentes modificaciones de una misma cita pueden ser realizadas por diferentes usuarios.

### Conservación

Los registros de auditoría deben mantenerse durante al menos el período de conservación establecido.

Cancelar o finalizar una cita no debe eliminar sus registros históricos.

### Separación conceptual

`REGISTRO_AUDITORIA` no representa observabilidad técnica.

Auditoría funcional y observabilidad continúan siendo conceptos diferentes.

### Trazabilidad

RF-22, RN-23, RN-24, RN-25, RNF-18, D-12, D-20.

---

# 3.10 Parametros_Configuracion

**Propósito:** representar valores configurables utilizados por las reglas de negocio.

| Atributo | Tipo lógico | Restricción lógica | Descripción |
|---|---|---|---|
| id_parametro | Identificador | PK, obligatorio | Identificador |
| clave | Texto | Obligatorio, único | Nombre técnico del parámetro |
| valor | Texto lógico | Obligatorio | Valor configurable |
| tipo_dato | Dominio | Obligatorio | Tipo lógico esperado |
| descripcion | Texto | Obligatorio | Significado del parámetro |

### Parámetros identificados

| Clave conceptual | Valor actual | Fuente |
|---|---|---|
| tiempo_min_cancelacion_minutos | PENDIENTE | RN-26, D-02 |
| tiempo_min_reprogramacion_minutos | PENDIENTE | RN-26, D-02 |
| tolerancia_no_asistida_minutos | PENDIENTE | RN-27, D-03 |
| anticipacion_maxima_dias | PENDIENTE | RN-28, D-04 |
| tiempo_inactividad_sesion_minutos | 15 inicial configurable | RNF-07, D-13 |

### Importante

No se asignan valores arbitrarios a:

- D-02;
- D-03;
- D-04.

Estos valores permanecen pendientes hasta que exista una decisión aprobada.

El valor de 15 minutos para inactividad de sesión se conserva porque está establecido como valor inicial configurable.

### Trazabilidad

RN-26, RN-27, RN-28, RNF-07, D-02, D-03, D-04, D-13.

---

# 3.11 Rol

**Propósito:** representar los roles utilizados para autorización.

| Atributo | Tipo lógico | Restricción lógica | Descripción |
|---|---|---|---|
| id_rol | Identificador | PK, obligatorio | Identificador |
| nombre | Texto | Obligatorio, único | Nombre del rol |
| descripcion | Texto | Opcional | Descripción |

### Roles conceptuales identificados

- paciente;
- médico;
- personal de admisión/recepción;
- administrador.

### Regla

Un usuario puede disponer de varios roles.

Un mismo rol puede estar asociado a múltiples usuarios.

### Trazabilidad

RF-21, RNF-05.

---

# 3.12 Permiso

**Propósito:** representar las operaciones o capacidades autorizadas.

| Atributo | Tipo lógico | Restricción lógica | Descripción |
|---|---|---|---|
| id_permiso | Identificador | PK, obligatorio | Identificador |
| nombre | Texto | Obligatorio, único | Nombre del permiso |
| descripcion | Texto | Obligatorio | Operación autorizada |

### Regla

Un permiso puede pertenecer a varios roles.

Un rol puede contener varios permisos.

Los permisos exactos correspondientes al personal de admisión permanecen pendientes mediante D-10.

### Trazabilidad

RF-21, RNF-05, D-10.

---

# 3.13 Usuario_Rol

**Propósito:** resolver la relación N:M entre Usuario y Rol.

| Atributo | Tipo lógico | Restricción lógica |
|---|---|---|
| id_usuario | Identificador | PK compuesta, FK → usuario.id_usuario, obligatorio |
| id_rol | Identificador | PK compuesta, FK → rol.id_rol, obligatorio |

### Clave primaria lógica

`(id_usuario, id_rol)`

### Cardinalidad

**Usuario N:M Rol**

Un usuario puede disponer de varios roles.

Un rol puede estar asociado con múltiples usuarios.

### Trazabilidad

RF-21, RNF-05.

---

# 3.14 Rol_Permiso

**Propósito:** resolver la relación N:M entre Rol y Permiso.

| Atributo | Tipo lógico | Restricción lógica |
|---|---|---|
| id_rol | Identificador | PK compuesta, FK → rol.id_rol, obligatorio |
| id_permiso | Identificador | PK compuesta, FK → permiso.id_permiso, obligatorio |

### Clave primaria lógica

`(id_rol, id_permiso)`

### Cardinalidad

**Rol N:M Permiso**

Un rol puede contener varios permisos.

Un permiso puede estar asignado a múltiples roles.

### Trazabilidad

RF-21, RNF-05.

---

# 4. Resumen de relaciones lógicas

| Relación | Cardinalidad | Implementación lógica |
|---|---|---|
| Paciente – Cita | 1:N | `cita.id_paciente` |
| Médico – Horario | 1:N | `horario.id_medico` |
| Médico – Cita | 1:N | Indirecta: `cita.id_horario → horario.id_medico` |
| Horario – Cita | 1 : 0..1 activa | `cita.id_horario` + regla de no doble reserva activa |
| Especialidad – Cita | 1:N | `cita.id_especialidad` |
| Médico – Especialidad | N:M | `medico_especialidad` |
| Usuario – Paciente | 0..1 : 0..1 | `usuario.id_paciente` opcional y único |
| Usuario – Médico | 0..1 : 0..1 | `usuario.id_medico` opcional y único |
| Usuario – Cita | 1:N | `cita.id_usuario_registrador` |
| Cita – Atención Virtual | 1 : 0..1 | `atencion_virtual.id_cita` |
| Cita – Registro Auditoría | 1:N | `registro_auditoria.id_cita` |
| Usuario – Registro Auditoría | 1:N | `registro_auditoria.id_usuario_responsable` |
| Usuario – Rol | N:M | `usuario_rol` |
| Rol – Permiso | N:M | `rol_permiso` |

---

# 5. Reglas de integridad lógica

## 5.1 Cita obligatoria

Toda cita debe estar asociada con:

- un paciente;
- una especialidad;
- un horario;
- un usuario registrador.

Además, toda cita tiene exactamente un médico responsable **derivado del horario seleccionado**.

Ruta:

`cita → horario → medico`

---

## 5.2 Médico–Especialidad

Todo médico debe encontrarse asociado al menos a una especialidad activa.

La especialidad seleccionada en una cita debe corresponder al médico propietario del horario.

Debe cumplirse:

`(horario.id_medico, cita.id_especialidad)`

→ asociación existente en:

`medico_especialidad(id_medico, id_especialidad)`

---

## 5.3 Horario del médico

El horario seleccionado determina el médico de la cita.

No existe un segundo `id_medico` almacenado dentro de `cita`.

Por tanto, no puede producirse una diferencia entre:

- médico registrado en la cita;
- médico propietario del horario.

---

## 5.4 Doble reserva

No pueden existir simultáneamente dos citas activas incompatibles para el mismo bloque de horario.

**No se establece una unicidad absoluta sobre `cita.id_horario`.**

La conservación histórica debe permitir mantener referencias de:

- citas canceladas;
- citas reprogramadas;
- citas finalizadas;
- citas no asistidas;

cuando corresponda.

La implementación concreta de la exclusión de reservas activas incompatibles se define posteriormente en integridad y concurrencia.

---

## 5.5 Superposición del paciente

Un paciente no puede mantener citas activas cuyos intervalos temporales se superpongan.

El mecanismo técnico se define en etapas posteriores.

---

## 5.6 Estados de la cita

Estados permitidos:

- Programada;
- Confirmada;
- En atención;
- Finalizada;
- Cancelada;
- No asistida.

Transiciones permitidas:

- Programada → Confirmada
- Programada → Cancelada
- Confirmada → En atención
- Confirmada → Cancelada
- Confirmada → No asistida
- En atención → Finalizada

No existe reversión desde:

- Finalizada;
- Cancelada;
- No asistida.

---

## 5.7 Reprogramación

Una reprogramación debe:

- asignar un nuevo horario disponible;
- liberar el horario anterior;
- mantener consistencia de la operación completa;
- conservar el historial correspondiente.

El mecanismo de transacción se define en el Paso 12.

---

## 5.8 Cancelación

Cancelar una cita debe liberar su horario para futuras reservas compatibles sin eliminar el historial de la cita.

---

## 5.9 Atención virtual

Una cita virtual debe disponer de información de acceso.

Una cita presencial no requiere registro en `atencion_virtual`.

---

## 5.10 Auditoría

Las modificaciones relevantes sobre una cita deben generar trazabilidad mediante `registro_auditoria`.

Cada registro identifica:

- cita;
- usuario responsable;
- fecha;
- hora;
- acción;
- valor anterior;
- valor actualizado.

---

## 5.11 Conservación

Las citas:

- Finalizadas;
- Canceladas;
- No asistidas;

deben conservarse durante el período definido en los requisitos.

Los registros de auditoría relacionados también deben conservarse.

---

# 6. Decisiones pendientes

| Decisión | Tema | Impacto lógico | Estado |
|---|---|---|---|
| D-01 | Proveedor de atención virtual | Datos externos de `atencion_virtual` | Pendiente |
| D-02 | Tiempo mínimo de cancelación/reprogramación | Parámetro configurable | Pendiente |
| D-03 | Tolerancia para no asistencia | Parámetro configurable | Pendiente |
| D-04 | Anticipación máxima de reserva | Parámetro configurable | Pendiente |
| D-07 | Médico inactivo con citas futuras | Política operativa | Pendiente |
| D-08 | Nivel de habilitación virtual | Médico / Especialidad / Médico_Especialidad / Horario | Pendiente |
| D-09 | Modalidad en Horario | Puede modificar estructura de `horario` | Pendiente |
| D-10 | Permisos exactos de admisión | Rol/Permiso | Pendiente |
| D-14 | Formatos de datos | Validaciones de Paciente, Médico y Usuario | Pendiente |
| D-21 | Control de concurrencia | Reserva atómica | Tratado técnicamente en Paso 12 |

---

# 7. Tratamiento de D-08 y D-09

## D-08 — Habilitación de modalidad virtual

El modelo lógico **no incorpora todavía un atributo** de habilitación virtual en:

- `medico`;
- `especialidad`;
- `medico_especialidad`;
- `horario`.

La decisión continúa abierta.

---

## D-09 — Modalidad del horario

`horario` no incorpora por ahora un atributo `modalidad`.

`cita.modalidad` permanece porque cada cita debe identificar si la atención solicitada es presencial o virtual.

Esto no resuelve D-09, ya que todavía debe decidirse si el horario también restringirá o determinará la modalidad.

---

# 8. Mapeo de requisitos principales

| Requisito | Elemento lógico principal |
|---|---|
| RF-01 | paciente |
| RF-02 | medico |
| RF-03 | especialidad |
| RF-04 | medico_especialidad |
| RF-05 | horario |
| RF-06 | especialidad + medico_especialidad |
| RF-07 | medico + horario |
| RF-08 | horario |
| RF-09 | cita |
| RF-10 | cita.id_usuario_registrador |
| RF-11 | cita + horario |
| RF-12 | cita + horario |
| RF-13 | cita.estado |
| RF-14 | paciente + cita |
| RF-15 | paciente + cita |
| RF-16 | medico + horario + cita |
| RF-17 | horario |
| RF-18 | cita.modalidad |
| RF-19 | atencion_virtual |
| RF-20 | cita.fecha_hora_inicio_atencion + cita.fecha_hora_fin_atencion |
| RF-21 | usuario + rol + permiso + usuario_rol + rol_permiso |
| RF-22 | registro_auditoria |

---

# 9. Mapeo de RNF principales

| RNF | Implicación lógica |
|---|---|
| RNF-04 | Usuario requerido para operaciones protegidas |
| RNF-05 | RBAC mediante Usuario, Rol y Permiso |
| RNF-06 | Usuario almacena únicamente credencial protegida |
| RNF-07 | Parámetro de inactividad configurable |
| RNF-11 | Regla de no doble reserva + atomicidad |
| RNF-17 | Observabilidad separada de auditoría |
| RNF-18 | Conservación de auditoría ≥ período definido |
| RNF-01/02/03 | Se evalúan mediante diseño físico y rendimiento |
| RNF-08/09/10 | Corresponden a infraestructura, respaldo y recuperación |
| RNF-13/14/15 | Corresponden principalmente a interfaz y accesibilidad |
| RNF-19 | No altera directamente el modelo lógico |

---

# 10. Observabilidad técnica

La observabilidad técnica continúa fuera del conjunto de tablas de dominio.

Debe mantenerse separada de `registro_auditoria`.

La observabilidad puede registrar eventos como:

- errores de aplicación;
- fallos de autenticación;
- errores de integración;
- fallas en operaciones críticas;
- eventos técnicos relevantes.

Su mecanismo concreto de persistencia corresponde a etapas técnicas posteriores.

---

# 11. Aspectos deliberadamente no definidos en el modelo lógico

En este modelo lógico no se definen:

- tamaños físicos definitivos de columnas;
- `AUTO_INCREMENT`;
- `IDENTITY`;
- `SEQUENCE`;
- sintaxis de expresiones regulares;
- índices físicos;
- índices parciales;
- triggers concretos;
- procedimientos almacenados;
- `SELECT FOR UPDATE`;
- niveles de aislamiento;
- estrategias específicas de concurrencia;
- particionamiento;
- compresión;
- sintaxis SQL;
- acciones físicas concretas `ON DELETE`;
- características exclusivas de PostgreSQL;
- características exclusivas de MySQL/MariaDB;
- características exclusivas de SQL Server.

Estos elementos corresponden a pasos posteriores del workflow.

---

# 12. Resultado de la normalización incorporado

El Paso 05 determinó la dependencia:

`id_horario → id_medico`

Debido a que `cita` contenía anteriormente ambos atributos, se producía:

`id_cita → id_horario → id_medico`

Para evitar esta dependencia transitiva se elimina:

`cita.id_medico`

El médico de la cita se deriva mediante:

`cita.id_horario → horario.id_medico`

## Resultado

Con esta corrección:

- `cita` conserva `id_especialidad`;
- `cita` conserva `id_horario`;
- `cita` ya no almacena `id_medico`;
- el médico se obtiene a través de `horario`;
- no se crea una nueva tabla;
- se mantienen las 14 tablas del modelo.

---

# 13. Resumen del modelo lógico

El modelo contiene **14 tablas lógicas**.

## 12 tablas derivadas de entidades conceptuales

1. `paciente`
2. `medico`
3. `usuario`
4. `especialidad`
5. `medico_especialidad`
6. `horario`
7. `cita`
8. `atencion_virtual`
9. `registro_auditoria`
10. `parametros_configuracion`
11. `rol`
12. `permiso`

## 2 tablas asociativas adicionales

13. `usuario_rol`
14. `rol_permiso`

`medico_especialidad` ya formaba parte del modelo conceptual aprobado y actúa simultáneamente como entidad asociativa.

---

# 14. Conclusiones del Paso 04 actualizado

1. El modelo conceptual fue transformado en un modelo lógico relacional.

2. Se mantienen **14 tablas lógicas**.

3. Se resolvieron las relaciones N:M de Usuario–Rol y Rol–Permiso mediante tablas asociativas.

4. La relación Médico–Especialidad continúa representada mediante `medico_especialidad`.

5. Usuario continúa separado conceptualmente de Paciente y Médico.

6. Las relaciones Usuario–Paciente y Usuario–Médico se representan mediante referencias opcionales y únicas en `usuario`.

7. La cita identifica al usuario que realiza el registro inicial.

8. Las modificaciones posteriores se trazan mediante `registro_auditoria`.

9. La normalización del Paso 05 determinó que `cita.id_medico` era redundante.

10. `cita.id_medico` fue eliminado del modelo lógico.

11. El médico de una cita se obtiene mediante:

   `cita.id_horario → horario.id_medico`.

12. La especialidad seleccionada permanece almacenada en `cita`.

13. La coherencia Médico–Especialidad se valida mediante:

   `(horario.id_medico, cita.id_especialidad)`

   contra:

   `medico_especialidad(id_medico, id_especialidad)`.

14. La regla Horario–Cita continúa siendo máximo una reserva activa incompatible por bloque.

15. **No se impone unicidad absoluta sobre `id_horario`**, porque debe conservarse el historial.

16. D-08 y D-09 continúan pendientes.

17. D-02, D-03 y D-04 permanecen como parámetros configurables pendientes y no reciben valores arbitrarios.

18. El modelo continúa conceptualmente independiente del mecanismo físico concreto utilizado para implementar las restricciones.

---

# 15. Resultado del Paso 04 corregido

**Agente utilizado:** `database-engineer`  
**Skill utilizado:** `database-schema-designer`  
**Ruta del skill:** `.agents/skills/database-schema-designer/SKILL.md`

**Archivo:**

`proyecto/base_datos/03_modelo_logico/modelo_logico.md`

**Paso de origen:**

`Paso 04 — Modelo lógico`

**Corrección posterior:**

Propagación de la normalización establecida en el Paso 05 y del hallazgo bloqueante detectado durante el Paso 14 — Revisión DBA.

**Cambio principal:**

Eliminar:

`cita.id_medico`

y utilizar:

`cita.id_horario → horario.id_medico`

para determinar el médico correspondiente.

**Estado:**

Corregido manualmente y pendiente de nueva revisión DBA.

---

# 16. Control de avance

Esta corrección **no autoriza automáticamente el Paso 15 — Generación SQL**.

El cambio debe propagarse también a los artefactos posteriores que todavía dependan de:

`cita.id_medico`

incluyendo:

- modelo físico;
- integridad;
- seguridad, si existen políticas dependientes;
- auditoría e histórico;
- índices y rendimiento;
- transacciones y concurrencia;
- migraciones.

Después de corregir los artefactos afectados debe repetirse:

`Paso 14 — Revisión DBA`

El resultado deberá ser nuevamente:

- `STATUS: APPROVED`;
- `STATUS: CHANGES_REQUIRED`;
- o `STATUS: REJECTED`.

Solo un:

`STATUS: APPROVED`

permitirá preparar el Paso 15.

**NO generar SQL todavía.**

**DETENERSE y esperar nueva validación DBA.**