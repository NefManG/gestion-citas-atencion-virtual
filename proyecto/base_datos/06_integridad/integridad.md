# Informe de Integridad — Paso 08

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español  
**Generado:** Paso 08 — Integridad  
**Agente utilizado:** `database-engineer`  
**Skill utilizado:** `database-schema-designer` + `postgresql-table-design`  
**Workflow:** `02_database_workflow`  
**Fuente principal:** `modelo_fisico.md` corregido + resultado de normalización del Paso 05  
**Estado:** Corregido manualmente después del Paso 14 — Revisión DBA. Pendiente de nueva validación DBA.

---

# 1. Objetivo del Paso 08

Documentar las restricciones de integridad del modelo físico PostgreSQL, incluyendo:

- claves primarias;
- claves foráneas;
- nulabilidad;
- restricciones `UNIQUE`;
- restricciones `CHECK`;
- valores `DEFAULT`;
- reglas de integridad entre tablas;
- reglas de negocio;
- triggers de validación;
- trazabilidad RF/RNF/RN/D.

Este documento incorpora las correcciones derivadas del:

- Paso 05 — Normalización;
- Paso 14 — Revisión DBA.

## Corrección principal incorporada

La tabla:

`cita`

ya no contiene:

`id_medico`.

El médico se obtiene mediante:

`cita.id_horario`

→

`horario.id_medico`

Por tanto, todas las reglas de integridad Médico–Cita y Médico–Especialidad deben utilizar el médico propietario del horario.

---

# 2. Principios de integridad utilizados

| Tipo | Uso en PostgreSQL |
|---|---|
| PK | Identificación única de cada fila |
| FK | Integridad referencial entre tablas |
| UNIQUE | Evitar duplicados cuando la regla lo exige |
| CHECK | Validaciones locales de atributos |
| NOT NULL | Campos obligatorios |
| DEFAULT | Valores iniciales aprobados |
| Trigger | Reglas que involucran varias tablas o transiciones |
| Índice UNIQUE parcial | Defensa de unicidad condicionada por estado |
| Transacción | Operaciones que deben ejecutarse de forma atómica |

## Regla importante

Un `CHECK` debe utilizarse para condiciones evaluables dentro de la misma fila.

Las reglas que dependen de otras tablas deben implementarse mediante:

- FK;
- trigger;
- función;
- restricción especializada;
- o lógica transaccional.

---

# 3. Restricciones por tabla

# 3.1 paciente

| Restricción | Definición | Fuente |
|---|---|---|
| PK | `id_paciente` | RF-01 |
| NOT NULL | `nombre`, `apellidos`, `documento_identidad`, `fecha_nacimiento`, `telefono` | RF-01 |
| UNIQUE | `documento_identidad` | RF-01 |
| CHECK | `fecha_nacimiento <= CURRENT_DATE` | Validación |
| CHECK | longitudes de campos | D-14 parcial |
| DEFAULT | `created_at`, `updated_at` → `now()` | Auditoría técnica |

### Reglas

- Cada paciente debe tener un documento de identidad único.
- La fecha de nacimiento no puede ser futura.
- Los formatos definitivos permanecen sujetos a D-14.
- Un paciente puede existir sin cuenta de usuario.

---

# 3.2 medico

| Restricción | Definición | Fuente |
|---|---|---|
| PK | `id_medico` | RF-02 |
| NOT NULL | `nombre_completo`, `numero_colegiado`, `activo` | RF-02 |
| UNIQUE | `numero_colegiado` | RF-02 |
| DEFAULT | `activo = TRUE` | RF-02 |
| DEFAULT | timestamps → `now()` | Auditoría técnica |

### RN-02

Cada médico debe poseer al menos una especialidad activa.

Esta regla no puede resolverse únicamente mediante una FK.

Debe validarse mediante una regla de existencia dentro del conjunto:

`medico_especialidad`.

---

# 3.3 usuario

| Restricción | Definición | Fuente |
|---|---|---|
| PK | `id_usuario` | RF-21 |
| NOT NULL | `username`, `password_hash`, `email`, `activo`, `intentos_fallidos` | RF-21, RNF-06 |
| UNIQUE | `username` | RF-21 |
| UNIQUE | `email` | RF-21 |
| UNIQUE opcional | `id_paciente` | Usuario–Paciente 0..1 |
| UNIQUE opcional | `id_medico` | Usuario–Médico 0..1 |
| FK | `id_paciente → paciente.id_paciente` | RN-14 |
| FK | `id_medico → medico.id_medico` | RN-15 |
| CHECK | `intentos_fallidos >= 0` | Seguridad |
| DEFAULT | `activo = TRUE` | RF-21 |
| DEFAULT | `intentos_fallidos = 0` | Seguridad |

### Usuario–Paciente

`usuario.id_paciente`

es nullable y único.

### Usuario–Médico

`usuario.id_medico`

es nullable y único.

### RNF-06

La tabla almacena exclusivamente:

`password_hash`.

No se almacena la contraseña original.

---

# 3.4 especialidad

| Restricción | Definición | Fuente |
|---|---|---|
| PK | `id_especialidad` | RF-03 |
| NOT NULL | `nombre`, `activo` | RF-03 |
| UNIQUE | `nombre` | RF-03 |
| DEFAULT | `activo = TRUE` | RF-03 |

---

# 3.5 medico_especialidad

| Restricción | Definición | Fuente |
|---|---|---|
| PK compuesta | `(id_medico, id_especialidad)` | RN-02 |
| FK | `id_medico → medico.id_medico` | RN-02 |
| FK | `id_especialidad → especialidad.id_especialidad` | RN-02 |
| NOT NULL | `id_medico`, `id_especialidad` | RN-02 |
| DEFAULT | `fecha_asociacion = CURRENT_DATE` | Operativo |

### D-08

No existe:

`habilitada_modalidad_virtual`.

D-08 continúa pendiente.

No debe existir una columna física placeholder mientras no exista decisión aprobada.

---

# 3.6 horario

| Restricción | Definición | Fuente |
|---|---|---|
| PK | `id_horario` | RF-05 |
| FK | `id_medico → medico.id_medico` | RF-05 |
| NOT NULL | `id_medico`, `hora_inicio`, `hora_fin`, `estado` | RF-05 |
| CHECK | `hora_fin > hora_inicio` | Integridad temporal |
| CHECK | `dia_semana BETWEEN 1 AND 7` cuando exista | RF-05 |
| CHECK | `dia_semana` XOR `fecha_especifica` | Modelo de horario |
| DEFAULT | `estado = 'disponible'` | RN-03 |

### Dependencia funcional

Cada horario pertenece exactamente a un médico:

`id_horario → id_medico`

Esta dependencia debe ser utilizada por todas las reglas relacionadas con el médico de una cita.

### D-09

No existe:

`horario.modalidad`.

D-09 continúa pendiente.

---

# 3.7 cita

La tabla `cita` corregida contiene:

- `id_cita`;
- `id_paciente`;
- `id_horario`;
- `id_especialidad`;
- `id_usuario_registrador`;
- `estado`;
- `modalidad`;
- `fecha_hora_programada`;
- `fecha_hora_inicio_atencion`;
- `fecha_hora_fin_atencion`;
- timestamps técnicos;
- observaciones.

## Restricciones

| Restricción | Definición | Fuente |
|---|---|---|
| PK | `id_cita` | RF-09 |
| FK | `id_paciente → paciente.id_paciente` | RN-01 |
| FK | `id_horario → horario.id_horario` | RN-03 |
| FK | `id_especialidad → especialidad.id_especialidad` | RN-01 |
| FK | `id_usuario_registrador → usuario.id_usuario` | RF-10 |
| NOT NULL | `id_paciente` | RN-01 |
| NOT NULL | `id_horario` | RN-03 |
| NOT NULL | `id_especialidad` | RN-01 |
| NOT NULL | `id_usuario_registrador` | RF-10 |
| NOT NULL | `estado` | RN-07 |
| NOT NULL | `modalidad` | RN-18 |
| NOT NULL | `fecha_hora_programada` | RF-09 |
| DEFAULT | `estado = 'Programada'` | RN-07 |
| DEFAULT | `modalidad = 'presencial'` | RN-18 |

## Eliminación de id_medico

No existe:

`cita.id_medico`.

Tampoco existe la FK:

`cita.id_medico → medico.id_medico`.

La relación Médico–Cita se deriva mediante:

`cita.id_horario`

→

`horario.id_medico`.

---

## Regla Médico–Especialidad

Para cada cita debe verificarse que:

`horario.id_medico`

junto con:

`cita.id_especialidad`

formen una asociación existente en:

`medico_especialidad`.

Formalmente:

`(horario.id_medico, cita.id_especialidad)`

debe existir en:

`medico_especialidad(id_medico, id_especialidad)`.

---

## Doble reserva

Se elimina:

`UNIQUE(id_medico, id_horario)`.

También se evita:

`UNIQUE(id_horario)` absoluto.

La regla correcta es:

> No puede existir simultáneamente más de una cita activa incompatible para la misma ocurrencia de un horario.

La ocurrencia de un horario recurrente se identifica mediante:

`id_horario + fecha_hora_programada`.

Estados considerados como reserva activa:

- `Programada`;
- `Confirmada`;
- `En_atencion`.

Estados que no bloquean nuevas reservas futuras:

- `Cancelada`;
- `Finalizada`;
- `No_asistida`.

La defensa definitiva será un índice UNIQUE parcial en el Paso 11, complementado por transacción atómica en el Paso 12.

Ejemplo conceptual:

```sql
CREATE UNIQUE INDEX uk_cita_horario_ocurrencia_activa
ON cita (id_horario, fecha_hora_programada)
WHERE estado IN (
    'Programada',
    'Confirmada',
    'En_atencion'
);
```

Este índice se documenta aquí como regla de integridad, pero su diseño definitivo corresponde al Paso 11.

---

## Fechas reales de atención

Cuando exista:

`fecha_hora_fin_atencion`

debe cumplirse:

```text
fecha_hora_fin_atencion > fecha_hora_inicio_atencion
```

Si la cita se encuentra en estado:

`Finalizada`

deben existir:

- `fecha_hora_inicio_atencion`;
- `fecha_hora_fin_atencion`.

---

# 3.8 atencion_virtual

| Restricción | Definición | Fuente |
|---|---|---|
| PK/FK | `id_cita → cita.id_cita` | RN-18 |
| NOT NULL | `id_cita`, `enlace_acceso`, `estado_disponibilidad` | RN-18 |
| CHECK | longitud de URL | D-01 |
| CHECK | estado de disponibilidad válido | RN-30 |

### Cardinalidad

`Cita 1 : 0..1 Atención Virtual`

### Integridad condicional

Cuando:

`cita.modalidad = 'virtual'`

debe existir la información necesaria para atención virtual.

Cuando:

`cita.modalidad = 'presencial'`

no se requiere `atencion_virtual`.

La validación completa se realiza transaccionalmente.

---

# 3.9 registro_auditoria

| Restricción | Definición | Fuente |
|---|---|---|
| PK | `id_registro` | RF-22 |
| FK | `id_cita → cita.id_cita` | RN-23 |
| FK | `id_usuario_responsable → usuario.id_usuario` | RN-23 |
| NOT NULL | `id_cita` | RN-23 |
| NOT NULL | `id_usuario_responsable` | RN-23 |
| NOT NULL | `accion` | RN-23 |
| NOT NULL | `valor_actual` | RN-24 |
| NOT NULL | `fecha_hora` | RN-23 |
| DEFAULT | `fecha_hora = now()` | RN-23 |

### Conservación

Las FK utilizan `ON DELETE RESTRICT`.

Una cita con registros de auditoría no debe eliminarse físicamente.

Los registros de auditoría deben conservarse durante el período requerido.

---

# 3.10 parametros_configuracion

| Restricción | Definición |
|---|---|
| PK | `id_parametro` |
| UNIQUE | `clave` |
| NOT NULL | `clave`, `valor`, `tipo_dato`, `descripcion`, `categoria`, `editable` |
| CHECK | dominio permitido de `tipo_dato` |
| DEFAULT | `editable = TRUE` |

### D-02, D-03 y D-04

Los siguientes parámetros existen conceptualmente:

- `tiempo_min_cancelacion_minutos`;
- `tiempo_min_reprogramacion_minutos`;
- `tolerancia_no_asistida_minutos`;
- `anticipacion_maxima_dias`.

Sus valores continúan:

`PENDIENTE`.

No deben existir valores hardcodeados sin decisión humana.

### RNF-07 / D-13

Se conserva:

`tiempo_inactividad_sesion_minutos = 15`

como valor inicial configurable.

---

# 3.11 rol

| Restricción | Definición |
|---|---|
| PK | `id_rol` |
| UNIQUE | `nombre` |
| NOT NULL | `nombre` |

Roles identificados:

- paciente;
- medico;
- admision;
- admin.

D-10 continúa pendiente respecto del alcance exacto de admisión.

---

# 3.12 permiso

| Restricción | Definición |
|---|---|
| PK | `id_permiso` |
| UNIQUE | `nombre` |
| NOT NULL | `nombre`, `descripcion`, `recurso`, `accion` |
| CHECK | dominio permitido de `accion` |

---

# 3.13 usuario_rol

| Restricción | Definición |
|---|---|
| PK | `(id_usuario, id_rol)` |
| FK | `id_usuario → usuario.id_usuario` |
| FK | `id_rol → rol.id_rol` |
| FK opcional | `asignado_por → usuario.id_usuario` |
| NOT NULL | `id_usuario`, `id_rol` |
| DEFAULT | `fecha_asignacion = now()` |

---

# 3.14 rol_permiso

| Restricción | Definición |
|---|---|
| PK | `(id_rol, id_permiso)` |
| FK | `id_rol → rol.id_rol` |
| FK | `id_permiso → permiso.id_permiso` |
| NOT NULL | `id_rol`, `id_permiso` |

---

# 4. Resumen de claves foráneas

| Tabla origen | Columna | Referencia | ON DELETE |
|---|---|---|---|
| usuario | id_paciente | paciente.id_paciente | SET NULL |
| usuario | id_medico | medico.id_medico | SET NULL |
| medico_especialidad | id_medico | medico.id_medico | RESTRICT |
| medico_especialidad | id_especialidad | especialidad.id_especialidad | RESTRICT |
| horario | id_medico | medico.id_medico | RESTRICT |
| cita | id_paciente | paciente.id_paciente | RESTRICT |
| cita | id_horario | horario.id_horario | RESTRICT |
| cita | id_especialidad | especialidad.id_especialidad | RESTRICT |
| cita | id_usuario_registrador | usuario.id_usuario | RESTRICT |
| atencion_virtual | id_cita | cita.id_cita | RESTRICT |
| registro_auditoria | id_cita | cita.id_cita | RESTRICT |
| registro_auditoria | id_usuario_responsable | usuario.id_usuario | RESTRICT |
| usuario_rol | id_usuario | usuario.id_usuario | CASCADE |
| usuario_rol | id_rol | rol.id_rol | RESTRICT |
| usuario_rol | asignado_por | usuario.id_usuario | SET NULL |
| rol_permiso | id_rol | rol.id_rol | CASCADE |
| rol_permiso | id_permiso | permiso.id_permiso | CASCADE |

No existe:

`FK_cita_medico`.

---

# 5. Restricciones UNIQUE

| Tabla | Campo(s) |
|---|---|
| paciente | documento_identidad |
| medico | numero_colegiado |
| usuario | username |
| usuario | email |
| usuario | id_paciente cuando exista |
| usuario | id_medico cuando exista |
| especialidad | nombre |
| parametros_configuracion | clave |
| rol | nombre |
| permiso | nombre |
| medico_especialidad | PK compuesta |
| usuario_rol | PK compuesta |
| rol_permiso | PK compuesta |

## Cita

No existe:

```text
UNIQUE(id_medico, id_horario)
```

ni:

```text
UNIQUE(id_horario)
```

absoluto.

La unicidad necesaria es **condicional a una ocurrencia y a estados activos**.

---

# 6. Restricciones CHECK principales

| Tabla | CHECK |
|---|---|
| paciente | fecha_nacimiento no futura |
| usuario | intentos_fallidos >= 0 |
| horario | hora_fin > hora_inicio |
| horario | dia_semana entre 1 y 7 |
| horario | dia_semana XOR fecha_especifica |
| cita | estado válido |
| cita | modalidad válida |
| cita | fin real > inicio real |
| atencion_virtual | longitud de enlace |
| atencion_virtual | estado de disponibilidad |
| registro_auditoria | acción válida |
| parametros_configuracion | tipo_dato válido |
| permiso | acción válida |

---

# 7. Valores DEFAULT principales

| Tabla | Columna | DEFAULT |
|---|---|---|
| medico | activo | TRUE |
| usuario | activo | TRUE |
| usuario | intentos_fallidos | 0 |
| especialidad | activo | TRUE |
| medico_especialidad | fecha_asociacion | CURRENT_DATE |
| horario | estado | disponible |
| cita | estado | Programada |
| cita | modalidad | presencial |
| registro_auditoria | fecha_hora | now() |
| usuario_rol | fecha_asignacion | now() |

No existe DEFAULT para:

`habilitada_modalidad_virtual`

porque esa columna fue eliminada.

---

# 8. Trigger Médico–Especialidad corregido

## 8.1 fn_validar_cita_medico_especialidad

El trigger ya no utiliza:

`NEW.id_medico`.

Debe obtener el médico desde `horario`.

```sql
CREATE OR REPLACE FUNCTION fn_validar_cita_medico_especialidad()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
    v_id_medico BIGINT;
BEGIN
    SELECT h.id_medico
      INTO v_id_medico
      FROM horario h
     WHERE h.id_horario = NEW.id_horario;

    IF v_id_medico IS NULL THEN
        RAISE EXCEPTION
            'Cita inválida: el horario % no tiene un médico válido',
            NEW.id_horario;
    END IF;

    IF NOT EXISTS (
        SELECT 1
          FROM medico_especialidad me
         WHERE me.id_medico = v_id_medico
           AND me.id_especialidad = NEW.id_especialidad
    ) THEN
        RAISE EXCEPTION
            'Cita inválida: la especialidad % no está asociada al médico % del horario %',
            NEW.id_especialidad,
            v_id_medico,
            NEW.id_horario;
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_cita_validar_medico_especialidad
BEFORE INSERT OR UPDATE OF id_horario, id_especialidad
ON cita
FOR EACH ROW
EXECUTE FUNCTION fn_validar_cita_medico_especialidad();
```

### Fuente

RN-02, RN-03.

---

# 9. Validación de doble reserva

## 9.1 Validación temprana

Puede existir una función de validación temprana que compruebe si ya existe una reserva activa para la misma ocurrencia.

```sql
CREATE OR REPLACE FUNCTION fn_verificar_no_doble_reserva()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    IF NEW.estado IN ('Programada', 'Confirmada', 'En_atencion') THEN

        IF EXISTS (
            SELECT 1
              FROM cita c
             WHERE c.id_horario = NEW.id_horario
               AND c.fecha_hora_programada = NEW.fecha_hora_programada
               AND c.estado IN (
                    'Programada',
                    'Confirmada',
                    'En_atencion'
               )
               AND c.id_cita <> COALESCE(NEW.id_cita, 0)
        ) THEN
            RAISE EXCEPTION
                'El horario % ya posee una reserva activa para %',
                NEW.id_horario,
                NEW.fecha_hora_programada;
        END IF;

    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_cita_no_doble_reserva
BEFORE INSERT OR UPDATE OF
    id_horario,
    fecha_hora_programada,
    estado
ON cita
FOR EACH ROW
EXECUTE FUNCTION fn_verificar_no_doble_reserva();
```

## Importante

Este trigger mejora la validación funcional, pero **no constituye por sí solo la última línea de defensa ante concurrencia**.

La protección definitiva debe incluir:

- índice UNIQUE parcial;
- transacción atómica;
- mecanismo de concurrencia del Paso 12.

---

# 10. No superposición de citas del paciente

RN-05 establece que un paciente no debe mantener citas activas superpuestas.

Para calcular la duración programada se utiliza la duración del horario:

`hora_fin - hora_inicio`.

```sql
CREATE OR REPLACE FUNCTION fn_verificar_no_solapamiento_paciente()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
    v_fin_nueva TIMESTAMPTZ;
BEGIN

    IF NEW.estado NOT IN (
        'Programada',
        'Confirmada',
        'En_atencion'
    ) THEN
        RETURN NEW;
    END IF;

    SELECT
        NEW.fecha_hora_programada
        + (h.hora_fin - h.hora_inicio)
      INTO v_fin_nueva
      FROM horario h
     WHERE h.id_horario = NEW.id_horario;

    IF v_fin_nueva IS NULL THEN
        RAISE EXCEPTION
            'No se pudo determinar la duración del horario %',
            NEW.id_horario;
    END IF;

    IF EXISTS (
        SELECT 1
          FROM cita c
          JOIN horario h_existente
            ON h_existente.id_horario = c.id_horario
         WHERE c.id_paciente = NEW.id_paciente

           AND c.id_cita <> COALESCE(NEW.id_cita, 0)

           AND c.estado IN (
               'Programada',
               'Confirmada',
               'En_atencion'
           )

           AND c.fecha_hora_programada < v_fin_nueva

           AND (
               c.fecha_hora_programada
               + (
                   h_existente.hora_fin
                   - h_existente.hora_inicio
               )
           ) > NEW.fecha_hora_programada
    ) THEN

        RAISE EXCEPTION
            'El paciente % tiene otra cita activa que se superpone',
            NEW.id_paciente;

    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_cita_verificar_solapamiento_paciente
BEFORE INSERT OR UPDATE OF
    id_paciente,
    id_horario,
    fecha_hora_programada,
    estado
ON cita
FOR EACH ROW
EXECUTE FUNCTION fn_verificar_no_solapamiento_paciente();
```

### Fuente

RN-05, D-11.

---

# 11. Transiciones de estado

## fn_verificar_transiciones_estado

El trigger debe ejecutarse solamente cuando el estado cambia.

```sql
CREATE OR REPLACE FUNCTION fn_verificar_transiciones_estado()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN

    IF NEW.estado = OLD.estado THEN
        RETURN NEW;
    END IF;

    IF NOT (
           (OLD.estado = 'Programada'
            AND NEW.estado IN ('Confirmada', 'Cancelada'))

        OR (OLD.estado = 'Confirmada'
            AND NEW.estado IN (
                'En_atencion',
                'Cancelada',
                'No_asistida'
            ))

        OR (OLD.estado = 'En_atencion'
            AND NEW.estado = 'Finalizada')
    ) THEN

        RAISE EXCEPTION
            'Transición de estado no permitida: % → %',
            OLD.estado,
            NEW.estado;

    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_cita_verificar_transiciones
BEFORE UPDATE OF estado
ON cita
FOR EACH ROW
EXECUTE FUNCTION fn_verificar_transiciones_estado();
```

### Fuente

RN-07, RN-08, RN-09.

---

# 12. Integridad de fechas de atención

```sql
CREATE OR REPLACE FUNCTION fn_validar_fechas_atencion()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN

    IF NEW.fecha_hora_inicio_atencion IS NOT NULL
       AND NEW.fecha_hora_fin_atencion IS NOT NULL
       AND NEW.fecha_hora_fin_atencion
           <= NEW.fecha_hora_inicio_atencion THEN

        RAISE EXCEPTION
            'La fecha/hora de fin debe ser posterior al inicio';

    END IF;

    IF NEW.estado = 'Finalizada'
       AND (
           NEW.fecha_hora_inicio_atencion IS NULL
           OR NEW.fecha_hora_fin_atencion IS NULL
       ) THEN

        RAISE EXCEPTION
            'Una cita Finalizada debe registrar inicio y fin de atención';

    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_cita_validar_fechas_atencion
BEFORE INSERT OR UPDATE OF
    estado,
    fecha_hora_inicio_atencion,
    fecha_hora_fin_atencion
ON cita
FOR EACH ROW
EXECUTE FUNCTION fn_validar_fechas_atencion();
```

---

# 13. Auditoría de cambios de cita

El código anterior contenía el typo:

`NEW.id_cica`.

Se corrige a:

`NEW.id_cita`.

```sql
CREATE OR REPLACE FUNCTION fn_auditar_cambio_cita()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
    v_usuario BIGINT;
BEGIN

    v_usuario :=
        current_setting(
            'app.current_user_id',
            TRUE
        )::BIGINT;

    IF v_usuario IS NULL THEN
        RAISE EXCEPTION
            'No existe app.current_user_id para registrar auditoría';
    END IF;

    IF TG_OP = 'INSERT' THEN

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
            NEW.id_cita,
            v_usuario,
            'crear',
            NULL,
            NULL,
            row_to_json(NEW)::TEXT,
            clock_timestamp()
        );

        RETURN NEW;

    ELSIF TG_OP = 'UPDATE' THEN

        IF NEW.estado IS DISTINCT FROM OLD.estado THEN

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
                NEW.id_cita,
                v_usuario,
                'modificar',
                'estado',
                OLD.estado::TEXT,
                NEW.estado::TEXT,
                clock_timestamp()
            );

        END IF;

        IF NEW.id_horario IS DISTINCT FROM OLD.id_horario
           OR NEW.fecha_hora_programada
              IS DISTINCT FROM OLD.fecha_hora_programada THEN

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
                NEW.id_cita,
                v_usuario,
                'reprogramar',
                'horario',
                CONCAT(
                    OLD.id_horario,
                    ' / ',
                    OLD.fecha_hora_programada
                ),
                CONCAT(
                    NEW.id_horario,
                    ' / ',
                    NEW.fecha_hora_programada
                ),
                clock_timestamp()
            );

        END IF;

        IF NEW.modalidad IS DISTINCT FROM OLD.modalidad THEN

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
                NEW.id_cita,
                v_usuario,
                'cambiar_modalidad',
                'modalidad',
                OLD.modalidad::TEXT,
                NEW.modalidad::TEXT,
                clock_timestamp()
            );

        END IF;

        RETURN NEW;

    ELSIF TG_OP = 'DELETE' THEN

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
            OLD.id_cita,
            v_usuario,
            'modificar',
            NULL,
            row_to_json(OLD)::TEXT,
            'ELIMINADA',
            clock_timestamp()
        );

        RETURN OLD;

    END IF;

    RETURN NULL;
END;
$$;

CREATE TRIGGER trg_cita_auditar_cambio
AFTER INSERT OR UPDATE OR DELETE
ON cita
FOR EACH ROW
EXECUTE FUNCTION fn_auditar_cambio_cita();
```

### Nota

La política de conservación establece que las citas históricas normalmente no deben borrarse físicamente.

El bloque `DELETE` representa una defensa adicional si una operación administrativa autorizada llegara a ejecutarse.

---

# 14. Estado operativo del horario

El modelo conserva:

- disponible;
- reservado;
- ocupado.

La modificación del estado debe mantenerse coordinada con la operación de reserva.

## Reserva inicial

```sql
CREATE OR REPLACE FUNCTION fn_actualizar_horario_al_reservar()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN

    IF NEW.estado = 'Programada' THEN

        UPDATE horario
           SET estado = 'reservado',
               updated_at = now()
         WHERE id_horario = NEW.id_horario;

    ELSIF NEW.estado IN ('Confirmada', 'En_atencion') THEN

        UPDATE horario
           SET estado = 'ocupado',
               updated_at = now()
         WHERE id_horario = NEW.id_horario;

    END IF;

    RETURN NEW;
END;
$$;
```

## Liberación

La cancelación o reprogramación debe liberar la ocurrencia anterior de acuerdo con las reglas transaccionales.

La implementación definitiva pertenece al Paso 12, porque debe evitar condiciones de carrera.

No debe considerarse el trigger como sustituto del control transaccional.

---

# 15. Integridad de Atención Virtual

La relación:

`cita.modalidad ↔ atencion_virtual`

cruza tablas.

No puede implementarse mediante un `CHECK` simple de `cita`.

La validación debe realizarse mediante una función o dentro de la transacción correspondiente.

Reglas:

- si `modalidad = virtual`, debe existir información virtual;
- si `modalidad = presencial`, no es obligatoria.

D-01 continúa pendiente respecto del proveedor externo.

---

# 16. Índice parcial para doble reserva

La última línea de defensa de RN-04/RNF-11 debe estar en persistencia.

La estrategia propuesta para PostgreSQL es:

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

## Importante

Este índice:

- no bloquea una cita cancelada histórica;
- no bloquea una cita finalizada histórica;
- no bloquea una cita no asistida histórica;
- evita dos reservas activas sobre la misma ocurrencia.

La creación formal y análisis de rendimiento pertenece al Paso 11.

---

# 17. Concurrencia

El trigger:

`fn_verificar_no_doble_reserva()`

no es suficiente ante dos transacciones concurrentes.

RNF-11 exige combinar:

1. validación de disponibilidad;
2. transacción;
3. bloqueo/estrategia de concurrencia;
4. índice UNIQUE parcial como última línea de defensa.

La estrategia final corresponde al Paso 12.

---

# 18. Trazabilidad de reglas de negocio

| Regla | Implementación corregida |
|---|---|
| RN-01 | FK paciente, horario, especialidad y usuario registrador |
| RN-02 | Validación Médico–Especialidad obteniendo médico desde horario |
| RN-03 | `cita.id_horario` + `horario.id_medico` |
| RN-04 | Reserva activa única por ocurrencia |
| RN-05 | Trigger no superposición paciente |
| RN-06 | Transacción atómica Paso 12 |
| RN-07 | ENUM estado |
| RN-08 | Trigger de transiciones |
| RN-09 | Sin reversión desde estados terminales |
| RN-10 | Gestión operativa del horario |
| RN-12 | Reprogramación atómica |
| RN-13 | Liberación de horario |
| RN-14 | RLS paciente |
| RN-15 | Médico derivado mediante horario |
| RN-16 | Usuario registrador |
| RN-18 | modalidad cita |
| RN-19 | Integridad condicional atención virtual |
| RN-20 | Fechas reales de atención |
| RN-22 | Conservación + RESTRICT |
| RN-23 | Trigger auditoría |
| RN-24 | valor anterior/actual |
| RN-25 | conservación auditoría |
| RN-26 | parámetro cancelación/reprogramación pendiente |
| RN-27 | tolerancia pendiente |
| RN-28 | anticipación pendiente |
| RN-30 | datos de incidente virtual |
| RN-32 | auditoría reprogramación/modalidad |
| RN-33/34 | Médico ≠ Usuario |
| RN-35 | desactivación lógica / RESTRICT |
| RNF-05 | RBAC |
| RNF-06 | password_hash |
| RNF-07 | inactividad 15 min configurable |
| RNF-11 | transacción + índice parcial |

---

# 19. D-08 y D-09

## D-08

Continúa pendiente.

No existe:

`medico_especialidad.habilitada_modalidad_virtual`.

## D-09

Continúa pendiente.

No existe:

`horario.modalidad`.

La modalidad permanece en:

`cita.modalidad`.

---

# 20. D-02, D-03 y D-04

Continúan pendientes.

No deben existir valores arbitrarios como:

- 60 minutos;
- 15 minutos;
- 90 días;

salvo que posteriormente sean aprobados explícitamente.

La tabla de parámetros permite asignarlos cuando exista decisión humana.

---

# 21. Cambios realizados respecto de la versión anterior

## Cambio 1

Eliminado:

`cita.id_medico`.

---

## Cambio 2

Eliminada FK:

`cita.id_medico → medico.id_medico`.

---

## Cambio 3

Eliminada:

`UNIQUE(id_medico, id_horario)`.

---

## Cambio 4

No se crea:

`UNIQUE(id_horario)` absoluto.

---

## Cambio 5

La doble reserva se define por:

`id_horario + fecha_hora_programada + estado activo`.

---

## Cambio 6

El trigger Médico–Especialidad obtiene el médico desde:

`horario.id_medico`.

---

## Cambio 7

Se elimina:

`habilitada_modalidad_virtual`.

D-08 sigue pendiente.

---

## Cambio 8

Se elimina:

`horario.modalidad`.

D-09 sigue pendiente.

---

## Cambio 9

El trigger de auditoría corrige:

`NEW.id_cica`

por:

`NEW.id_cita`.

---

## Cambio 10

La validación de solapamiento utiliza:

`fecha_hora_programada`

y la duración del horario.

---

## Cambio 11

D-02, D-03 y D-04 permanecen sin valores hardcodeados.

---

# 22. Conclusiones del Paso 08 corregido

1. Se mantienen las 14 tablas físicas.

2. `cita.id_medico` fue eliminado.

3. La relación Médico–Cita se obtiene mediante:

   `cita.id_horario → horario.id_medico`.

4. La coherencia Médico–Especialidad utiliza:

   `(horario.id_medico, cita.id_especialidad)`.

5. Se elimina la restricción:

   `UNIQUE(id_medico, id_horario)`.

6. No se establece:

   `UNIQUE(id_horario)` absoluto.

7. La protección de doble reserva considera:

   `id_horario + fecha_hora_programada`

   únicamente sobre estados activos.

8. La protección definitiva combinará índice parcial y control transaccional.

9. Se conserva el historial de citas canceladas, finalizadas y no asistidas.

10. El trigger de no superposición del paciente se actualiza al modelo corregido.

11. El trigger de auditoría corrige el typo `NEW.id_cica`.

12. Las transiciones de estado se validan únicamente en `UPDATE`.

13. D-08 y D-09 permanecen pendientes sin placeholders físicos.

14. D-02, D-03 y D-04 permanecen sin valores arbitrarios.

15. El documento queda alineado con el modelo lógico y físico corregidos.

---

# 23. Estado del archivo

**Archivo:**

`proyecto/base_datos/06_integridad/integridad.md`

**Paso de origen:**

`Paso 08 — Integridad`

**Agente utilizado:**

`database-engineer`

**Skills utilizados:**

- `database-schema-designer`
- `postgresql-table-design`

**Corrección posterior:**

Propagación de:

- Paso 05 — Normalización;
- Paso 14 — Revisión DBA con `STATUS: CHANGES_REQUIRED`.

**Estado:**

Corregido manualmente y pendiente de nueva Revisión DBA.

---

# 24. Control de avance

Esta corrección no autoriza:

`Paso 15 — Generación SQL`.

Todavía deben corregirse:

- `07_seguridad/seguridad.md`;
- `08_auditoria_historico/auditoria_historico.md`;
- `10_indices_rendimiento/indices_rendimiento.md`;
- `11_transacciones_concurrencia/transacciones_concurrencia.md`;
- `12_migraciones/migraciones.md`.

Después debe volver a ejecutarse:

`Paso 14 — Revisión DBA`.

Solo puede avanzarse al Paso 15 cuando el informe termine con:

`STATUS: APPROVED`.

**NO generar SQL todavía.**

**DETENERSE y esperar nueva validación DBA.**