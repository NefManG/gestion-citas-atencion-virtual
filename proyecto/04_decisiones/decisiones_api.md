# Decisiones de API

**Estado:** APROBADO

- PHP 8.x puro.
- Sin framework.
- PostgreSQL 18.6.
- PDO con driver PostgreSQL.
- REST + JSON.
- Prefijo de API: `/api/v1/`.

## API piloto

Tabla: `especialidad`.

Endpoints:

- GET `/api/v1/especialidades`
- GET `/api/v1/especialidades/{id}`
- POST `/api/v1/especialidades`
- PUT `/api/v1/especialidades/{id}`
- DELETE `/api/v1/especialidades/{id}`

## Eliminación lógica

DELETE significa inactivar:

`activo = false`

Nunca realizar eliminación física mediante:

`DELETE FROM especialidad`

## Seguridad

- prepared statements;
- validación server-side;
- credenciales únicamente mediante variables de entorno;
- sin secretos en Git;
- sin stack traces en producción.

## Validación humana

Un mensaje `continúa` aprueba únicamente el checkpoint anterior si se encuentra en estado PASSED y habilita un solo checkpoint nuevo.