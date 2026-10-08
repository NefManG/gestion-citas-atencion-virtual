# Decisiones de Alcance

**Estado:** APROBADO

## Sistema

Sistema de Gestión de Citas y Atención Virtual — Hospital Boliviano Español.

## Alcance de la fase actual

La fase de desarrollo continuará sobre la base de datos existente
`hospital_citas_dev`, implementada previamente en PostgreSQL 18.6.

La implementación se realizará de forma incremental y con validación
humana obligatoria.

## API piloto

La tabla seleccionada para la API piloto será:

`especialidad`

Esta tabla será utilizada para comprobar:

- conexión PHP con PostgreSQL;
- funcionamiento de PDO;
- diseño de API REST;
- consultas reales a la base de datos;
- validaciones;
- operaciones CRUD;
- inactivación lógica.

## Tabla piloto fija

`especialidad`

Campos principales existentes:

- `id_especialidad`
- `nombre`
- `descripcion`
- `activo`
- `created_at`
- `updated_at`

## Eliminación

Las especialidades no serán eliminadas físicamente.

La operación DELETE de la API deberá realizar:

`activo = false`

No ejecutar:

`DELETE FROM especialidad`

## Restricción de alcance

Durante la API piloto no implementar:

- pacientes;
- médicos;
- citas;
- horarios;
- usuarios;
- roles;
- permisos;
- atención virtual;
- otros módulos existentes en la base de datos.

Estos módulos podrán ser incorporados posteriormente según el workflow
y las decisiones aprobadas.

## Arquitectura

Se mantiene la arquitectura previamente seleccionada:

Monolito modular con cliente web desacoplado y API de aplicación.

Para la implementación en PHP se utilizará organización MVC y separación:

Request
→ Router
→ Controller
→ Validator
→ Service
→ Repository
→ PDO
→ PostgreSQL

## Regla

Ejecutar un solo checkpoint por interacción.

Cada checkpoint debe:

1. ejecutarse;
2. probarse;
3. actualizar el state;
4. terminar con `HUMAN_STATUS: PENDING`;
5. detenerse hasta recibir aprobación humana.