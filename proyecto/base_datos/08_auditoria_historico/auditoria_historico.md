# Informe de Auditoría, Histórico y Versionamiento — Paso 10

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español  
**Generado:** Paso 10 — Auditoría, histórico y versionamiento  
**Agente utilizado:** `database-engineer`  
**Skill utilizado:** `databases` + `postgresql-table-design`  
**Workflow:** `02_database_workflow`  
**DBMS:** PostgreSQL  
**Fuentes principales:** `modelo_fisico.md`, `06_integridad/integridad.md`, `07_seguridad/seguridad.md`  
**Estado:** Corregido manualmente después del Paso 14 — Revisión DBA. Pendiente de nueva validación DBA.

---

# 1. Objetivo del Paso 10

Diseñar y documentar la estrategia de:

- auditoría funcional;
- auditoría técnica;
- conservación histórica;
- versionamiento de información;
- retención;
- recuperación ante fallos;
- PITR;
- respaldo y restauración.

La estrategia debe preservar la trazabilidad de las operaciones críticas del sistema sin introducir nuevamente atributos eliminados durante la normalización.

---

# 2. Corrección principal incorporada

El Paso 05 determinó que:

`cita.id_medico`

era redundante debido a:

`id_horario → id_medico`.

Por tanto, el histórico de citas tampoco debe depender de una columna:

`cita.id_medico`.

La relación Médico–Cita continúa obteniéndose mediante:

`cita.id_horario`

→

`horario.id_medico`.

Cuando sea necesario reconstruir históricamente el médico asociado a un horario modificado, deberá utilizarse también:

`horario_historico`.

---

# 3. Separación de responsabilidades

El sistema distingue tres mecanismos diferentes.

## 3.1 Auditoría funcional

Tabla:

`registro_auditoria`

Registra eventos relevantes del negocio, entre ellos:

- creación de cita;
- modificación;
- cancelación;
- reprogramación;
- cambio de modalidad;
- cambio de estado;
- usuario responsable;
- valor anterior;
- valor posterior;
- fecha y hora.

---

## 3.2 Histórico de datos

Las tablas históricas permiten conservar versiones completas de determinados registros.

Principales tablas candidatas:

- `cita_historico`;
- `horario_historico`;
- `paciente_historico`;
- `medico_historico`.

---

## 3.3 Auditoría técnica

`pgAudit` y los logs de PostgreSQL registran actividad técnica como:

- DDL;
- modificaciones;
- cambios de privilegios;
- conexiones;
- actividad administrativa.

La auditoría técnica no sustituye a `registro_auditoria`.

---

# 4. Registro_Auditoria

Se conserva la estructura definida en el modelo físico.

```sql
CREATE TABLE registro_auditoria (
    id_registro BIGINT
        GENERATED ALWAYS AS IDENTITY
        PRIMARY KEY,

    id_cita BIGINT NOT NULL
        REFERENCES cita(id_cita)
        ON DELETE RESTRICT,

    id_usuario_responsable BIGINT NOT NULL
        REFERENCES usuario(id_usuario)
        ON DELETE RESTRICT,

    accion accion_auditoria NOT NULL,

    campo_modificado TEXT
        CHECK (LENGTH(campo_modificado) <= 100),

    valor_anterior TEXT,

    valor_actual TEXT NOT NULL,

    fecha_hora TIMESTAMPTZ NOT NULL
        DEFAULT now(),

    ip_origen INET,

    user_agent TEXT
        CHECK (LENGTH(user_agent) <= 500)
);
```

## Conservación

Los registros de auditoría:

- no se eliminan al cancelar una cita;
- no se eliminan al finalizar una cita;
- no se eliminan cuando la cita pasa a No asistida;
- deben conservarse durante el período mínimo establecido.

---

# 5. Índices de auditoría

La definición final corresponde al Paso 11.

Los índices previstos son:

```sql
CREATE INDEX idx_auditoria_cita_fecha
ON registro_auditoria (
    id_cita,
    fecha_hora DESC
);

CREATE INDEX idx_auditoria_usuario_fecha
ON registro_auditoria (
    id_usuario_responsable,
    fecha_hora DESC
);

CREATE INDEX idx_auditoria_accion_fecha
ON registro_auditoria (
    accion,
    fecha_hora DESC
);
```

Estos índices soportan:

- historial por cita;
- auditoría por usuario;
- búsquedas de operaciones críticas.

---

# 6. Particionamiento de Registro_Auditoria

El particionamiento se considera una **estrategia futura** y no una obligación inmediata del modelo.

Debe aplicarse únicamente cuando:

- el volumen real lo justifique;
- las pruebas de rendimiento lo recomienden;
- la administración por retención se beneficie de particiones temporales.

La estrategia candidata es:

```text
PARTITION BY RANGE (fecha_hora)
```

con particiones mensuales o anuales.

No se modifica el baseline físico únicamente por estimaciones no verificadas.

---

# 7. Histórico de Cita

`cita_historico` almacena versiones completas de una cita.

No contiene:

`id_medico`.

El médico se deriva mediante el horario correspondiente.

```sql
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
```

---

# 8. Por qué Cita_Historico no contiene id_medico

La tabla principal normalizada establece:

`cita.id_horario → horario.id_medico`.

Duplicar:

`id_medico`

dentro de:

`cita_historico`

volvería a introducir una copia del mismo dato.

Por tanto, se mantiene:

`id_horario`

como referencia histórica del horario utilizado.

Si se necesita conocer quién era el médico asociado al horario en un momento histórico determinado, se consulta:

`horario_historico`.

---

# 9. Índices de Cita_Historico

```sql
CREATE INDEX idx_cita_hist_cita
ON cita_historico (
    id_cita,
    fecha_accion DESC
);

CREATE INDEX idx_cita_hist_paciente
ON cita_historico (
    id_paciente,
    fecha_accion DESC
);

CREATE INDEX idx_cita_hist_horario
ON cita_historico (
    id_horario,
    fecha_accion DESC
);

CREATE INDEX idx_cita_hist_especialidad
ON cita_historico (
    id_especialidad,
    fecha_accion DESC
);
```

No existe:

```text
idx_cita_hist_medico
```

porque `cita_historico.id_medico` no existe.

---

# 10. Trigger de histórico de Cita

```sql
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
```

Trigger:

```sql
CREATE TRIGGER trg_cita_historico
AFTER INSERT OR UPDATE OR DELETE
ON cita
FOR EACH ROW
EXECUTE FUNCTION fn_auditar_cita_historico();
```

---

# 11. Eliminación física de Citas

Aunque existe soporte histórico para `DELETE`, las reglas del sistema indican que las citas históricas deben conservarse.

Por tanto, una cita:

- Finalizada;
- Cancelada;
- No asistida;

no debe eliminarse físicamente como parte del flujo normal.

La eliminación física debe considerarse una operación extraordinaria y controlada.

---

# 12. Histórico de Horario

La tabla `horario_historico` permite reconstruir el médico asociado a un horario en un momento determinado.

```sql
CREATE TABLE horario_historico (
    id_historico BIGINT
        GENERATED ALWAYS AS IDENTITY,

    id_horario BIGINT NOT NULL,

    id_medico BIGINT NOT NULL,

    dia_semana SMALLINT,

    fecha_especifica DATE,

    hora_inicio TIME WITHOUT TIME ZONE NOT NULL,

    hora_fin TIME WITHOUT TIME ZONE NOT NULL,

    estado estado_horario NOT NULL,

    accion TEXT NOT NULL
        CHECK (
            accion IN (
                'crear',
                'modificar',
                'eliminar'
            )
        ),

    usuario_accion BIGINT NOT NULL,

    fecha_accion TIMESTAMPTZ NOT NULL
        DEFAULT clock_timestamp(),

    PRIMARY KEY (
        id_historico,
        fecha_accion
    )
)
PARTITION BY RANGE (fecha_accion);
```

No existe:

`horario.modalidad`

porque D-09 continúa pendiente.

---

# 13. Reconstrucción histórica Médico–Cita

Para conocer el médico de una cita histórica se utiliza:

`cita_historico.id_horario`

junto con la versión correspondiente de:

`horario_historico`.

Conceptualmente:

```text
CITA_HISTORICO
       |
       | id_horario
       v
HORARIO_HISTORICO
       |
       | id_medico
       v
MEDICO
```

Esto mantiene la normalización sin perder trazabilidad histórica.

---

# 14. Histórico de Médico

```sql
CREATE TABLE medico_historico (
    id_historico BIGINT
        GENERATED ALWAYS AS IDENTITY,

    id_medico BIGINT NOT NULL,

    nombre_completo TEXT NOT NULL,

    numero_colegiado TEXT NOT NULL,

    activo BOOLEAN NOT NULL,

    accion TEXT NOT NULL
        CHECK (
            accion IN (
                'crear',
                'modificar',
                'eliminar'
            )
        ),

    usuario_accion BIGINT NOT NULL,

    fecha_accion TIMESTAMPTZ NOT NULL
        DEFAULT clock_timestamp(),

    PRIMARY KEY (
        id_historico,
        fecha_accion
    )
)
PARTITION BY RANGE (fecha_accion);
```

---

# 15. Histórico de Paciente

La versión anterior incluía un atributo:

`activo`

que no forma parte de la tabla principal `paciente`.

Se elimina.

También se utiliza el nombre correcto:

`correo_electronico`.

```sql
CREATE TABLE paciente_historico (
    id_historico BIGINT
        GENERATED ALWAYS AS IDENTITY,

    id_paciente BIGINT NOT NULL,

    nombre TEXT NOT NULL,

    apellidos TEXT NOT NULL,

    documento_identidad TEXT NOT NULL,

    fecha_nacimiento DATE NOT NULL,

    telefono TEXT NOT NULL,

    correo_electronico TEXT,

    accion TEXT NOT NULL
        CHECK (
            accion IN (
                'crear',
                'modificar',
                'eliminar'
            )
        ),

    usuario_accion BIGINT NOT NULL,

    fecha_accion TIMESTAMPTZ NOT NULL
        DEFAULT clock_timestamp(),

    PRIMARY KEY (
        id_historico,
        fecha_accion
    )
)
PARTITION BY RANGE (fecha_accion);
```

---

# 16. Creación de Particiones Históricas

Las tablas históricas pueden utilizar particiones mensuales por:

`fecha_accion`.

Ejemplo conceptual:

```sql
CREATE TABLE cita_historico_2026_10
PARTITION OF cita_historico

FOR VALUES FROM (
    '2026-10-01 00:00:00+00'
)

TO (
    '2026-11-01 00:00:00+00'
);
```

Las particiones futuras deben crearse mediante un proceso administrativo controlado.

---

# 17. Partición DEFAULT

Puede utilizarse una partición por defecto para evitar pérdida de registros si falta una partición mensual.

Ejemplo:

```sql
CREATE TABLE cita_historico_default
PARTITION OF cita_historico
DEFAULT;
```

Debe monitorearse.

La presencia de datos en la partición `DEFAULT` debe generar una alerta operativa.

---

# 18. Retención histórica

Requisito:

> Los registros históricos y de auditoría deben conservarse al menos durante cinco años.

Por tanto:

- no se eliminarán datos antes del período mínimo;
- el archivado no significa eliminación automática;
- la eliminación posterior deberá obedecer una política aprobada;
- cualquier operación destructiva requerirá autorización.

No se establece un período adicional arbitrario de seis meses.

---

# 19. Archivado

Después del período mínimo puede evaluarse:

- almacenamiento frío;
- almacenamiento inmutable;
- compresión;
- WORM;
- repositorio externo autorizado.

El proceso deberá:

1. verificar integridad;
2. generar evidencia de exportación;
3. confirmar restaurabilidad;
4. autorizar la eliminación del origen;
5. registrar la operación.

No se ejecutará:

`DROP TABLE`

automáticamente.

---

# 20. Auditoría Inmutable

Los usuarios normales no deben poseer:

- `UPDATE`;
- `DELETE`;

sobre:

`registro_auditoria`.

Los históricos tampoco deben modificarse mediante las operaciones normales de la aplicación.

El acceso debe seguir el principio de mínimo privilegio.

---

# 21. Auditoría Funcional

La tabla:

`registro_auditoria`

registra cambios relevantes.

Entre otros:

- estado;
- horario;
- modalidad;
- cancelación;
- reprogramación;
- finalización.

Los datos deben incluir:

- usuario responsable;
- fecha/hora;
- acción;
- valor anterior;
- valor actual.

---

# 22. Auditoría Técnica con pgAudit

`pgAudit` se utiliza para eventos técnicos.

Configuración conceptual:

```conf
shared_preload_libraries = 'pgaudit'

pgaudit.log = 'write,ddl,role'

pgaudit.log_catalog = off

pgaudit.log_parameter = off
```

La configuración debe evitar registrar innecesariamente datos sensibles.

---

# 23. Separación Auditoría Funcional / pgAudit

`registro_auditoria`

responde:

> ¿Quién cambió una cita y qué cambió?

`pgAudit`

responde:

> ¿Qué actividad técnica se ejecutó sobre PostgreSQL?

Son mecanismos complementarios.

---

# 24. PITR — Point-in-Time Recovery

PITR protege ante:

- corrupción;
- errores administrativos;
- operaciones incorrectas;
- pérdida parcial;
- fallos graves.

PITR se basa en:

- backups;
- WAL;
- archivado continuo.

---

# 25. WAL Archiving

Configuración base conceptual:

```conf
archive_mode = on

archive_command =
    'pgbackrest --stanza=hospital archive-push %p'

archive_timeout = '60s'

wal_level = replica
```

`wal_level = replica`

es suficiente para replicación física y PITR.

Si otro artefacto aprobado requiere replicación lógica, puede utilizarse:

```text
wal_level = logical
```

sin afectar el objetivo de PITR.

---

# 26. wal_keep_size

No se utiliza:

`wal_keep_segments`

porque corresponde a configuraciones antiguas de PostgreSQL.

Se utiliza:

```conf
wal_keep_size = '1GB'
```

El valor definitivo debe dimensionarse según:

- tasa de generación de WAL;
- infraestructura;
- réplica;
- ventana de recuperación.

---

# 27. Backup con pgBackRest

Ejemplo conceptual:

```ini
[hospital]

pg1-path=/var/lib/postgresql/data

repo1-path=/var/lib/pgbackrest

repo1-retention-full=7

process-max=4

compress-type=zst
```

El repositorio debe protegerse mediante:

- permisos restrictivos;
- cifrado del almacenamiento;
- control administrativo;
- copia fuera del servidor primario.

---

# 28. Política de Backups

La periodicidad exacta debe comprobar que se cumplen los objetivos:

- RPO;
- RTO;
- disponibilidad.

Ejemplo de estrategia:

- backup completo periódico;
- backup diferencial o incremental;
- archivado continuo de WAL;
- verificación automática;
- restauraciones de prueba.

---

# 29. Objetivos RPO y RTO

Los requisitos del sistema establecen:

```text
RPO ≤ 60 minutos
RTO ≤ 120 minutos
```

Por tanto:

| Métrica | Objetivo |
|---|---|
| RPO | ≤ 60 minutos |
| RTO | ≤ 120 minutos |
| Conservación funcional | ≥ 5 años |

El diseño de backup debe probarse contra estos valores.

---

# 30. Verificación de Backup

Un backup no debe considerarse válido únicamente porque el comando terminó correctamente.

Debe realizarse:

```text
BACKUP
   ↓
VALIDACIÓN
   ↓
RESTORE DE PRUEBA
   ↓
VERIFICACIÓN
```

Debe comprobarse:

- restauración;
- consistencia;
- tiempo de recuperación;
- disponibilidad de WAL;
- integridad de los datos.

---

# 31. Ejemplo de Restauración PITR

Ejemplo conceptual:

```bash
pgbackrest \
    --stanza=hospital \
    --type=time \
    --target="2026-10-01 08:30:00+00" \
    restore
```

La fecha del ejemplo es ilustrativa.

No constituye una fecha operativa fija.

---

# 32. Consultas Históricas de Cita

## Historial completo

```sql
SELECT
    ch.fecha_accion,
    ch.accion,
    ch.estado,
    ch.modalidad,
    ch.fecha_hora_programada,
    ch.fecha_hora_inicio_atencion,
    ch.fecha_hora_fin_atencion,
    ch.id_horario,
    ch.id_especialidad
FROM cita_historico ch
WHERE ch.id_cita = :id_cita
ORDER BY ch.fecha_accion DESC;
```

---

# 33. Médico de una versión histórica

No se consulta:

`cita_historico.id_medico`.

Debe relacionarse el horario histórico.

Conceptualmente:

```sql
SELECT
    ch.id_cita,
    ch.fecha_accion,
    hh.id_medico
FROM cita_historico ch
JOIN LATERAL (
    SELECT hhist.id_medico
    FROM horario_historico hhist
    WHERE hhist.id_horario = ch.id_horario
      AND hhist.fecha_accion <= ch.fecha_accion
    ORDER BY hhist.fecha_accion DESC
    LIMIT 1
) hh ON TRUE
WHERE ch.id_cita = :id_cita;
```

Esto permite reconstruir el médico correspondiente al horario en el momento histórico.

---

# 34. Estado AS OF de una Cita

```sql
SELECT *
FROM cita_historico
WHERE id_cita = :id_cita
  AND fecha_accion <= :fecha_consulta
ORDER BY fecha_accion DESC
LIMIT 1;
```

---

# 35. Versionamiento de Catálogos

Las tablas:

- `especialidad`;
- `rol`;
- `permiso`;

cambian con menor frecuencia.

No se crea obligatoriamente una tabla genérica nueva solamente por conveniencia.

Las modificaciones relevantes pueden ser auditadas mediante:

- `pgAudit`;
- auditoría administrativa;
- migraciones versionadas.

Si posteriormente existe un requisito de histórico funcional para estos catálogos, podrá diseñarse una estrategia específica.

---

# 36. Histórico y Datos Sensibles

Los históricos pueden contener información personal.

Por tanto, deben protegerse mediante:

- RLS cuando corresponda;
- permisos mínimos;
- cifrado del almacenamiento;
- logs controlados;
- acceso administrativo restringido.

Una tabla histórica no debe considerarse menos sensible que la tabla principal.

---

# 37. Reprogramación

Cuando una cita es reprogramada deben conservarse:

- horario anterior;
- fecha/hora anterior;
- nuevo horario;
- nueva fecha/hora;
- usuario responsable;
- momento de modificación.

Esto puede reconstruirse mediante:

`cita_historico`

y:

`registro_auditoria`.

---

# 38. Cancelación

Cancelar una cita:

- no elimina la cita;
- no elimina el histórico;
- no elimina la auditoría;
- permite liberar la ocurrencia del horario;
- conserva evidencia de la operación.

---

# 39. Cita Finalizada

Una cita Finalizada debe conservar:

- paciente;
- horario;
- especialidad;
- modalidad;
- fechas reales de atención;
- auditoría;
- histórico.

El médico se determina mediante el horario.

---

# 40. D-08 y D-09

## D-08

Continúa pendiente.

No se incorpora:

`habilitada_modalidad_virtual`

al histórico porque tampoco existe en el modelo físico corregido.

## D-09

Continúa pendiente.

No se incorpora:

`horario.modalidad`.

La modalidad existente es:

`cita.modalidad`.

---

# 41. D-02, D-03 y D-04

Los valores correspondientes a:

- cancelación;
- reprogramación;
- tolerancia;
- anticipación máxima;

continúan pendientes.

Auditoría e histórico no deben hardcodear estos valores.

---

# 42. Trazabilidad

| Requisito | Implementación |
|---|---|
| RF-22 | `registro_auditoria` |
| RN-22 | conservación histórica |
| RN-23 | evento de auditoría por cambio |
| RN-24 | valores anterior y actual |
| RN-25 | conservación y `ON DELETE RESTRICT` |
| RN-32 | auditoría de reprogramación/modalidad |
| RNF-08 | disponibilidad y recuperación |
| RNF-09 | RPO ≤ 60 minutos |
| RNF-10 | RTO ≤ 120 minutos |
| RNF-18 | conservación de auditoría |
| D-12 | auditoría ≠ observabilidad |
| D-20 | conservación histórica |

---

# 43. Cambios realizados respecto de la versión anterior

## Cambio 1

Eliminado:

```text
cita_historico.id_medico
```

---

## Cambio 2

El trigger histórico ya no utiliza:

```text
NEW.id_medico
OLD.id_medico
```

---

## Cambio 3

El médico histórico se obtiene mediante:

```text
cita_historico.id_horario
→ horario_historico.id_medico
```

---

## Cambio 4

Se reemplazaron:

```text
fecha_hora_inicio
fecha_hora_fin
```

por los atributos actuales:

```text
fecha_hora_programada
fecha_hora_inicio_atencion
fecha_hora_fin_atencion
```

---

## Cambio 5

Se eliminó:

`paciente_historico.activo`

porque la tabla principal `paciente` no contiene ese atributo.

---

## Cambio 6

Se corrigió:

`email`

por:

`correo_electronico`.

---

## Cambio 7

Se elimina el índice:

```text
idx_cita_hist_medico
```

---

## Cambio 8

Se evita convertir inmediatamente:

`registro_auditoria`

en una tabla particionada sin evidencia de volumen suficiente.

El particionamiento continúa como estrategia evolutiva.

---

## Cambio 9

Se elimina:

`wal_keep_segments`.

Se utiliza:

`wal_keep_size`.

---

## Cambio 10

Se corrigen los objetivos:

```text
RPO ≤ 60 minutos
RTO ≤ 120 minutos
```

---

## Cambio 11

No se establece archivado destructivo automático.

---

## Cambio 12

D-08 y D-09 continúan pendientes sin placeholders.

---

# 44. Conclusiones del Paso 10 corregido

1. La auditoría funcional continúa representada mediante `registro_auditoria`.

2. `pgAudit` se mantiene como mecanismo técnico independiente.

3. Se introduce una estrategia de histórico para entidades críticas.

4. `cita_historico` ya no almacena `id_medico`.

5. El médico histórico se obtiene a través de `horario_historico`.

6. Los nombres temporales de `cita_historico` se alinean con el modelo físico corregido.

7. La conservación mínima es de cinco años.

8. No se eliminan automáticamente datos al alcanzar cinco años.

9. El particionamiento se mantiene como estrategia evolutiva cuando el volumen lo justifique.

10. PITR se implementará mediante backups y archivado de WAL.

11. `wal_keep_segments` se reemplaza por `wal_keep_size`.

12. Los objetivos de recuperación son:

    `RPO ≤ 60 minutos`

    `RTO ≤ 120 minutos`.

13. D-08 y D-09 continúan pendientes.

14. D-02, D-03 y D-04 no reciben valores arbitrarios.

15. El documento queda alineado con los modelos lógico, físico, integridad y seguridad corregidos.

---

# 45. Estado del archivo

**Archivo:**

`proyecto/base_datos/08_auditoria_historico/auditoria_historico.md`

**Paso de origen:**

`Paso 10 — Auditoría, histórico y versionamiento`

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

# 46. Control de avance

Esta corrección no autoriza:

`Paso 15 — Generación SQL`.

Todavía deben corregirse:

- `10_indices_rendimiento/indices_rendimiento.md`;
- `11_transacciones_concurrencia/transacciones_concurrencia.md`;
- `12_migraciones/migraciones.md`.

Después debe ejecutarse nuevamente:

`Paso 14 — Revisión DBA`.

Solo se podrá avanzar cuando el resultado sea:

```text
STATUS: APPROVED
```

**NO generar SQL final todavía.**

**DETENERSE y esperar nueva validación DBA.**