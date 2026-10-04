# Informe de Migraciones — Paso 13

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español

**Generado:** Paso 13 — Migraciones

**Agente utilizado:** `database-engineer` (skill `databases` + `postgresql-table-design`)

**Workflow:** `02_database_workflow`

**DBMS:** PostgreSQL 18.6

**Fuentes principales:** `modelo_fisico.md`, `06_integridad/integridad.md`, `10_indices_rendimiento/indices_rendimiento.md`, `11_transacciones_concurrencia/transacciones_concurrencia.md`

**Estado:** Corregido manualmente después del Paso 14 — Revisión DBA. Pendiente de nueva validación humana

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

```text
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
│       │   ├── beforeMigrate.sql
│       │   ├── afterMigrate.sql
│       │   └── beforeEachMigrate.sql
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
# conf/flyway-dev.conf

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

```text
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
| **Monótono creciente** | Números siempre ascendentes |
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

CREATE EXTENSION IF NOT EXISTS "pgcrypto";

CREATE EXTENSION IF NOT EXISTS "btree_gist";

CREATE EXTENSION IF NOT EXISTS "pg_trgm";


-- 2. ENUMs

CREATE TYPE estado_cita AS ENUM (
    'Programada',
    'Confirmada',
    'En_atencion',
    'Finalizada',
    'Cancelada',
    'No_asistida'
);

CREATE TYPE modalidad_cita AS ENUM (
    'presencial',
    'virtual'
);

CREATE TYPE estado_horario AS ENUM (
    'disponible',
    'reservado',
    'ocupado'
);

CREATE TYPE accion_auditoria AS ENUM (
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


-- 3. Tablas principales (14 tablas)

-- [Copiar DDL completo desde modelo_fisico.md corregido
-- con PK, FK, UNIQUE, CHECK, NOT NULL y DEFAULT]


-- CORRECCIÓN OBLIGATORIA DE NORMALIZACIÓN:
--
-- La tabla cita NO debe contener id_medico.
--
-- El médico se obtiene mediante:
--
-- cita.id_horario -> horario.id_medico
--
-- No crear:
--
-- UNIQUE (id_medico, id_horario)
--
-- ni:
--
-- UNIQUE (id_horario)
--
-- de manera absoluta.


-- 4. Índices obligatorios

-- [Copiar desde indices_rendimiento.md corregido]


-- La protección contra doble reserva se implementa
-- mediante índice UNIQUE parcial:

-- (id_horario, fecha_hora_programada)
-- para estados activos.


-- 5. Funciones y triggers de integridad

-- [Copiar desde integridad.md corregido]


-- 6. Políticas RLS

-- [Copiar desde seguridad.md corregido]


-- 7. Datos semilla mínimos

INSERT INTO rol (
    nombre,
    descripcion
)
VALUES
    ('paciente', 'Usuario paciente del sistema'),
    ('medico', 'Profesional médico con consulta'),
    ('admision', 'Personal de admisión y registro'),
    ('admin', 'Administrador del sistema hospitalario');


INSERT INTO permiso (
    nombre,
    descripcion,
    recurso,
    accion
)
VALUES
    ('crear_cita',
     'Crear nueva cita',
     'cita',
     'crear'),

    ('leer_cita',
     'Ver citas',
     'cita',
     'leer'),

    ('actualizar_cita',
     'Modificar cita',
     'cita',
     'actualizar'),

    ('cancelar_cita',
     'Cancelar cita',
     'cita',
     'eliminar'),

    ('ver_atencion_virtual',
     'Ver información virtual',
     'atencion_virtual',
     'leer'),

    ('gestionar_parametros',
     'Configurar parámetros',
     'parametros_configuracion',
     'actualizar'),

    ('auditoria_lectura',
     'Leer logs de auditoría',
     'registro_auditoria',
     'leer');


INSERT INTO rol_permiso (
    id_rol,
    id_permiso
)
SELECT
    r.id_rol,
    p.id_permiso
FROM rol r, permiso p
WHERE
       (r.nombre = 'paciente'
        AND p.nombre = 'leer_cita')

    OR (r.nombre = 'medico'
        AND p.nombre IN (
            'crear_cita',
            'leer_cita',
            'actualizar_cita',
            'cancelar_cita',
            'ver_atencion_virtual'
        ))

    OR (r.nombre = 'admision'
        AND p.nombre IN (
            'crear_cita',
            'leer_cita',
            'actualizar_cita'
        ))

    OR (r.nombre = 'admin'
        AND p.nombre IN (
            'gestionar_parametros',
            'auditoria_lectura'
        ));


-- 8. Particiones iniciales de auditoría

SELECT fn_crear_particion_auditoria(
    date_trunc('month', CURRENT_DATE)::date,
    13
);
```

### 5.2 V2__add_rls_policies.sql

```sql
-- Políticas RLS detalladas.
-- Ver seguridad.md corregido.
```

### 5.3 V3__add_pgaudit.sql

```sql
-- Extensión pgAudit y configuración

CREATE EXTENSION IF NOT EXISTS pgaudit
WITH SCHEMA pg_catalog;


-- Requiere:
-- shared_preload_libraries = 'pgaudit'
-- configurado a nivel de servidor.

ALTER SYSTEM SET pgaudit.log =
    'write,ddl,role';

ALTER SYSTEM SET pgaudit.log_catalog =
    'off';

ALTER SYSTEM SET pgaudit.log_parameter =
    'off';


SELECT pg_reload_conf();
```

### 5.4 V4__partition_auditoria.sql

```sql
-- Función y particiones para registro_auditoria

-- Ver auditoria_historico.md §4.1

CREATE OR REPLACE FUNCTION
fn_crear_particion_auditoria(...);


SELECT fn_crear_particion_auditoria(
    date_trunc('month', CURRENT_DATE)::date,
    13
);


-- Job pg_cron para archivado

SELECT cron.schedule(
    'archivar-auditoria-mensual',
    '0 2 1 * *',
    'SELECT * FROM fn_archivar_particiones_auditoria()'
);
```

### 5.5 V5__add_triggers.sql

```sql
-- Triggers de integridad.
-- Ver integridad.md corregido.


CREATE OR REPLACE FUNCTION
fn_validar_cita_medico_especialidad();


CREATE TRIGGER trg_cita_validar_medico_especialidad

    BEFORE INSERT
    OR UPDATE OF id_horario, id_especialidad

    ON cita

    FOR EACH ROW

    EXECUTE FUNCTION
    fn_validar_cita_medico_especialidad();


-- ... resto de triggers
```

La corrección importante es que el trigger **ya no depende de `cita.id_medico`**.

El médico debe obtenerse desde:

```text
cita.id_horario
        ↓
horario.id_medico
```

---

### 5.6 V6__seed_reference_data.sql

```sql
-- Datos de referencia:
-- especialidades y parámetros aprobados.

INSERT INTO especialidad (
    nombre,
    descripcion,
    activo
)
VALUES
    ('Cardiología',
     'Especialidad cardiovascular',
     true),

    ('Neurología',
     'Sistema nervioso',
     true),

    ('Pediatría',
     'Atención infantil',
     true),

    ('Ginecología',
     'Salud reproductiva femenina',
     true),

    ('Traumatología',
     'Lesiones musculoesqueléticas',
     true),

    ('Medicina General',
     'Atención primaria',
     true);


INSERT INTO parametros_configuracion (
    clave,
    valor,
    tipo_dato,
    descripcion,
    categoria,
    editable
)
VALUES (
    'tiempo_inactividad_sesion_minutos',
    '15',
    'integer',
    'Tiempo inicial configurable de inactividad de sesión',
    'seguridad',
    true
);
```

**D-02, D-03 y D-04 permanecen pendientes.**

No insertar valores arbitrarios para:

- cancelación;
- reprogramación;
- tolerancia de no asistencia;
- anticipación máxima.

Tampoco se fijan aquí:

- máximo de intentos fallidos;
- tiempo de bloqueo de cuenta;
- duración estándar del slot;

si esos valores no han sido aprobados en requisitos.

---

## 6. Estrategia de Despliegue

### 6.1 Patrón Expand-Contract (Zero-Downtime)

```text
FASE 1: EXPAND

├── V(n+1): Add new column (nullable)
├── V(n+2): Add new index
├── V(n+3): Add new table
└── Deploy app v2


FASE 2: MIGRATE DATA

├── V(n+4): Backfill data
├── V(n+5): Add NOT NULL si corresponde
└── Deploy app v3


FASE 3: CONTRACT

├── V(n+6): Drop old column/index
├── V(n+7): Drop old table
└── Deploy app v4
```

Las operaciones destructivas no deben ejecutarse automáticamente en producción.

Cualquier:

- `DROP`;
- `TRUNCATE`;
- eliminación irreversible;

requiere autorización explícita.

---

### 6.2 Ejemplo: agregar `fecha_ultima_modificacion`

```sql
-- V11__add_fecha_ultima_modificacion.sql

ALTER TABLE cita
ADD COLUMN fecha_ultima_modificacion
TIMESTAMPTZ;


COMMENT ON COLUMN cita.fecha_ultima_modificacion
IS 'Timestamp de última modificación';


-- V12__backfill_fecha_ultima_modificacion.sql

UPDATE cita
SET fecha_ultima_modificacion =
    fecha_actualizacion
WHERE fecha_ultima_modificacion IS NULL;


-- V13__add_fecha_ultima_modificacion_not_null.sql

ALTER TABLE cita
ALTER COLUMN fecha_ultima_modificacion
SET NOT NULL;
```

Este ejemplo es ilustrativo y no significa que la columna deba incorporarse al modelo actual.

---

## 7. Migración de Datos

### 7.1 Datos Semilla

| Tabla | Estrategia | Cuándo |
|-------|------------|--------|
| `rol` | INSERT ON CONFLICT DO NOTHING | V1 |
| `permiso` | INSERT ON CONFLICT DO NOTHING | V1 |
| `especialidad` | INSERT ON CONFLICT (nombre) DO UPDATE | V6 |
| `parametros_configuracion` | UPSERT con claves | V6 |

Patrón:

```sql
INSERT INTO especialidad (
    nombre,
    descripcion,
    activo
)
VALUES (
    'Cardiología',
    'Especialidad cardiovascular',
    true
)

ON CONFLICT (nombre)

DO UPDATE SET
    descripcion = EXCLUDED.descripcion,
    activo = EXCLUDED.activo;
```

---

### 7.2 Migración de Datos Históricos

Solo aplica si existe un sistema anterior.

```sql
-- V20__migrate_legacy_citas.sql

BEGIN;


-- 1. Staging temporal

CREATE TEMP TABLE staging_citas (

    legacy_id BIGINT,

    paciente_dni VARCHAR(20),

    medico_colegiado VARCHAR(50),

    especialidad_nombre VARCHAR(150),

    fecha_hora TIMESTAMPTZ,

    estado VARCHAR(30),

    fecha_creacion TIMESTAMPTZ,

    fecha_actualizacion TIMESTAMPTZ

) ON COMMIT DROP;


-- 2. Cargar datos

COPY staging_citas
FROM '/data/legacy_citas.csv'
WITH (
    FORMAT csv,
    HEADER
);


-- 3. Transformar y cargar.
--
-- IMPORTANTE:
-- cita.id_medico ya no existe.
--
-- El médico se utiliza únicamente para
-- localizar un horario perteneciente a él.

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

SELECT

    p.id_paciente,

    h.id_horario,

    e.id_especialidad,

    u.id_usuario,

    CASE s.estado

        WHEN 'PENDIENTE'
            THEN 'Programada'

        WHEN 'CONFIRMADA'
            THEN 'Confirmada'

        WHEN 'EN_CURSO'
            THEN 'En_atencion'

        WHEN 'FINALIZADA'
            THEN 'Finalizada'

        WHEN 'CANCELADA'
            THEN 'Cancelada'

        WHEN 'NO_ASISTIO'
            THEN 'No_asistida'

        ELSE 'Programada'

    END::estado_cita,

    'presencial'::modalidad_cita,

    s.fecha_hora,

    s.fecha_creacion,

    s.fecha_actualizacion

FROM staging_citas s

JOIN paciente p
  ON p.documento_identidad =
     s.paciente_dni

JOIN medico m
  ON m.numero_colegiado =
     s.medico_colegiado

JOIN especialidad e
  ON e.nombre =
     s.especialidad_nombre

JOIN medico_especialidad me
  ON me.id_medico = m.id_medico
 AND me.id_especialidad =
     e.id_especialidad

JOIN usuario u
  ON u.username = 'migration_bot'

JOIN horario h
  ON h.id_medico = m.id_medico

 AND h.hora_inicio
     <= s.fecha_hora::time

 AND h.hora_fin
     > s.fecha_hora::time;


-- 4. Registrar migración en auditoría

INSERT INTO registro_auditoria (
    id_cita,
    id_usuario_responsable,
    accion,
    campo_modificado,
    valor_anterior,
    valor_actual,
    fecha_hora
)

SELECT

    c.id_cita,

    u.id_usuario,

    'crear',

    NULL,

    NULL,

    'Migrado desde sistema legacy',

    now()

FROM cita c

JOIN usuario u
  ON u.username = 'migration_bot';


COMMIT;
```

### Corrección aplicada

Ya no existe:

```sql
INSERT INTO cita (
    id_paciente,
    id_medico,
    ...
)
```

Ahora el médico se utiliza para localizar:

```text
horario.id_medico
```

y la cita almacena solamente:

```text
id_horario
```

---

## 8. Rollback y Procedimientos de Emergencia

### 8.1 Scripts UNDO

```sql
-- U10__rollback_optimize_autovacuum.sql

ALTER TABLE cita RESET (

    autovacuum_vacuum_scale_factor,

    autovacuum_analyze_scale_factor,

    autovacuum_vacuum_threshold,

    autovacuum_analyze_threshold,

    autovacuum_vacuum_cost_delay,

    autovacuum_vacuum_cost_limit
);


SELECT
    relname,
    reloptions

FROM pg_class

WHERE relname = 'cita';
```

---

### 8.2 Procedimiento de Rollback Manual

```bash
# 1. Identificar versión

flyway \
  -configFile=conf/flyway-prod.conf \
  info


# 2. Ejecutar UNDO aprobado si existe

psql \
  -h $DB_HOST \
  -U $DB_USER \
  -d $DB_NAME \
  -f sql/undo/U10__rollback_optimize.sql


# 3. Validar estado de migraciones

flyway \
  -configFile=conf/flyway-prod.conf \
  info


# 4. Aplicar migración correctiva aprobada

flyway \
  -configFile=conf/flyway-prod.conf \
  migrate
```

No modificar manualmente la tabla:

`flyway_schema_history`

en producción salvo procedimiento DBA expresamente aprobado.

---

### 8.3 PITR como Último Recurso

```bash
# Si una migración produce corrupción grave:

# 1. Detener aplicación

kubectl scale deployment/app --replicas=0


# 2. Restaurar con pgBackRest

pgbackrest \
    --stanza=hospital \
    --type=time \
    --target='2026-10-02 14:29:00' \
    restore


# 3. Verificar integridad

psql \
  -c "SELECT count(*) FROM cita;"


# 4. Validar migraciones

flyway \
  -configFile=conf/flyway-prod.conf \
  info


# 5. Reiniciar aplicación cuando DBA apruebe

kubectl scale deployment/app --replicas=3
```

---

## 9. Validación y Testing de Migraciones

### 9.1 Callbacks de Flyway

#### beforeMigrate.sql

```sql
-- Verificar versión PostgreSQL

SELECT version();


-- Verificar extensiones requeridas

SELECT extname
FROM pg_extension
WHERE extname IN (
    'pgcrypto',
    'btree_gist',
    'pg_trgm'
);


-- Verificar tamaño de BD

SELECT pg_size_pretty(
    pg_database_size(
        current_database()
    )
) AS db_size;


-- Verificar migraciones fallidas

SELECT
    version,
    checksum,
    success

FROM flyway_schema_history

WHERE success = false;


-- Verificar conexiones activas

SELECT count(*)

FROM pg_stat_activity

WHERE datname = current_database()

  AND pid != pg_backend_pid();
```

#### afterMigrate.sql

```sql
-- Contar objetos

SELECT
    'Tablas' AS tipo,
    count(*)

FROM information_schema.tables

WHERE table_schema = 'public'

UNION ALL

SELECT
    'Índices',
    count(*)

FROM pg_indexes

WHERE schemaname = 'public'

UNION ALL

SELECT
    'Funciones',
    count(*)

FROM pg_proc p

JOIN pg_namespace n
  ON n.oid = p.pronamespace

WHERE n.nspname = 'public'

UNION ALL

SELECT
    'Triggers',
    count(*)

FROM information_schema.triggers

WHERE trigger_schema = 'public'

UNION ALL

SELECT
    'Políticas RLS',
    count(*)

FROM pg_policies

WHERE schemaname = 'public';


-- Verificar FK no validadas

SELECT
    conname,
    conrelid::regclass,
    confrelid::regclass

FROM pg_constraint

WHERE contype = 'f'

  AND convalidated = false;


-- Verificar particiones de auditoría

SELECT
    schemaname,
    tablename,
    pg_size_pretty(
        pg_relation_size(
            schemaname || '.' || tablename
        )
    ) AS size

FROM pg_tables

WHERE tablename LIKE
      'registro_auditoria_%'

ORDER BY tablename;
```

No ejecutar automáticamente un:

```sql
DELETE FROM cita
```

como prueba post-migración en producción.

Las pruebas destructivas deben realizarse únicamente en entornos controlados.

---

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

        ports:
          - '5432:5432'

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


      - name: Run Migrations

        run: |

          flyway \
            -configFiles=proyecto/base_datos/migraciones/conf/flyway-dev.conf \
            -placeholders.app_schema=public \
            migrate


      - name: Validate Schema

        run: |

          psql \
            -h localhost \
            -U test \
            -d test_db \
            -f proyecto/base_datos/migraciones/scripts/validate_schema.sql


      - name: Run Integration Tests

        run: |

          npm run test:integration


      - name: Verify Undo Scripts

        if: always()

        run: |

          ls -la \
          proyecto/base_datos/migraciones/undo/
```

---

### 9.3 Script de Validación de Esquema

```sql
-- scripts/validate_schema.sql


-- 1. Verificar tablas esperadas

SELECT table_name

FROM information_schema.tables

WHERE table_schema = 'public'

ORDER BY table_name;


-- 2. Verificar columnas de cita

SELECT
    column_name,
    data_type,
    is_nullable,
    column_default

FROM information_schema.columns

WHERE table_schema = 'public'

  AND table_name = 'cita'

ORDER BY ordinal_position;


-- Validación crítica:
-- cita NO debe contener id_medico.

SELECT column_name

FROM information_schema.columns

WHERE table_schema = 'public'

  AND table_name = 'cita'

  AND column_name = 'id_medico';


-- El resultado esperado es 0 filas.


-- 3. Verificar constraints

SELECT
    constraint_name,
    constraint_type,
    table_name

FROM information_schema.table_constraints

WHERE table_schema = 'public'

ORDER BY
    table_name,
    constraint_type;


-- 4. Verificar índices

SELECT
    indexname,
    tablename,
    indexdef

FROM pg_indexes

WHERE schemaname = 'public'

ORDER BY
    tablename,
    indexname;


-- 5. Verificar particiones

SELECT

    parent.relname AS tabla,

    child.relname AS particion,

    pg_size_pretty(
        pg_relation_size(
            child.oid
        )
    ) AS size

FROM pg_inherits

JOIN pg_class parent
  ON parent.oid = inhparent

JOIN pg_class child
  ON child.oid = inhrelid

WHERE parent.relname IN (
    'registro_auditoria',
    'cita_historico'
);


-- 6. Verificar RLS

SELECT
    schemaname,
    tablename,
    policyname,
    permissive,
    roles,
    cmd,
    qual

FROM pg_policies

WHERE schemaname = 'public';


-- 7. Verificar funciones/triggers

SELECT
    routine_name,
    routine_type,
    data_type

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

        options:
          - dev
          - staging
          - prod

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

          find \
            proyecto/base_datos/migraciones/sql \
            -name 'V*.sql' \
            | sort -V

          sha256sum \
            proyecto/base_datos/migraciones/sql/V*.sql


  deploy-dev:

    needs: validate

    if: github.event.inputs.environment == 'dev'

    runs-on: ubuntu-latest

    environment: development

    steps:

      - uses: actions/checkout@v4

      - name: Deploy to Dev

        run: |

          flyway \
            -configFiles=proyecto/base_datos/migraciones/conf/flyway-dev.conf \
            migrate


  deploy-staging:

    needs: validate

    if: github.event.inputs.environment == 'staging'

    runs-on: ubuntu-latest

    environment: staging

    steps:

      - uses: actions/checkout@v4


      - name: Backup Staging

        run: |

          pgbackrest \
            --stanza=staging \
            backup \
            --type=incr


      - name: Deploy to Staging

        run: |

          flyway \
            -configFiles=proyecto/base_datos/migraciones/conf/flyway-staging.conf \
            migrate


      - name: Run Smoke Tests

        run: |

          npm run test:smoke \
            -- \
            --env=staging


  deploy-prod:

    needs: validate

    if: github.event.inputs.environment == 'prod'

    runs-on: ubuntu-latest

    environment: production

    steps:

      - uses: actions/checkout@v4


      - name: Backup Production

        run: |

          pgbackrest \
            --stanza=hospital \
            backup \
            --type=full


      - name: Deploy Production

        run: |

          # Ejecutar únicamente después
          # de aprobación DBA y autorización
          # de despliegue.

          flyway \
            -configFiles=proyecto/base_datos/migraciones/conf/flyway-prod.conf \
            migrate


      - name: Post-Deploy Verification

        run: |

          npm run test:smoke \
            -- \
            --env=prod
```

---

## 11. Checklist de Migración a Producción

| Paso | Acción | Responsable | Verificación |
|------|--------|-------------|--------------|
| 1 | Revisar PR de migraciones | DBA + Dev Lead | Code review aprobado |
| 2 | Ejecutar en Dev | Dev | `flyway migrate` OK, tests pasan |
| 3 | Ejecutar en Staging | DevOps | Backup + migrate + smoke tests |
| 4 | Aprobar ventana de mantenimiento | Gerencia TI | Ventana confirmada |
| 5 | Backup full producción | DBA | pgBackRest completado |
| 6 | Notificar stakeholders | PM | Notificación enviada |
| 7 | Ejecutar migración producción | DBA | Flyway + validación |
| 8 | Verificar aplicación | QA | Smoke tests |
| 9 | Monitorear | DBA + DevOps | CPU, conexiones, locks |
| 10 | Documentar resultado | PM / DBA | Release notes |

---

## 12. Correcciones realizadas después del Paso 14

### Corrección 1

Se elimina `id_medico` de la tabla `cita` dentro del baseline.

El médico se obtiene mediante:

```text
cita.id_horario
        ↓
horario.id_medico
```

---

### Corrección 2

No se debe crear:

```text
UNIQUE(id_medico, id_horario)
```

ni:

```text
UNIQUE(id_horario)
```

de manera absoluta.

---

### Corrección 3

La protección de doble reserva utiliza:

```text
id_horario
+
fecha_hora_programada
+
estado activo
```

---

### Corrección 4

El trigger:

```text
fn_validar_cita_medico_especialidad
```

ya no recibe cambios sobre:

```text
id_medico
```

en `cita`.

Debe ejecutarse ante cambios de:

```text
id_horario
id_especialidad
```

---

### Corrección 5

La migración legacy ya no inserta:

```text
cita.id_medico
```

El médico se utiliza exclusivamente para encontrar:

```text
horario.id_medico
```

y posteriormente se guarda:

```text
cita.id_horario
```

---

### Corrección 6

Se utiliza:

```text
fecha_hora_programada
```

en lugar del antiguo:

```text
fecha_hora_inicio
```

para la fecha programada de una cita.

---

### Corrección 7

Se mantiene únicamente el parámetro aprobado:

```text
tiempo_inactividad_sesion_minutos = 15
```

como valor inicial configurable.

---

### Corrección 8

D-02, D-03 y D-04 continúan pendientes.

No se fijan valores arbitrarios para:

- cancelación;
- reprogramación;
- tolerancia;
- anticipación máxima.

---

### Corrección 9

No se fijan arbitrariamente:

- máximo de intentos fallidos;
- duración de bloqueo;
- duración estándar del slot;

si los requisitos no han aprobado esos valores.

---

### Corrección 10

La validación posterior debe comprobar explícitamente que:

```text
cita.id_medico
```

no exista.

---

## 13. Estado del Archivo

**Archivo:**

`proyecto/base_datos/12_migraciones/migraciones.md`

**Paso de origen:**

`Paso 13 — Migraciones`

**Agente utilizado:**

`database-engineer`

**Skills utilizados:**

- `databases`
- `postgresql-table-design`

**Corrección posterior:**

Propagación de:

- Paso 05 — Normalización;
- Paso 14 — Revisión DBA con `STATUS: CHANGES_REQUIRED`.

**Estado actual:**

Corregido manualmente y pendiente de nueva Revisión DBA.

---

## 14. Próximo Paso

**Paso 14 — Revisión DBA**

Usar:

- `database-schema-designer`
- `databases`
- `postgresql-table-design`

Salida:

`proyecto/base_datos/15_reportes/revision_dba.md`

**Estado requerido para Paso 15:**

```text
STATUS: APPROVED
```

La nueva revisión debe comprobar especialmente que:

1. ningún artefacto posterior haya reintroducido `cita.id_medico`;
2. no exista `UNIQUE(id_medico,id_horario)` en `cita`;
3. la doble reserva utilice la ocurrencia del horario;
4. D-02/D-03/D-04 continúen pendientes;
5. D-08/D-09 continúen sin placeholders;
6. los triggers utilicen `horario.id_medico`;
7. los índices estén alineados con el modelo normalizado.

**NO ejecutar Paso 15 todavía.**

DETENERSE y esperar aprobación humana.