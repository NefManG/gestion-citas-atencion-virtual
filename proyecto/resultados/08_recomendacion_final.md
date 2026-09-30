# Recomendación Arquitectónica Final

**Proyecto:** Sistema de Gestión de Citas y Atención Virtual  
**Contexto académico:** Hospital Boliviano Español  
**Documento:** Consolidación y recomendación arquitectónica final  
**Estado:** RECOMENDACIÓN FINAL

---

## Información de consolidación

**Tipo de consolidación:** Revisión y consolidación humana.

**Agente previsto en el flujo metodológico:** `solution-leader`

**Skill previsto:** `decision-consolidation`

**Ruta prevista del skill:**

`.claude/skills/decision-consolidation/SKILL.md`

---

# 1. Objetivo

El objetivo de este documento es consolidar los resultados obtenidos durante
el análisis arquitectónico del proyecto:

**Sistema de Gestión de Citas y Atención Virtual**

y establecer una recomendación arquitectónica final para continuar
posteriormente con el diseño técnico y la implementación.

La recomendación integra:

- análisis de requisitos;
- decisiones de alcance;
- alternativas arquitectónicas;
- estrategia de persistencia;
- seguridad;
- infraestructura y DevOps;
- revisión arquitectónica final.

Esta recomendación se mantiene a nivel arquitectónico.

No pretende seleccionar todavía todos los productos, proveedores o
tecnologías concretas de implementación.

---

# 2. Fuentes consideradas

La consolidación toma como referencia las versiones actuales de:

## Contexto

- `proyecto/contexto/descripcion.md`
- `proyecto/contexto/alcance.md`
- `proyecto/contexto/reglas_negocio.md`
- `proyecto/contexto/restricciones.md`

## Requisitos

- `proyecto/requisitos/RF.md`
- `proyecto/requisitos/RNF.md`
- `proyecto/requisitos/criterios_aceptacion.md`

## Configuración

- `proyecto/configuracion/perfil_carga.yaml`

## Resultados del proceso arquitectónico

- `proyecto/resultados/01_requirements_analysis.md`
- `proyecto/resultados/02_decision_scope.md`
- `proyecto/resultados/03_architecture_options.md`
- `proyecto/resultados/04_database_analysis.md`
- `proyecto/resultados/05_security_analysis.md`
- `proyecto/resultados/06_devops_analysis.md`
- `proyecto/resultados/07_architecture_review.md`

---

# 3. Jerarquía documental

Se mantiene la decisión D-23.

Cuando exista alguna diferencia entre documentos, las fuentes de referencia
serán:

1. `RF.md` para requisitos funcionales;
2. `RNF.md` para requisitos no funcionales;
3. `reglas_negocio.md` para reglas de negocio;
4. `criterios_aceptacion.md` para criterios verificables;
5. `02_decision_scope.md` para decisiones humanas utilizadas para resolver
   ambigüedades.

Los documentos de análisis arquitectónico son derivados de estas fuentes.

---

# 4. Contexto de la solución

El sistema tiene como finalidad apoyar la gestión de citas médicas y atención
virtual.

Entre sus principales responsabilidades se encuentran:

- gestión de pacientes;
- gestión de médicos;
- gestión de especialidades;
- gestión de horarios;
- consulta de disponibilidad;
- reserva de citas;
- confirmación de citas;
- reprogramación;
- cancelación;
- seguimiento de estados;
- agenda médica;
- historial de citas;
- gestión de usuarios y roles;
- auditoría;
- integración con un servicio externo de atención virtual.

El alcance no contempla la construcción de una plataforma propia de
videollamadas.

Tampoco debe ampliarse automáticamente hacia funcionalidades que se encuentren
fuera del alcance aprobado.

---

# 5. Perfil operativo considerado

La arquitectura debe considerar como referencia:

## Carga inicial

- hasta 100 usuarios concurrentes;
- aproximadamente 500 transacciones por hora como carga normal;
- hasta 1.000 transacciones por hora como pico inmediato.

## Escenario futuro

- aproximadamente 5.000 transacciones por hora.

El escenario futuro representa un crecimiento aproximado de 10 veces la carga
normal inicial.

No implica que toda la infraestructura necesaria para dicho escenario deba
implementarse desde la primera versión.

---

# 6. Recomendación arquitectónica principal

Se recomienda utilizar una:

# Arquitectura de monolito modular con API y cliente web desacoplado

La aplicación deberá organizarse internamente por módulos funcionales,
manteniendo una única solución desplegable inicialmente.

La arquitectura debe separar claramente las responsabilidades relacionadas
con:

- pacientes;
- médicos;
- especialidades;
- horarios;
- citas;
- usuarios;
- roles y permisos;
- auditoría;
- integración con atención virtual.

---

# 7. Razones para seleccionar un monolito modular

La arquitectura modular resulta adecuada debido a que:

- el alcance funcional todavía es controlado;
- la carga inicial no justifica una arquitectura distribuida compleja;
- existen operaciones que requieren consistencia transaccional;
- facilita el mantenimiento;
- facilita la comprensión del dominio;
- permite separar responsabilidades;
- reduce costos operativos;
- reduce complejidad de despliegue;
- puede evolucionar progresivamente.

La utilización de un monolito modular no significa construir una aplicación
sin estructura.

Los módulos deberán mantener límites y responsabilidades claramente
definidos.

---

# 8. Microservicios

No se recomienda utilizar microservicios para la primera versión.

Actualmente no existe evidencia que justifique asumir desde el inicio:

- comunicación distribuida;
- descubrimiento de servicios;
- múltiples despliegues independientes;
- consistencia distribuida;
- orquestación compleja;
- múltiples bases de datos;
- incremento significativo de observabilidad operacional.

Los microservicios podrán ser reconsiderados posteriormente si aparecen
necesidades reales como:

- crecimiento significativo del sistema;
- módulos con ciclos de despliegue completamente independientes;
- múltiples equipos de desarrollo;
- requerimientos de escalamiento independientes;
- cargas considerablemente superiores;
- límites funcionales suficientemente maduros.

---

# 9. Organización lógica recomendada

A nivel conceptual la solución puede representarse como:

```text
Cliente Web
     |
     v
API / Aplicación
     |
     +----------------------------------+
     |                                  |
     |  Módulos funcionales             |
     |                                  |
     |  - Pacientes                     |
     |  - Médicos                       |
     |  - Especialidades                |
     |  - Horarios                      |
     |  - Citas                         |
     |  - Usuarios y Roles              |
     |  - Auditoría                     |
     |  - Atención Virtual              |
     |                                  |
     +----------------------------------+
                 |
                 v
      Persistencia Transaccional
                 |
                 v
      Base de datos relacional