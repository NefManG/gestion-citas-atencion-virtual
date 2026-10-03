# Modelo Lógico — Sistema de Gestión de Citas y Atención Virtual

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español  
**Generado:** Paso 04 — Modelo lógico  
**Agente utilizado:** `database-engineer`  
**Skill utilizado:** `database-schema-designer`  
**Ruta del skill:** `.agents/skills/database-schema-designer/SKILL.md`  
**Workflow:** `02_database_workflow`  
**Fuente principal:** `proyecto/base_datos/02_modelo_conceptual/modelo_conceptual.md` + `proyecto/base_datos/02_modelo_conceptual/diagrama_er.md`  
**Estado:**pendiente de validación humana  

---

## 1. Objetivo del Paso 04

El presente documento transforma el modelo conceptual aprobado y su diagrama E-R en un **modelo lógico relacional**, definiendo tablas, atributos lógicos, identificadores, claves primarias, claves foráneas, nulabilidad, unicidad y relaciones.

El modelo se mantiene independiente de un sistema gestor de base de datos específico.

En este paso se busca establecer la estructura lógica de persistencia sin entrar todavía en decisiones propias del modelo físico.

### Este paso NO:

- selecciona PostgreSQL, MySQL, MariaDB o SQL Server;
- genera sentencias SQL;
- define índices físicos;
- define estrategias de particionamiento;
- selecciona mecanismos específicos de bloqueo;
- define triggers concretos;
- selecciona funciones particulares de un SGBD;
- ejecuta formalmente la normalización;
- resuelve las decisiones pendientes D-08 y D-09;
- ejecuta el Paso 05.

El resultado de este paso servirá como entrada para:

**Paso 05 — Normalización**

---

## 2. Principios aplicados

| Principio | Aplicación |
|---|---|
| Separación conceptual | Paciente, Médico y Usuario continúan siendo entidades diferentes |
| Integridad lógica | Se identifican PK, FK, unicidad, obligatoriedad y reglas de dominio |
| Relaciones N:M | Se resuelven mediante relaciones asociativas |
| Independencia del SGBD | No se utilizan tipos ni mecanismos exclusivos de un motor |
| Trazabilidad | Las estructuras conservan relación con RF, RNF, RN y decisiones |
| Decisiones pendientes | No se materializan decisiones todavía no aprobadas |
| Preparación para normalización | El modelo será evaluado formalmente en 1FN, 2FN, 3FN y BCNF durante el Paso 05 |

---

# 3. Tablas del modelo lógico

El modelo lógico está compuesto por **14 tablas**:

- 12 derivadas de las entidades conceptuales aprobadas;
- 2 tablas asociativas adicionales necesarias para resolver las relaciones N:M del modelo RBAC.

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
- Un médico puede atender múltiples citas.

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

La selección del algoritmo concreto de protección se realizará durante el Paso 09 — Seguridad.

### Usuario–Paciente

La relación se representa mediante:

`usuario.id_paciente → paciente.id_paciente`

Reglas:

- `id_paciente` es opcional;
- debe ser único cuando exista;
- un paciente puede existir sin usuario;
- una cuenta vinculada a un paciente representa su identidad digital.

Cardinalidad:

**Paciente 0..1 ↔ 0..1 Usuario**

### Usuario–Médico

La relación se representa mediante:

`usuario.id_medico → medico.id_medico`

Reglas:

- `id_medico` es opcional;
- debe ser único cuando exista;
- un médico puede existir sin cuenta;
- una cuenta vinculada a un médico representa su identidad digital.

Cardinalidad:

**Médico 0..1 ↔ 0..1 Usuario**

No se establece todavía una regla que impida que una cuenta pueda relacionarse simultáneamente con Paciente y Médico, ya que el modelo de roles admite múltiples roles y no existe una decisión aprobada que establezca exclusividad.

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

La clave primaria compuesta es:

`(id_medico, id_especialidad)`

Esto garantiza que la combinación Médico–Especialidad no se repita.

### Relaciones

`medico_especialidad.id_medico → medico.id_medico`

`medico_especialidad.id_especialidad → especialidad.id_especialidad`

### Regla RN-02

Cada médico debe estar relacionado con al menos una especialidad activa.

La forma concreta de garantizar esta regla será evaluada en las etapas de integridad y diseño físico.

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
- El horario pertenece a un único médico.
- Debe existir información suficiente para determinar cuándo ocurre el bloque.
- Cuando el diseño utilice `dia_semana`, este representa disponibilidad recurrente.
- Cuando utilice `fecha_especifica`, representa una disponibilidad puntual.
- La relación exacta entre `dia_semana` y `fecha_especifica` será revisada formalmente durante el Paso 05.

### D-09 pendiente

No se incluye un atributo `modalidad` en `horario`.

Continúa pendiente decidir si la modalidad pertenece al horario, a la cita o a otra combinación del modelo.

### Trazabilidad

RF-05, RF-08, RF-17, RN-03, RN-10, RN-12, RN-13, D-09.

---

# 3.7 Cita

**Propósito:** representar la entidad transaccional central del sistema.

| Atributo | Tipo lógico | Restricción lógica | Descripción |
|---|---|---|---|
| id_cita | Identificador | PK, obligatorio | Identificador de la cita |
| id_paciente | Identificador | FK → paciente.id_paciente, obligatorio | Paciente |
| id_medico | Identificador | FK → medico.id_medico, obligatorio | Médico |
| id_especialidad | Identificador | FK lógica, obligatorio | Especialidad seleccionada |
| id_horario | Identificador | FK → horario.id_horario, obligatorio | Bloque utilizado |
| id_usuario_registrador | Identificador | FK → usuario.id_usuario, obligatorio | Usuario que registra la cita |
| estado | Dominio de estado | Obligatorio | Estado de la cita |
| modalidad | Dominio de modalidad | Obligatorio | Presencial o virtual |
| fecha_hora_programada | FechaHora | Obligatorio | Fecha y hora programada |
| fecha_hora_inicio_atencion | FechaHora | Opcional | Inicio real de la atención |
| fecha_hora_fin_atencion | FechaHora | Opcional | Fin real de la atención |

### Estados permitidos

La cita debe encontrarse en uno de los siguientes estados:

- Programada;
- Confirmada;
- En atención;
- Finalizada;
- Cancelada;
- No asistida.

### Modalidades

La modalidad debe identificar si la cita es:

- presencial;
- virtual.

La existencia de este atributo no resuelve D-09.

D-09 continúa determinando si el **Horario** también debe contener información de modalidad.

---

## Relación Paciente–Cita

`cita.id_paciente → paciente.id_paciente`

Cardinalidad:

**Paciente 1:N Cita**

Toda cita debe corresponder a un paciente.

---

## Relación Médico–Cita

`cita.id_medico → medico.id_medico`

Cardinalidad:

**Médico 1:N Cita**

Toda cita debe corresponder a un médico.

---

## Relación Horario–Cita

`cita.id_horario → horario.id_horario`

Cada cita debe utilizar exactamente un horario.

Un horario no puede mantener simultáneamente más de una reserva incompatible.

### Importante

No se establece en este paso una restricción de unicidad física incondicional sobre `id_horario`, porque las citas canceladas, reprogramadas o históricas deben conservarse y el horario puede volver a quedar disponible.

La regla lógica es:

> En un momento determinado no pueden coexistir dos citas activas incompatibles asociadas al mismo horario.

El mecanismo concreto para garantizar esta regla se definirá durante los pasos de integridad, selección del SGBD y transacciones/concurrencia.

---

## Usuario registrador

`cita.id_usuario_registrador → usuario.id_usuario`

Cardinalidad:

**Usuario 1:N Cita**

Representa únicamente al usuario que registró originalmente la cita.

Las modificaciones posteriores deben identificarse mediante `registro_auditoria`.

---

## Coherencia Médico–Especialidad–Cita

La combinación:

`(cita.id_medico, cita.id_especialidad)`

debe corresponder a una asociación válida existente en:

`medico_especialidad(id_medico, id_especialidad)`

Relación lógica:

`cita(id_medico, id_especialidad)`

→

`medico_especialidad(id_medico, id_especialidad)`

Esto garantiza que una cita no pueda asociar un médico con una especialidad que no le corresponda.

La forma física de implementar la referencia se definirá posteriormente.

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

y

`fecha_hora_fin_atencion`

deben estar registradas.

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

Los incidentes continúan formando parte conceptualmente de `atencion_virtual`.

La eventual normalización de incidentes como una entidad independiente será evaluada en el Paso 05.

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

No se asignan valores arbitrarios a D-02, D-03 o D-04.

Permanecen pendientes hasta una decisión humana.

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
| Médico – Cita | 1:N | `cita.id_medico` |
| Médico – Horario | 1:N | `horario.id_medico` |
| Horario – Cita | 1 : 0..1 activa | `cita.id_horario` + regla de no doble reserva |
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
- un médico;
- una especialidad;
- un horario;
- un usuario registrador.

---

## 5.2 Médico–Especialidad

Todo médico debe encontrarse asociado al menos a una especialidad activa.

La combinación médico–especialidad utilizada en una cita debe existir previamente en `medico_especialidad`.

---

## 5.3 Horario del médico

El horario seleccionado debe pertenecer al mismo médico registrado en la cita.

---

## 5.4 Doble reserva

No pueden existir simultáneamente dos citas activas incompatibles para el mismo bloque de horario.

La implementación concreta de esta regla no se define en el Paso 04.

Se analizará posteriormente en:

- integridad;
- selección del SGBD;
- transacciones y concurrencia.

---

## 5.5 Superposición del paciente

Un paciente no puede mantener citas activas cuyos intervalos temporales se superpongan.

El mecanismo técnico se definirá posteriormente.

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
- mantener consistencia de la operación completa.

El mecanismo de transacción se definirá en el Paso 12.

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
| D-21 | Control de concurrencia | Reserva atómica | Pendiente para Paso 12 |

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
| RF-16 | medico + cita |
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
| RNF-11 | Regla de no doble reserva + atomicidad pendiente Paso 12 |
| RNF-17 | Observabilidad separada de auditoría |
| RNF-18 | Conservación de auditoría ≥ período definido |
| RNF-01/02/03 | Se evaluarán posteriormente mediante diseño físico y rendimiento |
| RNF-08/09/10 | Corresponden a infraestructura, respaldo y recuperación |
| RNF-13/14/15 | Corresponden principalmente a interfaz y accesibilidad |
| RNF-19 | No altera directamente el modelo lógico |

---

# 10. Observabilidad técnica

La observabilidad técnica continúa fuera del conjunto de tablas de dominio.

Debe mantenerse separada de `registro_auditoria`.

La observabilidad puede registrar posteriormente eventos como:

- errores de aplicación;
- fallos de autenticación;
- errores de integración;
- fallas en operaciones críticas;
- eventos técnicos relevantes.

Su mecanismo concreto de persistencia se determinará en una etapa posterior.

---

# 11. Aspectos deliberadamente no definidos

En este Paso 04 no se definen:

- tipos físicos del SGBD;
- tamaños definitivos de columnas;
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
- acciones físicas `ON DELETE`;
- características propias de PostgreSQL;
- características propias de MySQL/MariaDB;
- características propias de SQL Server.

Estos elementos corresponden a pasos posteriores del workflow.

---

# 12. Preparación para el Paso 05 — Normalización

El presente modelo **no afirma todavía que todas las tablas estén en 3FN o BCNF**.

La siguiente etapa deberá revisar formalmente:

- Primera Forma Normal (1FN);
- Segunda Forma Normal (2FN);
- Tercera Forma Normal (3FN);
- Forma Normal de Boyce-Codd (BCNF), cuando corresponda.

La revisión deberá prestar especial atención a:

- atributos de `horario`;
- posible redundancia entre `horario` y la fecha/hora programada de `cita`;
- representación de incidentes en `atencion_virtual`;
- estructura de `parametros_configuracion`;
- relaciones asociativas;
- dependencias funcionales;
- atributos derivados;
- posibles redundancias.

Cualquier desnormalización deberá justificarse posteriormente mediante requisitos funcionales, no funcionales o patrones de acceso.

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

# 14. Conclusiones del Paso 04

1. El modelo conceptual fue transformado en un modelo lógico relacional.

2. Se identificaron 14 tablas lógicas.

3. Se resolvieron las relaciones N:M de Usuario–Rol y Rol–Permiso mediante tablas asociativas.

4. La relación Médico–Especialidad continúa representada mediante `medico_especialidad`.

5. Usuario continúa separado conceptualmente de Paciente y Médico.

6. Las relaciones Usuario–Paciente y Usuario–Médico se representan mediante referencias opcionales y únicas en `usuario`.

7. La cita identifica al usuario que realiza el registro inicial.

8. Las modificaciones posteriores se trazan mediante `registro_auditoria`.

9. La combinación Médico–Especialidad utilizada en una cita debe existir en `medico_especialidad`.

10. La regla Horario–Cita se mantiene como máximo una reserva activa incompatible por bloque, sin imponer todavía una restricción física que impida conservar citas históricas.

11. D-08 y D-09 continúan pendientes y no se materializan mediante columnas provisionales.

12. Los valores pendientes de D-02, D-03 y D-04 no se inventan.

13. No se selecciona ningún SGBD.

14. No se genera SQL.

15. No se afirma todavía que el modelo se encuentre en 3FN o BCNF, ya que dicha revisión corresponde al Paso 05.

---

# 15. Resultado del Paso 04

**Agente utilizado:** `database-engineer`  
**Skill utilizado:** `database-schema-designer`  
**Ruta del skill:** `.agents/skills/database-schema-designer/SKILL.md`

**Archivo generado:**

`proyecto/base_datos/03_modelo_logico/modelo_logico.md`

**Paso:**

`Paso 04 — Modelo lógico`

**Estado:**

Paso 04 completado y pendiente de validación humana.

**Siguiente paso:**

`Paso 05 — Normalización`

**Salida esperada del siguiente paso:**

`proyecto/base_datos/04_normalizacion/informe_normalizacion.md`

---

# 16. Control de avance

La finalización de este documento **no autoriza automáticamente el Paso 05**.

Antes de continuar debe realizarse validación humana del modelo lógico.

No se genera SQL.

No se selecciona SGBD.

No se ejecuta normalización en este paso.

**DETENERSE y esperar aprobación humana.**