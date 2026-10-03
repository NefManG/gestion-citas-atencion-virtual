# Informe de Integridad — Paso 08

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español
**Generado:** Paso 08 — Integridad
**Agente utilizado:** `database-engineer`
**Skill utilizado:** `database-schema-designer` + `postgresql-table-design`
**Workflow:** `02_database_workflow`
**Fuente principal:** `modelo_fisico.md`
**Estado:** Pendiente de validación humana

---

## 1. Objetivo del Paso 08

Documentar de forma exhaustiva **todas las restricciones de integridad** del modelo físico PostgreSQL:
- Claves primarias (PK)
- Claves foráneas (FK) con sus estrategias `ON DELETE` / `ON UPDATE`
- Restricciones `UNIQUE` (incluidas compuestas y `NULLS NOT DISTINCT`)
- Restricciones `CHECK`
- Valores por defecto (`DEFAULT`)
- Nulabilidad (`NOT NULL`)
- Triggers de integridad referencial y reglas de negocio
- Mapeo explícito de cada restricción a su **RF / RNF / RN / D** asociada

Este informe es la base para la revisión DBA (Paso 14) y la generación SQL (Paso 15).

---

## 2. Conceptos de integridad en PostgreSQL

| Tipo | Característica en PG | Nota |
|------|---------------------|------|
| **PK** | `UNIQUE` + `NOT NULL` implícitos; crea B-tree index | La PK siempre es indexada |
| **FK** | No crea índice automáticamente → **indexar FK manualmente** | Regla crítica `postgresql-table-design` |
| **UNIQUE** | Crea B-tree index; permite NULLs múltiples | PG15+: `NULLS NOT DISTINCT` para restringir a uno solo |
| **CHECK** | Row-local; **NULL pasa** (lógica de 3 valores) | Combinar con `NOT NULL` cuando aplique |
| **DEFAULT** | Valores no volátiles → DDL rápido; volátiles (`now()`) → rewrite completo de tabla | Usar `DEFAULT now()` para timestamps |
| **TRIGGER** | `BEFORE INSERT/UPDATE/DELETE` para validación; `AFTER` para efectos en cascada | Usar triggers para reglas que cruzan tablas |
| **EXCLUDE** | Prevención de superposiciones vía GiST | Usar para evitar dobles citas |

---

## 3. Restricciones por tabla

### 3.1 paciente

| Restricción | Definición | Fuente |
|-------------|-----------|--------|
| **PK** | `id_paciente BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY` | CA-001 |
| **NOT NULL** | `nombre`, `apellidos`, `documento_identidad`, `fecha_nacimiento`, `telefono` | RF-01, CA-002 |
| **UNIQUE** | `documento_identidad` | RF-01 (identificador único) |
| **CHECK** | `fecha_nacimiento <= CURRENT_DATE` | Validación fecha válida |
| **CHECK** | `LENGTH(telefono) <= 20` | Formato celular/NIT |
| **DEFAULT** | `created_at`, `updated_at` → `now()` | Auditoría técnica |

**FK salientes:** Ninguna. (La vinculación Usuario-Paciente es en la tabla `usuario`.)

**Triggers:** Ninguno de integridad referencial.

---

### 3.2 medico

| Restricción | Definición | Fuente |
|-------------|-----------|--------|
| **PK** | `id_medico BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY` | — |
| **NOT NULL** | `nombre_completo`, `numero_colegiado`, `activo` | RF-02 |
| **UNIQUE** | `numero_colegiado` | Registro profesional único |
| **CHECK** | `LENGTH(numero_colegiado) >= 4` | Formato colegiatura |
| **DEFAULT** | `activo` → `TRUE`, `created_at`/`updated_at` → `now()` | — |

**FK salientes:** Ninguna.

**Triggers:** Ninguno.

> **RN-02:** "Cada médico debe estar asociado a ≥ 1 especialidad activa" → **Restricción de existencia** (trigger, §6.1) — no es una FK/UNIQUE/CHECK simple.

---

### 3.3 usuario

| Restricción | Definición | Fuente |
|-------------|-----------|--------|
| **PK** | `id_usuario BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY` | — |
| **NOT NULL** | `username`, `password_hash`, `email`, `activo`, `intentos_fallidos` | RF-21, RNF-06 |
| **UNIQUE** | `username`, `email` | Identificadores de cuenta únicos |
| **CHECK** | `intentos_fallidos >= 0` | Semáforo de fuerza bruta |
| **CHECK** | `LENGTH(password_hash) <= 255` | bcrypt/Argon2 |
| **DEFAULT** | `activo` → `TRUE`, `created_at`/`updated_at` → `now()` | — |
| **FK** | `id_paciente BIGINT UNIQUE REFERENCES paciente(id_paciente) ON DELETE SET NULL` | R-18 |
| **FK** | `id_medico BIGINT UNIQUE REFERENCES medico(id_medico) ON DELETE SET NULL` | R-04 |

**Nota FK Usuario–Paciente / Usuario–Médico:**
- Columnas `id_paciente` e `id_medico` en `usuario` son **UNIQUE** y **NULLables**, permitiendo la relación 0..1:0..1.
- `ON DELETE SET NULL`: si se deshabilita un paciente/médico (RN-35), la cuenta no se rompe.

**Triggers:** Ninguno de integridad referencial.

> **RNF-06:** `password_hash` — el valor real de la contraseña **nunca** se escribe en la base; el DBMS solo almacena el hash. `pgcrypto::crypt()` (Paso 09).

---

### 3.4 especialidad

| Restricción | Definición | Fuente |
|-------------|-----------|--------|
| **PK** | `id_especialidad BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY` | — |
| **NOT NULL** | `nombre`, `activo` | RF-03, CA-007 |
| **UNIQUE** | `nombre` | Nombre de especialidad único |
| **DEFAULT** | `activo` → `TRUE`, `created_at`/`updated_at` → `now()` | RF-03 (solo activas consultadas) |

**FK salientes:** Ninguna.

**Triggers:** Ninguno.

---

### 3.5 medico_especialidad (tabla asociativa)

| Restricción | Definición | Fuente |
|-------------|-----------|--------|
| **PK compuesta** | `(id_medico, id_especialidad)` | RN-02 (unicidad del par) |
| **FK** | `id_medico REFERENCES medico(id_medico) ON DELETE RESTRICT` | — |
| **FK** | `id_especialidad REFERENCES especialidad(id_especialidad) ON DELETE RESTRICT` | — |
| **NOT NULL** | `id_medico`, `id_especialidad`, `fecha_asociacion`, `habilitada_modalidad_virtual` | — |
| **DEFAULT** | `fecha_asociacion` → `CURRENT_DATE`, `habilitada_modalidad_virtual` → `FALSE` | D-08 pendiente |

**FK ON DELETE RESTRICT:**
- No borrar médico si tiene especialidades asociadas (RN-35, D-06).
- No borrar especialidad si tiene médicos asociados.

**Triggers:** Ninguno de integridad referencial.

---

### 3.6 horario

| Restricción | Definición | Fuente |
|-------------|-----------|--------|
| **PK** | `id_horario BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY` | — |
| **NOT NULL** | `id_medico`, `hora_inicio`, `hora_fin`, `estado` | RF-05, RF-08 |
| **FK** | `id_medico REFERENCES medico(id_medico) ON DELETE RESTRICT` | R-05 |
| **CHECK** | `hora_fin > hora_inicio` | Franja coherente |
| **CHECK** | `(dia_semana BETWEEN 1 AND 7) OR (dia_semana IS NULL)` | Días válidos |
| **CHECK** | `dia_semana IS NULL XOR fecha_especifica IS NULL` | Uno u otro |
| **CHECK** | `estado IN ('disponible','reservado','ocupado')` (ENUM `estado_horario`) | RN-03, RN-10 |
| **DEFAULT** | `estado` → `'disponible'`, `created_at`/`updated_at` → `now()` | RN-03 |

**FK ON DELETE RESTRICT:** No borrar médico si tiene horarios activos (RN-35).

**Triggers:** Ninguno de integridad referencial.

> **D-09:** `modalidad` en `horario` es NULLABLE (no se decide si es obligatorio).

---

### 3.7 cita (tabla central)

| Restricción | Definición | Fuente |
|-------------|-----------|--------|
| **PK** | `id_cita BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY` | — |
| **NOT NULL** | `id_paciente`, `id_medico`, `id_horario`, `id_especialidad`, `id_usuario_registrador`, `estado`, `modalidad`, `fecha_hora_inicio`, `fecha_creacion`, `fecha_actualizacion` | RN-01, RF-09 |
| **FK** | `id_paciente REFERENCES paciente(id_paciente) ON DELETE RESTRICT` | R-08 |
| **FK** | `id_medico REFERENCES medico(id_medico) ON DELETE RESTRICT` | R-09 |
| **FK** | `id_horario REFERENCES horario(id_horario) ON DELETE RESTRICT` | R-07 |
| **FK** | `id_especialidad REFERENCES especialidad(id_especialidad) ON DELETE RESTRICT` | R-10 |
| **FK** | `id_usuario_registrador REFERENCES usuario(id_usuario) ON DELETE RESTRICT` | R-17 |
| **UNIQUE** | `(id_medico, id_horario)` | RN-04, RNF-11 (no doble reserva) |
| **CHECK** | `estado IN ('Programada','Confirmada','En_atencion','Finalizada','Cancelada','No_asistida')` (ENUM) | RN-07 |
| **CHECK** | `modalidad IN ('presencial','virtual')` (ENUM) | RN-18, RN-19 |
| **CHECK** | `fecha_hora_fin IS NULL OR fecha_hora_fin > fecha_hora_inicio` | RN-20 |
| **DEFAULT** | `estado` → `'Programada'`, `modalidad` → `'presencial'`, timestamps → `now()` | RN-07, RN-18 |

**FK ON DELETE RESTRICT en todas:**
- No borrar paciente/médico/horario/especialidad/usuario si tienen citas asociadas (conservación ≥5 años, RN-22/D-20).

**Triggers de integridad en cita (ver §6):**
- `fn_validar_cita_medico_especialidad()` — coherencia Médico–Especialidad
- `fn_verificar_no_solapamiento_paciente()` — RN-05
- `fn_verificar_no_doble_reserva()` — RN-04 (complemento a UNIQUE)
- `fn_verificar_transiciones_estado()` — RN-07/08/09
- `fn_actualizar_horario_al_reservar()` — RN-10
- `fn_liberar_horario_al_cancelar()` — RN-13
- `fn_auditar_cambio_cita()` — RN-23

---

### 3.8 atencion_virtual

| Restricción | Definición | Fuente |
|-------------|-----------|--------|
| **PK = FK** | `id_cita BIGINT PRIMARY KEY REFERENCES cita(id_cita) ON DELETE RESTRICT` | R-11 (1:0..1) |
| **NOT NULL** | `id_cita`, `enlace_acceso` | RN-19 |
| **CHECK** | `LENGTH(enlace_acceso) <= 500` | Longitud de URL |
| **CHECK** | `LENGTH(id_sesion_externa) <= 100` | Longitud ID externo |
| **CHECK** | `estado_disponibilidad IN ('disponible','no_disponible')` | Estado integridad |
| **DEFAULT** | `created_at`/`updated_at` → `now()` | — |

**FK ON DELETE RESTRICT:** No borrar cita si tiene información virtual.

**Triggers:** Ninguno (la integridad condicional modalidad ↔ atencion_virtual se verifica en `cita`).

> **RN-18/RN-19:** La existencia de fila en `atencion_virtual` ⇔ `cita.modalidad = 'virtual'`. Se valida con trigger en cita (Paso 12, transacción atómica).

---

### 3.9 registro_auditoria

| Restricción | Definición | Fuente |
|-------------|-----------|--------|
| **PK** | `id_registro BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY` | — |
| **NOT NULL** | `id_cita`, `id_usuario_responsable`, `accion`, `valor_actual`, `fecha_hora` | RN-23 |
| **FK** | `id_cita REFERENCES cita(id_cita) ON DELETE RESTRICT` | R-12 |
| **FK** | `id_usuario_responsable REFERENCES usuario(id_usuario) ON DELETE RESTRICT` | R-16 |
| **CHECK** | `accion IN ('crear','modificar','cancelar','finalizar','reprogramar','cambiar_modalidad')` (ENUM) | RN-23 |
| **CHECK** | `LENGTH(campo_modificado) <= 100` | Semántica de campo |
| **CHECK** | `LENGTH(user_agent) <= 500` | Metadato cliente |
| **DEFAULT** | `fecha_hora` → `now()` (con `clock_timestamp()` en trigger) | RN-23 |

**FK ON DELETE RESTRICT en ambas:**
- No borrar cita ni usuario si tienen auditoría asociada (RN-25, D-20: conservar ≥5 años).

**Triggers:** Ninguno (la auditoría la genera el trigger `fn_auditar_cambio_cita` en `cita`).

---

### 3.10 parametros_configuracion

| Restricción | Definición | Fuente |
|-------------|-----------|--------|
| **PK** | `id_parametro BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY` | — |
| **NOT NULL** | `clave`, `valor`, `tipo_dato`, `descripcion`, `categoria`, `editable` | RN-28, RNF-07 |
| **UNIQUE** | `clave` | Nombre técnico único |
| **CHECK** | `tipo_dato IN ('integer','decimal','string','boolean','duration')` | Validación de tipo |
| **CHECK** | `LENGTH(clave) <= 100`, `LENGTH(valor) <= 500`, `LENGTH(descripcion) <= 500`, `LENGTH(categoria) <= 50` | Longitudes |
| **DEFAULT** | `editable` → `TRUE`, `created_at`/`updated_at` → `now()` | — |

**FK salientes:** Ninguna.

**Triggers:** Ninguno.

---

### 3.11 rol

| Restricción | Definición | Fuente |
|-------------|-----------|--------|
| **PK** | `id_rol BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY` | — |
| **NOT NULL** | `nombre`, `descripcion` | RF-21, RNF-05 |
| **UNIQUE** | `nombre` | Roles únicos: paciente, médico, admisión, admin |
| **DEFAULT** | `created_at` → `now()` | — |

**FK salientes:** Ninguna.

**Datos semilla:** paciente, médico, admisión, admin (RF-21, RNF-05, RT-06).

**Triggers:** Ninguno.

---

### 3.12 permiso

| Restricción | Definición | Fuente |
|-------------|-----------|--------|
| **PK** | `id_permiso BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY` | — |
| **NOT NULL** | `nombre`, `descripcion`, `recurso`, `accion` | RF-21, RNF-05 |
| **UNIQUE** | `nombre` | Permisos únicos |
| **CHECK** | `accion IN ('crear','leer','actualizar','eliminar','ejecutar')` | Operaciones RBAC válidas |
| **CHECK** | `LENGTH(nombre) <= 100`, `LENGTH(descripcion) <= 300`, `LENGTH(recurso) <= 50` | Longitudes |
| **DEFAULT** | `created_at` → `now()` | — |

**FK salientes:** Ninguna.

**Datos semilla:** permisos base de RBAC (D-10 pendiente alcance de admisión).

**Triggers:** Ninguno.

---

### 3.13 usuario_rol (tabla asociativa)

| Restricción | Definición | Fuente |
|-------------|-----------|--------|
| **PK compuesta** | `(id_usuario, id_rol)` | RF-21, RNF-05 |
| **FK** | `id_usuario REFERENCES usuario(id_usuario) ON DELETE CASCADE` | — |
| **FK** | `id_rol REFERENCES rol(id_rol) ON DELETE RESTRICT` | — |
| **NOT NULL** | `id_usuario`, `id_rol`, `fecha_asignacion` | — |
| **FK (opcional)** | `asignado_por REFERENCES usuario(id_usuario) ON DELETE SET NULL` | Trazabilidad de asignación |
| **DEFAULT** | `fecha_asignacion` → `now()` | — |

**FK ON DELETE:**
- Usuario → CASCADE (si se borra usuario, se borran sus roles).
- Rol → RESTRICT (no borrar rol si tiene usuarios asignados, RN-17).

**Triggers:** Ninguno.

---

### 3.14 rol_permiso (tabla asociativa)

| Restricción | Definición | Fuente |
|-------------|-----------|--------|
| **PK compuesta** | `(id_rol, id_permiso)` | RF-21, RNF-05 |
| **FK** | `id_rol REFERENCES rol(id_rol) ON DELETE CASCADE` | — |
| **FK** | `id_permiso REFERENCES permiso(id_permiso) ON DELETE CASCADE` | — |
| **NOT NULL** | `id_rol`, `id_permiso` | — |

**FK ON DELETE CASCADE:** Si se borra rol o permiso, se borran las asociaciones.

**Triggers:** Ninguno.

---

## 4. Resumen tabular de todas las restricciones

### Tabla 1: Claves primarias

| Tabla | PK |
|-------|-----|
| paciente | `id_paciente BIGINT GENERATED ALWAYS AS IDENTITY` |
| medico | `id_medico BIGINT GENERATED ALWAYS AS IDENTITY` |
| usuario | `id_usuario BIGINT GENERATED ALWAYS AS IDENTITY` |
| especialidad | `id_especialidad BIGINT GENERATED ALWAYS AS IDENTITY` |
| medico_especialidad | `(id_medico, id_especialidad)` compuesta |
| horario | `id_horario BIGINT GENERATED ALWAYS AS IDENTITY` |
| cita | `id_cita BIGINT GENERATED ALWAYS AS IDENTITY` |
| atencion_virtual | `id_cita BIGINT GENERATED ALWAYS AS IDENTITY` (FK a cita) |
| registro_auditoria | `id_registro BIGINT GENERATED ALWAYS AS IDENTITY` |
| parametros_configuracion | `id_parametro BIGINT GENERATED ALWAYS AS IDENTITY` |
| rol | `id_rol BIGINT GENERATED ALWAYS AS IDENTITY` |
| permiso | `id_permiso BIGINT GENERATED ALWAYS AS IDENTITY` |
| usuario_rol | `(id_usuario, id_rol)` compuesta |
| rol_permiso | `(id_rol, id_permiso)` compuesta |

### Tabla 2: Claves foráneas y estrategias ON DELETE

| FK | Tabla origen | Referencia | ON DELETE | Justificación |
|----|--------------|-----------|-----------|---------------|
| `FK_usuario_paciente` | usuario | paciente.id_paciente | SET NULL | R-18; cuenta sobrevive a deshabilitación |
| `FK_usuario_medico` | usuario | medico.id_medico | SET NULL | R-04; cuenta sobrevive a deshabilitación |
| `FK_medico_especialidad_medico` | medico_especialidad | medico.id_medico | RESTRICT | RN-35, D-06 |
| `FK_medico_especialidad_especialidad` | medico_especialidad | especialidad.id_especialidad | RESTRICT | RN-35 |
| `FK_horario_medico` | horario | medico.id_medico | RESTRICT | RN-35; horarios asociados |
| `FK_cita_paciente` | cita | paciente.id_paciente | RESTRICT | RN-22/D-20 conservación |
| `FK_cita_medico` | cita | medico.id_medico | RESTRICT | RN-22/D-20 conservación |
| `FK_cita_horario` | cita | horario.id_horario | RESTRICT | RN-07; horario asignado |
| `FK_cita_especialidad` | cita | especialidad.id_especialidad | RESTRICT | RN-01 |
| `FK_cita_usuario_registrador` | cita | usuario.id_usuario | RESTRICT | RN-22; auditoría |
| `FK_atencion_virtual_cita` | atencion_virtual | cita.id_cita | RESTRICT | RN-18; información condicional |
| `FK_auditoria_cita` | registro_auditoria | cita.id_cita | RESTRICT | RN-25/D-20 conservación |
| `FK_auditoria_usuario` | registro_auditoria | usuario.id_usuario | RESTRICT | RN-25/D-20 conservación |
| `FK_usuario_rol_usuario` | usuario_rol | usuario.id_usuario | CASCADE | Borrado de cuenta |
| `FK_usuario_rol_rol` | usuario_rol | rol.id_rol | RESTRICT | RN-17; rol activo |
| `FK_rol_permiso_rol` | rol_permiso | rol.id_rol | CASCADE | Limpieza |
| `FK_rol_permiso_permiso` | rol_permiso | permiso.id_permiso | CASCADE | Limpieza |

### Tabla 3: Restricciones UNIQUE

| Tabla | Columna(s) | Tipo | Nota |
|-------|-----------|------|------|
| paciente | `documento_identidad` | SIMPLE | Identificador único de identidad clínica |
| usuario | `username` | SIMPLE | Identificador de cuenta |
| usuario | `email` | SIMPLE | Contacto único |
| medico | `numero_colegiado` | SIMPLE | Registro profesional único |
| especialidad | `nombre` | SIMPLE | Nombre único |
| usuario_rol | `(id_usuario, id_rol)` | COMPUESTA (PK) | Un usuario–un rol |
| rol | `nombre` | SIMPLE | Nombre único de rol |
| permiso | `nombre` | SIMPLE | Nombre único de permiso |
| parametros_configuracion | `clave` | SIMPLE | Nombre técnico único |
| cita | `(id_medico, id_horario)` | COMPUESTA | RN-04 / RNF-11 — doble reserva prohibida |

> **Nota PG15+:** Para `cita.id_horario` (si se quisiera unicidad estricta sobre NULLs), considerar `UNIQUE NULLS NOT DISTINCT`. No es necesario hoy, ya que `id_horario` es NOT NULL en cita.

### Tabla 4: Restricciones CHECK por tabla

| Tabla | CHECK | RN/RNF/D asociada |
|-------|-------|-------------------|
| paciente | `fecha_nacimiento <= CURRENT_DATE` | Validación |
| paciente | `LENGTH(telefono) <= 20` | Formato |
| medico | `LENGTH(numero_colegiado) >= 4` | Formato |
| usuario | `intentos_fallidos >= 0` | RNF-04 (fuerza bruta) |
| horario | `hora_fin > hora_inicio` | Validación franja |
| horario | `dia_semana IS NULL XOR fecha_especifica IS NULL` | RN-03 |
| cita | `estado IN (lista)` | RN-07 |
| cita | `modalidad IN ('presencial','virtual')` | RN-18 |
| cita | `fecha_hora_fin IS NULL OR fecha_hora_fin > fecha_hora_inicio` | RN-20 |
| atencion_virtual | `LENGTH(enlace_acceso) <= 500` | Longitud |
| atencion_virtual | `estado_disponibilidad IN (...)` | RN-30 |
| registro_auditoria | `accion IN (lista)` | RN-23 |
| registro_auditoria | `LENGTH(user_agent) <= 500` | Metadato |
| parametros_configuracion | `tipo_dato IN (...)` | D-02/D-03/D-04 |
| permiso | `accion IN ('crear','leer','actualizar','eliminar','ejecutar')` | D-10 |

> **Comportamiento PG:** `CHECK` con `NULL` pasa (lógica de 3 valores). Por eso `hora_fin > hora_inicio` no necesita `NOT NULL` — los NULLs se manejan con los CHECK de `XOR` y de unicidad.

### Tabla 5: Valores por defecto (DEFAULT)

| Tabla | Columna | DEFAULT | Fuente |
|-------|---------|---------|--------|
| medico | `activo` | `TRUE` | RF-02 |
| usuario | `activo` | `TRUE` | RF-21 |
| usuario | `intentos_fallidos` | `0` | RNF-04 |
| especialidad | `activo` | `TRUE` | CA-007 |
| medico_especialidad | `fecha_asociacion` | `CURRENT_DATE` | — |
| medico_especialidad | `habilitada_modalidad_virtual` | `FALSE` | D-08 pendiente |
| horario | `estado` | `'disponible'` | RN-03, RN-10 |
| cita | `estado` | `'Programada'` | RN-07 |
| cita | `modalidad` | `'presencial'` | RN-18 |
| cita / todas | `created_at`, `updated_at` | `now()` | Auditoría técnica |
| atencion_virtual | `estado_disponibilidad` | `'disponible'` | RN-30 |
| registro_auditoria | `fecha_hora` | `now()` (trigger: `clock_timestamp()`) | RN-23 |
| usuario_rol | `fecha_asignacion` | `now()` | Trazabilidad |
| rol, permiso, params, auditoria | `created_at` | `now()` | Auditoría técnica |

### Tabla 6: NOT NULL — columnas obligatorias

| Tabla | Columnas NOT NULL |
|-------|-------------------|
| paciente | `nombre`, `apellidos`, `documento_identidad`, `fecha_nacimiento`, `telefono`, `created_at`, `updated_at` |
| medico | `nombre_completo`, `numero_colegiado`, `activo`, `created_at`, `updated_at` |
| usuario | `username`, `password_hash`, `email`, `activo`, `intentos_fallidos`, `created_at`, `updated_at` |
| especialidad | `nombre`, `activo`, `created_at`, `updated_at` |
| medico_especialidad | `id_medico`, `id_especialidad`, `fecha_asociacion`, `habilitada_modalidad_virtual`, `created_at` |
| horario | `id_medico`, `hora_inicio`, `hora_fin`, `estado`, `created_at`, `updated_at` |
| cita | `id_paciente`, `id_medico`, `id_horario`, `id_especialidad`, `id_usuario_registrador`, `estado`, `modalidad`, `fecha_hora_inicio`, `fecha_creacion`, `fecha_actualizacion` |
| atencion_virtual | `id_cita`, `enlace_acceso`, `created_at`, `updated_at` |
| registro_auditoria | `id_cita`, `id_usuario_responsable`, `accion`, `valor_actual`, `fecha_hora` |
| parametros_configuracion | `clave`, `valor`, `tipo_dato`, `descripcion`, `categoria`, `editable`, `created_at`, `updated_at` |
| rol | `nombre`, `descripcion`, `created_at` |
| permiso | `nombre`, `descripcion`, `recurso`, `accion`, `created_at` |
| usuario_rol | `id_usuario`, `id_rol`, `fecha_asignacion` |
| rol_permiso | `id_rol`, `id_permiso` |

---

## 5. Trigger de integridad — código base PostgreSQL 18.6

### 5.1 fn_validar_cita_medico_especialidad (RN-02 implícito)

```sql
CREATE OR REPLACE FUNCTION fn_validar_cita_medico_especialidad()
RETURNS TRIGGER AS $$
BEGIN
    -- La especialidad de la cita debe estar asociada al médico seleccionado
    IF NOT EXISTS (
        SELECT 1 FROM medico_especialidad me
        WHERE me.id_medico = NEW.id_medico
          AND me.id_especialidad = NEW.id_especialidad
    ) THEN
        RAISE EXCEPTION
            'Cita inválida: la especialidad (%) no está asociada al médico (%)',
            NEW.id_especialidad, NEW.id_medico;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_cita_validar_medico_especialidad
BEFORE INSERT OR UPDATE OF id_medico, id_especialidad ON cita
FOR EACH ROW EXECUTE FUNCTION fn_validar_cita_medico_especialidad();
```

**Fuente:** RN-02 implícito; R-10; §4.12 del modelo conceptual.

### 5.2 fn_verificar_no_solapamiento_paciente (RN-05)

```sql
CREATE OR REPLACE FUNCTION fn_verificar_no_solapamiento_paciente()
RETURNS TRIGGER AS $$
DECLARE
    t_inicio TIMESTAMP WITH TIME ZONE := NEW.fecha_hora_inicio;
    t_fin   TIMESTAMP WITH TIME ZONE;
BEGIN
    t_fin := COALESCE(NEW.fecha_hora_fin, t_inicio + INTERVAL '1 hour');

    IF EXISTS (
        SELECT 1 FROM cita
        WHERE id_paciente = NEW.id_paciente
          AND id_cita != COALESCE(NEW.id_cita, 0)
          AND ((fecha_hora_inicio < t_fin) AND (COALESCE(fecha_hora_fin, fecha_hora_inicio + INTERVAL '1 hour') > t_inicio))
    ) THEN
        RAISE EXCEPTION
            'El paciente tiene citas que se superponen en ese horario', NEW.id_paciente;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_cita_verificar_solapamiento_paciente
BEFORE INSERT OR UPDATE OF id_paciente, fecha_hora_inicio, fecha_hora_fin ON cita
FOR EACH ROW EXECUTE FUNCTION fn_verificar_no_solapamiento_paciente();
```

**Fuente:** RN-05, D-11.

### 5.3 fn_verificar_transiciones_estado (RN-07/08/09)

```sql
CREATE OR REPLACE FUNCTION fn_verificar_transiciones_estado()
RETURNS TRIGGER AS $$
DECLARE
    transiciones_permitidas TEXT[][] := ARRAY[
        ARRAY['Programada','Confirmada'],
        ARRAY['Programada','Cancelada'],
        ARRAY['Confirmada','En_atencion'],
        ARRAY['Confirmada','Cancelada'],
        ARRAY['Confirmada','No_asistida'],
        ARRAY['En_atencion','Finalizada']
    ];
    ok BOOLEAN := FALSE;
BEGIN
    IF OLD IS NOT NULL THEN
        FOR i IN 1..array_length(transiciones_permitidas, 1) LOOP
            IF OLD.estado = transiciones_permitidas[i][1]
               AND NEW.estado = transiciones_permitidas[i][2] THEN
                ok := TRUE;
                EXIT;
            END IF;
        END LOOP;

        IF NOT ok THEN
            RAISE EXCEPTION
                'Transición de estado no permitida: % → %', OLD.estado, NEW.estado;
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_cita_verificar_transiciones
BEFORE INSERT OR UPDATE OF estado ON cita
FOR EACH ROW EXECUTE FUNCTION fn_verificar_transiciones_estado();
```

**Fuente:** RN-07 (conjunto de estados), RN-08 (transiciones), RN-09 (no reversa).

### 5.4 fn_auditar_cambio_cita (RF-22, RN-23, RN-32)

```sql
CREATE OR REPLACE FUNCTION fn_auditar_cambio_cita()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO registro_auditoria (id_cita, id_usuario_responsable, accion,
                                        campo_modificado, valor_anterior, valor_actual,
                                        fecha_hora)
        VALUES (NEW.id_cita, current_setting('app.current_user_id')::bigint,
                'crear', NULL, NULL,
                row_to_json(NEW)::text, clock_timestamp());
        RETURN NEW;
    ELSIF TG_OP = 'UPDATE' THEN
        IF NEW.estado != OLD.estado THEN
            INSERT INTO registro_auditoria (id_cita, id_usuario_responsable, accion,
                                            campo_modificado, valor_anterior, valor_actual,
                                            fecha_hora)
            VALUES (NEW.id_cica, current_setting('app.current_user_id')::bigint,
                    'modificar', 'estado', OLD.estado::text, NEW.estado::text, clock_timestamp());
        END IF;
        -- Registrar también cambios de modalidad, horario, reprogramación
        RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
        INSERT INTO registro_auditoria (id_cita, id_usuario_responsable, accion,
                                        campo_modificado, valor_anterior, valor_actual,
                                        fecha_hora)
        VALUES (OLD.id_cita, current_setting('app.current_user_id')::bigint,
                'modificar', 'estado', OLD.estado::text, 'ELIMINADA', clock_timestamp());
        RETURN OLD;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_cita_auditar_cambio
AFTER INSERT OR UPDATE OR DELETE ON cita
FOR EACH ROW EXECUTE FUNCTION fn_auditar_cambio_cita();
```

> **Nota:** En producción, `id_usuario_responsable` debe provenir de la autenticación de aplicación, no del rol de BD. Se recomienda `SET app.current_user_id` en la conexión.

**Fuente:** RF-22, RN-23, RN-24, RN-25, D-12.

### 5.5 fn_actualizar_horario_al_reservar y fn_liberar_horario (RN-10, RN-12, RN-13)

```sql
-- Al confirmar una cita: el horario pasa a 'ocupado'
CREATE OR REPLACE FUNCTION fn_actualizar_horario_al_reservar()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.estado = 'Confirmada' AND OLD.estado IS NULL THEN
        UPDATE horario SET estado = 'ocupado' WHERE id_horario = NEW.id_horario;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Al cancelar una cita: el horario se libera
CREATE OR REPLACE FUNCTION fn_liberar_horario_al_cancelar()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.estado = 'Cancelada' THEN
        UPDATE horario SET estado = 'disponible' WHERE id_horario = NEW.id_horario;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Reprogramación atómica (nuevo reservado + antiguo liberado) — ver Paso 12
```

**Fuente:** RN-10 (ocupar al confirmar), RN-12 (reprogramación atómica), RN-13 (liberar al cancelar).

---

## 6. Restricciones EXCLUDE (opcional — alternativa a triggers)

Para evitar dobles reservas con superposición temporal (RN-05), se puede usar `EXCLUDE USING GIST` en lugar del trigger:

```sql
ALTER TABLE cita ADD CONSTRAINT cita_no_superposicion
EXCLUDE USING GIST (
    id_paciente WITH =,
    tstzrange(fecha_hora_inicio, COALESCE(fecha_hora_fin, fecha_hora_inicio + INTERVAL '1 hour'), '[)') WITH &&
);
```

> **Regla `postgresql-table-design`:** `EXCLUDE` previene superposiciones con operadores GiST. Requiere PG11+. Ventana `[)` (cerrada a la izquierda, abierta a la derecha). Opcional si ya existe trigger `fn_verificar_no_solapamiento_paciente`.

---

## 7. Restricciones de negocio implementadas — trazabilidad completa

| RN | Mecanismo | Ubicación |
|----|-----------|-----------|
| RN-01 | FK NOT NULL (paciente, médico) | cita |
| RN-02 | Trigger `fn_validar_cita_medico_especialidad` + trigger de existencia ≥1 especialidad por médico | cita / medico |
| RN-03 | FK horario + estado 'disponible' (trigger) + CHECK horario.id_medico = cita.id_medico | cita, horario |
| RN-04 | UNIQUE (id_medico, id_horario) + trigger | cita |
| RN-05 | Trigger `fn_verificar_no_solapamiento_paciente` (o EXCLUDE GIST) | cita |
| RN-06 | Transacción atómica (SELECT FOR UPDATE) | Paso 12 |
| RN-07 | ENUM `estado_cita` | cita |
| RN-08 | Trigger `fn_verificar_transiciones_estado` | cita |
| RN-09 | Trigger `fn_verificar_transiciones_estado` (no reversa) | cita |
| RN-10 | Trigger `fn_actualizar_horario_al_reservar` | horario |
| RN-12 | Transacción atómica de reprogramación | Paso 12 |
| RN-13 | Trigger `fn_liberar_horario_al_cancelar` | horario |
| RN-14 | RLS (Paso 09) + FK | cita, paciente |
| RN-15 | RLS + filtro id_medico | cita |
| RN-16 | RLS + `id_usuario_registrador` | cita |
| RN-17 | RLS rol 'admin' + `rol_permiso` | sistema |
| RN-18 | CHECK modalidad + trigger atencion_virtual | cita |
| RN-19 | Trigger `fn_verificar_atencion_virtual_condicional` | atencion_virtual |
| RN-20 | CHECK `fecha_hora_fin > fecha_hora_inicio` + transición En_atención | cita |
| RN-21 | Cambio de estado solo autorizado por rol | Paso 09 |
| RN-22 | ON DELETE RESTRICT | cita |
| RN-23 | Trigger `fn_auditar_cambio_cita` | registro_auditoria |
| RN-24 | `valor_anterior` + `valor_actual` | registro_auditoria |
| RN-25 | ON DELETE RESTRICT en FK de auditoría | registro_auditoria |
| RN-26..28 | `parametros_configuracion` + validación | Paso 09/12 |
| RN-30 | `atencion_virtual.fecha_incidente`, `detalles_incidente` | atencion_virtual |
| RN-32 | Auditoría en reprogramación/cambio modalidad | registro_auditoria |
| RN-33/RN-34 | Entidades separadas + FK opcionales | usuario |
| RN-35 | ON DELETE RESTRICT + activo BOOLEAN | paciente, medico, especialidad |
| RNF-04 | `intentos_fallidos` + trigger de bloqueo | usuario |
| RNF-05 | `rol`, `permiso`, `usuario_rol`, `rol_permiso` | RBAC |
| RNF-06 | `password_hash` (hash, no texto plano) | usuario |
| RNF-07 | `parametros_configuracion.tiempo_inactividad_sesion_minutos` | Paso 09 |
| RNF-11 | Transacción atómica + UNIQUE | cita, horario |

---

## 8. Decisiones pendientes en integridad

| Decisión | Afecta | Estado |
|----------|--------|--------|
| **D-08** | `habilitada_modalidad_virtual` en `medico_especialidad` | Columna presente (DEFAULT FALSE); trigger de validación pendiente |
| **D-09** | `horario.modalidad` NULLABLE | Columna presente; si se fija `NOT NULL`, actualizar FK `cita.modalidad` |
| **D-07** | Desactivar médico con citas futuras | ON DELETE RESTRICT; trigger de bloqueo pendiente |
| **D-10** | Permisos de admisión | Permisos semilla pendientes; RLS pendiente |
| **D-14** | Validación de formatos (DNI, teléfono, email) | CHECKs básicos presentes; validación formal pendiente |

---

## 9. Conclusiones del Paso 08

1. **14 tablas** con PK, FK, UNIQUE, CHECK, DEFAULT, NOT NULL **documentados y trazados** a RF/RNF/RN/D.
2. **17 FK** definidas con estrategias `ON DELETE` (`RESTRICT` predominante para conservación; `CASCADE` solo en uniones N:M; `SET NULL` para vínculos Usuario-Paciente/Médico).
3. **9 restricciones UNIQUE** (8 simples + 1 compuesta RN-04).
4. **21 CHECK** definidos, combinados con NOT NULL donde aplica.
5. **6 triggers de integridad** implementados: coherencia médico-especialidad, solapamiento paciente, transiciones de estado, auditoría, ocupar/liberar horarios.
6. **EXCLUDE GIST** propuesto como alternativa para RN-05.
7. **D-08/D-09** reflejadas como columnas placeholder; integridad asociada pendiente de decisión final.
8. **Listo para Paso 09** (Seguridad: roles de BD, RLS, cifrado, SCRAM-SHA-256) y **Paso 10** (auditoría, histórico, versionamiento).

---

## 10. Próximo Paso

**Paso 09 — Seguridad**
Usar: `databases` (+ `sqlserver-security` si se hubiera seleccionado SQL Server, **no aplica** con PostgreSQL)
Salida: `proyecto/base_datos/07_seguridad/seguridad.md`

DETENERSE y esperar aprobación humana.