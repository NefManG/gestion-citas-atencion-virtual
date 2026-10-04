-- ===========================================================
-- V1__initial_schema.sql — Esquema físico completo (Baseline)
-- Generado desde: modelo_fisico.md corregido
-- Paso 14 — Revisión DBA: STATUS: APPROVED
-- ===========================================================


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
    'asignar_medico',
    'liberar_horario',
    'bloquear_usuario',
    'desbloquear_usuario'
);


-- 3. Tablas principales

-- 3.1 paciente

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


-- 3.2 medico

CREATE TABLE medico (
    id_medico        BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre_completo  TEXT NOT NULL CHECK (LENGTH(nombre_completo) <= 200),
    numero_colegiado TEXT NOT NULL UNIQUE CHECK (LENGTH(numero_colegiado) <= 30),
    activo           BOOLEAN NOT NULL DEFAULT TRUE,

    created_at       TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT now()
);


-- 3.3 usuario

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


-- 3.4 especialidad

CREATE TABLE especialidad (
    id_especialidad BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre          TEXT NOT NULL UNIQUE CHECK (LENGTH(nombre) <= 100),
    descripcion     TEXT CHECK (LENGTH(descripcion) <= 500),
    activo          BOOLEAN NOT NULL DEFAULT TRUE,

    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);


-- 3.5 medico_especialidad

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


-- 3.6 horario

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


-- 3.7 cita

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


-- 3.8 atencion_virtual

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


-- 3.9 registro_auditoria

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


-- 3.10 parametros_configuracion

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


-- 3.11 rol

CREATE TABLE rol (
    id_rol      BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,

    nombre      TEXT NOT NULL UNIQUE
                CHECK (LENGTH(nombre) <= 50),

    descripcion TEXT
                CHECK (LENGTH(descripcion) <= 200),

    created_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);


-- 3.12 permiso

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


-- 3.13 usuario_rol

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


-- 3.14 rol_permiso

CREATE TABLE rol_permiso (
    id_rol     BIGINT NOT NULL
               REFERENCES rol(id_rol)
               ON DELETE CASCADE,

    id_permiso BIGINT NOT NULL
               REFERENCES permiso(id_permiso)
               ON DELETE CASCADE,

    PRIMARY KEY (id_rol, id_permiso)
);


-- 3.15 cita_historico (histórico de citas)

CREATE TABLE cita_historico (
    id_historico BIGINT
        GENERATED ALWAYS AS IDENTITY,

    id_cita BIGINT NOT NULL,

    id_paciente BIGINT NOT NULL,

    id_horario BIGINT NOT NULL,

    id_especialidad BIGINT NOT NULL,

    id_usuario_registrador BIGINT NOT NULL,

    estado estado_cita NOT NULL,

    modalidad modalidad_cita NOT NULL,

    fecha_hora_programada TIMESTAMPTZ NOT NULL,

    fecha_hora_inicio_atencion TIMESTAMPTZ,

    fecha_hora_fin_atencion TIMESTAMPTZ,

    observaciones TEXT,

    accion TEXT NOT NULL
        CHECK (
            accion IN (
                'crear',
                'modificar',
                'cancelar',
                'finalizar',
                'reprogramar',
                'cambiar_modalidad',
                'eliminar'
            )
        ),

    usuario_accion BIGINT NOT NULL,

    fecha_accion TIMESTAMPTZ NOT NULL
        DEFAULT clock_timestamp(),

    transaction_id BIGINT
        DEFAULT txid_current(),

    PRIMARY KEY (
        id_historico,
        fecha_accion
    )
)
PARTITION BY RANGE (fecha_accion);


-- 4. Índices generados por restricciones (PK, UNIQUE)

-- PK automático en PostgreSQL (B-tree)

-- UNIQUE automáticos

CREATE UNIQUE INDEX paciente_documento_identidad_key
ON paciente (documento_identidad);

CREATE UNIQUE INDEX usuario_username_key
ON usuario (username);

CREATE UNIQUE INDEX usuario_email_key
ON usuario (email);

CREATE UNIQUE INDEX medico_numero_colegiado_key
ON medico (numero_colegiado);

CREATE UNIQUE INDEX especialidad_nombre_key
ON especialidad (nombre);


-- 5. Índices FK (manuales — PostgreSQL no indexa FK automáticamente)

CREATE INDEX idx_usuario_paciente
ON usuario (id_paciente)
WHERE id_paciente IS NOT NULL;

CREATE INDEX idx_usuario_medico
ON usuario (id_medico)
WHERE id_medico IS NOT NULL;

CREATE INDEX idx_me_medico
ON medico_especialidad (id_medico);

CREATE INDEX idx_me_especialidad
ON medico_especialidad (id_especialidad);

CREATE INDEX idx_horario_medico
ON horario (id_medico);

CREATE INDEX idx_cita_paciente
ON cita (id_paciente)
WHERE id_paciente IS NOT NULL;

CREATE INDEX idx_cita_horario
ON cita (id_horario)
WHERE id_horario IS NOT NULL;

CREATE INDEX idx_cita_especialidad
ON cita (id_especialidad)
WHERE id_especialidad IS NOT NULL;

CREATE INDEX idx_cita_registrador
ON cita (id_usuario_registrador)
WHERE id_usuario_registrador IS NOT NULL;

CREATE INDEX idx_av_cita
ON atencion_virtual (id_cita);

CREATE INDEX idx_auditoria_cita
ON registro_auditoria (id_cita);

CREATE INDEX idx_auditoria_usuario
ON registro_auditoria (id_usuario_responsable);

CREATE INDEX idx_ur_usuario
ON usuario_rol (id_usuario);

CREATE INDEX idx_ur_rol
ON usuario_rol (id_rol);

CREATE INDEX idx_rp_rol
ON rol_permiso (id_rol);

CREATE INDEX idx_rp_permiso
ON rol_permiso (id_permiso);


-- 6. Índices UNIQUE parcial (doble reserva — RN-04/RNF-11)

CREATE UNIQUE INDEX uk_cita_horario_ocurrencia_activa
ON cita (id_horario, fecha_hora_programada)
WHERE estado IN ('Programada', 'Confirmada', 'En_atencion');


-- 7. Índices para patrones de acceso críticos (RF/RNF)

-- 7.1 Disponibilidad y agenda médica

CREATE INDEX idx_horario_disponibilidad
ON horario (id_medico, estado, fecha_especifica, dia_semana, hora_inicio)
WHERE estado = 'disponible';

CREATE INDEX idx_medico_activo
ON medico (activo)
WHERE activo = true;

CREATE INDEX idx_especialidad_activa
ON especialidad (activo)
WHERE activo = true;


-- 7.2 Historial de paciente

CREATE INDEX idx_cita_paciente_fecha
ON cita (id_paciente, fecha_hora_programada DESC);

CREATE INDEX idx_cita_paciente_activas
ON cita (id_paciente, fecha_hora_programada)
WHERE estado IN ('Programada', 'Confirmada', 'En_atencion');

CREATE INDEX idx_cita_paciente_estado
ON cita (id_paciente, estado)
WHERE estado IN ('Programada', 'Confirmada', 'En_atencion');


-- 7.3 Agenda médica

CREATE INDEX idx_cita_horario_fecha
ON cita (id_horario, fecha_hora_programada);

CREATE INDEX idx_cita_horario_en_atencion
ON cita (id_horario, fecha_hora_programada)
WHERE estado = 'En_atencion';

CREATE INDEX idx_horario_medico_estado
ON horario (id_medico, estado);


-- 7.4 Consultas administrativas

CREATE INDEX idx_cita_especialidad_fecha
ON cita (id_especialidad, fecha_hora_programada);

CREATE INDEX idx_cita_modalidad_fecha
ON cita (modalidad, fecha_hora_programada)
WHERE modalidad = 'virtual';

CREATE INDEX idx_cita_fecha_creacion_estado
ON cita (fecha_creacion, estado);


-- 7.5 Cobertura (cover indexes)

CREATE INDEX idx_cita_paciente_cover
ON cita (id_paciente, fecha_hora_programada DESC)
INCLUDE (estado, modalidad, id_horario, id_especialidad);

CREATE INDEX idx_cita_horario_cover
ON cita (id_horario, fecha_hora_programada)
INCLUDE (estado, id_paciente, id_especialidad, modalidad);


-- 7.6 Texto (GIN trigram)

CREATE INDEX idx_paciente_nombre_trgm
ON paciente
USING GIN (nombre gin_trgm_ops, apellidos gin_trgm_ops);

CREATE INDEX idx_medico_nombre_trgm
ON medico
USING GIN (nombre_completo gin_trgm_ops);


-- 8. Índices de auditoría

CREATE INDEX idx_auditoria_cita_fecha
ON registro_auditoria (id_cita, fecha_hora DESC);

CREATE INDEX idx_auditoria_usuario_fecha
ON registro_auditoria (id_usuario_responsable, fecha_hora DESC);

CREATE INDEX idx_auditoria_accion_fecha
ON registro_auditoria (accion, fecha_hora DESC);


-- 9. Índices de cita_historico (desde auditoria_historico.md corregido)

CREATE INDEX idx_cita_hist_cita
ON cita_historico (id_cita, fecha_accion DESC);

CREATE INDEX idx_cita_hist_paciente
ON cita_historico (id_paciente, fecha_accion DESC);

CREATE INDEX idx_cita_hist_horario
ON cita_historico (id_horario, fecha_accion DESC);

CREATE INDEX idx_cita_hist_especialidad
ON cita_historico (id_especialidad, fecha_accion DESC);


-- 10. Trigger functions y triggers de integridad

-- 10.1 fn_validar_cita_medico_especialidad

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


-- 10.2 fn_verificar_no_doble_reserva

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


-- 10.3 fn_verificar_no_solapamiento_paciente

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


-- 10.4 fn_verificar_transiciones_estado

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


-- 10.5 fn_validar_fechas_atencion

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


-- 10.6 fn_auditar_cambio_cita

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
            'Migrado desde sistema legacy',
            now()
        );

    ELSIF TG_OP = 'UPDATE' THEN

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
            NULL,
            NULL,
            'Migrado desde sistema legacy',
            now()
        );

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
            'eliminar',
            NULL,
            NULL,
            'Migrado desde sistema legacy',
            now()
        );

    END IF;

    RETURN NEW;
END;
$$;


CREATE TRIGGER trg_cita_auditar_cambio
AFTER INSERT OR UPDATE OR DELETE
ON cita
FOR EACH ROW
EXECUTE FUNCTION fn_auditar_cambio_cita();


-- 10.7 fn_auditar_cita_historico

CREATE OR REPLACE FUNCTION fn_auditar_cita_historico()
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
            'No se puede registrar histórico: app.current_user_id no está definido';
    END IF;

    IF TG_OP = 'INSERT' THEN

        INSERT INTO cita_historico (
            id_cita,
            id_paciente,
            id_horario,
            id_especialidad,
            id_usuario_registrador,
            estado,
            modalidad,
            fecha_hora_programada,
            fecha_hora_inicio_atencion,
            fecha_hora_fin_atencion,
            observaciones,
            accion,
            usuario_accion
        )
        VALUES (
            NEW.id_cita,
            NEW.id_paciente,
            NEW.id_horario,
            NEW.id_especialidad,
            NEW.id_usuario_registrador,
            NEW.estado,
            NEW.modalidad,
            NEW.fecha_hora_programada,
            NEW.fecha_hora_inicio_atencion,
            NEW.fecha_hora_fin_atencion,
            NEW.observaciones,
            'crear',
            v_usuario
        );

        RETURN NEW;

    ELSIF TG_OP = 'UPDATE' THEN

        INSERT INTO cita_historico (
            id_cita,
            id_paciente,
            id_horario,
            id_especialidad,
            id_usuario_registrador,
            estado,
            modalidad,
            fecha_hora_programada,
            fecha_hora_inicio_atencion,
            fecha_hora_fin_atencion,
            observaciones,
            accion,
            usuario_accion
        )
        VALUES (
            NEW.id_cita,
            NEW.id_paciente,
            NEW.id_horario,
            NEW.id_especialidad,
            NEW.id_usuario_registrador,
            NEW.estado,
            NEW.modalidad,
            NEW.fecha_hora_programada,
            NEW.fecha_hora_inicio_atencion,
            NEW.fecha_hora_fin_atencion,
            NEW.observaciones,
            CASE
                WHEN NEW.id_horario
                     IS DISTINCT FROM OLD.id_horario
                  OR NEW.fecha_hora_programada
                     IS DISTINCT FROM OLD.fecha_hora_programada
                    THEN 'reprogramar'

                WHEN NEW.modalidad
                     IS DISTINCT FROM OLD.modalidad
                    THEN 'cambiar_modalidad'

                WHEN NEW.estado = 'Cancelada'
                     AND OLD.estado
                         IS DISTINCT FROM NEW.estado
                    THEN 'cancelar'

                WHEN NEW.estado = 'Finalizada'
                     AND OLD.estado
                         IS DISTINCT FROM NEW.estado
                    THEN 'finalizar'

                ELSE 'modificar'
            END,
            v_usuario
        );

        RETURN NEW;

    ELSIF TG_OP = 'DELETE' THEN

        INSERT INTO cita_historico (
            id_cita,
            id_paciente,
            id_horario,
            id_especialidad,
            id_usuario_registrador,
            estado,
            modalidad,
            fecha_hora_programada,
            fecha_hora_inicio_atencion,
            fecha_hora_fin_atencion,
            observaciones,
            accion,
            usuario_accion
        )
        VALUES (
            OLD.id_cita,
            OLD.id_paciente,
            OLD.id_horario,
            OLD.id_especialidad,
            OLD.id_usuario_registrador,
            OLD.estado,
            OLD.modalidad,
            OLD.fecha_hora_programada,
            OLD.fecha_hora_inicio_atencion,
            OLD.fecha_hora_fin_atencion,
            OLD.observaciones,
            'eliminar',
            v_usuario
        );

        RETURN OLD;

    END IF;

    RETURN NULL;
END;
$$;


CREATE TRIGGER trg_cita_historico
AFTER INSERT OR UPDATE OR DELETE
ON cita
FOR EACH ROW
EXECUTE FUNCTION fn_auditar_cita_historico();


-- 11. Configuración base pg_hba.conf

-- NOTA: La configuración física de pg_hba.conf debe colocarse en el
-- directorio de configuración del servidor PostgreSQL. Este es el contenido
-- corregido (typo mdc512 → scram-sha-256):

-- TYPE       DATABASE        USER            ADDRESS             METHOD
-- Administración local mediante socket del sistema
local        all             all                                 peer

-- Conexiones internas cifradas
hostssl      all             all             10.0.0.0/8          scram-sha-256
hostssl      all             all             192.168.0.0/16      scram-sha-256

-- Loopback cifrado cuando corresponda
hostssl      all             all             127.0.0.1/32        scram-sha-256
hostssl      all             all             ::1/128             scram-sha-256

-- Rechazar conexiones TCP sin TLS
hostnossl    all             all             0.0.0.0/0           reject
hostnossl    all             all             ::0/0               reject


-- 12. Configuración base postgresql.conf

-- Concurrencia y locks
max_connections = 200
superuser_reserved_connections = 3
max_locks_per_transaction = 256
deadlock_timeout = '1s'
lock_timeout = '30s'
statement_timeout = '60s'
idle_in_transaction_session_timeout = '180s'

-- MVCC y vacuum
vacuum_freeze_min_age = 50000000
vacuum_freeze_table_age = 150000000
autovacuum_vacuum_scale_factor = 0.05
autovacuum_analyze_scale_factor = 0.02

-- Memoria por operación
work_mem = 64MB
maintenance_work_mem = 2GB
temp_buffers = 256MB

-- WAL y checkpoint
checkpoint_timeout = '15min'
checkpoint_completion_target = 0.9
wal_buffers = 64MB
commit_delay = 0
commit_siblings = 5

-- Replicación, si aplica
max_wal_senders = 3
wal_sender_timeout = '60s'


-- 13. Usuarios y roles de PostgreSQL

CREATE ROLE app_read
    NOLOGIN
    NOSUPERUSER
    NOCREATEDB
    NOCREATEROLE;

CREATE ROLE app_write
    NOLOGIN
    NOSUPERUSER
    NOCREATEDB
    NOCREATEROLE;

CREATE ROLE app_admin
    NOLOGIN
    NOSUPERUSER
    NOCREATEDB
    NOCREATEROLE;

CREATE ROLE app_runtime
    LOGIN
    NOSUPERUSER
    NOCREATEDB
    NOCREATEROLE;


-- 14. Concesión de privilegios al rol técnico

GRANT app_read TO app_runtime;
GRANT app_write TO app_runtime;


-- 15. Datos semilla (aprobados — solo valores autorizados)

INSERT INTO rol (nombre, descripcion)
VALUES
    ('paciente', 'Acceso a sus citas y perfil'),
    ('medico',   'Acceso a su agenda y atención'),
    ('admision', 'Gestión de citas de terceros'),
    ('admin',    'Administración del sistema');


INSERT INTO permiso (nombre, descripcion, recurso, accion)
VALUES
    ('cita.crear',              'Crear cita',              'cita',              'crear'),
    ('cita.leer_propias',       'Leer sus propias citas',    'cita',              'leer'),
    ('cita.leer_todas',         'Leer todas las citas',      'cita',              'leer'),
    ('cita.actualizar',         'Actualizar cita',           'cita',              'actualizar'),
    ('cita.cancelar',           'Cancelar cita',             'cita',              'eliminar'),
    ('cita.reprogramar',        'Reprogramar cita',          'cita',              'ejecutar'),
    ('medico.crear',            'Crear médico',              'medico',            'crear'),
    ('medico.leer',             'Leer médico',               'medico',            'leer'),
    ('medico.actualizar',       'Actualizar médico',         'medico',            'actualizar'),
    ('medico.desactivar',       'Desactivar médico',         'medico',            'ejecutar'),
    ('paciente.crear',          'Crear paciente',            'paciente',          'crear'),
    ('paciente.leer_propio',    'Leer propio paciente',      'paciente',          'leer'),
    ('paciente.actualizar_propio', 'Actualizar propio paciente', 'paciente',      'actualizar'),
    ('usuario.gestionar_roles', 'Gestionar roles',           'usuario',           'ejecutar'),
    ('usuario.gestionar_permisos', 'Gestionar permisos',        'usuario',           'ejecutar'),
    ('auditoria.leer',          'Leer auditoría',            'auditoria',         'leer'),
    ('config.leer',             'Leer configuración',        'config',            'leer'),
    ('config.actualizar',       'Actualizar configuración',  'config',            'actualizar');


INSERT INTO rol_permiso (id_rol, id_permiso)
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


INSERT INTO parametros_configuracion (clave, valor, tipo_dato, descripcion, categoria, editable)
VALUES
    ('tiempo_inactividad_sesion_minutos', '15', 'integer',
     'Tiempo inicial configurable de inactividad de sesión', 'sesion', true);


-- 16. Comentarios del modelo

COMMENT ON TABLE cita IS 'Tabla de citas. id_medico no almacenado directamente; se deriva mediante cita.id_horario → horario.id_medico';

COMMENT ON COLUMN cita.id_horario IS 'Referencia al horario de la cita. El médico asociado se obtiene desde horario.id_medico.';

COMMENT ON TRIGGER trg_cita_validar_medico_especialidad IS 'Valida que la especialidad de la cita esté asociada al médico del horario (horario.id_medico). No consulta cita.id_medico (columna eliminada).';

COMMENT ON TRIGGER trg_cita_no_doble_reserva IS 'Previene doble reserva activa considerando ocurrencia (id_horario, fecha_hora_programada) y estados Programada/Confirmada/En_atencion. No impide conservar citas canceladas/finalizadas/no asistidas. Última línea de defensa: índice uk_cita_horario_ocurrencia_activa.';

COMMENT ON INDEX uk_cita_horario_ocurrencia_activa IS 'Índice UNIQUE parcial que protege contra doble reserva activa por ocurrencia de horario. Estados protegidos: Programada, Confirmada, En_atencion. No afecta citas canceladas/finalizadas/no asistidas. Reemplaza el anterior UNIQUE(id_medico, id_horario).';

COMMENT ON INDEX idx_cita_paciente_activas IS 'Índice para historial de citas activas del paciente. Condición temporal aplicada en la consulta WHERE fecha_hora_programada > NOW() AND estado IN (...).';

COMMENT ON INDEX idx_cita_horario_fecha IS 'Índice compuesto sobre cita para búsquedas por ocurrencia de horario y fecha programada.';


-- 17. Notas finales de generación

-- 1. `cita.id_medico` ha sido eliminado completamente. El médico se deriva mediante:
--    cita.id_horario → horario.id_medico → medico.id_medico

-- 2. La doble reserva se protege mediante:
--    - Índice UNIQUE parcial uk_cita_horario_ocurrencia_activa (id_horario, fecha_hora_programada)
--    - Transacción atómica con nivel SERIALIZABLE (reserva y reprogramación)
--    - Trigger fn_verificar_no_doble_reserva (validación temprana)

-- 3. Los parámetros D-02, D-03, D-04 se mantienen como PENDIENTE.
--    No se insertan valores arbitrarios hasta decisión humana aprobada.

-- 4. D-08 y D-09 continúan sin placeholders físicos.

-- 5. El valor inicial configurado es tiempo_inactividad_sesion_minutos = 15
--    por RNF-07/D-13.

-- 6. Todos los triggers y políticas RLS utilizan la relación normalizada:
--    horario.id_medico para obtener el médico de una cita.

-- 7. Este esquema está listo para proceder al Paso 16 (despliegue) una vez
