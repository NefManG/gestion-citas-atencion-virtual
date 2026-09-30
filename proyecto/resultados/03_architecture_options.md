# Opciones Arquitectónicas — Sistema de Gestión de Citas y Atención Virtual

## 1. Contexto y restricciones

El Sistema de Gestión de Citas y Atención Virtual, tomando como contexto de aplicación al Hospital Boliviano Español, debe considerar los requerimientos funcionales, requerimientos no funcionales, reglas de negocio, restricciones y decisiones pendientes previamente definidas.

### Requerimientos considerados

- RF-01 a RF-22: gestión de pacientes, médicos, especialidades, horarios, citas, estados, consultas, atención presencial y virtual, registro de atención, usuarios, roles e historial de modificaciones.
- RN-01 a RN-27: reglas de negocio relacionadas con concurrencia de horarios, transiciones de estado, reprogramación, cancelación, disponibilidad e historial de modificaciones.
- RNF-01 a RNF-19: requerimientos relacionados con rendimiento, escalabilidad, seguridad, disponibilidad, recuperación, concurrencia, usabilidad, accesibilidad, modularidad, auditoría y compatibilidad.

Entre los principales requerimientos no funcionales se encuentran:

- RNF-01: tiempo de respuesta menor o igual a 3 segundos para las operaciones principales.
- RNF-02: soporte inicial para 100 usuarios concurrentes, aproximadamente 500 transacciones por hora y picos de hasta 1.000 transacciones.
- RNF-03: posibilidad de escalar hasta 10 veces la carga inicial sin modificar el modelo funcional.
- RNF-04: autenticación obligatoria.
- RNF-05: control de acceso basado en roles.
- RNF-06: protección de datos personales, transmisión segura y protección de credenciales.
- RNF-07: cierre automático de sesión por inactividad.
- RNF-08: disponibilidad mensual igual o superior al 99 %.
- RNF-09: RPO máximo de 60 minutos.
- RNF-10: RTO máximo de 120 minutos.
- RNF-11: protección de la integridad ante operaciones concurrentes sobre horarios y reservas.
- RNF-12: validación de datos obligatorios y formatos.
- RNF-13 y RNF-14: funcionamiento en diferentes dispositivos y facilidad de uso.
- RNF-15: accesibilidad básica.
- RNF-16: modularidad.
- RNF-17 y RNF-18: auditoría, registro de eventos y observabilidad.
- RNF-19: compatibilidad con las versiones estables recientes de Chrome, Edge y Firefox.

### Restricciones consideradas

La primera versión del sistema no contempla:

- Facturación.
- Gestión completa de historias clínicas.
- Gestión de farmacia.
- Gestión de laboratorio clínico.
- Integración obligatoria con otros sistemas hospitalarios.
- Desarrollo de una plataforma propia de videollamadas.
- Aplicaciones móviles nativas.

La atención virtual podrá depender de un servicio externo.

Existen además decisiones pendientes relacionadas con:

- Servicio externo para atención virtual.
- Notificaciones.
- Stack tecnológico.
- Políticas temporales de reprogramación y cancelación.
- Tiempo de inactividad de sesión.
- Anticipación máxima permitida para reservar una cita.

### Restricción de este análisis

En esta etapa:

- No se utilizarán microservicios como opción predeterminada.
- No se seleccionará todavía una arquitectura definitiva.
- No se seleccionará todavía un motor de base de datos definitivo.
- No se escribirá código.
- No se instalarán dependencias.
- No se avanzará a la implementación.

---

# 2. Alternativa A — Monolito en capas clásicas

## 2.1 Descripción

Esta alternativa plantea desarrollar el sistema como una única unidad desplegable organizada internamente mediante capas.

Las principales capas serían:

- Presentación.
- Dominio o lógica de negocio.
- Persistencia o acceso a datos.

Todos los componentes formarían parte de un mismo proceso y serían desplegados como un solo artefacto.

## 2.2 Trazabilidad con RF y RNF

| Requerimiento | Forma de cobertura |
|---|---|
| RF-01 a RF-22 | Toda la lógica funcional se concentra dentro de una única aplicación. |
| RNF-01 | Al no existir comunicación entre servicios independientes se reduce el overhead de red. |
| RNF-11 | La reserva y validación de horarios puede manejarse mediante una única transacción. |
| RNF-08 | Existe un solo componente principal que debe ser monitoreado y mantenido disponible. |
| RNF-16 | Las funcionalidades pueden organizarse internamente por capas y módulos. |
| RNF-17 / RNF-18 | Los registros y auditoría pueden centralizarse dentro de la misma aplicación. |

## 2.3 Complejidad

**Desarrollo:** baja.

Existe una sola base de código, un único proyecto principal y una configuración centralizada.

**Despliegue:** bajo.

La aplicación puede desplegarse como una sola unidad.

**Operación:** baja.

Existe un único componente principal que debe ser monitoreado.

## 2.4 Escalabilidad

La escalabilidad horizontal es limitada porque normalmente es necesario replicar toda la aplicación.

La escalabilidad vertical puede ser suficiente para la carga inicial estimada de 100 usuarios concurrentes y 500 transacciones por hora.

Sin embargo, alcanzar el crecimiento de 10 veces establecido por RNF-03 podría requerir aumentar considerablemente los recursos de infraestructura.

## 2.5 Consistencia

La consistencia puede mantenerse de manera fuerte mediante operaciones transaccionales.

Reglas como:

- un médico no puede tener dos citas en el mismo horario;
- un paciente no puede reservar horarios incompatibles;
- una cancelación debe liberar nuevamente el horario;

pueden controlarse de forma centralizada.

## 2.6 Costo operativo

**Bajo.**

No requiere inicialmente:

- múltiples servidores;
- API Gateway;
- descubrimiento de servicios;
- service mesh;
- mensajería distribuida;
- observabilidad distribuida.

## 2.7 Mantenibilidad

**Moderada.**

La separación en capas facilita la organización inicial.

Sin embargo, si el proyecto aumenta considerablemente de tamaño y no existe una adecuada separación de responsabilidades, el monolito puede volverse difícil de mantener.

## 2.8 Riesgos

- RNF-03 puede resultar difícil de cumplir ante un crecimiento importante de carga.
- RNF-08 puede verse afectado por la existencia de un único punto principal de fallo.
- El crecimiento descontrolado del proyecto puede reducir la mantenibilidad.

---

# 3. Alternativa B — Monolito modular con API REST y frontend SPA

## 3.1 Descripción

Esta alternativa propone mantener el backend como un monolito modular, pero separar el frontend de la lógica del servidor.

El backend expondría una API REST.

El frontend sería una aplicación web SPA (Single Page Application) que consumiría los servicios mediante HTTP/JSON.

El backend continuaría siendo una única aplicación desplegable, organizada internamente mediante módulos de dominio.

Ejemplos de módulos:

- Pacientes.
- Médicos.
- Especialidades.
- Horarios.
- Citas.
- Usuarios.
- Auditoría.

## 3.2 Trazabilidad con RF y RNF

| Requerimiento | Forma de cobertura |
|---|---|
| RF-01 a RF-22 | El backend expone operaciones mediante API y el frontend consume los servicios. |
| RNF-13 / RNF-14 | Una SPA responsive permite utilizar el sistema desde computadoras, tabletas y teléfonos. |
| RNF-01 | Las operaciones del backend permanecen centralizadas y pueden optimizarse para responder dentro del tiempo establecido. |
| RNF-16 | El backend puede dividirse en módulos funcionales claramente delimitados. |
| RNF-19 | El frontend puede desarrollarse para funcionar en navegadores modernos compatibles. |
| RNF-11 | Las operaciones críticas de reserva permanecen dentro del mismo backend transaccional. |

## 3.3 Complejidad

**Desarrollo:** moderada.

Existen dos partes diferenciadas:

- frontend;
- backend.

Cada una posee responsabilidades y ciclos de desarrollo propios.

**Despliegue:** moderado.

El frontend y backend pueden desplegarse de manera independiente.

**Operación:** moderada.

Se deben monitorear al menos dos componentes.

## 3.4 Escalabilidad

El frontend puede escalar fácilmente al tratarse principalmente de recursos estáticos.

El backend conserva características de un monolito, por lo que puede convertirse en el principal cuello de botella.

Para RNF-03, el frontend no debería representar una limitación importante, pero el backend y la persistencia deberán ser diseñados adecuadamente para soportar un crecimiento de carga.

## 3.5 Consistencia

**Alta.**

Las operaciones críticas permanecen dentro del mismo backend y pueden ejecutarse utilizando transacciones consistentes.

No existe necesidad inicial de coordinación distribuida entre múltiples servicios.

## 3.6 Costo operativo

**Moderado.**

Se requiere infraestructura para:

- frontend;
- backend.

No requiere inicialmente infraestructura compleja de microservicios.

## 3.7 Mantenibilidad

**Buena.**

La separación entre frontend y backend permite modificar la interfaz sin afectar directamente la lógica del negocio.

El uso de módulos internos facilita mantener separados los diferentes dominios funcionales.

## 3.8 Riesgos

- Una conexión lenta entre cliente y servidor puede afectar el cumplimiento de RNF-01.
- El backend continúa siendo una unidad central.
- La selección posterior del stack tecnológico influirá en su implementación.

---

# 4. Alternativa C — Monolito modular con CQRS leve

## 4.1 Descripción

Esta alternativa propone utilizar un monolito modular con una separación interna entre operaciones de escritura y operaciones de lectura.

Las operaciones de escritura o comandos incluirían:

- reservar una cita;
- reprogramar una cita;
- cancelar una cita;
- cambiar estados;
- registrar atención.

Las operaciones de lectura incluirían:

- consultar especialidades;
- consultar médicos;
- consultar horarios disponibles;
- consultar citas futuras;
- consultar historial;
- visualizar agenda médica.

No se plantea utilizar CQRS distribuido.

La separación se realizaría dentro de la misma aplicación.

## 4.2 Trazabilidad con RF y RNF

| Requerimiento | Forma de cobertura |
|---|---|
| RNF-01 | Las consultas pueden optimizarse independientemente de las operaciones de escritura. |
| RNF-11 | Las escrituras pueden mantenerse dentro de transacciones consistentes. |
| RF-08 | La consulta de horarios puede utilizar una ruta especializada de lectura. |
| RF-09 | La confirmación final de reserva se realiza mediante el modelo transaccional de escritura. |
| RF-16 | La agenda médica puede utilizar consultas optimizadas. |
| RNF-03 | La separación permite posteriormente escalar la carga de lectura. |
| RNF-16 | Existe separación clara entre comandos, consultas y módulos funcionales. |

## 4.3 Complejidad

**Desarrollo:** moderada-alta.

Los desarrolladores deben comprender y mantener dos modelos conceptuales:

- comandos;
- consultas.

**Despliegue:** baja a moderada.

Continúa existiendo una única aplicación principal.

**Operación:** moderada.

Deben monitorearse diferentes patrones de lectura y escritura.

## 4.4 Escalabilidad

**Buena principalmente para lecturas.**

Las consultas de:

- especialidades;
- médicos;
- disponibilidad;
- agendas;
- historiales;

pueden optimizarse sin afectar las operaciones de escritura.

En una evolución futura podrían utilizarse réplicas de lectura si la tecnología seleccionada lo permite.

## 4.5 Consistencia

Las escrituras pueden mantener consistencia fuerte.

Las lecturas podrían llegar a tener consistencia eventual si posteriormente se utilizan réplicas.

La operación RF-09 debe realizar una verificación final contra la fuente transaccional antes de confirmar una reserva.

## 4.6 Costo operativo

**Moderado.**

Inicialmente puede funcionar con una única infraestructura.

El costo podría aumentar si posteriormente se incorporan réplicas de lectura.

## 4.7 Mantenibilidad

**Buena**, siempre que el equipo conozca el patrón.

La separación entre comandos y consultas ayuda a organizar responsabilidades.

Sin embargo, introduce mayor complejidad conceptual que las alternativas A y B.

## 4.8 Riesgos

- Mayor complejidad de desarrollo.
- Posible inconsistencia temporal entre escritura y lectura si se utilizan réplicas.
- Puede resultar innecesaria si la cantidad real de consultas no justifica CQRS.

---

# 5. Alternativa D — Monolito modular con procesamiento asíncrono

## 5.1 Descripción

Esta alternativa mantiene un monolito modular como núcleo del sistema, pero incorpora procesamiento asíncrono para operaciones que no necesitan completarse inmediatamente.

Podrían procesarse de manera asíncrona:

- notificaciones;
- auditoría;
- registro de determinados eventos;
- recordatorios futuros.

Las operaciones críticas permanecerían síncronas y transaccionales.

Por ejemplo:

- reservar;
- reprogramar;
- cancelar;
- cambiar estados.

## 5.2 Trazabilidad con RF y RNF

| Requerimiento | Forma de cobertura |
|---|---|
| RF-22 | Los cambios pueden generar eventos utilizados para registrar el historial. |
| RNF-17 / RNF-18 | Determinados registros de auditoría pueden procesarse sin bloquear la operación principal. |
| DP-02 | La arquitectura queda preparada para futuras notificaciones. |
| RNF-11 | La reserva de horarios continúa siendo síncrona y transaccional. |
| RNF-01 | Las tareas secundarias pueden separarse del flujo principal para reducir tiempos de respuesta. |

## 5.3 Complejidad

**Desarrollo:** moderada.

Se deben implementar mecanismos relacionados con:

- publicación de eventos;
- consumidores;
- reintentos;
- tratamiento de errores.

**Despliegue:** moderado.

Puede mantenerse una aplicación principal, pero se agrega un mecanismo de procesamiento asíncrono.

**Operación:** moderada-alta.

Es necesario monitorear procesos pendientes y posibles fallos.

## 5.4 Escalabilidad

**Buena para tareas asíncronas.**

Las tareas secundarias pueden procesarse sin bloquear operaciones críticas.

Esto permite absorber determinados picos de carga.

## 5.5 Consistencia

**Alta para las operaciones críticas.**

Las operaciones relacionadas directamente con la cita permanecen transaccionales.

Las operaciones secundarias pueden utilizar consistencia eventual.

## 5.6 Costo operativo

**Moderado.**

Es mayor que un monolito sencillo debido al procesamiento asíncrono, aunque continúa siendo menor que una arquitectura distribuida completa.

## 5.7 Mantenibilidad

**Buena**, porque permite desacoplar funcionalidades secundarias.

Sin embargo, se agrega complejidad relacionada con:

- mensajes;
- reintentos;
- consumidores;
- tratamiento de errores.

## 5.8 Riesgos

- Pérdida de eventos si no existe una estrategia adecuada ante fallos.
- Mayor complejidad de diagnóstico.
- Puede ser innecesaria si las notificaciones u otras tareas asíncronas no forman parte de la primera versión.

---

# 6. Alternativa E — Monolito modular contenerizado con despliegue simple

## 6.1 Descripción

Esta alternativa propone desarrollar un monolito modular y empaquetarlo mediante tecnología de contenedores.

La aplicación podría ejecutarse inicialmente en:

- una máquina virtual;
- un servidor;
- un host con contenedores.

No se plantea inicialmente utilizar:

- Kubernetes;
- service mesh;
- múltiples microservicios;
- orquestación compleja.

## 6.2 Trazabilidad con RF y RNF

| Requerimiento | Forma de cobertura |
|---|---|
| RNF-08 | Puede desplegarse sobre una infraestructura controlada y monitoreada. |
| RNF-09 / RNF-10 | Los procedimientos de respaldo y restauración pueden automatizarse. |
| RNF-03 | Inicialmente puede utilizar escalamiento vertical y posteriormente evolucionar. |
| RT relacionados con aplicación web | La aplicación continúa siendo accesible mediante navegador. |
| RNF-16 | El sistema mantiene organización modular interna. |

## 6.3 Complejidad

**Desarrollo:** baja-moderada.

La contenerización no modifica significativamente el desarrollo funcional.

**Despliegue:** bajo.

La aplicación puede distribuirse mediante una imagen reproducible.

**Operación:** baja-moderada.

Se debe administrar el host, contenedor y persistencia.

## 6.4 Escalabilidad

Principalmente vertical en la primera versión.

El host puede recibir:

- más CPU;
- más memoria;
- mejores recursos.

Existe un límite práctico para este tipo de escalamiento.

## 6.5 Consistencia

**Alta.**

Las operaciones críticas permanecen dentro del mismo sistema transaccional.

## 6.6 Costo operativo

**Bajo.**

Puede utilizar inicialmente un solo servidor o máquina virtual sin infraestructura de orquestación compleja.

## 6.7 Mantenibilidad

**Buena.**

El empaquetado mediante contenedores facilita reproducir el ambiente entre:

- desarrollo;
- pruebas;
- producción.

La mantenibilidad del código dependerá de conservar adecuadamente los módulos internos.

## 6.8 Riesgos

- Un solo host puede convertirse en punto único de fallo.
- RNF-08 requiere estrategias de disponibilidad y recuperación.
- RNF-03 puede superar posteriormente las posibilidades del escalamiento vertical.

---

# 7. Alternativa F — Arquitectura de microservicios livianos

## 7.1 Descripción

Esta alternativa se incluye únicamente como referencia comparativa y no como arquitectura predeterminada para la primera versión.

El sistema podría dividirse en servicios independientes como:

- Pacientes.
- Médicos.
- Especialidades.
- Horarios.
- Citas.
- Autenticación y roles.
- Auditoría.

Los servicios se comunicarían mediante APIs.

## 7.2 Trazabilidad con RF y RNF

| Requerimiento | Forma de cobertura |
|---|---|
| RF-01 a RF-22 | Cada dominio funcional podría implementarse como un servicio independiente. |
| RNF-03 | Los servicios pueden escalar independientemente. |
| RNF-08 | Los fallos pueden aislarse entre servicios si la arquitectura está correctamente diseñada. |
| RNF-16 | Existe separación física entre dominios. |
| RNF-11 | Requeriría coordinación distribuida para mantener la integridad de reservas. |

## 7.3 Complejidad

**Desarrollo:** alta.

Implica:

- múltiples servicios;
- múltiples contratos API;
- coordinación entre componentes;
- versionado;
- comunicación distribuida.

**Despliegue:** alto.

Existen múltiples componentes que deben desplegarse y administrarse.

**Operación:** alta.

Requiere mayor observabilidad, monitoreo y diagnóstico.

## 7.4 Escalabilidad

**Excelente.**

Cada servicio puede escalar de acuerdo con su propia demanda.

## 7.5 Consistencia

**Moderada o compleja.**

Al separar horarios y citas en servicios diferentes, garantizar que un mismo horario no sea reservado dos veces requiere mecanismos de coordinación distribuida.

Esto aumenta la dificultad para cumplir simultáneamente:

- RNF-11;
- RNF-01.

## 7.6 Costo operativo

**Alto.**

Puede requerir:

- múltiples instancias;
- gateway;
- monitoreo distribuido;
- trazas;
- gestión de servicios;
- mecanismos de tolerancia a fallos.

## 7.7 Mantenibilidad

Puede ser favorable para organizaciones con múltiples equipos independientes.

Sin embargo, para un equipo pequeño puede resultar más difícil debido a la cantidad de servicios y componentes que deben mantenerse.

## 7.8 Riesgos

- Complejidad excesiva para una carga inicial de 100 usuarios concurrentes y 500 transacciones por hora.
- Mayor dificultad para garantizar reservas atómicas.
- Mayor costo de infraestructura.
- Mayor dificultad operativa.
- El alcance de la primera versión no presenta todavía una necesidad clara de desacoplamiento físico mediante microservicios.

---

# 8. Tabla comparativa consolidada

| Criterio | A: Monolito en capas | B: Monolito + SPA | C: CQRS leve | D: Monolito + procesamiento asíncrono | E: Monolito contenerizado | F: Microservicios |
|---|---|---|---|---|---|---|
| Complejidad | Baja | Moderada | Moderada-alta | Moderada-alta | Baja-moderada | Alta |
| Escalabilidad | Limitada | Buena en frontend / limitada en backend | Buena para lecturas | Buena para procesos asíncronos | Limitada inicialmente | Excelente |
| Consistencia | Alta | Alta | Fuerte en escritura / posible eventual en lectura | Alta en operaciones críticas / eventual en secundarias | Alta | Moderada y distribuida |
| Costo operativo | Bajo | Moderado | Moderado | Moderado | Bajo | Alto |
| Mantenibilidad | Moderada | Buena | Buena | Buena | Buena | Variable según tamaño del equipo |
| RNF-01: respuesta ≤ 3 s | Favorable | Favorable con optimización | Favorable | Favorable | Favorable | Mayor riesgo por comunicación distribuida |
| RNF-11: concurrencia | Favorable | Favorable | Favorable | Favorable | Favorable | Compleja |
| RNF-03: escalabilidad 10× | Riesgo | Riesgo principalmente en backend | Favorable para lecturas | Favorable en procesos asíncronos | Riesgo | Favorable |
| RNF-08: disponibilidad 99 % | Requiere estrategia de alta disponibilidad | Requiere estrategia para backend | Requiere estrategia | Requiere estrategia | Requiere estrategia | Puede aislar fallos, pero aumenta complejidad |
| Alineación con alcance v1 | Alta | Alta | Alta | Alta si se necesitan tareas asíncronas | Alta | Baja por complejidad |

---

# 9. Recomendaciones preliminares del análisis

Las siguientes recomendaciones corresponden únicamente al análisis arquitectónico y no representan todavía una decisión definitiva del estudiante.

## Alternativa F — Microservicios

No se considera necesaria como arquitectura inicial debido a que la complejidad técnica y operativa puede superar los beneficios para la carga actualmente prevista.

La concurrencia de reservas y la necesidad de consistencia agregarían problemas de coordinación distribuida que no existen en las alternativas monolíticas.

## Alternativa B — Monolito modular + SPA

Representa una alternativa equilibrada debido a que permite separar frontend y backend sin introducir todavía una arquitectura distribuida compleja.

Puede facilitar el cumplimiento de los requerimientos relacionados con:

- acceso web;
- diseño responsive;
- navegadores;
- modularidad.

## Alternativa C — CQRS leve

Debe considerarse principalmente si las consultas de disponibilidad, agendas y citas representan una proporción importante de la carga del sistema.

Permite optimizar lecturas sin dividir físicamente el sistema en microservicios.

## Alternativa D — Procesamiento asíncrono

Debe considerarse si se decide incluir desde la primera versión:

- notificaciones;
- recordatorios;
- auditoría asíncrona;
- tareas secundarias no bloqueantes.

Si estas funcionalidades no se consideran necesarias inicialmente, puede introducir complejidad prematuramente.

## Alternativa E — Monolito contenerizado

Puede ser apropiada cuando se desea simplificar el despliegue y mantener ambientes reproducibles.

Será necesario considerar mecanismos de disponibilidad, respaldo y recuperación para evitar que un único host constituya un punto crítico.

## Alternativa A — Monolito en capas

Representa la opción más sencilla en términos de desarrollo y operación.

Sin embargo, debe mantenerse una adecuada disciplina de diseño para evitar un crecimiento desorganizado del código.

---

# 10. Decisiones pendientes que requieren validación humana

Antes de seleccionar definitivamente una arquitectura se deben revisar las siguientes decisiones:

### DP-04 — Stack tecnológico

La selección del lenguaje, framework y herramientas puede afectar la implementación y viabilidad práctica de las diferentes alternativas.

Todavía no debe seleccionarse en este documento.

### DP-02 — Notificaciones

Debe determinarse si las notificaciones mediante correo electrónico u otros medios serán parte de la primera versión.

Si se incluyen, el procesamiento asíncrono puede adquirir mayor relevancia.

### DP-08 — Anticipación máxima de reservas

Debe definirse con cuánta anticipación podrá reservarse una cita.

Un período considerablemente largo puede aumentar la cantidad de datos consultados al buscar disponibilidad.

### DP-07 — Tiempo de inactividad de sesión

Debe definirse el tiempo máximo de inactividad antes del cierre automático de sesión.

### Criterio de selección arquitectónica

El equipo deberá determinar qué aspectos tienen mayor prioridad:

- simplicidad;
- escalabilidad;
- separación frontend/backend;
- costo operativo;
- mantenibilidad;
- facilidad de implementación.

La decisión final debe ser realizada por el estudiante después de analizar las alternativas.

---

# 11. Requerimientos que requieren definición adicional

## RNF-15 — Accesibilidad

Debe definirse con mayor precisión qué nivel o estándar de accesibilidad deberá cumplir el sistema.

## RNF-19 — Compatibilidad con navegadores

Debe mantenerse una política para determinar qué versiones de navegadores serán oficialmente soportadas.

## RNF-03 — Escalabilidad 10×

Debe aclararse cómo será evaluado el crecimiento de diez veces la carga y si constituye una necesidad inmediata o una capacidad de evolución futura.

## RNF-01 — Tiempo de respuesta

Debe establecerse bajo qué condiciones se medirá el límite de 3 segundos, considerando factores como:

- carga concurrente;
- operación ejecutada;
- infraestructura;
- condiciones de red.

---

# 12. Nota final

El presente análisis no selecciona todavía una arquitectura definitiva para el Sistema de Gestión de Citas y Atención Virtual.

Se presentan seis alternativas arquitectónicas con diferentes niveles de complejidad, escalabilidad, consistencia, costo operativo y mantenibilidad.

Las alternativas fueron relacionadas con los requerimientos funcionales y no funcionales definidos previamente, permitiendo identificar ventajas, limitaciones y riesgos de cada enfoque.

No se selecciona todavía un motor de base de datos definitivo.

No se avanza a implementación ni se escribe código.

La selección final de la arquitectura deberá realizarse posteriormente mediante evaluación y validación humana, considerando el alcance del sistema, los requerimientos, las restricciones, los recursos disponibles y las decisiones pendientes del proyecto.