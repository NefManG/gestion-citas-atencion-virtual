# Informe de Normalización — Paso 05

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español  
**Generado:** Paso 05 — Normalización  
**Agente utilizado:** `database-engineer`  
**Skill utilizado:** `database-schema-designer`  
**Ruta del skill:** `.agents/skills/database-schema-designer/SKILL.md`  
**Workflow:** `02_database_workflow`  
**Fuente principal:** `proyecto/base_datos/03_modelo_logico/modelo_logico.md`  
**Estado:**pendiente de validación humana  

---

## 1. Objetivo del Paso 05

El objetivo de este paso es revisar formalmente el modelo lógico generado en el Paso 04 y comprobar el cumplimiento de:

- Primera Forma Normal (1FN);
- Segunda Forma Normal (2FN);
- Tercera Forma Normal (3FN);
- Forma Normal de Boyce-Codd (BCNF), cuando corresponda.

La revisión busca identificar:

- atributos no atómicos;
- dependencias parciales;
- dependencias transitivas;
- dependencias funcionales cuyo determinante no sea una superclave;
- redundancias;
- anomalías de inserción;
- anomalías de actualización;
- anomalías de eliminación.

Se revisan las **14 tablas lógicas** definidas en el modelo lógico.

En este paso:

- no se selecciona un SGBD;
- no se genera SQL;
- no se diseñan índices físicos;
- no se definen triggers;
- no se definen mecanismos específicos de concurrencia;
- no se ejecuta el Paso 06.

---

## 2. Tablas evaluadas

Las tablas revisadas son:

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

Las primeras 12 derivan de las entidades conceptuales aprobadas.

Las tablas:

- `usuario_rol`
- `rol_permiso`

fueron incorporadas en el modelo lógico para resolver las relaciones N:M correspondientes.

`medico_especialidad` ya formaba parte del modelo conceptual y representa la asociación N:M entre Médico y Especialidad.

---

## 3. Criterios utilizados

| Forma normal | Criterio |
|---|---|
| **1FN** | Los atributos deben contener valores atómicos y no deben existir grupos repetitivos |
| **2FN** | Cumple 1FN y los atributos no clave deben depender de la totalidad de la clave |
| **3FN** | Cumple 2FN y no existen dependencias transitivas entre atributos no clave |
| **BCNF** | Para toda dependencia funcional no trivial `X → Y`, `X` debe ser una superclave |

La evaluación se realiza utilizando únicamente las dependencias funcionales sustentadas por las reglas del modelo.

---

# 4. Revisión por tabla

## 4.1 Paciente

### Clave primaria

`id_paciente`

### Clave candidata

`documento_identidad`

### Dependencias funcionales

`id_paciente → nombre, apellidos, documento_identidad, fecha_nacimiento, telefono, correo_electronico`

Debido a su unicidad:

`documento_identidad → id_paciente, nombre, apellidos, fecha_nacimiento, telefono, correo_electronico`

### 1FN

Cumple.

Todos los atributos representan valores atómicos dentro de su dominio.

### 2FN

Cumple.

La clave primaria es simple.

### 3FN

Cumple.

No existen dependencias transitivas identificadas.

### BCNF

Cumple porque los determinantes identificados corresponden a claves candidatas.

**Resultado:** BCNF.

---

## 4.2 Médico

### Clave primaria

`id_medico`

### Clave candidata

`numero_colegiado`

### Dependencias funcionales

`id_medico → nombre_completo, numero_colegiado, activo`

Por unicidad:

`numero_colegiado → id_medico, nombre_completo, activo`

### 1FN

Cumple.

### 2FN

Cumple.

### 3FN

Cumple.

### BCNF

Cumple.

**Resultado:** BCNF.

---

## 4.3 Usuario

### Clave primaria

`id_usuario`

### Claves candidatas

- `username`
- `email`

### Atributos evaluados

- `id_usuario`
- `username`
- `password_hash`
- `email`
- `activo`
- `id_paciente`
- `id_medico`

### Dependencia principal

`id_usuario → username, password_hash, email, activo, id_paciente, id_medico`

Además, por las restricciones de unicidad:

`username → id_usuario`

`email → id_usuario`

Las referencias opcionales:

- `id_paciente`
- `id_medico`

representan las relaciones Usuario–Paciente y Usuario–Médico respectivamente.

### 1FN

Cumple.

### 2FN

Cumple.

La clave primaria es simple.

### 3FN

Cumple.

No se identifica un atributo no clave que determine otro atributo no clave dentro de esta relación.

### BCNF

Cumple con las dependencias funcionales actualmente definidas.

### RNF-06

`password_hash` representa únicamente la credencial protegida.

El algoritmo concreto de protección corresponde al Paso 09 — Seguridad y no afecta la normalización.

**Resultado:** BCNF.

---

## 4.4 Especialidad

### Clave primaria

`id_especialidad`

### Clave candidata

`nombre`

### Dependencias funcionales

`id_especialidad → nombre, activo`

`nombre → id_especialidad, activo`

### 1FN

Cumple.

### 2FN

Cumple.

### 3FN

Cumple.

### BCNF

Cumple.

**Resultado:** BCNF.

---

## 4.5 Medico_Especialidad

### Clave primaria compuesta

`(id_medico, id_especialidad)`

### Atributos

- `id_medico`
- `id_especialidad`
- `fecha_asociacion`

### Dependencia funcional

`(id_medico, id_especialidad) → fecha_asociacion`

No existe:

`id_medico → id_especialidad`

porque un médico puede estar asociado a varias especialidades.

Tampoco existe:

`id_especialidad → id_medico`

porque una especialidad puede estar asociada a varios médicos.

### 1FN

Cumple.

### 2FN

Cumple.

`fecha_asociacion` depende de la clave compuesta completa.

### 3FN

Cumple.

No existen dependencias transitivas.

### BCNF

Cumple.

### RN-02

La regla que exige que cada médico tenga al menos una especialidad activa corresponde a una regla de integridad del conjunto de datos y no exige una nueva descomposición.

**Resultado:** BCNF.

---

## 4.6 Horario

### Clave primaria

`id_horario`

### Atributos evaluados

- `id_horario`
- `id_medico`
- `dia_semana`
- `fecha_especifica`
- `hora_inicio`
- `hora_fin`
- `estado`

### Dependencia funcional principal

`id_horario → id_medico, dia_semana, fecha_especifica, hora_inicio, hora_fin, estado`

No existe:

`id_medico → hora_inicio, hora_fin`

porque un médico puede definir múltiples horarios.

### 1FN

Cumple.

### 2FN

Cumple.

La clave primaria es simple.

### 3FN

Cumple.

No existen dependencias transitivas identificadas dentro de `horario`.

### BCNF

Cumple.

### D-09

No se incluye el atributo `modalidad` en `horario`.

La decisión D-09 continúa pendiente.

**Resultado:** BCNF.

---

# 4.7 Cita

La tabla `cita` requiere un análisis especial porque el modelo lógico original almacenaba simultáneamente:

- `id_medico`;
- `id_horario`.

### Estructura original evaluada

- `id_cita`
- `id_paciente`
- `id_medico`
- `id_especialidad`
- `id_horario`
- `id_usuario_registrador`
- `estado`
- `modalidad`
- `fecha_hora_programada`
- `fecha_hora_inicio_atencion`
- `fecha_hora_fin_atencion`

---

## 4.7.1 Dependencia funcional detectada

Cada horario pertenece exactamente a un médico.

Por tanto:

`id_horario → id_medico`

Al almacenar ambos atributos dentro de `cita` se produce:

`id_cita → id_horario`

y:

`id_horario → id_medico`

por lo que:

`id_cita → id_horario → id_medico`

Esto constituye una dependencia transitiva.

---

## 4.7.2 Problema de redundancia

Mantener simultáneamente:

`cita.id_horario`

y:

`cita.id_medico`

permite potencialmente una inconsistencia.

Por ejemplo:

- `id_horario` pertenece al Médico A;
- `cita.id_medico` contiene Médico B.

La misma información sobre el médico estaría representada por dos caminos diferentes.

---

## 4.7.3 Corrección aplicada

Para eliminar la dependencia transitiva se determina retirar:

`id_medico`

de la tabla lógica `cita`.

El médico correspondiente se obtiene mediante:

`cita.id_horario`

→

`horario.id_medico`

---

## 4.7.4 Estructura normalizada de Cita

Después de la corrección, `cita` contiene:

- `id_cita`
- `id_paciente`
- `id_especialidad`
- `id_horario`
- `id_usuario_registrador`
- `estado`
- `modalidad`
- `fecha_hora_programada`
- `fecha_hora_inicio_atencion`
- `fecha_hora_fin_atencion`

### Dependencia principal

`id_cita → id_paciente, id_especialidad, id_horario, id_usuario_registrador, estado, modalidad, fecha_hora_programada, fecha_hora_inicio_atencion, fecha_hora_fin_atencion`

No se identifica otra dependencia transitiva obligatoria entre los atributos no clave con las reglas actualmente aprobadas.

---

## 4.7.5 id_especialidad no constituye una violación de BCNF

La presencia de:

`id_especialidad`

dentro de `cita` no constituye por sí sola una violación de 3FN ni de BCNF.

Un médico puede estar asociado con múltiples especialidades.

Por tanto, no existe la dependencia:

`id_medico → id_especialidad`

La especialidad utilizada para una cita es una elección propia de esa cita.

Por esa razón:

`id_especialidad`

debe conservarse.

---

## 4.7.6 Coherencia Médico–Especialidad

Aunque `id_medico` se elimina de `cita`, continúa vigente la regla que exige que la especialidad seleccionada corresponda al médico responsable.

El médico se obtiene mediante:

`cita.id_horario → horario.id_medico`

Posteriormente debe comprobarse que:

`(horario.id_medico, cita.id_especialidad)`

exista en:

`medico_especialidad(id_medico, id_especialidad)`

Esta es una regla de integridad y no una dependencia funcional que obligue a eliminar `id_especialidad`.

---

## 4.7.7 Formas normales después de la corrección

### 1FN

Cumple.

### 2FN

Cumple.

La clave primaria es simple.

### 3FN

Cumple después de eliminar `id_medico` redundante.

### BCNF

Cumple con las dependencias funcionales actualmente identificadas.

**Resultado:** BCNF después de la corrección.

---

## 4.8 Atencion_Virtual

### Clave primaria

`id_cita`

Esta clave también referencia la cita correspondiente.

### Atributos evaluados

- `id_cita`
- `id_sesion_externa`
- `enlace_acceso`
- `estado_disponibilidad`
- `fecha_hora_incidente`
- `detalles_incidente`

### Dependencia funcional

`id_cita → id_sesion_externa, enlace_acceso, estado_disponibilidad, fecha_hora_incidente, detalles_incidente`

### 1FN

Cumple de acuerdo con el alcance actual.

### 2FN

Cumple.

### 3FN

Cumple.

### BCNF

Cumple porque `id_cita` es la clave de la relación.

### Incidentes

Actualmente se contempla como máximo la información de un incidente vinculada a una atención virtual.

Si posteriormente se define que una atención virtual puede registrar múltiples incidentes históricos, deberá evaluarse una entidad independiente:

`incidente_virtual`

con una relación:

`Atencion_Virtual 1:N Incidente_Virtual`

Esta evolución no se incorpora sin una regla aprobada que la requiera.

**Resultado:** BCNF.

---

## 4.9 Registro_Auditoria

### Clave primaria

`id_registro`

### Atributos evaluados

- `id_registro`
- `id_cita`
- `id_usuario_responsable`
- `fecha_evento`
- `hora_evento`
- `accion`
- `valor_anterior`
- `valor_actual`

### Dependencia funcional

`id_registro → id_cita, id_usuario_responsable, fecha_evento, hora_evento, accion, valor_anterior, valor_actual`

No existe:

`id_cita → id_registro`

porque una cita puede generar varios eventos de auditoría.

Tampoco existe:

`id_usuario_responsable → id_registro`

porque un usuario puede ser responsable de múltiples eventos.

### 1FN

Cumple.

### 2FN

Cumple.

### 3FN

Cumple.

### BCNF

Cumple.

**Resultado:** BCNF.

---

## 4.10 Parametros_Configuracion

### Clave primaria

`id_parametro`

### Clave candidata

`clave`

### Atributos evaluados

- `id_parametro`
- `clave`
- `valor`
- `tipo_dato`
- `descripcion`

### Dependencias funcionales

`id_parametro → clave, valor, tipo_dato, descripcion`

`clave → id_parametro, valor, tipo_dato, descripcion`

### 1FN

Cumple.

### 2FN

Cumple.

### 3FN

Cumple.

### BCNF

Cumple.

Los valores pendientes de D-02, D-03 y D-04 son valores configurables y no generan dependencias funcionales.

**Resultado:** BCNF.

---

## 4.11 Rol

### Clave primaria

`id_rol`

### Clave candidata

`nombre`

### Dependencias funcionales

`id_rol → nombre, descripcion`

`nombre → id_rol, descripcion`

### 1FN

Cumple.

### 2FN

Cumple.

### 3FN

Cumple.

### BCNF

Cumple.

**Resultado:** BCNF.

---

## 4.12 Permiso

### Clave primaria

`id_permiso`

### Clave candidata

`nombre`

### Dependencias funcionales

`id_permiso → nombre, descripcion`

`nombre → id_permiso, descripcion`

### 1FN

Cumple.

### 2FN

Cumple.

### 3FN

Cumple.

### BCNF

Cumple.

**Resultado:** BCNF.

---

## 4.13 Usuario_Rol

### Clave primaria compuesta

`(id_usuario, id_rol)`

No existe:

`id_usuario → id_rol`

porque un usuario puede disponer de varios roles.

No existe:

`id_rol → id_usuario`

porque un rol puede estar asociado a varios usuarios.

### 1FN

Cumple.

### 2FN

Cumple.

No existen atributos no clave dependientes únicamente de una parte de la clave.

### 3FN

Cumple.

### BCNF

Cumple.

**Resultado:** BCNF.

---

## 4.14 Rol_Permiso

### Clave primaria compuesta

`(id_rol, id_permiso)`

No existe:

`id_rol → id_permiso`

porque un rol puede disponer de varios permisos.

No existe:

`id_permiso → id_rol`

porque un permiso puede pertenecer a múltiples roles.

### 1FN

Cumple.

### 2FN

Cumple.

### 3FN

Cumple.

### BCNF

Cumple.

**Resultado:** BCNF.

---

# 5. Resumen de normalización

| Tabla | 1FN | 2FN | 3FN | BCNF | Observación |
|---|---|---|---|---|---|
| paciente | ✓ | ✓ | ✓ | ✓ | Sin cambios |
| medico | ✓ | ✓ | ✓ | ✓ | Sin cambios |
| usuario | ✓ | ✓ | ✓ | ✓ | Sin cambios |
| especialidad | ✓ | ✓ | ✓ | ✓ | Sin cambios |
| medico_especialidad | ✓ | ✓ | ✓ | ✓ | Sin cambios |
| horario | ✓ | ✓ | ✓ | ✓ | Sin cambios |
| cita | ✓ | ✓ | ✓ | ✓ | Se elimina `id_medico` redundante |
| atencion_virtual | ✓ | ✓ | ✓ | ✓ | Sin cambios |
| registro_auditoria | ✓ | ✓ | ✓ | ✓ | Sin cambios |
| parametros_configuracion | ✓ | ✓ | ✓ | ✓ | Sin cambios |
| rol | ✓ | ✓ | ✓ | ✓ | Sin cambios |
| permiso | ✓ | ✓ | ✓ | ✓ | Sin cambios |
| usuario_rol | ✓ | ✓ | ✓ | ✓ | Sin cambios |
| rol_permiso | ✓ | ✓ | ✓ | ✓ | Sin cambios |

## Resultado

Se evaluaron **14 tablas**.

Después de aplicar el ajuste identificado en `cita`:

- 14 cumplen 1FN;
- 14 cumplen 2FN;
- 14 cumplen 3FN;
- 14 cumplen BCNF con las dependencias funcionales actualmente aprobadas.

No quedan excepciones de normalización documentadas.

---

# 6. Cambio estructural derivado del Paso 05

El análisis de normalización produce **un cambio estructural** respecto del modelo lógico aprobado inicialmente.

## Tabla afectada

`cita`

## Atributo eliminado

`id_medico`

## Motivo

Existe la dependencia funcional:

`id_horario → id_medico`

por lo que almacenar ambos atributos dentro de `cita` genera la dependencia transitiva:

`id_cita → id_horario → id_medico`

---

## Estructura de Cita antes

- `id_cita`
- `id_paciente`
- `id_medico`
- `id_especialidad`
- `id_horario`
- `id_usuario_registrador`
- `estado`
- `modalidad`
- `fecha_hora_programada`
- `fecha_hora_inicio_atencion`
- `fecha_hora_fin_atencion`

---

## Estructura de Cita después

- `id_cita`
- `id_paciente`
- `id_especialidad`
- `id_horario`
- `id_usuario_registrador`
- `estado`
- `modalidad`
- `fecha_hora_programada`
- `fecha_hora_inicio_atencion`
- `fecha_hora_fin_atencion`

---

# 7. Relación Médico–Cita después de la normalización

La relación conceptual:

**Médico 1:N Cita**

continúa siendo válida.

Sin embargo, en el modelo lógico normalizado se obtiene mediante:

`MEDICO`

→

`HORARIO`

→

`CITA`

Es decir:

`cita.id_horario → horario.id_horario`

y:

`horario.id_medico → medico.id_medico`

Por tanto, no es necesario almacenar nuevamente `id_medico` dentro de `cita`.

---

# 8. Coherencia Médico–Especialidad–Cita

La especialidad elegida para una cita continúa siendo obligatoria.

La regla se valida conceptualmente utilizando:

`cita.id_horario → horario.id_medico`

junto con:

`cita.id_especialidad`

La combinación:

`(horario.id_medico, cita.id_especialidad)`

debe existir en:

`medico_especialidad(id_medico, id_especialidad)`

La implementación concreta de esta regla corresponde a los pasos posteriores de integridad y modelo físico.

---

# 9. Anomalías evitadas

## 9.1 Anomalía de actualización

Antes era posible representar:

- un `id_horario` correspondiente al Médico A;
- un `id_medico` correspondiente al Médico B.

La eliminación de `cita.id_medico` evita esta inconsistencia.

---

## 9.2 Anomalía de inserción

Una nueva cita ya no puede registrar directamente un médico diferente al propietario del horario.

---

## 9.3 Anomalía de mantenimiento

La información sobre el médico responsable del horario tiene una única fuente lógica.

---

# 10. Elementos que se mantienen

## 10.1 id_especialidad en Cita

Se mantiene.

Un médico puede poseer múltiples especialidades.

Por lo tanto:

`id_medico → id_especialidad`

no es una dependencia funcional válida.

La especialidad constituye información propia de la cita.

---

## 10.2 Medico_Especialidad

Se mantiene porque resuelve correctamente la relación N:M entre médicos y especialidades.

---

## 10.3 Usuario_Rol

Se mantiene porque resuelve la relación N:M entre usuarios y roles.

---

## 10.4 Rol_Permiso

Se mantiene porque resuelve la relación N:M entre roles y permisos.

---

## 10.5 Usuario–Paciente y Usuario–Médico

Se mantienen mediante:

- `usuario.id_paciente`
- `usuario.id_medico`

como referencias opcionales y únicas.

Estas relaciones no requieren nuevas tablas con las reglas actualmente aprobadas.

---

# 11. Claves candidatas identificadas

| Tabla | Clave candidata |
|---|---|
| paciente | documento_identidad |
| medico | numero_colegiado |
| usuario | username |
| usuario | email |
| especialidad | nombre |
| parametros_configuracion | clave |
| rol | nombre |
| permiso | nombre |

Estas claves podrán considerarse en el Paso 11 al diseñar los índices.

---

# 12. Atención virtual

La tabla `atencion_virtual` permanece sin descomposición adicional.

Actualmente la información de incidente se considera opcional y asociada al único registro de atención virtual de la cita.

Si posteriormente se establece que una atención puede poseer múltiples incidentes, deberá evaluarse:

`incidente_virtual`

como una nueva tabla relacionada 1:N con `atencion_virtual`.

No se crea esa tabla en este paso porque no existe todavía una regla aprobada que exija múltiples incidentes.

---

# 13. Parámetros de configuración

`parametros_configuracion` permanece como tabla independiente.

Los siguientes valores continúan pendientes:

- tiempo mínimo de cancelación;
- tiempo mínimo de reprogramación;
- tolerancia para no asistencia;
- anticipación máxima para reservas.

El tiempo inicial de inactividad de sesión puede conservarse como valor configurable de acuerdo con los requisitos definidos.

Los valores configurables no modifican la forma normal de la tabla.

---

# 14. Reglas fuera del alcance de la normalización

Este Paso 05 no define todavía la implementación técnica de:

- doble reserva;
- superposición temporal;
- concurrencia;
- bloqueos;
- transacciones;
- transición de estados;
- reprogramación atómica;
- liberación de horario;
- auditoría automática;
- conservación física;
- integridad Médico–Especialidad;
- índices;
- particionamiento.

Estos elementos se desarrollarán en sus respectivos pasos del workflow.

---

# 15. Decisiones pendientes

| Decisión | Descripción | Estado |
|---|---|---|
| D-01 | Proveedor de atención virtual | Pendiente |
| D-02 | Tiempo mínimo para cancelar/reprogramar | Pendiente |
| D-03 | Tolerancia de no asistencia | Pendiente |
| D-04 | Anticipación máxima de reserva | Pendiente |
| D-07 | Médico inactivo con citas futuras | Pendiente |
| D-08 | Nivel de habilitación de modalidad virtual | Pendiente |
| D-09 | Modalidad asociada al horario | Pendiente |
| D-10 | Permisos exactos del personal de admisión | Pendiente |
| D-14 | Formatos definitivos de datos | Pendiente |
| D-21 | Estrategia de concurrencia | Paso 12 |

El proceso de normalización no resuelve estas decisiones.

---

# 16. Trazabilidad del cambio

| Cambio | Motivo | Resultado |
|---|---|---|
| Eliminar `cita.id_medico` | Dependencia transitiva `id_horario → id_medico` | Elimina redundancia |
| Mantener `cita.id_especialidad` | La especialidad no se deriva únicamente del médico | Conserva información propia de la cita |
| Mantener `medico_especialidad` | Asociación N:M | Coherencia Médico–Especialidad |
| Mantener `usuario_rol` | Asociación N:M | RBAC |
| Mantener `rol_permiso` | Asociación N:M | RBAC |
| Mantener incidentes dentro de `atencion_virtual` | Alcance actual no requiere múltiples incidentes | No se agrega tabla |

---

# 17. Resultado del Paso 05

Después de realizar el análisis de normalización:

- se mantienen 14 tablas;
- no se agregan tablas;
- no se elimina ninguna tabla;
- se elimina únicamente `id_medico` de `cita`;
- se mantiene `id_especialidad` en `cita`;
- las 14 tablas cumplen 1FN;
- las 14 tablas cumplen 2FN;
- las 14 tablas cumplen 3FN;
- las 14 tablas cumplen BCNF conforme a las dependencias actualmente aprobadas.

No existe una desnormalización intencional de `id_especialidad`.

No se genera SQL.

No se selecciona SGBD.

---

# 18. Actualización necesaria del modelo lógico

El resultado del Paso 05 debe reflejarse también en:

`proyecto/base_datos/03_modelo_logico/modelo_logico.md`

En la definición de la tabla `cita` debe eliminarse:

`id_medico`

También debe eliminarse cualquier relación lógica directa:

`cita.id_medico → medico.id_medico`

y reemplazarse conceptualmente por:

`cita.id_horario → horario.id_horario`

`horario.id_medico → medico.id_medico`

La relación conceptual Médico–Cita permanece vigente.

---

# 19. Conclusiones

1. Se analizaron las 14 tablas del modelo lógico.

2. Trece tablas no requirieron modificaciones estructurales.

3. La tabla `cita` presentaba una redundancia entre `id_horario` e `id_medico`.

4. Debido a que cada horario pertenece a un único médico, existe:

   `id_horario → id_medico`.

5. La presencia de ambos atributos en `cita` producía:

   `id_cita → id_horario → id_medico`.

6. Para eliminar la dependencia transitiva se retira `id_medico` de `cita`.

7. La relación Médico–Cita continúa obteniéndose mediante Horario.

8. `id_especialidad` permanece en `cita` porque un médico puede poseer múltiples especialidades y la especialidad seleccionada constituye información propia de la cita.

9. La coherencia entre médico y especialidad se validará mediante `medico_especialidad`.

10. Después del ajuste, las 14 tablas cumplen 1FN, 2FN, 3FN y BCNF según las dependencias funcionales actualmente aprobadas.

11. D-08 y D-09 continúan pendientes.

12. No se genera SQL.

13. No se selecciona un SGBD.

14. El modelo queda preparado para el Paso 06 una vez realizada la validación humana.

---

# 20. Próximo paso

**Paso 06 — Selección del DBMS**

En el siguiente paso deben compararse objetivamente:

- PostgreSQL;
- MySQL/MariaDB;
- SQL Server.

La selección debe basarse en los requisitos del sistema y no en preferencia personal.

Se deberán evaluar, entre otros:

- integridad;
- concurrencia;
- transacciones;
- rendimiento;
- seguridad;
- auditoría;
- respaldo y recuperación;
- soporte de restricciones;
- compatibilidad con el proyecto;
- herramientas de administración.

**Salida esperada:**

`proyecto/base_datos/05_modelo_fisico/seleccion_dbms.md`

---

# 21. Checkpoint

Después de la aprobación humana del Paso 05, el checkpoint debe contener:

- `Paso 05 — Normalización` en `completed`;
- `current_step: 6`;
- `next: Paso 06 — Selección del DBMS`;
- `informe_normalizacion.md` registrado en `outputs`.

El Paso 06 no debe ejecutarse automáticamente.

---

## Estado final

**Agente utilizado:** `database-engineer`  
**Skill utilizado:** `database-schema-designer`  
**Archivo:** `proyecto/base_datos/04_normalizacion/informe_normalizacion.md`

**Estado:** Paso 05 corregido y pendiente de validación humana.

**Siguiente paso:** Paso 06 — Selección del DBMS.

**DETENERSE y esperar aprobación humana.**