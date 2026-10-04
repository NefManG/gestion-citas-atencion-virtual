# Informe de Seguridad — Paso 09

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español  
**Generado:** Paso 09 — Seguridad  
**Agente utilizado:** `database-engineer`  
**Skill utilizado:** `databases` + `security-reviewer`  
**Workflow:** `02_database_workflow`  
**DBMS:** PostgreSQL  
**Fuentes principales:** `modelo_fisico.md`, `06_integridad/integridad.md`, requisitos de seguridad del sistema  
**Estado:** Corregido manualmente después del Paso 14 — Revisión DBA. Pendiente de nueva validación DBA.

---

# 1. Objetivo del Paso 09

Definir y documentar la estrategia de seguridad para la base de datos PostgreSQL del Sistema de Gestión de Citas y Atención Virtual.

Este paso contempla:

- autenticación segura del DBMS;
- separación entre usuarios de aplicación y usuarios de PostgreSQL;
- cifrado en tránsito;
- protección de credenciales;
- control de acceso mediante RBAC;
- Row-Level Security (RLS);
- auditoría técnica;
- endurecimiento básico del servidor;
- gestión segura de secretos;
- seguridad de conexiones;
- integración con PgBouncer;
- protección de los datos del paciente.

Este documento no significa que la infraestructura ya se encuentre desplegada.

Las configuraciones aquí descritas deben aplicarse y validarse durante el despliegue correspondiente.

---

# 2. Principios de seguridad

Se aplican los siguientes principios:

1. mínimo privilegio;
2. separación de responsabilidades;
3. credenciales nunca almacenadas en texto plano;
4. secretos fuera del repositorio;
5. conexiones cifradas;
6. defensa en profundidad;
7. trazabilidad de operaciones;
8. aislamiento de datos por usuario y rol;
9. separación entre autenticación de aplicación y autenticación del DBMS;
10. conservación segura de auditoría.

---

# 3. Separación entre autenticación de aplicación y PostgreSQL

Es importante diferenciar dos tipos de identidad.

## 3.1 Usuario de la aplicación

La tabla:

`usuario`

representa a las personas que utilizan el sistema.

Ejemplos:

- paciente;
- médico;
- personal de admisión;
- administrador.

La autenticación de estos usuarios ocurre en la aplicación.

La tabla almacena:

`password_hash`

y nunca la contraseña original.

El hash debe ser generado mediante un algoritmo apropiado para contraseñas desde la capa de aplicación.

---

## 3.2 Roles de PostgreSQL

PostgreSQL mantiene sus propias credenciales y roles internos.

Estas credenciales sirven para:

- conexión de la aplicación;
- administración;
- migraciones;
- backup;
- monitoreo.

Las credenciales de PostgreSQL **no son iguales** a:

`usuario.password_hash`.

Por tanto:

> PostgreSQL no autentica directamente a cada paciente o médico utilizando la tabla `usuario`.

La aplicación se conecta mediante una cuenta técnica controlada y posteriormente establece el contexto del usuario dentro de cada transacción.

---

# 4. Autenticación de PostgreSQL

## 4.1 Método

Para conexiones autenticadas se utilizará:

`scram-sha-256`.

No se utilizará en producción:

- `trust`;
- contraseñas en texto plano;
- métodos de autenticación obsoletos.

---

## 4.2 password_encryption

Configuración:

```conf
password_encryption = 'scram-sha-256'
```

Las contraseñas de los roles técnicos de PostgreSQL deben proporcionarse mediante gestión segura de secretos.

No deben escribirse directamente dentro de:

- scripts versionados;
- repositorios Git;
- documentación;
- archivos públicos.

---

# 5. pg_hba.conf

El archivo original contenía:

```text
mdc512
```

Este valor era un error tipográfico y no corresponde a un método válido de autenticación.

La configuración corregida debe utilizar conexiones TLS y SCRAM.

Ejemplo base:

```conf
# TYPE       DATABASE        USER            ADDRESS             METHOD

# Administración local mediante socket del sistema
local        all             all                                 peer

# Conexiones internas cifradas
hostssl      all             all             10.0.0.0/8          scram-sha-256
hostssl      all             all             192.168.0.0/16      scram-sha-256

# Loopback cifrado cuando corresponda
hostssl      all             all             127.0.0.1/32        scram-sha-256
hostssl      all             all             ::1/128             scram-sha-256

# Rechazar conexiones TCP sin TLS
hostnossl    all             all             0.0.0.0/0           reject
hostnossl    all             all             ::0/0               reject
```

## Importante

En producción deben reemplazarse las redes amplias por:

- la red real de aplicación;
- la red de PgBouncer;
- la red administrativa autorizada.

No se recomienda exponer PostgreSQL directamente a Internet.

---

# 6. Cifrado en tránsito

PostgreSQL debe utilizar TLS.

Configuración base:

```conf
ssl = on

ssl_cert_file = '/etc/postgresql/server.crt'
ssl_key_file  = '/etc/postgresql/server.key'
ssl_ca_file   = '/etc/postgresql/root.crt'

ssl_min_protocol_version = 'TLSv1.2'
```

Las conexiones remotas deben utilizar:

`hostssl`

en `pg_hba.conf`.

La aplicación debe validar correctamente el certificado del servidor.

---

# 7. Roles técnicos de PostgreSQL

Se separan los permisos mediante roles técnicos.

## 7.1 Roles sin LOGIN

```sql
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
```

Estos roles agrupan privilegios.

---

## 7.2 Usuario técnico de aplicación

La aplicación debe utilizar un rol técnico con `LOGIN`.

Ejemplo conceptual:

```sql
CREATE ROLE app_runtime
LOGIN
NOSUPERUSER
NOCREATEDB
NOCREATEROLE;
```

La contraseña **no debe aparecer en este archivo**.

Debe suministrarse mediante:

- variable de entorno;
- gestor de secretos;
- mecanismo seguro equivalente.

Los privilegios necesarios se conceden mediante:

```sql
GRANT app_read TO app_runtime;
GRANT app_write TO app_runtime;
```

---

# 8. Cuenta administrativa

La cuenta DBA no debe utilizarse por la aplicación.

Debe existir una identidad administrativa independiente para:

- mantenimiento;
- migraciones autorizadas;
- recuperación;
- operaciones DBA.

Las credenciales administrativas se gestionan fuera del repositorio.

No se recomienda mantener una sentencia con contraseña administrativa dentro de scripts versionados.

---

# 9. RBAC de aplicación

El sistema mantiene el RBAC mediante:

- `usuario`;
- `rol`;
- `permiso`;
- `usuario_rol`;
- `rol_permiso`.

Roles conceptuales:

- paciente;
- medico;
- admision;
- admin.

D-10 continúa pendiente respecto del alcance exacto de los permisos de admisión.

---

# 10. Row-Level Security

RLS se utilizará para reforzar el aislamiento de información.

Tablas principales:

```sql
ALTER TABLE paciente
ENABLE ROW LEVEL SECURITY;

ALTER TABLE medico
ENABLE ROW LEVEL SECURITY;

ALTER TABLE horario
ENABLE ROW LEVEL SECURITY;

ALTER TABLE cita
ENABLE ROW LEVEL SECURITY;

ALTER TABLE atencion_virtual
ENABLE ROW LEVEL SECURITY;

ALTER TABLE registro_auditoria
ENABLE ROW LEVEL SECURITY;
```

---

# 11. Contexto del usuario de aplicación

La aplicación debe establecer dentro de cada transacción información contextual como:

```text
app.current_user_id
app.current_paciente_id
app.current_medico_id
app.current_role
```

Ejemplo:

```sql
SET LOCAL app.current_user_id = '123';
SET LOCAL app.current_paciente_id = '45';
SET LOCAL app.current_role = 'paciente';
```

## Importante con PgBouncer

Cuando PgBouncer utiliza:

`pool_mode = transaction`

el estado de sesión no debe suponerse persistente entre transacciones.

Por esta razón, los valores:

`app.current_*`

deben establecerse mediante:

`SET LOCAL`

al inicio de **cada transacción** protegida.

No debe dependerse de un valor configurado únicamente al abrir una conexión.

---

# 12. RLS para citas del paciente

```sql
CREATE POLICY politica_cita_paciente
ON cita
FOR SELECT
TO app_runtime
USING (
    id_paciente =
    current_setting(
        'app.current_paciente_id',
        TRUE
    )::BIGINT
);
```

Esto permite que el paciente consulte únicamente sus propias citas.

Fuente:

RN-14.

---

# 13. RLS para citas del médico

La versión anterior utilizaba:

```text
cita.id_medico
```

pero esa columna fue eliminada durante la normalización.

El médico de una cita se obtiene mediante:

`cita.id_horario → horario.id_medico`.

La política corregida es:

```sql
CREATE POLICY politica_cita_medico
ON cita
FOR SELECT
TO app_runtime
USING (
    EXISTS (
        SELECT 1
        FROM horario h
        WHERE h.id_horario = cita.id_horario
          AND h.id_medico =
              current_setting(
                  'app.current_medico_id',
                  TRUE
              )::BIGINT
    )
);
```

Fuente:

RN-15.

---

# 14. RLS para horario

```sql
CREATE POLICY politica_horario_medico
ON horario
FOR SELECT
TO app_runtime
USING (
    id_medico =
    current_setting(
        'app.current_medico_id',
        TRUE
    )::BIGINT
);
```

Un médico solo debe consultar sus propios horarios salvo privilegio administrativo.

---

# 15. RLS de paciente

```sql
CREATE POLICY politica_paciente_propio
ON paciente
FOR SELECT
TO app_runtime
USING (
    id_paciente =
    current_setting(
        'app.current_paciente_id',
        TRUE
    )::BIGINT
);
```

---

# 16. RLS de atención virtual

`atencion_virtual` no debe utilizar:

```sql
USING (TRUE)
```

para pacientes o médicos porque permitiría acceso excesivo.

El acceso debe comprobar la cita relacionada.

## Paciente

```sql
CREATE POLICY politica_av_paciente
ON atencion_virtual
FOR SELECT
TO app_runtime
USING (
    EXISTS (
        SELECT 1
        FROM cita c
        WHERE c.id_cita = atencion_virtual.id_cita
          AND c.id_paciente =
              current_setting(
                  'app.current_paciente_id',
                  TRUE
              )::BIGINT
    )
);
```

## Médico

```sql
CREATE POLICY politica_av_medico
ON atencion_virtual
FOR SELECT
TO app_runtime
USING (
    EXISTS (
        SELECT 1
        FROM cita c
        JOIN horario h
          ON h.id_horario = c.id_horario
        WHERE c.id_cita = atencion_virtual.id_cita
          AND h.id_medico =
              current_setting(
                  'app.current_medico_id',
                  TRUE
              )::BIGINT
    )
);
```

---

# 17. RLS de auditoría

Los pacientes no deben consultar directamente la tabla completa de auditoría.

El acceso a:

`registro_auditoria`

se restringe a perfiles autorizados.

Ejemplo:

```sql
CREATE POLICY politica_auditoria_lectura
ON registro_auditoria
FOR SELECT
TO app_runtime
USING (
    current_setting(
        'app.current_role',
        TRUE
    ) IN ('admin')
);
```

D-10 deberá definir si algún rol adicional puede consultar registros de auditoría.

---

# 18. Inserción de auditoría

Los registros de auditoría deben generarse principalmente mediante los mecanismos definidos en Integridad/Auditoría.

La identidad del usuario responsable se obtiene desde:

`app.current_user_id`.

Ejemplo:

```sql
CREATE POLICY politica_auditoria_insercion
ON registro_auditoria
FOR INSERT
TO app_runtime
WITH CHECK (
    id_usuario_responsable =
    current_setting(
        'app.current_user_id',
        TRUE
    )::BIGINT
);
```

---

# 19. Administración y RLS

Un administrador autorizado puede requerir acceso más amplio.

El bypass de RLS no debe concederse indiscriminadamente al usuario técnico normal de aplicación.

La aplicación debe trabajar bajo el principio de mínimo privilegio.

---

# 20. Credenciales de la aplicación

La tabla:

`usuario`

almacena:

`password_hash`.

Estas credenciales son utilizadas por la lógica de autenticación de la aplicación.

No deben utilizarse como credenciales directas de PostgreSQL.

Por tanto, queda eliminada la afirmación anterior de que:

> `usuario.password_hash` es utilizado por PostgreSQL para validar SCRAM.

Eso era incorrecto.

---

# 21. PgBouncer

PgBouncer se utiliza como pool de conexiones entre:

Aplicación

→ PgBouncer

→ PostgreSQL.

La autenticación técnica de PgBouncer debe gestionarse de manera separada de las contraseñas de usuarios finales.

---

# 22. Configuración base de PgBouncer

Ejemplo de configuración:

```ini
[databases]

hospital =
    host=127.0.0.1
    port=5432
    dbname=hospital


[pgbouncer]

listen_addr = 127.0.0.1
listen_port = 6432

auth_type = scram-sha-256

pool_mode = transaction

max_client_conn = 1000
default_pool_size = 20
reserve_pool_size = 5

server_reset_query = DISCARD ALL

query_timeout = 300

logfile = /var/log/pgbouncer/pgbouncer.log
```

## Nota

Los valores:

- `max_client_conn`;
- `default_pool_size`;
- `reserve_pool_size`;
- `query_timeout`;

son parámetros operativos y deben ajustarse utilizando:

- perfil real de carga;
- pruebas de rendimiento;
- capacidad del servidor.

No representan límites funcionales permanentes.

---

# 23. Autenticación en PgBouncer

No se debe usar directamente:

`usuario.password_hash`

como fuente de autenticación SCRAM de PostgreSQL.

Existen dos dominios distintos:

1. credenciales de usuarios de aplicación;
2. credenciales de roles técnicos PostgreSQL.

PgBouncer debe autenticar las cuentas técnicas mediante un mecanismo compatible con los roles de PostgreSQL.

La aplicación autentica pacientes/médicos de manera separada.

---

# 24. Cifrado en reposo

El diseño requiere proteger los datos almacenados.

La estrategia recomendada debe considerar:

- cifrado del volumen o sistema de archivos;
- cifrado de backups;
- control de acceso al servidor;
- protección de claves fuera del repositorio.

La utilización de cifrado de columnas con `pgcrypto` debe reservarse para casos donde exista una necesidad concreta.

No se agrega automáticamente una columna nueva como:

`observaciones_secure`

porque esto modificaría el modelo físico sin una decisión aprobada.

---

# 25. Gestión de secretos

Nunca deben almacenarse en Git:

- contraseñas de PostgreSQL;
- contraseña de PgBouncer;
- claves TLS privadas;
- claves de cifrado;
- credenciales administrativas;
- tokens;
- claves de servicios externos.

Estos secretos deben obtenerse mediante:

- `.env` no versionado;
- gestor de secretos;
- KMS/HSM;
- mecanismo seguro equivalente.

Esto será especialmente relevante durante:

`Paso 16 — Conexión y credenciales`.

---

# 26. Auditoría técnica con pgAudit

La auditoría funcional de las citas se mantiene en:

`registro_auditoria`.

`pgAudit` cumple un propósito diferente:

> registrar actividad técnica realizada en PostgreSQL.

Ambos mecanismos son complementarios.

---

# 27. Preparación de pgAudit

`pgAudit` debe cargarse de acuerdo con la instalación de PostgreSQL utilizada.

Configuración conceptual:

```conf
shared_preload_libraries = 'pgaudit'
```

Posteriormente:

```sql
CREATE EXTENSION IF NOT EXISTS pgaudit;
```

Ejemplo de categorías de auditoría:

```conf
pgaudit.log = 'write,ddl,role'
pgaudit.log_catalog = off
pgaudit.log_parameter = off
```

## Motivo

No se recomienda registrar indiscriminadamente valores de parámetros que puedan contener:

- credenciales;
- datos personales;
- tokens;
- información sensible.

---

# 28. Auditoría funcional vs auditoría técnica

## Auditoría funcional

Tabla:

`registro_auditoria`.

Registra:

- cambio de estado;
- cancelación;
- reprogramación;
- cambio de modalidad;
- usuario responsable;
- valor anterior;
- valor posterior.

## Auditoría técnica

`pgAudit`.

Registra:

- operaciones SQL;
- DDL;
- cambios de roles;
- actividad administrativa relevante.

No deben confundirse ambos conceptos.

---

# 29. RBAC — Roles iniciales

Roles de aplicación:

```sql
INSERT INTO rol (
    nombre,
    descripcion
)
VALUES
    ('paciente', 'Usuario paciente del sistema'),
    ('medico',   'Profesional médico'),
    ('admision', 'Personal de admisión'),
    ('admin',    'Administrador del sistema');
```

D-10 continúa pendiente para definir el alcance exacto de:

`admision`.

---

# 30. Permisos base

Se mantienen los permisos definidos en el modelo físico:

```sql
INSERT INTO permiso (
    nombre,
    descripcion,
    recurso,
    accion
)
VALUES
    (
        'cita.crear',
        'Crear cita',
        'cita',
        'crear'
    ),
    (
        'cita.leer_propias',
        'Leer sus propias citas',
        'cita',
        'leer'
    ),
    (
        'cita.leer_todas',
        'Leer todas las citas',
        'cita',
        'leer'
    ),
    (
        'cita.actualizar',
        'Actualizar cita',
        'cita',
        'actualizar'
    ),
    (
        'cita.cancelar',
        'Cancelar cita',
        'cita',
        'ejecutar'
    ),
    (
        'cita.reprogramar',
        'Reprogramar cita',
        'cita',
        'ejecutar'
    ),
    (
        'auditoria.leer',
        'Leer auditoría',
        'auditoria',
        'leer'
    ),
    (
        'config.leer',
        'Leer configuración',
        'config',
        'leer'
    ),
    (
        'config.actualizar',
        'Actualizar configuración',
        'config',
        'actualizar'
    );
```

---

# 31. Asignaciones de permisos

Las asignaciones deben realizarse mediante:

`rol_permiso`.

No debe utilizarse una subconsulta escalar que pueda devolver múltiples filas.

Ejemplo:

```sql
INSERT INTO rol_permiso (
    id_rol,
    id_permiso
)
SELECT
    r.id_rol,
    p.id_permiso
FROM rol r
JOIN permiso p
  ON p.nombre IN (
      'cita.leer_propias',
      'cita.cancelar',
      'cita.reprogramar'
  )
WHERE r.nombre = 'paciente';
```

Para médicos:

```sql
INSERT INTO rol_permiso (
    id_rol,
    id_permiso
)
SELECT
    r.id_rol,
    p.id_permiso
FROM rol r
JOIN permiso p
  ON p.nombre IN (
      'cita.leer_todas',
      'cita.actualizar'
  )
WHERE r.nombre = 'medico';
```

## Admisión

No se fija todavía la lista final de permisos del rol:

`admision`

porque D-10 continúa pendiente.

---

# 32. Protección frente a fuerza bruta

La tabla `usuario` incluye:

- `intentos_fallidos`;
- `bloqueado_hasta`.

El control de intentos de acceso corresponde principalmente a la aplicación.

La aplicación debe:

1. registrar intento fallido;
2. incrementar contador;
3. aplicar bloqueo según política aprobada;
4. reiniciar contador cuando corresponda;
5. auditar eventos relevantes.

No se inventa en este paso un número específico de intentos máximos si no está definido en requisitos.

---

# 33. Tiempo de sesión

RNF-07 / D-13 establece:

`15 minutos`

como valor inicial configurable de inactividad de sesión.

Este valor pertenece a:

`parametros_configuracion`.

No debe confundirse con:

`idle_in_transaction_session_timeout`.

Son conceptos diferentes.

---

# 34. Timeouts de PostgreSQL

Pueden definirse límites operativos para evitar conexiones o consultas abandonadas.

Ejemplo de configuración inicial:

```conf
idle_in_transaction_session_timeout = '180s'
statement_timeout = '60s'
```

Estos valores son ajustes operativos.

Deben validarse mediante:

- pruebas;
- perfil de carga;
- comportamiento real de la aplicación.

No sustituyen el parámetro funcional de sesión de 15 minutos.

---

# 35. Hardening básico de PostgreSQL

Configuraciones relevantes:

```conf
password_encryption = 'scram-sha-256'

ssl = on
ssl_min_protocol_version = 'TLSv1.2'

log_error_verbosity = DEFAULT
log_min_error_statement = ERROR
```

Los siguientes aspectos deben ajustarse según capacidad real:

- `max_connections`;
- memoria;
- WAL;
- checkpoints;
- autovacuum;
- logging.

No deben establecerse valores arbitrarios como una regla permanente del sistema.

---

# 36. Acceso de red

PostgreSQL no debe exponerse directamente a redes públicas.

Se recomienda:

Aplicación

→ red privada

→ PgBouncer

→ PostgreSQL.

Los accesos administrativos deben realizarse mediante una red controlada.

---

# 37. Backup y recuperación

La seguridad también comprende:

- confidencialidad del backup;
- integridad;
- disponibilidad;
- capacidad de restauración.

Los backups deben almacenarse cifrados.

Debe existir prueba periódica de restauración.

El diseño detallado de backup y recuperación debe respetar:

- RPO;
- RTO;
- retención;
- política de infraestructura.

No se considera un backup exitoso hasta comprobar que puede restaurarse.

---

# 38. Protección de datos sensibles

Se deben proteger especialmente:

- datos personales del paciente;
- datos médicos;
- credenciales;
- enlaces de atención virtual;
- auditoría;
- IP;
- metadatos técnicos.

Las consultas deben seguir el principio de:

> mínimo dato necesario.

---

# 39. Datos de producción

Los datos reales de pacientes no deben copiarse directamente a entornos de desarrollo o pruebas sin un proceso de:

- anonimización;
- seudonimización;
- autorización correspondiente.

---

# 40. Logs

Los logs no deben incluir innecesariamente:

- contraseñas;
- tokens;
- hashes completos;
- claves privadas;
- datos sensibles completos del paciente.

Las operaciones críticas deben ser trazables sin exponer información innecesaria.

---

# 41. Observabilidad y seguridad

La observabilidad técnica permanece separada de:

`registro_auditoria`.

Puede incluir:

- fallos de autenticación;
- errores del servidor;
- consultas lentas;
- fallos de integración;
- eventos de seguridad.

No debe sustituir la auditoría funcional.

---

# 42. Conservación de auditoría

Los registros funcionales relacionados con las citas deben conservarse conforme a:

RN-25 / D-20 / RNF-18.

Las políticas de seguridad no deben permitir que un usuario normal pueda eliminar registros históricos protegidos.

---

# 43. Médico derivado del horario

Después de la normalización:

`cita.id_medico`

no existe.

Por tanto, cualquier regla de seguridad que necesite conocer el médico debe utilizar:

```text
cita.id_horario
        ↓
horario.id_medico
```

Esto aplica a:

- RLS;
- filtros;
- consultas;
- auditoría;
- seguridad de atención virtual;
- políticas de acceso del médico.

---

# 44. D-08 y D-09

## D-08

Continúa pendiente.

No existe:

`medico_especialidad.habilitada_modalidad_virtual`.

## D-09

Continúa pendiente.

No existe:

`horario.modalidad`.

Se mantiene:

`cita.modalidad`.

La seguridad no debe inventar una resolución de estas decisiones.

---

# 45. D-10

El alcance exacto del personal de admisión continúa pendiente.

Por tanto:

- puede existir el rol `admision`;
- pueden existir permisos candidatos;
- pero no debe considerarse cerrada su matriz definitiva de autorización.

---

# 46. D-02, D-03 y D-04

La seguridad no asigna valores a:

- tiempo mínimo de cancelación;
- tiempo mínimo de reprogramación;
- tolerancia de no asistencia;
- anticipación máxima de reserva.

Estos parámetros continúan pendientes.

---

# 47. Checklist de seguridad corregido

- [x] Autenticación PostgreSQL separada de autenticación de usuarios finales.
- [x] `scram-sha-256` definido para roles técnicos.
- [x] Error `mdc512` eliminado.
- [x] Conexiones remotas mediante `hostssl`.
- [x] Conexiones sin TLS rechazadas.
- [x] Contraseñas fuera del repositorio.
- [x] Usuario técnico de aplicación sin privilegios administrativos.
- [x] RLS habilitado para tablas sensibles.
- [x] Política del médico corregida para utilizar `horario.id_medico`.
- [x] `cita.id_medico` no utilizado.
- [x] Atención virtual protegida mediante la cita relacionada.
- [x] Auditoría funcional separada de pgAudit.
- [x] `usuario.password_hash` no utilizado como contraseña de PostgreSQL.
- [x] PgBouncer separado de autenticación de usuarios finales.
- [x] Contexto RLS mediante `SET LOCAL` por transacción.
- [x] D-08 permanece pendiente.
- [x] D-09 permanece pendiente.
- [x] D-10 permanece pendiente.
- [x] D-02/D-03/D-04 permanecen sin valores inventados.
- [x] Secretos no versionados.
- [x] Preparado para nueva revisión DBA.

---

# 48. Cambios realizados respecto de la versión anterior

## Cambio 1

Corregido:

```text
mdc512
```

Se elimina y se utiliza:

```text
scram-sha-256
```

en conexiones autenticadas.

---

## Cambio 2

Eliminado el uso directo de:

`cita.id_medico`

en políticas RLS.

Ahora se utiliza:

`cita.id_horario → horario.id_medico`.

---

## Cambio 3

Se corrige la política:

`politica_cita_medico`.

---

## Cambio 4

Se elimina:

`USING (TRUE)`

como protección general de `atencion_virtual`.

Ahora el acceso se valida mediante la cita relacionada.

---

## Cambio 5

Se separa:

`usuario.password_hash`

de las credenciales internas de PostgreSQL.

---

## Cambio 6

Se elimina la afirmación de que PostgreSQL utiliza el hash de contraseña del paciente/médico para SCRAM.

---

## Cambio 7

La cuenta técnica de aplicación se separa de:

- paciente;
- médico;
- admisión;
- admin.

Estos continúan siendo roles funcionales de la aplicación.

---

## Cambio 8

Se corrige el uso de PgBouncer.

PgBouncer autentica cuentas técnicas, no directamente las credenciales finales de la tabla `usuario`.

---

## Cambio 9

Se establece:

`SET LOCAL app.current_*`

por transacción para compatibilidad con:

`pool_mode = transaction`.

---

## Cambio 10

Se eliminan modificaciones no aprobadas del modelo como:

`observaciones_secure`.

---

## Cambio 11

Se evita afirmar que la seguridad ya está físicamente desplegada.

El documento define el diseño que deberá aplicarse y validarse posteriormente.

---

# 49. Conclusiones del Paso 09 corregido

1. La autenticación de usuarios de aplicación se mantiene separada de PostgreSQL.

2. PostgreSQL utiliza roles técnicos para las conexiones.

3. Las credenciales PostgreSQL utilizan `scram-sha-256`.

4. Se elimina el error `mdc512`.

5. Se exige TLS para las conexiones remotas.

6. Los secretos no deben almacenarse dentro del repositorio.

7. `cita.id_medico` ya no se utiliza.

8. El médico relacionado con una cita se obtiene mediante:

   `cita.id_horario → horario.id_medico`.

9. Las políticas RLS del médico se actualizan para utilizar esa relación.

10. Las políticas de atención virtual utilizan la cita relacionada.

11. `usuario.password_hash` corresponde exclusivamente a autenticación de aplicación.

12. Las credenciales técnicas de PostgreSQL son independientes.

13. PgBouncer trabaja con las credenciales técnicas de conexión.

14. En modo `transaction`, el contexto RLS se establece mediante `SET LOCAL` en cada transacción.

15. La auditoría funcional continúa separada de la auditoría técnica de pgAudit.

16. D-08 y D-09 continúan pendientes.

17. D-10 continúa pendiente respecto del alcance definitivo de admisión.

18. D-02, D-03 y D-04 no reciben valores arbitrarios.

19. El documento queda alineado con el modelo lógico, físico e integridad corregidos.

---

# 50. Estado del archivo

**Archivo:**

`proyecto/base_datos/07_seguridad/seguridad.md`

**Paso de origen:**

`Paso 09 — Seguridad`

**Agente utilizado:**

`database-engineer`

**Skills utilizados:**

- `databases`
- `security-reviewer`

**Corrección posterior:**

Propagación de:

- Paso 05 — Normalización;
- Paso 14 — Revisión DBA con `STATUS: CHANGES_REQUIRED`.

**Estado actual:**

Corregido manualmente y pendiente de nueva Revisión DBA.

---

# 51. Control de avance

Esta corrección **NO autoriza ejecutar el Paso 15 — Generación SQL**.

Todavía deben revisarse y corregirse:

- `08_auditoria_historico/auditoria_historico.md`;
- `10_indices_rendimiento/indices_rendimiento.md`;
- `11_transacciones_concurrencia/transacciones_concurrencia.md`;
- `12_migraciones/migraciones.md`.

Después debe ejecutarse nuevamente:

`Paso 14 — Revisión DBA`.

Solo se podrá preparar el Paso 15 cuando el resultado sea:

```text
STATUS: APPROVED
```

**NO generar SQL final todavía.**

**DETENERSE y esperar nueva validación DBA.**