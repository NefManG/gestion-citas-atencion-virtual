# Requerimientos No Funcionales

## Rendimiento

- RNF-01. El sistema deberá responder a las operaciones de consulta de especialidades, consulta de médicos, consulta de horarios, registro de citas, reprogramación y cancelación en un tiempo máximo de 3 segundos, considerando una carga de hasta 100 usuarios concurrentes.

Para verificar este requisito, el tiempo de respuesta se medirá desde que el backend recibe la solicitud hasta que genera la respuesta correspondiente, sin considerar la latencia externa propia de la conexión a Internet del usuario.

Las pruebas deberán realizarse en un entorno controlado y reproducible, utilizando la carga definida en RNF-02.

---

## Capacidad

- RNF-02. El sistema deberá soportar inicialmente hasta 100 usuarios concurrentes y una carga objetivo de 500 transacciones de negocio por hora, considerando un pico inmediato de hasta 1.000 transacciones por hora.

Las pruebas de capacidad deberán utilizar una combinación representativa de operaciones de consulta y escritura, incluyendo como mínimo:

- consulta de especialidades;
- consulta de médicos;
- consulta de horarios;
- registro de citas;
- reprogramación;
- cancelación.

---

## Escalabilidad

- RNF-03. El diseño del sistema deberá permitir evolucionar desde una carga normal inicial de 500 transacciones por hora hasta un escenario futuro de evaluación de 5.000 transacciones por hora, equivalente a 10 veces la carga inicial.

El crecimiento de capacidad no deberá requerir modificaciones de:

- los requerimientos funcionales;
- las reglas principales del negocio;
- el comportamiento funcional esperado por los usuarios.

Se permitirán cambios técnicos relacionados con:

- infraestructura;
- configuración;
- optimización;
- índices;
- replicación;
- separación de cargas;
- mecanismos de escalamiento.

El escenario de 5.000 transacciones por hora corresponde a una capacidad futura de evolución y no implica que toda esa infraestructura deba estar disponible desde la primera versión.

---

## Seguridad

- RNF-04. El sistema deberá requerir autenticación para acceder a las funciones correspondientes a pacientes, médicos, personal de admisión o recepción y administradores.

Los usuarios no autenticados únicamente podrán acceder a las funciones públicas que sean definidas expresamente como tales.

- RNF-05. El sistema deberá restringir el acceso a la información y funciones de acuerdo con el rol asignado a cada usuario.

Un usuario no deberá poder ejecutar operaciones que no correspondan a los permisos asociados a su rol.

- RNF-06. El sistema deberá proteger la información personal de pacientes y usuarios mediante:

  - controles de acceso;
  - transmisión segura de información;
  - almacenamiento protegido de credenciales;
  - restricción de acceso según roles y permisos.

Las credenciales no deberán almacenarse en texto plano.

La selección del mecanismo técnico específico de protección de credenciales se realizará durante el diseño de seguridad.

---

## Gestión de sesiones

## Gestión de sesiones

- RNF-07. El sistema deberá finalizar automáticamente la sesión de un usuario después de 15 minutos de inactividad.

El tiempo de inactividad deberá ser configurable sin requerir modificaciones en la lógica funcional del sistema.

Para el presente proyecto, los 15 minutos constituyen un criterio académico. Antes de una implementación real, este valor deberá validarse o sustituirse de acuerdo con la política institucional correspondiente.

---

## Disponibilidad

- RNF-08. El sistema deberá mantener una disponibilidad mensual mínima del 99 %, excluyendo los períodos de mantenimiento previamente programados y comunicados.

La disponibilidad se calculará sobre el período mensual correspondiente mediante la relación entre:

**tiempo total de servicio disponible / tiempo total de servicio esperado**

Las interrupciones no programadas deberán contabilizarse como tiempo de indisponibilidad.

---

## Recuperación y respaldo

- RNF-09. El sistema deberá implementar mecanismos de respaldo y recuperación que permitan cumplir con un RPO máximo de 60 minutos.

Ante una falla que provoque pérdida de información, la cantidad máxima de datos que podrá perderse corresponderá a un período no superior a 60 minutos respecto del último punto recuperable válido.

Los mecanismos de respaldo deberán ejecutarse de forma periódica y deberán permitir verificar la recuperación de la información.

- RNF-10. El sistema deberá permitir restablecer el servicio dentro de un RTO máximo de 120 minutos ante una falla que requiera recuperación.

El RTO será medido desde el momento en que la falla sea identificada y declarada como incidente de recuperación hasta el restablecimiento del servicio necesario para continuar las operaciones principales.

Deberá existir un procedimiento documentado de recuperación y restauración.

---

## Integridad y concurrencia

- RNF-11. El sistema deberá garantizar que un mismo horario no pueda ser reservado simultáneamente por más de un paciente para el mismo médico.

La verificación final de disponibilidad y la confirmación de la cita deberán ejecutarse como una operación atómica.

Cuando dos o más usuarios intenten reservar simultáneamente el mismo horario:

- solamente una reserva podrá confirmarse correctamente;
- las solicitudes restantes deberán recibir una respuesta indicando que el horario ya no se encuentra disponible.

El mecanismo técnico específico de control de concurrencia será seleccionado durante el diseño de persistencia y deberá ser validado bajo la carga establecida en RNF-02.

- RNF-12. Antes de registrar pacientes, médicos, horarios o citas, el sistema deberá validar:

  - presencia de los datos obligatorios;
  - formato de los datos;
  - consistencia básica de los valores ingresados.

Las reglas específicas de formato para documento de identidad, teléfono, correo electrónico, fecha de nacimiento y demás campos deberán quedar definidas antes de la implementación definitiva.

---

## Usabilidad

- RNF-13. La interfaz deberá permitir utilizar las funciones principales del sistema desde:

  - computadoras;
  - tabletas;
  - teléfonos móviles.

La adaptación a diferentes tamaños de pantalla no deberá impedir el acceso a las funciones correspondientes al rol del usuario.

Las operaciones principales de consulta de disponibilidad, reserva, reprogramación, cancelación y consulta de citas deberán mantenerse disponibles en los dispositivos soportados.

- RNF-14. En una prueba de usabilidad, al menos el 80 % de los usuarios participantes deberá poder completar sin asistencia externa las siguientes tareas:

  - consultar disponibilidad de citas;
  - seleccionar especialidad y médico;
  - seleccionar fecha y horario;
  - completar la reserva de una cita.

La metodología, cantidad y perfil de participantes y condiciones de la prueba deberán quedar definidos antes de realizar la validación de usabilidad.

---

## Accesibilidad

- RNF-15. La interfaz deberá aplicar como referencia mínima los criterios de accesibilidad establecidos por WCAG 2.1 nivel AA en los componentes y flujos principales del sistema.

Como mínimo deberán considerarse:

- navegación mediante teclado;
- contraste adecuado entre texto y fondo;
- etiquetas asociadas a los campos de formularios;
- textos alternativos para elementos visuales relevantes;
- mensajes de error comprensibles;
- estructura semántica de encabezados;
- identificación visible del elemento que posee el foco;
- formularios utilizables mediante tecnologías de asistencia.

La validación de accesibilidad deberá realizarse sobre los principales flujos de pacientes, médicos, personal de admisión y administradores.

---

## Mantenibilidad

- RNF-16. Los componentes del sistema deberán mantenerse organizados de forma modular, permitiendo realizar cambios en una funcionalidad sin requerir modificaciones innecesarias en funcionalidades no relacionadas.

La arquitectura deberá mantener una separación clara de responsabilidades entre los principales módulos funcionales, como mínimo:

- pacientes;
- médicos;
- especialidades;
- horarios;
- citas;
- usuarios y roles;
- auditoría;
- integración con atención virtual.

La modularidad no implica obligatoriamente el uso de microservicios.

---

## Observabilidad

- RNF-17. El sistema deberá registrar como mínimo eventos operativos relacionados con:

  - errores de aplicación;
  - intentos fallidos de autenticación;
  - fallas en operaciones críticas;
  - errores de integración con servicios externos;
  - cambios relevantes de estado cuando sean necesarios para diagnóstico.

Los registros de observabilidad deberán permitir identificar como mínimo:

- fecha y hora del evento;
- tipo o nivel del evento;
- componente relacionado;
- descripción suficiente para facilitar el diagnóstico.

La observabilidad tendrá como finalidad principal el monitoreo, diagnóstico y operación del sistema y será independiente del historial de auditoría establecido en RNF-18.

---

## Auditoría

## Auditoría

- RNF-18. El sistema deberá mantener un registro de las modificaciones realizadas sobre las citas.

Cada registro de auditoría deberá almacenar como mínimo:

- fecha;
- hora;
- usuario responsable;
- acción realizada;
- valor anterior;
- valor actualizado.

El historial de auditoría no deberá eliminarse automáticamente cuando una cita sea cancelada o finalizada.

Para el presente proyecto académico, los registros de auditoría deberán conservarse durante un período mínimo de 5 años.

Este período deberá ser configurable y deberá validarse o sustituirse por la política institucional correspondiente antes de una implementación real.

Los registros de auditoría deberán mantenerse separados conceptualmente de los registros técnicos utilizados para observabilidad.

---

## Portabilidad y compatibilidad

- RNF-19. El sistema deberá funcionar mediante navegador web en las dos últimas versiones estables disponibles de:

  - Google Chrome;
  - Microsoft Edge;
  - Mozilla Firefox.

La compatibilidad será evaluada utilizando las versiones estables disponibles en el momento de ejecutar las pruebas de aceptación.

Cuando se publique una nueva versión estable de alguno de los navegadores soportados, el conjunto de versiones oficialmente evaluadas deberá actualizarse periódicamente.