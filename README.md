# Sistema de Gestión de Citas y Atención Virtual

Proyecto desarrollado como parte del módulo **Desarrollo al Lado del Cliente y Servidor Web** de la **Maestría en Ingeniería de Software y Sistemas Informáticos**.

El sistema toma como contexto de aplicación al **Hospital Boliviano Español** y constituye una solución web orientada a la gestión progresiva de información relacionada con médicos, especialidades, usuarios y atención médica.

El proyecto integra componentes del lado del cliente, procesamiento del lado del servidor, una API REST y una base de datos PostgreSQL.

Como parte del Trabajo Final se desarrolló además una **Landing Page funcional**, integrada con el Backend y la fuente de datos del sistema.

---

# Datos académicos

**Participante:**  
Lic. Neftali Mamani Garcia

**Docente:**  
Ph.D. Alan Rodrigo Corini Guarachi

**Módulo:**  
Desarrollo al Lado del Cliente y Servidor Web

**Programa:**  
Maestría en Ingeniería de Software y Sistemas Informáticos

**Gestión:**  
2026

**Lugar:**  
Cochabamba - Bolivia

---

# Descripción del proyecto

El proyecto corresponde a un **Sistema de Gestión de Citas y Atención Virtual** desarrollado tomando como contexto de aplicación al Hospital Boliviano Español.

La solución se encuentra organizada en diferentes componentes que permiten demostrar la comunicación entre cliente, servidor y base de datos.

Actualmente se encuentran implementadas funcionalidades relacionadas principalmente con:

- autenticación de usuarios;
- manejo de sesión;
- cierre de sesión;
- gestión de especialidades médicas;
- gestión de médicos;
- API REST;
- integración con PostgreSQL;
- eliminación lógica;
- Frontend administrativo;
- Landing Page pública;
- consulta dinámica de especialidades;
- consulta dinámica de médicos;
- diseño responsive;
- accesibilidad básica;
- interacción mediante JavaScript;
- procesamiento mediante PHP;
- comunicación cliente-servidor;
- validaciones mediante agentes;
- skills especializados;
- workflows;
- checkpoints;
- revisión humana.

El sistema completo contempla funcionalidades adicionales relacionadas con pacientes, horarios, disponibilidad, reservas, citas, reprogramaciones, cancelaciones, historial, auditoría y atención virtual, las cuales pueden incorporarse progresivamente.

---

# Metodología de desarrollo

El desarrollo del proyecto se realizó de manera progresiva mediante una metodología apoyada en:

- agentes de inteligencia artificial;
- skills especializados;
- workflows;
- checkpoints;
- pruebas técnicas;
- validación humana.

Estas herramientas fueron utilizadas como apoyo durante las diferentes etapas del proyecto, tanto en el análisis como en el Backend, Frontend, API, base de datos y Landing Page.

El principio aplicado durante el desarrollo fue:

```text
Análisis
   ↓
Implementación
   ↓
Agente / Skill
   ↓
Revisión técnica
   ↓
Checkpoint
   ↓
Pruebas
   ↓
Revisión humana
   ↓
PASSED + APPROVED
   ↓
Siguiente etapa
```

Los resultados propuestos por agentes o skills no fueron considerados definitivos de manera automática.

Cada etapa fue revisada, probada y validada antes de continuar.

---

# Componentes principales

La solución está formada principalmente por:

```text
Landing Page pública
        +
Frontend administrativo
        +
Backend PHP
        +
API REST
        +
PostgreSQL
```

Cada componente cumple una responsabilidad específica dentro de la aplicación.

---

# Landing Page

La Landing Page constituye la interfaz pública del Sistema de Gestión de Citas y Atención Virtual.

Su propósito es presentar de manera clara información relacionada con el Hospital Boliviano Español, los servicios disponibles, las especialidades médicas y los médicos registrados.

La Landing Page funciona de manera independiente del panel administrativo y no requiere autenticación para consultar información pública.

Entre sus principales características se encuentran:

- diseño moderno;
- interfaz responsive;
- navegación entre secciones;
- menú adaptable para dispositivos móviles;
- sección principal o Hero;
- presentación de servicios;
- visualización de especialidades médicas;
- visualización de médicos activos;
- información obtenida dinámicamente;
- procesamiento mediante PHP;
- interacción mediante JavaScript;
- estructura HTML semántica;
- criterios básicos de accesibilidad;
- soporte para `prefers-reduced-motion`;
- consultas de solo lectura.

La Landing Page no realiza operaciones de creación, actualización o eliminación sobre la base de datos.

---

# Frontend administrativo

El proyecto cuenta también con un **Frontend administrativo** que permite interactuar con las funcionalidades implementadas en el Backend mediante una interfaz gráfica.

Entre las principales vistas desarrolladas se encuentran:

- inicio de sesión;
- dashboard administrativo;
- navegación principal;
- gestión de especialidades;
- gestión de médicos;
- formularios de registro;
- formularios de edición;
- visualización de registros;
- inactivación lógica;
- manejo de sesión.

El Frontend se comunica con el Backend mediante los endpoints disponibles en la API REST.

Durante su desarrollo también se trabajó con apoyo de **agentes, skills especializados, workflows y checkpoints** para revisar aspectos relacionados con:

- estructura de la interfaz;
- organización visual;
- navegación;
- formularios;
- responsive;
- accesibilidad;
- consistencia;
- experiencia de usuario;
- animaciones;
- comportamiento técnico;
- integración con el Backend.

El desarrollo del Frontend se realizó de forma progresiva, manteniendo la revisión y decisión final por parte del estudiante.

---

# Arquitectura general

La aplicación utiliza una arquitectura organizada por responsabilidades.

```text
Usuario / Navegador
        ↓
Landing Page / Frontend
HTML + CSS + JavaScript
        ↓
Servidor PHP
        ↓
Router
        ↓
Controller
        ↓
Validator
        ↓
Service
        ↓
Repository
        ↓
PDO
        ↓
PostgreSQL
```

---

# Arquitectura del Backend

El Backend se encuentra organizado principalmente mediante los siguientes componentes:

## Router

Identifica la ruta solicitada y dirige la petición hacia el controlador correspondiente.

## Controller

Recibe las solicitudes HTTP y coordina la respuesta que será enviada al cliente.

## Validator

Verifica que los datos recibidos cumplan con las condiciones requeridas.

## Service

Contiene la lógica correspondiente a las funcionalidades implementadas.

## Repository

Gestiona las operaciones de acceso a la información almacenada en PostgreSQL.

## Database

Administra la conexión entre PHP y PostgreSQL mediante PDO.

---

# Frontend, Backend y base de datos

La solución permite diferenciar claramente las responsabilidades de cada parte.

## Frontend

Responsable de:

- interfaz gráfica;
- navegación;
- formularios;
- interacción;
- Landing Page;
- panel administrativo;
- diseño responsive;
- accesibilidad;
- experiencia del usuario.

Tecnologías principales:

```text
HTML5
CSS3
JavaScript
```

## Backend

Responsable de:

- recepción de solicitudes;
- validaciones;
- autenticación;
- manejo de sesión;
- lógica;
- acceso a datos;
- API REST;
- respuestas HTTP.

Tecnología principal:

```text
PHP 8.2
```

## Base de datos

Responsable de:

- persistencia;
- almacenamiento;
- consulta;
- estados activos e inactivos.

Tecnología:

```text
PostgreSQL 18.6
```

---

# Tecnologías utilizadas

| Tecnología | Uso en el proyecto |
|---|---|
| HTML5 | Estructura de las interfaces |
| CSS3 | Diseño visual y responsive |
| JavaScript | Interacción del lado del cliente |
| PHP 8.2 | Procesamiento del lado del servidor |
| PostgreSQL 18.6 | Base de datos |
| PDO | Comunicación entre PHP y PostgreSQL |
| REST | Organización de la API |
| Git | Control de versiones |
| GitHub | Repositorio remoto |
| Claude Code | Apoyo mediante agentes, análisis y validaciones |

---

# Base de datos

El proyecto utiliza:

```text
PostgreSQL 18.6
```

La base de datos utilizada durante el desarrollo es:

```text
hospital_citas_dev
```

La conexión entre PHP y PostgreSQL se realiza mediante **PDO**.

Las operaciones de acceso a datos se encuentran organizadas principalmente dentro de los componentes Repository.

---

# API REST

Los endpoints del Backend se encuentran organizados bajo la versión:

```text
/api/v1/
```

---

## API de Especialidades

| Método | Endpoint | Función |
|---|---|---|
| GET | `/api/v1/especialidades` | Listar especialidades activas |
| GET | `/api/v1/especialidades/{id}` | Consultar una especialidad |
| POST | `/api/v1/especialidades` | Registrar una especialidad |
| PUT | `/api/v1/especialidades/{id}` | Modificar una especialidad |
| DELETE | `/api/v1/especialidades/{id}` | Inactivar una especialidad |

---

## API de Médicos

| Método | Endpoint | Función |
|---|---|---|
| GET | `/api/v1/medicos` | Listar médicos activos |
| GET | `/api/v1/medicos/{id}` | Consultar un médico |
| POST | `/api/v1/medicos` | Registrar un médico |
| PUT | `/api/v1/medicos/{id}` | Modificar un médico |
| DELETE | `/api/v1/medicos/{id}` | Inactivar un médico |

---

## Autenticación

| Método | Endpoint | Función |
|---|---|---|
| POST | `/api/v1/auth/login` | Iniciar sesión |
| GET | `/api/v1/auth/me` | Consultar sesión actual |
| POST | `/api/v1/auth/logout` | Cerrar sesión |

---

# Health Check

La aplicación incorpora el endpoint:

```http
GET /api/v1/health
```

Este endpoint permite comprobar:

- funcionamiento de PHP;
- funcionamiento del Backend;
- disponibilidad de PostgreSQL;
- comunicación mediante PDO;
- estado general de la aplicación.

---

# Eliminación lógica

El sistema implementa eliminación lógica para determinados registros.

En lugar de eliminar físicamente los registros de PostgreSQL, se modifica su estado:

```text
activo = false
```

De esta manera:

- el registro permanece almacenado;
- se conserva la información;
- deja de aparecer entre los registros activos.

Este comportamiento se utiliza principalmente para médicos y especialidades.

---

# Comunicación cliente-servidor

La aplicación demuestra la comunicación entre los diferentes componentes de una aplicación web.

```text
Landing Page / Frontend
          ↓
         PHP
          ↓
        Router
          ↓
      Controller
          ↓
      Validator
          ↓
       Service
          ↓
     Repository
          ↓
         PDO
          ↓
     PostgreSQL
          ↓
       Respuesta
          ↓
Landing Page / Frontend
```

---

# Comunicación de la Landing Page con PostgreSQL

La Landing Page consulta información almacenada en PostgreSQL.

El flujo general es:

```text
Usuario
   ↓
Landing Page
   ↓
PHP
   ↓
Repository
   ↓
PDO
   ↓
PostgreSQL
   ↓
Especialidades y médicos activos
   ↓
Landing Page
```

Las operaciones realizadas desde la Landing Page son de **solo lectura**.

No se realizan:

```text
INSERT
UPDATE
DELETE
```

desde la interfaz pública.

---

# Agentes de inteligencia artificial

Durante las diferentes etapas del proyecto se utilizaron agentes especializados como herramientas de apoyo.

Los agentes fueron utilizados para realizar tareas relacionadas con:

- análisis;
- revisión;
- arquitectura;
- validación;
- base de datos;
- Backend;
- API;
- Frontend;
- Landing Page;
- pruebas técnicas.

Los agentes no reemplazaron la revisión humana.

Las recomendaciones obtenidas fueron evaluadas antes de ser aceptadas.

---

## requirements-analyst

Se utilizó para revisar los requerimientos funcionales y no funcionales.

Permitió identificar:

- ambigüedades;
- inconsistencias;
- omisiones;
- duplicidades;
- problemas de verificabilidad;
- posibles riesgos.

---

## database-engineer

Fue utilizado como apoyo durante actividades relacionadas con:

- diseño de base de datos;
- análisis del modelo;
- generación y revisión de SQL;
- validación de estructuras;
- implementación en PostgreSQL.

---

## solution-leader

Se utilizó como apoyo durante la consolidación de decisiones técnicas y arquitectónicas.

Permitió revisar diferentes alternativas antes de establecer una decisión final.

---

## Agentes de Backend y API

Durante el desarrollo del Backend y la API se utilizaron procesos apoyados por agentes para revisar:

- contratos de endpoints;
- arquitectura;
- estructura de capas;
- conexión con PostgreSQL;
- control de errores;
- autenticación;
- pruebas;
- checkpoints.

---

## Agentes aplicados al Frontend

Durante el desarrollo del Frontend administrativo y de la Landing Page también se utilizaron agentes como apoyo para:

- analizar la estructura de la interfaz;
- revisar componentes;
- validar navegación;
- verificar responsive;
- analizar accesibilidad;
- revisar interacción;
- verificar integración con el Backend;
- validar el resultado final.

El Frontend fue trabajado mediante un proceso iterativo de implementación, revisión técnica y validación humana.

---

# Skills utilizados

Durante las diferentes etapas se utilizaron skills especializados según el tipo de trabajo realizado.

---

## Skills de Backend y API

```text
php-development
api-design-principles
```

Estos skills sirvieron como apoyo para:

- desarrollo PHP;
- organización de endpoints;
- diseño REST;
- separación de responsabilidades;
- revisión técnica del Backend.

---

# Skill de decisiones arquitectónicas

```text
decision-consolidation
```

Se utilizó como apoyo durante la consolidación de decisiones técnicas y arquitectónicas.

---

# Skills de Frontend y Landing Page

Durante el desarrollo y revisión del Frontend y la Landing Page se utilizaron skills especializados relacionados con diseño, experiencia de usuario y animaciones.

```text
design-taste-frontend
impeccable
animate
review-animations
improve-animations
web-design-guidelines
```

---

## design-taste-frontend

Utilizado como apoyo para revisar:

- composición;
- jerarquía visual;
- organización de elementos;
- coherencia del diseño.

---

## impeccable

Utilizado como apoyo para revisar la calidad general de la interfaz.

---

## animate

Utilizado como apoyo durante la incorporación de animaciones y transiciones.

---

## review-animations

Utilizado para revisar:

- comportamiento de animaciones;
- duración;
- fluidez;
- consistencia.

---

## improve-animations

Utilizado como apoyo para mejorar transiciones y efectos de movimiento.

---

## web-design-guidelines

Utilizado para revisar buenas prácticas relacionadas con:

- estructura web;
- navegación;
- responsive;
- usabilidad;
- accesibilidad;
- interacción;
- organización visual.

---

# Aplicación de agents y skills al Frontend

El proceso aplicado al Frontend puede representarse así:

```text
Diseño inicial
     ↓
Implementación
     ↓
Agent / Skill
     ↓
Revisión visual y técnica
     ↓
Checkpoint
     ↓
Pruebas
     ↓
Revisión humana
     ↓
Ajustes
     ↓
PASSED + APPROVED
```

Los agents y skills fueron herramientas de apoyo durante el proceso.

La decisión final sobre cada modificación fue realizada mediante revisión humana.

---

# Aplicación de agents y skills a la Landing Page

La Landing Page también fue sometida a procesos de revisión mediante agentes y skills.

Se revisaron aspectos relacionados con:

- Hero principal;
- navegación;
- secciones;
- especialidades;
- médicos;
- CTA;
- responsive;
- accesibilidad;
- estructura semántica;
- comportamiento del menú;
- animaciones;
- `prefers-reduced-motion`;
- JavaScript;
- comunicación con PHP;
- comunicación con PostgreSQL.

La validación final permitió comprobar que la Landing Page:

```text
carga correctamente
+
consulta datos reales
+
se adapta a diferentes pantallas
+
mantiene navegación funcional
+
respeta consultas de solo lectura
```

---

# Workflows

El proyecto se desarrolló utilizando workflows organizados por etapas.

Los workflows permitieron controlar el avance del proyecto de manera progresiva.

Entre los procesos trabajados se encuentran:

- análisis de requerimientos;
- diseño de base de datos;
- implementación de PostgreSQL;
- API piloto;
- Backend;
- autenticación;
- especialidades;
- médicos;
- Frontend;
- Landing Page;
- pruebas;
- validación final.

Entre los workflows utilizados se encuentran:

```text
.agents/workflows/03_api_pilot_workflow.md
.agents/workflows/04_backend_workflow.md
```

Además, el proyecto mantiene archivos de estado asociados a los diferentes procesos de validación.

---

# Checkpoints

Durante el desarrollo se utilizaron checkpoints para verificar cada etapa antes de avanzar.

Ejemplo:

```text
Implementación
     ↓
Checkpoint
     ↓
Validación técnica
     ↓
Pruebas
     ↓
Revisión humana
     ↓
PASSED
     ↓
APPROVED
     ↓
Siguiente etapa
```

Este proceso permitió evitar continuar sobre componentes que todavía presentaban problemas.

---

# Human in the Loop

El proyecto mantiene el principio de **revisión humana durante todo el proceso**.

El flujo utilizado fue:

```text
Agente analiza
      ↓
Agente propone
      ↓
Estudiante revisa
      ↓
Se realizan pruebas
      ↓
Estudiante acepta, modifica o rechaza
```

Las herramientas de inteligencia artificial fueron utilizadas como apoyo y no como sustituto de la toma de decisiones.

---

# Diseño responsive

La Landing Page y el Frontend fueron desarrollados considerando diferentes tamaños de pantalla.

Se contemplaron:

- computadoras de escritorio;
- laptops;
- tabletas;
- teléfonos móviles.

Para lograr esta adaptación se utilizaron:

```text
CSS
Media Queries
Layouts adaptables
Menú responsive
```

---

# Accesibilidad

La Landing Page incorpora criterios básicos de accesibilidad.

Entre ellos se encuentran:

- estructura HTML semántica;
- elemento `main`;
- navegación identificable;
- enlace de salto al contenido;
- atributos ARIA donde corresponde;
- navegación mediante teclado;
- comportamiento accesible del menú;
- compatibilidad con `prefers-reduced-motion`.

---

# Respuestas HTTP

La API utiliza códigos HTTP para indicar el resultado de las operaciones.

Entre los principales códigos utilizados se encuentran:

```text
200 OK
201 Created
400 Bad Request
401 Unauthorized
404 Not Found
409 Conflict
500 Internal Server Error
```

---

# Estructura general del proyecto

La estructura principal del proyecto es similar a:

```text
gestion-citas-atencion-virtual/
│
├── .agents/
│   ├── agents/
│   ├── skills/
│   ├── workflows/
│   └── state/
│
├── proyecto/
│   └── 06_codigo/
│       │
│       ├── app/
│       │   ├── Controllers/
│       │   ├── Core/
│       │   ├── Repositories/
│       │   ├── Services/
│       │   └── Validators/
│       │
│       ├── config/
│       │   ├── app.php
│       │   └── database.php
│       │
│       ├── public/
│       │   ├── index.php
│       │   └── ...
│       │
│       ├── routes/
│       │   └── api.php
│       │
│       └── ...
│
└── README.md
```

---

# Configuración

El proyecto utiliza variables de entorno para configuraciones sensibles.

Ejemplo:

```env
DB_HOST=localhost
DB_PORT=5432
DB_NAME=hospital_citas_dev
DB_USER=postgres
DB_PASSWORD=TU_PASSWORD
```

El archivo `.env` con credenciales reales no debe publicarse en GitHub.

---

# Cómo ejecutar el proyecto

## Requisitos previos

Antes de ejecutar el sistema se debe contar con:

- PHP 8.2 o superior;
- PostgreSQL;
- Git;
- navegador web moderno;
- base de datos `hospital_citas_dev`;
- estructura de base de datos previamente cargada;
- configuración correcta de conexión.

---

## 1. Clonar el repositorio

Abrir una terminal y ejecutar:

```bash
git clone https://github.com/NefManG/gestion-citas-atencion-virtual.git
```

---

## 2. Ingresar al proyecto

```bash
cd gestion-citas-atencion-virtual
```

Luego ingresar al directorio del código:

```bash
cd proyecto/06_codigo
```

---

## 3. Verificar PostgreSQL

Antes de iniciar la aplicación se debe comprobar que PostgreSQL se encuentre ejecutándose.

La base de datos utilizada es:

```text
hospital_citas_dev
```

---

## 4. Configurar la conexión

Configurar las variables correspondientes al entorno local.

```env
DB_HOST=localhost
DB_PORT=5432
DB_NAME=hospital_citas_dev
DB_USER=postgres
DB_PASSWORD=TU_PASSWORD
```

El usuario y contraseña deben corresponder a la instalación local de PostgreSQL.

---

## 5. Ejecutar el servidor PHP

Desde:

```text
proyecto/06_codigo
```

ejecutar:

```bash
php -S localhost:8000 -t public
```

La terminal debe permanecer abierta mientras se utiliza el sistema.

---

## 6. Abrir la Landing Page

Desde el navegador ingresar a:

```text
http://localhost:8000
```

Esta dirección permite visualizar la Landing Page pública.

---

## 7. Verificar el Backend

Ingresar a:

```text
http://localhost:8000/api/v1/health
```

Una respuesta correcta permite comprobar:

```text
PHP            ✓
Backend        ✓
PostgreSQL     ✓
PDO            ✓
Aplicación     ✓
```

---

# Inicio rápido

Una vez configurada la base de datos:

```bash
git clone https://github.com/NefManG/gestion-citas-atencion-virtual.git

cd gestion-citas-atencion-virtual/proyecto/06_codigo

php -S localhost:8000 -t public
```

Luego abrir:

```text
http://localhost:8000
```

Para verificar el Backend:

```text
http://localhost:8000/api/v1/health
```

---

# Detener el servidor

Para detener el servidor PHP, regresar a la terminal donde está ejecutándose y presionar:

```text
Ctrl + C
```

---

# Flujo para ejecutar la aplicación

```text
1. Iniciar PostgreSQL
        ↓
2. Verificar hospital_citas_dev
        ↓
3. Configurar variables de conexión
        ↓
4. Ejecutar PHP
        ↓
5. Abrir localhost:8000
        ↓
6. Landing Page / Frontend
```

---

# Pruebas realizadas

Durante el desarrollo se realizaron pruebas orientadas a comprobar:

- conexión con PostgreSQL;
- endpoint health;
- API de especialidades;
- API de médicos;
- autenticación;
- manejo de sesión;
- cierre de sesión;
- respuestas HTTP;
- eliminación lógica;
- consulta dinámica de información;
- Landing Page;
- Frontend administrativo;
- navegación;
- formularios;
- diseño responsive;
- menú móvil;
- visualización en diferentes dispositivos;
- especialidades activas;
- médicos activos;
- ausencia de operaciones de escritura desde la Landing Page;
- arquitectura por responsabilidades;
- integración cliente-servidor;
- validaciones con agentes;
- validaciones mediante skills;
- checkpoints;
- revisión humana.

---

# Alcance actual

La versión actual constituye una base funcional del Sistema de Gestión de Citas y Atención Virtual.

Actualmente se encuentran implementados:

```text
Landing Page
      +
Frontend administrativo
      +
Autenticación
      +
Especialidades
      +
Médicos
      +
API REST
      +
Backend PHP
      +
PostgreSQL
```

El sistema completo contempla posteriormente:

- pacientes;
- horarios médicos;
- disponibilidad;
- citas;
- reservas;
- reprogramaciones;
- cancelaciones;
- historial;
- atención presencial;
- atención virtual;
- auditoría.

---

# Repositorio GitHub

Repositorio oficial del proyecto:

```text
https://github.com/NefManG/gestion-citas-atencion-virtual
```

---

# Estado actual del proyecto

**Estado:** Funcional para el alcance implementado.

Actualmente se encuentran operativos:

- Landing Page pública;
- Frontend administrativo;
- Backend PHP;
- API REST;
- PostgreSQL;
- conexión PDO;
- autenticación;
- manejo de sesión;
- especialidades;
- médicos;
- eliminación lógica;
- diseño responsive;
- integración cliente-servidor;
- agentes especializados;
- skills de Backend;
- skills de Frontend;
- workflows;
- checkpoints;
- revisión humana.

---

# Trabajo académico

Proyecto desarrollado para la:

**Maestría en Ingeniería de Software y Sistemas Informáticos**

Módulo:

**Desarrollo al Lado del Cliente y Servidor Web**

Docente:

**Ph.D. Alan Rodrigo Corini Guarachi**

Participante:

**Lic. Neftali Mamani Garcia**

**Cochabamba - Bolivia, 2026**