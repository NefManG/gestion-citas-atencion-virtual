# Sistema de Gestión de Citas y Atención Virtual

Proyecto académico desarrollado para el análisis, diseño y validación de la
arquitectura de un **Sistema de Gestión de Citas y Atención Virtual**.

El proyecto utiliza un enfoque basado en requisitos, decisiones
arquitectónicas, análisis especializados y revisión progresiva mediante
agentes y skills.

> **Importante:** el proyecto se desarrolla con fines académicos. Las
> decisiones identificadas como académicas deberán validarse con las políticas
> institucionales correspondientes antes de una implementación real.

---

## 1. Objetivo del proyecto

Diseñar y validar una arquitectura de software que permita gestionar de forma
segura y organizada procesos relacionados con:

- pacientes;
- médicos;
- especialidades;
- horarios;
- disponibilidad;
- reserva de citas;
- confirmación de citas;
- reprogramación;
- cancelación;
- estados de las citas;
- agenda médica;
- historial;
- usuarios y roles;
- auditoría;
- atención virtual mediante un servicio externo.

El análisis busca obtener una solución:

- modular;
- mantenible;
- segura;
- transaccional;
- observable;
- recuperable;
- escalable progresivamente.

---

## 2. Alcance académico

El proyecto analiza el sistema en el contexto académico del
**Hospital Boliviano Español**.

La documentación no debe interpretarse como una política oficial de la
institución.

Cuando no existe información institucional suficiente, las decisiones se
mantienen como:

- pendientes;
- criterios académicos;
- decisiones sujetas a validación institucional.

No se deben inventar políticas del hospital para completar requisitos.

---

## 3. Estado actual

El proyecto se encuentra en fase de:

**análisis y validación arquitectónica.**

### Estado de los casos de estudio

- **Caso de Estudio 1:** completado.
- **Caso de Estudio 2:** consolidado a nivel arquitectónico.

Todavía no se considera una solución productiva implementada.

Los requisitos relacionados con rendimiento, disponibilidad, recuperación,
seguridad y escalabilidad deberán demostrarse posteriormente mediante pruebas.

---

## 4. Recomendación arquitectónica actual

Después del análisis realizado en el Caso de Estudio 2, la recomendación
arquitectónica consolidada es:

> **Monolito modular con cliente web desacoplado, API de aplicación,
> persistencia relacional transaccional, integración de atención virtual
> mediante adaptador y estrategia de infraestructura evolutiva.**

La arquitectura busca evitar complejidad prematura y permitir una evolución
progresiva según las necesidades reales del sistema.

---

## 5. Principios arquitectónicos

La propuesta se basa en los siguientes principios:

1. Modularidad.
2. Separación de responsabilidades.
3. Consistencia de operaciones críticas.
4. Reserva de citas atómica.
5. Seguridad por diseño.
6. Mínimo privilegio.
7. Auditoría funcional.
8. Observabilidad.
9. Independencia del proveedor de atención virtual.
10. Escalabilidad progresiva.
11. Despliegues reproducibles.
12. Recuperación verificable.
13. Evitar sobredimensionamiento sin evidencia.
14. Evitar complejidad distribuida innecesaria.
15. No asumir políticas institucionales no proporcionadas.

---

## 6. Perfil de carga considerado

El análisis arquitectónico utiliza como referencia:

### Carga inicial

- hasta 100 usuarios concurrentes;
- 500 transacciones por hora como carga normal;
- hasta 1.000 transacciones por hora como pico inmediato.

### Escenario futuro

- hasta 5.000 transacciones por hora como escenario de crecimiento.

El escenario futuro representa una capacidad de evolución.

No significa que toda la infraestructura deba desplegarse desde la primera
versión.

---

## 7. Requisitos no funcionales relevantes

Entre los principales objetivos no funcionales se encuentran:

| Aspecto | Objetivo |
|---|---|
| Rendimiento | Respuesta del backend de hasta 3 segundos bajo las condiciones de prueba definidas |
| Concurrencia | Hasta 100 usuarios concurrentes |
| Carga normal | 500 transacciones/hora |
| Pico inmediato | 1.000 transacciones/hora |
| Escenario futuro | 5.000 transacciones/hora |
| Disponibilidad | >= 99 % mensual |
| RPO | <= 60 minutos |
| RTO | <= 120 minutos |
| Sesión | 15 minutos de inactividad, configurable |
| Accesibilidad | WCAG 2.1 nivel AA |
| Auditoría | Fecha, hora, usuario, acción, valor anterior y valor actualizado |

Estos valores representan objetivos del diseño.

Su cumplimiento deberá demostrarse mediante implementación y pruebas.

---

## 8. Estructura principal del proyecto

```text
gestion-citas-atencion-virtual/
│
├── README.md
├── CLAUDE.md
├── AGENTS.md
├── PROMPTS_Y_EVIDENCIAS.md
│
├── .claude/
│   ├── agents/
│   └── skills/
│
└── proyecto/
    ├── contexto/
    ├── requisitos/
    ├── configuracion/
    ├── resultados/
    ├── decisiones/
    └── base_datos/
```

---

## 9. Organización de `proyecto/`

### `proyecto/contexto/`

Contiene información general del dominio y las restricciones del proyecto.

Incluye documentos relacionados con:

- descripción;
- alcance;
- reglas de negocio;
- restricciones.

---

### `proyecto/requisitos/`

Contiene las fuentes normativas principales relacionadas con los requisitos.

Entre ellas:

- `RF.md`
- `RNF.md`
- `criterios_aceptacion.md`

---

### `proyecto/configuracion/`

Contiene parámetros utilizados durante el análisis.

Por ejemplo:

- `perfil_carga.yaml`

---

### `proyecto/resultados/`

Contiene los resultados de las diferentes etapas del Caso de Estudio 2.

```text
proyecto/resultados/
├── 01_requirements_analysis.md
├── 02_decision_scope.md
├── 03_architecture_options.md
├── 04_database_analysis.md
├── 05_security_analysis.md
├── 06_devops_analysis.md
├── 07_architecture_review.md
└── 08_recomendacion_final.md
```

---

### `proyecto/decisiones/`

Destinado a documentación adicional relacionada con decisiones del proyecto,
si fuera necesaria.

---

### `proyecto/base_datos/`

Destinado a documentación y artefactos relacionados con persistencia y base
de datos cuando se avance hacia el diseño técnico.

---

## 10. Flujo de análisis del Caso de Estudio 2

El proceso seguido es:

```text
Requisitos
    ↓
Análisis de requisitos
    ↓
Resolución de decisiones y alcance
    ↓
Comparación de alternativas arquitectónicas
    ↓
Análisis de persistencia
    ↓
Análisis de seguridad
    ↓
Análisis DevOps e infraestructura
    ↓
Revisión arquitectónica integral
    ↓
Consolidación de la recomendación final
```

Cada etapa genera un documento independiente para mantener trazabilidad.

---

## 11. Agentes y skills

El proyecto está configurado para utilizar agentes especializados durante las
diferentes etapas del análisis.

| Etapa | Agente | Skill | Resultado |
|---|---|---|---|
| Requisitos | `requirements-analyst` | `requirements-analysis` | `01_requirements_analysis.md` |
| Decisiones | `solution-leader` | `decision-consolidation` | `02_decision_scope.md` |
| Arquitectura | `architect` | `architecture-design` | `03_architecture_options.md` |
| Persistencia | `database-specialist` | `database-evaluation` | `04_database_analysis.md` |
| Seguridad | `security-reviewer` | `security-review` | `05_security_analysis.md` |
| DevOps | `devops-architect` | `infrastructure-evaluation` | `06_devops_analysis.md` |
| Revisión | `architecture-reviewer` | `architecture-validation` | `07_architecture_review.md` |
| Consolidación | `solution-leader` | `decision-consolidation` | `08_recomendacion_final.md` |

La existencia de esta tabla documenta el **flujo previsto**.

La evidencia sobre qué agentes y skills fueron ejecutados realmente se
mantiene separadamente en `PROMPTS_Y_EVIDENCIAS.md`.

---

## 12. Configuración de Claude Code

Las instrucciones persistentes utilizadas por Claude Code se encuentran en:

```text
CLAUDE.md
```

Los agentes especializados se encuentran en:

```text
.claude/agents/
```

Los skills utilizados por los agentes se encuentran en:

```text
.claude/skills/
```

El `README.md` no contiene los prompts operativos utilizados para solicitar
los análisis.

---

## 13. Evidencia del uso de inteligencia artificial

Los prompts, ejecuciones verificables de agentes, skills asociados,
correcciones y revisiones humanas se documentan en:

```text
PROMPTS_Y_EVIDENCIAS.md
```

Este archivo permite diferenciar entre:

- prompt enviado;
- agente solicitado;
- agente ejecutado de forma comprobable;
- skill configurado;
- evidencia disponible del skill;
- resultado generado;
- corrección posterior;
- revisión humana.

No se considera correcto afirmar la ejecución de un agente o skill cuando no
exista evidencia suficiente.

---

## 14. Fuente de verdad documental

Para evitar contradicciones entre documentos se utiliza la siguiente
jerarquía:

### Requisitos funcionales

```text
proyecto/requisitos/RF.md
```

### Requisitos no funcionales

```text
proyecto/requisitos/RNF.md
```

### Reglas de negocio

```text
proyecto/contexto/reglas_negocio.md
```

### Criterios de aceptación

```text
proyecto/requisitos/criterios_aceptacion.md
```

### Resolución de ambigüedades y decisiones

```text
proyecto/resultados/02_decision_scope.md
```

Los demás documentos de `resultados/` son derivados de estas fuentes.

---

## 15. Persistencia

La estrategia actualmente recomendada es:

**persistencia relacional transaccional con capacidad de evolución
progresiva.**

Debe permitir:

- transacciones;
- integridad referencial;
- control de concurrencia;
- operaciones atómicas;
- auditoría;
- respaldos;
- recuperación;
- índices;
- evolución futura.

El motor concreto de base de datos deberá seleccionarse durante una etapa
posterior de evaluación tecnológica.

---

## 16. Concurrencia

La confirmación de una cita debe tratarse como una operación crítica.

La arquitectura establece:

```text
Verificación final de disponibilidad
                +
Confirmación de la cita
                =
Operación atómica
```

Cuando dos usuarios intenten reservar simultáneamente el mismo horario:

- solamente una solicitud podrá confirmarse;
- las demás deberán recibir una respuesta indicando que el horario ya no se
  encuentra disponible.

El mecanismo técnico concreto se seleccionará durante el diseño de
persistencia.

---

## 17. Atención virtual

La plataforma propia de videollamadas se encuentra fuera del alcance.

La solución utilizará un servicio externo mediante una capa de abstracción:

```text
Sistema
   ↓
Interfaz / Puerto
   ↓
Adaptador
   ↓
Proveedor externo
```

El proveedor concreto permanece pendiente de selección.

Una falla del proveedor externo no deberá provocar automáticamente:

- eliminación de la cita;
- cancelación;
- registro de no asistencia;
- cambio de modalidad;
- modificación incorrecta del estado de la cita.

---

## 18. Seguridad

La arquitectura considera como aspectos esenciales:

- autenticación;
- autorización;
- roles;
- mínimo privilegio;
- gestión de sesiones;
- protección de credenciales;
- protección de datos personales;
- transmisión segura;
- gestión de secretos;
- validación de entradas;
- auditoría;
- protección de logs;
- protección de respaldos;
- seguridad de integraciones externas.

La existencia de una estrategia de seguridad no significa que dichos
controles ya estén implementados.

---

## 19. DevOps e infraestructura

La estrategia recomendada es:

**evolutiva y progresiva.**

Se busca iniciar con una infraestructura suficientemente sencilla y
evolucionar cuando las mediciones lo justifiquen.

La evolución puede incorporar posteriormente:

- automatización de despliegues;
- contenerización;
- monitoreo;
- centralización de logs;
- múltiples instancias;
- balanceo de carga;
- réplicas;
- escalamiento horizontal;
- mecanismos avanzados de recuperación.

---

## 20. Kubernetes

Kubernetes no se considera un requisito para la primera versión.

Su adopción deberá justificarse mediante necesidades reales como:

- elevado número de servicios;
- múltiples instancias;
- múltiples equipos;
- despliegues muy frecuentes;
- autoescalado;
- necesidades avanzadas de recuperación automática.

No se descarta como tecnología futura.

---

## 21. Decisiones funcionales pendientes

Algunas decisiones permanecen pendientes de validación institucional.

Entre ellas:

- política de cancelación;
- política de reprogramación;
- tolerancia para no asistencia;
- anticipación máxima para reservar;
- comportamiento ante desactivación de médicos con citas futuras;
- habilitación de modalidad virtual;
- relación entre modalidad y horarios;
- permisos detallados del personal de admisión;
- reglas específicas de validación de determinados datos.

Estas decisiones no deben completarse mediante supuestos no respaldados.

---

## 22. Decisiones tecnológicas pendientes

Todavía deberán evaluarse posteriormente:

- lenguaje y framework backend;
- tecnología frontend;
- motor de base de datos;
- proveedor de infraestructura;
- plataforma CI/CD;
- tecnología de contenedores;
- sistema de monitoreo;
- sistema de logs;
- gestor de secretos;
- proveedor de atención virtual;
- mecanismo técnico de concurrencia;
- balanceo;
- réplicas;
- estrategia concreta de alta disponibilidad.

---

## 23. Validaciones futuras

Durante la implementación deberán realizarse pruebas para verificar:

### Rendimiento

- tiempo de respuesta;
- carga;
- concurrencia.

### Seguridad

- autenticación;
- autorización;
- sesiones;
- manejo de entradas;
- protección de información.

### Recuperación

- restauración;
- RPO;
- RTO.

### Disponibilidad

- medición mensual;
- fallas;
- recuperación.

### Accesibilidad

- WCAG 2.1 AA.

### Usabilidad

- cumplimiento del objetivo mínimo establecido.

---

## 24. Estado de la arquitectura

La revisión arquitectónica determinó que el proyecto puede continuar hacia
las etapas de diseño tecnológico e implementación.

No obstante:

- los requisitos técnicos todavía deben probarse;
- las políticas institucionales pendientes deben validarse;
- las tecnologías concretas deberán seleccionarse posteriormente.

Por tanto, el estado actual es:

```text
CASO DE ESTUDIO 2
CONSOLIDADO A NIVEL ARQUITECTÓNICO
```

---

## 25. Documentación relacionada

Los principales archivos de documentación son:

```text
README.md
CLAUDE.md
AGENTS.md
PROMPTS_Y_EVIDENCIAS.md
```

Su propósito es diferente:

| Archivo | Propósito |
|---|---|
| `README.md` | Presentar y explicar el proyecto |
| `CLAUDE.md` | Establecer instrucciones persistentes para Claude Code |
| `AGENTS.md` | Documentar los agentes y el flujo especializado |
| `PROMPTS_Y_EVIDENCIAS.md` | Registrar prompts, ejecuciones y evidencias |

---

## 26. Nota académica

Las recomendaciones desarrolladas en este repositorio corresponden a un
ejercicio académico de análisis y arquitectura de software.

No representan decisiones oficiales, políticas médicas, administrativas,
tecnológicas o de seguridad del Hospital Boliviano Español.

Cualquier implementación real deberá ser revisada y aprobada por las
instancias institucionales correspondientes.