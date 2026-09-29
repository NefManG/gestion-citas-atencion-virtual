# Alcance de Decisiones — Sistema de Gestión de Citas y Atención Virtual

## 1. Objetivo

El presente documento identifica las decisiones que deben resolverse antes de avanzar hacia la definición de arquitectura, persistencia e implementación del Sistema de Gestión de Citas y Atención Virtual.

El alcance se obtiene a partir de:

- los requerimientos funcionales;
- los requerimientos no funcionales;
- las reglas de negocio;
- las restricciones;
- las decisiones pendientes;
- el análisis de requerimientos realizado;
- la validación humana de las observaciones detectadas.

Las decisiones descritas en este documento mantienen trazabilidad con las observaciones obtenidas durante la etapa de `requirements-analysis`.

En esta etapa no se selecciona todavía un stack tecnológico definitivo, una base de datos específica ni una arquitectura final.

---

## 2. Decisiones que deben tomarse

### D-01 — Servicio externo de atención virtual

**Origen del análisis:** OBS-01 y OBS-33.

**Requerimientos relacionados:** RF-19, RN-18, RN-19, RT-05, DP-01.

Debe definirse qué tipo de servicio externo será utilizado para las citas virtuales.

La decisión deberá considerar:

- forma de acceso del paciente;
- forma de acceso del médico;
- generación o almacenamiento del enlace de acceso;
- necesidad de credenciales o tokens;
- disponibilidad del servicio;
- integración con el sistema;
- posibilidad de cambiar de proveedor en el futuro.

Estado: Pendiente.

---

### D-02 — Políticas de cancelación y reprogramación

**Origen del análisis:** OBS-02.

**Requerimientos relacionados:** RF-11, RF-12, RN-11, RN-12, RN-26, DP-05.

Debe definirse el tiempo mínimo de anticipación permitido para que un paciente o el personal autorizado pueda cancelar o reprogramar una cita.

Esta decisión deberá establecer claramente:

- tiempo mínimo para cancelar;
- tiempo mínimo para reprogramar;
- comportamiento cuando se intente realizar la operación fuera del tiempo permitido.

Estado: Pendiente.

---

### D-03 — Tiempo de tolerancia para no asistencia

**Origen del análisis:** OBS-02.

**Requerimientos relacionados:** RF-13, RN-21, DP-06.

Debe definirse cuánto tiempo puede transcurrir desde la hora programada de una cita antes de considerarla como "No asistida".

Debe aclararse también qué actor estará autorizado para realizar este cambio de estado.

Estado: Pendiente.

---

### D-04 — Anticipación máxima para reservar una cita

**Origen del análisis:** OBS-06.

**Requerimientos relacionados:** RF-08, RN-11, DP-08.

Debe establecerse hasta qué fecha futura podrá un paciente consultar y reservar horarios disponibles.

La decisión debe definir una ventana máxima de reserva expresada en días, semanas o meses.

Estado: Pendiente.

---

### D-05 — Inicio del estado "En atención"

**Origen del análisis:** OBS-04 y OBS-07.

**Requerimientos relacionados:** RF-13, RF-14, RF-16, RF-20, RN-20 y criterios de aceptación asociados.

Debe definirse qué acción provoca la transición:

**Confirmada → En atención**

Las alternativas que deberán analizarse posteriormente son:

- el médico inicia manualmente la atención;
- el personal de admisión registra el inicio;
- el sistema permite ambas opciones según permisos.

También deberá definirse la visibilidad de una cita que se encuentra en estado "En atención" para el paciente y para el médico.

Estado: Pendiente.

---

### D-06 — Relación entre Médico y Usuario

**Origen del análisis:** OBS-08 y OBS-11.

**Requerimientos relacionados:** RF-02, RF-21, RN-17 y criterios de aceptación correspondientes.

Debe definirse la relación existente entre:

- la entidad Médico;
- el usuario del sistema;
- el rol Médico.

Debe resolverse si:

1. médico y usuario son una misma entidad;
2. son entidades diferentes relacionadas entre sí;
3. un médico puede existir sin poseer una cuenta de usuario.

También deberá determinarse qué ocurre cuando:

- un médico es desactivado;
- su usuario es desactivado;
- se revoca el rol Médico;
- existen citas futuras asociadas al médico.

Estado: Pendiente.

---

### D-07 — Desactivación de médicos con citas futuras

**Origen del análisis:** OBS-18 y observación de riesgo relacionada con la desactivación de médicos.

**Requerimientos relacionados:** RF-02, RF-05, RN-02 y reglas relacionadas con citas futuras.

Debe definirse el comportamiento del sistema cuando un médico es marcado como inactivo y todavía posee citas futuras programadas o confirmadas.

Las alternativas a evaluar son:

- impedir la desactivación hasta resolver las citas;
- permitir la desactivación y mantener las citas;
- solicitar previamente la reprogramación o cancelación de las citas afectadas.

No se selecciona todavía una alternativa definitiva.

Estado: Pendiente.

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

Estado: Pendiente.

---

### D-09 — Modalidad asociada a los horarios

**Origen del análisis:** OBS-22.

**Requerimientos relacionados:** RF-05, RF-07, RF-08, RF-18, RN-18, RO-06.

Debe definirse si un mismo médico puede tener:

- horarios exclusivamente presenciales;
- horarios exclusivamente virtuales;
- horarios de ambas modalidades.

También deberá determinarse si la modalidad estará asociada al bloque horario específico.

Estado: Pendiente.

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

Las operaciones realizadas por este rol deberán quedar sujetas a control de acceso y auditoría.

Estado: Pendiente.

---

### D-11 — Restricción de citas superpuestas

**Origen del análisis:** OBS-03.

**Requerimientos relacionados:** RF-09, RF-10, RF-11, RN-05, RN-16.

Debe aclararse que la regla que impide citas superpuestas de un paciente se aplica independientemente de quién realice la operación.

Por tanto, deberá aplicarse cuando la cita sea registrada o reprogramada por:

- el propio paciente;
- el personal de admisión en representación del paciente.

No se define todavía el mecanismo técnico utilizado para garantizar esta restricción.

Estado: Pendiente de formalización.

---

### D-12 — Información mínima de auditoría

**Origen del análisis:** OBS-12 y OBS-26.

**Requerimientos relacionados:** RF-22, RNF-17, RNF-18, RN-23, RN-24, RN-25.

Debe unificarse la información mínima que deberá registrarse en el historial de modificaciones.

Se debe considerar como mínimo:

- fecha;
- hora;
- usuario responsable;
- acción realizada;
- valor anterior;
- valor actualizado.

También deberá definirse posteriormente el tiempo de conservación de esta información.

Estado: Pendiente.

---

### D-13 — Tiempo máximo de inactividad de sesión

**Origen del análisis:** OBS-10 y OBS-15.

**Requerimientos relacionados:** RNF-07, DP-07.

Debe definirse el tiempo máximo que una sesión podrá permanecer inactiva antes de cerrarse automáticamente.

Esta decisión deberá ser posteriormente revisada desde el punto de vista de seguridad y experiencia de usuario.

No se seleccionará todavía ningún mecanismo técnico de autenticación o manejo de sesiones.

Estado: Pendiente.

---

### D-14 — Reglas de validación de datos

**Origen del análisis:** OBS-28 y OBS-30.

**Requerimientos relacionados:** RF-01, RNF-12, CA-001, CA-002.

Deben definirse reglas verificables para los principales datos registrados por el sistema.

Se deberán establecer criterios para campos como:

- documento de identidad;
- nombre y apellidos;
- teléfono;
- correo electrónico;
- fecha de nacimiento.

La definición debe indicar qué datos son obligatorios y qué condiciones hacen que un dato sea considerado válido.

Estado: Pendiente.

---

### D-15 — Accesibilidad

**Origen del análisis:** OBS-09 y OBS-28.

**Requerimientos relacionados:** RNF-15.

El requisito de "criterios básicos de accesibilidad" deberá transformarse posteriormente en criterios verificables.

Se deberá analizar qué nivel o estándar de accesibilidad será apropiado para el sistema.

En esta etapa no se selecciona todavía un estándar definitivo.

Estado: Pendiente de análisis especializado.

---

### D-16 — Prueba de usabilidad

**Origen del análisis:** OBS-29.

**Requerimientos relacionados:** RNF-13, RNF-14.

Debe definirse la metodología utilizada para comprobar el requisito que establece que al menos el 80 % de los usuarios pueda consultar disponibilidad y reservar una cita sin asistencia externa.

Posteriormente deberá especificarse:

- tamaño de la muestra;
- perfil de los participantes;
- tareas que deben realizar;
- criterio de éxito;
- forma de medición.

Estado: Pendiente.

---

### D-17 — Condiciones de medición de rendimiento

**Origen del análisis:** OBS-32 y OBS-36.

**Requerimientos relacionados:** RNF-01, RNF-02, RNF-08, RNF-09, RNF-10.

Los requisitos de rendimiento ya poseen valores numéricos, pero deben establecerse las condiciones bajo las cuales serán evaluados.

Debe definirse posteriormente:

- entorno de pruebas;
- carga aplicada;
- cantidad de usuarios concurrentes;
- distribución de operaciones;
- condiciones de red consideradas;
- forma de medir el tiempo de respuesta.

Estado: Pendiente de definición técnica.

---

### D-18 — Escenario de crecimiento

**Origen del análisis:** OBS-16.

**Requerimientos relacionados:** RNF-02, RNF-03, CA-RNF-02, CA-RNF-03 y perfil de carga.

Debe aclararse la diferencia entre:

- carga objetivo de 500 transacciones por hora;
- pico de 1.000 transacciones por hora;
- escenario de crecimiento de hasta 10 veces la carga inicial.

Se propone interpretar inicialmente:

- 500 transacciones/hora como carga normal;
- 1.000 transacciones/hora como pico inmediato;
- crecimiento 10x como escenario futuro de evaluación.

Esta interpretación deberá ser aprobada antes de utilizarse como criterio arquitectónico.

Estado: Pendiente de aprobación.

---

### D-19 — Contingencia ante falla del servicio virtual

**Origen del análisis:** OBS-20 y OBS-33.

**Requerimientos relacionados:** RF-19, RT-04, RT-05, RN-18, RN-19, DP-01.

Debe definirse qué comportamiento tendrá el sistema si el proveedor externo de atención virtual no se encuentra disponible.

Se deberán analizar posteriormente alternativas como:

- mantener la cita y notificar el problema;
- permitir reprogramación;
- permitir cambio a modalidad presencial cuando sea posible;
- intervención manual del personal autorizado.

No se selecciona todavía una alternativa.

Estado: Pendiente.

---

### D-20 — Conservación de información

**Origen del análisis:** observaciones relacionadas con la retención del historial y los registros de auditoría.

**Requerimientos relacionados:** RF-15, RF-22, RN-22, RNF-17, RNF-18, CA-039.

Debe analizarse el tiempo durante el cual deberán conservarse:

- citas finalizadas;
- citas canceladas;
- citas no asistidas;
- registros de auditoría.

La decisión deberá considerar necesidades operativas, de trazabilidad y las políticas institucionales aplicables.

Estado: Pendiente.

---

## 3. Especialistas necesarios

Para resolver las decisiones anteriores será necesario contar con la revisión de los siguientes perfiles:

| Especialista | Responsabilidad principal |
|---|---|
| Analista de requisitos | Validar reglas de negocio, estados, actores y casos ambiguos |
| Solution Leader | Coordinar las decisiones y mantener trazabilidad con RF/RNF |
| Arquitecto de software | Comparar alternativas arquitectónicas sin imponer tecnología por preferencia |
| Especialista de base de datos | Analizar concurrencia, integridad, auditoría, recuperación y crecimiento |
| Especialista de seguridad | Revisar autenticación, autorización, sesiones, permisos y protección de datos |
| Especialista UX/Accesibilidad | Revisar usabilidad, dispositivos y criterios de accesibilidad |
| Especialista de infraestructura/DevOps | Evaluar disponibilidad, rendimiento, observabilidad, respaldo y recuperación |
| Especialista de integración | Evaluar la dependencia del servicio externo de atención virtual |

---

## 4. Alternativas que deberán compararse

En las siguientes etapas se deberán comparar alternativas para los siguientes puntos.

### Arquitectura

Se deberán plantear entre 4 y 6 alternativas arquitectónicas y compararlas considerando:

- complejidad;
- escalabilidad;
- consistencia;
- costo operativo;
- mantenibilidad.

No deberán utilizarse microservicios por defecto.

---

### Persistencia

Se deberán comparar alternativas de almacenamiento considerando:

- integridad de las citas;
- prevención de reservas simultáneas;
- transacciones;
- auditoría;
- respaldo;
- recuperación;
- crecimiento esperado.

No se selecciona todavía un motor de base de datos.

---

### Atención virtual

Se deberán comparar alternativas de integración con servicios externos considerando:

- facilidad de integración;
- seguridad;
- disponibilidad;
- costo;
- dependencia del proveedor;
- capacidad de sustitución futura.

---

### Gestión de identidad

Se deberán comparar alternativas para representar la relación entre:

**Usuario → Rol → Médico**

sin tomar todavía una decisión de implementación.

---

### Gestión de modalidades

Se deberá analizar si la modalidad presencial/virtual se administra a nivel de:

- médico;
- especialidad;
- médico-especialidad;
- horario;
- combinación de niveles.

---

## 5. Restricciones críticas

Se consideran restricciones críticas del proyecto:

1. El sistema será utilizado mediante una aplicación web.
2. La primera versión no desarrollará una plataforma propia de videollamadas.
3. La atención virtual dependerá de un servicio externo.
4. La atención virtual depende de la disponibilidad de conexión a Internet.
5. La primera versión no contempla facturación ni gestión contable.
6. La primera versión no contempla historias clínicas, diagnóstico ni prescripción médica.
7. La primera versión no contempla farmacia, laboratorio, internación ni emergencias.
8. Se deberán respetar los roles y permisos definidos para cada actor.
9. Se deberá impedir la doble reserva del mismo horario para un mismo médico.
10. Las modificaciones de citas deberán mantener trazabilidad mediante auditoría.
11. Las decisiones tecnológicas deberán justificarse posteriormente utilizando RF, RNF, restricciones y resultados de los especialistas.

---

## 6. Preguntas pendientes

Antes de implementar deberán responderse las siguientes preguntas:

1. ¿Qué servicio externo de atención virtual será utilizado?
2. ¿Cuánto tiempo antes puede cancelarse una cita?
3. ¿Cuánto tiempo antes puede reprogramarse una cita?
4. ¿Cuál será el tiempo de tolerancia para considerar una cita como no asistida?
5. ¿Hasta cuánto tiempo hacia adelante podrá reservarse una cita?
6. ¿Quién inicia el estado "En atención"?
7. ¿Quién habilita la modalidad virtual?
8. ¿La modalidad virtual se configura por médico, especialidad o bloque horario?
9. ¿Qué relación existe entre un Médico y un Usuario con rol Médico?
10. ¿Qué sucede con las citas futuras cuando un médico es desactivado?
11. ¿Qué permisos exactos tendrá el personal de admisión?
12. ¿Cuál será el tiempo máximo de inactividad de una sesión?
13. ¿Cuánto tiempo se conservarán las citas históricas?
14. ¿Cuánto tiempo se conservarán los registros de auditoría?
15. ¿Qué ocurre cuando el servicio externo de atención virtual falla?
16. ¿Qué formatos serán aceptados para los principales datos de pacientes?
17. ¿Qué criterios se utilizarán para evaluar accesibilidad?
18. ¿Cómo se realizará la prueba de usabilidad?
19. ¿Bajo qué condiciones se medirán los requisitos de rendimiento?
20. ¿El escenario 10x corresponde únicamente a crecimiento futuro?

---

## 7. Decisiones que NO deben tomarse todavía

En esta etapa no corresponde seleccionar:

- lenguaje de programación;
- framework frontend;
- framework backend;
- motor definitivo de base de datos;
- proveedor de nube;
- Kubernetes;
- arquitectura de microservicios;
- tecnología específica de autenticación;
- mecanismo específico de locking o control de concurrencia.

Estas decisiones deberán evaluarse posteriormente con los especialistas correspondientes.

---

## 8. Trazabilidad general

La secuencia utilizada para definir las decisiones de este documento es:

**RF/RNF → análisis del agente → observación → validación humana → decisión**

De esta forma, las decisiones no son generadas por preferencia tecnológica ni de manera aislada, sino a partir de problemas, ambigüedades, riesgos y aspectos pendientes identificados durante el análisis de requerimientos.

Las observaciones rechazadas durante la validación humana no se utilizan como fundamento obligatorio para tomar decisiones posteriores.

---

## 9. Resultado de la etapa

El análisis de requisitos permite continuar hacia la evaluación de alternativas arquitectónicas, pero existen decisiones funcionales y no funcionales que deberán mantenerse visibles durante las siguientes etapas.

Las decisiones pendientes no deberán resolverse por preferencia tecnológica.

Cada decisión deberá mantener trazabilidad con:

- requerimientos funcionales;
- requerimientos no funcionales;
- reglas de negocio;
- restricciones;
- observaciones validadas.

La aprobación de este documento significa que el alcance de las decisiones ha sido revisado y aceptado.

No significa que todas las decisiones D-01 a D-20 ya estén resueltas.

---

## Estado de la etapa

STATUS: APPROVED

Etapa revisada y aprobada. Se puede continuar con la evaluación de alternativas arquitectónicas.