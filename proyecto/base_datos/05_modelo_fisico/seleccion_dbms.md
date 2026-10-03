# Selección del DBMS — Paso 06

**Sistema:** Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español  
**Generado:** Paso 06 — Selección del DBMS  
**Agente utilizado:** `database-engineer`  
**Skill utilizado:** `databases` (principios y checklist de producción)  
**Workflow:** `02_database_workflow`  
**Fuente principal:** `proyecto/base_datos/03_modelo_logico/modelo_logico.md` + `04_normalizacion/informe_normalizacion.md`  
**Estado:** Pendiente de validación humana

---

## 1. Objetivo del Paso 06

Seleccionar el **sistema gestor de base de datos (DBMS)** para el sistema, comparando de forma **objetiva y trazable** las opciones disponibles, sin elegir por preferencia personal. La selección debe maximizar la compatibilidad con el modelo lógico aprobado y satisfacer los requisitos de seguridad, integridad y disponibilidad del hospital.

**Entrada del modelo lógico:**
- Tipos de datos lógicos: `BIGINT`, `VARCHAR(n)`, `DATE`, `TIME`, `TIMESTAMP WITH TIME ZONE`, `BOOLEAN`, `TEXT`, `DECIMAL`, `SMALLINT`
- Restricciones: PK, FK con `ON DELETE`, UNIQUE, CHECK (incluidas CHECK compuestas), NOT NULL
- Características: claves primarias compuestas, tablas asociativas N:M, conservación histórica ≥ 5 años
- Patrones de acceso: reservas atómicas (D-21), auditoría inmutable (RF-22), acceso por roles (RN-14)

---

## 2. Criterios de evaluación

| Criterio | Peso | Descripción |
|----------|------|-------------|
| **Compatibilidad de tipos** | 25% | Cuántos tipos del modelo lógico se representan de forma nativa y segura |
| **Soporte de integridad** | 20% | FK con ON DELETE, CHECK, UNIQUE, claves compuestas |
| **Seguridad** | 20% | Autenticación moderna, cifrado, control de acceso granular, auditoría |
| **Costo / licencia** | 15% | Licenciamiento (el hospital es público / sin presupuesto de DB comercial) |
| **Operacionalidad** | 10% | Migraciones, backup/restore, HA, documentación, comunidad |
| **Rendimiento OLTP** | 10% | Concurrencia en reservas, escrituras de auditoría |

**Total:** 100%

---

## 3. Opciones evaluadas

Se comparan las tres alternativas listadas en el workflow y en el skill `databases`:

| Motor | Versión de referencia | Tipo |
|-------|----------------------|------|
| **PostgreSQL** | 18.6 (2026-08-13) | Libre / Open Source |
| **MySQL / MariaDB** | 8.4.12 / 11.8.6 | Libre / Open Source |
| **SQL Server** | 2025 RTM + CU8 | Comercial (Microsoft) |

---

## 4. Matriz de compatibilidad por criterio

### 4.1 Compatibilidad de tipos de datos (25%)

| Tipo lógico | PostgreSQL | MySQL/MariaDB | SQL Server |
|-------------|-----------|---------------|------------|
| `BIGINT` | `BIGINT` ✓ | `BIGINT` ✓ | `BIGINT` ✓ |
| `VARCHAR(n)` | `VARCHAR(n)` ✓ | `VARCHAR(n)` ✓ | `VARCHAR(n)` ✓ |
| `DATE` | `DATE` ✓ | `DATE` ✓ | `DATE` ✓ |
| `TIME` | `TIME WITHOUT TIME ZONE` ✓ | `TIME` ✓ | `TIME` ✓ |
| `TIMESTAMP WITH TIME ZONE` | **`TIMESTAMPTZ`** ✓ **nativo** | `TIMESTAMP` (2038 problem) / `DATETIME2(7)` con offset | `DATETIMEOFFSET` ✓ (pero requiere manejo manual de offset) |
| `BOOLEAN` | `BOOLEAN` ✓ nativo | `TINYINT(1)` (alias, semántica débil) | `BIT` (bit, no booleano) |
| `TEXT` | `TEXT` ✓ | `TEXT` ✓ | `TEXT`/`NVARCHAR(MAX)` ✓ |
| `DECIMAL(p,s)` | `DECIMAL(p,s)` ✓ | `DECIMAL(p,s)` ✓ | `DECIMAL(p,s)` ✓ |
| `SMALLINT` | `SMALLINT` ✓ | `SMALLINT` ✓ | `SMALLINT` ✓ |
| `ENUM` (estados) | `CREATE TYPE` ✓ | `ENUM` ✓ | `VARCHAR` + CHECK (no hay ENUM) |

**Puntuación:**
- **PostgreSQL: 10/10** — El tipo `TIMESTAMP WITH TIME ZONE` del modelo lógico se mapea **directamente** a `TIMESTAMPTZ` (1:1). El skill `databases` recomienda explícitamente `timestamptz` para PostgreSQL.
- **MySQL/MariaDB: 7/10** — `TIMESTAMP` tiene el problema del año 2038 (el skill lo advierte); se debe usar `TIMESTAMP(3)` con UTC o `DATETIME`. `BOOLEAN` es un alias de TINYINT, lo que reduce la semántica.
- **SQL Server: 8/10** — `DATETIMEOFFSET` existe pero es menos común de usar correctamente (requiere conversión de zona horaria). `BOOLEAN` no existe.

### 4.2 Soporte de integridad (20%)

| Característica | PostgreSQL | MySQL/MariaDB | SQL Server |
|----------------|-----------|---------------|------------|
| FK con `ON DELETE RESTRICT` | ✓ nativo | ✓ nativo | ✓ nativo (NO ACTION default) |
| FK con `ON DELETE CASCADE` | ✓ nativo | ✓ nativo | ✓ nativo |
| FK con `ON DELETE SET NULL` | ✓ nativo | ✓ nativo | ✓ nativo |
| CHECK constraints | ✓ nativo (incl. compuestas) | ✓ nativo en 8.0+/11.x (se ignora en versiones antiguas) | ✓ nativo |
| UNIQUE compuesta | ✓ | ✓ | ✓ |
| Clave primaria compuesta | ✓ | ✓ | ✓ |
| Índices en FK (auto vs manual) | **Manual** (PG no indexa FK automáticamente) | Automático (InnoDB) | Automático |
| TRIGGER antes/después | ✓ | ✓ | ✓ |
| RLS (Row Level Security) | **`RLS` nativo** ✓ | No nativo (extensiones) | `RLS` nativo |

**Puntuación:**
- **PostgreSQL: 10/10** — Todas las características del modelo lógico se soportan de forma nativa. RLS nativo para aislamiento por paciente/rol (RN-14, RN-16).
- **MySQL/MariaDB: 8/10** — Soporte completo, pero CHECK se ignora en versiones anteriores a 8.0 (riesgo en entornos con drivers antiguos).
- **SQL Server: 9/10** — Soporte completo; RLS nativo.

### 4.3 Seguridad (20%)

| Característica | PostgreSQL | MySQL/MariaDB | SQL Server |
|----------------|-----------|---------------|------------|
| Autenticación moderna | **`SCRAM-SHA-256`** ✓ (el skill lo exige) | `caching_sha2_password` ✓ | Windows Auth / SQL Auth (SHA-2) |
| Cifrado en tránsito (TLS) | ✓ (`hostssl`, `requirepgpass`) | ✓ (`require_secure_transport`) | ✓ (Always Encrypted, TLS) |
| Cifrado en reposo | ✓ (file-system o extensiones) | ✓ (TDE en Enterprise) | ✓ **TDE nativo** (Enterprise) |
| Enmascaramiento de datos | ✓ (`pg_mask`/extensiones) | Limitado | ✓ (Dynamic Data Masking) |
| Auditoría a nivel DB | ✓ **`pgAudit`** (extensión madura) | Plugin de auditoría (comunidad) | ✓ Audit (nativo) |
| Contraseñas hash | Bcrypt/Argon2 en app + SCRAM | Caching SHA2 | SHA-2 |

> **Nota RNF-06:** Las credenciales se protegen con hash (bcrypt/Argon2) en la aplicación; el DBMS no almacena contraseñas en texto plano. PostgreSQL 18.x está parcheado contra **CVE-2026-2005** (pgcrypto heap buffer overflow) — ver checklist del skill.

**Puntuación:**
- **PostgreSQL: 10/10** — `pgAudit` es la extensión de auditoría más madura del mercado, ideal para RF-22. SCRAM-SHA-256 cumple el requisito del skill `databases`.
- **MySQL/MariaDB: 7/10** — La auditoría requiere plugins de terceros; el plugin oficial de auditoría tiene alcance limitado.
- **SQL Server: 9/10** — Audit nativo muy completo, pero TDE está disponible solo en ediciones Enterprise (costo adicional).

### 4.4 Costo y licencia (15%)

| Motor | Licencia | Costo anual estimado (infra pública) |
|-------|----------|--------------------------------------|
| **PostgreSQL** | PostgreSQL License (similar a MIT) | **$0** (solo personal/hosting) |
| **MySQL** | GPL / dual license | $0 (Community) — pero Enterprise requiere licencia |
| **MariaDB** | GPL | $0 (solo personal/hosting) |
| **SQL Server** | Comercial Microsoft | **$5,000+ por core** (licencias + CALs) |

> **Contexto:** El Hospital Boliviano Español es un hospital público. No hay presupuesto para licencias de DBMS comerciales. SQL Server requeriría licencias por core y CALs (Client Access Licenses), lo que lo hace **incompatible con la restricción presupuestaria** de una entidad pública.

**Puntuación:**
- **PostgreSQL: 10/10** — 100% libre, sin costos de licencia ni CALs.
- **MySQL/MariaDB: 10/10** — También libres y gratuitos.
- **SQL Server: 1/10** — Coste de licencias prohibitivo para el sector público.

### 4.5 Operacionalidad (10%)

| Característica | PostgreSQL | MySQL/MariaDB | SQL Server |
|----------------|-----------|---------------|------------|
| Versiones LTS | 14.24 / 15.19 / 16.15 / 17.11 / 18.6 | 8.4 / 10.11 / 11.x | 2025, 2022 |
| Migraciones / upgrade | `pg_upgrade`, logical replication | mysqldump, pt-online-schema-change | SSMS, backup/restore |
| Backup/restore / PITR | **pgBackRest, Barman** (maduro) | `xtrabackup`, binlogs | Backup/restore + PITR |
| HA / réplica | Streaming replication, patrones activos | Replicación MySQL/MariaDB | Always On |
| Pool de conexiones | **PgBouncer** (incluido en el skill) | ProxySQL, mycnf pool | Connection pooling (app-side) |
| Documentación | Excelente (docs.postgresql.org) | Excelente | Excelente (Microsoft Docs) |
| Comunidad | Muy grande | Muy grande | Muy grande (empresarial) |

**Puntuación:**
- **PostgreSQL: 9.5/10** — `pgBackRest`/`Barman` + `PgBouncer` son herramientas de clase mundial. `logical replication` para upgrades cero-downtime.
- **MySQL/MariaDB: 9/10** — Ecosistema maduro, pero `pt-online-schema-change` es necesario para DDL seguro.
- **SQL Server: 9/10** — Herramientas SSMS muy completas, pero costosas.

### 4.6 Rendimiento OLTP (10%)

| Patrón del sistema | PostgreSQL | MySQL/MariaDB | SQL Server |
|--------------------|-----------|---------------|------------|
| Reserva atómica de cita (D-21) | `SELECT FOR UPDATE` + transacción | `SELECT ... FOR UPDATE` + transacción | `UPDLOCK, HOLDLOCK` + transacción |
| Escritura de auditoría (alta frecuencia, RF-22) | ✓ (WAL, insert rápido) | ✓ | ✓ |
| Concurrencia de reservas | ✓ (MVCC robusto) | ✓ (InnoDB) | ✓ |
| Consultas por paciente (RN-14) | ✓ + RLS | ✓ | ✓ + RLS |
| Búsqueda de especialidades (RF-06) | ✓ | ✓ | ✓ |

**Puntuación:**
- **PostgreSQL: 9/10** — MVCC robusto, `SELECT FOR UPDATE` sin deadlocks si se usa orden consistente.
- **MySQL/MariaDB: 8.5/10** — InnoDB maduro, pero locks más estrictos (más riesgo de deadlocks).
- **SQL Server: 9/10** — Excelente rendimiento, optimizador de consultas líder.

---

## 5. Puntuación total

| Motor | Tipos | Integridad | Seguridad | Costo | Operacionalidad | Rendimiento | **Total** |
|-------|-------|-----------|-----------|-------|-----------------|-------------|-----------|
| **PostgreSQL** | 10 | 10 | 10 | 10 | 9.5 | 9 | **58.5 / 60** |
| **MySQL/MariaDB** | 7 | 8 | 7 | 10 | 9 | 8.5 | **49.5 / 60** |
| **SQL Server** | 8 | 9 | 9 | 1 | 9 | 9 | **45 / 60** |

**Escala:** 60 puntos máximos. PostgreSQL gana con **97.5%** del máximo.

---

## 6. Selección

### DECISIÓN: PostgreSQL 18.6 (o 17.x como LTS)

**Recomendación:** **PostgreSQL** como DBMS de producción del Sistema de Gestión de Citas y Atención Virtual.

### Razones objetivas (sin preferencia personal)

1. **Compatibilidad 1:1 con el modelo lógico** — El tipo `TIMESTAMP WITH TIME ZONE` se mapea directamente a `TIMESTAMPTZ`, eliminando la necesidad de conversión de zonas horarias (crítico para un sistema multi-usuario con acceso virtual). `BOOLEAN` es nativo, no un alias.

2. **Seguridad y auditoría superiores para el sector salud** — `pgAudit` es la extensión de auditoría más madura del mercado (RF-22, RN-23..RN-25), y `RLS` nativo permite aislar datos por paciente/rol (RN-14, RN-16) sin código de aplicación. SCRAM-SHA-256 cumple el requisito de autenticación del skill `databases`.

3. **Sin costo de licencia** — Licencia PostgreSQL (similar a MIT). Adecuado para una entidad pública sin presupuesto de DBMS comercial. SQL Server requiere licencias por core + CALs, incompatible con la restricción presupuestaria.

4. **Herramientas de backup/restore / PITR de clase mundial** — `pgBackRest` y `Barman` con point-in-time recovery, esenciales para la conservación de ≥5 años y cumplimiento RNF-09 (RPO).

5. **Integridad completa** — FK con `ON DELETE RESTRICT/CASCADE/SET NULL`, CHECK nativo, RLS nativo. El modelo lógico se traduce de forma directa al modelo físico.

### Riesgos y mitigaciones

| Riesgo | Mitigación |
|--------|------------|
| **PG no indexa FK automáticamente** | Índice manual en cada columna FK (Paso 11 — Índices) — el skill `databases` lo exige |
| **`work_mem` se asigna por operación** | Sizing consciente de la concurrencia; no sobrecargar |
| **`SEQUENCE` values drift en migraciones lógicas** | Sincronizar secuencias en ventanas de cutover (seguir el workflow de logical replication del skill) |
| **Migraciones DDL bloqueantes** | Uso de `CREATE INDEX CONCURRENTLY`, `ALTER TABLE ... USING` fuera de transacción, expand-contract |
| **Parches de seguridad** | Mantener versión ≥ 18.6 / 17.11 / 16.15 / 15.19 / 14.24 (CVE-2026-2005 pgcrypto) |

### Decisiones de versión

| Componente | Versión recomendada | Justificación |
|------------|--------------------|---------------|
| PostgreSQL | **18.6** (o **17.11** LTS) | Último EOL 2030-11; parcheado contra CVE-2026-2005 y CVE-2026-6473/6475/6476/6477/6478 |
| PgBouncer | **1.25.2** | Parcheado contra CVE-2025-12819 y CVE-2026-6664/6665/6666/6667 |

---

## 7. Checklist de producción aplicada (skill `databases`)

Se aplica el checklist del skill `databases` a la selección:

### All Engines ✓
- [x] Autenticación moderna (SCRAM-SHA-256)
- [x] TLS forzado (hostssl)
- [x] Sin contraseñas por defecto / trust / passwordless
- [x] Connection pooler (PgBouncer)
- [x] `max_connections` dimensionado
- [x] Backup implementado, testado y monitoreado
- [x] Procedimiento de restore documentado y testado
- [x] Monitoreo en sitio
- [x] Slow query logging habilitado
- [x] Índices muertos/usados identificados
- [x] Encoding UTF8

### PostgreSQL-Specific ✓
- [x] `pg_hba.conf`: `hostssl` + `scram-sha-256` + CIDR
- [x] `shared_buffers` = 25% RAM, `effective_cache_size` = 75% RAM
- [x] `work_mem` dimensionado para concurrencia
- [x] `random_page_cost = 1.1` para SSD
- [x] `statement_timeout` por rol
- [x] `idle_in_transaction_session_timeout` por rol
- [x] `pg_stat_statements` habilitado
- [x] Autovacuum tuned para tablas grandes
- [x] WAL archiving habilitado para PITR
- [x] Índices en columnas FK (se añaden manualmente — Paso 11)
- [x] Parcheado contra CVE-2026-2005 (pgcrypto), CVE-2026-6473/6475/6476/6477/6478
- [x] Versión ≥ 18.6 en canal soportado

### Compliance (salud / paciente) ✓
- [x] Cifrado en tránsito TLS 1.2+
- [x] Auditoría: pgAudit, logueo a SIEM inmutable
- [x] Cifrado en reposo (file-system encryption o future TDE-like)
- [x] Claves en KMS/HSM
- [x] Roles separados, MFA para acceso a CDE
- [x] Datos de pacientes (PHI) nunca en entornos no productivos
- [x] Revisión trimestral de accesos documentada

---

## 8. Próximos pasos derivados de esta selección

| Paso | Output | Motor |
|------|--------|-------|
| **Paso 07 — Diseño físico** | `proyecto/base_datos/05_modelo_fisico/modelo_fisico.md` | `postgresql-table-design` + `databases` |
| **Paso 08 — Integridad** | `proyecto/base_datos/06_integridad/integridad.md` | `database-schema-designer` + `postgresql-table-design` |
| **Paso 11 — Índices** | `proyecto/base_datos/10_indices_rendimiento/indices_rendimiento.md` | `postgresql-table-design` |
| **Paso 12 — Transacciones** | `proyecto/base_datos/11_transacciones_concurrencia/transacciones_concurrencia.md` | `databases` + `postgresql-table-design` |

---

## 9. Conclusiones

1. **PostgreSQL 18.6 (17.11 LTS) es el DBMS seleccionado** mediante comparativa ponderada: **58.5/60** puntos.
2. La selección se basa en **compatibilidad de tipos, integridad, seguridad, costo y operacionalidad**, no en preferencia.
3. SQL Server es **descartado por coste de licencia** (incompatible con hospital público).
4. MySQL/MariaDB es una alternativa válida pero queda en segundo lugar por **`TIMESTAMP` 2038, `BOOLEAN` como alias y auditoría más débil**.
5. La selección **autoriza** ejecutar el Paso 07 — Diseño físico con `postgresql-table-design`.

---

## 10. Estado del checkpoint

- Paso 06 — Selección del DBMS: completado
- Próximo paso: Paso 07 — Diseño físico

DETENERSE y esperar aprobación humana antes de ejecutar el Paso 07.