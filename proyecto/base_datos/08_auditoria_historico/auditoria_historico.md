# Informe de Auditoría, Histórico y Versionamiento — Paso 10

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español
**Generado:** Paso 10 — Auditoría, histórico y versionamiento
**Agente utilizado:** `database-engineer` (skill `databases` + `postgresql-table-design`)
**Workflow:** `02_database_workflow`
**DBMS:** PostgreSQL 18.6
**Fuentes principales:** `modelo_fisico.md`, `06_integridad/integridad.md`, `07_seguridad/seguridad.md`
**Estado:** Pendiente de validación humana

---

## 1. Objetivo del Paso 10

Diseñar y documentar la **estrategia completa de auditoría, histórico y versionamiento** del sistema, cubriendo:

- Auditoría de operaciones críticas (ya iniciada en `registro_auditoria`)
- Estrategia de versionamiento de filas (temporal tables / system-versioned tables)
- Retención y archivado de datos (≥ 5 años por RN-22/D-20)
- Particionamiento de tablas de auditoría
- Recuperación punto-en-el-tiempo (PITR) y Point-in-Time Recovery
- Datos históricos para reportes y análisis

**Salida:** `proyecto/base_datos/08_auditoria_historico/auditoria_historico.md`

DETENERSE y esperar aprobación humana.

---

## 2. Arquitectura de Auditoría y Histórico

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        CAPA DE APLICACIÓN                                │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┼───────────────┐
                    ▼               ▼               ▼
            ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
            │  Cita       │ │  Horario    │ │  Paciente   │
            │  (tabla)    │ │  (tabla)    │ │  (tabla)    │
            └──────┬──────┘ └──────┬──────┘ └──────┬──────┘
                   │               │               │
           ┌───────┴───────┐       │               │
           ▼               ▼       ▼               ▼
    ┌────────────────────────────────────────────────────────┐
    │              TRIGGERS DE AUDITORÍA (AFTER)              │
    │  • fn_auditar_cambio_cita()                             │
    │  • fn_auditar_horario()                                 │
    │  • fn_auditar_paciente()                                │
    └─────────────────────────┬───────────────────────────────┘
                              │
                              ▼
    ┌────────────────────────────────────────────────────────┐
    │              REGISTRO_AUDITORIA (Particionada)         │
    │  • id_registro BIGSERIAL PK                             │
    │  • id_cita, id_usuario_responsable FK                  │
    │  • accion, campo_modificado, valor_anterior, actual    │
    │  • fecha_hora (PARTITION KEY)                          │
    └─────────────────────────┬───────────────────────────────┘
                              │
                    ┌─────────┴─────────┐
                    ▼                   ▼
           ┌──────────────┐      ┌──────────────┐
           │  pgAudit     │      │  pgBackRest  │
           │  (DDL, sesión│      │  (PITR,      │
           │   misrole)   │      │   WAL arch)  │
           └──────────────┘      └──────────────┘
                    │                   │
                    └─────────┬─────────┘
                              ▼
                   ┌────────────────────┐
                   │  SIEM / Log Store  │
                   │  (Inmutable, ≥5a)  │
                   └────────────────────┘
```

---

## 3. Tabla de Auditoría Principal (`registro_auditoria`)

### 3.1 Definición Física (revisada del modelo físico)

```sql
CREATE TABLE registro_auditoria (
    id_registro        BIGINT GENERATED ALWAYS AS IDENTITY,
    id_cita            BIGINT NOT NULL,
    id_usuario_responsable BIGINT NOT NULL,
    accion             audit_accion_enum NOT NULL,
    campo_modificado   VARCHAR(100),
    valor_anterior     TEXT,
    valor_actual       TEXT NOT NULL,
    fecha_hora         TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    session_id         BIGINT,
    transaction_id     BIGINT,
    client_addr        INET,
    user_agent         VARCHAR(500),

    CONSTRAINT pk_registro_auditoria PRIMARY KEY (id_registro, fecha_hora),
    CONSTRAINT fk_auditoria_cita FOREIGN KEY (id_cita)
        REFERENCES cita(id_cita) ON DELETE RESTRICT,
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario_responsable)
        REFERENCES usuario(id_usuario) ON DELETE RESTRICT
) PARTITION BY RANGE (fecha_hora);
```

### 3.2 ENUM para acciones de auditoría

```sql
CREATE TYPE audit_accion_enum AS ENUM (
    'crear',
    'modificar',
    'cancelar',
    'finalizar',
    'reprogramar',
    'cambiar_modalidad',
    'cambiar_estado',
    'asignar_medico',
    'liberar_horario',
    'bloquear_usuario',
    'desbloquear_usuario'
);
```

### 3.3 Índices de auditoría (Paso 11 — ver §8)

```sql
-- Índice para consultas por cita (historial completo)
CREATE INDEX idx_auditoria_cita ON registro_auditoria (id_cita, fecha_hora DESC);

-- Índice para consultas por usuario responsable
CREATE INDEX idx_auditoria_usuario ON registro_auditoria (id_usuario_responsable, fecha_hora DESC);

-- Índice para búsquedas por acción
CREATE INDEX idx_auditoria_accion ON registro_auditoria (accion, fecha_hora DESC);
```

---

## 4. Particionamiento de `registro_auditoria`

### 4.1 Estrategia: Partición por tiempo (RANGE mensual)

```sql
-- Función para crear particiones automáticamente
CREATE OR REPLACE FUNCTION fn_crear_particion_auditoria(
    p_mes_inicio DATE,
    p_meses INT DEFAULT 1
) RETURNS VOID AS $$
DECLARE
    v_inicio DATE := p_mes_inicio;
    v_fin DATE;
    v_nombre TEXT;
BEGIN
    FOR i IN 1..p_meses LOOP
        v_fin := (v_inicio + INTERVAL '1 month')::date;
        v_nombre := 'registro_auditoria_' || to_char(v_inicio, 'YYYY_MM');

        EXECUTE format(
            'CREATE TABLE IF NOT EXISTS %I PARTITION OF registro_auditoria
             FOR VALUES FROM (%L) TO (%L)',
            v_nombre, v_inicio, v_fin
        );

        -- Índices locales en cada partición
        EXECUTE format('CREATE INDEX IF NOT EXISTS %I ON %I (id_cita, fecha_hora DESC)',
                       'idx_' || v_nombre || '_cita', v_nombre);
        EXECUTE format('CREATE INDEX IF NOT EXISTS %I ON %I (id_usuario_responsable, fecha_hora DESC)',
                       'idx_' || v_nombre || '_usuario', v_nombre);

        v_inicio := v_fin;
    END LOOP;
END;
$$ LANGUAGE plpgsql;

-- Crear particiones para los próximos 12 meses (incluyendo actual)
SELECT fn_crear_particion_auditoria(date_trunc('month', CURRENT_DATE)::date, 13);

-- Partición por defecto (captura datos fuera de rango)
CREATE TABLE registro_auditoria_default PARTITION OF registro_auditoria DEFAULT;
```

### 4.2 Retención y limpieza automática

```sql
-- Función para archivar particiones antiguas (> 5 años + 6 meses buffer)
CREATE OR REPLACE FUNCTION fn_archivar_particiones_auditoria(
    p_retencion_anos INT DEFAULT 5,
    p_buffer_meses INT DEFAULT 6
) RETURNS TABLE(particion TEXT, accion TEXT) AS $$
DECLARE
    v_corte DATE := (CURRENT_DATE - INTERVAL '1 month' * (p_retencion_anos * 12 + p_buffer_meses))::date;
    v_part RECORD;
BEGIN
    FOR v_part IN
        SELECT schemaname, tablename
        FROM pg_tables
        WHERE tablename LIKE 'registro_auditoria_%'
          AND tablename ~ '^registro_auditoria_\d{4}_\d{2}$'
    LOOP
        -- Extraer fecha de la partición
        DECLARE
            v_ano INT := substring(v_part.tablename FROM '(\d{4})_\d{2}')::int;
            v_mes INT := substring(v_part.tablename FROM '\d{4}_(\d{2})')::int;
            v_fin_part DATE := make_date(v_ano, v_mes, 1) + INTERVAL '1 month';
        BEGIN
            IF v_fin_part < v_corte THEN
                -- Exportar a almacenamiento frío (ej. S3, archivo comprimido)
                -- NOTA: Implementación real requiere COPY TO PROGRAM o external table
                RETURN QUERY SELECT v_part.tablename, 'ARCHIVAR'::TEXT;

                -- Marcar para eliminación (requiere confirmación manual)
                -- DROP TABLE v_part.tablename;
            ELSE
                RETURN QUERY SELECT v_part.tablename, 'MANTENER'::TEXT;
            END IF;
        END;
    END LOOP;
END;
$$ LANGUAGE plpgsql;

-- Job programado (ej. pg_cron)
-- SELECT cron.schedule('archivar-auditoria-mensual', '0 2 1 * *', 'SELECT * FROM fn_archivar_particiones_auditoria()');
```

> **Nota RN-22/D-20:** Conservación mínima **≥ 5 años**. Se añade 6 meses de buffer antes de archivar. El archivo final se mueve a almacenamiento inmutable (WORM, S3 Object Lock, cinta).

---

## 5. Versionamiento de Filas (System-Versioned Tables)

### 5.1 Estrategia: Tablas temporales nativas (PostgreSQL 18.x no tiene `SYSTEM VERSIONING` nativo completo)

PostgreSQL 18 **no** implementa aún `SYSTEM VERSIONING` como SQL:2011 (a diferencia de SQL Server, MariaDB 10.3+, Oracle 12c). Alternativas:

| Opción | Descripción | Pros | Contras |
|--------|-------------|------|---------|
| **Tablas de histórico manual** | Trigger `AFTER` que inserta en tabla `_hist` | Control total, compatible PG18 | Código repetitivo |
| **`temporal_tables` extension** | Extensión de terceros | Funcionalidad completa | Dependencia externa |
| **pgAudit + WAL** | Auditoría completa vía logs | Sin cambios en tablas | Consulta histórica compleja |
| **CDC (Change Data Capture)** | Logical replication slots | Tiempo real, streaming | Complejidad operacional |

**Decisión para este proyecto:** **Tablas de histórico manuales** para tablas críticas + `pgAudit` para DDL/sesiones.

### 5.2 Tablas de histórico para entidades críticas

#### 5.2.1 Histórico de `cita` (tabla central)

```sql
-- Tabla histórico de citas (versión completa en cada cambio)
CREATE TABLE cita_historico (
    id_historico     BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_cita          BIGINT NOT NULL,
    id_paciente      BIGINT NOT NULL,
    id_medico        BIGINT NOT NULL,
    id_horario       BIGINT NOT NULL,
    id_especialidad  BIGINT NOT NULL,
    id_usuario_registrador BIGINT NOT NULL,
    estado           estado_cita_enum NOT NULL,
    modalidad        modalidad_cita_enum NOT NULL,
    fecha_hora_inicio TIMESTAMPTZ NOT NULL,
    fecha_hora_fin   TIMESTAMPTZ,
    observaciones    TEXT,
    accion           audit_accion_enum NOT NULL,
    usuario_accion   BIGINT NOT NULL,
    fecha_accion     TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    txid             BIGINT NOT NULL DEFAULT txid_current()
) PARTITION BY RANGE (fecha_accion);

-- Índices
CREATE INDEX idx_cita_hist_cita ON cita_historico (id_cita, fecha_accion DESC);
CREATE INDEX idx_cita_hist_paciente ON cita_historico (id_paciente, fecha_accion DESC);
CREATE INDEX idx_cita_hist_medico ON cita_historico (id_medico, fecha_accion DESC);
```

#### 5.2.2 Trigger para poblar histórico

```sql
CREATE OR REPLACE FUNCTION fn_auditar_cita_historico()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO cita_historico (
            id_cita, id_paciente, id_medico, id_horario, id_especialidad,
            id_usuario_registrador, estado, modalidad, fecha_hora_inicio,
            fecha_hora_fin, observaciones, accion, usuario_accion
        ) VALUES (
            NEW.id_cita, NEW.id_paciente, NEW.id_medico, NEW.id_horario,
            NEW.id_especialidad, NEW.id_usuario_registrador, NEW.estado,
            NEW.modalidad, NEW.fecha_hora_inicio, NEW.fecha_hora_fin,
            NEW.observaciones, 'crear', current_setting('app.current_user_id')::bigint
        );
        RETURN NEW;
    ELSIF TG_OP = 'UPDATE' THEN
        INSERT INTO cita_historico (
            id_cita, id_paciente, id_medico, id_horario, id_especialidad,
            id_usuario_registrador, estado, modalidad, fecha_hora_inicio,
            fecha_hora_fin, observaciones, accion, usuario_accion
        ) VALUES (
            NEW.id_cita, NEW.id_paciente, NEW.id_medico, NEW.id_horario,
            NEW.id_especialidad, NEW.id_usuario_registrador, NEW.estado,
            NEW.modalidad, NEW.fecha_hora_inicio, NEW.fecha_hora_fin,
            NEW.observaciones, 'modificar', current_setting('app.current_user_id')::bigint
        );
        RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
        INSERT INTO cita_historico (
            id_cita, id_paciente, id_medico, id_horario, id_especialidad,
            id_usuario_registrador, estado, modalidad, fecha_hora_inicio,
            fecha_hora_fin, observaciones, accion, usuario_accion
        ) VALUES (
            OLD.id_cita, OLD.id_paciente, OLD.id_medico, OLD.id_horario,
            OLD.id_especialidad, OLD.id_usuario_registrador, OLD.estado,
            OLD.modalidad, OLD.fecha_hora_inicio, OLD.fecha_hora_fin,
            OLD.observaciones, 'eliminar', current_setting('app.current_user_id')::bigint
        );
        RETURN OLD;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_cita_historico
AFTER INSERT OR UPDATE OR DELETE ON cita
FOR EACH ROW EXECUTE FUNCTION fn_auditar_cita_historico();
```

#### 5.2.3 Histórico para `paciente`, `medico`, `horario` (similar)

```sql
-- Paciente histórico
CREATE TABLE paciente_historico (
    id_historico    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_paciente     BIGINT NOT NULL,
    nombre          TEXT NOT NULL,
    apellidos       TEXT NOT NULL,
    documento_identidad TEXT NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    telefono        TEXT,
    email           TEXT,
    activo          BOOLEAN NOT NULL,
    accion          audit_accion_enum NOT NULL,
    usuario_accion  BIGINT NOT NULL,
    fecha_accion    TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
) PARTITION BY RANGE (fecha_accion);

-- Médico histórico
CREATE TABLE medico_historico (
    id_historico       BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_medico          BIGINT NOT NULL,
    nombre_completo    TEXT NOT NULL,
    numero_colegiado   TEXT NOT NULL,
    activo             BOOLEAN NOT NULL,
    accion             audit_accion_enum NOT NULL,
    usuario_accion     BIGINT NOT NULL,
    fecha_accion       TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
) PARTITION BY RANGE (fecha_accion);

-- Horario histórico
CREATE TABLE horario_historico (
    id_historico    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_horario      BIGINT NOT NULL,
    id_medico       BIGINT NOT NULL,
    dia_semana      SMALLINT,
    fecha_especifica DATE,
    hora_inicio     TIME NOT NULL,
    hora_fin        TIME NOT NULL,
    estado          estado_horario_enum NOT NULL,
    accion          audit_accion_enum NOT NULL,
    usuario_accion  BIGINT NOT NULL,
    fecha_accion    TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
) PARTITION BY RANGE (fecha_accion);
```

---

## 6. Estrategia de PITR (Point-in-Time Recovery)

### 6.1 Configuración WAL Archiving

```conf
# postgresql.conf
wal_level = 'logical'          -- necesario para logical replication + PITR
archive_mode = 'on'
archive_command = 'pgbackrest --stanza=hospital archive-push %p'
archive_timeout = '60s'        -- forzar segmento cada 60s aunque no haya actividad
max_wal_senders = 3
wal_keep_segments = 64         -- retención mínima en servidor primario
wal_sender_timeout = '60s'
```

### 6.2 pgBackRest para PITR

```bash
# Configuración repo (repo1)
[repo1]
  repo1-path = /var/lib/pgbackrest/repo1
  repo1-retention-full = 7
  repo1-retention-diff = 30
  repo1-retention-archive = 30  # WALs necesarios para PITR hasta 30 días atrás

# Backup full semanal
0 1 * * 0 pgbackrest --stanza=hospital --type=full backup

# Backup incremental diario
0 2 * * 1-6 pgbackrest --stanza=hospital --type=incr backup

# PITR a punto específico
pgbackrest --stanza=hospital --type=time --target='2026-09-27 14:30:00' restore

# PITR a LSN específico (para recuperación precisa)
pgbackrest --stanza=hospital --type=lsn --target='0/1A2B3C4D' restore
```

### 6.3 Objetivos de recuperación (RPO/RTO)

| Métrica | Objetivo | Implementación |
|---------|----------|----------------|
| **RPO (Recovery Point Objective)** | < 1 minuto | `archive_timeout = '60s'` + WAL streaming |
| **RTO (Recovery Time Objective)** | < 4 horas | pgBackRest + standby hot / warm |
| **Retención PITR** | 30 días | `repo1-retention-archive = 30` |
| **Retención completa** | ≥ 5 años | Backup full semanal + archive off-site |

---

## 7. Consultas Históricas Comunes

### 7.1 Historial completo de una cita

```sql
SELECT
    ch.fecha_accion,
    ch.accion,
    u.username AS usuario,
    ch.estado,
    ch.modalidad,
    ch.fecha_hora_inicio,
    ch.fecha_hora_fin
FROM cita_historico ch
JOIN usuario u ON u.id_usuario = ch.usuario_accion
WHERE ch.id_cita = 12345
ORDER BY ch.fecha_accion DESC;
```

### 7.2 Estado de una cita en fecha/hora específica (AS OF)

```sql
-- Obtener el estado de la cita en un momento dado
SELECT * FROM cita_historico
WHERE id_cita = 12345
  AND fecha_accion <= '2026-09-15 10:00:00+00'
ORDER BY fecha_accion DESC
LIMIT 1;
```

### 7.3 Auditoría de accesos no autorizados (via pgAudit)

```sql
-- Desde logs SIEM (ejemplo JSON)
SELECT
    log_time,
    pgaudit.user_id,
    pgaudit.command,
    pgaudit.table_name,
    pgaudit.session_id
FROM pgaudit_logs
WHERE pgaudit.table_name = 'cita'
  AND pgaudit.command IN ('SELECT', 'INSERT', 'UPDATE', 'DELETE')
  AND log_time >= NOW() - INTERVAL '24 hours'
  AND pgaudit.user_id NOT IN (
      SELECT id_usuario FROM usuario_rol ur
      JOIN rol r ON r.id_rol = ur.id_rol
      WHERE r.nombre IN ('medico', 'admision', 'admin')
  );
```

---

## 8. Datos Maestros y Referenciales — Versionamiento

### 8.1 Tablas de catálogo (especialidad, rol, permiso)

Las tablas de catálogo cambian raramente. Se versionan **solo cambios de estructura**, no datos operativos.

```sql
-- Log de cambios en catálogos (ya cubierto por pgAudit DDL)
-- Para cambios de datos en catálogos, usar tabla genérica:
CREATE TABLE catalogo_cambios (
    id_cambio       BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tabla_afectada  TEXT NOT NULL,
    id_registro     BIGINT NOT NULL,
    operacion       TEXT NOT NULL,  -- INSERT, UPDATE, DELETE
    datos_anteriores JSONB,
    datos_nuevos    JSONB,
    usuario         BIGINT NOT NULL,
    fecha_cambio    TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);
```

---

## 9. Cumplimiento y Trazabilidad

| Requisito | Implementación | Verificación |
|-----------|---------------|--------------|
| RN-22: Conservación ≥ 5 años | Particiones mensuales + archivado a 5a+6m | Job `fn_archivar_particiones_auditoria` |
| RN-23: Registro de cada cambio | Trigger `fn_auditar_cambio_cita` + histórico | Pruebas de inserción/actualización |
| RN-24: Valor anterior/actual | `valor_anterior`, `valor_actual` en auditoría | Verificar JSON/text capture |
| RN-25: FK auditoría RESTRICT | FK ON DELETE RESTRICT en `registro_auditoria` | Intentar borrar cita con auditoría |
| RN-26: Configuración centralizada | `parametros_configuracion` + validación | Tests de parámetros críticos |
| RNF-09: RPO ≤ 1 min | WAL archiving + `archive_timeout` | Simulación fallo + recover |
| RNF-09: RTO ≤ 4 h | Standby + pgBackRest | Drill de recuperación trimestral |

---

## 10. Próximo Paso

**Paso 11 — Índices y rendimiento**
Usar: `postgresql-table-design`
Salida: `proyecto/base_datos/10_indices_rendimiento/indices_rendimiento.md`

DETENERSE y esperar aprobación humana.