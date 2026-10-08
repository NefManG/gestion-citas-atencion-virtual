# Workflow 03 — API piloto: Especialidades

Tabla fija: `especialidad`.

Base de datos existente: `hospital_citas_dev`.
DBMS: PostgreSQL 18.6.

Skills:
- `php-development`
- `api-design-principles`
- `openapi-spec-generation`

## CP-API-01
Diseñar contrato de especialidades.
No programar.
STOP.

## CP-API-02
Bootstrap PHP + `.env` + PDO PostgreSQL + `/api/v1/health`.
STOP.

## CP-API-03
GET `/api/v1/especialidades`.
Probar colección con datos, vacía y error DB.
STOP.

## CP-API-04
GET `/api/v1/especialidades/{id}`.
Probar existente, inexistente e ID inválido.
STOP.

## CP-API-05
POST especialidad + fixtures válidos e inválidos.
STOP.

## CP-API-06
PUT especialidad + validaciones.
STOP.

## CP-API-07
DELETE lógico mediante `activo = false`.
Nunca ejecutar `DELETE FROM especialidad`.
STOP.

## CP-API-08
OpenAPI + suite CRUD completa.
Terminar en `HUMAN_STATUS: PENDING`.
No iniciar backend.