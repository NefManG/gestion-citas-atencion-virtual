# Informe de Índices y Rendimiento — Paso 11

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español

**Generado:** Paso 11 — Índices y rendimiento

**Agente utilizado:** `database-engineer` (skill `postgresql-table-design`)

**Workflow:** `02_database_workflow`

**DBMS:** PostgreSQL 18.6

**Fuentes principales:** `modelo_fisico.md`, `06_integridad/integridad.md`, `08_auditoria_historico/auditoria_historico.md`

**Estado:** Corregido manualmente después de la Revisión DBA del Paso 14. Pendiente de nueva validación humana.

---

## 1. Objetivo del Paso 11

Diseñar y documentar la **estrategia completa de indexación** para optimizar el rendimiento de consultas críticas, cubriendo:

- Índices obligatorios por restricciones (PK, FK, UNIQUE)
- Índices para patrones de acceso identificados (RF, RNF)
- Índices compuestos y parciales
- Índices para auditoría e histórico
- Estrategia de mantenimiento (autovacuum, REINDEX CONCURRENTLY)
- Estimación de tamaño y overhead

**Salida:** `proyecto/base_datos/10_indices_rendimiento/indices_rendimiento.md`

DETENERSE y esperar aprobación humana.

---

## 2. Principios de Indexación (Skill `postgresql-table-design`)

| Principio | Aplicación |
|-----------|------------|
| **PK siempre indexada** | Automático en PostgreSQL (B-tree) |
| **FK NO se indexa automáticamente** | **Regla crítica**: crear índice manual en cada FK |
| **UNIQUE crea índice** | Automático (B-tree) |
| **CHECK no crea índice** | Solo valida, no acelera búsquedas |
| **Columnas de alta selectividad primero** | En índices compuestos, ordenar según patrón de acceso y selectividad |
| **Índices parciales** | `WHERE` con condiciones estables para reducir tamaño |
| **EXCLUDE USING GIST** | Para restricciones de superposición temporal |
| **Mantenimiento** | `autovacuum` en tablas de alta rotación |

---

## 3. Índices Obligatorios (Generados por Restricciones)

### 3.1 Índices de Clave Primaria (PK)

| Tabla | Índice | Tipo | Tamaño estimado |
|-------|--------|------|-----------------|
| paciente | `paciente_pkey` (id_paciente) | B-tree | ~8 bytes/row |
| medico | `medico_pkey` (id_medico) | B-tree | ~8 bytes/row |
| usuario | `usuario_pkey` (id_usuario) | B-tree | ~8 bytes/row |
| especialidad | `especialidad_pkey` (id_especialidad) | B-tree | ~8 bytes/row |
| medico_especialidad | `medico_especialidad_pkey` (id_medico, id_especialidad) | B-tree compuesto | ~16 bytes/row |
| horario | `horario_pkey` (id_horario) | B-tree | ~8 bytes/row |
| cita | `cita_pkey` (id_cita) | B-tree | ~8 bytes/row |
| atencion_virtual | `atencion_virtual_pkey` (id_cita) | B-tree | ~8 bytes/row |
| registro_auditoria | `registro_auditoria_pkey` | B-tree | Según implementación física |
| parametros_configuracion | `parametros_configuracion_pkey` (id_parametro) | B-tree | ~8 bytes/row |
| rol | `rol_pkey` (id_rol) | B-tree | ~8 bytes/row |
| permiso | `permiso_pkey` (id_permiso) | B-tree | ~8 bytes/row |
| usuario_rol | `usuario_rol_pkey` (id_usuario, id_rol) | B-tree compuesto | ~16 bytes/row |
| rol_permiso | `rol_permiso_pkey` (id_rol, id_permiso) | B-tree compuesto | ~16 bytes/row |

---

### 3.2 Índices de Clave Foránea (FK) — OBLIGATORIOS MANUALES

> **Regla del skill `postgresql-table-design`:** PostgreSQL no indexa automáticamente las claves foráneas. Deben evaluarse índices para acelerar JOIN, validaciones y operaciones sobre las tablas relacionadas.

| FK | Tabla | Columna(s) | Índice Recomendado | Justificación |
|----|-------|------------|-------------------|---------------|
| FK_usuario_paciente | usuario | id_paciente | `idx_usuario_paciente` | Join paciente-usuario, RLS |
| FK_usuario_medico | usuario | id_medico | `idx_usuario_medico` | Join médico-usuario, RLS |
| FK_medico_especialidad_medico | medico_especialidad | id_medico | `idx_me_medico` | Consultas por médico |
| FK_medico_especialidad_especialidad | medico_especialidad | id_especialidad | `idx_me_especialidad` | Consultas por especialidad |
| FK_horario_medico | horario | id_medico | `idx_horario_medico` | **Crítico**: agenda y disponibilidad |
| FK_cita_paciente | cita | id_paciente | `idx_cita_paciente` | **Crítico**: historial paciente |
| FK_cita_horario | cita | id_horario | `idx_cita_horario` | Reserva, concurrencia y relación con médico |
| FK_cita_especialidad | cita | id_especialidad | `idx_cita_especialidad` | Filtro por especialidad |
| FK_cita_usuario_registrador | cita | id_usuario_registrador | `idx_cita_registrador` | Auditoría, trazabilidad |
| FK_atencion_virtual_cita | atencion_virtual | id_cita | `idx_av_cita` | Join 1:1 cita ↔ virtual |
| FK_auditoria_cita | registro_auditoria | id_cita | `idx_auditoria_cita` | **Crítico**: historial auditoría |
| FK_auditoria_usuario | registro_auditoria | id_usuario_responsable | `idx_auditoria_usuario` | Auditoría por responsable |
| FK_usuario_rol_usuario | usuario_rol | id_usuario | `idx_ur_usuario` | Roles por usuario |
| FK_usuario_rol_rol | usuario_rol | id_rol | `idx_ur_rol` | Usuarios por rol |
| FK_rol_permiso_rol | rol_permiso | id_rol | `idx_rp_rol` | Permisos por rol |
| FK_rol_permiso_permiso | rol_permiso | id_permiso | `idx_rp_permiso` | Roles por permiso |

### Corrección de normalización

Ya no existe:

`FK_cita_medico`

porque `cita.id_medico` fue eliminado.

El médico de una cita se obtiene mediante:

`cita.id_horario → horario.id_medico`.

---

### 3.3 Índices UNIQUE (Automáticos)

| Tabla | Columna(s) | Índice | Notas |
|-------|-----------|--------|-------|
| paciente | documento_identidad | `paciente_documento_identidad_key` | Identificador único |
| usuario | username | `usuario_username_key` | Login |
| usuario | email | `usuario_email_key` | Recuperación contraseña |
| medico | numero_colegiado | `medico_numero_colegiado_key` | Registro profesional |
| especialidad | nombre | `especialidad_nombre_key` | Catálogo |

Ya no se utiliza:

`UNIQUE (id_medico, id_horario)`.

Tampoco debe utilizarse:

`UNIQUE (id_horario)`

de forma incondicional.

La regla de doble reserva se implementará mediante un índice parcial para reservas activas.

---

## 4. Índices para Patrones de Acceso Críticos (RF/RNF)

### 4.1 Consultas de Disponibilidad y Reserva

```sql
-- 1. Buscar horarios disponibles de un médico

CREATE INDEX idx_horario_disponibilidad
ON horario (
    id_medico,
    estado,
    fecha_especifica,
    dia_semana,
    hora_inicio
)
WHERE estado = 'disponible';
```

```sql
-- 2. Especialidades y médicos activos

CREATE INDEX idx_medico_activo
ON medico (activo)
WHERE activo = true;

CREATE INDEX idx_especialidad_activa
ON especialidad (activo)
WHERE activo = true;
```

No se crea:

```text
idx_me_habilitada_virtual
```

porque `habilitada_modalidad_virtual` fue eliminada y D-08 continúa pendiente.

```sql
-- 3. Verificación de disponibilidad atómica

-- SELECT *
-- FROM horario
-- WHERE id_horario = ?
--   AND estado = 'disponible'
-- FOR UPDATE;

-- horario_pkey cubre id_horario.
```

---

### 4.2 Consultas de Historial de Paciente

```sql
-- Historial de citas del paciente

CREATE INDEX idx_cita_paciente_fecha
ON cita (
    id_paciente,
    fecha_hora_programada DESC
);
```

#### Próximas citas del paciente

La versión anterior utilizaba `CURRENT_DATE` dentro del predicado de un índice parcial.

Ese diseño se elimina.

Se utiliza un predicado estable basado en estados:

```sql
CREATE INDEX idx_cita_paciente_activas
ON cita (
    id_paciente,
    fecha_hora_programada
)
WHERE estado IN (
    'Programada',
    'Confirmada',
    'En_atencion'
);
```

La condición temporal se aplica en la consulta:

```sql
SELECT *
FROM cita
WHERE id_paciente = ?
  AND fecha_hora_programada > NOW()
  AND estado IN (
      'Programada',
      'Confirmada',
      'En_atencion'
  );
```

```sql
-- Citas activas por estado

CREATE INDEX idx_cita_paciente_estado
ON cita (
    id_paciente,
    estado
)
WHERE estado IN (
    'Programada',
    'Confirmada',
    'En_atencion'
);
```

---

### 4.3 Consultas de Agenda Médica

Después de eliminar `cita.id_medico`, la agenda médica se obtiene mediante:

`medico → horario → cita`.

Consulta típica:

```sql
SELECT c.*
FROM cita c
JOIN horario h
  ON h.id_horario = c.id_horario
WHERE h.id_medico = ?
ORDER BY c.fecha_hora_programada;
```

Índices:

```sql
CREATE INDEX idx_horario_medico
ON horario (
    id_medico
);
```

```sql
CREATE INDEX idx_cita_horario_fecha
ON cita (
    id_horario,
    fecha_hora_programada
);
```

#### Citas en atención

```sql
CREATE INDEX idx_cita_horario_en_atencion
ON cita (
    id_horario,
    fecha_hora_programada
)
WHERE estado = 'En_atencion';
```

#### Horarios por médico y estado

```sql
CREATE INDEX idx_horario_medico_estado
ON horario (
    id_medico,
    estado
);
```

No existen índices:

```text
idx_cita_medico_fecha
idx_cita_medico_en_atencion
```

porque `cita.id_medico` ya no existe.

---

### 4.4 Consultas Administrativas y Reportes

```sql
-- Citas por especialidad y fecha

CREATE INDEX idx_cita_especialidad_fecha
ON cita (
    id_especialidad,
    fecha_hora_programada
);
```

```sql
-- Citas virtuales

CREATE INDEX idx_cita_modalidad_fecha
ON cita (
    modalidad,
    fecha_hora_programada
)
WHERE modalidad = 'virtual';
```

```sql
-- Estadísticas por estado

CREATE INDEX idx_cita_fecha_creacion_estado
ON cita (
    fecha_creacion,
    estado
);
```

Los índices de:

`usuario_rol`

continúan cubriendo las consultas administrativas por rol.

---

### 4.5 Auditoría e Histórico

```sql
-- Auditoría completa de una cita

CREATE INDEX idx_auditoria_cita
ON registro_auditoria (
    id_cita,
    fecha_hora DESC
);
```

```sql
-- Acciones de un usuario

CREATE INDEX idx_auditoria_usuario
ON registro_auditoria (
    id_usuario_responsable,
    fecha_hora DESC
);
```

```sql
-- Histórico por paciente

CREATE INDEX idx_cita_hist_paciente_fecha
ON cita_historico (
    id_paciente,
    fecha_accion DESC
);
```

Como `cita_historico.id_medico` fue eliminado, se reemplaza el índice anterior por:

```sql
CREATE INDEX idx_cita_hist_horario_fecha
ON cita_historico (
    id_horario,
    fecha_accion DESC
);
```

La búsqueda del médico histórico se realiza mediante la relación con el horario.

---

### 4.6 Búsquedas de Texto

```sql
CREATE EXTENSION IF NOT EXISTS pg_trgm;
```

```sql
CREATE INDEX idx_paciente_nombre_trgm
ON paciente
USING GIN (
    nombre gin_trgm_ops,
    apellidos gin_trgm_ops
);
```

```sql
CREATE INDEX idx_medico_nombre_trgm
ON medico
USING GIN (
    nombre_completo gin_trgm_ops
);
```

---

## 5. Índices Compuestos y de Cobertura

### 5.1 Índices INCLUDE

Consulta frecuente del paciente:

```sql
CREATE INDEX idx_cita_paciente_cover
ON cita (
    id_paciente,
    fecha_hora_programada DESC
)
INCLUDE (
    estado,
    modalidad,
    id_horario,
    id_especialidad
);
```

Para consultas que parten del horario:

```sql
CREATE INDEX idx_cita_horario_cover
ON cita (
    id_horario,
    fecha_hora_programada
)
INCLUDE (
    estado,
    id_paciente,
    id_especialidad,
    modalidad
);
```

Se eliminan:

```text
idx_cita_medico_cover
```

y cualquier `INCLUDE(id_medico)` en `cita`.

---

### 5.2 Índices Expresionales

Las búsquedas temporales deben preferir rangos sobre:

`fecha_hora_programada`.

Ejemplo:

```sql
SELECT *
FROM cita
WHERE fecha_hora_programada >= :inicio_dia
  AND fecha_hora_programada < :inicio_dia_siguiente;
```

El índice:

```sql
CREATE INDEX idx_cita_fecha_programada
ON cita (
    fecha_hora_programada
);
```

puede utilizarse para esas búsquedas.

Para auditoría se conserva la estrategia temporal ya definida según el modelo físico correspondiente.

---

## 6. Regla de Doble Reserva

La regla RN-04/RNF-11 se protege mediante un índice UNIQUE parcial.

```sql
CREATE UNIQUE INDEX uk_cita_horario_ocurrencia_activa
ON cita (
    id_horario,
    fecha_hora_programada
)
WHERE estado IN (
    'Programada',
    'Confirmada',
    'En_atencion'
);
```

Este índice impide que existan simultáneamente dos reservas activas para la misma ocurrencia del horario.

No impide conservar:

- citas canceladas;
- citas finalizadas;
- citas no asistidas.

Por tanto, reemplaza el anterior:

`UNIQUE(id_medico, id_horario)`.

---

## 7. EXCLUDE Constraint para Superposición Temporal del Paciente

La regla RN-05 evita que un paciente tenga citas activas superpuestas.

El diseño definitivo permanece complementado por la lógica documentada en Integridad.

La duración de la cita se deriva del horario correspondiente.

La implementación exacta deberá revisarse junto con las transacciones del Paso 12.

---

## 8. Resumen de Índices Recomendados

| # | Tabla | Índice | Columnas | Tipo | Parcial |
|---|-------|--------|----------|------|---------|
| 1 | paciente | PK | id_paciente | B-tree | No |
| 2 | paciente | UNIQUE | documento_identidad | B-tree | No |
| 3 | paciente | GIN trigram | nombre, apellidos | GIN | No |
| 4 | medico | PK | id_medico | B-tree | No |
| 5 | medico | UNIQUE | numero_colegiado | B-tree | No |
| 6 | medico | GIN trigram | nombre_completo | GIN | No |
| 7 | medico | Parcial | activo | B-tree | activo=true |
| 8 | usuario | PK | id_usuario | B-tree | No |
| 9 | usuario | UNIQUE | username | B-tree | No |
| 10 | usuario | UNIQUE | email | B-tree | No |
| 11 | usuario | FK | id_paciente | B-tree | No |
| 12 | usuario | FK | id_medico | B-tree | No |
| 13 | especialidad | PK | id_especialidad | B-tree | No |
| 14 | especialidad | UNIQUE | nombre | B-tree | No |
| 15 | especialidad | Parcial | activo | B-tree | activo=true |
| 16 | medico_especialidad | PK | (id_medico, id_especialidad) | B-tree | No |
| 17 | medico_especialidad | FK | id_medico | B-tree | No |
| 18 | medico_especialidad | FK | id_especialidad | B-tree | No |
| 19 | horario | PK | id_horario | B-tree | No |
| 20 | horario | FK | id_medico | B-tree | No |
| 21 | horario | Compuesto | (id_medico, estado) | B-tree | No |
| 22 | horario | Disponibilidad | (id_medico, estado, fecha_especifica, dia_semana, hora_inicio) | B-tree | estado='disponible' |
| 23 | cita | PK | id_cita | B-tree | No |
| 24 | cita | FK | id_paciente | B-tree | No |
| 25 | cita | FK | id_horario | B-tree | No |
| 26 | cita | FK | id_especialidad | B-tree | No |
| 27 | cita | FK | id_usuario_registrador | B-tree | No |
| 28 | cita | Compuesto | (id_paciente, fecha_hora_programada DESC) | B-tree | No |
| 29 | cita | Parcial | (id_paciente, fecha_hora_programada) | B-tree | estado activo |
| 30 | cita | Compuesto | (id_paciente, estado) | B-tree | estado activo |
| 31 | cita | Compuesto | (id_horario, fecha_hora_programada) | B-tree | No |
| 32 | cita | Parcial | (id_horario, fecha_hora_programada) | B-tree | estado='En_atencion' |
| 33 | cita | Compuesto | (id_especialidad, fecha_hora_programada) | B-tree | No |
| 34 | cita | Compuesto | (modalidad, fecha_hora_programada) | B-tree | modalidad='virtual' |
| 35 | cita | Compuesto | (fecha_creacion, estado) | B-tree | No |
| 36 | cita | Cover | (id_paciente, fecha_hora_programada DESC) INCLUDE (...) | B-tree | No |
| 37 | cita | Cover | (id_horario, fecha_hora_programada) INCLUDE (...) | B-tree | No |
| 38 | cita | UNIQUE parcial | (id_horario, fecha_hora_programada) | B-tree | estado activo |
| 39 | atencion_virtual | PK/FK | id_cita | B-tree | No |
| 40 | registro_auditoria | PK | id_registro | B-tree | Según modelo físico |
| 41 | registro_auditoria | Compuesto | (id_cita, fecha_hora DESC) | B-tree | No |
| 42 | registro_auditoria | Compuesto | (id_usuario_responsable, fecha_hora DESC) | B-tree | No |
| 43 | cita_historico | Histórico | (id_cita, fecha_accion DESC) | B-tree | Según partición |
| 44 | cita_historico | Histórico | (id_paciente, fecha_accion DESC) | B-tree | Según partición |
| 45 | cita_historico | Histórico | (id_horario, fecha_accion DESC) | B-tree | Según partición |
| 46 | usuario_rol | PK | (id_usuario, id_rol) | B-tree | No |
| 47 | usuario_rol | FK | id_rol | B-tree | No |
| 48 | rol_permiso | PK | (id_rol, id_permiso) | B-tree | No |
| 49 | rol_permiso | FK | id_permiso | B-tree | No |
| 50 | parametros_configuracion | PK | id_parametro | B-tree | No |
| 51 | parametros_configuracion | UNIQUE | clave | B-tree | No |

### Índices eliminados de la versión anterior

No deben existir:

```text
idx_cita_medico
cita_id_medico_id_horario_key
idx_me_habilitada_virtual
idx_cita_medico_fecha
idx_cita_medico_en_atencion
idx_cita_medico_cover
idx_cita_hist_medico_fecha
```

---

## 9. Mantenimiento de Índices

### 9.1 Configuración Autovacuum

```sql
ALTER TABLE cita SET (
    autovacuum_vacuum_scale_factor = 0.05,
    autovacuum_analyze_scale_factor = 0.02,
    autovacuum_vacuum_threshold = 500,
    autovacuum_analyze_threshold = 200,
    autovacuum_vacuum_cost_delay = 10,
    autovacuum_vacuum_cost_limit = 1000
);
```

La configuración de auditoría e histórico se mantiene según la estrategia definida en los pasos anteriores.

---

### 9.2 REINDEX CONCURRENTLY

```sql
REINDEX INDEX CONCURRENTLY idx_cita_paciente_fecha;

REINDEX INDEX CONCURRENTLY idx_cita_horario_fecha;

REINDEX INDEX CONCURRENTLY idx_horario_disponibilidad;
```

Se elimina:

```text
REINDEX INDEX CONCURRENTLY idx_cita_medico_fecha;
```

porque ese índice ya no existe.

Para revisar uso:

```sql
SELECT
    schemaname,
    tablename,
    indexname,
    idx_scan,
    idx_tup_read,
    idx_tup_fetch,
    pg_size_pretty(
        pg_relation_size(indexrelid)
    ) AS size
FROM pg_stat_user_indexes
WHERE schemaname = 'public'
ORDER BY idx_scan DESC;
```

---

### 9.3 Monitoreo de Índices No Usados

```sql
SELECT
    schemaname,
    tablename,
    indexname,
    pg_size_pretty(
        pg_relation_size(indexrelid)
    ) AS size,
    idx_scan
FROM pg_stat_user_indexes
WHERE idx_scan = 0
  AND schemaname = 'public'
  AND indexname NOT LIKE '%_pkey'
ORDER BY pg_relation_size(indexrelid) DESC;
```

No debe eliminarse automáticamente un índice únicamente porque temporalmente tenga:

`idx_scan = 0`.

Debe analizarse:

- período observado;
- función del índice;
- FK;
- UNIQUE;
- concurrencia;
- frecuencia real del flujo asociado.

---

## 10. Estimación de Carga y Sizing

### 10.1 Supuestos de Carga

| Métrica | Valor | Fuente |
|---------|-------|--------|
| Citas/día | 500 | Estimación original del Paso 11 |
| Citas/mes | 15,000 | 500 × 30 |
| Citas/año | 180,000 | Estimación |
| Pacientes activos | 50,000 | Estimación |
| Médicos activos | 200 | Estimación |
| Usuarios concurrentes (pico) | 100 | RNF asociado |
| Transacciones/seg (pico) | 50 | Estimación técnica |

Estos valores continúan siendo supuestos de dimensionamiento y deben validarse mediante pruebas de carga.

---

### 10.2 Sizing de Memoria

Configuración de referencia del documento original:

```conf
shared_buffers = 8GB
effective_cache_size = 24GB
work_mem = 64MB
maintenance_work_mem = 2GB
random_page_cost = 1.1
effective_io_concurrency = 200
```

Estos valores deben ajustarse a la infraestructura real.

---

### 10.3 Pool de Conexiones

```ini
max_client_conn = 1000
default_pool_size = 25
reserve_pool_size = 5
pool_mode = transaction
```

Los valores definitivos requieren pruebas de carga y capacidad.

---

## 11. Correcciones derivadas del Paso 14

Se realizaron exclusivamente las correcciones necesarias para propagar la normalización:

1. Se eliminó toda dependencia de índices sobre `cita.id_medico`.

2. Se eliminó:

   `UNIQUE(id_medico, id_horario)`.

3. El acceso a citas de un médico utiliza:

   `horario.id_medico`

   junto con:

   `cita.id_horario`.

4. Se eliminó el índice de:

   `medico_especialidad.habilitada_modalidad_virtual`

   porque D-08 continúa pendiente.

5. Se eliminó el índice parcial cuyo predicado dependía de:

   `CURRENT_DATE`.

6. Las próximas citas del paciente utilizan un índice parcial basado en estados activos y la condición temporal se mantiene en la consulta.

7. Se eliminaron índices de `cita_historico.id_medico`.

8. El histórico utiliza:

   `cita_historico.id_horario`.

9. Se incorporó un índice UNIQUE parcial para evitar doble reserva activa por ocurrencia:

   `(id_horario, fecha_hora_programada)`.

10. No se modificaron las demás decisiones de rendimiento que no forman parte de los hallazgos del Paso 14.

---

## 12. Estado del Paso 11 después de la corrección

**Archivo:**

`proyecto/base_datos/10_indices_rendimiento/indices_rendimiento.md`

**Agente original:**

`database-engineer`

**Skill original:**

`postgresql-table-design`

**Corrección:**

Manual, como respuesta a los hallazgos de la Revisión DBA del Paso 14.

**Estado actual:**

Corregido y pendiente de nueva Revisión DBA.

---

## 13. Próximo Paso

La corrección de este archivo no autoriza automáticamente el Paso 15.

Todavía debe verificarse la consistencia de:

`proyecto/base_datos/11_transacciones_concurrencia/transacciones_concurrencia.md`

y:

`proyecto/base_datos/12_migraciones/migraciones.md`.

Después debe volver a ejecutarse:

**Paso 14 — Revisión DBA**

y obtener:

```text
STATUS: APPROVED
```

antes de continuar al Paso 15.

DETENERSE y esperar aprobación humana.