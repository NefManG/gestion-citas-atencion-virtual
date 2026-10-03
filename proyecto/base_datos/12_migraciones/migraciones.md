# Informe de Migraciones — Paso 13

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español
**Generado:** Paso 13 — Migraciones
**Agente utilizado:** `database-engineer` (skill `databases` + `postgresql-table-design`)
**Workflow:** `02_database_workflow`
**DBMS:** PostgreSQL 18.6
**Fuentes principales:** `modelo_fisico.md`, `06_integridad/integridad.md`, `10_indices_rendimiento/indices_rendimiento.md`, `11_transacciones_concurrencia/transacciones_concurrencia.md`
**Estado:** Pendiente de validación humana

---

## 1. Objetivo del Paso 13

Definir la **estrategia completa de migración de base de datos**, cubriendo:

- Herramienta de migración y versión
- Estructura del repositorio de migraciones
- Convenciones de nombrado y versionado
- Estrategia de despliegue (expand-contract, blue-green, rolling)
- Migración de datos (seed, referenciales, históricos)
- Rollback y procedimientos de emergencia
- Automatización CI/CD
- Validación y testing de migraciones

**Salida:** `proyecto/base_datos/12_migraciones/migraciones.md`

DETENERSE y esperar aprobación humana.

---

## 2. Herramienta de Migración Seleccionada

### 2.1 Flyway (Community Edition 10.x) — **Seleccionado**

| Criterio | Flyway | Liquibase | pg_migrate / golang-migrate |
|----------|--------|-----------|----------------------------|
| **SQL nativo** | ✓ (archivos .sql) | XML/YAML/JSON + SQL | Solo SQL |
| **Checksums** | ✓ (SHA-256) | ✓ | ✓ |
| **Rollback** | Scripts UNDO manuales | Automático (con XML) | Scripts manuales |
| **Callbacks** | ✓ (before/after) | ✓ | Limitado |
| **CI/CD integración** | Maven/Gradle/GitHub Actions | Maven/Gradle/GitHub | GitHub Actions |
| **PostgreSQL features** | Excelente (extensiones, particiones) | Muy bueno | Bueno |
| **Equipo experto** | Sí (Java/Spring) | Sí | Variable |
| **Costo** | Gratis (Community) | Gratis (Community) | Gratis |

**Decisión:** **Flyway Community Edition 10.x** por:
- Uso nativo de SQL (equipo conoce PostgreSQL)
- Checksums automáticos de integridad
- Callbacks para validaciones pre/post
- Integración nativa con GitHub Actions
- Compatibilidad total con particiones, RLS, triggers, ENUMs

---

## 3. Estructura del Repositorio de Migraciones

### 3.1 Layout en Control de Versiones

```
proyecto/
├── base_datos/
│   └── migraciones/
│       ├── flyway.conf                 # Configuración principal
│       ├── conf/
│       │   ├── flyway-dev.conf         # Desarrollo
│       │   ├── flyway-staging.conf     # Staging
│       │   └── flyway-prod.conf        # Producción
│       ├── sql/
│       │   ├── V1__initial_schema.sql  # Esquema completo (baseline)
│       │   ├── V2__add_rls_policies.sql
│       │   ├── V3__add_pgaudit.sql
│       │   ├── V4__partition_auditoria.sql
│       │   ├── V5__add_triggers.sql
│       │   ├── V6__seed_reference_data.sql
│       │   ├── V7__add_missing_indexes.sql
│       │   ├── V8__add_check_constraints.sql
│       │   ├── V9__add_advisory_functions.sql
│       │   └── V10__optimize_autovacuum.sql
│       ├── callbacks/
│       │   ├── beforeMigrate.sql       # Validaciones previas
│       │   ├── afterMigrate.sql        # Verificaciones post-migración
│       │   └── beforeEachMigrate.sql   # Logging
│       ├── undo/
│       │   ├── U10__rollback_optimize.sql
│       │   └── U9__rollback_advisory.sql
│       └── scripts/
│           ├── validate_schema.sql
│           └── generate_diff.sh
```

### 3.2 Configuración Principal (`flyway.conf`)

```properties
# flyway.conf
flyway.url=jdbc:postgresql://${DB_HOST}:5432/${DB_NAME}
flyway.user=${DB_USER}
flyway.password=${DB_PASSWORD}
flyway.schemas=public
flyway.table=flyway_schema_history
flyway.baselineOnMigrate=true
flyway.baselineVersion=0
flyway.baselineDescription=<< Flyway Baseline >>
flyway.validateOnMigrate=true
flyway.cleanDisabled=true
flyway.outOfOrder=false
flyway.ignoreMissingMigrations=false
flyway.failOnMissingLocations=true
flyway.placeholderReplacement=true
flyway.placeholders.app_schema=public
flyway.callbacks=beforeMigrate,afterMigrate,beforeEachMigrate
flyway.sqlMigrationPrefix=V
flyway.sqlMigrationSeparator=__
flyway.sqlMigrationSuffixes=.sql
flyway.undoSqlMigrationPrefix=U
flyway.encoding=UTF-8
```

### 3.3 Configuración por Ambiente

```properties
# conf/flyway-dev.conf (incluye flyway.conf)
flyway.url=jdbc:postgresql://localhost:5432/hospital_citas_dev
flyway.user=app_dev
flyway.password=${DEV_DB_PASSWORD}
flyway.locations=filesystem:sql,filesystem:callbacks

# conf/flyway-staging.conf
flyway.url=jdbc:postgresql://staging-db:5432/hospital_citas_staging
flyway.user=app_staging
flyway.password=${STAGING_DB_PASSWORD}
flyway.locations=filesystem:sql,filesystem:callbacks

# conf/flyway-prod.conf
flyway.url=jdbc:postgresql://prod-db:5432/hospital_citas
flyway.user=app_prod
flyway.password=${PROD_DB_PASSWORD}
flyway.locations=filesystem:sql,filesystem:callbacks
flyway.cleanDisabled=true
```

---

## 4. Estrategia de Versionado y Nombrado

### 4.1 Convención Semántica

```
V{MAJOR}{MINOR}{PATCH}__{descripcion_breve}.sql
```

| Ejemplo | Significado |
|---------|-------------|
| `V1__initial_schema.sql` | Baseline: esquema completo |
| `V2__add_rls_policies.sql` | Feature: RLS |
| `V3__add_pgaudit.sql` | Feature: pgAudit |
| `V4__partition_auditoria.sql` | Feature: particionamiento |
| `V5__add_triggers.sql` | Feature: triggers integridad |
| `V6__seed_reference_data.sql` | Data: semillas |
| `V7__add_missing_indexes.sql` | Perf: índices |
| `V8__add_check_constraints.sql` | Fix: CHECKs |
| `V9__add_advisory_functions.sql` | Feature: advisory locks |
| `V10__optimize_autovacuum.sql` | Perf: autovacuum |

### 4.2 Reglas de Versionado

| Regla | Descripción |
|-------|-------------|
| **Monótono creciente** | Números siempre ascendentes (no gaps permitidos en prod) |
| **Inmutabilidad** | Una vez aplicada en prod, NUNCA modificar el archivo |
| **Undo solo para hotfix** | Scripts `U{version}` solo para rollback de emergencia |
| **Descripción corta** | ≤ 50 chars, kebab-case, sin espacios |
| **Un cambio por archivo** | Un archivo = una unidad atómica de cambio |

---

## 5. Migraciones Iniciales (Baseline y Primeras Versiones)

### 5.1 V1__initial_schema.sql — Esquema Completo (Baseline)

```sql
-- ============================================================
-- V1__initial_schema.sql — Esquema físico completo
-- Generado desde: modelo_fisico.md
-- ============================================================

-- 1. Extensiones requeridas
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "btree_gist";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- 2. ENUMs (tipos de estado)
CREATE TYPE estado_cita_enum AS ENUM (
    'Programada', 'Confirmada', 'En_atencion', 'Finalizada',
    'Cancelada', 'No_asistida'
);
CREATE TYPE modalidad_cita_enum AS ENUM ('presencial', 'virtual');
CREATE TYPE estado_horario_enum AS ENUM ('disponible', 'reservado', 'ocupado');
CREATE TYPE audit_accion_enum AS ENUM (
    'crear', 'modificar', 'cancelar', 'finalizar',
    'reprogramar', 'cambiar_modalidad', 'cambiar_estado',
    'asignar_medico', 'liberar_horario', 'bloquear_usuario',
    'desbloquear_usuario'
);

-- 3. Tablas principales (14 tablas)
-- [Copiar DDL completo desde modelo_fisico.md - todas las tablas
-- con PK, FK, UNIQUE, CHECK, NOT NULL, DEFAULT, PARTITION BY]

-- 4. Índices obligatorios (FK + UNIQUE + PK)
-- [Copiar desde indices_rendimiento.md - solo índices obligatorios]

-- 5. Funciones y triggers de integridad
-- [Copiar desde integridad.md - §5]

-- 6. Políticas RLS
-- [Copiar desde seguridad.md - §4]

-- 7. Datos semilla mínimos (roles, permisos, especialidades base)
INSERT INTO rol (nombre, descripcion) VALUES
    ('paciente', 'Usuario paciente del sistema'),
    ('medico', 'Profesional médico con consulta'),
    ('admision', 'Personal de admisión y registro'),
    ('admin', 'Administrador del sistema hospitalario');

INSERT INTO permiso (nombre, descripcion, recurso, accion) VALUES
    ('crear_cita', 'Crear nueva cita', 'cita', 'crear'),
    ('leer_cita', 'Ver citas', 'cita', 'leer'),
    ('actualizar_cita', 'Modificar cita', 'cita', 'actualizar'),
    ('cancelar_cita', 'Cancelar cita', 'cita', 'eliminar'),
    ('ver_atencion_virtual', 'Ver información virtual', 'atencion_virtual', 'leer'),
    ('gestionar_parametros', 'Configurar parámetros', 'parametros_configuracion', 'actualizar'),
    ('auditoria_lectura', 'Leer logs de auditoría', 'registro_auditoria', 'leer');

INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM rol r, permiso p
WHERE (r.nombre = 'paciente' AND p.nombre = 'leer_cita')
   OR (r.nombre = 'medico' AND p.nombre IN ('crear_cita','leer_cita','actualizar_cita','cancelar_cita','ver_atencion_virtual'))
   OR (r.nombre = 'admision' AND p.nombre IN ('crear_cita','leer_cita','actualizar_cita'))
   OR (r.nombre = 'admin' AND p.nombre IN ('gestionar_parametros','auditoria_lectura'));

-- 8. Particiones iniciales de auditoría
SELECT fn_crear_particion_auditoria(date_trunc('month', CURRENT_DATE)::date, 13);
```

### 5.2 V2__add_rls_policies.sql

```sql
-- Políticas RLS detalladas (ya en V1, pero separadas para rollback granular)
-- Ver seguridad.md §4
```

### 5.3 V3__add_pgaudit.sql

```sql
-- Extensión pgAudit y configuración
CREATE EXTENSION IF NOT EXISTS pgaudit WITH SCHEMA pg_catalog;

ALTER SYSTEM SET pgaudit.log = 'ddl, misrole, session, unsigned';
ALTER SYSTEM SET pgaudit.log_catalog = 'on';
ALTER SYSTEM SET pgaudit.log_parameter_never = 'password';
ALTER SYSTEM SET pgaudit.log_parameter_always = 'on';

SELECT pg_reload_conf();
```

### 5.4 V4__partition_auditoria.sql

```sql
-- Función y particiones para registro_auditoria
-- Ver auditoria_historico.md §4.1
CREATE OR REPLACE FUNCTION fn_crear_particion_auditoria(...);
SELECT fn_crear_particion_auditoria(date_trunc('month', CURRENT_DATE)::date, 13);

-- Job pg_cron para archivado
SELECT cron.schedule('archivar-auditoria-mensual', '0 2 1 * *',
    'SELECT * FROM fn_archivar_particiones_auditoria()');
```

### 5.5 V5__add_triggers.sql

```sql
-- Triggers de integridad (ver integridad.md §5)
CREATE OR REPLACE FUNCTION fn_validar_cita_medico_especialidad();
CREATE TRIGGER trg_cita_validar_medico_especialidad
    BEFORE INSERT OR UPDATE OF id_medico, id_especialidad ON cita
    FOR EACH ROW EXECUTE FUNCTION fn_validar_cita_medico_especialidad();

-- ... resto de triggers
```

### 5.6 V6__seed_reference_data.sql

```sql
-- Datos de referencia: especialidades, parámetros configuración
INSERT INTO especialidad (nombre, descripcion, activo) VALUES
    ('Cardiología', 'Especialidad cardiovascular', true),
    ('Neurología', 'Sistema nervioso', true),
    ('Pediatría', 'Atención infantil', true),
    ('Ginecología', 'Salud reproductiva femenina', true),
    ('Traumatología', 'Lesiones musculoesqueléticas', true),
    ('Medicina General', 'Atención primaria', true);

INSERT INTO parametros_configuracion (clave, valor, tipo_dato, descripcion, categoria, editable) VALUES
    ('tiempo_inactividad_sesion_minutos', '30', 'integer', 'Minutos de inactividad para cerrar sesión', 'seguridad', true),
    ('max_intentos_fallidos_login', '5', 'integer', 'Intentos fallidos antes de bloquear cuenta', 'seguridad', true),
    ('tiempo_bloqueo_cuenta_minutos', '30', 'integer', 'Tiempo de bloqueo tras intentos fallidos', 'seguridad', true),
    ('duracion_slot_cita_minutos', '30', 'integer', 'Duración estándar de slot de cita', 'citas', true),
    ('ventana_reprogramacion_horas', '2', 'integer', 'Horas mínimo para reprogramar', 'citas', true);
```

---

## 6. Estrategia de Despliegue

### 6.1 Patrón Expand-Contract (Zero-Downtime)

```
FASE 1: EXPAND (additive only)
├── V(n+1): Add new column (nullable, default)
├── V(n+2): Add new index (CONCURRENTLY)
├── V(n+3): Add new table
└── Deploy app v2 (reads old + new, writes both)

FASE 2: MIGRATE DATA (backfill)
├── V(n+4): Backfill data (batch, low priority)
├── V(n+5): Add NOT NULL constraint (if needed)
└── Deploy app v3 (uses new column)

FASE 3: CONTRACT (remove old)
├── V(n+6): Drop old column/index
├── V(n+7): Drop old table
└── Deploy app v4 (clean)
```

### 6.2 Ejemplo: Agregar columna `fecha_ultima_modificacion` a `cita`

```sql
-- V11__add_fecha_ultima_modificacion.sql (EXPAND)
ALTER TABLE cita ADD COLUMN fecha_ultima_modificacion TIMESTAMPTZ;
COMMENT ON COLUMN cita.fecha_ultima_modificacion IS 'Timestamp de última modificación (app-managed)';

-- V12__backfill_fecha_ultima_modificacion.sql (MIGRATE)
UPDATE cita SET fecha_ultima_modificacion = fecha_actualizacion
WHERE fecha_ultima_modificacion IS NULL;

-- V13__add_fecha_ultima_modificacion_not_null.sql (CONTRACT)
ALTER TABLE cita ALTER COLUMN fecha_ultima_modificacion SET NOT NULL;
```

---

## 7. Migración de Datos (Seed, Históricos, Referenciales)

### 7.1 Datos Semilla (Seed Data)

| Tabla | Estrategia | Cuándo |
|-------|------------|--------|
| `rol` | INSERT ON CONFLICT DO NOTHING | V1 (baseline) |
| `permiso` | INSERT ON CONFLICT DO NOTHING | V1 |
| `especialidad` | INSERT ON CONFLICT (nombre) DO UPDATE | V6 (data) |
| `parametros_configuracion` | UPSERT con claves | V6 |

```sql
-- Patrón seguro para seed data
INSERT INTO especialidad (nombre, descripcion, activo)
VALUES ('Cardiología', 'Especialidad cardiovascular', true)
ON CONFLICT (nombre) DO UPDATE SET
    descripcion = EXCLUDED.descripcion,
    activo = EXCLUDED.activo;
```

### 7.2 Migración de Datos Históricos (si aplica)

```sql
-- V20__migrate_legacy_citas.sql
-- Solo si hay sistema previo a migrar

BEGIN;
-- 1. Crear tabla temporal de staging
CREATE TEMP TABLE staging_citas (
    -- columnas del sistema legacy
    legacy_id BIGINT,
    paciente_dni VARCHAR(20),
    medico_colegiado VARCHAR(50),
    fecha_hora TIMESTAMPTZ,
    estado VARCHAR(30),
    ...
) ON COMMIT DROP;

-- 2. Cargar datos (COPY desde CSV/parquet)
COPY staging_citas FROM '/data/legacy_citas.csv' WITH (FORMAT csv, HEADER);

-- 3. Transformar y cargar
INSERT INTO cita (id_paciente, id_medico, id_horario, id_especialidad,
                  id_usuario_registrador, estado, modalidad,
                  fecha_hora_inicio, fecha_creacion, fecha_actualizacion)
SELECT
    p.id_paciente,
    m.id_medico,
    h.id_horario,
    e.id_especialidad,
    u.id_usuario,
    CASE s.estado
        WHEN 'PENDIENTE' THEN 'Programada'
        WHEN 'CONFIRMADA' THEN 'Confirmada'
        WHEN 'EN_CURSO' THEN 'En_atencion'
        WHEN 'FINALIZADA' THEN 'Finalizada'
        WHEN 'CANCELADA' THEN 'Cancelada'
        WHEN 'NO_ASISTIO' THEN 'No_asistida'
        ELSE 'Programada'
    END::estado_cita_enum,
    'presencial'::modalidad_cita_enum,
    s.fecha_hora,
    s.fecha_creacion,
    s.fecha_actualizacion
FROM staging_citas s
JOIN paciente p ON p.documento_identidad = s.paciente_dni
JOIN medico m ON m.numero_colegiado = s.medico_colegiado
JOIN especialidad e ON e.nombre = s.especialidad_nombre
JOIN usuario u ON u.username = 'migration_bot'
LEFT JOIN horario h ON h.id_medico = m.id_medico
    AND h.hora_inicio <= s.fecha_hora::time
    AND h.hora_fin > s.fecha_hora::time
    AND h.estado = 'disponible'
ON CONFLICT DO NOTHING;

-- 4. Registrar migración en auditoría
INSERT INTO registro_auditoria (id_cita, id_usuario_responsable, accion, campo_modificado, valor_anterior, valor_actual, fecha_hora)
SELECT c.id_cita, u.id_usuario, 'crear', NULL, NULL, 'Migrado desde sistema legacy', now()
FROM cita c
JOIN usuario u ON u.username = 'migration_bot'
WHERE c.fecha_creacion >= '2020-01-01';

COMMIT;
```

---

## 8. Rollback y Procedimientos de Emergencia

### 8.1 Scripts UNDO (Solo Hotfix Críticos)

```sql
-- U10__rollback_optimize_autovacuum.sql
-- Revertir V10 si causa problemas de rendimiento

ALTER TABLE cita RESET (
    autovacuum_vacuum_scale_factor,
    autovacuum_analyze_scale_factor,
    autovacuum_vacuum_threshold,
    autovacuum_analyze_threshold,
    autovacuum_vacuum_cost_delay,
    autovacuum_vacuum_cost_limit
);

-- Verificar
SELECT relname, reloptions FROM pg_class WHERE relname = 'cita';
```

### 8.2 Procedimiento de Rollback Manual

```bash
# 1. Identificar versión a revertir
flyway -configFile=conf/flyway-prod.conf info

# 2. Ejecutar script UNDO manual (si existe)
psql -h $DB_HOST -U $DB_USER -d $DB_NAME -f sql/undo/U10__rollback_optimize.sql

# 3. Marcar migración como fallida en Flyway (requiere Flyway Teams)
# O: reparar checksum manualmente
psql -c "UPDATE flyway_schema_history SET checksum = NULL, success = false WHERE version = '10';"

# 4. Re-ejecutar migración correcta
flyway -configFile=conf/flyway-prod.conf migrate
```

### 8.3 Point-in-Time Recovery (PITR) como Último Recurso

```bash
# Si migración catastrófica corrompe datos
# 1. Detener aplicación
kubectl scale deployment/app --replicas=0

# 2. Restaurar desde backup pgBackRest (ver auditoria_historico.md §6)
pgbackrest --stanza=hospital --type=time \
    --target='2026-10-02 14:29:00' restore

# 3. Verificar integridad
psql -c "SELECT count(*) FROM cita;"

# 4. Re-aplicar migraciones Flyway (si backup anterior a baseline)
flyway -configFile=conf/flyway-prod.conf migrate

# 5. Reiniciar aplicación
kubectl scale deployment/app --replicas=3
```

---

## 9. Validación y Testing de Migraciones

### 9.1 Callbacks de Flyway

```sql
-- callbacks/beforeMigrate.sql
-- Validaciones previas a cualquier migración

-- Verificar versión PG
SELECT version();

-- Verificar extensiones requeridas
SELECT extname FROM pg_extension WHERE extname IN ('uuid-ossp','pgcrypto','btree_gist','pg_trgm');

-- Verificar espacio en disco
SELECT pg_size_pretty(pg_database_size(current_database())) AS db_size;

-- Verificar que no hay migraciones pendientes con checksums incorrectos
SELECT version, checksum, success
FROM flyway_schema_history
WHERE success = false;

-- Verificar conexiones activas (no migrar con app corriendo)
SELECT count(*) FROM pg_stat_activity
WHERE datname = current_database() AND pid != pg_backend_pid();
```

```sql
-- callbacks/afterMigrate.sql
-- Verificaciones post-migración

-- Contar objetos
SELECT 'Tablas' AS tipo, count(*) FROM information_schema.tables WHERE table_schema = 'public'
UNION ALL SELECT 'Índices', count(*) FROM pg_indexes WHERE schemaname = 'public'
UNION ALL SELECT 'Funciones', count(*) FROM pg_proc p JOIN pg_namespace n ON n.oid = p.pronamespace WHERE n.nspname = 'public'
UNION ALL SELECT 'Triggers', count(*) FROM information_schema.triggers WHERE trigger_schema = 'public'
UNION ALL SELECT 'Políticas RLS', count(*) FROM pg_policies WHERE schemaname = 'public';

-- Verificar integridad referencial
SELECT conname, conrelid::regclass, confrelid::regclass
FROM pg_constraint
WHERE contype = 'f' AND convalidated = false;

-- Verificar particiones
SELECT schemaname, tablename, pg_size_pretty(pg_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE tablename LIKE 'registro_auditoria_%'
ORDER BY tablename;

-- Test rápido de constraints
INSERT INTO cita (...) VALUES (...) ON CONFLICT DO NOTHING;
DELETE FROM cita WHERE id_cita = (SELECT max(id_cita) FROM cita);
```

### 9.2 Tests de Migración en CI/CD

```yaml
# .github/workflows/flyway-test.yml
name: Flyway Migration Test

on:
  push:
    paths:
      - 'proyecto/base_datos/migraciones/**'

jobs:
  test-migrations:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:18.6
        env:
          POSTGRES_DB: test_db
          POSTGRES_USER: test
          POSTGRES_PASSWORD: test
        ports: ['5432:5432']
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
      - uses: actions/checkout@v4

      - name: Setup Flyway
        uses: flyway/flyway-action@v1
        with:
          version: '10.15.0'

      - name: Run Migrations (Dev)
        run: |
          flyway -configFiles=proyecto/base_datos/migraciones/conf/flyway-dev.conf \
            -placeholders.app_schema=public \
            migrate

      - name: Validate Schema
        run: |
          psql -h localhost -U test -d test_db \
            -f proyecto/base_datos/migraciones/scripts/validate_schema.sql

      - name: Run Integration Tests
        run: |
          # Ejecutar tests de la app contra BD migrada
          npm run test:integration

      - name: Test Rollback (Undo)
        if: always()
        run: |
          # Solo para validar que scripts UNDO existen
          ls -la proyecto/base_datos/migraciones/sql/undo/
```

### 9.3 Script de Validación de Esquema

```sql
-- scripts/validate_schema.sql
-- Comparar esquema aplicado vs modelo_fisico.md

-- 1. Verificar tablas esperadas
SELECT table_name FROM information_schema.tables
WHERE table_schema = 'public'
ORDER BY table_name;

-- 2. Verificar columnas por tabla
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns
WHERE table_schema = 'public' AND table_name = 'cita'
ORDER BY ordinal_position;

-- 3. Verificar constraints
SELECT constraint_name, constraint_type, table_name
FROM information_schema.table_constraints
WHERE table_schema = 'public'
ORDER BY table_name, constraint_type;

-- 4. Verificar índices
SELECT indexname, tablename, indexdef
FROM pg_indexes
WHERE schemaname = 'public'
ORDER BY tablename, indexname;

-- 5. Verificar particiones
SELECT parent.relname AS tabla, child.relname AS particion,
       pg_size_pretty(pg_relation_size(child.oid)) AS size
FROM pg_inherits
JOIN pg_class parent ON parent.oid = inhparent
JOIN pg_class child ON child.oid = inhrelid
WHERE parent.relname IN ('registro_auditoria', 'cita_historico');

-- 6. Verificar RLS
SELECT schemaname, tablename, policyname, permissive, roles, cmd, qual
FROM pg_policies
WHERE schemaname = 'public';

-- 7. Verificar funciones/triggers
SELECT routine_name, routine_type, data_type
FROM information_schema.routines
WHERE routine_schema = 'public'
ORDER BY routine_name;
```

---

## 10. Automatización CI/CD

### 10.1 Pipeline Completo

```yaml
# .github/workflows/db-deploy.yml
name: Database Deploy

on:
  workflow_dispatch:
    inputs:
      environment:
        type: choice
        options: [dev, staging, prod]
        required: true
      version:
        type: string
        description: 'Target Flyway version (optional)'

jobs:
  validate:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Validate Migration Files
        run: |
          # Verificar convención de nombres
          find proyecto/base_datos/migraciones/sql -name 'V*.sql' | sort -V
          # Verificar checksums (simulado)
          sha256sum proyecto/base_datos/migraciones/sql/V*.sql

  deploy-dev:
    needs: validate
    if: github.event.inputs.environment == 'dev'
    runs-on: ubuntu-latest
    environment: development
    steps:
      - uses: actions/checkout@v4
      - name: Deploy to Dev
        run: |
          flyway -configFiles=proyecto/base_datos/migraciones/conf/flyway-dev.conf migrate

  deploy-staging:
    needs: validate
    if: github.event.inputs.environment == 'staging'
    runs-on: ubuntu-latest
    environment: staging
    steps:
      - uses: actions/checkout@v4
      - name: Backup Staging (pgBackRest)
        run: pgbackrest --stanza=staging backup --type=incr
      - name: Deploy to Staging
        run: |
          flyway -configFiles=proyecto/base_datos/migraciones/conf/flyway-staging.conf migrate
      - name: Run Smoke Tests
        run: npm run test:smoke -- --env=staging

  deploy-prod:
    needs: validate
    if: github.event.inputs.environment == 'prod'
    runs-on: ubuntu-latest
    environment: production
    steps:
      - uses: actions/checkout@v4
      - name: Backup Production (pgBackRest Full)
        run: pgbackrest --stanza=hospital backup --type=full
      - name: Deploy to Production (Blue-Green)
        run: |
          # 1. Preparar standby (green)
          pgbackrest --stanza=hospital --type=standby create
          # 2. Migrar en standby
          flyway -configFiles=proyecto/base_datos/migraciones/conf/flyway-prod.conf \
            -url=jdbc:postgresql://standby-db:5432/hospital_citas migrate
          # 3. Validar en standby
          psql -h standby-db -f proyecto/base_datos/migraciones/scripts/validate_schema.sql
          # 4. Promover standby a primary (switchover)
          pgbackrest --stanza=hospital --type=promote promote
          # 5. Actualizar DNS / connection strings
      - name: Post-Deploy Verification
        run: npm run test:smoke -- --env=prod
```

---

## 11. Checklist de Migración a Producción

| Paso | Acción | Responsable | Verificación |
|------|--------|-------------|--------------|
| 1 | Revisar PR de migraciones | DBA + Dev Lead | Code review aprobado |
| 2 | Ejecutar en Dev | Dev | `flyway migrate` OK, tests pasan |
| 3 | Ejecutar en Staging | DevOps | Backup + migrate + smoke tests |
| 4 | Aprobar ventana de mantenimiento | Gerencia TI | Ventana confirmada |
| 5 | Backup full producción | DBA | pgBackRest full completado |
| 6 | Notificar stakeholders | PM | Email/Slack enviado |
| 7 | Ejecutar migración prod (blue-green) | DBA | Flyway success, validate_schema OK |
| 8 | Verificar aplicación | QA | Smoke tests + 1 transacción real |
| 9 | Monitorear métricas 30 min | DBA + DevOps | CPU, conexiones, locks, slow queries |
| 10 | Cerrar incidencia / documentar | PM | Release notes actualizadas |

---

## 12. Próximo Paso

**Paso 14 — Revisión DBA**
Usar: `security-reviewer` + `database-specialist` + `architect`
Salida: `proyecto/base_datos/13_revision_dba/revision_dba.md`

**Estado requerido para Paso 15:** `STATUS: APPROVED` en revisión DBA.

DETENERSE y esperar aprobación humana.