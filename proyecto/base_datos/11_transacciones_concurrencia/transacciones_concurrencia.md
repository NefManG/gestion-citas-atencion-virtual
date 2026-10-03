# Informe de Transacciones y Concurrencia — Paso 12

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español
**Generado:** Paso 12 — Transacciones y concurrencia
**Agente utilizado:** `database-engineer` (skill `databases` + `postgresql-table-design`)
**Workflow:** `02_database_workflow`
**DBMS:** PostgreSQL 18.6
**Fuentes principales:** `modelo_fisico.md`, `06_integridad/integridad.md`, `10_indices_rendimiento/indices_rendimiento.md`
**Estado:** Pendiente de validación humana

---

## 1. Objetivo del Paso 12

Documentar la **estrategia completa de transacciones y manejo de concurrencia**, cubriendo:

- Aislamiento de transacciones (niveles de lectura)
- Control de bloqueos y deadlocks
- Transacciones atómicas para reglas de negocio (RN-06, RN-10, RN-12, RN-13, RN-19)
- Manejo de conteo de intentos fallidos y bloqueo de cuentas
- Serialización de citas en conflicto (SELECT FOR UPDATE, advisory locks)
- Configuración del DBMS (`deadlock_timeout`, `max_locks_per_transaction`)

**Salida:** `proyecto/base_datos/11_transacciones_concurrencia/transacciones_concurrencia.md`

DETENERSE y esperar aprobación humana.

---

## 2. Modelo de Concurrencia en PostgreSQL

### 2.1 MVCC (Multi-Version Concurrency Control)

PostgreSQL usa MVCC: las lecturas **nunca bloquean escrituras** y viceversa. Cada transacción ve una "foto" consistente de la base de datos al iniciar.

| Acción | Lectura | Escritura |
|--------|---------|-----------|
| **Lectura (SELECT)** | Snap-shot MVCC, sin bloqueos | Sin impacto (ve versión previa) |
| **Escritura (INSERT/UPDATE/DELETE)** | Bloquea filas afectadas | Actualiza fila, crea nueva versión, marca vieja como muerta |
| **Vacuum** | Limpia tuplas muertas (autovacuum) | Espacio liberado progresivamente |

### 2.2 Niveles de Aislamiento Soportados

| Nivel | Dirty read | Non-repeatable read | Phantom read |
|-------|-----------|---------------------|--------------|
| **Read Uncommitted** | SÍ (alias de Read Committed) | SÍ | SÍ |
| **Read Committed** (default) | NO | SÍ | SÍ |
| **Repeatable Read** | NO | NO | SÍ (parcialmente) |
| **Serializable** | NO | NO | NO |

**Decisión para este sistema:**

- **Aplicación (lectura de datos de usuario): `READ COMMITTED`** (default PG)
- **Reservas y cambios de estado críticos: `SERIALIZABLE`**

> **Regla:** Nunca usar `READ UNCOMMITTED` (en PG es alias de Read Committed, semántica engañosa).

---

## 3. Transacciones Atómicas — Patrones Implementados

### 3.1 Reserva de Cita Atómica (RN-06, RN-07, RN-10, D-21)

**Regla RN-06:** "La creación, confirmación o reprogramación de una cita debe ser una transacción atómica: o todo se hace o nada ocurre."

```sql
-- NIVEL: SERIALIZABLE para garantizar atomicidad completa
-- TIEMPO: ventana máxima 30s (statement_timeout)

BEGIN;
    SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;

    -- 1. Verificar disponibilidad del médico en rango
    SELECT id_horario INTO v_horario
    FROM horario
    WHERE id_medico = p_id_medico
      AND (dia_semana = p_dia_semana OR fecha_especifica = p_fecha_especifica)
      AND estado = 'disponible'
    ORDER BY dia_semana, hora_inicio
    LIMIT 1
    FOR UPDATE;          -- Bloqueo row-level en la fila seleccionada

    -- 2. Verificar no solapamiento con citas del paciente
    PERFORM 1 FROM cita
    WHERE id_paciente = p_id_paciente
      AND id_cita != p_id_cita_nueva
      AND fecha_hora_inicio < (p_inicio + INTERVAL '1 hour')
      AND (COALESCE(fecha_hora_fin, fecha_hora_inicio + INTERVAL '1 hour')) > p_inicio;
    IF FOUND THEN
        RAISE EXCEPTION 'El paciente tiene una cita en ese horario';
    END IF;

    -- 3. Verificar no doble reserva (id_medico + id_horario único)
    PERFORM 1 FROM cita WHERE id_medico = p_id_medico AND id_horario = v_horario;
    IF FOUND THEN
        ROLLBACK;
        RAISE EXCEPTION 'No hay disponibilidad: horario ya ocupado';
    END IF;

    -- 4. Reservar (INSERT)
    INSERT INTO cita (id_paciente, id_medico, id_horario, id_especialidad,
                      id_usuario_registrador, estado, modalidad,
                      fecha_hora_inicio, fecha_hora_fin,
                      fecha_creacion, fecha_actualizacion)
    VALUES (p_id_paciente, p_id_medico, v_horario, p_id_especialidad,
            p_id_usuario_registrador, 'Programada', 'presencial',
            p_inicio, NULL,
            now(), now())
    RETURNING id_cita INTO v_id_cita;

    -- 5. Actualizar estado del horario (TRANSACCIONAL: no trigger, para control total)
    UPDATE horario SET estado = 'ocupado'
    WHERE id_horario = v_horario
      AND estado = 'disponible';   -- Condición: garantiza atomicidad final

    -- 6. Registrar en auditoría (transaccional)
    INSERT INTO registro_auditoria (id_cita, id_usuario_responsable, accion,
                                    campo_modificado, valor_anterior, valor_actual,
                                    fecha_hora)
    VALUES (v_id_cita, p_id_usuario_registrador, 'crear', NULL, NULL, NULL, now());

    -- 7. Si es virtual: crear atencion_virtual (o fallar la cita completa)
    IF p_modalidad = 'virtual' THEN
        INSERT INTO atencion_virtual (id_cita, enlace_acceso, estado_disponibilidad)
        VALUES (v_id_cita, p_enlace_acceso, 'disponible');
    END IF;

COMMIT;

-- RETRY EN CASO DE SERIALIZABLE FAILURE:
-- PG devuelve SERIALIZABLE_FAILURE (cod. 40001) → la app reintenta.
-- Patrones de reintento: backoff exponencial, 3-5 intentos.
```

> **Nota crítica:** La condicional `AND estado = 'disponible'` en el UPDATE final es **esencial**. Con `SERIALIZABLE`, dos transacciones pueden leer `estado='disponible'` en la misma fila; el segundo UPDATE no afecta ninguna fila (0 rows), lo que dispara `SerializationFailure` y fuerza el reintento.

### 3.2 Confirmación de Cita (RN-07, RN-08, RN-10)

```sql
BEGIN;
    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;

    -- 1. Verificar estado actual
    SELECT estado INTO v_estado
    FROM cita
    WHERE id_cita = p_id_cita
    FOR UPDATE;      -- Bloquea fila para evitar race conditions

    -- 2. Validar transición (Programada → Confirmada)
    IF v_estado != 'Programada' THEN
        RAISE EXCEPTION 'La cita no está en estado Programada; estado actual: %', v_estado;
    END IF;

    -- 3. Actualizar estado (trigger trg_cita_verificar_transiciones valida)
    UPDATE cita SET
        estado = 'Confirmada',
        fecha_actualizacion = now()
    WHERE id_cita = p_id_cita;

    -- 4. Ocupar horario (transaccional, no trigger)
    UPDATE horario SET estado = 'ocupado'
    WHERE id_horario = (SELECT id_horario FROM cita WHERE id_cita = p_id_cita);

    -- 5. Auditoría transaccional
    INSERT INTO registro_auditoria (id_cita, id_usuario_responsable, accion,
                                    campo_modificado, valor_anterior, valor_actual,
                                    fecha_hora)
    VALUES (p_id_cita, p_id_usuario_responsable, 'modificar', 'estado',
            'Programada', 'Confirmada', clock_timestamp());

    -- 6. Si era virtual: activar enlace
    IF EXISTS (SELECT 1 FROM atencion_virtual WHERE id_cita = p_id_cita) THEN
        UPDATE atencion_virtual SET
            estado_disponibilidad = 'disponible',
            fecha_inicio_sesion = now()
        WHERE id_cita = p_id_cita;
    END IF;

COMMIT;
```

### 3.3 Cancelación de Cita (RN-08, RN-13)

```sql
BEGIN;
    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;

    SELECT estado INTO v_estado
    FROM cita
    WHERE id_cita = p_id_cita
    FOR UPDATE;

    IF v_estado NOT IN ('Programada', 'Confirmada') THEN
        RAISE EXCEPTION 'No se puede cancelar una cita en estado %', v_estado;
    END IF;

    UPDATE cita SET
        estado = 'Cancelada',
        fecha_actualizacion = now()
    WHERE id_cita = p_id_cita;

    -- Liberar horario
    UPDATE horario SET estado = 'disponible'
    WHERE id_horario = (SELECT id_horario FROM cita WHERE id_cita = p_id_cita);

    -- Liberar slot virtual (si existe)
    UPDATE atencion_virtual SET
        estado_disponibilidad = 'no_disponible',
        fecha_fin_sesion = now(),
        detalles_incidente = 'Cita cancelada por el paciente'
    WHERE id_cita = p_id_cita;

    INSERT INTO registro_auditoria (id_cita, id_usuario_responsable, accion,
                                    campo_modificado, valor_anterior, valor_actual,
                                    fecha_hora)
    VALUES (p_id_cita, p_id_usuario_responsable, 'cancelar', 'estado',
            v_estado, 'Cancelada', clock_timestamp());

COMMIT;
```

### 3.4 Reprogramación Atómica (RN-12, D-21)

```sql
BEGIN;
    SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;

    -- 1. Obtener estado y datos actuales
    SELECT c.estado, c.id_paciente, c.id_medico, c.id_horario,
           c.fecha_hora_inicio, c.fecha_hora_fin
    INTO v_cita
    FROM cita c
    WHERE c.id_cita = p_id_cita
    FOR UPDATE;   -- Bloquea la fila de la cita

    IF v_cita.estado NOT IN ('Programada', 'Confirmada') THEN
        RAISE EXCEPTION 'Transición no permitida para reprogramar: %', v_cita.estado;
    END IF;

    -- 2. Nuevo horario: verificar disponibilidad (mismo patrón que reserva)
    SELECT h.id_horario INTO v_nuevo_horario
    FROM horario h
    WHERE h.id_medico = v_cita.id_medico
      AND ((h.dia_semana = p_dia_semana OR h.fecha_especifica = p_fecha_especifica)
           AND h.hora_inicio >= p_nueva_hora_inicio
           AND h.hora_fin <= p_nueva_hora_fin)
      AND h.estado = 'disponible'
    ORDER BY h.hora_inicio
    LIMIT 1
    FOR UPDATE;

    IF v_nuevo_horario IS NULL THEN
        RAISE EXCEPTION 'No hay horario disponible en el nuevo slot';
    END IF;

    -- 3. Verificar no solapamiento del paciente en nuevo horario
    PERFORM 1 FROM cita
    WHERE id_paciente = v_cita.id_paciente
      AND id_cita != p_id_cita
      AND ...   -- [verificar superposición]

    -- 4. Liberar horario antiguo
    UPDATE horario SET estado = 'disponible'
    WHERE id_horario = v_cita.id_horario;

    -- 5. Asignar nuevo horario
    UPDATE cita SET
        id_horario = v_nuevo_horario,
        fecha_hora_inicio = p_nueva_hora_inicio,
        fecha_hora_fin = p_nueva_hora_fin,
        estado = 'Programada',   -- Reinicia a Programada
        fecha_actualizacion = now()
    WHERE id_cita = p_id_cita;

    -- 6. Auditoría transaccional
    INSERT INTO registro_auditoria (id_cita, id_usuario_responsable, accion,
                                    campo_modificado, valor_anterior, valor_actual,
                                    fecha_hora)
    VALUES (p_id_cita, p_id_usuario_responsable, 'reprogramar', 'horario',
            'Slot antiguo', 'Slot nuevo', clock_timestamp());

COMMIT;
```

### 3.5 Finalización de Cita (RN-19, RN-20)

```sql
BEGIN;
    SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;

    SELECT estado, fecha_hora_inicio, id_paciente
    INTO v_cita
    FROM cita
    WHERE id_cita = p_id_cita
    FOR UPDATE;

    IF v_cita.estado != 'En_atencion' THEN
        RAISE EXCEPTION 'Solo se puede finalizar una cita en estado En_atencion';
    END IF;

    IF p_fecha_hora_fin < v_cita.fecha_hora_inicio THEN
        RAISE EXCEPTION 'Fecha hora fin no puede ser anterior a la inicio';
    END IF;

    UPDATE cita SET
        estado = 'Finalizada',
        fecha_hora_fin = p_fecha_hora_fin,
        fecha_actualizacion = now()
    WHERE id_cita = p_id_cita;

    -- Registrar observaciones clínicas en atencion_virtual
    IF EXISTS (SELECT 1 FROM atencion_virtual av WHERE av.id_cita = p_id_cita) THEN
        UPDATE atencion_virtual SET
            observaciones = p_observaciones,
            fecha_hora_fin = p_fecha_hora_fin,
            estado_disponibilidad = 'no_disponible'
        WHERE id_cita = p_id_cita;
    END IF;

    -- Liberar horario al finalizar (no se mantiene ocupado)
    UPDATE horario SET estado = 'disponible'
    WHERE id_horario = p_id_horario;

    INSERT INTO registro_auditoria (id_cita, id_usuario_responsable, accion,
                                    campo_modificado, valor_anterior, valor_actual,
                                    fecha_hora)
    VALUES (p_id_cita, p_id_usuario_responsable, 'finalizar', 'estado',
            'En_atencion', 'Finalizada', clock_timestamp());

    INSERT INTO atencion_virtual (id_cita, fecha_hora_fin, ...)
    ...

COMMIT;
```

---

## 4. Bloqueos (Locks) — Gestión y Prevención de Deadlocks

### 4.1 Tipos de Bloqueo en Postgres

| Clase de bloqueo | Uso | Equivalente SQL |
|-----------------|-----|-----------------|
| **AccessShareLock** | SELECT simple | (ninguno) |
| **RowShareLock** | UPDATE/DELETE con WHERE | SELECT FOR UPDATE |
| **RowExclusiveLock** | INSERT/UPDATE/DELETE | SELECT FOR UPDATE |
| **ShareRowExclusiveLock** | TRIGGER/RLS/REFRESH MATERIALIZED VIEW | — |
| **ShareLock** | SELECT FOR SHARE | SELECT FOR SHARE |
| **ExclusiveLock** | `LOCK TABLE` explícito | `LOCK TABLE ... IN EXCLUSIVE MODE` |
| **AccessExclusiveLock** | DDL, DROP, TRUNCATE | `ALTER TABLE`, `DROP` |

### 4.2 `SELECT FOR UPDATE` — Locking Semántica

```sql
-- Bloquea fila SELECTED (row-exclusive) hasta COMMIT
SELECT * FROM cita WHERE id_cita = ? FOR UPDATE;

-- Bloquea filas matcheadas y las que se inserten después (gap lock)
SELECT * FROM horario WHERE id_medico = ? FOR UPDATE;

-- SKIP LOCKED: ideal para colas/turnos
SELECT * FROM horario WHERE estado = 'disponible' FOR UPDATE SKIP LOCKED;

-- NOWAIT: falla inmediatamente si hay bloqueo (útil para retry rápido)
SELECT * FROM horario WHERE id_horario = ? FOR UPDATE NOWAIT;
```

> **Regla `databases`:** Usar `SKIP LOCKED` / `NOWAIT` para evitar waits prolongados.

### 4.3 Configuración para Deadlock

```conf
# postgresql.conf
deadlock_timeout = '1s'          -- Detectar deadlock tras 1s (default)
lock_timeout = '30s'             -- Abortar espera de lock > 30s
```

**Estrategia de prevención:**
1. **Acceder tablas en orden consistente** (siempre cita → horario → atencion_virtual).
2. **Mantener transacciones cortas** (≤ 5s).
3. **Usar `FOR UPDATE`** en lecturas que preceden escritura.
4. **Indexar FK** (Paso 11) para que los locks sean rápidos.
5. **Evitar subtransacciones** (NO `SAVEPOINT` que no se usen).

### 4.4 Advisory Locks (Locks de Aplicación)

```sql
-- Bloqueo global (transaccional): solo una cita se procesa a la vez
SELECT pg_advisory_xact_lock(hashtext('proceso_citas_globales'));

-- Bloqueo por clave (ej. por médico, para no procesar dos citas del mismo médico)
SELECT pg_advisory_xact_lock(p_medico_id);

-- Bloqueo semántico con nombre (128-bit)
SELECT pg_advisory_xact_lock(1234, 5678);  -- high << 32 | low

-- Para bloqueos compartidos (múltiples lectores, 1 escritor)
SELECT pg_try_advisory_lock(hashtext('proceso_citas'));  -- No bloqueante
```

> **Regla:** advisory locks son **transaccionales** (se liberan al COMMIT), no persisten. No para coordinación a través de conexiones.

---

## 5. Conteo de Intentos Fallidos y Bloqueo de Cuentas (RNF-04)

### 5.1 Transacción Atómica para Auth

```sql
-- 1. Verificar credenciales y estado de la cuenta
BEGIN;

SELECT id_usuario, username, password_hash, activo, intentos_fallidos,
       fecha_ultimo_fallo, fecha_desbloqueo
INTO v_usuario
FROM usuario
WHERE username = p_username
FOR UPDATE;   -- Bloquea la fila del usuario (no hay race condition)

IF NOT FOUND THEN
    -- Registrar intento fallido genérico (sin revelar si el usuario existe)
    INSERT INTO login_attempts_log (username, fecha_fallo, ip_addr)
    VALUES (p_username, now(), p_ip_addr);
    RAISE EXCEPTION 'Credenciales inválidas';
END IF;

-- 2. Verificar si está bloqueado
IF NOT v_usuario.activo OR (
        v_usuario.fecha_desbloqueo IS NOT NULL
        AND v_usuario.fecha_desbloqueo > now()
    ) THEN
    RAISE EXCEPTION 'Cuenta bloqueada temporalmente';
END IF;

-- 3. Verificar contraseña
IF pgcrypto.crypt(p_password_hash, v_usuario.password_hash) != v_usuario.password_hash THEN
    -- Incrementar intentos fallidos (transaccional)
    UPDATE usuario SET
        intentos_fallidos = intentos_fallidos + 1,
        fecha_ultimo_fallo = now()
    WHERE id_usuario = v_usuario.id_usuario;

    -- Verificar umbral de bloqueo
    IF v_usuario.intentos_fallidos >= 5 THEN
        UPDATE usuario SET
            activo = FALSE,
            fecha_desbloqueo = now() + INTERVAL '30 minutes',
            motivo_bloqueo = 'Intentos fallidos excesivos'
        WHERE id_usuario = v_usuario.id_usuario;

        UPDATE usuario SET intentos_fallidos = 0;

        RAISE EXCEPTION 'Cuenta bloqueada por intentos fallidos. Reintente en 30 minutos.';
    END IF;

    RAISE EXCEPTION 'Credenciales inválidas';
END IF;

-- 4. Login exitoso: reiniciar intentos
UPDATE usuario SET
    intentos_fallidos = 0,
    ultimo_login = now()
WHERE id_usuario = v_usuario.id_usuario;

-- 5. Actualizar sesión (asignar role de app, tenant)
UPDATE usuario SET fecha_ultimo_acceso = now() WHERE id_usuario = v_usuario.id_usuario;

COMMIT;

-- 6. Establecer session variables (para RLS)
SET LOCAL app.current_user_id = v_usuario.id_usuario::text;
SET LOCAL app.current_paciente_id = COALESCE(v_usuario.id_paciente::text, '0');
SET LOCAL app.current_medico_id = COALESCE(v_usuario.id_medico::text, '0');
SET LOCAL app.user_role = COALESCE((
    SELECT r.nombre FROM usuario_rol ur JOIN rol r ON r.id_rol = ur.id_rol
    WHERE ur.id_usuario = v_usuario.id_usuario
    ORDER BY fecha_asignacion DESC LIMIT 1
), 'paciente');
```

### 5.2 Job para desbloquear cuentas expiradas

```sql
-- pg_cron: cada hora, desbloquea cuentas cuyo bloqueo expiró
-- SELECT cron.schedule('desbloquear-cuentas', '0 * * * *',
--     'UPDATE usuario SET activo = TRUE, fecha_desbloqueo = NULL
--      WHERE activo = FALSE AND fecha_desbloqueo IS NOT NULL
--        AND fecha_desbloqueo < (now() - INTERVAL ''30 minutes'')');
```

---

## 6. Configuración del DBMS para Concurrencia

### 6.1 `postgresql.conf` — Parámetros Críticos

```conf
# Concurrencia y locks
max_connections = 200                    -- 100 app × 2
superuser_reserved_connections = 3
max_locks_per_transaction = 256          -- Ajustar si hay muchos locks por transacción
deadlock_timeout = '1s'
lock_timeout = '30s'
statement_timeout = '60s'
idle_in_transaction_session_timeout = '180s'

# MVCC y vacuum
vacuum_freeze_min_age = 50000000
vacuum_freeze_table_age = 150000000
autovacuum_vacuum_scale_factor = 0.05    -- Tablas grandes
autovacuum_analyze_scale_factor = 0.02

# Memoria por operación (con cota global)
work_mem = 64MB
maintenance_work_mem = 2GB
temp_buffers = 256MB

# WAL y checkpoint
checkpoint_timeout = '15min'
checkpoint_completion_target = 0.9
wal_buffers = 64MB
commit_delay = 0
commit_siblings = 5

# Replicación (si aplica)
max_wal_senders = 3
wal_sender_timeout = '60s'
```

### 6.2 Controles en PgBouncer

```ini
# pgbouncer.ini
max_client_conn = 1000
default_pool_size = 25
reserve_pool_size = 5
reserve_pool_timeout = 3
server_check_delay = 20
server_check_query = SELECT 1
server_lifetime = 3600           # Reusar conexiones (no cerrar transacciones largas)
server_idle_timeout = 600
```

> **Advertencia:** PgBouncer **no soporta** transacciones a través de múltiples conexiones. `autocommit=off` en el driver debe coincidir con el pool de PG. Usar **pool_mode = transaction** y cerrar transacciones al terminar.

### 6.3 Controles en el Driver de Aplicación

| Driver | Configuración recomendada |
|--------|--------------------------|
| **PostgreSQL JDBC** | `reconnect=true`, `socketTimeout=30000`, `defaultTransactionIsolation=READ_COMMITTED` |
| **Node.js pg** | `connectionTimeoutMillis: 5000`, `idleTimeoutMillis: 30000`, `keepAlive: true` |
| **.NET Npgsql** | `MaxPoolSize=100`, `MinPoolSize=5`, `ConnectionIdleTimeout=30` |
| **Python asyncpg** | `pool_size=25`, `max_conns=100`, `connect_timeout=10` |

---

## 7. Manejo de Errores de Concurrencia

### 7.1 Código de Error SERIALIZABLE_FAILURE (40001)

```python
# Ejemplo Python con psycopg2
import psycopg2
from psycopg2 import sql

def reservar_cita(id_medico, id_horario, ...):
    max_reintentos = 5
    for intento in range(max_reintentos):
        try:
            conn = pool.getconn()
            with conn:
                with conn.cursor() as cur:
                    cur.execute(reserva_sql, (id_medico, id_horario, ...))
            return cur.fetchone()[0]  # id_cita
        except psycopg2.errors.SerializationFailure:
            if intento == max_reintentos - 1:
                raise  # Último intento: propagar error a la app
            # Backoff exponencial: 50ms, 100ms, 200ms...
            time.sleep(0.05 * (2 ** intento))
```

### 7.2 Código de Error LOCK_ACQUISITION_FAILED / deadlock_detected

```python
# Detectar y manejar deadlock
except psycopg2.errors.DeadlockDetected:
    # Loguear y reintentar con backoff más largo
    logger.warning(f"Deadlock detectado para cita {id_cita}, reintentando")
    time.sleep(0.1)  # Backoff fijo
    continue
```

### 7.3 Monitoreo de Bloqueos en Tiempo Real

```sql
-- Consultas bloqueantes/bloqueadas
SELECT
    blocked_locks.pid     AS pid_bloqueada,
    blocked_locks.mode    AS mode_bloqueada,
    blocked_locks.locked  AS tabla_bloqueada,
    blocked_activity.query AS query_bloqueada,
    blocking_locks.pid    AS pid_bloqueante,
    blocking_locks.mode   AS mode_bloqueante,
    blocking_activity.query AS query_bloqueante,
    EXTRACT(EPOCH FROM (now() - blocked_activity.query_start)) AS segundos_bloqueada
FROM pg_catalog.pg_locks blocked_locks
JOIN pg_catalog.pg_stat_activity blocked_activity
    ON blocked_activity.pid = blocked_locks.pid
JOIN pg_catalog.pg_locks blocking_locks
    ON blocking_locks.locktype = blocked_locks.locktype
    AND blocking_locks.database IS NOT DISTINCT FROM blocked_locks.database
    AND blocking_locks.relation IS NOT DISTINCT FROM blocked_locks.relation
    AND blocking_locks.page IS NOT DISTINCT FROM blocked_locks.page
    AND blocking_locks.tuple IS NOT DISTINCT FROM blocked_locks.tuple
    AND blocking_locks.virtualxid IS NOT DISTINCT FROM blocked_locks.virtualxid
    AND blocking_locks.transactionid IS NOT DISTINCT FROM blocked_locks.transactionid
    AND blocking_locks.classid IS NOT DISTINCT FROM blocked_locks.classid
    AND blocking_locks.classid IS NOT DISTINCT FROM blocked_locks.classid
    AND blocking_locks.objid IS NOT DISTINCT FROM blocked_locks.objid
    AND blocking_locks.objsubid IS NOT DISTINCT FROM blocked_locks.objsubid
    AND blocking_locks.pid != blocked_locks.pid
JOIN pg_catalog.pg_stat_activity blocking_activity
    ON blocking_activity.pid = blocking_locks.pid
WHERE NOT blocked_locks.GRANTED;
```

---

## 8. Estrategia de Reparto de Carga para Citas Simultáneas

```
┌──────────────────────────────────────────────────────────────┐
│                    Capa de Concurrencia                       │
└──────────────────────────────────────────────────────────────┘
                            │
            ┌───────────────┼───────────────┐
            ▼               ▼               ▼
    ┌───────────┐   ┌───────────┐   ┌───────────┐
    │  PgBouncer│   │  App 1    │   │  App 2    │
    │  (pool)   │   │  (25 conn)│   │  (25 conn)│
    └─────┬─────┘   └─────┬─────┘   └─────┬─────┘
          │               │               │
          └───────────────┼───────────────┘
                          ▼
            ┌─────────────────────────┐
            │   PostgreSQL (200 conn) │
            │  ┌───────────────────┐  │
            │  │  Citas (SELECT    │  │
            │  │  FOR UPDATE)      │  │
            │  └───────────────────┘  │
            │  ┌───────────────────┐  │
            │  │  Citas (INSERT)   │  │
            │  │  (serializable)   │  │
            │  └───────────────────┘  │
            └─────────────────────────┘
```

**Estimación de capacidad:**
- `max_connections = 200` (100 por app + reservadas)
- `default_pool_size = 25` × 8 apps = 200
- **Transacciones de reserva por segundo:** ~50-100 (con SERIALIZABLE)
- **Tiempo promedio de reserva:** 20-50ms (SELECT FOR UPDATE + INSERT)

---

## 9. Resúmenes de Transacciones Críticas

| Regla | Transacción | Nivel de aislamiento | Bloqueos | Retry en |
|-------|-------------|---------------------|----------|----------|
| RN-06 Reserva atómica | Reserva completa | SERIALIZABLE | SELECT FOR UPDATE + condicional UPDATE | SERIALIZABLE_FAILURE |
| RN-07 Confirmar cita | Confirmación | REPEATABLE READ | FOR UPDATE | — |
| RN-08 Cancelar cita | Cancelación | REPEATABLE READ | FOR UPDATE | — |
| RN-10 Ocupar horario | Parte de reserva/cancelación | REPEATABLE READ | condicional UPDATE | — |
| RN-12 Reprogramar atómica | Reprogramación | SERIALIZABLE | SELECT FOR UPDATE (2 filas) | SERIALIZABLE_FAILURE |
| RN-13 Liberar horario | Cancelación/reprogramación | REPEATABLE READ | condicional UPDATE | — |
| RN-19 Finalizar cita | Finalización | REPEATABLE READ | FOR UPDATE | — |
| RN-19 Verificar atencion_virtual | Reserva virtual | SERIALIZABLE | INSERT condicional | SERIALIZABLE_FAILURE |
| RNF-04 Bloqueo cuenta | Auth | REPEATABLE READ | FOR UPDATE (fila usuario) | — |
| RNF-06 Cambio password | Auth | REPEATABLE READ | FOR UPDATE (fila usuario) | — |

---

## 10. Próximo Paso

**Paso 13 — Migraciones**
Usar: `databases` (+ `flyway`/`liquibase` como herramientas de migración)
Salida: `proyecto/base_datos/12_migraciones/migraciones.md`

DETENERSE y esperar aprobación humana.