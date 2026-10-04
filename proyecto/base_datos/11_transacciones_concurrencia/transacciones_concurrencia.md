# Informe de Transacciones y Concurrencia — Paso 12

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español

**Generado:** Paso 12 — Transacciones y concurrencia

**Agente utilizado:** `database-engineer` (skill `databases` + `postgresql-table-design`)

**Workflow:** `02_database_workflow`

**DBMS:** PostgreSQL 18.6

**Fuentes principales:** `modelo_fisico.md`, `06_integridad/integridad.md`, `10_indices_rendimiento/indices_rendimiento.md`

**Estado:** Corregido manualmente después de la Revisión DBA del Paso 14. Pendiente de nueva validación humana.

---

## 1. Objetivo del Paso 12

Documentar la **estrategia completa de transacciones y manejo de concurrencia**, cubriendo:

- Aislamiento de transacciones (niveles de lectura)
- Control de bloqueos y deadlocks
- Transacciones atómicas para reglas de negocio (RN-06, RN-10, RN-12, RN-13, RN-19)
- Manejo de conteo de intentos fallidos y bloqueo de cuentas
- Serialización de citas en conflicto (`SELECT FOR UPDATE`, advisory locks)
- Configuración del DBMS (`deadlock_timeout`, `max_locks_per_transaction`)

**Salida:** `proyecto/base_datos/11_transacciones_concurrencia/transacciones_concurrencia.md`

DETENERSE y esperar aprobación humana.

---

## 2. Modelo de Concurrencia en PostgreSQL

### 2.1 MVCC (Multi-Version Concurrency Control)

PostgreSQL utiliza MVCC para permitir concurrencia entre lecturas y escrituras.

Cada transacción trabaja con una vista consistente de los datos según el nivel de aislamiento utilizado.

| Acción | Lectura | Escritura |
|--------|---------|-----------|
| **Lectura (`SELECT`)** | Snapshot MVCC | Generalmente no bloquea escrituras |
| **Escritura (`INSERT/UPDATE/DELETE`)** | Puede esperar por filas bloqueadas | Genera una nueva versión de fila |
| **Vacuum** | Limpia versiones antiguas | Recupera espacio progresivamente |

---

### 2.2 Niveles de Aislamiento Soportados

| Nivel | Dirty read | Non-repeatable read | Phantom read |
|-------|-----------|---------------------|--------------|
| **Read Uncommitted** | Se comporta como Read Committed en PostgreSQL | Sí | Sí |
| **Read Committed** | No | Sí | Sí |
| **Repeatable Read** | No | No | PostgreSQL mantiene snapshot consistente |
| **Serializable** | No | No | No a nivel lógico; puede provocar errores de serialización |

**Decisión para este sistema:**

- **Aplicación y lecturas normales:** `READ COMMITTED`
- **Reservas y reprogramaciones críticas:** `SERIALIZABLE`
- **Cambios de estado con bloqueo de una cita:** `REPEATABLE READ` o bloqueo explícito según operación

> **Regla:** Nunca depender de `READ UNCOMMITTED` como un nivel diferente en PostgreSQL, porque se comporta como `READ COMMITTED`.

---

## 3. Transacciones Atómicas — Patrones Implementados

### 3.1 Reserva de Cita Atómica (RN-06, RN-07, RN-10, D-21)

**Regla RN-06:** La creación, confirmación o reprogramación de una cita debe ser una transacción atómica: o toda la operación se completa o no se realiza ningún cambio.

Después de la normalización del Paso 05:

`cita.id_medico`

**ya no existe**.

El médico de una cita se obtiene mediante:

`cita.id_horario → horario.id_medico`.

El identificador del médico continúa utilizándose como dato de entrada para buscar sus horarios disponibles, pero no se almacena nuevamente dentro de `cita`.

```sql
BEGIN;

SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;


-- 1. Buscar y bloquear un horario disponible
-- perteneciente al médico solicitado.

SELECT id_horario
INTO v_horario
FROM horario
WHERE id_medico = p_id_medico
  AND (
        dia_semana = p_dia_semana
        OR fecha_especifica = p_fecha_especifica
      )
  AND estado = 'disponible'
ORDER BY dia_semana, hora_inicio
LIMIT 1
FOR UPDATE;


IF v_horario IS NULL THEN
    RAISE EXCEPTION
        'No existe un horario disponible para el médico solicitado';
END IF;


-- 2. Verificar que la especialidad seleccionada
-- esté asociada al médico propietario del horario.

IF NOT EXISTS (
    SELECT 1
    FROM horario h
    JOIN medico_especialidad me
      ON me.id_medico = h.id_medico
    WHERE h.id_horario = v_horario
      AND me.id_especialidad = p_id_especialidad
) THEN

    RAISE EXCEPTION
        'La especialidad seleccionada no corresponde al médico del horario';

END IF;


-- 3. Verificar que el paciente no tenga otra
-- cita activa superpuesta.
--
-- La validación completa está implementada
-- también mediante las reglas del Paso 08.

PERFORM 1
FROM cita c
WHERE c.id_paciente = p_id_paciente
  AND c.estado IN (
      'Programada',
      'Confirmada',
      'En_atencion'
  )
  AND c.fecha_hora_programada = p_fecha_hora_programada;

IF FOUND THEN

    RAISE EXCEPTION
        'El paciente ya posee una cita activa en ese horario';

END IF;


-- 4. Verificar doble reserva de la misma ocurrencia.

PERFORM 1
FROM cita c
WHERE c.id_horario = v_horario
  AND c.fecha_hora_programada = p_fecha_hora_programada
  AND c.estado IN (
      'Programada',
      'Confirmada',
      'En_atencion'
  );

IF FOUND THEN

    RAISE EXCEPTION
        'No hay disponibilidad: la ocurrencia del horario ya está reservada';

END IF;


-- 5. Insertar la cita.
--
-- IMPORTANTE:
-- id_medico NO forma parte de cita.

INSERT INTO cita (
    id_paciente,
    id_horario,
    id_especialidad,
    id_usuario_registrador,
    estado,
    modalidad,
    fecha_hora_programada,
    fecha_creacion,
    fecha_actualizacion
)
VALUES (
    p_id_paciente,
    v_horario,
    p_id_especialidad,
    p_id_usuario_registrador,
    'Programada',
    p_modalidad,
    p_fecha_hora_programada,
    now(),
    now()
)
RETURNING id_cita
INTO v_id_cita;


-- 6. Actualizar estado operativo del horario.

UPDATE horario
SET estado = 'reservado',
    updated_at = now()
WHERE id_horario = v_horario
  AND estado = 'disponible';


IF NOT FOUND THEN
    RAISE EXCEPTION
        'El horario dejó de estar disponible durante la reserva';
END IF;


-- 7. Si es virtual, registrar la información
-- necesaria para la atención virtual.

IF p_modalidad = 'virtual' THEN

    INSERT INTO atencion_virtual (
        id_cita,
        enlace_acceso,
        estado_disponibilidad
    )
    VALUES (
        v_id_cita,
        p_enlace_acceso,
        'disponible'
    );

END IF;


COMMIT;
```

### Defensa final de doble reserva

La comprobación previa dentro de la transacción mejora el mensaje funcional, pero la última línea de defensa en persistencia es el índice UNIQUE parcial definido en el Paso 11:

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

Este índice permite conservar:

- citas Canceladas;
- citas Finalizadas;
- citas No_asistidas;

sin impedir futuras reservas de la misma ocurrencia cuando corresponda.

### SERIALIZABLE_FAILURE

PostgreSQL puede devolver:

`SQLSTATE 40001`

ante conflictos de serialización.

La aplicación debe:

1. revertir la transacción;
2. aplicar un pequeño backoff;
3. reintentar la operación completa;
4. limitar el número de reintentos.

---

### 3.2 Confirmación de Cita (RN-07, RN-08, RN-10)

```sql
BEGIN;

SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;


-- 1. Obtener y bloquear la cita.

SELECT
    estado,
    id_horario
INTO
    v_estado,
    v_id_horario
FROM cita
WHERE id_cita = p_id_cita
FOR UPDATE;


-- 2. Validar transición.

IF v_estado != 'Programada' THEN

    RAISE EXCEPTION
        'La cita no está en estado Programada; estado actual: %',
        v_estado;

END IF;


-- 3. Actualizar cita.

UPDATE cita
SET estado = 'Confirmada',
    fecha_actualizacion = now()
WHERE id_cita = p_id_cita;


-- 4. Ocupar horario.

UPDATE horario
SET estado = 'ocupado',
    updated_at = now()
WHERE id_horario = v_id_horario;


-- 5. Registrar auditoría.

INSERT INTO registro_auditoria (
    id_cita,
    id_usuario_responsable,
    accion,
    campo_modificado,
    valor_anterior,
    valor_actual,
    fecha_hora
)
VALUES (
    p_id_cita,
    p_id_usuario_responsable,
    'modificar',
    'estado',
    'Programada',
    'Confirmada',
    clock_timestamp()
);


-- 6. Si existe atención virtual,
-- conservar el recurso disponible.

UPDATE atencion_virtual
SET estado_disponibilidad = 'disponible',
    updated_at = now()
WHERE id_cita = p_id_cita;


COMMIT;
```

No se utiliza:

`fecha_inicio_sesion`

porque dicho atributo no forma parte del modelo físico aprobado.

---

### 3.3 Cancelación de Cita (RN-08, RN-13)

```sql
BEGIN;

SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;


SELECT
    estado,
    id_horario
INTO
    v_estado,
    v_id_horario
FROM cita
WHERE id_cita = p_id_cita
FOR UPDATE;


IF v_estado NOT IN ('Programada', 'Confirmada') THEN

    RAISE EXCEPTION
        'No se puede cancelar una cita en estado %',
        v_estado;

END IF;


-- Actualizar cita.

UPDATE cita
SET estado = 'Cancelada',
    fecha_actualizacion = now()
WHERE id_cita = p_id_cita;


-- Liberar horario.

UPDATE horario
SET estado = 'disponible',
    updated_at = now()
WHERE id_horario = v_id_horario;


-- Deshabilitar acceso virtual si existe.

UPDATE atencion_virtual
SET estado_disponibilidad = 'no_disponible',
    updated_at = now()
WHERE id_cita = p_id_cita;


-- Auditoría.

INSERT INTO registro_auditoria (
    id_cita,
    id_usuario_responsable,
    accion,
    campo_modificado,
    valor_anterior,
    valor_actual,
    fecha_hora
)
VALUES (
    p_id_cita,
    p_id_usuario_responsable,
    'cancelar',
    'estado',
    v_estado,
    'Cancelada',
    clock_timestamp()
);


COMMIT;
```

La cancelación normal no se registra como un incidente de atención virtual.

---

### 3.4 Reprogramación Atómica (RN-12, D-21)

La reprogramación debe ejecutarse como una sola transacción.

`cita.id_medico` no existe.

El médico actual se determina mediante:

`cita.id_horario → horario.id_medico`.

```sql
BEGIN;

SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;


-- 1. Obtener y bloquear cita actual.
--
-- El médico se obtiene mediante el JOIN con horario.

SELECT
    c.estado,
    c.id_paciente,
    c.id_horario,
    c.id_especialidad,
    c.fecha_hora_programada,
    h.id_medico
INTO
    v_estado,
    v_id_paciente,
    v_horario_anterior,
    v_id_especialidad,
    v_fecha_anterior,
    v_id_medico
FROM cita c
JOIN horario h
  ON h.id_horario = c.id_horario
WHERE c.id_cita = p_id_cita
FOR UPDATE OF c;


IF v_estado NOT IN ('Programada', 'Confirmada') THEN

    RAISE EXCEPTION
        'Transición no permitida para reprogramar: %',
        v_estado;

END IF;


-- 2. Buscar y bloquear un nuevo horario
-- del mismo médico.

SELECT h.id_horario
INTO v_nuevo_horario
FROM horario h
WHERE h.id_medico = v_id_medico
  AND (
        h.dia_semana = p_dia_semana
        OR h.fecha_especifica = p_fecha_especifica
      )
  AND h.estado = 'disponible'
ORDER BY h.hora_inicio
LIMIT 1
FOR UPDATE;


IF v_nuevo_horario IS NULL THEN

    RAISE EXCEPTION
        'No existe un nuevo horario disponible';

END IF;


-- 3. Verificar que la especialidad siga siendo válida
-- para el médico propietario del nuevo horario.

IF NOT EXISTS (
    SELECT 1
    FROM medico_especialidad me
    WHERE me.id_medico = v_id_medico
      AND me.id_especialidad = v_id_especialidad
) THEN

    RAISE EXCEPTION
        'La especialidad de la cita no corresponde al médico del nuevo horario';

END IF;


-- 4. Verificar doble reserva en la nueva ocurrencia.

IF EXISTS (
    SELECT 1
    FROM cita c
    WHERE c.id_horario = v_nuevo_horario
      AND c.fecha_hora_programada =
          p_nueva_fecha_hora_programada
      AND c.id_cita <> p_id_cita
      AND c.estado IN (
          'Programada',
          'Confirmada',
          'En_atencion'
      )
) THEN

    RAISE EXCEPTION
        'La nueva ocurrencia ya se encuentra reservada';

END IF;


-- 5. Liberar horario anterior.

UPDATE horario
SET estado = 'disponible',
    updated_at = now()
WHERE id_horario = v_horario_anterior;


-- 6. Reservar nuevo horario.

UPDATE horario
SET estado = 'reservado',
    updated_at = now()
WHERE id_horario = v_nuevo_horario
  AND estado = 'disponible';


IF NOT FOUND THEN

    RAISE EXCEPTION
        'El nuevo horario dejó de estar disponible';

END IF;


-- 7. Actualizar la cita.

UPDATE cita
SET id_horario = v_nuevo_horario,
    fecha_hora_programada =
        p_nueva_fecha_hora_programada,
    estado = 'Programada',
    fecha_actualizacion = now()
WHERE id_cita = p_id_cita;


-- 8. Registrar auditoría.

INSERT INTO registro_auditoria (
    id_cita,
    id_usuario_responsable,
    accion,
    campo_modificado,
    valor_anterior,
    valor_actual,
    fecha_hora
)
VALUES (
    p_id_cita,
    p_id_usuario_responsable,
    'reprogramar',
    'horario',
    CONCAT(
        v_horario_anterior,
        ' / ',
        v_fecha_anterior
    ),
    CONCAT(
        v_nuevo_horario,
        ' / ',
        p_nueva_fecha_hora_programada
    ),
    clock_timestamp()
);


COMMIT;
```

El nuevo horario pertenece al mismo médico porque se selecciona mediante:

```text
horario.id_medico = v_id_medico
```

Si posteriormente el sistema permite cambiar de médico durante una reprogramación, deberá documentarse como una decisión funcional adicional.

---

### 3.5 Finalización de Cita (RN-19, RN-20)

```sql
BEGIN;

SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;


-- Obtener y bloquear la cita.

SELECT
    estado,
    fecha_hora_inicio_atencion,
    id_paciente,
    id_horario
INTO
    v_estado,
    v_fecha_hora_inicio_atencion,
    v_id_paciente,
    v_id_horario
FROM cita
WHERE id_cita = p_id_cita
FOR UPDATE;


IF v_estado != 'En_atencion' THEN

    RAISE EXCEPTION
        'Solo se puede finalizar una cita en estado En_atencion';

END IF;


IF v_fecha_hora_inicio_atencion IS NULL THEN

    RAISE EXCEPTION
        'La cita no tiene registrada la fecha/hora de inicio de atención';

END IF;


IF p_fecha_hora_fin <= v_fecha_hora_inicio_atencion THEN

    RAISE EXCEPTION
        'La fecha/hora de fin debe ser posterior al inicio de atención';

END IF;


-- Finalizar cita.

UPDATE cita
SET estado = 'Finalizada',
    fecha_hora_fin_atencion = p_fecha_hora_fin,
    fecha_actualizacion = now()
WHERE id_cita = p_id_cita;


-- Cerrar disponibilidad virtual si existe.

UPDATE atencion_virtual
SET estado_disponibilidad = 'no_disponible',
    updated_at = now()
WHERE id_cita = p_id_cita;


-- Liberar estado operativo del horario.

UPDATE horario
SET estado = 'disponible',
    updated_at = now()
WHERE id_horario = v_id_horario;


-- Auditoría.

INSERT INTO registro_auditoria (
    id_cita,
    id_usuario_responsable,
    accion,
    campo_modificado,
    valor_anterior,
    valor_actual,
    fecha_hora
)
VALUES (
    p_id_cita,
    p_id_usuario_responsable,
    'finalizar',
    'estado',
    'En_atencion',
    'Finalizada',
    clock_timestamp()
);


COMMIT;
```

No se insertan campos inexistentes como:

- `atencion_virtual.fecha_hora_fin`;
- `atencion_virtual.observaciones`.

La tabla `atencion_virtual` conserva únicamente los atributos definidos por el modelo físico.

---

## 4. Bloqueos (Locks) — Gestión y Prevención de Deadlocks

### 4.1 Tipos de Bloqueo en PostgreSQL

| Clase de bloqueo | Uso | Operación relacionada |
|-----------------|-----|------------------------|
| **AccessShareLock** | SELECT simple | Lectura |
| **RowShareLock** | `SELECT FOR UPDATE` / `FOR SHARE` | Lectura con intención de bloqueo |
| **RowExclusiveLock** | INSERT / UPDATE / DELETE | Escritura |
| **ShareRowExclusiveLock** | Operaciones estructurales específicas | DDL parcial |
| **ShareLock** | Operaciones de mantenimiento determinadas | Bloqueos compartidos |
| **ExclusiveLock** | Operaciones exclusivas | Bloqueo explícito |
| **AccessExclusiveLock** | DDL destructivo o estructural fuerte | ALTER / DROP / TRUNCATE |

---

### 4.2 `SELECT FOR UPDATE`

```sql
-- Bloquear una cita concreta.

SELECT *
FROM cita
WHERE id_cita = ?
FOR UPDATE;
```

```sql
-- Bloquear un horario concreto.

SELECT *
FROM horario
WHERE id_horario = ?
FOR UPDATE;
```

```sql
-- SKIP LOCKED para seleccionar horarios libres
-- sin esperar filas actualmente bloqueadas.

SELECT *
FROM horario
WHERE estado = 'disponible'
FOR UPDATE SKIP LOCKED;
```

```sql
-- NOWAIT para fallar inmediatamente
-- si otra transacción posee el bloqueo.

SELECT *
FROM horario
WHERE id_horario = ?
FOR UPDATE NOWAIT;
```

> **Regla:** `SKIP LOCKED` y `NOWAIT` pueden utilizarse cuando el flujo funcional admita reintento o selección de otro recurso.

---

### 4.3 Configuración para Deadlocks

```conf
deadlock_timeout = '1s'
lock_timeout = '30s'
```

**Estrategia de prevención:**

1. Acceder a las tablas en un orden consistente.
2. Mantener transacciones cortas.
3. Utilizar `FOR UPDATE` cuando una lectura preceda a una escritura crítica.
4. Mantener los índices necesarios en claves foráneas.
5. Evitar bloqueos innecesarios.
6. Reintentar operaciones cuando PostgreSQL detecte un deadlock o conflicto serializable.

---

### 4.4 Advisory Locks

Los advisory locks son una herramienta complementaria.

```sql
-- Ejemplo de lock transaccional por una clave.

SELECT pg_advisory_xact_lock(p_id_horario);
```

También pueden utilizarse variantes:

```sql
SELECT pg_try_advisory_xact_lock(p_id_horario);
```

Los advisory locks no reemplazan:

- restricciones UNIQUE;
- FK;
- `FOR UPDATE`;
- transacciones;
- índices de persistencia.

Para la reserva de citas, la protección principal permanece en:

- bloqueo de horario;
- transacción;
- índice UNIQUE parcial.

---

## 5. Conteo de Intentos Fallidos y Bloqueo de Cuentas (RNF-04)

El control de autenticación corresponde principalmente a la aplicación.

La fila del usuario puede bloquearse durante una actualización de intentos para evitar condiciones de carrera.

```sql
BEGIN;

SELECT
    id_usuario,
    username,
    password_hash,
    activo,
    intentos_fallidos,
    bloqueado_hasta,
    id_paciente,
    id_medico
INTO v_usuario
FROM usuario
WHERE username = p_username
FOR UPDATE;


IF NOT FOUND THEN

    RAISE EXCEPTION
        'Credenciales inválidas';

END IF;


IF NOT v_usuario.activo THEN

    RAISE EXCEPTION
        'Cuenta inactiva';

END IF;


IF v_usuario.bloqueado_hasta IS NOT NULL
   AND v_usuario.bloqueado_hasta > now() THEN

    RAISE EXCEPTION
        'Cuenta temporalmente bloqueada';

END IF;


-- La validación criptográfica de contraseña
-- corresponde a la capa de autenticación de aplicación.


-- En caso de intento fallido:

UPDATE usuario
SET intentos_fallidos =
        intentos_fallidos + 1
WHERE id_usuario = v_usuario.id_usuario;


-- En caso de login exitoso:

UPDATE usuario
SET intentos_fallidos = 0,
    ultimo_acceso = now(),
    bloqueado_hasta = NULL
WHERE id_usuario = v_usuario.id_usuario;


COMMIT;
```

El número exacto de intentos permitidos y el tiempo de bloqueo deben provenir de una política aprobada.

No se fija aquí arbitrariamente un valor como:

- 5 intentos;
- 30 minutos;

si estos valores no forman parte de los requisitos aprobados.

---

### 5.1 Variables RLS

Después de autenticar al usuario, la aplicación debe establecer el contexto dentro de la misma transacción de trabajo.

Ejemplo:

```sql
SET LOCAL app.current_user_id =
    '123';

SET LOCAL app.current_paciente_id =
    '456';

SET LOCAL app.current_medico_id =
    '789';

SET LOCAL app.current_role =
    'medico';
```

Con PgBouncer en:

`pool_mode = transaction`

estas variables deben establecerse nuevamente dentro de cada transacción protegida.

---

## 6. Configuración del DBMS para Concurrencia

### 6.1 `postgresql.conf`

Configuración de referencia:

```conf
# Concurrencia y locks

max_connections = 200
superuser_reserved_connections = 3

max_locks_per_transaction = 256

deadlock_timeout = '1s'

lock_timeout = '30s'

statement_timeout = '60s'

idle_in_transaction_session_timeout = '180s'


# MVCC y vacuum

vacuum_freeze_min_age = 50000000

vacuum_freeze_table_age = 150000000

autovacuum_vacuum_scale_factor = 0.05

autovacuum_analyze_scale_factor = 0.02


# Memoria por operación

work_mem = 64MB

maintenance_work_mem = 2GB

temp_buffers = 256MB


# WAL y checkpoint

checkpoint_timeout = '15min'

checkpoint_completion_target = 0.9

wal_buffers = 64MB

commit_delay = 0

commit_siblings = 5


# Replicación, si aplica

max_wal_senders = 3

wal_sender_timeout = '60s'
```

Estos valores representan una configuración técnica inicial.

Deben ajustarse posteriormente según:

- infraestructura disponible;
- pruebas de carga;
- consumo de memoria;
- perfil real de concurrencia.

---

### 6.2 Controles en PgBouncer

```ini
max_client_conn = 1000

default_pool_size = 25

reserve_pool_size = 5

reserve_pool_timeout = 3

server_check_delay = 20

server_check_query = SELECT 1

server_lifetime = 3600

server_idle_timeout = 600

pool_mode = transaction
```

> **Advertencia:** en `pool_mode = transaction`, una conexión física puede cambiar entre transacciones. El contexto RLS debe establecerse dentro de cada transacción mediante `SET LOCAL`.

---

### 6.3 Controles en el Driver de Aplicación

| Driver | Configuración de referencia |
|--------|-----------------------------|
| **PostgreSQL JDBC** | timeout y aislamiento configurados explícitamente |
| **Node.js pg** | timeout de conexión, idle timeout y keepalive |
| **.NET Npgsql** | pool limitado y tiempos de conexión |
| **Python asyncpg** | pool limitado y timeout de conexión |

Los valores exactos deberán adaptarse a la implementación del backend.

---

## 7. Manejo de Errores de Concurrencia

### 7.1 SERIALIZABLE_FAILURE — SQLSTATE 40001

Las operaciones de reserva y reprogramación ejecutadas bajo:

`SERIALIZABLE`

pueden fallar con:

`SQLSTATE 40001`.

Ejemplo conceptual:

```python
def reservar_cita(datos):
    max_reintentos = 5

    for intento in range(max_reintentos):
        try:
            ejecutar_transaccion_reserva(datos)
            return

        except SerializationFailure:
            if intento == max_reintentos - 1:
                raise

            esperar_backoff(intento)
```

El reintento debe ejecutar **la transacción completa**, no solamente la sentencia que falló.

---

### 7.2 DeadlockDetected

Cuando PostgreSQL detecte un deadlock:

1. una transacción será abortada;
2. la aplicación debe registrar el evento;
3. puede realizar un reintento controlado;
4. debe ejecutarse nuevamente la operación completa.

Ejemplo conceptual:

```python
except DeadlockDetected:
    registrar_evento()
    esperar_backoff()
    reintentar()
```

---

### 7.3 Monitoreo de Bloqueos

Para monitorear sesiones y bloqueos se pueden consultar:

```sql
SELECT
    pid,
    usename,
    state,
    wait_event_type,
    wait_event,
    query_start,
    query
FROM pg_stat_activity
WHERE datname = current_database();
```

Para identificar bloqueadores se puede complementar con:

```sql
SELECT
    pid,
    pg_blocking_pids(pid) AS blocking_pids,
    query
FROM pg_stat_activity
WHERE cardinality(
    pg_blocking_pids(pid)
) > 0;
```

---

## 8. Estrategia de Reparto de Carga para Citas Simultáneas

Arquitectura conceptual:

```text
Clientes
   |
   v
Aplicación
   |
   v
PgBouncer
   |
   v
PostgreSQL
   |
   +--> horario
   |
   +--> cita
   |
   +--> atencion_virtual
```

La concurrencia de reservas se controla principalmente mediante:

1. PgBouncer para limitar conexiones físicas;
2. transacciones cortas;
3. `SELECT ... FOR UPDATE`;
4. nivel `SERIALIZABLE` para operaciones críticas;
5. índice UNIQUE parcial;
6. reintentos por `40001`;
7. monitoreo de bloqueos.

---

## 9. Relación Médico–Cita después de la normalización

Esta corrección es fundamental para todo el Paso 12.

La tabla `cita` ya no contiene:

```text
id_medico
```

El médico se determina siempre mediante:

```text
cita.id_horario
        ↓
horario.id_medico
```

Por tanto, son incorrectos patrones como:

```sql
SELECT *
FROM cita
WHERE id_medico = ?;
```

En su lugar se utiliza:

```sql
SELECT c.*
FROM cita c
JOIN horario h
  ON h.id_horario = c.id_horario
WHERE h.id_medico = ?;
```

Sin embargo, siguen siendo válidos:

```text
horario.id_medico
usuario.id_medico
medico_especialidad.id_medico
```

La normalización eliminó `id_medico` solamente de:

`cita`.

---

## 10. Regla de Doble Reserva

La versión anterior utilizaba:

```text
id_medico + id_horario
```

como criterio absoluto.

La regla corregida utiliza la **ocurrencia de un horario**:

```text
id_horario
+
fecha_hora_programada
+
estado activo
```

Estados activos:

```text
Programada
Confirmada
En_atencion
```

Estados históricos que no deben bloquear indefinidamente:

```text
Cancelada
Finalizada
No_asistida
```

La protección se implementa mediante:

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

Esto permite conservar el historial sin imponer:

```text
UNIQUE(id_horario)
```

absoluto.

---

## 11. Resumen de Transacciones Críticas

| Regla | Transacción | Nivel de aislamiento | Bloqueos | Retry |
|-------|-------------|----------------------|----------|-------|
| RN-06 Reserva atómica | Reserva completa | SERIALIZABLE | horario `FOR UPDATE` + índice parcial | `40001` |
| RN-07 Confirmar cita | Confirmación | REPEATABLE READ | cita `FOR UPDATE` | Según error |
| RN-08 Cancelar cita | Cancelación | REPEATABLE READ | cita `FOR UPDATE` | Según error |
| RN-10 Ocupar horario | Reserva/confirmación | Dentro de la misma transacción | UPDATE horario | Según conflicto |
| RN-12 Reprogramar | Reprogramación completa | SERIALIZABLE | cita + nuevo horario | `40001` |
| RN-13 Liberar horario | Cancelación/reprogramación | Dentro de la transacción | UPDATE horario | Según conflicto |
| RN-19 Atención virtual | Reserva virtual | SERIALIZABLE cuando forme parte de reserva | INSERT/UPDATE relacionado | `40001` |
| RN-20 Finalizar cita | Finalización | REPEATABLE READ | cita `FOR UPDATE` | Según error |
| RNF-04 Bloqueo cuenta | Autenticación | READ COMMITTED / bloqueo de fila | usuario `FOR UPDATE` | Según implementación |

---

## 12. Correcciones realizadas respecto de la versión anterior

### Corrección 1

Se eliminó toda referencia a:

```text
cita.id_medico
```

---

### Corrección 2

La reserva ya no inserta:

```text
id_medico
```

en `cita`.

---

### Corrección 3

La reprogramación obtiene el médico mediante:

```text
cita.id_horario
→ horario.id_medico
```

---

### Corrección 4

La regla de doble reserva dejó de utilizar:

```text
id_medico + id_horario
```

y utiliza:

```text
id_horario + fecha_hora_programada
```

para estados activos.

---

### Corrección 5

Se reemplazaron referencias antiguas:

```text
fecha_hora_inicio
fecha_hora_fin
```

cuando correspondían a datos de `cita`, por:

```text
fecha_hora_programada
fecha_hora_inicio_atencion
fecha_hora_fin_atencion
```

según su función.

---

### Corrección 6

Se eliminaron referencias inexistentes de `atencion_virtual` como:

```text
fecha_inicio_sesion
fecha_fin_sesion
fecha_hora_fin
observaciones
```

---

### Corrección 7

La cancelación ya no registra automáticamente:

```text
detalles_incidente = 'Cita cancelada...'
```

porque una cancelación normal no constituye un incidente técnico.

---

### Corrección 8

Se mantiene `horario.id_medico`.

No debe eliminarse porque es la relación correcta:

```text
Horario → Médico
```

---

### Corrección 9

Se mantiene `usuario.id_medico`.

Ese atributo corresponde a la vinculación opcional:

```text
Usuario ↔ Médico
```

y no constituye la redundancia encontrada en `cita`.

---

## 13. Conclusiones del Paso 12 corregido

1. Las reservas y reprogramaciones críticas utilizan transacciones atómicas.

2. `SERIALIZABLE` se utiliza en reserva y reprogramación.

3. `SELECT FOR UPDATE` protege los horarios y citas que serán modificados.

4. `cita.id_medico` fue eliminado de todas las operaciones del Paso 12.

5. El médico se obtiene mediante:

   `cita.id_horario → horario.id_medico`.

6. La reserva inserta únicamente:

   - paciente;
   - horario;
   - especialidad;
   - usuario registrador;
   - estado;
   - modalidad;
   - fecha programada.

7. La reprogramación conserva la relación normalizada con Médico.

8. La doble reserva se controla por ocurrencia de horario y estado activo.

9. El índice UNIQUE parcial constituye la última línea de defensa en persistencia.

10. Las citas históricas Canceladas, Finalizadas o No asistidas pueden conservarse.

11. Las columnas temporales utilizadas se alinean con el modelo físico actual.

12. Las operaciones sobre `atencion_virtual` utilizan solamente atributos existentes.

13. Los errores `40001` deben generar un reintento de la transacción completa.

14. El documento queda alineado con:

   - modelo lógico;
   - normalización;
   - modelo físico;
   - integridad;
   - índices y rendimiento.

---

## 14. Estado del archivo

**Archivo:**

`proyecto/base_datos/11_transacciones_concurrencia/transacciones_concurrencia.md`

**Paso de origen:**

`Paso 12 — Transacciones y concurrencia`

**Agente original:**

`database-engineer`

**Skills originales:**

- `databases`
- `postgresql-table-design`

**Corrección posterior:**

Propagación de la normalización establecida en el Paso 05 y de los hallazgos encontrados durante el Paso 14 — Revisión DBA.

**Estado actual:**

Corregido manualmente y pendiente de nueva Revisión DBA.

---

## 15. Próximo Paso

**Paso 13 — Migraciones**

Salida:

`proyecto/base_datos/12_migraciones/migraciones.md`

Sin embargo, debido al resultado:

```text
STATUS: CHANGES_REQUIRED
```

del Paso 14, esta corrección no autoriza avanzar al Paso 15.

Debe corregirse también:

`proyecto/base_datos/12_migraciones/migraciones.md`

y posteriormente volver a ejecutar:

**Paso 14 — Revisión DBA**.

Solo se podrá continuar cuando el resultado sea:

```text
STATUS: APPROVED
```

DETENERSE y esperar aprobación humana.