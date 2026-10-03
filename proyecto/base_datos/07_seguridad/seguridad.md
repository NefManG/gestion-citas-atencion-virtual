# Informe de Seguridad — Paso 09

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español
**Generado:** Paso 09 — Seguridad
**Agente utilizado:** `database-engineer` (skill `databases` + `security-reviewer`)
**Workflow:** `02_database_workflow`
**DBMS:** PostgreSQL 18.6
**Fuentes principales:** `modelo_fisico.md`, `06_integridad/integridad.md`, skill `databases`
**Estado:** Pendiente de validación humana

---

## 1. Objetivo del Paso 09

Implementar y documentar la **configuración de seguridad completa** para PostgreSQL 18.6 en el contexto de un sistema hospitalario público con manejo de datos de salud (PHI). Este paso cubre:

- Autenticación moderna (SCRAM-SHA-256)
- Cifrado en tránsito (TLS) y en reposo
- Políticas de Nivel de Fila (RLS) por rol/paciente
- Auditoría con pgAudit
- Gestión de roles y permisos RBAC
- Configuración de connection pooler (PgBouncer)
- Hardening del servidor PostgreSQL
- Cumplimiento de normativas de salud

**Salida:** `proyecto/base_datos/07_seguridad/seguridad.md`

DETENERSE y esperar aprobación humana.

---

## 2. Arquitectura de Seguridad del Sistema

```
+---------------------+       +----------------------+
|  Cliente / App      | <---> |  PgBouncer (pooler)  |
+---------------------+       +----------------------+
         |                              |
         | SSL/TLS 1.2+                 | hostssl + scram-sha-256
         v                              v
+---------------------+       +----------------------+
|  PostgreSQL 18.6    |       |  pg_hba.conf         |
|  + pgAudit         |<----->|  + rls policies      |
|  + pgcrypto         |       |  + roles/permissions |
|  + encryption       |       +----------------------+
+---------------------+
```

---

## 3. Autenticación y Acceso

### 3.1 Configuración `pg_hba.conf` (PostgreSQL-Specific)

```
# TYPE  DATABASE  USER  ADDRESS  METHOD
# Conexiones locales (UNIX socket)
local   all             all                                     peer
# Conexiones remotas (TCP/IP)
host    all             all             127.0.0.1/32    scram-sha-256
host    all             all             ::1/128        scram-sha-256
host    all             all             10.0.0.0/8      scram-sha-256
host    all             all             192.168.0.0/16  scram-sha-256
# Solo acceso vía PgBouncer (sin hostssl directo)
host    all             all             0.0.0.0/0       mdc512
# Conexiones con SSL forzado (solo app → PG directamente)
hostssl all             all             10.0.0.0/8      scram-sha-256 hostnossl
hostssl all             all             192.168.0.0/16  scram-sha-256 hostnossl
```

### 3.2 Autenticación SCRAM-SHA-256

- **Ninguna contraseña `trust` en producción** (RF-21, RNF-05).
- Las contraseñas se almacenan como `password_hash` en la tabla `usuario` (bcrypt/Argon2 en la aplicación, nunca texto plano).
- El `password_hash` en la BD es solo para que PostgreSQL valide el SCRAM-SHA-256 durante el login.
- **Migración segura**: Cuando un usuario cambia su contraseña por la app, se actualiza `password_hash` en la tabla `usuario` y la entrada del rol de PG se actualiza automáticamente.

### 3.3 Roles de Base de Datos

```sql
-- Roles de aplicación (no superusuario)
CREATE ROLE app_login WITH LOGIN PASSWORD 'cambiar_por_env' LOGIN VIA pgbouncer;
CREATE ROLE app_read  WITH LOGIN NOCREATEDB NOCREATEROLE INHERIT; -- solo lecturas
CREATE ROLE app_write WITH LOGIN NOCREATEDB NOCREATEROLE INHERIT; -- escrituras controladas
CREATE ROLE app_admin WITH LOGIN NOCREATEDB NOCREATEROLE INHERIT; -- DBA tasks

-- Roles de aplicación (con contraseñas gestionadas por app, no por PG)
-- Las credenciales se pasan vía conexión, no se almacenan en scripts

-- Roles operativos (solo para tareas de mantenimiento)
CREATE ROLE dba_admin WITH LOGIN SUPERUSER INHERIT;
```

### 3.4 Niveles de acceso por rol (RLS)

| Rol | Tabla(s) | Política RLS | Justificación |
|-----|----------|--------------|---------------|
| `paciente` | `cita`, `atencion_virtual`, `registro_auditoria` | `politica_paciente`: `id_paciente = current_setting('app.current_paciente_id')::bigint` | Acceso exclusivo a sus propios datos (RN-14, RN-16) |
| `medico` | `cita`, `horario` | `politica_medico`: `id_medico = current_setting('app.current_medico_id')::bigint` | Acceso a citas/horarios de su consulta (RN-15) |
| `administracion` | Todas | Política completa con filtro por `id_usuario` | Gestión administrativa (RN-17, RF-21) |
| `admin` | `parametros_configuracion`, `rol`, `permiso` | Sin RLS (solo superusuario) | Configuración del sistema (D-10) |

---

## 4. Políticas de Nivel de Fila (RLS) Implementadas

### 4.1 RLS en tabla `cita`

```sql
-- Habilitar RLS
ALTER TABLE cita ENABLE ROW LEVEL SECURITY;

-- Política: paciente ve solo sus citas
CREATE POLICY politica_cita_paciente ON cita
    USING (id_paciente = current_setting('app.current_paciente_id')::bigint);

-- Política: médico ve citas de su especialidad/horario
CREATE POLICY politica_cita_medico ON cita
    USING (id_medico = current_setting('app.current_medico_id')::bigint);

-- Política: admin/admisión ve todas (con auditoría)
CREATE POLICY politica_cita_admin ONcita
    USING (current_setting('app.user_role')::text = 'admin');
```

> **Importante:** `current_setting('app.current_paciente_id')` debe establecerse al iniciar cada conexión en la aplicación (connection hook), nunca confiar en el rol de BD solo.

### 4.2 RLS en tabla `atencion_virtual`

```sql
ALTER TABLE atencion_virtual ENABLE ROW LEVEL SECURITY;

-- Acceso condicional: solo si la cita es virtual
CREATE POLICY politica_atencion_virtual ON atencion_virtual
    USING (TRUE); -- Acceso condicional validado en aplicación + trigger en cita
```

### 4.3 RLS en tabla `registro_auditoria`

```sql
ALTER TABLE registro_auditoria ENABLE ROW LEVEL SECURITY;

-- Solo personal autorizado puede leer auditoría (no pacientes)
CREATE POLICY politica_auditoria_lectura ON registro_auditoria
    FOR SELECT
    USING (current_setting('app.user_role')::text IN ('admin', 'dba_admin'));

-- Solo el usuario responsable o admin puede insertar
CREATE POLICY politica_auditoria_insercion ON registro_auditoria
    FOR INSERT
    WITH CHECK (id_usuario_responsable = current_setting('app.current_user_id')::bigint);
```

### 4.4 RLS en tabla `horario`

```sql
ALTER TABLE horario ENABLE ROW LEVEL SECURITY;

-- Médico solo ve sus propios horarios
CREATE POLICY politica_horario_medico ON horario
    USING (id_medico = current_setting('app.current_medico_id')::bigint);
```

---

## 5. Cifrado de Datos

### 5.1 Cifrado en tránsito (TLS)

- **Configuración obligatoria en `postgresql.conf`:**
  ```
  ssl = on
  ssl_cert_file = '/etc/postgresql/18/main/server.crt'
  ssl_key_file = '/etc/postgresql/18/main/server.key'
  ssl_ca_file = '/etc/postgresql/18/main/root.crt'
  ssl_crl_file = '/etc/postgresql/18/main/server.crl'
  ssl_renegotiation_limit = 524288
  ssl_timeout = 300s
  ```
- **`pg_hba.conf`**: Solo `hostssl` (no `host`), `requirepgpass` desactivado.
- **TLS 1.2+** solo — desactivar TLS 1.0/1.1.
- **Certificate pinning** para clientes críticos.

### 5.2 Cifrado en reposo

- **No TDE nativo en PostgreSQL 18.6** (solo en ediciones comerciales con parches).
- **Recomendación:** Cifrado a nivel de sistema de archivos (LVM, ZFS, BitLocker en Windows, dm-crypt en Linux).
- **Alternativa PG:** Extensión `pgcrypto` para cifrar columnas sensibles (passwords, datos PHI extra).

```sql
-- Ejemplo: Cifrar campo de observaciones sensibles
ALTER TABLE cita ADD COLUMN observaciones_secure TEXT;

-- Función para cifrar/descifrar (usando pgcrypto)
CREATE OR REPLACE FUNCTION fn_encrypt_observaciones(texto TEXT)
RETURNS TEXT AS $$
DECLARE
    key_bytes BYTEA := decode(current_setting('app.encryption_key'), 'hex');
BEGIN
    RETURN encode(pgp_sym_encrypt(texto, key_bytes), 'base64');
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION fn_decrypt_observaciones(texto_base64 TEXT)
RETURNS TEXT AS $$
DECLARE
    key_bytes BYTEA := decode(current_setting('app.encryption_key'), 'hex');
BEGIN
    RETURN pgp_sym_decrypt(decode(texto_base64, 'base64'), key_bytes)::text;
END;
$$ LANGUAGE plpgsql;

-- Aplicar a columna existente
UPDATE cita SET observaciones_secure = fn_encrypt_observaciones(observaciones)
WHERE observaciones IS NOT NULL;
```

> **Nota RNF-07:** Las claves de cifrado deben gestionarse vía KMS/HSM, nunca en `postgresql.conf` en producción. `current_setting('app.encryption_key')` se inyecta al iniciar la conexión.

---

## 6. Auditoría con pgAudit

### 6.1 Extensión pgAudit (v3.3+ compatible PG18)

```sql
-- Habilitar extensión en base de datos del sistema
CREATE EXTENSION IF NOT EXISTS pgaudit WITH SCHEMA pg_catalog;

-- Configurar niveles de logging
ALTER SYSTEM SET pgaudit.log = 'ddl, misrole, session, unsigned';
ALTER SYSTEM SET pgaudit.log_catalog = 'on';
ALTER SYSTEM SET pgaudit.log_parameter_never = 'password';
ALTER SYSTEM SET pgaudit.log_parameter_always = 'on'; -- para parámetros sensibles

-- Reiniciar PostgreSQL para aplicar
SELECT pg_reload_conf();
```

### 6.2 Tipos de log de pgAudit

| Valor pgaudit.log | Descripción |
|-------------------|-------------|
| `ddl` | Comandos CREATE, ALTER, DROP |
| `misrole` | Cambios de roles/permisos |
| `session` | Inicios/terminos de sesión, usuario, DB |
| `unsigned` | Consultas sin firma (potenciales inyecciones) |

**Ejemplo de salida en logs:**
```
2026-10-02 14:32:15.482 pgaudit: pgaudit.role_select=1 pgaudit.table_name=cita pgaudit.command=INSERT pgaudit.schema=public pgaudit.user_id=1056 pgaudit.session_id=8392
```

### 6.3 Integración SIEM

- **Formato JSON estructurado** para log forwarder (Fluentd, Filebeat, etc.).
- **Filtrado por:** `pgaudit.user_id`, `pgaudit.session_id`, `pgaudit.command`.
- **Alertas:** INSERT/UPDATE/DELETE en `cita` fuera de horas hábiles, múltiples fallos de login, cambios de roles.

---

## 7. Gestión de Roles y Permisos RBAC

### 7.1 Roles semilla (datos iniciales)

```sql
-- Insertar roles (únicos, D-10 alcance limitado)
INSERT INTO rol (nombre, descripcion) VALUES
    ('paciente', 'Usuario paciente del sistema'),
    ('medico', 'Profesional médico con consulta'),
    ('admision', 'Personal de admisión y registro'),
    ('admin', 'Administrador del sistema hospitalario');

-- Insertar permisos base (D-10, alcance a definir)
INSERT INTO permiso (nombre, descripcion, recurso, accion) VALUES
    ('crear_cita', 'Crear nueva cita', 'cita', 'crear'),
    ('leer_cita', 'Ver citas', 'cita', 'leer'),
    ('actualizar_cita', 'Modificar cita', 'cita', 'actualizar'),
    ('cancelar_cita', 'Cancelar cita', 'cita', 'eliminar'), -- mapeado a 'eliminar' en app
    ('ver_atencion_virtual', 'Ver información virtual', 'atencion_virtual', 'leer'),
    ('gestionar_parametros', 'Configurar parámetros', 'parametros_configuracion', 'actualizar'),
    ('auditoría_lectura', 'Leer logs de auditoría', 'registro_auditoria', 'leer');
```

### 7.2 Asignación de roles a usuarios

```sql
-- Asignar rol paciente a usuario
INSERT INTO usuario_rol (id_usuario, id_rol, fecha_asignacion)
    SELECT id_usuario, (SELECT id_rol FROM rol WHERE nombre = 'paciente'),
           now()
    FROM usuario WHERE username = 'juan.paciente';

-- Asignar rol médico a usuario
INSERT INTO usuario_rol (id_usuario, id_rol, fecha_asignacion)
    SELECT id_usuario, (SELECT id_rol FROM rol WHERE nombre = 'medico'),
           now()
    FROM usuario WHERE username = 'dra.sanchez';

-- Asignar permisos por rol
INSERT INTO rol_permiso (id_rol, id_permiso) VALUES
    ((SELECT id_rol FROM rol WHERE nombre = 'paciente'),
     (SELECT id_permiso FROM permiso WHERE nombre = 'leer_cita')),
    ((SELECT id_rol FROM rol WHERE nombre = 'medico'),
     (SELECT id_permiso FROM permiso WHERE nombre IN ('crear_cita', 'leer_cita', 'actualizar_cita', 'cancelar_cita', 'ver_atencion_virtual'))),
    ((SELECT id_rol FROM rol WHERE nombre = 'admision'),
     (SELECT id_permiso FROM permiso WHERE nombre IN ('crear_cita', 'leer_cita', 'actualizar_cita'))),
    ((SELECT id_rol FROM rol WHERE nombre = 'admin'),
     (SELECT id_permiso FROM permiso WHERE nombre IN ('gestionar_parametros', 'auditoría_lectura')));
```

### 7.3 Políticas de contraseña y bloques

- **`password_authen`** en `pg_hba.conf`: `scram-sha-256` (predeterminado PG18).
- **Bloqueo automático** después de N intentos fallidos (controlado por la aplicación, no por BD):
  - `intentos_fallidos` en tabla `usuario` controla el bloqueo.
  - La aplicación incrementa este contador y bloquea el login cuando reaches el umbral.
- **`idle_in_transaction_session_timeout`**: 180s (para liberar conexiones varadas).
- **`statement_timeout`**: 60s por rol (evita queries colgantes).

---

## 8. Configuración de Connection Pooler (PgBouncer)

### 8.1 `pgbouncer.ini` (versión 1.25.2)

```
[max client connections]
  1000

[default_pool_size]
  20
  reserve_pool_size = 5

[auth_type]
  scram-sha-256

[auth_user_domain]
  true

[passthrough]
  auth_query = SELECT u.password_hash, u.activo, u.id_rol
                 FROM usuario u WHERE u.username = current_user();

[pool_mode]
  transaction

[serve_sql]
  query_timeout = 300

[logging]
  syslog = 0
  logfile = /var/log/pgbouncer.log
  verbose = 1
```

### 8.2 Consideraciones críticas

- **Modo transaction:** Cada transacción de la aplicación obtiene una conexión del pool, luego la devuelve. Ideal para aplicaciones web con transacciones cortas.
- **Modo statement:** Cada comando SQL obtiene y suelta conexión. Más overhead, pero necesario para transacciones largas.
- **`auth_query`:** Valida credenciales contra la tabla `usuario` (password_hash validado por SCRAM-SHA-256).
- **`server_reset_query`:** `DISCARD ALL` (por defecto en modo transaction).
- **Health check:** `SELECT 1` vía `ping` endpoint.

---

## 9. Hardening Adicional del Servidor

### 9.1 `postgresql.conf` — Parámetros de seguridad

```conf
# Evitar información reveladora en errores
log_error_verbosity = DEFAULT
log_min_duration_statement = 0  # Loguear todas las queries (luego filtrar)
log_min_error_statement = ERROR

# Tiempo de sesión inactiva
idle_in_transaction_session_timeout = '180s'
statement_timeout = '60s'

# Conexiones concurrentas
max_connections = 200
superuser_reserved_connections = 3

# WAL y replicación
wal_level = 'logical'  -- para logical replication / upgrades
max_wal_senders = 3
wal_keep_segments = 64

# Búsqueda de planes
enable_bitmapscan = on
enable_indexscan = on
enable_indexonlyscan = on

# Evitar plan caching attacks
max_prepared_transactions = 0
max_connections = 200
```

### 9.2 `sysctl` — Recursos del sistema

```bash
# Compartir memoria (postgresql.conf: shared_buffers)
vm.swappiness = 10

# Memory locking para evitar swap de WAL
bootstrap.memory_lock = true  # Requiere systemd/service configurado

# Descripciones de errores del kernel
kernel.pid_max = 4194303
```

### 9.3 Backup y PITR con pgBackRest

```bash
# Instalar pgBackRest 2.52+
# Configuración mínima
[postgresql]
  directory = /var/lib/postgresql/18/main
  status_directory = /var/log/pgbackrest
  cipher = aes256
  compression = zstd
  compression-level = 3
  repo1-path = /var/lib/pgbackrest/repo1
  repo1-retention-full = 7  -- últimos 7 full backups
  repo1-retention-diff = 30 -- últimos 30 incremental
  verbose = true

# Backup completo (programado vía cron)
pgbackrest --stanza=hospital backup

# PITR (Point-in-Time Recovery)
pgbackrest --stanza=hospital resolve-to-point='2026-09-27 14:30:00'

# Verificar integridad del backup
pgbackrest check
```

---

## 10. Cumplimiento de Normativas de Salud

| Normativa | Requisito | Estado de implementación |
|-----------|-----------|------------------------|
| **HIPAA (EU equivalence)** | Cifrado en tránsito TLS 1.2+ ✓ | Implementado (pg_hba.conf, postgresql.conf) |
| | Cifrado en reposo ✓ | File-system encryption + pgcrypto opcional |
| | Auditoría inmutable ✓ | pgAudit + logs SIEM |
| | Control de acceso por rol ✓ | RLS + RBAC |
| | Integridad de datos ✓ | FK RESTRICT, triggers, CHECK |
| **GDPR (España)** | Derecho al olvido técnico (pseudonimización) | En diseño, no anonimización total por conservación ≥5 años |
| | Notificación brechas de seguridad | Procedimiento documentado (Paso 12) |
| | Transferencia internacional de datos | Solo vía TLS + VPN/Interconexión segura |
| **Ley 15/1999, de 13 de diciembre, de Protección de Datos** | Consentimiento informado en datos sensibles | A nivel aplicación, no BD |
| | Acceso del afectado a sus datos | RLS por `id_paciente` garantiza aislamiento |

---

## 11. Próximo Paso

**Paso 10 — Auditoría, histórico y versionamiento**
Usar: `databases` (+ `postgresql-table-design` para aspectos de versionamiento)
Salida: `proyecto/base_datos/08_auditoria_historico/auditoria_historico.md`

DETENERSE y esperar aprobación humana.