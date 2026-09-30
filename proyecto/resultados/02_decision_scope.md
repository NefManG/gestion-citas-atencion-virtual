# Alcance de Decisiones — Sistema de Gestión de Citas y Atención Virtual

## 1. Objetivo

El presente documento identifica y mantiene las decisiones que deben resolverse antes de avanzar hacia la definición definitiva de arquitectura, persistencia e implementación del Sistema de Gestión de Citas y Atención Virtual.

El alcance se obtiene a partir de:

- los requerimientos funcionales;
- los requerimientos no funcionales;
- las reglas de negocio;
- las restricciones;
- las decisiones pendientes;
- el análisis de requerimientos realizado;
- la validación humana de las observaciones detectadas;
- el análisis de alternativas arquitectónicas;
- la revisión arquitectónica adversarial.

Las decisiones descritas en este documento mantienen trazabilidad con las observaciones obtenidas durante la etapa de `requirements-analysis` y con los hallazgos encontrados durante la revisión de arquitectura.

En esta etapa todavía no se selecciona de forma definitiva:

- lenguaje de programación;
- framework frontend;
- framework backend;
- motor de base de datos;
- proveedor de nube;
- tecnología específica de autenticación;
- mecanismo técnico concreto de control de concurrencia.

Las decisiones arquitectónicas se establecen únicamente hasta el nivel necesario para poder continuar con la evaluación y selección de una arquitectura.

---

## 2. Decisiones que deben tomarse

### D-01 — Servicio externo de atención virtual

**Origen del análisis:** OBS-01, OBS-33, ARB-02 y ARB-09.

**Requerimientos relacionados:** RF-19, RN-18, RN-19, RT-04, RT-05, DP-01.

La primera versión del sistema utilizará un servicio externo para las citas virtuales.

La arquitectura no dependerá directamente de un proveedor específico.

La integración deberá realizarse mediante un componente o adaptador que permita desacoplar el núcleo del sistema del proveedor de atención virtual.

El sistema deberá manejar únicamente la información necesaria para gestionar la cita virtual, como:

- identificador de la cita;
- identificador externo de la sesión cuando corresponda;
- información o enlace de acceso;
- estado de disponibilidad de la integración;
- información necesaria para paciente y médico.

La selección del proveedor específico podrá realizarse posteriormente sin modificar la lógica principal del sistema.

También deberá existir la posibilidad de sustituir el proveedor en el futuro.

**Estado: Resuelta a nivel arquitectónico.**

**Pendiente técnico:** seleccionar el proveedor externo y definir los detalles específicos de integración con su API.

---

### D-02 — Políticas de cancelación y reprogramación

**Origen del análisis:** OBS-02.

**Requerimientos relacionados:** RF-11, RF-12, RN-11, RN-12, RN-26, DP-05.

Debe definirse el tiempo mínimo de anticipación permitido para que un paciente o el personal autorizado pueda cancelar o reprogramar una cita.

Esta decisión deberá establecer claramente:

- tiempo mínimo para cancelar;
- tiempo mínimo para reprogramar;
- comportamiento cuando se intente realizar la operación fuera del tiempo permitido.

**Estado: Pendiente.**

---

### D-03 — Tiempo de tolerancia para no asistencia

**Origen del análisis:** OBS-02.

**Requerimientos relacionados:** RF-13, RN-21, DP-06.

Debe definirse cuánto tiempo puede transcurrir desde la hora programada de una cita antes de considerarla como "No asistida".

Debe aclararse también qué actor estará autorizado para realizar este cambio de estado.

**Estado: Pendiente.**

---

### D-04 — Anticipación máxima para reservar una cita

**Origen del análisis:** OBS-06.

**Requerimientos relacionados:** RF-08, RN-11, DP-08.

Debe establecerse hasta qué fecha futura podrá un paciente consultar y reservar horarios disponibles.

La decisión debe definir una ventana máxima de reserva expresada en días, semanas o meses.

**Estado: Pendiente.**

---

### D-05 — Inicio del estado "En atención"

**Origen del análisis:** OBS-04, OBS-07, ARB-07 y ARB-13.

**Requerimientos relacionados:** RF-13, RF-14, RF-16, RF-20, RN-20 y criterios de aceptación asociados.

Se establece que el inicio del estado **"En atención"** será registrado por el médico responsable de la cita.

Cuando el médico registre el inicio de la atención, se realizará la transición:

**Confirmada → En atención**

El sistema deberá almacenar la fecha y hora de inicio de la atención.

Cuando el médico registre la finalización de la atención, se realizará la transición:

**En atención → Finalizada**

El sistema deberá almacenar la fecha y hora de finalización.

Las citas que se encuentren en estado **"En atención"** deberán continuar visibles:

- para el paciente dentro de sus citas vigentes;
- para el médico dentro de su agenda.

De esta manera, el estado "En atención" no quedará fuera de las consultas mientras la atención se encuentre en curso.

**Estado: Resuelta.**

---

### D-06 — Relación entre Médico y Usuario

**Origen del análisis:** OBS-08, OBS-11 y ARB-05.

**Requerimientos relacionados:** RF-02, RF-21, RN-17 y criterios de aceptación correspondientes.

Se establece que **Médico** y **Usuario** representan conceptos diferentes dentro del sistema.

La entidad **Médico** representa la información profesional necesaria para gestionar:

- especialidades;
- horarios;
- disponibilidad;
- citas;
- agenda;
- atención médica.

La entidad **Usuario** representa la identidad utilizada para:

- autenticación;
- autorización;
- roles;
- acceso al sistema;
- auditoría.

Un médico podrá ser registrado inicialmente sin disponer todavía de una cuenta de usuario.

Cuando el médico necesite ingresar al sistema para consultar su agenda, iniciar una atención o registrar información autorizada, deberá existir una cuenta de usuario asociada con el rol Médico.

Se establece que:

- un Médico podrá tener cero o una cuenta de Usuario asociada;
- una cuenta de Usuario con rol Médico deberá estar asociada a un único Médico para poder realizar funciones clínicas;
- la existencia del registro profesional del Médico no dependerá de que posea una cuenta de acceso.

La desactivación de una cuenta de Usuario no eliminará ni desactivará automáticamente el registro profesional del Médico.

La desactivación del Médico tampoco eliminará automáticamente su Usuario ni su información histórica.

El comportamiento de las citas futuras cuando un médico sea desactivado será definido mediante D-07.

**Estado: Resuelta.**

---

### D-07 — Desactivación de médicos con citas futuras

**Origen del análisis:** OBS-18 y observaciones de riesgo relacionadas con la desactivación de médicos.

**Requerimientos relacionados:** RF-02, RF-05, RN-02 y reglas relacionadas con citas futuras.

Debe definirse el comportamiento del sistema cuando un médico es marcado como inactivo y todavía posee citas futuras programadas o confirmadas.

Las alternativas a evaluar son:

- impedir la desactivación hasta resolver las citas;
- permitir la desactivación y mantener las citas;
- solicitar previamente la reprogramación o cancelación de las citas afectadas.

No se selecciona todavía una alternativa definitiva.

**Estado: Pendiente.**

---

### D-08 — Habilitación de modalidad virtual

**Origen del análisis:** OBS-05.

**Requerimientos relacionados:** RF-05, RF-18, RN-18, RN-19, RO-06.

Debe definirse quién puede habilitar o deshabilitar la atención virtual.

También deberá determinarse si la modalidad se configura:

- por médico;
- por especialidad;
- por relación médico-especialidad;
- por bloque horario;
- mediante una combinación de los anteriores.

**Estado: Pendiente.**

---

### D-09 — Modalidad asociada a los horarios

**Origen del análisis:** OBS-22.

**Requerimientos relacionados:** RF-05, RF-07, RF-08, RF-18, RN-18, RO-06.

Debe definirse si un mismo médico puede tener:

- horarios exclusivamente presenciales;
- horarios exclusivamente virtuales;
- horarios de ambas modalidades.

También deberá determinarse si la modalidad estará asociada directamente al bloque horario específico.

**Estado: Pendiente.**

---

### D-10 — Permisos del personal de admisión

**Origen del análisis:** observación de riesgo relacionada con los permisos del personal de admisión.

**Requerimientos relacionados:** RF-10, RF-11, RF-12, RN-16, RO-02.

Debe especificarse qué operaciones puede realizar el personal de admisión o recepción.

Como mínimo se debe aclarar el alcance de sus permisos para:

- registrar citas en representación de pacientes;
- reprogramar citas;
- cancelar citas;
- consultar disponibilidad;
- modificar estados cuando corresponda.

Las operaciones realizadas por este rol deberán quedar sujetas a:

- autenticación;
- autorización;
- control de acceso;
- auditoría.

**Estado: Pendiente.**

---

### D-11 — Restricción de citas superpuestas

**Origen del análisis:** OBS-03 y ARB-12.

**Requerimientos relacionados:** RF-09, RF-10, RF-11, RN-05, RN-16.

Se establece que la regla que impide citas superpuestas de un paciente debe aplicarse independientemente de quién realice la operación.

Por tanto, deberá aplicarse cuando una cita sea registrada o reprogramada por:

- el propio paciente;
- el personal de admisión en representación del paciente.

La lógica de negocio deberá comprobar esta restricción antes de confirmar la operación.

El personal de admisión no podrá generar para un paciente una cita que se superponga con otra cita activa del mismo paciente.

El mecanismo técnico específico utilizado para garantizar esta condición será definido posteriormente.

**Estado: Resuelta a nivel funcional.**

---

### D-12 — Información mínima de auditoría

**Origen del análisis:** OBS-12, OBS-26, ARB-06 y ARB-15.

**Requerimientos relacionados:** RF-22, RNF-17, RNF-18, RN-23, RN-24, RN-25.

Se establece como información mínima del historial de modificaciones:

- fecha;
- hora;
- usuario responsable;
- acción realizada;
- valor anterior;
- valor actualizado.

RF-22 se utilizará como referencia principal para determinar el nivel mínimo de detalle.

RNF-18 deberá mantener los mismos datos mínimos establecidos en RF-22.

La política de conservación de esta información queda definida mediante D-20.

Los datos mínimos establecidos deberán mantenerse independientemente del rol que realice la modificación.

**Estado: Resuelta.**

---

### D-13 — Tiempo máximo de inactividad de sesión

**Origen del análisis:** OBS-10, OBS-15 y ARB-14.

**Requerimientos relacionados:** RNF-07, DP-07.

Para el alcance académico del proyecto se establece un tiempo máximo inicial de:

**15 minutos de inactividad.**

Cuando una sesión autenticada permanezca durante 15 minutos consecutivos sin actividad del usuario, el sistema deberá finalizar automáticamente la sesión.

El tiempo deberá ser configurable para permitir que la política pueda modificarse posteriormente sin requerir cambios en la lógica funcional del sistema.

El cierre automático deberá aplicarse a:

- paciente;
- médico;
- personal de admisión o recepción;
- administrador.

Cuando la sesión expire:

- el usuario deberá volver a autenticarse para acceder a funciones protegidas;
- no deberá permitirse continuar ejecutando operaciones con la sesión expirada;
- la finalización de la sesión no deberá eliminar información previamente confirmada;
- las operaciones que no hayan sido confirmadas podrán requerir ser iniciadas nuevamente.

No se selecciona todavía la tecnología específica utilizada para autenticación, administración de tokens, cookies o sesiones.

El período inicial de 15 minutos constituye una decisión de seguridad del proyecto académico y podrá modificarse posteriormente según una política institucional formal.

**Estado: Resuelta.**

---

### D-14 — Reglas de validación de datos

**Origen del análisis:** OBS-28, OBS-30 y revisión arquitectónica.

**Requerimientos relacionados:** RF-01, RNF-12, CA-001, CA-002.

Deben definirse reglas verificables para los principales datos registrados por el sistema.

Se deberán establecer criterios para campos como:

- documento de identidad;
- nombre y apellidos;
- teléfono;
- correo electrónico;
- fecha de nacimiento.

La definición debe indicar:

- datos obligatorios;
- datos opcionales;
- formatos aceptados;
- condiciones que determinan si un dato es válido.

**Estado: Pendiente.**

---

### D-15 — Accesibilidad

**Origen del análisis:** OBS-09, OBS-28 y ARB-10.

**Requerimientos relacionados:** RNF-15 y RNF-19.

Se establece como referencia mínima de accesibilidad para los componentes y flujos principales del sistema:

**WCAG 2.1 nivel AA.**

La interfaz deberá considerar como mínimo:

- navegación mediante teclado;
- contraste adecuado entre texto y fondo;
- etiquetas asociadas a los campos de formularios;
- textos alternativos para elementos visuales relevantes;
- mensajes de error comprensibles;
- estructura semántica de encabezados;
- identificación visible del elemento que posee el foco;
- formularios utilizables mediante tecnologías de asistencia.

La validación deberá realizarse sobre los principales flujos correspondientes a:

- pacientes;
- médicos;
- personal de admisión o recepción;
- administradores.

La selección posterior del framework frontend deberá permitir cumplir estos criterios.

Para la compatibilidad de navegadores se aplicará RNF-19, considerando las dos últimas versiones estables disponibles de Google Chrome, Microsoft Edge y Mozilla Firefox al momento de ejecutar las pruebas de aceptación.

**Estado: Resuelta.**

---

### D-16 — Prueba de usabilidad y carga del frontend

**Origen del análisis:** OBS-29 y ARB-11.

**Requerimientos relacionados:** RNF-01, RNF-13 y RNF-14.

Se establece que la usabilidad del sistema deberá evaluarse mediante una prueba controlada con usuarios representativos de los perfiles que utilizarán la aplicación.

La prueba deberá incluir como mínimo:

- consultar especialidades disponibles;
- consultar médicos disponibles;
- consultar horarios;
- seleccionar una fecha y horario;
- completar la reserva de una cita.

Para considerar cumplido RNF-14, al menos el **80 % de los participantes** deberá completar las tareas principales sin asistencia externa.

La prueba deberá documentar:

- cantidad de participantes;
- perfil de los participantes;
- dispositivo utilizado;
- navegador utilizado;
- tareas asignadas;
- resultado de cada tarea;
- necesidad o no de asistencia;
- observaciones relevantes.

La prueba deberá considerar como mínimo:

- una computadora;
- una tableta o dispositivo de tamaño equivalente;
- un teléfono móvil.

El tiempo máximo de 3 segundos definido en RNF-01 corresponde al procesamiento de las operaciones del backend bajo las condiciones establecidas en D-17.

El tiempo de carga inicial de la aplicación web y de los recursos del frontend deberá evaluarse de manera separada.

La arquitectura del frontend deberá evitar que una carga inicial excesiva impida realizar adecuadamente las tareas consideradas en las pruebas de usabilidad.

La selección del framework frontend y las técnicas concretas de optimización se realizará posteriormente.

**Estado: Resuelta a nivel de requisitos.**

**Pendiente técnico:** definir la muestra concreta de participantes, herramientas de medición y entorno definitivo de la prueba.

---

### D-17 — Condiciones de medición de rendimiento

**Origen del análisis:** OBS-32, OBS-36 y ARB-08.

**Requerimientos relacionados:** RNF-01, RNF-02, RNF-08, RNF-09, RNF-10.

Se establecen las siguientes condiciones generales para la evaluación:

- las pruebas deberán realizarse en un entorno controlado y reproducible;
- RNF-01 será evaluado considerando hasta 100 usuarios concurrentes;
- el tiempo de respuesta se medirá desde que el backend recibe una solicitud hasta que genera la respuesta;
- no se incluirá la latencia externa propia de la conexión del usuario;
- deberán probarse operaciones representativas de consulta y escritura;
- la carga normal será de 500 transacciones por hora;
- el pico inmediato será de hasta 1.000 transacciones por hora;
- el escenario futuro será de 5.000 transacciones por hora;
- la disponibilidad será evaluada mensualmente;
- los mantenimientos programados y comunicados quedarán excluidos;
- las interrupciones no programadas serán contabilizadas como indisponibilidad;
- el RPO máximo será de 60 minutos;
- el RTO máximo será de 120 minutos.

Las operaciones representativas deberán incluir:

- consulta de especialidades;
- consulta de médicos;
- consulta de horarios;
- registro de citas;
- reprogramación;
- cancelación.

La distribución exacta de operaciones, herramientas de prueba, infraestructura y escenarios técnicos deberán documentarse posteriormente.

**Estado: Resuelta a nivel de requisitos.**

**Pendiente técnico:** elaborar el plan de pruebas y seleccionar herramientas.

---

### D-18 — Escenario de crecimiento

**Origen del análisis:** OBS-16 y ARB-04.

**Requerimientos relacionados:** RNF-02, RNF-03, CA-RNF-02, CA-RNF-03 y perfil de carga.

Se aprueba:

- **500 transacciones/hora:** carga normal inicial;
- **1.000 transacciones/hora:** pico inmediato;
- **5.000 transacciones/hora:** escenario futuro equivalente a 10 veces la carga inicial.

El escenario de 5.000 transacciones por hora no significa que la primera versión deba disponer desde el primer día de toda la infraestructura requerida para soportarlo.

La arquitectura deberá permitir aumentar posteriormente la capacidad sin modificar:

- requerimientos funcionales;
- reglas principales del negocio;
- comportamiento funcional esperado.

El crecimiento podrá lograrse mediante:

- incremento de recursos;
- replicación;
- optimización;
- separación de cargas;
- otros mecanismos técnicos posteriores.

**Estado: Resuelta y aprobada.**

---

### D-19 — Contingencia ante falla del servicio virtual

**Origen del análisis:** OBS-20, OBS-33, ARB-02, ARB-09 y ARB-17.

**Requerimientos relacionados:** RF-18, RF-19, RN-18, RN-19, RT-03, RT-04, RT-05 y DP-01.

Cuando una cita virtual no pueda desarrollarse debido a una falla atribuible al servicio externo o a un problema de conexión detectado por el sistema, se deberá conservar íntegramente la información de la cita.

La falla no deberá:

- eliminar la cita;
- cancelar automáticamente la cita;
- marcar automáticamente al paciente como "No asistido";
- modificar automáticamente la modalidad.

El sistema deberá registrar el incidente y conservar como mínimo:

- identificador de la cita;
- paciente;
- médico;
- fecha y hora;
- modalidad;
- estado actual;
- fecha y hora del incidente;
- información técnica disponible sobre la falla.

Cuando el sistema detecte una falla antes o durante la atención virtual:

1. La cita mantendrá su estado actual mientras se determina la acción de contingencia.

2. El sistema deberá mostrar al usuario afectado un mensaje visible indicando que existe un problema de conexión o disponibilidad del servicio virtual.

3. Cuando paciente y médico se encuentren utilizando el sistema, ambos deberán poder conocer que se produjo el incidente.

4. Si posteriormente se incorpora un mecanismo de notificaciones externas, podrán enviarse adicionalmente avisos mediante los canales habilitados.

5. La primera versión no dependerá obligatoriamente de correo electrónico, SMS, WhatsApp u otro canal externo para poder gestionar la contingencia.

6. Si la atención puede continuar una vez restablecido el servicio, se mantendrá la misma cita.

7. Si no puede continuar, el médico o el personal de admisión autorizado podrá:

   - reprogramar hacia otro horario disponible;
   - cambiar a modalidad presencial cuando se encuentre habilitada y exista conformidad;
   - mantener la cita pendiente de resolución manual.

8. Toda reprogramación o cambio de modalidad deberá registrarse en el historial de modificaciones.

9. Una falla técnica o de conexión no podrá utilizarse por sí sola como motivo para clasificar al paciente como "No asistido".

El sistema deberá mantener esta política desacoplada del proveedor específico de atención virtual.

**Estado: Resuelta a nivel funcional y arquitectónico.**

**Pendiente técnico:** seleccionar proveedor, mecanismos de detección, reintentos y canales adicionales de notificación.

---

### D-20 — Conservación de información

**Origen del análisis:** observaciones relacionadas con retención, ARB-06 y ARB-18.

**Requerimientos relacionados:** RF-15, RF-22, RN-22, RNF-17, RNF-18, CA-039.

Para el diseño académico se establece una política inicial de conservación.

Las citas:

- finalizadas;
- canceladas;
- no asistidas;

deberán conservarse durante un período mínimo de **5 años** desde la fecha correspondiente.

Los registros de auditoría deberán conservarse durante un período mínimo de **5 años** desde la fecha del evento.

Durante ese período:

- no deberán eliminarse automáticamente;
- deberán permanecer disponibles para consulta autorizada;
- deberán mantener su relación con la cita;
- deberán conservar los campos definidos en D-12;
- cualquier archivado o eliminación posterior deberá ser controlado.

La política deberá poder configurarse para permitir que la institución amplíe el período sin modificar la lógica principal.

Una vez finalizado el período, los registros podrán:

- mantenerse;
- archivarse;
- eliminarse mediante procedimiento autorizado;

según la política institucional aplicable.

**Estado: Resuelta para el alcance académico del proyecto.**

**Nota:** Los 5 años son una decisión del proyecto académico y deberán validarse o sustituirse por una política institucional formal antes de una implementación real.

---

### D-21 — Control de concurrencia en la reserva de citas

**Origen del análisis:** ARB-01.

**Requerimientos relacionados:** RF-09, RNF-01, RNF-11, RN-04, RN-06, CA-012.

La confirmación de una cita deberá realizarse como una operación atómica.

Antes de confirmar una reserva, se deberá comprobar nuevamente que el horario continúa disponible.

Cuando dos o más usuarios intenten reservar simultáneamente el mismo horario:

- solo una operación podrá finalizar correctamente;
- las demás deberán ser rechazadas indicando que el horario ya no está disponible;
- nunca deberán confirmarse dos citas incompatibles para el mismo médico, fecha y horario.

La validación final y la confirmación deberán ejecutarse dentro de la misma operación transaccional.

El mecanismo específico podrá utilizar posteriormente:

- bloqueo de fila;
- control optimista;
- restricciones únicas;
- nivel de aislamiento;
- mecanismo equivalente.

**Estado: Resuelta a nivel arquitectónico.**

**Pendiente técnico:** seleccionar y validar el mecanismo durante el diseño de persistencia.

---

### D-22 — Disponibilidad, respaldo y recuperación

**Origen del análisis:** ARB-03.

**Requerimientos relacionados:** RNF-08, RNF-09 y RNF-10.

La arquitectura deberá permitir:

- disponibilidad mensual igual o superior al 99 %;
- RPO máximo de 60 minutos;
- RTO máximo de 120 minutos.

La solución productiva no deberá depender exclusivamente de un único punto de fallo sin estrategia de recuperación.

Como mínimo deberán contemplarse:

- monitoreo;
- respaldos automáticos;
- copias separadas de la instancia principal;
- procedimiento documentado de restauración;
- pruebas periódicas de recuperación;
- posibilidad de redundancia cuando la infraestructura lo permita.

Todavía no se seleccionan:

- proveedor de infraestructura;
- servicio de nube;
- motor de base de datos;
- tecnología de replicación;
- balanceador;
- mecanismo concreto de failover.

**Estado: Resuelta a nivel arquitectónico.**

**Pendiente técnico:** definición de infraestructura durante DevOps.

---

### D-23 — Fuente única de verdad documental

**Origen del análisis:** OBS-25, OBS-26, OBS-27 y ARB-16.

Se establece una jerarquía documental para evitar inconsistencias provocadas por la repetición de requerimientos, reglas, estados y criterios en diferentes documentos.

Las fuentes normativas del proyecto serán:

- `proyecto/requisitos/RF.md` para requerimientos funcionales;
- `proyecto/requisitos/RNF.md` para requerimientos no funcionales;
- `proyecto/contexto/reglas_negocio.md` para reglas de negocio;
- `proyecto/requisitos/criterios_aceptacion.md` para criterios de aceptación;
- `proyecto/resultados/02_decision_scope.md` para decisiones humanas que resuelvan ambigüedades o completen definiciones necesarias.

Los documentos:

- `01_requirements_analysis.md`;
- `03_architecture_options.md`;
- revisiones realizadas por agentes;

serán considerados documentos derivados de análisis y no sustituirán las fuentes normativas anteriores.

Cuando un requerimiento aparezca repetido en un documento de análisis o arquitectura, deberá interpretarse como una referencia o resumen.

Si existe diferencia entre un resumen de un documento derivado y su fuente normativa, prevalecerá la fuente normativa más reciente validada por el estudiante.

Las modificaciones futuras deberán realizarse primero en la fuente correspondiente y después actualizar, cuando sea necesario, los documentos derivados.

Para los aspectos observados específicamente por ARB-16 se establece:

- estados y transiciones de citas → RF-13 y reglas de negocio relacionadas;
- información mínima de auditoría → RF-22, RNF-18 y D-12;
- disponibilidad y confirmación de reservas → RF-09, RNF-11, reglas de negocio relacionadas y D-21.

De esta forma, la repetición de información en análisis y alternativas arquitectónicas tendrá carácter explicativo y no constituirá una definición normativa independiente.

**Estado: Resuelta.**

---

## 3. Especialistas necesarios

Para resolver y validar las decisiones será necesario considerar los siguientes perfiles:

| Especialista | Responsabilidad principal |
|---|---|
| Analista de requisitos | Validar reglas de negocio, estados, actores y casos ambiguos |
| Solution Leader | Coordinar decisiones y mantener trazabilidad |
| Arquitecto de software | Comparar y seleccionar alternativas arquitectónicas |
| Especialista de base de datos | Analizar concurrencia, integridad, auditoría y recuperación |
| Especialista de seguridad | Revisar autenticación, autorización y protección de datos |
| Especialista UX/Accesibilidad | Revisar usabilidad y accesibilidad |
| Especialista DevOps | Evaluar disponibilidad, rendimiento y recuperación |
| Especialista de integración | Evaluar servicio externo de atención virtual |

---

## 4. Alternativas que deberán compararse

### Arquitectura

Se deberán comparar alternativas considerando:

- complejidad;
- escalabilidad;
- consistencia;
- costo operativo;
- mantenibilidad;
- seguridad;
- relación con RF y RNF.

No deberán utilizarse microservicios por defecto.

La selección deberá considerar D-18, D-21 y D-22.

---

### Persistencia

Las alternativas deberán considerar:

- integridad de citas;
- prevención de reservas simultáneas;
- transacciones;
- auditoría;
- respaldo;
- recuperación;
- crecimiento esperado.

No se selecciona todavía un motor definitivo.

---

### Atención virtual

La integración deberá seguir D-01 y D-19.

Deberá considerar:

- integración;
- seguridad;
- disponibilidad;
- costo;
- dependencia del proveedor;
- sustitución futura;
- contingencia;
- detección de fallas;
- reintentos;
- registro de incidentes.

---

### Gestión de identidad

La relación entre Médico y Usuario queda establecida mediante D-06.

Médico y Usuario son conceptos diferentes.

Un Médico podrá tener cero o una cuenta de Usuario asociada.

Una cuenta con rol Médico deberá corresponder a un único Médico para realizar funciones clínicas.

---

### Gestión de modalidades

Deberá analizarse si la modalidad se administra a nivel de:

- médico;
- especialidad;
- médico-especialidad;
- horario;
- combinación.

Las decisiones permanecen pendientes en D-08 y D-09.

---

## 5. Restricciones críticas

Se consideran restricciones críticas:

1. El sistema será una aplicación web.

2. La primera versión no desarrollará videollamadas propias.

3. La atención virtual dependerá de un servicio externo.

4. La integración estará desacoplada del proveedor.

5. La atención virtual depende de conexión a Internet.

6. No se contempla facturación ni contabilidad.

7. No se contempla historia clínica completa, diagnóstico ni prescripción.

8. No se contempla farmacia, laboratorio, internación ni emergencias.

9. Se respetarán roles y permisos.

10. Se impedirá la doble reserva.

11. La reserva garantizará atomicidad mediante D-21.

12. Las restricciones de superposición aplicarán también a admisión.

13. Las modificaciones deberán mantener auditoría.

14. La arquitectura deberá permitir disponibilidad, RPO y RTO de D-22.

15. El crecimiento 10× seguirá D-18.

16. La interfaz deberá cumplir D-15.

17. La conservación de información seguirá D-20.

18. Una falla virtual no eliminará, cancelará ni marcará automáticamente la cita como no asistida.

19. Ante una falla virtual detectada deberá existir como mínimo información visible para los usuarios afectados.

20. Las sesiones finalizarán después de 15 minutos de inactividad, según D-13.

21. El backend y la carga inicial del frontend se medirán de forma diferenciada.

22. La documentación deberá respetar la jerarquía de fuentes establecida en D-23.

23. Las decisiones tecnológicas deberán justificarse con trazabilidad.

---

## 6. Preguntas pendientes

Antes de implementar deberán responderse:

1. ¿Qué proveedor externo de atención virtual será utilizado?

2. ¿Cuánto tiempo antes puede cancelarse una cita?

3. ¿Cuánto tiempo antes puede reprogramarse una cita?

4. ¿Cuál será la tolerancia antes de marcar una cita como no asistida?

5. ¿Hasta cuánto tiempo hacia adelante podrá reservarse?

6. ¿Quién habilita la modalidad virtual?

7. ¿Cómo se configura la modalidad virtual?

8. ¿Qué sucede con citas futuras al desactivar un médico?

9. ¿Qué permisos exactos tendrá admisión?

10. ¿Qué formatos serán aceptados para los principales datos?

11. ¿Cuál será la muestra definitiva para usabilidad?

12. ¿Qué herramientas se utilizarán para pruebas de rendimiento?

13. ¿Qué mecanismo de concurrencia implementará D-21?

14. ¿Qué estrategia de infraestructura implementará D-22?

15. ¿Qué proveedor y mecanismos técnicos implementarán D-19?

16. ¿Qué canales externos de notificación se incorporarán, si corresponde?

---

## 7. Decisiones resueltas durante la revisión arquitectónica

Las siguientes decisiones cuentan con una definición suficiente:

### Inicio del estado "En atención"

Resuelta mediante D-05.

### Relación Médico–Usuario

Resuelta mediante D-06.

### Restricción de citas superpuestas

Resuelta mediante D-11.

### Información mínima de auditoría

Resuelta mediante D-12.

### Tiempo máximo de inactividad

Resuelto mediante D-13 con un valor inicial configurable de 15 minutos.

### Accesibilidad

Resuelta mediante D-15 con WCAG 2.1 nivel AA.

### Usabilidad y carga del frontend

Resuelta a nivel de requisitos mediante D-16.

### Condiciones de rendimiento

Resueltas a nivel de requisitos mediante D-17.

### Crecimiento 10×

Resuelto mediante D-18.

### Contingencia virtual

Resuelta mediante D-19.

### Conservación de información

Resuelta mediante D-20 para el alcance académico.

### Concurrencia

Resuelta a nivel arquitectónico mediante D-21.

### Disponibilidad y recuperación

Resuelta a nivel arquitectónico mediante D-22.

### Fuente única de verdad documental

Resuelta mediante D-23.

Los archivos normativos y los archivos derivados quedan diferenciados para evitar inconsistencias de mantenimiento.

---

## 8. Decisiones que NO deben tomarse todavía

Todavía no corresponde seleccionar definitivamente:

- lenguaje de programación;
- framework frontend;
- framework backend;
- motor definitivo de base de datos;
- proveedor de nube;
- Kubernetes;
- microservicios por preferencia;
- tecnología de autenticación;
- proveedor específico de videollamada;
- mecanismo específico de locking;
- tecnología concreta de replicación;
- balanceador;
- mecanismo específico de failover;
- herramientas de pruebas de carga;
- herramientas de usabilidad;
- canales externos específicos de notificación.

Estas decisiones deberán evaluarse posteriormente con los especialistas correspondientes.

---

## 9. Trazabilidad general

La secuencia utilizada es:

**RF/RNF → análisis del agente → observación → validación humana → alternativas arquitectónicas → revisión adversarial → decisión**

Las decisiones no se generan por preferencia tecnológica.

Cada decisión deberá relacionarse con:

- requerimientos;
- reglas de negocio;
- restricciones;
- observaciones;
- hallazgos arquitectónicos.

La documentación deberá respetar D-23.

Las observaciones rechazadas no constituyen fundamento obligatorio.

Las recomendaciones de los agentes tampoco son automáticamente decisiones definitivas.

La decisión final permanece bajo responsabilidad humana.

---

## 10. Resultado de la etapa

El análisis y la revisión permiten continuar hacia las siguientes etapas.

### Hallazgos bloqueantes

- ARB-01 → atendido mediante D-21.
- ARB-02 → atendido mediante D-01 y D-19.
- ARB-03 → atendido mediante D-22.
- ARB-04 → atendido mediante D-18.
- ARB-05 → atendido mediante D-06.

**Resultado: 5 de 5 atendidos.**

### Hallazgos de severidad alta

- ARB-06 → atendido mediante D-12, D-20 y RNF-18.
- ARB-07 → atendido mediante D-05 y RF-14, RF-16 y RF-20.
- ARB-08 → atendido mediante D-17 y los RNF actualizados.
- ARB-09 → atendido mediante D-01 y D-19.
- ARB-10 → atendido mediante D-15, RNF-15 y RNF-19.

**Resultado: 5 de 5 atendidos.**

### Hallazgos de severidad media

- ARB-11 → atendido mediante D-16.
- ARB-12 → atendido mediante D-11.
- ARB-13 → atendido mediante D-05.
- ARB-14 → atendido mediante D-13.
- ARB-15 → atendido mediante D-12 y RNF-18.

**Resultado: 5 de 5 atendidos.**

### Hallazgos de severidad baja

- ARB-16 → atendido mediante D-23, que establece las fuentes normativas y la jerarquía documental.
- ARB-17 → atendido mediante D-19, que define información visible, conservación del estado y contingencia ante fallas de conexión.
- ARB-18 → atendido mediante D-20, que establece una política inicial de conservación.

**Resultado: 3 de 3 atendidos.**

### Resultado general

La revisión arquitectónica contiene 18 hallazgos:

- 5 bloqueantes;
- 5 altos;
- 5 medios;
- 3 bajos.

Los **18 de 18 hallazgos cuentan ahora con una resolución suficiente para continuar**.

Esto no significa que todas las decisiones del proyecto estén terminadas.

Permanecen decisiones funcionales y técnicas pendientes, entre ellas:

- políticas de cancelación y reprogramación;
- tolerancia para no asistencia;
- anticipación máxima de reservas;
- desactivación de médicos;
- modalidad virtual;
- permisos de admisión;
- validación detallada de datos;
- motor de base de datos;
- tecnologías de implementación;
- proveedor virtual;
- infraestructura;
- herramientas de pruebas.

Estas decisiones deberán resolverse en las etapas especializadas correspondientes.

---

## Estado de la etapa

**STATUS: APPROVED — ARCHITECTURAL REVIEW FINDINGS RESOLVED**

La revisión realizada establece:

**18 de 18 hallazgos atendidos.**

- 5/5 BLOQUEANTES;
- 5/5 ALTOS;
- 5/5 MEDIOS;
- 3/3 BAJOS.

Los hallazgos cuentan con decisiones suficientes para continuar hacia los análisis especializados de persistencia, seguridad e infraestructura.

La aprobación no implica que se haya seleccionado todavía el stack tecnológico, el motor de base de datos o la arquitectura definitiva.

Las decisiones tecnológicas deberán realizarse posteriormente utilizando los resultados de los especialistas y manteniendo trazabilidad con los requerimientos y decisiones aprobadas.