# PROMPTS Y EVIDENCIAS DEL PROYECTO

## Sistema de Gestión de Citas y Atención Virtual

**Contexto académico:** Hospital Boliviano Español  
**Finalidad:** conservar en un único archivo la trazabilidad de los prompts utilizados, agentes y skills asociados, resultados, correcciones y evidencia disponible desde el Caso de Estudio 1 hasta el cierre del Caso de Estudio 2.

---

# 1. Criterio de evidencia

En este documento se diferencia entre:

- **Ejecución comprobada:** existe evidencia explícita de que Claude Code ejecutó el agente.
- **Agente solicitado:** el prompt pidió utilizarlo, pero la evidencia disponible no demuestra por sí sola que la delegación se ejecutó.
- **Skill solicitado o asociado:** el prompt o la configuración lo relaciona con la etapa, pero no se afirma una ejecución independiente si no existe evidencia.
- **Revisión humana:** el contenido fue revisado, corregido o consolidado.

No se registran como ejecutados agentes o skills cuya ejecución no pueda demostrarse.

---

# 2. CASO DE ESTUDIO 1

## 2.1 Tema

**Validación de Requerimientos Funcionales y No Funcionales mediante Agentes**

Sistema seleccionado:

**Sistema de Gestión de Citas y Atención Virtual**

Contexto académico:

**Hospital Boliviano Español**

El Caso 1 tuvo como objetivo definir y validar los requerimientos funcionales, requerimientos no funcionales y reglas de negocio antes de continuar hacia el diseño arquitectónico.

## 2.2 Agente utilizado

**Agente:** `requirements-analyst`

El documento final del Caso 1 registra la ejecución del agente especializado `requirements-analyst` mediante Claude Code.

**Resultado asociado:**

`proyecto/resultados/01_requirements_analysis.md`

## 2.3 Prompt inicial

```text
requirements-analyst

Analiza proyecto/ aplicando requirements-analysis.

No selecciones todavía una tecnología.

Devuelve el contenido para:

proyecto/resultados/01_requirements_analysis.md
```

**Agente solicitado:** `requirements-analyst`  
**Skill solicitado/asociado:** `requirements-analysis`

## 2.4 Prompt de ampliación

```text
requirements-analyst

Amplía el análisis anterior.

Detalla individualmente cada observación detectada indicando:

- identificador de la observación;
- categoría;
- RF, RNF, regla de negocio, restricción o decisión pendiente relacionada;
- descripción concreta del problema;
- explicación de por qué representa una ambigüedad, inconsistencia, omisión,
  duplicidad, falta de verificabilidad o riesgo.

No decidas si la observación es válida o no.
Esa decisión será realizada posteriormente por el estudiante.

No modifiques los requisitos.
No selecciones tecnología.
No avances a arquitectura, persistencia ni implementación.

Devuelve únicamente el contenido completo correspondiente a:

proyecto/resultados/01_requirements_analysis.md
```

## 2.5 Resultado

El análisis produjo **50 observaciones** clasificadas en:

- ambigüedad;
- inconsistencia;
- omisión;
- duplicidad;
- falta de verificabilidad;
- riesgo.

La decisión de aceptar, ajustar o rechazar cada observación quedó bajo responsabilidad del estudiante.

**Estado de evidencia:** ejecución de `requirements-analyst` comprobada.

---

# 3. CASO DE ESTUDIO 2

## 3.1 Flujo trabajado

```text
01. Análisis de requisitos
02. Decisiones y alcance
03. Alternativas arquitectónicas
04. Persistencia
05. Seguridad
06. DevOps e infraestructura
07. Revisión arquitectónica
08. Recomendación final
```

---

# 4. ETAPA 01 — ANÁLISIS DE REQUISITOS

Se utilizó como base el resultado del Caso 1:

`proyecto/resultados/01_requirements_analysis.md`

Prompt principal:

```text
requirements-analyst

Analiza proyecto/ aplicando requirements-analysis.

No selecciones todavía una tecnología.

Devuelve el contenido para:

proyecto/resultados/01_requirements_analysis.md
```

**Agente:** `requirements-analyst`  
**Skill:** `requirements-analysis`

---

# 5. ETAPA 02 — DECISIONES Y ALCANCE

## Prompt utilizado

```text
Analiza el resultado de requisitos y define:

- decisiones que deben tomarse;
- especialistas necesarios;
- alternativas que deben compararse;
- restricciones críticas;
- preguntas pendientes.

No programes y no selecciones todavía un stack final.

Resultado:

proyecto/resultados/02_decision_scope.md
```

**Agente previsto por el flujo:** `solution-leader`  
**Skill asociado:** `decision-consolidation`

**Resultado:**

`proyecto/resultados/02_decision_scope.md`

La evidencia recuperada no permite afirmar de forma independiente que `solution-leader` haya sido ejecutado en esta etapa.

---

# 6. ETAPA 03 — ALTERNATIVAS ARQUITECTÓNICAS

**Agente solicitado:** `architect`  
**Skill solicitado:** `architecture-design`

```text
architect

Analiza proyecto/ aplicando architecture-design.

Propón de 4 a 6 alternativas arquitectónicas para el
Sistema de Gestión de Citas y Atención Virtual.

Compara cada alternativa considerando:
- complejidad;
- escalabilidad;
- consistencia;
- costo operativo;
- mantenibilidad.

Toma como base el contexto, los requerimientos funcionales,
los requerimientos no funcionales, las reglas de negocio
y las decisiones existentes en proyecto/.

No uses microservicios por defecto.

No elijas todavía una arquitectura definitiva.
No elijas aún una base de datos definitiva.

No escribas código.
No instales dependencias.
No avances a implementación.

El contenido será revisado manualmente antes de guardarse como:

proyecto/resultados/03_architecture_options.md
```

Confirmación posterior:

```text
Sí. Guarda el contenido aprobado en:

proyecto/resultados/03_architecture_options.md

No modifiques ningún otro archivo.
No cambies RF, RNF ni reglas de negocio.
No avances a implementación.
```

**Resultado:** `proyecto/resultados/03_architecture_options.md`

---

# 7. ETAPA 04 — PERSISTENCIA

**Agente previsto:** `database-specialist`  
**Skill previsto:** `database-evaluation`

El prompt inicial completo de la primera generación no quedó recuperado en la evidencia disponible. Sí se conserva el prompt de corrección.

```text
Ejecuta explícitamente el subagente database-specialist utilizando el skill database-evaluation.

Aplica la ETAPA 04 — PERSISTENCIA de PROMPTS_Y_EVIDENCIA.md.
Revisa proyecto/resultados/04_database_analysis.md.

No modifiques ningún archivo.
Antes del análisis, indica el agente y skill utilizados.
**Revisión humana** sí.

---

# 8. ETAPA 05 — SEGURIDAD

**Agente previsto:** `security-reviewer`  
**Skill previsto:** `security-review`

```text
Evalúa la seguridad del Sistema de Gestión de Citas y Atención Virtual.

Trabaja con la documentación actual existente dentro de proyecto/,
especialmente:

- proyecto/contexto/descripcion.md
- proyecto/contexto/alcance.md
- proyecto/contexto/reglas_negocio.md
- proyecto/contexto/restricciones.md
- proyecto/requisitos/RF.md
- proyecto/requisitos/RNF.md
- proyecto/requisitos/criterios_aceptacion.md
- proyecto/resultados/01_requirements_analysis.md
- proyecto/resultados/02_decision_scope.md
- proyecto/resultados/03_architecture_options.md
- proyecto/resultados/04_database_analysis.md

Utiliza las versiones actuales de RF, RNF, criterios de aceptación,
reglas de negocio y decisiones.

No programes.
No generes código.
No instales dependencias.
No modifiques archivos.
No selecciones todavía una tecnología, proveedor o producto específico.

Analiza como mínimo:

- autenticación;
- autorización y roles;
- diferencia entre Médico y Usuario;
- sesiones;
- D-13;
- credenciales;
- datos personales;
- datos en tránsito y almacenados;
- acceso de pacientes y médicos;
- permisos de admisión y administración;
- auditoría D-12;
- integración virtual;
- fallos del servicio externo;
- validación de entradas;
- inyección;
- XSS;
- CSRF;
- errores;
- eventos de seguridad;
- intentos repetidos de autenticación;
- mínimo privilegio;
- separación entre auditoría y logs;
- disponibilidad;
- respaldo;
- recuperación;
- amenazas principales.

No inventes políticas institucionales del Hospital Boliviano Español.

No marques un requisito como “cumplido” si todavía no existe implementación
ni pruebas.

Clasifica hallazgos como:

- BLOCKER;
- HIGH;
- MEDIUM;
- LOW.

Resultado:

proyecto/resultados/05_security_analysis.md
```

**Resultado:** `proyecto/resultados/05_security_analysis.md`

**Revisión humana:** sí.

---

# 9. ETAPA 06 — DEVOPS E INFRAESTRUCTURA

## Prompt especializado

```text

Quiero que esta tarea se ejecute mediante un SUBAGENTE especializado.

Usa explícitamente el agente:

devops-architect

No realices directamente el análisis desde el agente principal.

Antes de comenzar el análisis:

1. Delega la tarea al subagente devops-architect.

2. El subagente debe revisar los skills disponibles.

3. Debe leer y aplicar únicamente los skills reales que sean pertinentes.

NO inventes skills que no existan.

Antes del resultado, informa:

- Agente utilizado:
- Archivo del agente:
- Skill o skills utilizados:
- Ruta de cada SKILL.md utilizado:

Si no puedes ejecutar realmente el subagente o no puedes leer los skills,
DETENTE e indícalo claramente.

No simules haber utilizado agentes o skills.

Evalúa la estrategia DevOps e infraestructura del proyecto
“Sistema de Gestión de Citas y Atención Virtual”.

Analiza:

- estrategia de despliegue;
- ambientes;
- configuración;
- CI/CD;
- secretos;
- disponibilidad;
- tolerancia a fallos;
- puntos únicos de falla;
- RPO <= 60 minutos;
- RTO <= 120 minutos;
- respaldos;
- restauración;
- monitoreo;
- logs;
- alertas;
- TLS;
- escalabilidad;
- 100 usuarios concurrentes;
- 500 transacciones/hora normales;
- 1.000 transacciones/hora de pico;
- crecimiento a 5.000 transacciones/hora;
- escalamiento vertical y horizontal;
- réplicas;
- balanceo;
- contenerización;
- integración con atención virtual;
- complejidad;
- costos relativos;
- mantenibilidad;
- recuperación ante desastres.

No asumas que contenedores, Kubernetes, microservicios o cloud
son obligatorios.

No selecciones todavía un proveedor definitivo.

No marques RNF-08, RNF-09 o RNF-10 como cumplidos sin pruebas.

No programes.
No generes código.
No instales dependencias.
No modifiques archivos.

Resultado:

proyecto/resultados/06_devops_analysis.md
```

Prompt simplificado posterior:

```text
Usa el agente devops-architect para evaluar la estrategia DevOps e infraestructura del proyecto.

El agente debe utilizar el skill infrastructure-evaluation que tiene precargado.

No realices el análisis desde el agente principal.

Devuelve el resultado en Markdown para:

proyecto/resultados/06_devops_analysis.md
```

## Evidencia real

Claude Code mostró explícitamente:

```text
devops-architect(Evaluación DevOps e infraestructura del proyecto)
```

y posteriormente indicó que el agente había terminado.

**Agente `devops-architect`: EJECUCIÓN COMPROBADA.**

**Skill asociado:** `infrastructure-evaluation`

La carga independiente del skill no quedó demostrada explícitamente en la salida disponible, por lo que se registra como **configurado/asociado**.

La salida original quedó parcialmente truncada y contenía referencias incorrectas a decisiones D-*.

El documento final `06_devops_analysis.md` 

---

# 10. ETAPA 07 — REVISIÓN ARQUITECTÓNICA

**Agente solicitado:** `architecture-reviewer`  
**Skill solicitado:** `architecture-validation`

```text
architecture-reviewer

Aplica architecture-validation.

Realiza una revisión adversarial de los resultados existentes
del proyecto contra los requerimientos funcionales y no funcionales.

Toma como fuente:
- proyecto/contexto/
- proyecto/requisitos/
- proyecto/configuracion/
- proyecto/resultados/

Valida la trazabilidad contra:
- RF-01 a RF-22;
- RNF-01 a RNF-19;
- reglas de negocio;
- restricciones;
- decisiones pendientes.

Identifica:
- contradicciones;
- requisitos sin cobertura;
- cobertura parcial;
- riesgos arquitectónicos;
- supuestos no justificados;
- problemas de consistencia;
- problemas de escalabilidad;
- problemas de disponibilidad;
- problemas de seguridad;
- problemas de mantenibilidad;
- complejidad innecesaria.

Clasifica cada hallazgo como:
- BLOQUEANTE;
- ALTO;
- MEDIO;
- BAJO.

No selecciones todavía una arquitectura definitiva.
No selecciones tecnologías.
No selecciones base de datos.
No escribas código.
No instales dependencias.
No avances a implementación.

NO modifiques archivos.

El resultado será revisado manualmente antes de guardarse como:

proyecto/resultados/07_architecture_review.md
```

Debido al agotamiento de cuota de Claude Code/OpenRouter, la versión final del archivo fue realizada mediante **revisión humana**.

**Resultado:** `proyecto/resultados/07_architecture_review.md`

No se registra la versión final como ejecución comprobada de `architecture-reviewer`.

---

# 11. ETAPA 08 — CONSOLIDACIÓN FINAL

**Agente previsto:** `solution-leader`  
**Skill previsto:** `decision-consolidation`

Prompt histórico:

```text
Consolida todos los resultados.

Entrega:

- matriz de decisión;
- arquitectura recomendada;
- backend;
- persistencia;
- integraciones;
- seguridad;
- infraestructura;
- capacidad;
- riesgos;
- preguntas pendientes;
- trazabilidad RF/RNF;
- ADR propuesto;
- plan de pruebas antes de implementar.

Resultado:

proyecto/resultados/08_recomendacion_final.md
```

Debido a la falta de cuota de Claude Code, la versión final fue consolidada mediante **revisión humana**.

**Resultado:** `proyecto/resultados/08_recomendacion_final.md`

Recomendación consolidada:

**Monolito modular con cliente web desacoplado, API de aplicación, persistencia relacional transaccional, integración de atención virtual mediante adaptador y estrategia de infraestructura evolutiva.**

---

# 12. CORRECCIONES IMPORTANTES REALIZADAS

Durante el Caso 2 se realizaron revisiones y correcciones humanas.

## Persistencia

Se evitó seleccionar prematuramente un motor concreto.

## Auditoría

Se unificaron seis campos mínimos:

- fecha;
- hora;
- usuario;
- acción;
- valor anterior;
- valor actualizado.

## Sesiones

Se definieron **15 minutos de inactividad**, configurable, como criterio académico.

## Retención

Se establecieron **5 años** como criterio académico mínimo para citas históricas y auditoría.

## Concurrencia

Se estableció:

**verificación final de disponibilidad + confirmación = operación atómica.**

## Atención virtual

Se definió integración mediante adaptador y se evitó cambiar automáticamente la cita ante una falla del proveedor externo.

## DevOps

Se eliminaron afirmaciones no demostradas sobre disponibilidad, RPO, RTO, cantidad de instancias y necesidad de Kubernetes.

---

# 13. RESULTADOS DEL CASO 2

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

# 14. MATRIZ DE EVIDENCIA

| Etapa | Agente | Skill | Evidencia del agente | Resultado |
|---|---|---|---|---|
| Requisitos | `requirements-analyst` | `requirements-analysis` | **Comprobada** | `01_requirements_analysis.md` |
| Decisiones | `solution-leader` | `decision-consolidation` | No comprobada explícitamente | `02_decision_scope.md` |
| Arquitectura | `architect` | `architecture-design` | Solicitado; no comprobado independientemente | `03_architecture_options.md` |
| Persistencia | `database-specialist` | `database-evaluation` | No comprobada explícitamente | `04_database_analysis.md` |
| Seguridad | `security-reviewer` | `security-review` | No comprobada explícitamente | `05_security_analysis.md` |
| DevOps | `devops-architect` | `infrastructure-evaluation` | **Comprobada** | `06_devops_analysis.md` |
| Revisión | `architecture-reviewer` | `architecture-validation` | Solicitado; ejecución final no comprobada | `07_architecture_review.md` |
| Consolidación | `solution-leader` | `decision-consolidation` | No comprobada en la versión final | `08_recomendacion_final.md` |

---

# 15. ACLARACIÓN SOBRE RUTAS DE SKILLS

En prompts históricos aparecieron referencias a:

```text
.agents/skills/
```

Posteriormente, la configuración utilizada con Claude Code se organizó bajo:

```text
.claude/skills/
```

Las rutas antiguas se conservan únicamente como evidencia histórica del prompt utilizado.

---

# 16. USO DE CHATGPT

ChatGPT fue utilizado como apoyo para:

- revisar coherencia;
- corregir referencias;
- evitar decisiones tecnológicas prematuras;
- revisar persistencia;
- revisar seguridad;
- corregir DevOps;
- realizar la revisión arquitectónica cuando Claude Code quedó sin cuota;
- consolidar la recomendación final;
- revisar RNF-07 y RNF-18;
- organizar la documentación.

Estas intervenciones se consideran **revisión humana**, no ejecución de subagentes de Claude Code.

---

# 17. PRINCIPIO DE REVISIÓN HUMANA

El flujo aplicado fue:

```text
Prompt
  ↓
Análisis de IA
  ↓
Revisión del estudiante
  ↓
Corrección / aceptación / rechazo
  ↓
Resultado final
```

Las respuestas de los agentes no fueron aceptadas automáticamente.

---

# 18. ESTADO FINAL

**Caso de Estudio 1:** completado.

**Caso de Estudio 2:** consolidado a nivel arquitectónico.

Arquitectura recomendada:

**Monolito modular con cliente web desacoplado, API de aplicación, persistencia relacional transaccional, integración externa mediante adaptador y estrategia de infraestructura progresiva.**

Las decisiones institucionales y tecnológicas pendientes deberán resolverse posteriormente.

---

# 19. CONCLUSIÓN

El trabajo realizado permitió utilizar agentes de inteligencia artificial como apoyo al análisis de requerimientos y arquitectura, manteniendo la revisión humana como parte central del proceso.

La evidencia disponible permite afirmar explícitamente la ejecución de `requirements-analyst` en el Caso 1 y de `devops-architect` durante el Caso 2.

En las demás etapas se conserva el prompt utilizado, el agente previsto o solicitado y el resultado, pero no se afirma una ejecución cuando no existe evidencia suficiente.

Este archivo constituye la bitácora única de prompts y evidencias del proyecto hasta el cierre arquitectónico del Caso de Estudio 2.
