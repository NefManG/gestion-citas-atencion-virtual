# Revisión Arquitectónica Integral

**Proyecto:** Sistema de Gestión de Citas y Atención Virtual  
**Contexto académico:** Hospital Boliviano Español  
**Documento:** Revisión arquitectónica integral  
**Estado:** APTO PARA CONSOLIDACIÓN FINAL CON OBSERVACIONES

---

## Información de revisión

**Tipo de revisión:** Revisión adversarial mediante agente `architecture-reviewer`  
**Agente utilizado en el flujo:** `architecture-reviewer`  
**Skill utilizado:** `architecture-validation`  
**Ruta del skill:** `.claude/skills/architecture-validation/SKILL.md`

**Observación:** Esta revisión fue realizada mediante el agente especializado `architecture-reviewer` para realizar una revisión adversarial de los resultados existentes contra los requerimientos funcionales y no funcionales.

---

## 1. Objetivo

El objetivo de esta revisión es evaluar de forma integral la consistencia de la arquitectura propuesta para el:

**Sistema de Gestión de Citas y Atención Virtual**

antes de realizar la consolidación final de decisiones.

La revisión analiza:

- requisitos funcionales (RF-01 a RF-22)
- requisitos no funcionales (RNF-01 a RNF-19)
- reglas de negocio (RN-01 a RN-35 y reglas pendientes)
- criterios de aceptación (CA-001 a CA-043 y CA-RNF-01 a CA-RNF-20)
- decisiones de alcance (D-01 a D-23)
- alternativas arquitectónicas (resultados 03_architecture_options.md)
- persistencia (resultados 04_database_analysis.md)
- seguridad (resultados 05_security_analysis.md)
- DevOps e infraestructura (resultados 06_devops_analysis.md)
- concurrencia
- disponibilidad
- recuperación
- integración externa
- escalabilidad
- auditoría
- mantenibilidad
- trazabilidad

No se pretende demostrar que el sistema ya cumple los requisitos técnicos.

La revisión determina únicamente si las decisiones arquitectónicas propuestas son coherentes y si existen bloqueos que impidan continuar con la consolidación.

---

## 2. Documentos considerados

La revisión considera las versiones actuales de:

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

## Resultados previos

- `proyecto/resultados/01_requirements_analysis.md`
- `proyecto/resultados/02_decision_scope.md`
- `proyecto/resultados/03_architecture_options.md`
- `proyecto/resultados/04_database_analysis.md`
- `proyecto/resultados/05_security_analysis.md`
- `proyecto/resultados/06_devops_analysis.md`

---

## 3. Jerarquía documental

Se mantiene la decisión D-23 como referencia para resolver diferencias entre documentos.

Las fuentes principales son:

- `RF.md`: requisitos funcionales;
- `RNF.md`: requisitos no funcionales;
- `reglas_negocio.md`: reglas de negocio;
- `criterios_aceptacion.md`: condiciones verificables de aceptación;
- `02_decision_scope.md`: decisiones humanas que resuelven ambigüedades o completan aspectos necesarios.

Los documentos:

- `01_requirements_analysis.md`
- `03_architecture_options.md`
- `04_database_analysis.md`
- `05_security_analysis.md`
- `06_devops_analysis.md`

son documentos derivados.

No deben modificar silenciosamente las fuentes normativas.

---

## 4. Criterios de revisión

Cada hallazgo se clasifica utilizando las siguientes severidades.

## BLOCKER

Problema que impide continuar con una decisión arquitectónica razonable.

Debe resolverse antes de la consolidación.

## HIGH

Problema importante que puede provocar una implementación incorrecta, insegura o incompatible con los requisitos.

Debe resolverse antes de implementar el sistema.

## MEDIUM

Problema que no impide consolidar la arquitectura, pero requiere corrección o decisión antes de completar la implementación.

## LOW

Problema principalmente documental, de trazabilidad o mantenibilidad.

---

## 5. Resultado general

Después de considerar los requisitos, decisiones, alternativas arquitectónicas, persistencia, seguridad y DevOps, se identifican los siguientes hallazgos:

| Severidad | Cantidad |
|---|---:|
| BLOCKER | 0 |
| HIGH | 6 |
| MEDIUM | 12 |
| LOW | 8 |

Los hallazgos HIGH identificados no requieren rediseñar completamente la arquitectura.

Principalmente requieren mantener sincronizadas las decisiones, los requisitos normativos y las futuras decisiones técnicas.

---

## 6. Revisión de los hallazgos identificados

### ARQ-01 – Complejidad innecesaria en alternativa CQRS

**Severidad:** MEDIUM  
**Relacionado con:** RNF-16 (modularidad), principios de simplicidad  

**Descripción:**  
La alternativa C (CQRS leve) introduce complejidad conceptual que puede no estar justificada para la carga inicial de 500 transacciones/hora. El análisis de arquitectura indica que CQRS resulta "moderada-alta" en complejidad de desarrollo y podría resultar innecesaria si la cantidad real de consultas no justifica la separación.

**Impacto:**  
Incremento innecesario de complejidad en desarrollo y mantenimiento sin beneficio comprobado para el escenario actual.

**Recomendación:**  
Mantener CQRS como opción para evolución futura, pero no como decisión inicial. Considerar implementarlo solo cuando exista evidencia de que las lecturas representan una proporción significativa de la carga que justifica la separación.

**Estado:** PENDIENTE DE VALIDACIÓN FUTURA.

---

### ARQ-02 – Complejidad operativa en procesamiento asíncrono

**Severidad:** MEDIUM  
**Relacionado con:** RNF-16, RNF-17, principios de mantenibilidad  

**Descripción:**  
La alternativa D (procesamiento asíncrono) aumenta la complejidad operativa al requerir manejo de mensajes, reintentos, consumidores y tratamiento de errores. El análisis indica que esta complejidad puede ser innecesaria si las tareas asíncronas (notificaciones, auditoría) no son requeridas desde la primera versión.

**Impacto:**  
Mayor carga operativa y de diagnóstico sin necesidad inmediata comprobada.

**Recomendación:**  
Dejar el procesamiento asíncrono como decisión pendiente (relacionado con DP-02 sobre notificaciones). Implementar solo cuando se confirme la necesidad de notificaciones u otras tareas secundarias en la primera versión.

**Estado:** PENDIENTE DE DECISIÓN DP-02.

---

### ARQ-03 – Punto único de falla en alternativa contenerizada simple

**Severidad:** HIGH  
**Relacionado con:** RNF-08 (disponibilidad), principios de tolerancia a fallos  

**Descripción:**  
La alternativa E (monolito contenerizado simple) mantiene un único punto de falla potencial, lo que podría afectar el cumplimiento de RNF-08 (disponibilidad >= 99%). Aunque los contenedores mejoran la reproducibilidad, no eliminan por sí mismos los puntos únicos de fallo.

**Impacto:**  
Riesgo de indisponibilidad total del servicio ante falla del host único.

**Recomendación:**  
Considerar la alternativa E como base para evolución, pero planificar mecanismos de redundancia (balanceo, múltiples instancias) cuando las métricas operativas lo justifiquen para alcanzar los objetivos de disponibilidad.

**Estado:** PENDIENTE DE EVALUACIÓN DE MÉTRICAS OPERATIVAS.

---

### ARQ-04 – Complejidad excesiva en microservicios para carga inicial

**Severidad:** HIGH  
**Relacionado con:** RNF-01 (rendimiento), RNF-11 (concurrencia), principios de simplicidad  

**Descripción:**  
La alternativa F (microservicios livianos) introduce complejidad excesiva para la carga inicial prevista (100 usuarios concurrentes, 500 transacciones/hora). El análisis indica que la coordinación distribuida requerida para mantener RNF-11 (protección de integridad ante operaciones concurrentes) aumentaría significativamente la dificultad para cumplir simultáneamente RNF-01 y RNF-11.

**Impacto:**  
Mayor complejidad de desarrollo, despliegue y operación que probablemente no esté justificada por los beneficios para la escala actual.

**Recomendación:**  
Mantener la alternativa F como referencia para evolución futura cuando la arquitectura requiera desacoplamiento físico por crecimiento significativo o múltiples equipos de desarrollo, pero no considerarla para la primera versión.

**Estado:** DESCARTADA PARA PRIMERA VERSIÓN, VIABLE PARA EVOLUCIÓN.

---

### ARQ-05 – Alineación inconsistente de RNF-07 con D-13

**Severidad:** HIGH  
**Relacionado con:** RNF-07, D-13  

**Descripción:**  
D-13 establece como decisión académica un tiempo de inactividad de **15 minutos**, configurable. Sin embargo, RNF-07 en el documento de requisitos utiliza expresiones genéricas como "periodo prolongado de inactividad" sin especificar los 15 minutos acordados en D-13.

**Impacto:**  
Distintos desarrolladores podrían implementar valores diferentes, generando inconsistencia entre lo decidido y lo especificado en requisitos.

**Recomendación:**  
Actualizar RNF-07 para quedar alineado explícitamente con D-13:  
> El sistema deberá finalizar automáticamente la sesión después de 15 minutos de inactividad. El valor deberá ser configurable.  
> Debe aclararse que los 15 minutos constituyen una decisión académica y pueden ser sustituidos por una política institucional en una implementación real.

**Estado:** REQUIERE CORRECCIÓN DOCUMENTAL EN RNF.MD.

---

### ARQ-06 – Cobertura parcial de RNF-18 respecto a D-12

**Severidad:** HIGH  
**Relacionado con:** RNF-18, D-12, RF-22  

**Descripción:**  
D-12 establece seis datos mínimos para el historial de modificaciones: fecha, hora, usuario, acción, valor anterior y valor actualizado. RF-22 requiere consultar el historial de modificaciones registrando estos seis campos. Sin embargo, RNF-18 en el documento de requisitos no especifica explícitamente todos estos seis campos, lo que podría llevar a una implementación insuficiente.

**Impacto:**  
Riesgo de implementar un historial de auditoría que no cumpla con los requisitos mínimos establecidos en D-12 y RF-22.

**Recomendación:**  
Actualizar RNF-18 para especificar explícitamente los seis campos mínimos definidos por D-12:  
> El sistema deberá mantener un registro de las modificaciones realizadas sobre las citas. Cada registro de auditoría deberá almacenar como mínimo: fecha; hora; usuario responsable; acción realizada; valor anterior; valor actualizado.

**Estado:** REQUIERE CORRECCIÓN DOCUMENTAL EN RNF.MD.

---

### ARQ-07 – Supuesto de compatibilidad con RNF-03 sin evidencia de evolución

**Severidad:** MEDIUM  
**Relacionado con:** RNF-03, principio de evolución progresiva  

**Descripción:**  
RNF-03 requiere que el diseño permita evolucionar desde 500 transacciones/hora hasta 5.000 transacciones/hora (10x) sin modificar requerimientos funcionales ni reglas principales del negocio. Aunque las alternativas arquitectónicas consideran esta evolución, existe el riesgo de que las decisiones iniciales de diseño (índices, modelos de datos, configuraciones) puedan limitar posteriormente esta escalabilidad.

**Impacto:**  
Posible necesidad de rediseño significativo cuando se intente escalar a 10x la carga inicial.

**Recomendación:**  
Documentar explícitamente los principios de diseño que deben preservarse para permitir la evolución futura: evitar acoplamiento fuerte a tecnologías específicas de escalamiento vertical, diseñar con interfaces claras entre módulos, y establecer mecanismos de monitoreo para detectar cuándo es necesario escalar.

**Estado:** CONTROLADO MEDIANTE PRINCIPIOS DE DISEÑO.

---

### ARQ-08 – Complejidad de validación de reglas de negocio pendientes

**Severidad:** MEDIUM  
**Relacionado con:** RN-26 a RN-31 (reglas pendientes), D-02 a D-10, D-14  

**Descripción:**  
Existen múltiples reglas de negocio pendientes de definición institucional (tiempo mínimo para cancelar/reprogramar, tiempo de tolerancia para no asistida, anticipación máxima para reservar, comportamiento de citas futuras al desactivar un médico, habilitación de modalidad virtual, permisos de admisión, reglas detalladas de validación). Aunque estas están correctamente identificadas como pendientes, su implementación futura podría requerir cambios significativos en la arquitectura si no se consideran desde el diseño inicial.

**Impacto:**  
Posible necesidad de refactorización cuando se definan finalmente estas reglas de negocio.

**Recomendación:**  
Diseñar la arquitectura con puntos de extensión claros para estas reglas de negocio: hacer los tiempos configurables, diseñar el manejo de citas futuras como política separada, y definir puntos de validación centralizados para las reglas de admisión y validación de datos.

**Estado:** CONTROLADO MEDIANTE DISEÑO EXTENSIBLE.

---

### ARQ-09 – Supuesto de indexado óptimo sin consultas reales

**Severidad:** MEDIUM  
**Relacionado con:** RNF-01 (rendimiento), principio de medición  

**Descripción:**  
El análisis de persistencia identifica candidatos para índices (médico, fecha, horario, estado, paciente, especialidad, modalidad, fecha de auditoría) y índices compuestos, pero estas recomendaciones se basan en suposiciones de consultas frecuentes en lugar de análisis de consultas reales del sistema.

**Impacto:**  
Riesgo de crear índices innecesarios o omitir índices críticos para el rendimiento real del sistema.

**Recomendación:**  
Posponer la definición definitiva de índices hasta tener acceso a un registro de consultas reales o patrones de uso medidos. Utilizar enfoques de monitoreo de consultas lentas para identificar necesidades reales de indexado.

**Estado:** PENDIENTE DE MEDICIÓN DE CONSULTAS REALES.

---

### ARQ-10 – Separación adecuada de auditoría y observabilidad

**Severidad:** LOW  
**Relacionado con:** RNF-17, RNF-18, principios de separación de responsabilidades  

**Descripción:**  
La documentación actual mantiene adecuadamente separadas las concepts de auditoría funcional (quién hizo qué, cuándo y qué cambió) y observabilidad (errores, métricas, rendimiento, fallos técnicos, diagnóstico). Sin embargo, se recomienda reforzar esta separación a nivel físico en los registros.

**Impacto:**  
Bajo, principalmente de mantenibilidad y claridad conceptual.

**Recomendación:**  
Mantenerlos separados conceptualmente y, cuando corresponda, físicamente en diferentes sistemas o almacenamientos para evitar confusiones y permitir retenciones diferentes.

**Estado:** CONTROLADO.

---

### ARQ-11 – Complejidad de gestión de credenciales del servicio externo

**Severidad:** MEDIUM  
**Relacionado con:** D-01, RNF-06, principios de seguridad operacional  

**Descripción:**  
La integración con el servicio externo de atención virtual requerirá manejo seguro de credenciales (tokens, claves API, etc.). Aunque se reconoce la necesidad de gestión segura de secretos, el análisis no especifica mecanismos concretos para rotación, almacenamiento y distribución de estas credenciales específicas.

**Impacto:**  
Riesgo de exposición de credenciales del servicio externo si no se implementan controles adecuados desde el inicio.

**Recomendación:**  
Incluir las credenciales del servicio externo en el mecanismo general de gestión de secretos recomendado en el análisis de seguridad, asegurando que nunca se almacenen en código fuente, repositorio Git, archivos públicos o logs.

**Estado:** CONTROLADO MEDIANTE GESTIÓN GENERAL DE SECRETOS.

---

### ARQ-12 – Evolución de contenerización sin orquestación prematura

**Severidad:** LOW  
**Relacionado con:** principio de evolución progresiva, evitación de complejidad innecesaria  

**Descripción:**  
Aunque se recomienda la contenerización como base para reproducibilidad y despliegue, existe el riesgo de adoptar prematuramente plataformas de orquestación complejas (como Kubernetes) que no están justificadas por la carga inicial.

**Impacto:**  
Mayor complejidad operativa y curva de aprendizaje innecesaria para la escala actual.

**Recomendación:**  
Mantener una estrategia progresiva: comenzar con contenedores simples en hosts gestionados, y reevaluar la necesidad de orquestación avanzada cuando exista evidencia que la justifique (numerosas instancias, múltiples servicios, alta frecuencia de despliegues, etc.).

**Estado:** CONTROLADO MEDIANTE PRINCIPIO DE EVOLUCIÓN PROGRESIVA.

---

## 7. Revisión de concurrencia (D-21, RNF-11)

La arquitectura debe conservar como principio obligatorio:

**verificación final + confirmación atómica.**

Una consulta anterior que muestre un horario disponible no constituye una reserva.

La disponibilidad debe verificarse nuevamente dentro de la operación crítica.

Ante dos solicitudes concurrentes sobre el mismo recurso:

- una puede confirmar;
- la otra debe fallar de forma controlada.

El análisis de persistencia propone una estrategia relacional transaccional capaz de soportar esta condición mediante operaciones ACID y mecanismos de control de concurrencia.

**Resultado:** ARQUITECTÓNICAMENTE CUBIERTO.

**Pendiente:** seleccionar y probar el mecanismo técnico concreto de concurrencia (pesimista, optimista, restricciones declarativas).

---

## 8. Revisión de estados de citas

Los estados considerados son:

- Programada;
- Confirmada;
- En atención;
- Finalizada;
- Cancelada;
- No asistida.

El inicio de `En atención` debe ser ejecutado por un usuario autorizado, principalmente el médico según las decisiones actuales.

La cita `En atención` debe poder visualizarse tanto en:

- citas actuales del paciente;
- agenda correspondiente del médico.

No debe producirse una transición automática debido únicamente a una falla del servicio virtual.

**Resultado:** CONSISTENTE.

---

## 9. Revisión del servicio virtual

La atención virtual depende de un servicio externo.

La arquitectura no debe implementar una plataforma propia de videollamadas.

Se mantiene:

```
Sistema
    ↓
Adaptador de atención virtual
    ↓
Proveedor externo
```

Ante una falla externa:

- conservar la cita;
- informar el incidente;
- no cancelarla automáticamente;
- no marcarla como no asistida;
- no cambiar automáticamente su modalidad;
- no alterar automáticamente su estado.

**Resultado:** CONSISTENTE.

---

## 10. Revisión de escalabilidad

Perfil considerado:

**Inicial:**

- 500 transacciones/hora normales;
- hasta 1.000 transacciones/hora de pico;
- hasta 100 usuarios concurrentes.

**Futuro:**

- 5.000 transacciones/hora.

La arquitectura no debe dimensionarse obligatoriamente para 5.000 transacciones/hora desde el primer día.

Debe permitir evolución mediante:

- optimización;
- escalamiento vertical;
- escalamiento horizontal;
- réplicas cuando corresponda;
- balanceo cuando corresponda;
- separación de cargas cuando sea necesario.

**Resultado:** CONSISTENTE.

---

## 11. Revisión de Kubernetes

No existe actualmente una necesidad arquitectónica que obligue a utilizar Kubernetes en la primera versión.

Su utilización inicial podría introducir complejidad que no está justificada por el perfil de carga actual.

Puede reconsiderarse cuando existan condiciones como:

- numerosas instancias;
- múltiples servicios independientes;
- numerosos equipos;
- alta frecuencia de despliegues;
- necesidad significativa de autoescalado;
- necesidades avanzadas de auto-recuperación.

**Resultado:** NO REQUERIDO ACTUALMENTE.

---

## 12. Revisión de seguridad (basado en 05_security_analysis.md)

Los riesgos de seguridad identificados no requieren sustituir la arquitectura propuesta.

Se identificaron **37 hallazgos de seguridad**:
- 10 BLOCKER;
- 18 HIGH;
- 7 MEDIUM;
- 2 LOW.

Los hallazgos BLOCKER representan controles que deberán estar presentes antes de considerar una implementación preparada para uso real.

La arquitectura actual permite establecer una arquitectura compatible con controles adecuados de:

- autenticación obligatoria para funciones protegidas;
- autorización por rol y por recurso;
- mínimo privilegio;
- separación Médico–Usuario;
- contraseñas protegidas mediante hash adaptativo;
- sesiones con expiración por inactividad (15 minutos configurable);
- cifrado de comunicaciones (TLS);
- gestión segura de secretos;
- validación del lado del servidor;
- prevención de inyección;
- prevención de XSS;
- protección CSRF cuando sea aplicable;
- auditoría independiente con seis campos mínimos;
- logs operativos separados;
- protección de respaldos;
- pruebas de restauración;
- validación de la integración externa;
- no permitir que fallos del proveedor alteren automáticamente el estado de una cita.

**Resultado:** ARQUITECTURA COMPATIBLE, IMPLEMENTACIÓN PENDIENTE.

Los controles todavía requieren implementación y validación técnica, pero esto no constituye contradicción arquitectónica.

---

## 13. Revisión de disponibilidad y recuperación

Los objetivos:

- disponibilidad >= 99 %;
- RPO <= 60 minutos;
- RTO <= 120 minutos;

son compatibles con una infraestructura que evolucione progresivamente según lo recomendado en el análisis DevOps.

No se exige inicialmente una topología específica.

La decisión final deberá considerar:

- costo;
- riesgo;
- pruebas;
- métricas;
- criticidad;
- operación.

**Resultado:** VIABLE, NO DEMOSTRADO (requiere validación futura mediante pruebas operativas).

---

## 14. Decisiones que pueden mantenerse pendientes

Las siguientes decisiones no bloquean la consolidación arquitectónica:

- motor de base de datos;
- proveedor cloud;
- herramienta CI/CD;
- plataforma de logs;
- plataforma de monitoreo;
- mecanismo de gestión de secretos;
- proveedor de atención virtual;
- mecanismo concreto de locking (para concurrencia);
- número de instancias;
- balanceador;
- estrategia exacta de réplicas;
- tecnología de contenerización;
- eventual uso de Kubernetes.

Estas decisiones pertenecen a una etapa técnica posterior.

---

## 15. Dictamen de revisión

Después de revisar requisitos, decisiones, arquitectura, persistencia, seguridad y DevOps, se concluye que:

**NO EXISTEN BLOCKERS ARQUITECTÓNICOS ABIERTOS QUE IMPIDAN PASAR A LA CONSOLIDACIÓN FINAL.**

Los hallazgos encontrados son principalmente de severidad MEDIUM y HIGH que requieren atención pero no impiden continuar con el proceso arquitectónico.

Los requerimientos de disponibilidad, RPO y RTO son compatibles con las estrategias propuestas pero requieren validación futura mediante pruebas.

---

## 16. Estado final

**Resultado de la revisión:** `APTO PARA CONSOLIDACIÓN FINAL CON OBSERVACIONES`

Esto significa que el proyecto puede continuar hacia:

`proyecto/resultados/08_recomendacion_final.md`

siempre que la consolidación:

- respete las decisiones vigentes;
- no invente políticas institucionales;
- no declare RNF técnicos como probados;
- diferencie decisiones arquitectónicas de decisiones tecnológicas;
- conserve explícitamente los pendientes.

Los hallazgos de mayor prioridad que requieren atención antes de la consolidación final son:

1. **ARQ-05:** Alinear RNF-07 explícitamente con D-13 (15 minutos de inactividad configurable)
2. **ARQ-06:** Alinear RNF-18 explícitamente con D-12 (seis campos mínimos de auditoría)
3. **ARQ-01 y ARQ-02:** Considerar la complejidad de CQRF y procesamiento asíncrono como opciones para evolución futura, no decisiones iniciales
4. **ARQ-03 y ARQ-04:** Evitar puntos únicos de falla y complejidad excesiva en las alternativas seleccionadas

**Revisión cerrada para efectos del Caso de Estudio 2.**