# Informe de Índices y Rendimiento — Paso 11

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español
**Generado:** Paso 11 — Índices y rendimiento
**Agente utilizado:** `database-engineer` (skill `postgresql-table-design`)
**Workflow:** `02_database_workflow`
**DBMS:** PostgreSQL 18.6
**Fuentes principales:** `modelo_fisico.md`, `06_integridad/integridad.md`, `08_auditoria_historico/auditoria_historico.md`
**Estado:** Pendiente de validación humana

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
| **Columnas de alta selectividad primero** | En índices compuestos, ordenar por cardinalidad |
| **Índices parciales** | `WHERE` condition para reducir tamaño (ej. `activo = true`) |
| **EXCLUDE USING GIST** | Para restricciones de superposición temporal |
| **Mantenimiento** | `autovacuum` agresivo en tablas de alta rotación |

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
| registro_auditoria | `registro_auditoria_pkey` (id_registro, fecha_hora) | B-tree compuesto particionado | ~16 bytes/row |
| parametros_configuracion | `parametros_configuracion_pkey` (id_parametro) | B-tree | ~8 bytes/row |
| rol | `rol_pkey` (id_rol) | B-tree | ~8 bytes/row |
| permiso | `permiso_pkey` (id_permiso) | B-tree | ~8 bytes/row |
| usuario_rol | `usuario_rol_pkey` (id_usuario, id_rol) | B-tree compuesto | ~16 bytes/row |
| rol_permiso | `rol_permiso_pkey` (id_rol, id_permiso) | B-tree compuesto | ~16 bytes/row |

### 3.2 Índices de Clave Foránea (FK) — **OBLIGATORIOS MANUALES**

> **Regla del skill `postgresql-table-design`**: *PostgreSQL no indexa FK automáticamente. Cada FK debe tener su índice correspondiente para evitar bloqueos en cascada y acelerar joins.*

| FK | Tabla | Columna(s) | Índice Recomendado | Justificación |
|----|-------|------------|-------------------|---------------|
| FK_usuario_paciente | usuario | id_paciente | `idx_usuario_paciente` | Join paciente-usuario, RLS |
| FK_usuario_medico | usuario | id_medico | `idx_usuario_medico` | Join médico-usuario, RLS |
| FK_medico_especialidad_medico | medico_especialidad | id_medico | `idx_me_medico` | Consultas por médico |
| FK_medico_especialidad_especialidad | medico_especialidad | id_especialidad | `idx_me_especialidad` | Consultas por especialidad |
| FK_horario_medico | horario | id_medico | `idx_horario_medico` | **Crítico**: reservas, disponibilidad |
| FK_cita_paciente | cita | id_paciente | `idx_cita_paciente` | **Crítico**: historial paciente (RN-14) |
| FK_cita_medico | cita | id_medico | `idx_cita_medico` | **Crítico**: agenda médico (RN-15) |
| FK_cita_horario | cita | id_horario | `idx_cita_horario` | Validación reserva, liberar horario |
| FK_cita_especialidad | cita | id_especialidad | `idx_cita_especialidad` | Filtro por especialidad (RF-06) |
| FK_cita_usuario_registrador | cita | id_usuario_registrador | `idx_cita_registrador` | Auditoría, trazabilidad |
| FK_atencion_virtual_cita | atencion_virtual | id_cita | `idx_av_cita` | Join 1:1 cita ↔ virtual |
| FK_auditoria_cita | registro_auditoria | id_cita | `idx_auditoria_cita` | **Crítico**: historial auditoría |
| FK_auditoria_usuario | registro_auditoria | id_usuario_responsable | `idx_auditoria_usuario` | Auditoría por responsable |
| FK_usuario_rol_usuario | usuario_rol | id_usuario | `idx_ur_usuario` | Roles por usuario |
| FK_usuario_rol_rol | usuario_rol | id_rol | `idx_ur_rol` | Usuarios por rol |
| FK_rol_permiso_rol | rol_permiso | id_rol | `idx_rp_rol` | Permisos por rol |
| FK_rol_permiso_permiso | rol_permiso | id_permiso | `idx_rp_permiso` | Roles por permiso |

### 3.3 Índices UNIQUE (Automáticos)

| Tabla | Columna(s) | Índice | Notas |
|-------|------------|--------|-------|
| paciente | documento_identidad | `paciente_documento_identidad_key` | Lookup por DNI |
| usuario | username | `usuario_username_key` | Login |
| usuario | email | `usuario_email_key` | Recuperación contraseña |
| medico | numero_colegiado | `medico_numero_colegiado_key` | Verificación profesional |
| especialidad | nombre | `especialidad_nombre_key` | Catálogo |
| cita | (id_medico, id_horario) | `cita_id_medico_id_horario_key` | **RN-04**: No doble reserva |

---

## 4. Índices para Patrones de Acceso Críticos (RF/RNF)

### 4.1 Consultas de Disponibilidad y Reserva (RF-05, RF-06, RF-09, RN-03, RN-06, D-21)

```sql
-- 1. Buscar horarios disponibles de un médico en fecha/rango
-- Patrón: SELECT * FROM horario WHERE id_medico = ? AND estado = 'disponible'
--          AND (dia_semana = ? OR fecha_especifica = ?)
--          AND hora_inicio >= ? AND hora_fin <= ?
CREATE INDEX idx_horario_disponibilidad ON horario (id_medico, estado, fecha_especifica, dia_semana, hora_inicio)
    WHERE estado = 'disponible';

-- 2. Buscar especialidades activas con médicos disponibles
-- Patrón: JOIN medico_especialidad + medico + especialidad WHERE activo = true
CREATE INDEX idx_medico_activo ON medico (activo) WHERE activo = true;
CREATE INDEX idx_especialidad_activa ON especialidad (activo) WHERE activo = true;
CREATE INDEX idx_me_habilitada_virtual ON medico_especialidad (habilitada_modalidad_virtual)
    WHERE habilitada_modalidad_virtual = true;

-- 3. Verificar disponibilidad atómica (SELECT FOR UPDATE)
-- Patrón: SELECT * FROM horario WHERE id_horario = ? AND estado = 'disponible' FOR UPDATE
-- Índice PK horario_pkey ya cubre id_horario
-- Índice parcial idx_horario_disponibilidad cubre estado
```

### 4.2 Consultas de Historial de Paciente (RN-14, RN-16, RF-22)

```sql
-- 1. Historial de citas de un paciente (orden cronológico)
-- Patrón: SELECT * FROM cita WHERE id_paciente = ? ORDER BY fecha_hora_inicio DESC
CREATE INDEX idx_cita_paciente_fecha ON cita (id_paciente, fecha_hora_inicio DESC);

-- 2. Próximas citas de un paciente (futuras)
-- Patrón: SELECT * FROM cita WHERE id_paciente = ? AND fecha_hora_inicio > NOW()
CREATE INDEX idx_cita_paciente_futuras ON cita (id_paciente, fecha_hora_inicio)
    WHERE fecha_hora_inicio > (CURRENT_DATE - INTERVAL '1 day'); -- Parcial dinámica via app

-- 3. Citas por estado de un paciente
-- Patrón: SELECT * FROM cita WHERE id_paciente = ? AND estado IN ('Programada','Confirmada')
CREATE INDEX idx_cita_paciente_estado ON cita (id_paciente, estado)
    WHERE estado IN ('Programada', 'Confirmada', 'En_atencion');
```

### 4.3 Consultas de Agenda Médica (RN-15)

```sql
-- 1. Agenda del día de un médico
-- Patrón: SELECT * FROM cita WHERE id_medico = ? AND fecha_hora_inicio::date = ? ORDER BY fecha_hora_inicio
CREATE INDEX idx_cita_medico_fecha ON cita (id_medico, fecha_hora_inicio);

-- 2. Citas en atención de un médico
-- Patrón: SELECT * FROM cita WHERE id_medico = ? AND estado = 'En_atencion'
CREATE INDEX idx_cita_medico_en_atencion ON cita (id_medico, fecha_hora_inicio)
    WHERE estado = 'En_atencion';

-- 3. Horarios ocupados de un médico en rango
-- Patrón: SELECT h.* FROM horario h JOIN cita c ON c.id_horario = h.id_horario
--          WHERE h.id_medico = ? AND c.estado IN ('Confirmada','En_atencion')
-- Índice compuesto en horario: (id_medico, estado)
CREATE INDEX idx_horario_medico_estado ON horario (id_medico, estado);
```

### 4.4 Consultas Administrativas y Reportes (RF-20, RN-17, RN-28)

```sql
-- 1. Citas por especialidad en rango de fechas
-- Patrón: SELECT * FROM cita WHERE id_especialidad = ? AND fecha_hora_inicio BETWEEN ? AND ?
CREATE INDEX idx_cita_especialidad_fecha ON cita (id_especialidad, fecha_hora_inicio);

-- 2. Citas por modalidad (virtual/presencial)
-- Patrón: SELECT * FROM cita WHERE modalidad = 'virtual' AND fecha_hora_inicio > NOW()
CREATE INDEX idx_cita_modalidad_fecha ON cita (modalidad, fecha_hora_inicio)
    WHERE modalidad = 'virtual';

-- 3. Estadísticas de cancelaciones / no asistidas
-- Patrón: SELECT estado, count(*) FROM cita WHERE fecha_creacion BETWEEN ? AND ? GROUP BY estado
CREATE INDEX idx_cita_fecha_creacion_estado ON cita (fecha_creacion, estado);

-- 4. Usuarios por rol (administración)
-- Patrón: SELECT u.* FROM usuario u JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
--          WHERE ur.id_rol = ?
-- Índices FK ya creados: idx_ur_rol, idx_ur_usuario
```

### 4.5 Auditoría e Histórico (RN-23, RN-24, RN-25)

```sql
-- 1. Auditoría completa de una cita
-- Patrón: SELECT * FROM registro_auditoria WHERE id_cita = ? ORDER BY fecha_hora DESC
-- Índice local en cada partición: idx_<part>_cita (id_cita, fecha_hora DESC)

-- 2. Acciones de un usuario en rango
-- Patrón: SELECT * FROM registro_auditoria WHERE id_usuario_responsable = ? AND fecha_hora BETWEEN ? AND ?
-- Índice local en cada partición: idx_<part>_usuario (id_usuario_responsable, fecha_hora DESC)

-- 3. Citas_historico por paciente
CREATE INDEX idx_cita_hist_paciente_fecha ON cita_historico (id_paciente, fecha_accion DESC);

-- 4. Citas_historico por médico
CREATE INDEX idx_cita_hist_medico_fecha ON cita_historico (id_medico, fecha_accion DESC);
```

### 4.6 Búsquedas de Texto (Pacientes, Médicos)

```sql
-- Búsqueda por nombre/apellidos paciente (trigram)
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE INDEX idx_paciente_nombre_trgm ON paciente USING GIN (nombre gin_trgm_ops, apellidos gin_trgm_ops);

-- Búsqueda por nombre médico
CREATE INDEX idx_medico_nombre_trgm ON medico USING GIN (nombre_completo gin_trgm_ops);
```

---

## 5. Índices Compuestos y de Cobertura (Covering Indexes)

### 5.1 Índices INCLUDE (PostgreSQL 11+) — Index-Only Scans

```sql
-- Consulta frecuente: SELECT id_cita, estado, fecha_hora_inicio FROM cita WHERE id_paciente = ?
-- Index-only scan si todas las columnas están en el índice
CREATE INDEX idx_cita_paciente_cover ON cita (id_paciente, fecha_hora_inicio DESC)
    INCLUDE (estado, modalidad, id_medico, id_especialidad);

-- Agenda médico: SELECT id_cita, fecha_hora_inicio, estado, id_paciente FROM cita WHERE id_medico = ?
CREATE INDEX idx_cita_medico_cover ON cita (id_medico, fecha_hora_inicio)
    INCLUDE (estado, id_paciente, id_especialidad, modalidad);
```

### 5.2 Índices Expresionales (Function-Based)

```sql
-- Búsqueda por fecha sola (sin hora) en cita
CREATE INDEX idx_cita_fecha_inicio_date ON cita ((fecha_hora_inicio::date));

-- Búsqueda por mes/año en auditoría
CREATE INDEX idx_auditoria_mes ON registro_auditoria ((date_trunc('month', fecha_hora)));
```

---

## 6. EXCLUDE Constraint para Superposición Temporal (RN-05)

```sql
-- Alternativa al trigger fn_verificar_no_solapamiento_paciente
-- Usa GiST con operador de rango &&
ALTER TABLE cita ADD CONSTRAINT cita_no_superposicion_paciente
EXCLUDE USING GIST (
    id_paciente WITH =,
    tstzrange(fecha_hora_inicio, COALESCE(fecha_hora_fin, fecha_hora_inicio + INTERVAL '1 hour'), '[)') WITH &&
);

-- Requiere extensión btree_gist
CREATE EXTENSION IF NOT EXISTS btree_gist;
```

> **Nota**: Este `EXCLUDE` previene superposiciones a nivel de BD, pero el trigger permite mensajes de error más amigables. **Usar ambos** (defensa en profundidad).

---

## 7. Resumen Completo de Índices Recomendados

| # | Tabla | Índice | Columnas | Tipo | Parcial | Tamaño est. (1M rows) |
|---|-------|--------|----------|------|---------|----------------------|
| 1 | paciente | PK | id_paciente | B-tree | No | 35 MB |
| 2 | paciente | UNIQUE | documento_identidad | B-tree | No | 35 MB |
| 3 | paciente | GIN trigram | nombre, apellidos | GIN | No | 50 MB |
| 4 | medico | PK | id_medico | B-tree | No | 8 MB |
| 5 | medico | UNIQUE | numero_colegiado | B-tree | No | 8 MB |
| 6 | medico | GIN trigram | nombre_completo | GIN | No | 12 MB |
| 7 | medico | Parcial | activo | B-tree | activo=true | 2 MB |
| 8 | usuario | PK | id_usuario | B-tree | No | 15 MB |
| 9 | usuario | UNIQUE | username | B-tree | No | 15 MB |
| 10 | usuario | UNIQUE | email | B-tree | No | 15 MB |
| 11 | usuario | FK | id_paciente | B-tree | No | 15 MB |
| 12 | usuario | FK | id_medico | B-tree | No | 15 MB |
| 13 | especialidad | PK | id_especialidad | B-tree | No | 1 MB |
| 14 | especialidad | UNIQUE | nombre | B-tree | No | 1 MB |
| 15 | especialidad | Parcial | activo | B-tree | activo=true | <1 MB |
| 16 | medico_especialidad | PK | (id_medico, id_especialidad) | B-tree | No | 10 MB |
| 17 | medico_especialidad | FK | id_medico | B-tree | No | 10 MB |
| 18 | medico_especialidad | FK | id_especialidad | B-tree | No | 10 MB |
| 19 | medico_especialidad | Parcial | habilitada_modalidad_virtual | B-tree | true | 2 MB |
| 20 | horario | PK | id_horario | B-tree | No | 20 MB |
| 21 | horario | FK | id_medico | B-tree | No | 20 MB |
| 22 | horario | Compuesto | (id_medico, estado) | B-tree | No | 25 MB |
| 23 | horario | Disponibilidad | (id_medico, estado, fecha_especifica, dia_semana, hora_inicio) | B-tree | estado='disponible' | 15 MB |
| 24 | cita | PK | id_cita | B-tree | No | 50 MB |
| 25 | cita | UNIQUE | (id_medico, id_horario) | B-tree | No | 50 MB |
| 26 | cita | FK | id_paciente | B-tree | No | 50 MB |
| 27 | cita | FK | id_medico | B-tree | No | 50 MB |
| 28 | cita | FK | id_horario | B-tree | No | 50 MB |
| 29 | cita | FK | id_especialidad | B-tree | No | 50 MB |
| 30 | cita | FK | id_usuario_registrador | B-tree | No | 50 MB |
| 31 | cita | Compuesto | (id_paciente, fecha_hora_inicio DESC) | B-tree | No | 60 MB |
| 32 | cita | Compuesto | (id_paciente, estado) | B-tree | estado IN (...) | 20 MB |
| 33 | cita | Compuesto | (id_medico, fecha_hora_inicio) | B-tree | No | 60 MB |
| 34 | cita | Compuesto | (id_medico, estado) | B-tree | estado='En_atencion' | 10 MB |
| 35 | cita | Compuesto | (id_especialidad, fecha_hora_inicio) | B-tree | No | 50 MB |
| 36 | cita | Compuesto | (modalidad, fecha_hora_inicio) | B-tree | modalidad='virtual' | 15 MB |
| 37 | cita | Compuesto | (fecha_creacion, estado) | B-tree | No | 50 MB |
| 38 | cita | Cover | (id_paciente, fecha_hora_inicio DESC) INCLUDE (...) | B-tree | No | 80 MB |
| 39 | cita | Cover | (id_medico, fecha_hora_inicio) INCLUDE (...) | B-tree | No | 80 MB |
| 40 | cita | EXCLUDE | GIST (paciente + rango temporal) | GiST | No | 40 MB |
| 41 | atencion_virtual | PK=FK | id_cita | B-tree | No | 10 MB |
| 42 | registro_auditoria | PK | (id_registro, fecha_hora) | B-tree | Particionado | Partición/mes ~200 MB |
| 43 | registro_auditoria | Local/part | (id_cita, fecha_hora DESC) | B-tree | Particionado | Partición/mes ~100 MB |
| 44 | registro_auditoria | Local/part | (id_usuario_responsable, fecha_hora DESC) | B-tree | Particionado | Partición/mes ~100 MB |
| 45 | cita_historico | PK | id_historico | B-tree | Particionado | Partición/mes ~300 MB |
| 46 | cita_historico | Local/part | (id_cita, fecha_accion DESC) | B-tree | Particionado | Partición/mes ~150 MB |
| 47 | cita_historico | Local/part | (id_paciente, fecha_accion DESC) | B-tree | Particionado | Partición/mes ~150 MB |
| 48 | cita_historico | Local/part | (id_medico, fecha_accion DESC) | B-tree | Particionado | Partición/mes ~150 MB |
| 49 | usuario_rol | PK | (id_usuario, id_rol) | B-tree | No | 5 MB |
| 50 | usuario_rol | FK | id_usuario | B-tree | No | 5 MB |
| 51 | usuario_rol | FK | id_rol | B-tree | No | 5 MB |
| 52 | rol_permiso | PK | (id_rol, id_permiso) | B-tree | No | 1 MB |
| 53 | rol_permiso | FK | id_rol | B-tree | No | 1 MB |
| 54 | rol_permiso | FK | id_permiso | B-tree | No | 1 MB |
| 55 | parametros_configuracion | PK | id_parametro | B-tree | No | <1 MB |
| 56 | parametros_configuracion | UNIQUE | clave | B-tree | No | <1 MB |

**Total estimado (excluyendo particiones históricas): ~1.2 GB para 1M citas**
**Particiones mensuales auditoría/histórico: +200-500 MB/mes**

---

## 8. Mantenimiento de Índices

### 8.1 Configuración Autovacuum (Tablas de Alta Rotación)

```sql
-- Cita: alta rotación (INSERT/UPDATE frecuente)
ALTER TABLE cita SET (
    autovacuum_vacuum_scale_factor = 0.05,      -- 5% de tuplas muertas
    autovacuum_analyze_scale_factor = 0.02,     -- 2% para estadísticas
    autovacuum_vacuum_threshold = 500,          -- mínimo 500 tuplas
    autovacuum_analyze_threshold = 200,
    autovacuum_vacuum_cost_delay = 10,          -- menos agresivo en I/O
    autovacuum_vacuum_cost_limit = 1000
);

-- Registro_auditoria: solo INSERT, particionado
ALTER TABLE registro_auditoria SET (
    autovacuum_enabled = false  -- Particiones se vacían individualmente
);

-- Particiones de auditoría: vacuum automático al crearse
-- (pg_partman o job propio maneja esto)
```

### 8.2 REINDEX CONCURRENTLY (Sin Bloqueo)

```sql
-- Reindexar índices fragmentados sin bloquear escrituras
-- Ejecutar en ventana de mantenimiento (mensual)
REINDEX INDEX CONCURRENTLY idx_cita_paciente_fecha;
REINDEX INDEX CONCURRENTLY idx_cita_medico_fecha;
REINDEX INDEX CONCURRENTLY idx_horario_disponibilidad;

-- Verificar fragmentación
SELECT
    schemaname,
    tablename,
    indexname,
    idx_scan,
    idx_tup_read,
    idx_tup_fetch,
    pg_size_pretty(pg_relation_size(indexrelid)) AS size
FROM pg_stat_user_indexes
WHERE schemaname = 'public'
ORDER BY idx_scan DESC;
```

### 8.3 Monitoreo de Índices No Usados

```sql
-- Índices candidatos a eliminación (0 scans en 30 días)
SELECT
    schemaname,
    tablename,
    indexname,
    pg_size_pretty(pg_relation_size(indexrelid)) AS size,
    idx_scan
FROM pg_stat_user_indexes
WHERE idx_scan = 0
  AND schemaname = 'public'
  AND indexname NOT LIKE '%_pkey'  -- No tocar PKs
ORDER BY pg_relation_size(indexrelid) DESC;
```

---

## 9. Estimación de Carga y Sizing

### 9.1 Supuestos de Carga

| Métrica | Valor | Fuente |
|---------|-------|--------|
| Citas/día | 500 | Estimación hospital media |
| Citas/mes | 15,000 | 500 × 30 |
| Citas/año | 180,000 | |
| Pacientes activos | 50,000 | |
| Médicos activos | 200 | |
| Usuarios concurrentes (pico) | 100 | App + admisión |
| Transacciones/seg (pico) | 50 | Reserva atómica |

### 9.2 Sizing de Memoria (shared_buffers, work_mem)

```conf
# postgresql.conf - Para 32 GB RAM dedicado
shared_buffers = 8GB              # 25% RAM
effective_cache_size = 24GB       # 75% RAM
work_mem = 64MB                   # Por operación (sort/hash)
maintenance_work_mem = 2GB        # Para CREATE INDEX, VACUUM
random_page_cost = 1.1            # SSD NVMe
effective_io_concurrency = 200    # NVMe parallelism
```

### 9.3 Pool de Conexiones (PgBouncer)

```ini
# pgbouncer.ini
max_client_conn = 1000
default_pool_size = 25            # 25 conexiones por pool
reserve_pool_size = 5
pool_mode = transaction
```

---

## 10. Próximo Paso

**Paso 12 — Transacciones y concurrencia**
Usar: `databases` + `postgresql-table-design`
Salida: `proyecto/base_datos/11_transacciones_concurrencia/transacciones_concurrencia.md`

DETENERSE y esperar aprobación humana.