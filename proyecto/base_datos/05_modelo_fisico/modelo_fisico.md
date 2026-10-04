# Modelo Físico — Paso 07

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español  
**Generado:** Paso 07 — Diseño físico  
**Agente utilizado:** `database-engineer`  
**Skill utilizado:** `postgresql-table-design` + `databases`  
**Workflow:** `02_database_workflow`  
**Fuente principal:** `modelo_logico.md` + `seleccion_dbms.md` + corrección de normalización del Paso 05  
**Motor seleccionado:** PostgreSQL  
**Estado:** Corregido manualmente después de la Revisión DBA del Paso 14. Pendiente de nueva validación DBA.

---

## 1. Objetivo del Paso 07

Traducir el **modelo lógico normalizado** a un **modelo físico específico para PostgreSQL**, aplicando:

- tipos de datos nativos de PostgreSQL;
- convenciones `snake_case`;
- claves primarias y foráneas;
- restricciones físicas;
- valores por defecto únicamente cuando estén aprobados;
- preparación para integridad, seguridad, índices y concurrencia;
- conservación histórica de citas y auditoría.

### Corrección posterior incorporada

El Paso 05 — Normalización determinó que:

`cita.id_medico`

era redundante debido a:

`id_horario → id_medico`

Por tanto:

`id_cita → id_horario → id_medico`

generaba una dependencia transitiva.

Como consecuencia, el modelo físico corregido:

- elimina `cita.id_medico`;
- obtiene el médico mediante `horario.id_medico`;
- elimina la FK directa Cita → Médico;
- elimina `UNIQUE(id_medico, id_horario)`;
- no establece `UNIQUE(id_horario)` absoluto;
- conserva la posibilidad de mantener citas históricas;
- prepara una regla de unicidad únicamente para reservas activas incompatibles;
- conserva `cita.id_especialidad`.

---

## 2. Mapeo de tipos lógicos a PostgreSQL

| Tipo lógico | Tipo físico PostgreSQL | Aplicación |
|---|---|---|
| Identificador | `BIGINT GENERATED ALWAYS AS IDENTITY` | PK numérica |
| Texto | `TEXT` | Texto general |
| Fecha | `DATE` | Fecha |
| Hora | `TIME WITHOUT TIME ZONE` | Hora |
| FechaHora | `TIMESTAMPTZ` | Instante temporal |
| Booleano | `BOOLEAN` | Verdadero/falso |
| Entero pequeño | `SMALLINT` | Valores pequeños |
| Decimal | `NUMERIC(p,s)` | Valores decimales |
| Dominio estable | `ENUM` o `CHECK` | Estados controlados |

---

## 3. Extensiones consideradas

```sql
CREATE EXTENSION IF NOT EXISTS "pgaudit";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
```

### Nota

`pgaudit` se considera para auditoría técnica del motor.

`pgcrypto` puede utilizarse para funciones criptográficas cuando corresponda.

Las contraseñas de los usuarios **no deben almacenarse en texto plano**. La tabla `usuario` almacena únicamente `password_hash`.

No se requiere `uuid-ossp` mientras el modelo mantenga identificadores `BIGINT`.

---

## 4. Tipos ENUM

```sql
CREATE TYPE estado_cita AS ENUM (
    'Programada',
    'Confirmada',
    'En_atencion',
    'Finalizada',
    'Cancelada',
    'No_asistida'
);

CREATE TYPE estado_horario AS ENUM (
    'disponible',
    'reservado',
    'ocupado'
);

CREATE TYPE modalidad_cita AS ENUM (
    'presencial',
    'virtual'
);

CREATE TYPE accion_auditoria AS ENUM (
    'crear',
    'modificar',
    'cancelar',
    'finalizar',
    'reprogramar',
    'cambiar_modalidad'
);
```

Estos dominios representan conjuntos pequeños y controlados.

---

# 5. Tablas físicas

## 5.1 paciente

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
```

Los índices adicionales para patrones de acceso se definen en el Paso 11.

---

## 5.2 medico

```sql
CREATE TABLE medico (
    id_medico        BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre_completo  TEXT NOT NULL CHECK (LENGTH(nombre_completo) <= 200),
    numero_colegiado TEXT NOT NULL UNIQUE CHECK (LENGTH(numero_colegiado) <= 30),
    activo           BOOLEAN NOT NULL DEFAULT TRUE,

    created_at       TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT now()
);
```

---

## 5.3 usuario

```sql
CREATE TABLE usuario (
    id_usuario        BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    username          TEXT NOT NULL UNIQUE CHECK (LENGTH(username) <= 100),
    password_hash     TEXT NOT NULL CHECK (LENGTH(password_hash) <= 255),
    email             TEXT NOT NULL UNIQUE CHECK (LENGTH(email) <= 255),
    activo            BOOLEAN NOT NULL DEFAULT TRUE,

    id_paciente       BIGINT UNIQUE
                      REFERENCES paciente(id_paciente)
                      ON DELETE SET NULL,

    id_medico         BIGINT UNIQUE
                      REFERENCES medico(id_medico)
                      ON DELETE SET NULL,

    ultimo_acceso     TIMESTAMPTZ,

    intentos_fallidos SMALLINT NOT NULL
                      DEFAULT 0
                      CHECK (intentos_fallidos >= 0),

    bloqueado_hasta   TIMESTAMPTZ,

    created_at        TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at        TIMESTAMPTZ NOT NULL DEFAULT now()
);
```

### Índices FK planificados

```sql
-- Paso 11:
-- CREATE INDEX idx_usuario_paciente
--     ON usuario(id_paciente)
--     WHERE id_paciente IS NOT NULL;

-- CREATE INDEX idx_usuario_medico
--     ON usuario(id_medico)
--     WHERE id_medico IS NOT NULL;
```

### Seguridad

`password_hash` almacena únicamente la credencial protegida.

El algoritmo concreto y su aplicación se documentan en el Paso 09 — Seguridad.

---

## 5.4 especialidad

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

---

## 5.5 medico_especialidad

```sql
CREATE TABLE medico_especialidad (
    id_medico       BIGINT NOT NULL
                    REFERENCES medico(id_medico)
                    ON DELETE RESTRICT,

    id_especialidad BIGINT NOT NULL
                    REFERENCES especialidad(id_especialidad)
                    ON DELETE RESTRICT,

    fecha_asociacion DATE DEFAULT CURRENT_DATE,

    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),

    PRIMARY KEY (id_medico, id_especialidad)
);
```

### Importante sobre D-08

No se crea:

```text
habilitada_modalidad_virtual
```

porque D-08 continúa pendiente.

No debe materializarse una decisión funcional no aprobada.

### Índice FK planificado

```sql
-- Paso 11:
-- CREATE INDEX idx_medico_especialidad_especialidad
--     ON medico_especialidad(id_especialidad);
```

---

## 5.6 horario

```sql
CREATE TABLE horario (
    id_horario       BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    id_medico        BIGINT NOT NULL
                     REFERENCES medico(id_medico)
                     ON DELETE RESTRICT,

    dia_semana       SMALLINT
                     CHECK (dia_semana BETWEEN 1 AND 7),

    fecha_especifica DATE,

    hora_inicio      TIME WITHOUT TIME ZONE NOT NULL,
    hora_fin         TIME WITHOUT TIME ZONE NOT NULL,

    estado           estado_horario NOT NULL DEFAULT 'disponible',

    created_at       TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT now(),

    CHECK (hora_fin > hora_inicio),

    CHECK (
        (dia_semana IS NOT NULL AND fecha_especifica IS NULL)
        OR
        (dia_semana IS NULL AND fecha_especifica IS NOT NULL)
    )
);
```

### D-09

No se crea:

```text
horario.modalidad
```

porque D-09 continúa pendiente.

La modalidad continúa almacenada en `cita`.

### Dependencia funcional relevante

Cada horario pertenece a un único médico:

```text
id_horario → id_medico
```

Esta dependencia es la razón por la cual `cita` no debe duplicar `id_medico`.

### Índice FK planificado

```sql
-- Paso 11:
-- CREATE INDEX idx_horario_medico
--     ON horario(id_medico);
```

---

# 5.7 cita

```sql
CREATE TABLE cita (
    id_cita                 BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    id_paciente             BIGINT NOT NULL
                            REFERENCES paciente(id_paciente)
                            ON DELETE RESTRICT,

    id_horario              BIGINT NOT NULL
                            REFERENCES horario(id_horario)
                            ON DELETE RESTRICT,

    id_especialidad         BIGINT NOT NULL
                            REFERENCES especialidad(id_especialidad)
                            ON DELETE RESTRICT,

    id_usuario_registrador  BIGINT NOT NULL
                            REFERENCES usuario(id_usuario)
                            ON DELETE RESTRICT,

    estado                  estado_cita NOT NULL
                            DEFAULT 'Programada',

    modalidad               modalidad_cita NOT NULL
                            DEFAULT 'presencial',

    fecha_hora_programada   TIMESTAMPTZ NOT NULL,

    fecha_hora_inicio_atencion TIMESTAMPTZ,

    fecha_hora_fin_atencion TIMESTAMPTZ,

    fecha_creacion          TIMESTAMPTZ NOT NULL DEFAULT now(),

    fecha_actualizacion     TIMESTAMPTZ NOT NULL DEFAULT now(),

    observaciones           TEXT,

    CHECK (
        fecha_hora_fin_atencion IS NULL
        OR fecha_hora_inicio_atencion IS NULL
        OR fecha_hora_fin_atencion > fecha_hora_inicio_atencion
    )
);
```

## Corrección de normalización

La tabla `cita` **no contiene `id_medico`**.

El médico se obtiene mediante:

```text
cita.id_horario
        ↓
horario.id_horario
        ↓
horario.id_medico
        ↓
medico.id_medico
```

Por tanto, se elimina la FK directa:

```text
cita.id_medico → medico.id_medico
```

---

## Regla de Médico–Especialidad

Para una cita debe verificarse que:

```text
(horario.id_medico, cita.id_especialidad)
```

exista en:

```text
medico_especialidad(id_medico, id_especialidad)
```

La implementación se desarrolla en el Paso 08 mediante una regla de integridad apropiada.

---

## Regla de doble reserva

No se utiliza:

```sql
UNIQUE (id_medico, id_horario)
```

y tampoco:

```sql
UNIQUE (id_horario)
```

como restricciones absolutas.

Esto permitiría problemas con:

- horarios recurrentes;
- citas canceladas;
- reprogramaciones;
- conservación histórica.

La regla funcional es:

> No pueden coexistir dos citas activas incompatibles sobre la misma ocurrencia de un horario.

Para horarios recurrentes, la ocurrencia está determinada por:

```text
id_horario + fecha_hora_programada
```

Por tanto, en el Paso 11 puede implementarse en PostgreSQL una estrategia equivalente a:

```sql
-- Ejemplo conceptual para Paso 11.
-- No ejecutar todavía en este Paso 07.

-- CREATE UNIQUE INDEX uk_cita_horario_ocurrencia_activa
-- ON cita (id_horario, fecha_hora_programada)
-- WHERE estado IN ('Programada', 'Confirmada', 'En_atencion');
```

Este enfoque:

- impide doble reserva activa;
- permite conservar citas canceladas;
- permite conservar citas históricas;
- permite reutilizar un bloque recurrente en fechas diferentes.

---

## Índices FK planificados

```sql
-- Paso 11:

-- CREATE INDEX idx_cita_paciente
--     ON cita(id_paciente);

-- CREATE INDEX idx_cita_horario
--     ON cita(id_horario);

-- CREATE INDEX idx_cita_especialidad
--     ON cita(id_especialidad);

-- CREATE INDEX idx_cita_usuario_registrador
--     ON cita(id_usuario_registrador);
```

No existe un índice sobre:

```text
cita.id_medico
```

porque esa columna fue eliminada.

---

## 5.8 atencion_virtual

```sql
CREATE TABLE atencion_virtual (
    id_cita                BIGINT PRIMARY KEY
                           REFERENCES cita(id_cita)
                           ON DELETE RESTRICT,

    id_sesion_externa      TEXT
                           CHECK (LENGTH(id_sesion_externa) <= 100),

    enlace_acceso          TEXT NOT NULL
                           CHECK (LENGTH(enlace_acceso) <= 500),

    estado_disponibilidad  TEXT NOT NULL
                           DEFAULT 'disponible'
                           CHECK (
                               estado_disponibilidad
                               IN ('disponible', 'no_disponible')
                           ),

    fecha_hora_incidente   TIMESTAMPTZ,

    detalles_incidente     TEXT,

    created_at             TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at             TIMESTAMPTZ NOT NULL DEFAULT now()
);
```

La relación sigue siendo:

**Cita 1 : 0..1 Atención Virtual**

---

## 5.9 registro_auditoria

```sql
CREATE TABLE registro_auditoria (
    id_registro            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    id_cita                BIGINT NOT NULL
                           REFERENCES cita(id_cita)
                           ON DELETE RESTRICT,

    id_usuario_responsable BIGINT NOT NULL
                           REFERENCES usuario(id_usuario)
                           ON DELETE RESTRICT,

    accion                 accion_auditoria NOT NULL,

    campo_modificado       TEXT
                           CHECK (LENGTH(campo_modificado) <= 100),

    valor_anterior         TEXT,

    valor_actual           TEXT NOT NULL,

    fecha_hora             TIMESTAMPTZ NOT NULL DEFAULT now(),

    ip_origen              INET,

    user_agent             TEXT
                           CHECK (LENGTH(user_agent) <= 500)
);
```

### Índices planificados

```sql
-- Paso 11:

-- CREATE INDEX idx_auditoria_cita
--     ON registro_auditoria(id_cita);

-- CREATE INDEX idx_auditoria_usuario
--     ON registro_auditoria(id_usuario_responsable);

-- CREATE INDEX idx_auditoria_cita_fecha
--     ON registro_auditoria(id_cita, fecha_hora DESC);
```

La auditoría no se elimina cuando una cita es cancelada o finalizada.

---

# 5.10 parametros_configuracion

```sql
CREATE TABLE parametros_configuracion (
    id_parametro BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    clave        TEXT NOT NULL UNIQUE
                 CHECK (LENGTH(clave) <= 100),

    valor        TEXT NOT NULL
                 CHECK (LENGTH(valor) <= 500),

    tipo_dato    TEXT NOT NULL
                 CHECK (
                     tipo_dato IN (
                         'integer',
                         'decimal',
                         'string',
                         'boolean',
                         'duration'
                     )
                 ),

    descripcion  TEXT NOT NULL
                 CHECK (LENGTH(descripcion) <= 500),

    categoria    TEXT NOT NULL
                 CHECK (LENGTH(categoria) <= 50),

    editable     BOOLEAN NOT NULL DEFAULT TRUE,

    created_at   TIMESTAMPTZ NOT NULL DEFAULT now(),

    updated_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);
```

## Parámetros pendientes

No se insertan valores arbitrarios para:

- D-02 — tiempo mínimo de cancelación/reprogramación;
- D-03 — tolerancia de no asistencia;
- D-04 — anticipación máxima de reserva.

Por tanto, se eliminan los valores físicos anteriores:

```text
60
60
15
90
```

que no estaban aprobados.

### Único valor inicial aprobado

El requisito RNF-07/D-13 establece un valor inicial configurable de 15 minutos para inactividad de sesión.

```sql
INSERT INTO parametros_configuracion (
    clave,
    valor,
    tipo_dato,
    descripcion,
    categoria
)
VALUES (
    'tiempo_inactividad_sesion_minutos',
    '15',
    'integer',
    'Tiempo inicial configurable de inactividad de sesión',
    'sesion'
);
```

### Parámetros a registrar posteriormente

Cuando exista decisión humana para D-02, D-03 y D-04 podrán incorporarse:

```text
tiempo_min_cancelacion_minutos
tiempo_min_reprogramacion_minutos
tolerancia_no_asistida_minutos
anticipacion_maxima_dias
```

sin modificar la estructura de la tabla.

---

# 5.11 rol

```sql
CREATE TABLE rol (
    id_rol      BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    nombre      TEXT NOT NULL UNIQUE
                CHECK (LENGTH(nombre) <= 50),

    descripcion TEXT
                CHECK (LENGTH(descripcion) <= 200),

    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);
```

### Datos iniciales

```sql
INSERT INTO rol (nombre, descripcion)
VALUES
    ('paciente', 'Acceso a sus citas y perfil'),
    ('medico',   'Acceso a su agenda y atención'),
    ('admision', 'Gestión de citas de terceros'),
    ('admin',    'Administración del sistema');
```

D-10 continúa pendiente respecto del alcance exacto de las capacidades de admisión.

---

# 5.12 permiso

```sql
CREATE TABLE permiso (
    id_permiso  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    nombre      TEXT NOT NULL UNIQUE
                CHECK (LENGTH(nombre) <= 100),

    descripcion TEXT NOT NULL
                CHECK (LENGTH(descripcion) <= 300),

    recurso     TEXT NOT NULL
                CHECK (LENGTH(recurso) <= 50),

    accion      TEXT NOT NULL
                CHECK (
                    accion IN (
                        'crear',
                        'leer',
                        'actualizar',
                        'eliminar',
                        'ejecutar'
                    )
                ),

    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);
```

### Permisos base

```sql
INSERT INTO permiso (
    nombre,
    descripcion,
    recurso,
    accion
)
VALUES
    ('cita.crear',
     'Crear cita',
     'cita',
     'crear'),

    ('cita.leer_propias',
     'Leer sus propias citas',
     'cita',
     'leer'),

    ('cita.leer_todas',
     'Leer todas las citas',
     'cita',
     'leer'),

    ('cita.actualizar',
     'Actualizar cita',
     'cita',
     'actualizar'),

    ('cita.cancelar',
     'Cancelar cita',
     'cita',
     'ejecutar'),

    ('cita.reprogramar',
     'Reprogramar cita',
     'cita',
     'ejecutar'),

    ('medico.crear',
     'Crear médico',
     'medico',
     'crear'),

    ('medico.leer',
     'Leer médico',
     'medico',
     'leer'),

    ('medico.actualizar',
     'Actualizar médico',
     'medico',
     'actualizar'),

    ('medico.desactivar',
     'Desactivar médico',
     'medico',
     'ejecutar'),

    ('paciente.crear',
     'Crear paciente',
     'paciente',
     'crear'),

    ('paciente.leer_propio',
     'Leer propio paciente',
     'paciente',
     'leer'),

    ('paciente.actualizar_propio',
     'Actualizar propio paciente',
     'paciente',
     'actualizar'),

    ('usuario.gestionar_roles',
     'Gestionar roles',
     'usuario',
     'ejecutar'),

    ('usuario.gestionar_permisos',
     'Gestionar permisos',
     'usuario',
     'ejecutar'),

    ('auditoria.leer',
     'Leer auditoría',
     'auditoria',
     'leer'),

    ('config.leer',
     'Leer configuración',
     'config',
     'leer'),

    ('config.actualizar',
     'Actualizar configuración',
     'config',
     'actualizar');
```

La asignación exacta de permisos al personal de admisión continúa sujeta a D-10.

---

# 5.13 usuario_rol

```sql
CREATE TABLE usuario_rol (
    id_usuario       BIGINT NOT NULL
                     REFERENCES usuario(id_usuario)
                     ON DELETE CASCADE,

    id_rol           BIGINT NOT NULL
                     REFERENCES rol(id_rol)
                     ON DELETE RESTRICT,

    asignado_por     BIGINT
                     REFERENCES usuario(id_usuario)
                     ON DELETE SET NULL,

    fecha_asignacion TIMESTAMPTZ NOT NULL DEFAULT now(),

    PRIMARY KEY (id_usuario, id_rol)
);
```

### Índice planificado

```sql
-- Paso 11:
-- CREATE INDEX idx_usuario_rol_rol
--     ON usuario_rol(id_rol);
```

---

# 5.14 rol_permiso

```sql
CREATE TABLE rol_permiso (
    id_rol     BIGINT NOT NULL
               REFERENCES rol(id_rol)
               ON DELETE CASCADE,

    id_permiso BIGINT NOT NULL
               REFERENCES permiso(id_permiso)
               ON DELETE CASCADE,

    PRIMARY KEY (id_rol, id_permiso)
);
```

### Índice planificado

```sql
-- Paso 11:
-- CREATE INDEX idx_rol_permiso_permiso
--     ON rol_permiso(id_permiso);
```

---

# 6. Relaciones físicas principales

| Relación | Implementación |
|---|---|
| Paciente → Cita | `cita.id_paciente` |
| Médico → Horario | `horario.id_medico` |
| Horario → Cita | `cita.id_horario` |
| Médico → Cita | Indirecta por `cita.id_horario → horario.id_medico` |
| Especialidad → Cita | `cita.id_especialidad` |
| Médico ↔ Especialidad | `medico_especialidad` |
| Usuario → Cita | `cita.id_usuario_registrador` |
| Cita → Atención Virtual | `atencion_virtual.id_cita` |
| Cita → Auditoría | `registro_auditoria.id_cita` |
| Usuario → Auditoría | `registro_auditoria.id_usuario_responsable` |
| Usuario ↔ Rol | `usuario_rol` |
| Rol ↔ Permiso | `rol_permiso` |

---

# 7. Row-Level Security — preparación Paso 09

```sql
ALTER TABLE paciente
ENABLE ROW LEVEL SECURITY;

ALTER TABLE medico
ENABLE ROW LEVEL SECURITY;

ALTER TABLE cita
ENABLE ROW LEVEL SECURITY;

ALTER TABLE registro_auditoria
ENABLE ROW LEVEL SECURITY;
```

## Paciente

Ejemplo conceptual:

```sql
-- CREATE POLICY paciente_own
-- ON paciente
-- FOR SELECT
-- TO app_paciente
-- USING (
--     id_paciente =
--     current_setting('app.current_paciente_id')::BIGINT
-- );
```

## Citas del paciente

```sql
-- CREATE POLICY cita_paciente
-- ON cita
-- FOR SELECT
-- TO app_paciente
-- USING (
--     id_paciente =
--     current_setting('app.current_paciente_id')::BIGINT
-- );
```

## Citas del médico

Como `cita.id_medico` ya no existe, la política debe obtener el médico desde `horario`.

```sql
-- CREATE POLICY cita_medico
-- ON cita
-- FOR SELECT
-- TO app_medico
-- USING (
--     EXISTS (
--         SELECT 1
--         FROM horario h
--         WHERE h.id_horario = cita.id_horario
--           AND h.id_medico =
--               current_setting('app.current_medico_id')::BIGINT
--     )
-- );
```

La implementación definitiva corresponde al Paso 09 — Seguridad.

---

# 8. Estado de D-08 y D-09

| Decisión | Estado físico corregido |
|---|---|
| D-08 — habilitación de modalidad virtual | Pendiente. No se crea columna física provisional |
| D-09 — modalidad asociada al horario | Pendiente. `horario` no contiene `modalidad`; `cita.modalidad` permanece |

Por tanto, se eliminan del diseño físico:

```text
medico_especialidad.habilitada_modalidad_virtual
horario.modalidad
```

No deben existir placeholders físicos para decisiones aún no aprobadas.

---

# 9. Funciones y triggers previstos

| Función / Trigger | Propósito | Paso |
|---|---|---|
| `fn_validar_cita_medico_especialidad()` | Obtener médico desde `horario.id_medico` y comprobar su asociación con `cita.id_especialidad` | 08 |
| `fn_actualizar_horario_al_reservar()` | Gestionar estado del horario al reservar | 08/12 |
| `fn_liberar_horario_al_cancelar()` | Gestionar liberación del horario al cancelar | 08/12 |
| `fn_reprogramar_cita_atomica()` | Reprogramar conservando atomicidad e histórico | 12 |
| `fn_auditar_cambio_cita()` | Registrar modificaciones sobre una cita | 08/10 |
| `fn_verificar_transiciones_estado()` | Validar las transiciones permitidas | 08 |
| `fn_verificar_no_solapamiento_paciente()` | Validar ausencia de superposición de citas activas | 08 |

## Validación Médico–Especialidad

La función correspondiente debe utilizar:

```text
NEW.id_horario
      ↓
horario.id_medico
```

junto con:

```text
NEW.id_especialidad
```

para comprobar la existencia de:

```text
medico_especialidad(
    id_medico,
    id_especialidad
)
```

No debe buscar:

```text
NEW.id_medico
```

porque esa columna ya no existe en `cita`.

---

# 10. Regla de doble reserva

La restricción funcional sigue siendo:

> No puede existir más de una cita activa incompatible para la misma ocurrencia de un horario.

Se consideran estados que mantienen la reserva, de acuerdo con las reglas actuales:

- `Programada`;
- `Confirmada`;
- `En_atencion`.

Los estados históricos o liberados incluyen:

- `Cancelada`;
- `Finalizada`;
- `No_asistida`.

La implementación definitiva deberá mantener la posibilidad de conservar registros históricos.

Para un horario recurrente, la comprobación debe considerar:

```text
id_horario + fecha_hora_programada
```

y no solamente `id_horario`.

---

# 11. Particionamiento

El particionamiento no se implementa todavía de manera obligatoria.

Las principales tablas candidatas futuras son:

- `registro_auditoria`, por `fecha_hora`;
- `cita`, por `fecha_hora_programada`, solamente si el volumen futuro lo justifica.

Ejemplo conceptual:

```sql
-- Solo si el volumen real lo justifica.

-- CREATE TABLE registro_auditoria (
--     ...
-- ) PARTITION BY RANGE (fecha_hora);
```

La decisión final dependerá de volumen, mantenimiento y política de retención.

---

# 12. Índices previstos

La definición completa corresponde al Paso 11.

Las principales FK candidatas son:

```text
usuario.id_paciente
usuario.id_medico

medico_especialidad.id_medico
medico_especialidad.id_especialidad

horario.id_medico

cita.id_paciente
cita.id_horario
cita.id_especialidad
cita.id_usuario_registrador

registro_auditoria.id_cita
registro_auditoria.id_usuario_responsable

usuario_rol.id_rol

rol_permiso.id_permiso
```

No existe:

```text
cita.id_medico
```

por lo que ningún índice debe diseñarse sobre esa columna.

---

# 13. Parámetros configurables

Los parámetros:

```text
tiempo_min_cancelacion_minutos
tiempo_min_reprogramacion_minutos
tolerancia_no_asistida_minutos
anticipacion_maxima_dias
```

continúan pendientes.

No deben utilizarse valores hardcodeados hasta que se resuelvan:

- D-02;
- D-03;
- D-04.

El parámetro:

```text
tiempo_inactividad_sesion_minutos
```

mantiene el valor inicial configurable:

```text
15
```

por RNF-07 / D-13.

---

# 14. Checklist del modelo físico corregido

- [x] 14 tablas físicas conservadas.
- [x] PostgreSQL como motor seleccionado.
- [x] PK con `BIGINT GENERATED ALWAYS AS IDENTITY`.
- [x] `TIMESTAMPTZ` para instantes temporales.
- [x] FK con acciones `ON DELETE` explícitas.
- [x] `cita.id_medico` eliminado.
- [x] Médico de una cita derivado mediante `horario.id_medico`.
- [x] `id_especialidad` conservado en `cita`.
- [x] No existe `UNIQUE(id_medico, id_horario)`.
- [x] No existe `UNIQUE(id_horario)` absoluto.
- [x] Doble reserva preparada para control por ocurrencia activa.
- [x] Historial de citas conservado.
- [x] D-08 no materializado como placeholder.
- [x] D-09 no materializado en `horario`.
- [x] D-02/D-03/D-04 sin valores arbitrarios.
- [x] RLS de médico preparada mediante `horario`.
- [x] Triggers previstos actualizados para médico derivado.
- [x] Índices sobre `cita.id_medico` eliminados.
- [x] Particionamiento de cita referenciado a `fecha_hora_programada`.
- [x] Preparado para nueva revisión DBA.

---

# 15. Cambios respecto a la versión anterior

## Cambio 1 — Cita

Eliminado:

```text
cita.id_medico
```

---

## Cambio 2 — FK Cita–Médico

Eliminada:

```text
cita.id_medico → medico.id_medico
```

Sustituida por la ruta:

```text
cita.id_horario
→ horario.id_medico
→ medico.id_medico
```

---

## Cambio 3 — Doble reserva

Eliminado:

```sql
UNIQUE (id_medico, id_horario)
```

No se sustituye por:

```sql
UNIQUE (id_horario)
```

La estrategia se basa en la ocurrencia:

```text
id_horario + fecha_hora_programada
```

y únicamente para citas activas incompatibles.

---

## Cambio 4 — Médico–Especialidad

Antes:

```text
(cita.id_medico, cita.id_especialidad)
```

Ahora:

```text
(horario.id_medico, cita.id_especialidad)
```

---

## Cambio 5 — RLS

La política del médico ya no consulta:

```text
cita.id_medico
```

Obtiene el médico mediante:

```text
cita → horario → medico
```

---

## Cambio 6 — D-08

Eliminado:

```text
medico_especialidad.habilitada_modalidad_virtual
```

D-08 permanece pendiente.

---

## Cambio 7 — D-09

Eliminado:

```text
horario.modalidad
```

D-09 permanece pendiente.

`cita.modalidad` se conserva.

---

## Cambio 8 — Parámetros

Eliminados los valores arbitrarios:

```text
60 minutos cancelación
60 minutos reprogramación
15 minutos no asistencia
90 días anticipación
```

D-02, D-03 y D-04 continúan pendientes.

Se conserva únicamente:

```text
tiempo_inactividad_sesion_minutos = 15
```

porque posee fundamento explícito en RNF-07 / D-13.

---

# 16. Conclusiones del Paso 07 corregido

1. Se mantienen **14 tablas físicas**.

2. PostgreSQL continúa siendo el motor seleccionado.

3. `cita.id_medico` fue eliminado del modelo físico.

4. El médico de una cita se determina mediante:

   `cita.id_horario → horario.id_medico`.

5. La coherencia Médico–Especialidad utiliza ahora:

   `(horario.id_medico, cita.id_especialidad)`.

6. Se elimina la restricción:

   `UNIQUE(id_medico, id_horario)`.

7. No se introduce una unicidad absoluta sobre `id_horario`.

8. La protección contra doble reserva debe trabajar sobre una **ocurrencia de horario**, considerando `id_horario` y `fecha_hora_programada`, y únicamente sobre estados activos incompatibles.

9. Esto permite conservar citas canceladas, finalizadas, no asistidas y demás registros históricos.

10. La política RLS para médicos debe obtener el médico mediante `horario`.

11. Las funciones y triggers posteriores deben utilizar `horario.id_medico`.

12. D-08 y D-09 continúan pendientes y no generan columnas placeholder.

13. D-02, D-03 y D-04 continúan pendientes y no reciben valores arbitrarios.

14. Se conserva el valor inicial configurable de 15 minutos para inactividad de sesión.

15. El modelo queda preparado para propagar estas correcciones a Integridad, Seguridad, Auditoría, Índices, Transacciones y Migraciones.

---

# 17. Estado del archivo

**Archivo:**

`proyecto/base_datos/05_modelo_fisico/modelo_fisico.md`

**Paso de origen:**

`Paso 07 — Diseño físico`

**Agente utilizado:**

`database-engineer`

**Skills utilizados:**

- `postgresql-table-design`
- `databases`

**Corrección posterior:**

Se incorporaron los cambios derivados de:

- Paso 05 — Normalización;
- Paso 14 — Revisión DBA con `STATUS: CHANGES_REQUIRED`.

**Estado actual:**

Corregido manualmente y pendiente de nueva Revisión DBA.

---

# 18. Control de avance

Esta corrección **NO autoriza el Paso 15 — Generación SQL**.

Todavía deben propagarse los cambios hacia los artefactos posteriores afectados:

- `integridad.md`;
- `seguridad.md`;
- `auditoria_historico.md`;
- `indices_rendimiento.md`;
- `transacciones_concurrencia.md`;
- `migraciones.md`.

Una vez corregidos, debe repetirse:

**Paso 14 — Revisión DBA**

y obtener:

```text
STATUS: APPROVED
```

antes de avanzar al Paso 15.

**NO generar SQL final todavía.**

**DETENERSE y esperar nueva validación DBA.**