# Paso 16 — Configuración de Conexión

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español  
**Generado:** Paso 16 — Conexión  
**Workflow:** `02_database_workflow`  
**Estado previo:** Paso 15 — Generación SQL: VALIDADO (0 fallos)  

---

## 1. Propósito

Documentar la configuración de conexión esperada para el despliegue del esquema SQL generado en el Paso 15, sin crear infraestructura real ni almacenar credenciales en versionado. Cumple con la regla del workflow: *"Leer credenciales solo desde .env. No escribir contraseñas en reportes ni commits."*

---

## 2. Variables de Entorno Requeridas (.env)

El archivo `.env` **no se versiona** y debe colocarse en el directorio de despliegue. Ejemplo de estructura:

```bash
# ===========================================================
# CONFIGURACIÓN DE CONEXIÓN POSTGRESQL 18.6
# Hospital Boliviano Español — Sistema de Citas y Atención Virtual
# ===========================================================

# Servidor
PGHOST=localhost
PGPORT=5432

# Base de datos objetivo (crear antes del despliegue)
PGDATABASE=citas_atencion_virtual

# Usuario de aplicación (rol app_runtime)
PGUSER=app_runtime
PGPASSWORD=<SECRETO_NO_VERSIONADO>

# SSL/TLS (obligatorio según pg_hba.conf corregido)
PGSSLMODE=require
PGSSLROOTCERT=/ruta/a/ca.pem
PGSSLCERT=/ruta/a/client.pem
PGSSLKEY=/ruta/a/client.key

# Pool de conexiones (ajustar según carga)
PGPOOL_MIN=2
PGPOOL_MAX=20

# Timeouts (coherentes con postgresql.conf)
PGCONNECT_TIMEOUT=10
PGSTATEMENT_TIMEOUT=60000
PGIDLE_IN_TRANSACTION_TIMEOUT=180000
```

> **⚠️ Seguridad:** `PGPASSWORD` y certificados SSL **nunca** se commitean. Se inyectan en tiempo de despliegue mediante gestor de secretos (HashiCorp Vault, AWS Secrets Manager, Azure Key Vault, etc.).

---

## 3. Perfiles de Conexión por Entorno

| Entorno | Host | Puerto | Base de Datos | Usuario | SSL |
|---------|------|--------|---------------|---------|-----|
| Desarrollo | localhost | 5432 | citas_dev | app_runtime | require |
| Pruebas (QA) | pg-qa.hbe.local | 5432 | citas_qa | app_runtime | require |
| Staging | pg-stg.hbe.local | 5432 | citas_stg | app_runtime | require |
| Producción | pg-prod.hbe.local | 5432 | citas_prod | app_runtime | require |

---

## 4. Verificación de Conectividad (Pre-despliegue)

Antes de ejecutar el Paso 17 (Despliegue), validar conectividad **solo lectura**:

```bash
# 1. Probar conexión TCP + TLS
psql "postgresql://${PGUSER}:${PGPASSWORD}@${PGHOST}:${PGPORT}/${PGDATABASE}?sslmode=${PGSSLMODE}" -c "SELECT version();"

# 2. Verificar que la BD existe y está vacía (o versión de migración esperada)
psql "..." -c "SELECT * FROM pg_database WHERE datname = '${PGDATABASE}';"

# 3. Verificar roles y privilegios base
psql "..." -c "\du"
psql "..." -c "SELECT has_schema_privilege('app_runtime', 'public', 'CREATE');"
```

> **Nota:** Estas validaciones **no modifican** la base de datos. Solo confirman conectividad, existencia de BD y permisos mínimos.

---

## 5. Configuración de Cliente (Ejemplos)

### 5.1 psql (CLI)

```bash
# Archivo ~/.pgpass (permisos 600)
# hostname:port:database:username:password
localhost:5432:citas_atencion_virtual:app_runtime:<password>
```

```bash
# Conexión rápida
psql "postgresql://app_runtime@localhost:5432/citas_atencion_virtual?sslmode=require"
```

### 5.2 Librerías de Aplicación (Node.js / Python / Java)

**Node.js (pg):**
```javascript
const { Pool } = require('pg');
const pool = new Pool({
  host: process.env.PGHOST,
  port: process.env.PGPORT,
  database: process.env.PGDATABASE,
  user: process.env.PGUSER,
  password: process.env.PGPASSWORD,
  ssl: {
    rejectUnauthorized: true,
    ca: fs.readFileSync(process.env.PGSSLROOTCERT).toString(),
    cert: fs.readFileSync(process.env.PGSSLCERT).toString(),
    key: fs.readFileSync(process.env.PGSSLKEY).toString()
  },
  max: parseInt(process.env.PGPOOL_MAX),
  idleTimeoutMillis: 30000,
  connectionTimeoutMillis: parseInt(process.env.PGCONNECT_TIMEOUT)
});
```

**Python (psycopg2):**
```python
import psycopg2
import os

conn = psycopg2.connect(
    host=os.getenv('PGHOST'),
    port=os.getenv('PGPORT'),
    dbname=os.getenv('PGDATABASE'),
    user=os.getenv('PGUSER'),
    password=os.getenv('PGPASSWORD'),
    sslmode=os.getenv('PGSSLMODE'),
    sslrootcert=os.getenv('PGSSLROOTCERT'),
    sslcert=os.getenv('PGSSLCERT'),
    sslkey=os.getenv('PGSSLKEY')
)
```

---

## 6. Scripts de Migración a Ejecutar (Orden)

Los scripts generados en Paso 13 (Migraciones) y Paso 15 (Generación SQL) deben aplicarse en este orden:

| Orden | Archivo | Descripción |
|-------|---------|-------------|
| 1 | `V1__initial_schema.sql` | Baseline completo (extensiones, ENUMs, tablas, índices, triggers, roles, datos semilla) |
| 2 | `V2__parametros_pendientes.sql` | *Solo cuando D-02 a D-09 tengan decisión aprobada* — insertar valores en `parametros_configuracion` |
| 3 | `V3__indices_adicionales.sql` | *Opcional* — índices basados en métricas reales de carga |
| 4 | `V4__rls_policies.sql` | Políticas RLS completas (ver seguridad.md) |

> **Regla:** `V1__initial_schema.sql` contiene todo el esquema aprobado. Las migraciones posteriores son **incrementales** y requieren decisión humana aprobada antes de generarse.

---

## 7. Rollback de Emergencia

En caso de fallo crítico durante despliegue:

```sql
-- SOLO con autorización explícita por escrito
-- NO ejecutar sin aprobación de DBA y stakeholder

-- 1. Detener aplicación
-- 2. Verificar estado de transacciones activas
SELECT pid, state, query_start, query FROM pg_stat_activity WHERE datname = 'citas_atencion_virtual';

-- 3. Rollback migración específica (si es transaccional)
--    Flyway: flyway undo (requiere scripts de undo)
--    Manual: restaurar desde backup punto-en-el-tiempo (PITR)

-- 4. Notificar a equipo y registrar incidente
```

---

## 8. Checklist Pre-Despliegue (Paso 17)

| Ítem | Verificado | Responsable |
|------|------------|-------------|
| `.env` existe con variables correctas (sin commitear) | ☐ | DevOps |
| BD objetivo creada y vacía / en versión esperada | ☐ | DBA |
| Usuario `app_runtime` existe con rol `app_read` + `app_write` | ☐ | DBA |
| Certificados SSL válidos y accesibles por el proceso | ☐ | DevOps/Seguridad |
| Backup PITR configurado y probado | ☐ | DBA |
| Script `V1__initial_schema.sql` revisado y firmado | ☐ | DBA + Arquitecto |
| Decisiones D-02 a D-09 resueltas (o documentadas como pendientes) | ☐ | Product Owner |
| Plan de rollback documentado y aprobado | ☐ | DBA + Tech Lead |

---

## 9. Próximos Pasos

1. **Paso 17 — Despliegue:** Aplicar `V1__initial_schema.sql` sobre BD objetivo (requiere autorización explícita).
2. **Paso 18 — Verificación de base real:** Comparar esquema desplegado vs diseño (ERD, diccionario, drift detection).
3. **Paso 19 — Pruebas:** Validar PK/FK/UNIQUE/CHECK, auditoría, permisos, transacciones, migraciones.
4. **Paso 20 — Informe final:** Consolidar resultados y cerrar workflow.

---

## 10. Referencias

- `proyecto/base_datos/15_reportes/generacion_sql.md` — SQL completo V1
- `proyecto/base_datos/12_migraciones/migraciones.md` — Estrategia de migraciones
- `proyecto/base_datos/07_seguridad/seguridad.md` — pg_hba.conf, postgresql.conf, roles, RLS
- `proyecto/base_datos/14_revision_dba.md` — STATUS: APPROVED

---

**Nota:** Este documento no contiene credenciales reales. La conexión real se realiza en el Paso 17 bajo autorización explícita y con credenciales inyectadas desde gestor de secretos.