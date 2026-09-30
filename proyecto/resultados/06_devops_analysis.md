# Análisis Especializado DevOps e Infraestructura

**Proyecto:** Sistema de Gestión de Citas y Atención Virtual  
**Contexto académico:** Hospital Boliviano Español  
**Tipo de documento:** Análisis DevOps e infraestructura  
**Estado:** REVISADO – RECOMENDACIÓN PROVISIONAL

---

## Información de ejecución

**Agente utilizado:** `devops-architect`

**Skill asociado:** `infrastructure-evaluation`

**Ruta configurada del skill:**

`.claude/skills/infrastructure-evaluation/SKILL.md`

**Observación:** Existe evidencia de ejecución del subagente
`devops-architect`. La utilización interna del skill
`infrastructure-evaluation` se considera asociada a la configuración del
agente, pero no debe afirmarse como ejecución independiente si la salida de
Claude Code no muestra evidencia explícita de su carga.

---

# 1. Objetivo

El presente documento analiza la estrategia DevOps e infraestructura para el
proyecto académico **Sistema de Gestión de Citas y Atención Virtual**.

El propósito es evaluar las necesidades operativas del sistema antes de
seleccionar una plataforma, proveedor cloud o herramienta tecnológica
definitiva.

El análisis considera principalmente:

- despliegue;
- ambientes;
- integración y entrega continua;
- disponibilidad;
- tolerancia a fallos;
- respaldo;
- recuperación;
- RPO;
- RTO;
- monitoreo;
- observabilidad;
- gestión de logs;
- seguridad operacional;
- escalabilidad;
- integración con servicios externos;
- complejidad operativa;
- crecimiento futuro.

El análisis no pretende demostrar que los requisitos no funcionales ya se
encuentran cumplidos.

Los requisitos de disponibilidad, rendimiento, recuperación y escalabilidad
deberán posteriormente ser demostrados mediante implementación, pruebas de
carga, pruebas de recuperación y monitoreo operativo.

---

# 2. Fuentes analizadas

Para elaborar este análisis se consideran como fuentes del proyecto las
versiones actuales de:

- `proyecto/contexto/descripcion.md`
- `proyecto/contexto/alcance.md`
- `proyecto/contexto/reglas_negocio.md`
- `proyecto/contexto/restricciones.md`
- `proyecto/requisitos/RF.md`
- `proyecto/requisitos/RNF.md`
- `proyecto/requisitos/criterios_aceptacion.md`
- `proyecto/configuracion/perfil_carga.yaml`
- `proyecto/resultados/01_requirements_analysis.md`
- `proyecto/resultados/02_decision_scope.md`
- `proyecto/resultados/03_architecture_options.md`
- `proyecto/resultados/04_database_analysis.md`
- `proyecto/resultados/05_security_analysis.md`

Cuando exista una diferencia entre un documento derivado y una fuente
normativa del proyecto, debe respetarse la jerarquía definida en la decisión
D-23.

Por tanto:

- `RF.md` es fuente normativa de requisitos funcionales;
- `RNF.md` es fuente normativa de requisitos no funcionales;
- `reglas_negocio.md` es fuente normativa de reglas de negocio;
- `criterios_aceptacion.md` es fuente normativa de criterios de aceptación;
- `02_decision_scope.md` contiene las decisiones humanas adoptadas para
  resolver ambigüedades y completar aspectos necesarios del alcance.

Los demás documentos de análisis son derivados de estas fuentes.

---

# 3. Requisitos operativos relevantes

## 3.1 Perfil de carga

El perfil de carga actualmente considerado establece:

- hasta **100 usuarios concurrentes**;
- aproximadamente **500 transacciones por hora** como carga normal;
- aproximadamente **1.000 transacciones por hora** como pico inmediato;
- aproximadamente **5.000 transacciones por hora** como escenario futuro de
  crecimiento 10x.

El escenario de 5.000 transacciones por hora representa una condición futura
de evolución.

No implica que la infraestructura inicial deba dimensionarse desde el primer
día para operar permanentemente con dicha carga.

La arquitectura debe permitir evolucionar hacia ese escenario sin modificar
las reglas fundamentales del negocio.

---

## 3.2 Rendimiento

El requisito RNF-01 establece como objetivo un tiempo de respuesta de backend
de hasta **3 segundos**, considerando una carga de hasta 100 usuarios
concurrentes.

Este requisito se considera:

**Estado:** REQUIERE VALIDACIÓN MEDIANTE PRUEBAS.

El diseño de infraestructura puede ser compatible con este objetivo, pero no
es posible afirmar su cumplimiento únicamente mediante análisis documental.

Será necesario ejecutar pruebas controladas y reproducibles que permitan
medir:

- tiempo de respuesta;
- percentiles de latencia;
- errores;
- saturación de recursos;
- comportamiento de la persistencia;
- comportamiento bajo concurrencia.

---

## 3.3 Disponibilidad

El sistema establece como objetivo:

**Disponibilidad mensual >= 99 %.**

La disponibilidad deberá medirse sobre el servicio considerado operativo,
excluyendo únicamente las ventanas de mantenimiento programado que hayan sido
definidas y comunicadas según las decisiones del proyecto.

**Estado:** REQUIERE IMPLEMENTACIÓN Y MEDICIÓN.

Una topología propuesta puede ser compatible con este objetivo, pero no se
puede declarar el RNF como cumplido antes de disponer de evidencia operativa.

---

## 3.4 RPO

El objetivo establecido es:

**RPO <= 60 minutos.**

Esto significa que, ante un incidente grave, la estrategia de protección de
datos debe limitar la pérdida potencial de información a un máximo de
60 minutos.

**Estado:** REQUIERE DISEÑO, IMPLEMENTACIÓN Y PRUEBA.

Un respaldo diario aislado no sería suficiente para demostrar este objetivo.

La estrategia seleccionada deberá utilizar mecanismos capaces de mantener una
ventana de pérdida compatible con el RPO definido.

---

## 3.5 RTO

El objetivo establecido es:

**RTO <= 120 minutos.**

La plataforma debe diseñarse para poder recuperar los servicios críticos
dentro del tiempo definido.

**Estado:** REQUIERE VALIDACIÓN MEDIANTE PRUEBAS DE RECUPERACIÓN.**

La simple existencia de respaldos no demuestra el cumplimiento del RTO.

Debe verificarse mediante ejercicios reales de restauración y recuperación.

---

## 3.6 Concurrencia en la reserva

De acuerdo con D-21, la confirmación de una cita debe ejecutarse como una
operación atómica.

Antes de confirmar una reserva se deberá volver a verificar la disponibilidad.

Ante solicitudes concurrentes sobre el mismo horario:

- solamente una solicitud puede obtener exitosamente el recurso disponible;
- las demás solicitudes deberán recibir una respuesta coherente indicando que
  el horario ya no se encuentra disponible.

La infraestructura y la persistencia deberán soportar este comportamiento.

---

## 3.7 Servicio de atención virtual

De acuerdo con D-01, la integración con el servicio externo de atención
virtual debe realizarse mediante una abstracción o adaptador.

El proveedor tecnológico definitivo permanece pendiente de selección.

De acuerdo con D-19, una falla del proveedor externo no debe provocar
automáticamente:

- eliminación de la cita;
- cancelación de la cita;
- registro de inasistencia;
- cambio automático de modalidad;
- modificación automática del estado clínico o administrativo.

La infraestructura deberá permitir detectar y comunicar la falla sin alterar
incorrectamente la información de negocio.

---

## 3.8 Retención de información

La decisión D-20 establece como criterio académico una conservación mínima de
**5 años** para:

- citas históricas;
- información de auditoría relacionada.

Este valor constituye una decisión académica del proyecto y debe validarse o
reemplazarse por una política institucional antes de una implementación real.

La retención funcional de información no debe confundirse con la política de
retención de respaldos.

---

# 4. Alternativas de despliegue

Se analizan cuatro alternativas razonables.

---

## 4.1 Alternativa A – Infraestructura tradicional simple

Consiste en desplegar la aplicación utilizando una infraestructura sencilla,
con una cantidad mínima de componentes operativos.

Podría utilizar:

- servidor de aplicación;
- servicio de persistencia;
- almacenamiento de respaldos;
- mecanismo básico de monitoreo.

### Ventajas

- menor complejidad inicial;
- administración relativamente sencilla;
- menor esfuerzo de aprendizaje;
- menor costo operativo inicial;
- adecuada para ambientes académicos o cargas pequeñas.

### Desventajas

- mayor riesgo de puntos únicos de falla;
- menor flexibilidad de escalamiento;
- recuperación más dependiente de procedimientos manuales;
- actualización de componentes potencialmente más riesgosa;
- menor capacidad de aislamiento entre servicios.

### Adecuación

Puede ser suficiente para desarrollo o pruebas.

Para producción requeriría demostrar que puede alcanzar los objetivos de:

- disponibilidad;
- RPO;
- RTO;
- rendimiento.

No debe asumirse que una infraestructura de un solo servidor cumple dichos
objetivos sin pruebas.

---

## 4.2 Alternativa B – Despliegue contenerizado simple

Consiste en empaquetar los componentes de la aplicación de manera aislada y
reproducible mediante tecnología de contenedores.

No se selecciona en este documento una herramienta específica.

### Ventajas

- mayor consistencia entre ambientes;
- despliegues reproducibles;
- simplificación del empaquetado;
- facilidad para automatizar CI/CD;
- facilita futura escalabilidad horizontal;
- mejora el aislamiento entre componentes.

### Desventajas

- introduce conocimientos operativos adicionales;
- necesita administración de imágenes y versiones;
- no elimina por sí sola los puntos únicos de falla;
- contenerizar una aplicación no garantiza alta disponibilidad.

### Adecuación

Es compatible con la carga inicial del proyecto.

Puede constituir una base apropiada para evolucionar posteriormente hacia una
infraestructura más redundante.

---

## 4.3 Alternativa C – Infraestructura redundante

Esta alternativa incorpora desde el inicio mecanismos como:

- múltiples instancias del backend;
- balanceo de carga;
- redundancia de componentes;
- réplicas donde sean técnicamente apropiadas;
- mecanismos automatizados de recuperación;
- mayor separación entre componentes.

### Ventajas

- mayor tolerancia a fallos;
- mejores posibilidades de continuidad del servicio;
- mayor capacidad de escalabilidad horizontal;
- reducción de algunos puntos únicos de falla.

### Desventajas

- mayor costo;
- mayor complejidad;
- mayor esfuerzo de monitoreo;
- mayor cantidad de componentes operativos;
- necesidad de personal con mayor experiencia;
- posibilidad de sobredimensionamiento para la carga inicial.

### Adecuación

Puede resultar adecuada cuando las pruebas, la disponibilidad requerida o la
criticidad operacional demuestren que una infraestructura sencilla no es
suficiente.

No existe todavía evidencia de pruebas de carga que justifique determinar una
cantidad concreta de instancias.

---

## 4.4 Alternativa D – Evolución progresiva de infraestructura

Consiste en iniciar con una infraestructura controlada y relativamente
sencilla, pero diseñada desde el principio para permitir evolución.

La evolución puede incorporar progresivamente:

- automatización de despliegues;
- contenerización;
- separación de responsabilidades;
- monitoreo;
- centralización de logs;
- balanceo de carga;
- múltiples instancias;
- réplicas;
- mecanismos adicionales de alta disponibilidad.

La incorporación de cada mecanismo debe responder a:

- mediciones;
- pruebas de carga;
- crecimiento real;
- requisitos operativos;
- análisis de riesgo.

### Ventajas

- evita sobredimensionamiento inicial;
- reduce complejidad innecesaria;
- permite mantener costos controlados;
- facilita crecimiento gradual;
- mantiene abierta la evolución hacia el escenario 10x;
- permite tomar decisiones basadas en evidencia.

### Desventajas

- requiere disciplina arquitectónica desde el inicio;
- necesita monitoreo para detectar cuándo evolucionar;
- obliga a documentar criterios de escalamiento;
- las migraciones futuras deben planificarse adecuadamente.

### Adecuación

Esta alternativa presenta buena compatibilidad con el carácter académico del
proyecto y con el perfil de carga actual.

Permite satisfacer las necesidades iniciales sin imponer prematuramente una
infraestructura de alta complejidad.

---

# 5. Comparación de alternativas

| Criterio | A. Tradicional simple | B. Contenerizada simple | C. Redundante | D. Evolución progresiva |
|---|---|---|---|---|
| Complejidad inicial | Baja | Baja-Media | Alta | Baja-Media |
| Costo inicial | Bajo | Bajo-Medio | Alto | Bajo-Medio |
| Automatización | Limitada | Buena | Alta | Progresiva |
| Escalabilidad | Limitada | Buena base | Alta | Alta evolución |
| Tolerancia a fallos | Baja | Depende del despliegue | Alta | Progresiva |
| Mantenibilidad | Media | Buena | Buena pero compleja | Buena |
| Adecuación carga inicial | Buena | Buena | Puede ser excesiva | Buena |
| Adecuación escenario 10x | Limitada | Posible evolución | Buena | Buena evolución |
| Complejidad operativa | Baja | Media | Alta | Controlada |
| Riesgo de sobredimensionamiento | Bajo | Bajo | Alto | Bajo |

La comparación no constituye una demostración de cumplimiento de RNF.

Las decisiones definitivas deberán apoyarse posteriormente en pruebas y
evidencia operacional.

---

# 6. Ambientes y configuración

Se recomienda disponer como mínimo de tres ambientes lógicamente separados:

## 6.1 Desarrollo

Destinado al trabajo diario de desarrollo.

Debe permitir:

- desarrollo local;
- pruebas unitarias;
- validaciones básicas;
- utilización de configuraciones no productivas.

No deben almacenarse secretos reales de producción en este ambiente.

---

## 6.2 Pruebas

Debe aproximarse razonablemente a las características de producción.

Debe permitir:

- pruebas de integración;
- pruebas funcionales;
- pruebas de seguridad;
- pruebas de rendimiento;
- pruebas de concurrencia;
- pruebas de recuperación;
- validación previa al despliegue.

Cuando se necesiten datos, deberán utilizarse datos sintéticos o datos
adecuadamente protegidos.

---

## 6.3 Producción

Debe contener únicamente componentes y configuraciones aprobadas.

El acceso debe limitarse según responsabilidades.

Los cambios deben llegar mediante un proceso controlado de despliegue y no
mediante modificaciones manuales no registradas.

---

## 6.4 Configuración por ambiente

La configuración debe externalizarse del código de aplicación.

Debe diferenciarse, entre otros:

- cadenas de conexión;
- endpoints;
- credenciales;
- parámetros de monitoreo;
- configuración del proveedor externo;
- configuración de sesiones;
- niveles de logging.

Los secretos nunca deben almacenarse directamente en el repositorio.

---

# 7. CI/CD

Se recomienda establecer una estrategia de integración y entrega continua.

No se selecciona todavía una herramienta específica.

Un flujo mínimo debería contemplar:

1. recepción del cambio en el repositorio;
2. validación del código;
3. ejecución de pruebas automatizadas;
4. análisis de calidad;
5. verificaciones básicas de seguridad;
6. construcción del artefacto;
7. versionado;
8. despliegue en ambiente de pruebas;
9. pruebas posteriores al despliegue;
10. aprobación para producción;
11. despliegue controlado;
12. verificación posterior;
13. posibilidad de rollback.

Los mismos artefactos validados deberían promoverse entre ambientes cuando
sea posible, evitando reconstrucciones inconsistentes.

---

## 7.1 Rollback

Debe existir un procedimiento para regresar a una versión estable cuando un
despliegue produzca errores.

Debe considerarse especialmente la compatibilidad entre:

- versión de aplicación;
- esquema de datos;
- configuración;
- integraciones externas.

El rollback de aplicación no necesariamente implica un rollback automático de
datos.

---

## 7.2 Cambios de base de datos

Las modificaciones de esquema deberán:

- estar versionadas;
- ser revisables;
- ejecutarse de forma controlada;
- considerar compatibilidad;
- disponer de estrategia de recuperación.

---

# 8. Disponibilidad y tolerancia a fallos

El objetivo de disponibilidad definido es:

**>= 99 % mensual.**

Para acercarse a este objetivo deben identificarse los posibles puntos únicos
de falla.

Entre ellos pueden encontrarse:

- servidor de aplicación;
- servicio de persistencia;
- almacenamiento;
- conectividad;
- DNS;
- certificados;
- servicio de autenticación;
- servicio externo de atención virtual;
- mecanismos de gestión de secretos.

Una infraestructura simple puede resultar compatible con el objetivo de
disponibilidad, pero solamente las mediciones operativas podrán demostrarlo.

La redundancia debe introducirse cuando sea necesaria para cumplir los
objetivos definidos y no únicamente por tendencia tecnológica.

---

## 8.1 Fallo de una instancia de aplicación

Si en el futuro existen múltiples instancias, el sistema debería permitir
retirar temporalmente una instancia defectuosa sin afectar innecesariamente
al resto del servicio.

Para ello el backend debe evitar depender innecesariamente de estado local.

---

## 8.2 Fallo del servicio externo

Una falla del proveedor de atención virtual debe tratarse como una degradación
parcial del sistema.

El módulo local de gestión de citas puede continuar disponible cuando sea
técnicamente posible.

La falla externa no debe provocar automáticamente modificaciones incorrectas
sobre la cita.

---

# 9. Respaldo y recuperación

La estrategia de respaldo deberá diseñarse teniendo en cuenta el RPO máximo de
60 minutos.

Por tanto, no es suficiente afirmar que existe un respaldo periódico.

Debe existir una estrategia capaz de limitar la pérdida potencial de datos al
intervalo definido.

Dependiendo de la tecnología de persistencia finalmente seleccionada podrán
considerarse mecanismos como:

- respaldos completos;
- respaldos incrementales;
- snapshots;
- registro continuo de cambios;
- mecanismos equivalentes de recuperación a un punto en el tiempo.

La tecnología específica queda pendiente.

---

## 9.1 Principios de respaldo

Los respaldos deberán:

- estar protegidos contra acceso no autorizado;
- disponer de copia separada del entorno principal;
- ser verificables;
- tener procedimientos de restauración;
- ser monitoreados;
- registrar fallos de ejecución.

---

## 9.2 Restauración

La existencia de una copia no garantiza que pueda restaurarse correctamente.

Deben realizarse pruebas periódicas que permitan responder:

- ¿el respaldo está íntegro?
- ¿puede restaurarse?
- ¿cuánto tarda la restauración?
- ¿qué componentes son necesarios?
- ¿se alcanza el RTO?
- ¿se conserva la consistencia de los datos?

---

## 9.3 Retención de respaldos

La retención de respaldos debe definirse independientemente de la retención
funcional de información.

La decisión D-20 establece una conservación mínima académica de cinco años
para determinados datos históricos y de auditoría.

Esto no significa necesariamente conservar todas las copias de respaldo
durante cinco años.

La política exacta de retención de backups permanece pendiente de definición.

---

# 10. RPO y RTO

## 10.1 RPO

Objetivo:

**RPO <= 60 minutos.**

Estado:

**REQUIERE IMPLEMENTACIÓN Y VALIDACIÓN.**

La estrategia elegida deberá demostrar mediante pruebas que la pérdida
máxima de datos ante un evento grave permanece dentro del objetivo.

---

## 10.2 RTO

Objetivo:

**RTO <= 120 minutos.**

Estado:

**REQUIERE IMPLEMENTACIÓN Y VALIDACIÓN.**

La recuperación deberá considerar como mínimo:

- infraestructura;
- aplicación;
- configuración;
- persistencia;
- secretos;
- conectividad;
- validación funcional posterior.

---

## 10.3 Pruebas de recuperación

Se recomienda ejecutar ejercicios controlados de recuperación.

Estos deben registrar:

- fecha;
- escenario probado;
- punto de recuperación;
- duración;
- resultado;
- problemas detectados;
- acciones correctivas.

Hasta disponer de estas evidencias no se debe afirmar que RNF-09 o RNF-10
están cumplidos.

---

# 11. Monitoreo, logs y alertas

## 11.1 Monitoreo

El sistema debería registrar métricas que permitan conocer su estado
operativo.

Como mínimo deberían considerarse:

- disponibilidad;
- tiempo de respuesta;
- utilización de CPU;
- utilización de memoria;
- utilización de almacenamiento;
- conexiones;
- tasa de solicitudes;
- tasa de errores;
- saturación;
- estado de integraciones externas.

---

## 11.2 Métricas de negocio operativas

Cuando corresponda, pueden monitorearse indicadores técnicos asociados a
operaciones críticas, por ejemplo:

- intentos de reserva;
- reservas rechazadas por concurrencia;
- fallos de confirmación;
- errores en cambios de estado;
- fallos de integración con atención virtual.

Estas métricas no deben reemplazar a la auditoría funcional.

---

## 11.3 Logs

Los logs deben permitir investigar incidentes sin exponer innecesariamente
información sensible.

Deben evitar registrar:

- contraseñas;
- secretos;
- tokens completos;
- credenciales;
- información sensible innecesaria.

Se recomienda que los logs incluyan información como:

- fecha y hora;
- componente;
- nivel;
- identificador de operación;
- resultado;
- código de error.

---

## 11.4 Centralización

En producción resulta conveniente disponer de centralización de logs para
facilitar:

- búsqueda;
- correlación;
- investigación;
- alertas;
- análisis de incidentes.

La herramienta concreta permanece pendiente.

---

## 11.5 Alertas

Las alertas deberán definirse sobre eventos que requieran intervención.

Ejemplos:

- servicio no disponible;
- alta tasa de errores;
- uso crítico de almacenamiento;
- fallos de backups;
- fallos de integración externa;
- certificados próximos a vencer;
- degradación significativa de tiempos de respuesta.

Los umbrales exactos deben ajustarse mediante observación y pruebas.

---

# 12. Seguridad operacional

El análisis de infraestructura debe mantener coherencia con
`05_security_analysis.md`.

---

## 12.1 Gestión de secretos

Los secretos no deben:

- almacenarse en código;
- registrarse en Git;
- incluirse en imágenes de despliegue;
- escribirse en logs.

Debe establecerse un mecanismo seguro para:

- almacenar secretos;
- distribuirlos;
- rotarlos;
- revocarlos;
- auditar su utilización.

La herramienta específica permanece pendiente.

---

## 12.2 TLS

Las comunicaciones que transporten información sensible deben protegerse
durante el tránsito.

La operación debe contemplar:

- emisión de certificados;
- renovación;
- expiración;
- revocación cuando corresponda;
- monitoreo de vigencia.

---

## 12.3 Principio de mínimo privilegio

Cada componente debe recibir únicamente los permisos necesarios.

Esto aplica a:

- aplicación;
- base de datos;
- pipeline;
- operadores;
- respaldos;
- monitoreo;
- servicios externos.

---

## 12.4 Actualizaciones

Debe existir un proceso para mantener actualizados:

- sistema operativo;
- runtime;
- librerías;
- imágenes;
- componentes de infraestructura.

Las actualizaciones deben evaluarse antes de producción.

---

## 12.5 Protección de respaldos

Los respaldos contienen información potencialmente sensible.

Deben aplicar controles de:

- confidencialidad;
- integridad;
- acceso;
- cifrado cuando corresponda;
- trazabilidad.

---

# 13. Escalabilidad

El sistema debe permitir evolucionar desde la carga inicial hacia el escenario
futuro de 5.000 transacciones por hora.

---

## 13.1 Escalabilidad vertical

En las primeras etapas puede resultar suficiente aumentar recursos del entorno
existente cuando las mediciones demuestren saturación.

Esto puede incluir incremento de:

- CPU;
- memoria;
- capacidad de almacenamiento;
- recursos de persistencia.

La escalabilidad vertical tiene límites físicos y económicos.

---

## 13.2 Escalabilidad horizontal

El backend debería diseñarse de forma que pueda permitir múltiples instancias
cuando las pruebas lo hagan necesario.

Para facilitar esta evolución se recomienda reducir dependencias de estado
local.

Cuando existan varias instancias podrá utilizarse un mecanismo de distribución
de solicitudes.

No se define todavía una cantidad de instancias.

La cantidad deberá determinarse mediante:

- pruebas de carga;
- consumo de recursos;
- objetivos de disponibilidad;
- costos;
- métricas reales.

---

## 13.3 Persistencia

La estrategia de persistencia deberá conservar consistencia en las operaciones
críticas.

Las técnicas de escalamiento pueden incluir posteriormente:

- optimización de consultas;
- índices;
- separación de cargas;
- réplicas de lectura cuando sean apropiadas;
- particionamiento si el volumen futuro lo justifica.

La estrategia definitiva depende de la tecnología de persistencia seleccionada
posteriormente.

---

## 13.4 Escenario 10x

El crecimiento hasta 5.000 transacciones por hora debe interpretarse como una
meta de evolución.

No se recomienda adquirir o desplegar desde el primer día toda la capacidad
correspondiente a dicho escenario sin evidencia de necesidad.

La infraestructura debe ser escalable, no necesariamente estar
sobredimensionada.

---

## 13.5 Kubernetes

Kubernetes no se considera obligatorio para la primera versión.

El perfil inicial presenta:

- hasta 100 usuarios concurrentes;
- 500 transacciones/hora normales;
- 1.000 transacciones/hora de pico inmediato.

Para esta escala, introducir una plataforma de orquestación compleja podría
aumentar:

- esfuerzo operativo;
- curva de aprendizaje;
- cantidad de componentes;
- mantenimiento;
- costo;
- superficie de configuración.

Por tanto:

**Estado:** NO JUSTIFICADO COMO REQUISITO INICIAL.

Esto no significa que Kubernetes quede descartado permanentemente.

Podría evaluarse en una fase posterior cuando exista evidencia como:

- gran cantidad de servicios independientes;
- múltiples equipos de desarrollo;
- necesidad significativa de escalamiento automático;
- gran número de instancias;
- despliegues muy frecuentes;
- alta necesidad de auto-recuperación;
- operación distribuida de mayor complejidad.

La decisión debe basarse en necesidades reales y no en preferencia
tecnológica.

---

# 14. Integración con servicios externos

De acuerdo con D-01, el servicio de atención virtual debe estar desacoplado
mediante una abstracción o adaptador.

Esto evita que las reglas centrales del sistema dependan directamente de un
proveedor.

---

## 14.1 Fallas externas

La plataforma deberá diferenciar entre:

- falla del sistema local;
- falla del servicio externo;
- falla de comunicación;
- timeout;
- respuesta inválida;
- indisponibilidad temporal.

---

## 14.2 Reintentos

Si posteriormente se implementan reintentos automáticos deberán realizarse
con cuidado.

Las operaciones con efectos secundarios deberán considerar mecanismos de
idempotencia o equivalentes para evitar ejecuciones duplicadas.

Los valores de:

- timeout;
- número de reintentos;
- intervalos;
- circuit breaker;

quedan pendientes de decisión técnica.

---

## 14.3 Contingencia

De acuerdo con D-19:

Ante una falla del servicio virtual:

1. la cita debe conservarse;
2. no debe eliminarse automáticamente;
3. no debe cancelarse automáticamente;
4. no debe marcarse automáticamente como inasistencia;
5. no debe cambiar de modalidad automáticamente;
6. debe mostrarse un mensaje de incidente;
7. debe conservarse la posibilidad de continuar con la misma cita cuando el
   servicio se recupere.

Cuando no sea posible continuar, los usuarios autorizados podrán aplicar las
acciones permitidas por las reglas del proyecto, tales como:

- reprogramar;
- cambiar a modalidad presencial cuando se encuentre habilitada y exista
  consentimiento;
- aplicar una resolución manual autorizada.

Las modificaciones deben quedar registradas cuando corresponda.

---

# 15. Riesgos y hallazgos

## DEVOPS-01 – Recuperación no demostrada

**Severidad:** BLOCKER para salida a producción

**Relacionado con:** RNF-09, RNF-10, D-22

**Descripción:**  
Actualmente existen objetivos de RPO y RTO, pero no existe evidencia de una
prueba real de recuperación.

**Impacto:**  
No puede garantizarse que el sistema pueda recuperar la información y volver
a operar dentro de los tiempos definidos.

**Recomendación:**  
Implementar estrategia de respaldo y ejecutar pruebas documentadas de
restauración antes de considerar los RNF como cumplidos.

**Estado:** REQUIERE IMPLEMENTACIÓN Y PRUEBA.

---

## DEVOPS-02 – Punto único de falla

**Severidad:** HIGH

**Relacionado con:** RNF-08, D-22

**Descripción:**  
Una infraestructura inicial basada completamente en un único host podría
convertir ese host en punto único de falla.

**Impacto:**  
Una falla física, lógica o de conectividad podría interrumpir completamente
el servicio.

**Recomendación:**  
Medir disponibilidad y evaluar redundancia cuando los resultados demuestren
que es necesaria.

**Estado:** PENDIENTE DE DISEÑO DE INFRAESTRUCTURA.

---

## DEVOPS-03 – Estrategia de backup pendiente

**Severidad:** HIGH

**Relacionado con:** RNF-09

**Descripción:**  
El RPO de 60 minutos requiere una estrategia de protección de datos compatible
con dicho intervalo.

**Impacto:**  
Un esquema de respaldo demasiado espaciado podría provocar pérdida de datos
superior al objetivo.

**Recomendación:**  
Seleccionar un mecanismo de backup o recuperación a punto en el tiempo
compatible con el RPO y posteriormente validarlo.

**Estado:** REQUIERE DECISIÓN TÉCNICA.

---

## DEVOPS-04 – Gestión de secretos pendiente

**Severidad:** HIGH

**Relacionado con:** requisitos de seguridad

**Descripción:**  
La herramienta concreta para gestionar credenciales y secretos aún no está
seleccionada.

**Impacto:**  
Una administración inadecuada puede exponer credenciales de base de datos,
servicios o integraciones.

**Recomendación:**  
Implementar almacenamiento seguro, rotación y control de acceso.

**Estado:** PENDIENTE.

---

## DEVOPS-05 – Dependencia del servicio virtual externo

**Severidad:** HIGH

**Relacionado con:** D-01, D-19

**Descripción:**  
La modalidad virtual depende de un proveedor externo todavía no seleccionado.

**Impacto:**  
Una interrupción externa podría impedir la atención virtual.

**Recomendación:**  
Mantener el proveedor desacoplado y aplicar la estrategia de contingencia
definida en D-19.

**Estado:** REQUIERE IMPLEMENTACIÓN.

---

## DEVOPS-06 – Rollback no implementado

**Severidad:** HIGH

**Relacionado con:** CI/CD y disponibilidad

**Descripción:**  
Todavía no existe evidencia de un mecanismo probado para regresar a una
versión estable después de un despliegue defectuoso.

**Impacto:**  
Un cambio incorrecto podría prolongar una indisponibilidad.

**Recomendación:**  
Definir y probar el procedimiento de rollback.

**Estado:** REQUIERE IMPLEMENTACIÓN.

---

## DEVOPS-07 – Umbrales de alertas pendientes

**Severidad:** MEDIUM

**Relacionado con:** RNF-17

**Descripción:**  
Se identifican métricas necesarias, pero todavía no existen umbrales
operativos validados.

**Impacto:**  
Las alertas podrían ser insuficientes o generar exceso de notificaciones.

**Recomendación:**  
Ajustar umbrales utilizando métricas de pruebas y operación.

**Estado:** REQUIERE VALIDACIÓN.

---

## DEVOPS-08 – Criterios de escalamiento pendientes

**Severidad:** MEDIUM

**Relacionado con:** RNF-03, D-18

**Descripción:**  
Se conoce el escenario futuro 10x, pero todavía no existen umbrales concretos
que indiquen cuándo escalar.

**Impacto:**  
El escalamiento podría ejecutarse demasiado temprano o demasiado tarde.

**Recomendación:**  
Definir criterios basados en carga, latencia, saturación y crecimiento real.

**Estado:** PENDIENTE.

---

## DEVOPS-09 – Separación de ambientes

**Severidad:** MEDIUM

**Relacionado con:** seguridad y mantenibilidad

**Descripción:**  
La separación conceptual entre desarrollo, pruebas y producción debe
implementarse técnicamente.

**Impacto:**  
Una configuración incorrecta puede mezclar credenciales, información o
servicios.

**Recomendación:**  
Establecer configuraciones y permisos independientes.

**Estado:** REQUIERE IMPLEMENTACIÓN.

---

## DEVOPS-10 – Retención de cinco años

**Severidad:** MEDIUM

**Relacionado con:** D-20

**Descripción:**  
La conservación académica de datos durante cinco años tendrá impacto sobre
almacenamiento, respaldo y mantenimiento.

**Impacto:**  
El volumen acumulado podría incrementar costos y tiempos operativos.

**Recomendación:**  
Medir crecimiento y definir estrategia de almacenamiento y archivado.

**Estado:** REQUIERE VALIDACIÓN.

---

## DEVOPS-11 – Gestión del ciclo de vida de certificados

**Severidad:** MEDIUM

**Relacionado con:** seguridad operacional

**Descripción:**  
La expiración de un certificado podría interrumpir comunicaciones seguras.

**Impacto:**  
Indisponibilidad o fallos de integración.

**Recomendación:**  
Automatizar o monitorear expiraciones y renovaciones.

**Estado:** REQUIERE IMPLEMENTACIÓN.

---

## DEVOPS-12 – Complejidad prematura de orquestación

**Severidad:** LOW

**Relacionado con:** mantenibilidad y costo

**Descripción:**  
Introducir una plataforma de orquestación compleja sin necesidad demostrada
podría aumentar innecesariamente la dificultad operativa.

**Impacto:**  
Mayor mantenimiento y mayor probabilidad de errores de configuración.

**Recomendación:**  
Mantener una estrategia progresiva y evaluar orquestación avanzada solo
cuando exista evidencia que la justifique.

**Estado:** CONTROLABLE MEDIANTE DECISIÓN ARQUITECTÓNICA.

---

# 16. Decisiones pendientes

Permanecen pendientes, entre otras, las siguientes decisiones:

1. proveedor de infraestructura;
2. infraestructura local, cloud o híbrida;
3. tecnología específica de contenerización;
4. necesidad futura de un orquestador;
5. plataforma de CI/CD;
6. mecanismo de gestión de secretos;
7. plataforma de monitoreo;
8. plataforma de centralización de logs;
9. mecanismo concreto de backup;
10. mecanismo de recuperación a punto en el tiempo;
11. topología definitiva de producción;
12. necesidad y tipo de balanceo;
13. estrategia exacta de redundancia;
14. utilización futura de réplicas;
15. proveedor de atención virtual;
16. timeouts del servicio externo;
17. estrategia de reintentos;
18. política de circuit breaker;
19. política específica de retención de backups;
20. estrategia de recuperación ante desastre;
21. herramienta para pruebas de carga;
22. umbrales de escalamiento;
23. tecnología definitiva de persistencia.

Estas decisiones no deben resolverse únicamente por preferencia tecnológica.

---

# 17. Recomendación provisional

Con la información actualmente disponible se recomienda adoptar una
**estrategia de infraestructura evolutiva y progresiva**, equivalente a la
Alternativa D.

La primera versión debería priorizar:

- separación de ambientes;
- configuración externalizada;
- gestión segura de secretos;
- automatización de CI/CD;
- despliegues reproducibles;
- backend preparado para no depender innecesariamente de estado local;
- monitoreo;
- logs;
- alertas;
- backups compatibles con el objetivo de RPO;
- procedimientos documentados de recuperación;
- integración del servicio virtual mediante adaptador;
- capacidad de evolucionar horizontalmente.

La primera versión no necesita asumir desde el inicio:

- una gran infraestructura distribuida;
- una cantidad fija de múltiples servidores;
- Kubernetes;
- microservicios;
- infraestructura dimensionada permanentemente para 5.000
  transacciones/hora.

Posteriormente, mediante pruebas y métricas, podrán incorporarse:

- balanceo de carga;
- múltiples instancias;
- mecanismos adicionales de redundancia;
- réplicas;
- escalamiento horizontal;
- técnicas de optimización de persistencia;
- orquestación avanzada cuando exista justificación.

La selección de un proveedor o herramienta concreta debe realizarse después
de consolidar la arquitectura y comparar las alternativas tecnológicas
correspondientes.

---

# 18. Conclusión

El análisis DevOps determina que los objetivos operativos del sistema son
viables conceptualmente, pero todavía requieren implementación y validación.

No existe evidencia suficiente para declarar actualmente como cumplidos:

- el tiempo de respuesta de RNF-01;
- la disponibilidad de RNF-08;
- el RPO de RNF-09;
- el RTO de RNF-10.

Estos requisitos deberán comprobarse mediante pruebas y operación controlada.

La carga inicial del sistema no justifica por sí sola una infraestructura de
alta complejidad.

Al mismo tiempo, la arquitectura debe evitar decisiones que impidan crecer
posteriormente desde:

- 500 transacciones/hora normales;
- 1.000 transacciones/hora de pico;

hasta:

- 5.000 transacciones/hora como escenario futuro.

Por ello se propone una evolución gradual de infraestructura basada en
mediciones.

Kubernetes no se considera necesario como requisito inicial y solamente
debería reconsiderarse si la complejidad, cantidad de servicios, volumen de
instancias, frecuencia de despliegues o necesidades operativas futuras lo
justifican.

La disponibilidad, RPO y RTO deben tratarse como objetivos verificables y no
como propiedades asumidas del diseño.

La estrategia de respaldo debe permitir alcanzar un RPO máximo de 60 minutos
y su restauración debe ser validada para comprobar el RTO máximo de
120 minutos.

La integración con el servicio de atención virtual debe permanecer desacoplada
mediante el mecanismo definido en D-01 y respetar estrictamente la
contingencia definida en D-19.

La recomendación final de infraestructura debe realizarse después de la
revisión arquitectónica integral del proyecto y de la consolidación de las
decisiones pendientes.

**Estado final del análisis:** APTO PARA PASAR A REVISIÓN ARQUITECTÓNICA,
manteniendo como pendientes las validaciones técnicas y operativas indicadas
en este documento.