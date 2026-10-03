# Modelo Físico — Paso 07

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español
**Generado:** Paso 07 — Diseño físico
**Agente utilizado:** `database-engineer`
**Skill utilizado:** `postgresql-table-design` + `databases`
**Workflow:** `02_database_workflow`
**Fuente principal:** `modelo_logico.md` + `seleccion_dbms.md`
**Estado:** Pendiente de validación humana

---

## 1. Objetivo del Paso 07

Traducir el **modelo lógico** a un **modelo físico específico para PostgreSQL 18.6** aplicando:

- Tipos de datos PostgreSQL nativos (`TIMESTAMPTZ`, `BOOLEAN`, `TEXT`, `BIGINT GENERATED ALWAYS AS IDENTITY`)
- Convenciones de nombrado: `snake_case`, sin comillas
- Reglas `postgresql-table-design`: FK indexadas manualmente, `NOT NULL` por defecto, `CHECK` + `NOT NULL` combinados
- Extensiones requeridas: `pgaudit`, `pgcrypto`, `uuid-ossp` (opcional), `citext` (si se requiere)
- Preparar para Paso 08 (Integridad), Paso 11 (Índices), Paso 12 (Transacciones)

---

## 2. Mapeo de tipos lógicos → físicos (PostgreSQL)

| Tipo lógico | Tipo físico PostgreSQL | Regla aplicada |
|-------------|------------------------|----------------|
| `BIGINT` (PK) | `BIGINT GENERATED ALWAYS AS IDENTITY` | Core rule: prefer over `SERIAL` |
| `VARCHAR(n)` | `TEXT` + `CHECK (LENGTH(col) <= n)` | Avoid `VARCHAR(n)` — use `TEXT` with length check |
| `DATE` | `DATE` | Native |
| `TIME` | `TIME WITHOUT TIME ZONE` | Native |
| `TIMESTAMP WITH TIME ZONE` | `TIMESTAMPTZ` | **Rule: always `timestamptz`** |
| `BOOLEAN` | `BOOLEAN NOT NULL` | Native boolean; avoid tri-state unless needed |
| `TEXT` | `TEXT` | Native; large values auto-TOAST |
| `DECIMAL(p,s)` | `NUMERIC(p,s)` | Rule: never `money`, never float for money |
| `SMALLINT` | `SMALLINT` | Native |
| `ENUM` (estados) | `CREATE TYPE ... AS ENUM` | For small, stable sets (cita.estado, horario.estado) |

---

## 3. Extensiones requeridas

```sql
-- Extensiones base
CREATE EXTENSION IF NOT EXISTS "pgaudit";       -- Auditoría RF-22
CREATE EXTENSION IF NOT EXISTS "pgcrypto";      -- Hash contraseñas, gen_random_uuid()
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";     -- UUIDs (opcional, para PK opacas)
-- CREATE EXTENSION IF NOT EXISTS "citext";      -- Solo si se requiere case-insensitive en PK/FK/UNIQUE
```

---

## 4. Tipos ENUM (pequeños, estables)

```sql
CREATE TYPE estado_cita AS ENUM (
    'Programada', 'Confirmada', 'En_atencion', 'Finalizada', 'Cancelada', 'No_asistida'
);

CREATE TYPE estado_horario AS ENUM (
    'disponible', 'reservado', 'ocupado'
);

CREATE TYPE modalidad_cita AS ENUM (
    'presencial', 'virtual'
);

CREATE TYPE accion_auditoria AS ENUM (
    'crear', 'modificar', 'cancelar', 'finalizar', 'reprogramar', 'cambiar_modalidad'
);
```

> **Nota:** Per `postgresql-table-design`, ENUM solo para conjuntos pequeños y estables. Estados de cita/horario son estables; para valores de negocio evolutivos usar `TEXT` + `CHECK`.

---

## 5. Tablas físicas (DDL PostgreSQL 18.6)

### 5.1 paciente

```sql
CREATE TABLE paciente (
    id_paciente         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre              TEXT NOT NULL CHECK (LENGTH(nombre) <= 100),
    apellidos           TEXT NOT NULL CHECK (LENGTH(apellidos) <= 150),
    documento_identidad TEXT NOT NULL UNIQUE CHECK (LENGTH(documento_identidad) <= 20),
    fecha_nacimiento    DATE NOT NULL CHECK (fecha_nacimiento <= CURRENT_DATE),
    telefono            TEXT NOT NULL CHECK (LENGTH(telefono) <= 20),
    correo_electronico  TEXT UNIQUE CHECK (LENGTH(correo_electronico) <= 255),
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Índice FK manual no requerido (no hay FK salientes)
-- Índices de acceso: Paso 11
```

### 5.2 medico

```sql
CREATE TABLE medico (
    id_medico       BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre_completo TEXT NOT NULL CHECK (LENGTH(nombre_completo) <= 200),
    numero_colegiado TEXT NOT NULL UNIQUE CHECK (LENGTH(numero_colegiado) <= 30),
    activo          BOOLEAN NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);
```

### 5.3 usuario

```sql
CREATE TABLE usuario (
    id_usuario          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    username            TEXT NOT NULL UNIQUE CHECK (LENGTH(username) <= 100),
    password_hash       TEXT NOT NULL CHECK (LENGTH(password_hash) <= 255),  -- RNF-06: solo hash
    email               TEXT NOT NULL UNIQUE CHECK (LENGTH(email) <= 255),
    activo              BOOLEAN NOT NULL DEFAULT TRUE,
    id_paciente         BIGINT UNIQUE REFERENCES paciente(id_paciente) ON DELETE SET NULL,
    id_medico           BIGINT UNIQUE REFERENCES medico(id_medico) ON DELETE SET NULL,
    ultimo_acceso       TIMESTAMPTZ,
    intentos_fallidos   SMALLINT NOT NULL DEFAULT 0 CHECK (intentos_fallidos >= 0),
    bloqueado_hasta     TIMESTAMPTZ,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Índices FK manuales (Paso 11):
-- CREATE INDEX idx_usuario_id_paciente ON usuario(id_paciente) WHERE id_paciente IS NOT NULL;
-- CREATE INDEX idx_usuario_id_medico ON usuario(id_medico) WHERE id_medico IS NOT NULL;
```

> **RNF-06:** `password_hash` almacena hash (bcrypt/Argon2 via `pgcrypto::crypt()`). Nunca texto plano.

### 5.4 especialidad

```sql
CREATE TABLE especialidad (
    id_especialidad BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre          TEXT NOT NULL UNIQUE CHECK (LENGTH(nombre) <= 100),
    descripcion     TEXT CHECK (LENGTH(descripcion) <= 500),
    activo          BOOLEAN NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);
```

### 5.5 medico_especialidad (tabla asociativa N:M)

```sql
CREATE TABLE medico_especialidad (
    id_medico                    BIGINT NOT NULL REFERENCES medico(id_medico) ON DELETE RESTRICT,
    id_especialidad              BIGINT NOT NULL REFERENCES especialidad(id_especialidad) ON DELETE RESTRICT,
    fecha_asociacion             DATE NOT NULL DEFAULT CURRENT_DATE,
    habilitada_modalidad_virtual BOOLEAN NOT NULL DEFAULT FALSE,  -- D-08 pendiente
    created_at                   TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (id_medico, id_especialidad)
);

-- Índice FK manual (Paso 11): CREATE INDEX ON medico_especialidad (id_especialidad);
```

### 5.6 horario

```sql
CREATE TABLE horario (
    id_horario      BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_medico       BIGINT NOT NULL REFERENCES medico(id_medico) ON DELETE RESTRICT,
    dia_semana      SMALLINT CHECK (dia_semana BETWEEN 1 AND 7),  -- 1=Lunes...7=Domingo
    fecha_especifica DATE,                                        -- NULL para semanal recurrente
    hora_inicio     TIME WITHOUT TIME ZONE NOT NULL,
    hora_fin        TIME WITHOUT TIME ZONE NOT NULL,
    estado          estado_horario NOT NULL DEFAULT 'disponible',
    modalidad       modalidad_cita,                              -- D-09 pendiente
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now(),

    CHECK (hora_fin > hora_inicio),
    CHECK (
        (dia_semana IS NOT NULL AND fecha_especifica IS NULL) OR
        (dia_semana IS NULL AND fecha_especifica IS NOT NULL)
    )
);

-- Índice FK manual (Paso 11): CREATE INDEX ON horario (id_medico);
-- Índice para búsqueda de disponibilidad: CREATE INDEX ON horario (id_medico, dia_semana) WHERE estado = 'disponible';
```

### 5.7 cita

```sql
CREATE TABLE cita (
    id_cita                BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_paciente            BIGINT NOT NULL REFERENCES paciente(id_paciente) ON DELETE RESTRICT,
    id_medico              BIGINT NOT NULL REFERENCES medico(id_medico) ON DELETE RESTRICT,
    id_horario             BIGINT NOT NULL REFERENCES horario(id_horario) ON DELETE RESTRICT,
    id_especialidad        BIGINT NOT NULL REFERENCES especialidad(id_especialidad) ON DELETE RESTRICT,
    id_usuario_registrador BIGINT NOT NULL REFERENCES usuario(id_usuario) ON DELETE RESTRICT,
    estado                 estado_cita NOT NULL DEFAULT 'Programada',
    modalidad              modalidad_cita NOT NULL DEFAULT 'presencial',
    fecha_hora_inicio      TIMESTAMPTZ NOT NULL,
    fecha_hora_fin         TIMESTAMPTZ,
    fecha_creacion         TIMESTAMPTZ NOT NULL DEFAULT now(),
    fecha_actualizacion    TIMESTAMPTZ NOT NULL DEFAULT now(),
    observaciones          TEXT,

    -- RN-04 / RNF-11: No doble reserva mismo médico+horario
    UNIQUE (id_medico, id_horario),

    -- RN-20: fecha_hora_fin > fecha_hora_inicio
    CHECK (fecha_hora_fin IS NULL OR fecha_hora_fin > fecha_hora_inicio),

    -- Coherencia modalidad ↔ atencion_virtual (validación en trigger Paso 08)
    -- CHECK (modalidad = 'virtual' AND EXISTS (SELECT 1 FROM atencion_virtual av WHERE av.id_cita = cita.id_cita)
    --        OR modalidad = 'presencial' AND NOT EXISTS (SELECT 1 FROM atencion_virtual av WHERE av.id_cita = cita.id_cita))
);

-- Índices FK manuales (Paso 11):
-- CREATE INDEX ON cita (id_paciente);
-- CREATE INDEX ON cita (id_medico);
-- CREATE INDEX ON cita (id_horario);
-- CREATE INDEX ON cita (id_especialidad);
-- CREATE INDEX ON cita (id_usuario_registrador);
```

### 5.8 atencion_virtual

```sql
CREATE TABLE atencion_virtual (
    id_cita                 BIGINT PRIMARY KEY REFERENCES cita(id_cita) ON DELETE RESTRICT,
    id_sesion_externa       TEXT CHECK (LENGTH(id_sesion_externa) <= 100),
    enlace_acceso           TEXT NOT NULL CHECK (LENGTH(enlace_acceso) <= 500),
    estado_disponibilidad   TEXT NOT NULL DEFAULT 'disponible' CHECK (estado_disponibilidad IN ('disponible', 'no_disponible')),
    fecha_incidente         TIMESTAMPTZ,
    detalles_incidente      TEXT,
    created_at              TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at              TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Índice FK manual no requerido (PK = FK a cita)
```

### 5.9 registro_auditoria

```sql
CREATE TABLE registro_auditoria (
    id_registro              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_cita                  BIGINT NOT NULL REFERENCES cita(id_cita) ON DELETE RESTRICT,
    id_usuario_responsable   BIGINT NOT NULL REFERENCES usuario(id_usuario) ON DELETE RESTRICT,
    accion                   accion_auditoria NOT NULL,
    campo_modificado         TEXT CHECK (LENGTH(campo_modificado) <= 100),
    valor_anterior           TEXT,
    valor_actual             TEXT NOT NULL,
    fecha_hora               TIMESTAMPTZ NOT NULL DEFAULT now(),
    ip_origen                INET,                    -- IPv4/IPv6
    user_agent               TEXT CHECK (LENGTH(user_agent) <= 500)
);

-- Índices FK manuales (Paso 11):
-- CREATE INDEX ON registro_auditoria (id_cita);
-- CREATE INDEX ON registro_auditoria (id_usuario_responsable);
-- Índice patrón historial: CREATE INDEX ON registro_auditoria (id_cita, fecha_hora DESC);
```

> **RN-25:** `ON DELETE RESTRICT` en FKs — auditoría no se borra al cancelar/finalizar cita (≥5 años).

### 5.10 parametros_configuracion

```sql
CREATE TABLE parametros_configuracion (
    id_parametro  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    clave         TEXT NOT NULL UNIQUE CHECK (LENGTH(clave) <= 100),
    valor         TEXT NOT NULL CHECK (LENGTH(valor) <= 500),
    tipo_dato     TEXT NOT NULL CHECK (tipo_dato IN ('integer','decimal','string','boolean','duration')),
    descripcion   TEXT NOT NULL CHECK (LENGTH(descripcion) <= 500),
    categoria     TEXT NOT NULL CHECK (LENGTH(categoria) <= 50),
    editable      BOOLEAN NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Datos semilla (trazables a RN-26..RN-28, D-02..D-04, D-13):
INSERT INTO parametros_configuracion (clave, valor, tipo_dato, descripcion, categoria) VALUES
('tiempo_min_cancelacion_minutos',    '60',   'integer', 'Minutos mínimos antes de la cita para cancelar',     'citas'),
('tiempo_min_reprogramacion_minutos', '60',   'integer', 'Minutos mínimos antes para reprogramar',           'citas'),
('tolerancia_no_asistida_minutos',    '15',   'integer', 'Minutos de espera antes de marcar No_asistida',    'citas'),
('anticipacion_maxima_dias',          '90',   'integer', 'Días máximos de anticipación para reservar',       'citas'),
('tiempo_inactividad_sesion_minutos', '15',   'integer', 'Timeout de sesión inactiva (RNF-07, D-13)',        'sesion');
```

### 5.11 rol

```sql
CREATE TABLE rol (
    id_rol        BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre        TEXT NOT NULL UNIQUE CHECK (LENGTH(nombre) <= 50),
    descripcion   TEXT CHECK (LENGTH(descripcion) <= 200),
    created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Datos semilla (RF-21, RNF-05, RT-06):
INSERT INTO rol (nombre, descripcion) VALUES
('paciente',       'Acceso a sus citas y perfil'),
('medico',         'Acceso a su agenda y atención'),
('admision',       'Gestión de citas de terceros (D-10 pendiente alcance)'),
('admin',          'Acceso total al sistema');
```

### 5.12 permiso

```sql
CREATE TABLE permiso (
    id_permiso    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre        TEXT NOT NULL UNIQUE CHECK (LENGTH(nombre) <= 100),
    descripcion   TEXT NOT NULL CHECK (LENGTH(descripcion) <= 300),
    recurso       TEXT NOT NULL CHECK (LENGTH(recurso) <= 50),
    accion        TEXT NOT NULL CHECK (accion IN ('crear','leer','actualizar','eliminar','ejecutar')),
    created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Ejemplos (D-10 pendiente alcance admisión):
INSERT INTO permiso (nombre, descripcion, recurso, accion) VALUES
('cita.crear',         'Crear cita',                'cita',      'crear'),
('cita.leer_propias',  'Leer sus propias citas',    'cita',      'leer'),
('cita.leer_todas',    'Leer todas las citas',      'cita',      'leer'),
('cita.actualizar',    'Actualizar cita',           'cita',      'actualizar'),
('cita.cancelar',      'Cancelar cita',             'cita',      'eliminar'),
('cita.reprogramar',   'Reprogramar cita',          'cita',      'ejecutar'),
('medico.crear',       'Crear médico',              'medico',    'crear'),
('medico.leer',        'Leer médico',               'medico',    'leer'),
('medico.actualizar',  'Actualizar médico',         'medico',    'actualizar'),
('medico.desactivar',  'Desactivar médico',         'medico',    'ejecutar'),
('paciente.crear',     'Crear paciente',            'paciente',  'crear'),
('paciente.leer_propio','Leer propio paciente',     'paciente',  'leer'),
('paciente.actualizar_propio','Actualizar propio paciente','paciente','actualizar'),
('usuario.gestionar_roles','Gestionar roles',       'usuario',   'ejecutar'),
('usuario.gestionar_permisos','Gestionar permisos', 'usuario',   'ejecutar'),
('auditoria.leer',     'Leer auditoría',            'auditoria', 'leer'),
('config.leer',        'Leer configuración',        'config',    'leer'),
('config.actualizar',  'Actualizar configuración',  'config',    'actualizar');
```

### 5.13 usuario_rol (tabla asociativa N:M)

```sql
CREATE TABLE usuario_rol (
    id_usuario        BIGINT NOT NULL REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    id_rol            BIGINT NOT NULL REFERENCES rol(id_rol) ON DELETE RESTRICT,
    asignado_por      BIGINT REFERENCES usuario(id_usuario) ON DELETE SET NULL,
    fecha_asignacion  TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (id_usuario, id_rol)
);

-- Índices FK manuales (Paso 11):
-- CREATE INDEX ON usuario_rol (id_rol);
```

### 5.14 rol_permiso (tabla asociativa N:M)

```sql
CREATE TABLE rol_permiso (
    id_rol      BIGINT NOT NULL REFERENCES rol(id_rol) ON DELETE CASCADE,
    id_permiso  BIGINT NOT NULL REFERENCES permiso(id_permiso) ON DELETE CASCADE,
    PRIMARY KEY (id_rol, id_permiso)
);

-- Índice FK manual (Paso 11): CREATE INDEX ON rol_permiso (id_permiso);
```

---

## 6. Row-Level Security (RLS) — Preparación Paso 09

```sql
-- Habilitar RLS en tablas sensibles
ALTER TABLE paciente        ENABLE ROW LEVEL SECURITY;
ALTER TABLE medico          ENABLE ROW LEVEL SECURITY;
ALTER TABLE cita            ENABLE ROW LEVEL SECURITY;
ALTER TABLE registro_auditoria ENABLE ROW LEVEL SECURITY;
-- atencion_virtual se protege vía cita

-- Políticas ejemplo (implementación completa en Paso 09):
-- CREATE POLICY paciente_own ON paciente
--   FOR SELECT TO app_users
--   USING (id_paciente = current_setting('app.current_paciente_id')::bigint);
--
-- CREATE POLICY cita_paciente ON cita
--   FOR SELECT TO app_paciente
--   USING (id_paciente = current_setting('app.current_paciente_id')::bigint);
--
-- CREATE POLICY cita_medico ON cita
--   FOR SELECT TO app_medico
--   USING (id_medico = current_setting('app.current_medico_id')::bigint);
```

> RLS nativo en PostgreSQL permite aislamiento por paciente/rol (RN-14, RN-16) sin lógica de aplicación.

---

## 7. Decisiones D-08 y D-09 — Estado en modelo físico

| Decisión | Columna física | Estado |
|----------|----------------|--------|
| **D-08** Habilitación modalidad virtual | `medico_especialidad.habilitada_modalidad_virtual BOOLEAN DEFAULT FALSE` | **Placeholder** — decisión final en Paso 08/09 |
| **D-09** Modalidad en horario vs cita | `horario.modalidad modalidad_cita` + `cita.modalidad modalidad_cita NOT NULL DEFAULT 'presencial'` | **Ambas presentes** — una se usará según decisión final |

---

## 8. Funciones y triggers (esquema para Paso 08/12)

| Función/Trigger | Propósito | Paso |
|-----------------|-----------|------|
| `fn_validar_cita_medico_especialidad()` | Validar `(id_medico, id_especialidad)` existe en `medico_especialidad` antes de INSERT/UPDATE en `cita` | 08 |
| `fn_actualizar_horario_al_reservar()` | Al INSERT `cita` (estado=Confirmada) → UPDATE `horario` estado='ocupado' | 08/12 |
| `fn_liberar_horario_al_cancelar()` | Al CANCELAR `cita` → UPDATE `horario` estado='disponible' | 08/12 |
| `fn_reprogramar_cita_atomica()` | Transacción: nuevo horario reservado + anterior liberado (RN-12) | 12 |
| `fn_auditar_cambio_cita()` | Insertar en `registro_auditoria` en UPDATE/DELETE de `cita` | 08 |
| `fn_verificar_transiciones_estado()` | Validar máquina de estados RN-07/08/09 | 08 |
| `fn_verificar_no_solapamiento_paciente()` | Validar RN-05 (no superposición citas paciente) | 08 |

> Implementación completa en **Paso 08 — Integridad** y **Paso 12 — Transacciones**.

---

## 9. Particionamiento (preparación para tablas grandes)

```sql
-- Tablas candidatas a partición por tiempo (>100M filas estimadas):
-- - registro_auditoria: PARTITION BY RANGE (fecha_hora) mensual/anual
-- - cita: PARTITION BY RANGE (fecha_hora_inicio) si volumen muy alto

-- Ejemplo registro_auditoria (declarativa PG10+):
-- CREATE TABLE registro_auditoria (
--     ... columnas ...
-- ) PARTITION BY RANGE (fecha_hora);
--
-- CREATE TABLE registro_auditoria_2025_01 PARTITION OF registro_auditoria
--     FOR VALUES FROM ('2025-01-01') TO ('2025-02-01');
-- ...
```

> **Regla `postgresql-table-design`:** Particionar solo si >100M filas o mantenimiento (purga/retención) sigue una clave temporal. `registro_auditoria` es candidata (conservación ≥5 años). `cita` depende de volumen real.

---

## 10. Checklist AI Self-Check aplicado (`databases` skill)

- [x] PK: `BIGINT GENERATED ALWAYS AS IDENTITY` (no `SERIAL`)
- [x] `TIMESTAMPTZ` para todos los timestamps (no `timestamp`)
- [x] `TEXT` + `CHECK (LENGTH)` en vez de `VARCHAR(n)`
- [x] `NUMERIC` para dinero (no `money`, no float)
- [x] `BOOLEAN NOT NULL` salvo tri-state
- [x] `ENUM` para estados pequeños/estables
- [x] FK con `ON DELETE` explícito (`RESTRICT` por conservación)
- [x] `NULLS NOT DISTINCT` considerado para UNIQUE (PG15+)
- [x] Índices FK manuales planificados (Paso 11)
- [x] Sin `trust` / `md5` auth — `SCRAM-SHA-256` (config Paso 09)
- [x] TLS forced — `hostssl` (config Paso 09)
- [x] `pgAudit` para auditoría RF-22
- [x] Datos semilla para `parametros_configuracion`, `rol`, `permiso`
- [x] RLS habilitado en tablas sensibles (políticas Paso 09)

---

## 11. Conclusiones del Paso 07

1. **14 tablas físicas** definidas con DDL PostgreSQL 18.6 completo.
2. **Tipos nativos:** `TIMESTAMPTZ`, `BOOLEAN`, `TEXT` + `CHECK`, `BIGINT GENERATED ALWAYS AS IDENTITY`, `ENUM` para estados.
3. **FK indexadas manualmente** listadas (implementación en Paso 11).
4. **Extensiones:** `pgaudit`, `pgcrypto`, `uuid-ossp`.
5. **RLS preparado** para aislamiento por paciente/rol (RN-14, RN-16).
6. **D-08/D-09** reflejadas como columnas placeholder; decisión final en Paso 08/09.
7. **Particionamiento** diseñado para `registro_auditoria` (conservación ≥5 años).
8. **Triggers/funciones** esquematizadas para Paso 08 (Integridad) y Paso 12 (Transacciones).
9. **Listo para Paso 08** (Integridad: PK, FK, UNIQUE, CHECK, DEFAULT, triggers).

---

## 12. Próximo Paso

**Paso 08 — Integridad**  
Usar: `database-schema-designer` + `postgresql-table-design`  
Salida: `proyecto/base_datos/06_integridad/integridad.md`

DETENERSE y esperar aprobación humana.