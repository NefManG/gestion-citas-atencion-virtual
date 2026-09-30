# Revisión Arquitectónica Integral

**Proyecto:** Sistema de Gestión de Citas y Atención Virtual  
**Contexto académico:** Hospital Boliviano Español  
**Documento:** Revisión arquitectónica integral  
**Estado:** APTO PARA CONSOLIDACIÓN FINAL CON OBSERVACIONES

---

## Información de revisión

**Tipo de revisión:** Revisión humana asistida por ChatGPT.

**Agente previsto en el flujo original:** `architecture-reviewer`

**Skill previsto:** `architecture-validation`

**Ruta prevista del skill:**

`.claude/skills/architecture-validation/SKILL.md`

**Observación de evidencia:**

Esta versión no debe registrarse como una ejecución real del subagente
`architecture-reviewer`, debido a que la revisión fue realizada fuera de
Claude Code después de agotarse la cuota disponible.

El documento podrá ser contrastado posteriormente mediante el subagente
especializado cuando vuelva a estar disponible.

---

# 1. Objetivo

El objetivo de esta revisión es evaluar de forma integral la consistencia de
la arquitectura propuesta para el:

**Sistema de Gestión de Citas y Atención Virtual**

antes de realizar la consolidación final de decisiones.

La revisión analiza:

- requisitos funcionales;
- requisitos no funcionales;
- reglas de negocio;
- criterios de aceptación;
- decisiones de alcance;
- alternativas arquitectónicas;
- persistencia;
- seguridad;
- DevOps e infraestructura;
- concurrencia;
- disponibilidad;
- recuperación;
- integración externa;
- escalabilidad;
- auditoría;
- mantenibilidad;
- trazabilidad.

No se pretende demostrar que el sistema ya cumple los requisitos técnicos.

La revisión determina únicamente si las decisiones arquitectónicas propuestas
son coherentes y si existen bloqueos que impidan continuar con la
consolidación.

---

# 2. Documentos considerados

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

# 3. Jerarquía documental

Se mantiene la decisión D-23 como referencia para resolver diferencias entre
documentos.

Las fuentes principales son:

- `RF.md`: requisitos funcionales;
- `RNF.md`: requisitos no funcionales;
- `reglas_negocio.md`: reglas de negocio;
- `criterios_aceptacion.md`: condiciones verificables de aceptación;
- `02_decision_scope.md`: decisiones humanas que resuelven ambigüedades o
  completan aspectos necesarios.

Los documentos:

- `01_requirements_analysis.md`
- `03_architecture_options.md`
- `04_database_analysis.md`
- `05_security_analysis.md`
- `06_devops_analysis.md`

son documentos derivados.

No deben modificar silenciosamente las fuentes normativas.

---

# 4. Criterios de revisión

Cada hallazgo se clasifica utilizando las siguientes severidades.

## BLOCKER

Problema que impide continuar con una decisión arquitectónica razonable.

Debe resolverse antes de la consolidación.

## HIGH

Problema importante que puede provocar una implementación incorrecta,
insegura o incompatible con los requisitos.

Debe resolverse antes de implementar el sistema.

## MEDIUM

Problema que no impide consolidar la arquitectura, pero requiere corrección o
decisión antes de completar la implementación.

## LOW

Problema principalmente documental, de trazabilidad o mantenibilidad.

---

# 5. Resultado general

Después de considerar las decisiones adoptadas y los análisis especializados,
no se identifican actualmente bloqueos arquitectónicos que impidan continuar
hacia la consolidación final.

Resumen:

| Severidad | Cantidad |
|---|---:|
| BLOCKER | 0 |
| HIGH | 3 |
| MEDIUM | 5 |
| LOW | 2 |

Los hallazgos HIGH identificados no requieren rediseñar completamente la
arquitectura.

Principalmente requieren mantener sincronizadas las decisiones, los requisitos
normativos y las futuras decisiones técnicas.

---

# 6. Revisión de los bloqueos identificados anteriormente

## ARB-01 – Concurrencia y doble reserva

**Estado anterior:** BLOCKER

**Problema original:**

No existía una definición suficiente para garantizar que dos usuarios no
confirmaran simultáneamente el mismo horario.

**Resolución:**

D-21 establece que la confirmación de la cita debe:

1. verificar nuevamente la disponibilidad;
2. ejecutar la confirmación de forma atómica;
3. permitir únicamente un ganador ante solicitudes concurrentes.

RNF-11 complementa esta garantía.

El análisis de persistencia propone una estrategia relacional transaccional
capaz de soportar esta condición.

**Estado actual:** RESUELTO A NIVEL ARQUITECTÓNICO.

**Pendiente:** seleccionar y probar el mecanismo técnico concreto.

---

## ARB-02 – Servicio externo de atención virtual

**Estado anterior:** BLOCKER

**Problema original:**

La arquitectura dependía de un proveedor externo todavía no definido.

**Resolución:**

D-01 establece una abstracción o adaptador entre la lógica del sistema y el
servicio externo.

Esto permite sustituir el proveedor sin alterar directamente las reglas
centrales del negocio.

**Estado actual:** RESUELTO A NIVEL ARQUITECTÓNICO.

La elección del proveedor permanece correctamente pendiente.

---

## ARB-03 – Disponibilidad, RPO y RTO

**Estado anterior:** BLOCKER

**Problema original:**

Los objetivos eran:

- disponibilidad >= 99 %;
- RPO <= 60 minutos;
- RTO <= 120 minutos;

pero no existía una estrategia suficiente para considerarlos.

**Resolución:**

D-22 y `06_devops_analysis.md` establecen una estrategia que contempla:

- respaldo;
- copia separada;
- recuperación;
- monitoreo;
- pruebas;
- posible redundancia;
- evolución progresiva de infraestructura.

**Estado actual:** RESUELTO COMO ESTRATEGIA.

**No está probado su cumplimiento.**

RNF-08, RNF-09 y RNF-10 deben validarse posteriormente mediante operación y
pruebas reales.

---

## ARB-04 – Crecimiento 10x

**Estado anterior:** BLOCKER

**Problema original:**

No estaba definido qué significaba el crecimiento 10x.

**Resolución:**

D-18 establece:

- 500 transacciones/hora como carga normal;
- 1.000 transacciones/hora como pico inmediato;
- 5.000 transacciones/hora como escenario futuro.

El valor futuro no implica dimensionar desde el primer día toda la
infraestructura para 5.000 transacciones/hora.

**Estado actual:** RESUELTO.

---

## ARB-05 – Relación Médico y Usuario

**Estado anterior:** BLOCKER

**Problema original:**

Los conceptos Médico y Usuario no estaban suficientemente diferenciados.

**Resolución:**

D-06 establece que:

- Médico y Usuario son entidades distintas;
- un Médico puede existir sin una cuenta;
- un Médico puede tener como máximo un Usuario asociado;
- un Usuario con rol Médico debe estar asociado a un Médico para utilizar
  funciones clínicas;
- la desactivación de ambas entidades es independiente;
- el historial debe conservarse.

**Estado actual:** RESUELTO.

---

# 7. Validación de decisiones arquitectónicas principales

## 7.1 Arquitectura modular

Las alternativas analizadas favorecen una aplicación modular para la primera
versión.

La separación modular resulta consistente con:

- mantenibilidad;
- crecimiento progresivo;
- integración externa;
- separación de responsabilidades;
- perfil de carga actual.

No existe evidencia que obligue actualmente a utilizar microservicios.

**Resultado:** CONSISTENTE.

---

## 7.2 Persistencia

El análisis de base de datos recomienda provisionalmente:

**persistencia relacional transaccional con capacidad de evolución.**

La estrategia es coherente con:

- relaciones estructuradas;
- integridad referencial;
- reserva atómica;
- control de concurrencia;
- historial;
- auditoría;
- reprogramaciones;
- cancelaciones.

El motor específico continúa correctamente sin seleccionarse.

**Resultado:** CONSISTENTE.

---

## 7.3 Seguridad

El análisis de seguridad identifica controles necesarios sobre:

- autenticación;
- autorización;
- roles;
- sesiones;
- datos personales;
- auditoría;
- secretos;
- servicios externos;
- transmisión;
- logs;
- respaldo.

Los controles todavía requieren implementación.

Esto no constituye contradicción arquitectónica.

**Resultado:** CONSISTENTE CON PENDIENTES DE IMPLEMENTACIÓN.

---

## 7.4 Infraestructura

La estrategia DevOps recomienda evolución progresiva.

La propuesta evita asumir prematuramente:

- Kubernetes;
- microservicios;
- grandes clústeres;
- cantidades fijas de servidores.

También permite evolucionar posteriormente hacia:

- balanceo;
- múltiples instancias;
- réplicas;
- redundancia;
- escalamiento horizontal.

**Resultado:** CONSISTENTE.

---

# 8. Hallazgos actuales

## REV-01 – Sincronización del timeout de sesión

**Severidad:** HIGH

**Relacionado con:** RNF-07, D-13

**Descripción:**

D-13 establece como decisión académica un tiempo de inactividad de
**15 minutos**, configurable.

Debe verificarse que RNF-07 se encuentre sincronizado con esta decisión.

Si RNF-07 todavía utiliza únicamente expresiones como:

“periodo prolongado de inactividad”

el requisito continúa siendo ambiguo por sí mismo.

**Impacto:**

Distintos desarrolladores podrían implementar valores diferentes.

**Recomendación:**

RNF-07 debe quedar alineado explícitamente con D-13.

Redacción esperada:

> El sistema deberá finalizar automáticamente la sesión después de
> 15 minutos de inactividad. El valor deberá ser configurable.

Debe aclararse que los 15 minutos constituyen una decisión académica y pueden
ser sustituidos por una política institucional en una implementación real.

**Estado:** VERIFICAR SINCRONIZACIÓN DOCUMENTAL.

---

## REV-02 – Información mínima de auditoría

**Severidad:** HIGH

**Relacionado con:** RF-22, RNF-18, D-12

**Descripción:**

D-12 establece seis datos mínimos para el historial de modificaciones:

1. fecha;
2. hora;
3. usuario;
4. acción;
5. valor anterior;
6. valor actualizado.

Debe verificarse que RNF-18 no continúe especificando únicamente:

- fecha;
- hora;
- usuario.

**Impacto:**

Podría implementarse un historial insuficiente respecto de RF-22 y D-12.

**Recomendación:**

Mantener los seis campos definidos por D-12 como información mínima de
auditoría funcional.

**Estado:** VERIFICAR SINCRONIZACIÓN DOCUMENTAL.

---

## REV-03 – Políticas funcionales pendientes

**Severidad:** HIGH

**Relacionado con:** D-02, D-03, D-04, D-07, D-08, D-09, D-10, D-14

**Descripción:**

Continúan pendientes decisiones como:

- anticipación mínima para cancelar o reprogramar;
- tolerancia para considerar una cita no asistida;
- anticipación máxima para reservar;
- tratamiento de futuras citas al desactivar un médico;
- habilitación de modalidad virtual;
- relación de modalidad con horarios;
- permisos exactos del rol de admisión;
- reglas detalladas de validación de datos.

**Impacto:**

No impiden seleccionar un estilo arquitectónico, pero sí impiden implementar
completamente determinados casos de uso.

**Recomendación:**

Mantener estas decisiones como parámetros o decisiones funcionales pendientes
y resolverlas antes de implementar las funcionalidades afectadas.

No deben inventarse valores técnicos o administrativos.

**Estado:** PENDIENTE.

---

## REV-04 – Referencias antiguas en documentos derivados

**Severidad:** MEDIUM

**Relacionado con:** reglas de negocio, `03_architecture_options.md`

**Descripción:**

Durante etapas anteriores el proyecto utilizó una numeración de reglas de
negocio más pequeña.

La versión actual alcanza reglas posteriores, incluyendo RN-33 a RN-35.

Los documentos derivados deben evitar conservar referencias antiguas como si
representaran la totalidad de las reglas actuales.

**Impacto:**

Puede afectar la trazabilidad documental.

**Recomendación:**

En la consolidación final utilizar únicamente la numeración vigente.

No es necesario rehacer las alternativas arquitectónicas si su razonamiento
sigue siendo válido.

**Estado:** CORRECCIÓN DOCUMENTAL.

---

## REV-05 – Motor de base de datos todavía pendiente

**Severidad:** MEDIUM

**Relacionado con:** `04_database_analysis.md`

**Descripción:**

El análisis de persistencia determina una estrategia relacional
transaccional, pero no selecciona todavía el motor.

Esto es correcto para esta etapa.

**Riesgo:**

La consolidación final podría confundir la estrategia con una selección
definitiva de PostgreSQL, SQL Server, MySQL u otro producto.

**Recomendación:**

Mantener claramente separadas:

- estrategia de persistencia;
- selección posterior del motor.

**Estado:** CONTROLADO.

---

## REV-06 – Requisitos operativos todavía no demostrados

**Severidad:** MEDIUM

**Relacionado con:** RNF-01, RNF-08, RNF-09, RNF-10

**Descripción:**

Actualmente existen objetivos de:

- respuesta <= 3 segundos;
- disponibilidad >= 99 %;
- RPO <= 60 minutos;
- RTO <= 120 minutos.

Los análisis establecen estrategias compatibles, pero no evidencia de
cumplimiento.

**Impacto:**

Declararlos como “cumplidos” antes de realizar pruebas produciría una falsa
certeza.

**Recomendación:**

Utilizar los estados:

- diseñado;
- compatible;
- pendiente de implementación;
- pendiente de prueba;

según corresponda.

**Estado:** REQUIERE VALIDACIÓN FUTURA.

---

## REV-07 – Servicio externo todavía sin proveedor

**Severidad:** MEDIUM

**Relacionado con:** D-01, D-19

**Descripción:**

La arquitectura define correctamente un adaptador para integrar el servicio de
atención virtual.

El proveedor definitivo continúa pendiente.

**Impacto:**

La selección futura puede afectar:

- API;
- autenticación;
- costos;
- disponibilidad;
- timeouts;
- reintentos.

**Recomendación:**

Mantener la abstracción definida y evaluar proveedores posteriormente.

**Estado:** CONTROLADO ARQUITECTÓNICAMENTE.

---

## REV-08 – Retención funcional y backups

**Severidad:** MEDIUM

**Relacionado con:** D-20, D-22

**Descripción:**

D-20 establece una retención académica mínima de cinco años para citas
históricas y auditoría.

Esto no significa que todas las copias de respaldo tengan que conservarse
durante cinco años.

**Recomendación:**

Mantener separadas:

- política de conservación de información;
- política de backups;
- política de archivado;
- estrategia de recuperación.

**Estado:** CONTROLADO, PENDIENTE DE POLÍTICA TÉCNICA.

---

## REV-09 – Auditoría y observabilidad

**Severidad:** LOW

**Relacionado con:** RNF-17, RNF-18

**Descripción:**

La auditoría funcional y los logs técnicos tienen objetivos diferentes.

**Recomendación:**

Mantenerlos separados conceptualmente y, cuando corresponda, físicamente.

Auditoría:

- quién;
- qué;
- cuándo;
- valores anteriores;
- valores posteriores.

Observabilidad:

- errores;
- métricas;
- rendimiento;
- fallos técnicos;
- diagnóstico.

**Estado:** CONTROLADO.

---

## REV-10 – Evidencia de agentes y skills

**Severidad:** LOW

**Relacionado con:** proceso académico

**Descripción:**

No todos los documentos históricos poseen evidencia equivalente de que el
subagente y el skill correspondiente fueron ejecutados realmente.

Esto no modifica la validez técnica del análisis, pero sí afecta la evidencia
del proceso académico.

**Recomendación:**

En `PROMPTS_Y_EVIDENCIAS.md` diferenciar posteriormente entre:

- agente solicitado;
- agente cuya ejecución fue comprobada;
- skill configurado;
- skill cuya carga pudo comprobarse;
- revisión humana;
- resultado final.

No afirmar ejecuciones que no puedan demostrarse.

**Estado:** DOCUMENTAL.

---

# 9. Revisión de concurrencia

La arquitectura debe conservar como principio obligatorio:

**verificación final + confirmación atómica.**

Una consulta anterior que muestre un horario disponible no constituye una
reserva.

La disponibilidad debe verificarse nuevamente dentro de la operación crítica.

Ante dos solicitudes concurrentes sobre el mismo recurso:

- una puede confirmar;
- la otra debe fallar de forma controlada.

La tecnología específica para lograrlo permanece pendiente.

Puede utilizar mecanismos equivalentes proporcionados por el motor de
persistencia elegido.

**Resultado:** ARQUITECTÓNICAMENTE CUBIERTO.

---

# 10. Revisión de estados de citas

Los estados considerados son:

- Programada;
- Confirmada;
- En atención;
- Finalizada;
- Cancelada;
- No asistida.

El inicio de `En atención` debe ser ejecutado por un usuario autorizado,
principalmente el médico según las decisiones actuales.

La cita `En atención` debe poder visualizarse tanto en:

- citas actuales del paciente;
- agenda correspondiente del médico.

No debe producirse una transición automática debido únicamente a una falla
del servicio virtual.

**Resultado:** CONSISTENTE.

---

# 11. Revisión del servicio virtual

La atención virtual depende de un servicio externo.

La arquitectura no debe implementar una plataforma propia de videollamadas.

Se mantiene:

Sistema
    ↓
Adaptador de atención virtual
    ↓
Proveedor externo

Ante una falla externa:

- conservar la cita;
- informar el incidente;
- no cancelarla automáticamente;
- no marcarla como no asistida;
- no cambiar automáticamente su modalidad;
- no alterar automáticamente su estado.

**Resultado:** CONSISTENTE.

---

# 12. Revisión de escalabilidad

Perfil considerado:

**Inicial:**

- 500 transacciones/hora normales;
- hasta 1.000 transacciones/hora de pico;
- hasta 100 usuarios concurrentes.

**Futuro:**

- 5.000 transacciones/hora.

La arquitectura no debe dimensionarse obligatoriamente para 5.000
transacciones/hora desde el primer día.

Debe permitir evolución mediante:

- optimización;
- escalamiento vertical;
- escalamiento horizontal;
- réplicas cuando corresponda;
- balanceo cuando corresponda;
- separación de cargas cuando sea necesario.

**Resultado:** CONSISTENTE.

---

# 13. Revisión de Kubernetes

No existe actualmente una necesidad arquitectónica que obligue a utilizar
Kubernetes en la primera versión.

Su utilización inicial podría introducir complejidad que no está justificada
por el perfil de carga actual.

Puede reconsiderarse cuando existan condiciones como:

- numerosas instancias;
- múltiples servicios independientes;
- numerosos equipos;
- alta frecuencia de despliegues;
- necesidad significativa de autoescalado;
- necesidades avanzadas de auto-recuperación.

**Resultado:** NO REQUERIDO ACTUALMENTE.

---

# 14. Revisión de seguridad

Los riesgos de seguridad identificados no requieren sustituir la arquitectura
propuesta.

La solución deberá implementar posteriormente:

- autenticación;
- autorización por roles;
- mínimo privilegio;
- seguridad de sesiones;
- protección de secretos;
- TLS;
- validación de entradas;
- auditoría;
- protección de logs;
- protección de backups;
- seguridad de integraciones externas.

**Resultado:** ARQUITECTURA COMPATIBLE, IMPLEMENTACIÓN PENDIENTE.

---

# 15. Revisión de disponibilidad y recuperación

Los objetivos:

- disponibilidad >= 99 %;
- RPO <= 60 minutos;
- RTO <= 120 minutos;

son compatibles con una infraestructura que evolucione progresivamente.

No se exige inicialmente una topología específica.

La decisión final deberá considerar:

- costo;
- riesgo;
- pruebas;
- métricas;
- criticidad;
- operación.

**Resultado:** VIABLE, NO DEMOSTRADO.

---

# 16. Decisiones que pueden mantenerse pendientes

Las siguientes decisiones no bloquean la consolidación arquitectónica:

- motor de base de datos;
- proveedor cloud;
- herramienta CI/CD;
- plataforma de logs;
- plataforma de monitoreo;
- mecanismo de gestión de secretos;
- proveedor de atención virtual;
- mecanismo concreto de locking;
- número de instancias;
- balanceador;
- estrategia exacta de réplicas;
- tecnología de contenerización;
- eventual uso de Kubernetes.

Estas decisiones pertenecen a una etapa técnica posterior.

---

# 17. Condiciones para la consolidación final

Antes o durante `08_recomendacion_final.md` deben respetarse las siguientes
condiciones:

1. No presentar requisitos no probados como cumplidos.
2. No seleccionar tecnologías únicamente por preferencia.
3. Mantener D-21 para concurrencia.
4. Mantener D-01 para integración externa.
5. Mantener D-19 para contingencia virtual.
6. Mantener D-20 como decisión académica de retención.
7. Mantener D-22 como objetivo operativo.
8. Mantener D-23 como jerarquía documental.
9. Mantener separadas auditoría y observabilidad.
10. Mantener la posibilidad de crecimiento sin sobredimensionamiento inicial.
11. Mantener las decisiones funcionales pendientes explícitamente
    identificadas.
12. Verificar la sincronización de RNF-07 con D-13.
13. Verificar la sincronización de RNF-18 con D-12.

---

# 18. Dictamen de revisión

Después de revisar requisitos, decisiones, arquitectura, persistencia,
seguridad y DevOps, se concluye que:

**NO EXISTEN BLOCKERS ARQUITECTÓNICOS ABIERTOS QUE IMPIDAN PASAR A LA
CONSOLIDACIÓN FINAL.**

Los bloqueos encontrados durante revisiones anteriores fueron tratados
mediante las decisiones de alcance y los análisis especializados.

Persisten:

- decisiones funcionales pendientes;
- decisiones tecnológicas pendientes;
- validaciones mediante pruebas;
- correcciones menores de sincronización documental.

Estas condiciones no requieren reiniciar el diseño arquitectónico.

---

# 19. Estado final

**Resultado de la revisión:**

`APTO PARA CONSOLIDACIÓN FINAL CON OBSERVACIONES`

Esto significa que el proyecto puede continuar hacia:

`proyecto/resultados/08_recomendacion_final.md`

siempre que la consolidación:

- respete las decisiones vigentes;
- no invente políticas institucionales;
- no declare RNF técnicos como probados;
- diferencie decisiones arquitectónicas de decisiones tecnológicas;
- conserve explícitamente los pendientes.

---

# 20. Conclusión

La arquitectura del Sistema de Gestión de Citas y Atención Virtual ha
evolucionado desde una definición inicial con múltiples ambigüedades hacia una
propuesta más consistente.

Los principales riesgos arquitectónicos relacionados con:

- concurrencia;
- dependencia del proveedor virtual;
- crecimiento;
- disponibilidad;
- RPO;
- RTO;
- relación Médico-Usuario;
- auditoría;
- recuperación;

cuentan actualmente con decisiones o estrategias arquitectónicas que permiten
continuar.

La recomendación final deberá favorecer una solución:

- modular;
- mantenible;
- transaccional;
- segura;
- observable;
- escalable progresivamente;
- independiente del proveedor de atención virtual;
- compatible con los objetivos operativos definidos.

La selección definitiva de tecnologías deberá realizarse después de consolidar
estas decisiones y no antes.

**Revisión cerrada para efectos del Caso de Estudio 2.**