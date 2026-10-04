-- ============================================================
-- pruebas_bd.sql
-- Paso 19 — Pruebas de Base de Datos
-- Sistema de Gestión de Citas y Atención Virtual
-- Hospital Boliviano Español
--
-- IMPORTANTE:
-- - Ejecutar sobre hospital_citas_dev.
-- - Todas las pruebas se ejecutan dentro de una transacción.
-- - Al final se hace ROLLBACK, por lo que NO deja datos de prueba.
-- ============================================================

\set ON_ERROR_STOP on
\encoding UTF8

\echo ''
\echo '============================================================'
\echo ' PASO 19 - PRUEBAS AUTOMATICAS DE BASE DE DATOS'
\echo '============================================================'

BEGIN;

-- ------------------------------------------------------------
-- 1. Datos base de prueba
-- ------------------------------------------------------------

INSERT INTO paciente (
    nombre, apellidos, documento_identidad,
    fecha_nacimiento, telefono, correo_electronico
)
VALUES
    ('Paciente', 'Prueba Uno', 'TEST-P19-001',
     DATE '1995-01-10', '70000001', 'test.p19.1@example.local'),
    ('Paciente', 'Prueba Dos', 'TEST-P19-002',
     DATE '1990-02-20', '70000002', 'test.p19.2@example.local');

INSERT INTO medico (
    nombre_completo, numero_colegiado
)
VALUES (
    'Medico Prueba Paso 19',
    'TEST-COL-P19'
);

INSERT INTO especialidad (
    nombre, descripcion
)
VALUES
    ('Especialidad Prueba P19 A', 'Especialidad valida para pruebas'),
    ('Especialidad Prueba P19 B', 'Especialidad no asociada al medico');

INSERT INTO medico_especialidad (
    id_medico, id_especialidad
)
SELECT
    m.id_medico,
    e.id_especialidad
FROM medico m
JOIN especialidad e
  ON e.nombre = 'Especialidad Prueba P19 A'
WHERE m.numero_colegiado = 'TEST-COL-P19';

INSERT INTO horario (
    id_medico, fecha_especifica,
    hora_inicio, hora_fin, estado
)
SELECT
    m.id_medico,
    DATE '2030-01-15',
    TIME '09:00',
    TIME '10:00',
    'disponible'
FROM medico m
WHERE m.numero_colegiado = 'TEST-COL-P19';

INSERT INTO horario (
    id_medico, fecha_especifica,
    hora_inicio, hora_fin, estado
)
SELECT
    m.id_medico,
    DATE '2030-01-15',
    TIME '09:30',
    TIME '10:30',
    'disponible'
FROM medico m
WHERE m.numero_colegiado = 'TEST-COL-P19';

INSERT INTO horario (
    id_medico, fecha_especifica,
    hora_inicio, hora_fin, estado
)
SELECT
    m.id_medico,
    DATE '2030-01-15',
    TIME '11:00',
    TIME '12:00',
    'disponible'
FROM medico m
WHERE m.numero_colegiado = 'TEST-COL-P19';

INSERT INTO usuario (
    username, password_hash, email
)
VALUES (
    'test_paso19',
    'hash_solo_para_prueba',
    'test.paso19@example.local'
);

SELECT set_config(
    'app.current_user_id',
    (
        SELECT id_usuario::text
        FROM usuario
        WHERE username = 'test_paso19'
    ),
    true
);

\echo '[OK] Datos base de prueba creados'

-- ------------------------------------------------------------
-- 2. Prueba: creación de cita válida
-- ------------------------------------------------------------

INSERT INTO cita (
    id_paciente,
    id_horario,
    id_especialidad,
    id_usuario_registrador,
    estado,
    modalidad,
    fecha_hora_programada,
    observaciones
)
SELECT
    p.id_paciente,
    h.id_horario,
    e.id_especialidad,
    u.id_usuario,
    'Programada',
    'presencial',
    TIMESTAMPTZ '2030-01-15 09:00:00-04',
    'Cita valida de prueba Paso 19'
FROM paciente p
JOIN horario h
  ON h.fecha_especifica = DATE '2030-01-15'
 AND h.hora_inicio = TIME '09:00'
JOIN medico m
  ON m.id_medico = h.id_medico
 AND m.numero_colegiado = 'TEST-COL-P19'
JOIN especialidad e
  ON e.nombre = 'Especialidad Prueba P19 A'
JOIN usuario u
  ON u.username = 'test_paso19'
WHERE p.documento_identidad = 'TEST-P19-001';

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM cita c
        JOIN paciente p ON p.id_paciente = c.id_paciente
        WHERE p.documento_identidad = 'TEST-P19-001'
          AND c.fecha_hora_programada = TIMESTAMPTZ '2030-01-15 09:00:00-04'
          AND c.estado = 'Programada'
    ) THEN
        RAISE EXCEPTION 'PRUEBA FALLIDA: no se creó la cita válida';
    END IF;

    RAISE NOTICE '[OK] Creación de cita válida';
END
$$;

-- ------------------------------------------------------------
-- 3. Prueba: auditoría e histórico al crear cita
-- ------------------------------------------------------------

DO $$
DECLARE
    v_id_cita BIGINT;
BEGIN
    SELECT c.id_cita
      INTO v_id_cita
    FROM cita c
    JOIN paciente p ON p.id_paciente = c.id_paciente
    WHERE p.documento_identidad = 'TEST-P19-001'
      AND c.fecha_hora_programada = TIMESTAMPTZ '2030-01-15 09:00:00-04';

    IF NOT EXISTS (
        SELECT 1
        FROM registro_auditoria
        WHERE id_cita = v_id_cita
          AND accion = 'crear'
    ) THEN
        RAISE EXCEPTION 'PRUEBA FALLIDA: no se registró auditoría de creación';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM cita_historico
        WHERE id_cita = v_id_cita
          AND accion = 'crear'
    ) THEN
        RAISE EXCEPTION 'PRUEBA FALLIDA: no se registró histórico de creación';
    END IF;

    RAISE NOTICE '[OK] Auditoría e histórico de creación';
END
$$;

-- ------------------------------------------------------------
-- 4. Prueba: especialidad no asociada al médico
-- ------------------------------------------------------------

DO $$
BEGIN
    BEGIN
        INSERT INTO cita (
            id_paciente,
            id_horario,
            id_especialidad,
            id_usuario_registrador,
            estado,
            modalidad,
            fecha_hora_programada,
            observaciones
        )
        SELECT
            p.id_paciente,
            h.id_horario,
            e.id_especialidad,
            u.id_usuario,
            'Programada',
            'presencial',
            TIMESTAMPTZ '2030-01-15 11:00:00-04',
            'Debe fallar por especialidad'
        FROM paciente p
        JOIN horario h
          ON h.fecha_especifica = DATE '2030-01-15'
         AND h.hora_inicio = TIME '11:00'
        JOIN medico m
          ON m.id_medico = h.id_medico
         AND m.numero_colegiado = 'TEST-COL-P19'
        JOIN especialidad e
          ON e.nombre = 'Especialidad Prueba P19 B'
        JOIN usuario u
          ON u.username = 'test_paso19'
        WHERE p.documento_identidad = 'TEST-P19-002';

        RAISE EXCEPTION
            'PRUEBA FALLIDA: se permitió una especialidad no asociada';
    EXCEPTION
        WHEN OTHERS THEN
            IF SQLERRM LIKE 'Cita inválida: especialidad % no asociada al médico %' THEN
                RAISE NOTICE '[OK] Especialidad no asociada fue bloqueada';
            ELSE
                RAISE;
            END IF;
    END;
END
$$;

-- ------------------------------------------------------------
-- 5. Prueba: doble reserva activa
-- ------------------------------------------------------------

DO $$
BEGIN
    BEGIN
        INSERT INTO cita (
            id_paciente,
            id_horario,
            id_especialidad,
            id_usuario_registrador,
            estado,
            modalidad,
            fecha_hora_programada,
            observaciones
        )
        SELECT
            p.id_paciente,
            h.id_horario,
            e.id_especialidad,
            u.id_usuario,
            'Confirmada',
            'presencial',
            TIMESTAMPTZ '2030-01-15 09:00:00-04',
            'Debe fallar por doble reserva'
        FROM paciente p
        JOIN horario h
          ON h.fecha_especifica = DATE '2030-01-15'
         AND h.hora_inicio = TIME '09:00'
        JOIN medico m
          ON m.id_medico = h.id_medico
         AND m.numero_colegiado = 'TEST-COL-P19'
        JOIN especialidad e
          ON e.nombre = 'Especialidad Prueba P19 A'
        JOIN usuario u
          ON u.username = 'test_paso19'
        WHERE p.documento_identidad = 'TEST-P19-002';

        RAISE EXCEPTION
            'PRUEBA FALLIDA: se permitió una doble reserva activa';
    EXCEPTION
        WHEN OTHERS THEN
            IF SQLERRM LIKE 'El horario % ya posee una reserva activa para %' THEN
                RAISE NOTICE '[OK] Doble reserva activa fue bloqueada';
            ELSE
                RAISE;
            END IF;
    END;
END
$$;

-- ------------------------------------------------------------
-- 6. Prueba: solapamiento de citas del mismo paciente
-- ------------------------------------------------------------

DO $$
BEGIN
    BEGIN
        INSERT INTO cita (
            id_paciente,
            id_horario,
            id_especialidad,
            id_usuario_registrador,
            estado,
            modalidad,
            fecha_hora_programada,
            observaciones
        )
        SELECT
            p.id_paciente,
            h.id_horario,
            e.id_especialidad,
            u.id_usuario,
            'Programada',
            'presencial',
            TIMESTAMPTZ '2030-01-15 09:30:00-04',
            'Debe fallar por solapamiento'
        FROM paciente p
        JOIN horario h
          ON h.fecha_especifica = DATE '2030-01-15'
         AND h.hora_inicio = TIME '09:30'
        JOIN medico m
          ON m.id_medico = h.id_medico
         AND m.numero_colegiado = 'TEST-COL-P19'
        JOIN especialidad e
          ON e.nombre = 'Especialidad Prueba P19 A'
        JOIN usuario u
          ON u.username = 'test_paso19'
        WHERE p.documento_identidad = 'TEST-P19-001';

        RAISE EXCEPTION
            'PRUEBA FALLIDA: se permitió solapamiento del paciente';
    EXCEPTION
        WHEN OTHERS THEN
            IF SQLERRM LIKE 'El paciente % tiene otra cita activa que se superpone' THEN
                RAISE NOTICE '[OK] Solapamiento del paciente fue bloqueado';
            ELSE
                RAISE;
            END IF;
    END;
END
$$;

-- ------------------------------------------------------------
-- 7. Prueba: transición válida de estados
-- ------------------------------------------------------------

UPDATE cita c
SET estado = 'Confirmada'
FROM paciente p
WHERE p.id_paciente = c.id_paciente
  AND p.documento_identidad = 'TEST-P19-001'
  AND c.fecha_hora_programada = TIMESTAMPTZ '2030-01-15 09:00:00-04';

UPDATE cita c
SET estado = 'En_atencion',
    fecha_hora_inicio_atencion = TIMESTAMPTZ '2030-01-15 09:05:00-04'
FROM paciente p
WHERE p.id_paciente = c.id_paciente
  AND p.documento_identidad = 'TEST-P19-001'
  AND c.fecha_hora_programada = TIMESTAMPTZ '2030-01-15 09:00:00-04';

UPDATE cita c
SET estado = 'Finalizada',
    fecha_hora_fin_atencion = TIMESTAMPTZ '2030-01-15 09:45:00-04'
FROM paciente p
WHERE p.id_paciente = c.id_paciente
  AND p.documento_identidad = 'TEST-P19-001'
  AND c.fecha_hora_programada = TIMESTAMPTZ '2030-01-15 09:00:00-04';

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM cita c
        JOIN paciente p ON p.id_paciente = c.id_paciente
        WHERE p.documento_identidad = 'TEST-P19-001'
          AND c.estado = 'Finalizada'
          AND c.fecha_hora_inicio_atencion IS NOT NULL
          AND c.fecha_hora_fin_atencion IS NOT NULL
    ) THEN
        RAISE EXCEPTION
            'PRUEBA FALLIDA: transición válida no llegó a Finalizada';
    END IF;

    RAISE NOTICE '[OK] Transiciones válidas de estado';
END
$$;

-- ------------------------------------------------------------
-- 8. Prueba: transición inválida Programada -> Finalizada
-- ------------------------------------------------------------

INSERT INTO cita (
    id_paciente,
    id_horario,
    id_especialidad,
    id_usuario_registrador,
    estado,
    modalidad,
    fecha_hora_programada,
    observaciones
)
SELECT
    p.id_paciente,
    h.id_horario,
    e.id_especialidad,
    u.id_usuario,
    'Programada',
    'virtual',
    TIMESTAMPTZ '2030-01-15 11:00:00-04',
    'Cita para prueba de transición inválida'
FROM paciente p
JOIN horario h
  ON h.fecha_especifica = DATE '2030-01-15'
 AND h.hora_inicio = TIME '11:00'
JOIN medico m
  ON m.id_medico = h.id_medico
 AND m.numero_colegiado = 'TEST-COL-P19'
JOIN especialidad e
  ON e.nombre = 'Especialidad Prueba P19 A'
JOIN usuario u
  ON u.username = 'test_paso19'
WHERE p.documento_identidad = 'TEST-P19-002';

DO $$
BEGIN
    BEGIN
        UPDATE cita c
        SET estado = 'Finalizada',
            fecha_hora_inicio_atencion = TIMESTAMPTZ '2030-01-15 11:05:00-04',
            fecha_hora_fin_atencion = TIMESTAMPTZ '2030-01-15 11:45:00-04'
        FROM paciente p
        WHERE p.id_paciente = c.id_paciente
          AND p.documento_identidad = 'TEST-P19-002'
          AND c.fecha_hora_programada = TIMESTAMPTZ '2030-01-15 11:00:00-04';

        RAISE EXCEPTION
            'PRUEBA FALLIDA: se permitió Programada -> Finalizada';
    EXCEPTION
        WHEN OTHERS THEN
            IF SQLERRM LIKE 'Transición de estado no permitida:%' THEN
                RAISE NOTICE '[OK] Transición inválida fue bloqueada';
            ELSE
                RAISE;
            END IF;
    END;
END
$$;

-- ------------------------------------------------------------
-- 9. Prueba: parámetro de inactividad = 15
-- ------------------------------------------------------------

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM parametros_configuracion
        WHERE clave = 'tiempo_inactividad_sesion_minutos'
          AND valor = '15'
          AND tipo_dato = 'integer'
    ) THEN
        RAISE EXCEPTION
            'PRUEBA FALLIDA: parámetro de inactividad no coincide con 15 minutos';
    END IF;

    RAISE NOTICE '[OK] Parámetro de inactividad = 15 minutos';
END
$$;

-- ------------------------------------------------------------
-- 10. Resultado general
-- ------------------------------------------------------------

\echo ''
\echo '============================================================'
\echo ' RESULTADO'
\echo '============================================================'
\echo ' TODAS LAS PRUEBAS FUNCIONALES DEL PASO 19 FUERON APROBADAS'
\echo ' Los datos de prueba se revertiran con ROLLBACK'
\echo '============================================================'

ROLLBACK;
