# Decisiones de Frontend

**Estado:** APROBADO

## Contexto

El Frontend corresponde al Sistema de Gestión de Citas y Atención Virtual
del Hospital Boliviano Español.

Debe consumir exclusivamente las funcionalidades actualmente implementadas
y aprobadas en la API y el Backend.

## Stack

PHP Views + HTML5 + CSS3 + JavaScript ES6+ + Fetch API.

No incorporar frameworks de frontend ni herramientas adicionales
sin una decisión explícita del proyecto.

## Arquitectura

El Frontend no accederá directamente a PostgreSQL.

Toda operación deberá realizarse mediante la API.

Flujo esperado:

Usuario
→ Vista
→ JavaScript / Fetch API
→ API REST
→ Controller
→ Service
→ Repository
→ PDO
→ PostgreSQL

## Pantallas iniciales permitidas

- `/login`
- `/dashboard`
- `/especialidades`
- `/medicos`

## Login

La pantalla de login deberá permitir autenticarse utilizando
el Backend ya implementado.

Endpoint:

POST `/api/v1/auth/login`

No implementar un segundo mecanismo de autenticación.

## Dashboard

El dashboard será una pantalla inicial sencilla para el usuario autenticado.

En esta etapa podrá mostrar:

- usuario actualmente autenticado;
- acceso a Especialidades;
- acceso a Médicos;
- cierre de sesión.

No agregar estadísticas o módulos que todavía no existan en el Backend.

## Especialidades

La pantalla deberá consumir la API existente:

- GET `/api/v1/especialidades`
- GET `/api/v1/especialidades/{id}`
- POST `/api/v1/especialidades`
- PUT `/api/v1/especialidades/{id}`
- DELETE `/api/v1/especialidades/{id}`

DELETE representa inactivación lógica mediante `activo = false`.

No realizar eliminación física.

## Médicos

La pantalla deberá consumir exclusivamente los endpoints
de médicos implementados en el Backend.

Permitirá:

- listar;
- consultar;
- registrar;
- modificar;
- inactivar.

La inactivación deberá respetar la lógica existente del Backend.

## Base de datos

Motor existente:

PostgreSQL 18.6

Base de datos:

`hospital_citas_dev`

El Frontend no ejecutará SQL ni utilizará PDO directamente.

## Diseño

La interfaz deberá ser:

- clara;
- profesional;
- coherente con un sistema hospitalario;
- responsive;
- accesible;
- sencilla de utilizar.

Se priorizará primero la funcionalidad y posteriormente
se realizarán mejoras visuales.

## Restricciones

No desarrollar todavía:

- gestión de citas;
- pacientes;
- horarios;
- atención virtual;
- reportes;
- módulos administrativos adicionales.

Estas funcionalidades requieren su correspondiente Backend antes
de incorporarlas al Frontend.

## Regla de desarrollo incremental

Se trabajará un checkpoint por vez.

Cada checkpoint deberá:

1. implementarse;
2. probarse;
3. validarse;
4. recibir aprobación humana;
5. recién entonces permitir avanzar al siguiente.

No ampliar automáticamente el alcance.