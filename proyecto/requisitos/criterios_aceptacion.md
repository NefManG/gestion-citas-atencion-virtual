# Criterios de Aceptación

## Gestión de pacientes

- CA-001. Al registrar un paciente con todos los datos obligatorios válidos, el sistema deberá guardar la información y permitir su consulta posterior.

- CA-002. Si falta el nombre, apellidos, documento de identidad o teléfono, el sistema deberá impedir el registro e informar los datos faltantes.

## Gestión de médicos y especialidades

- CA-003. El administrador deberá poder registrar, consultar, modificar y cambiar el estado activo o inactivo de un médico.

- CA-004. El administrador deberá poder registrar, consultar, modificar y cambiar el estado activo o inactivo de una especialidad médica.

- CA-005. El sistema deberá permitir asociar uno o más médicos con una o más especialidades.

## Gestión de horarios

- CA-006. El médico o personal autorizado deberá poder registrar y actualizar sus días y bloques horarios disponibles.

- CA-007. Al consultar una especialidad, el sistema deberá mostrar únicamente especialidades activas con médicos asociados.

- CA-008. Al seleccionar una especialidad, el sistema deberá mostrar únicamente médicos activos que tengan disponibilidad registrada.

- CA-009. Al seleccionar un médico, el sistema deberá mostrar únicamente horarios futuros disponibles dentro del periodo habilitado para reservas.

## Reserva de citas

- CA-010. Al seleccionar especialidad, médico, fecha y horario disponible, el paciente deberá poder registrar una cita.

- CA-011. Antes de confirmar una reserva, el sistema deberá verificar nuevamente que el horario continúe disponible.

- CA-012. Si dos o más usuarios intentan reservar simultáneamente el mismo horario para el mismo médico, únicamente una reserva deberá confirmarse correctamente.

Las demás solicitudes deberán ser rechazadas indicando que el horario ya no se encuentra disponible.

- CA-013. Una vez confirmada una cita, el horario correspondiente deberá dejar de mostrarse como disponible.

- CA-014. El personal de admisión deberá poder registrar una cita en representación de un paciente cuando tenga los permisos correspondientes.

## Reprogramación y cancelación

- CA-015. Al reprogramar una cita, el sistema deberá reservar el nuevo horario y liberar el horario anterior únicamente cuando la reprogramación haya sido confirmada correctamente.

- CA-016. La reprogramación únicamente deberá realizarse hacia un horario disponible.

Antes de confirmar la reprogramación, el sistema deberá verificar nuevamente la disponibilidad del nuevo horario.

- CA-017. Al cancelar una cita, el sistema deberá actualizar su estado a Cancelada y liberar el horario reservado.

- CA-018. El sistema deberá impedir la reprogramación o cancelación cuando no se cumplan las políticas establecidas por la institución.

Los valores temporales concretos de estas políticas deberán definirse antes de la implementación definitiva.

## Estados de las citas

- CA-019. El sistema deberá permitir únicamente las siguientes transiciones:

  - Programada → Confirmada.
  - Programada → Cancelada.
  - Confirmada → En atención.
  - Confirmada → Cancelada.
  - Confirmada → No asistida.
  - En atención → Finalizada.

- CA-020. El sistema deberá impedir transiciones de estado que no se encuentren autorizadas.

- CA-021. Una cita cancelada o finalizada no deberá volver a un estado anterior.

## Consulta e historial

- CA-022. El paciente autenticado deberá poder consultar únicamente sus propias citas vigentes en estado:

  - Programada.
  - Confirmada.
  - En atención.

- CA-023. El paciente deberá poder consultar su historial de citas:

  - Finalizadas.
  - Canceladas.
  - No asistidas.

- CA-024. El paciente deberá poder filtrar su historial por fecha y estado.

- CA-025. El médico deberá poder consultar únicamente las citas correspondientes a su propia agenda en estado:

  - Programada.
  - Confirmada.
  - En atención.

- CA-026. El médico deberá poder filtrar su agenda por fecha y estado.

- CA-027. El personal de admisión deberá poder consultar la disponibilidad de médicos y horarios dentro de las especialidades habilitadas.

## Atención presencial y virtual

- CA-028. Durante la creación de una cita, el sistema deberá permitir seleccionar la modalidad presencial o virtual cuando ambas se encuentren habilitadas para el médico y la especialidad correspondientes.

- CA-029. Para una cita virtual, el sistema deberá mostrar al paciente y al médico el enlace o información necesaria para acceder al servicio externo de atención virtual.

- CA-030. Una cita presencial no deberá requerir información de acceso al servicio virtual.

## Registro de atención

- CA-031. Al iniciar una atención, el médico deberá poder registrar la fecha y hora de inicio.

Al registrarse correctamente el inicio, el estado de la cita deberá cambiar de:

**Confirmada → En atención**

- CA-032. Al finalizar una atención, el médico deberá poder registrar la fecha y hora de finalización.

Al registrarse correctamente la finalización, el estado deberá cambiar de:

**En atención → Finalizada**

- CA-033. Una cita únicamente deberá poder marcarse como Finalizada después de haber pasado por el estado "En atención".

## Usuarios y roles

- CA-034. El administrador deberá poder registrar, consultar, modificar, activar y desactivar usuarios.

- CA-035. El administrador deberá poder asignar y revocar roles a los usuarios.

- CA-036. Un usuario no deberá poder acceder a funciones que no correspondan a su rol o para las cuales no posea autorización.

## Historial y auditoría

- CA-037. Cada modificación realizada sobre una cita deberá registrar como mínimo:

  - fecha;
  - hora;
  - usuario responsable;
  - acción realizada;
  - valor anterior;
  - valor actualizado.

- CA-038. Cuando se modifique información de una cita, el historial deberá permitir identificar claramente el valor anterior y el nuevo valor registrado.

- CA-039. El historial de modificaciones no deberá eliminarse automáticamente cuando una cita sea cancelada o finalizada.

Para el alcance académico del proyecto, las citas históricas y los registros de auditoría deberán conservarse durante un periodo mínimo de 5 años.

El periodo deberá ser configurable y podrá modificarse posteriormente de acuerdo con una política institucional formal.

## Reglas de superposición

- CA-040. El sistema deberá impedir que un paciente posea dos citas activas que se superpongan en el mismo horario, independientemente de si la operación es realizada:

  - directamente por el paciente;
  - por personal de admisión o recepción en representación del paciente.

La verificación deberá realizarse antes de confirmar una nueva cita o una reprogramación.

## Contingencia de atención virtual

- CA-041. Si se detecta una falla del servicio externo de atención virtual o un problema de conexión durante una cita virtual, el sistema no deberá:

  - eliminar automáticamente la cita;
  - cancelar automáticamente la cita;
  - marcar automáticamente al paciente como No asistido;
  - cambiar automáticamente la modalidad de atención.

- CA-042. Ante una falla detectada del servicio virtual, el sistema deberá mostrar información visible al usuario afectado indicando que existe un problema con la conexión o disponibilidad del servicio.

Cuando paciente y médico se encuentren utilizando el sistema, ambos deberán poder conocer la existencia del incidente.

- CA-043. Cuando una atención virtual no pueda continuar, el médico o el personal autorizado deberá poder gestionar, según corresponda:

  - reprogramación hacia otro horario disponible;
  - cambio a modalidad presencial cuando se encuentre habilitada;
  - resolución manual de la incidencia.

Toda reprogramación o cambio de modalidad deberá quedar registrada en el historial de modificaciones.

---

# Criterios de aceptación de requisitos no funcionales

## Rendimiento y capacidad

- CA-RNF-01. Bajo una carga de hasta 100 usuarios concurrentes, las operaciones principales de:

  - consulta de especialidades;
  - consulta de médicos;
  - consulta de horarios;
  - registro de citas;
  - reprogramación;
  - cancelación;

deberán generar una respuesta del backend en un tiempo máximo de 3 segundos.

La medición deberá realizarse desde que el backend recibe la solicitud hasta que genera la respuesta correspondiente, en un entorno controlado y reproducible.

No deberá incluirse dentro de esta medición la latencia externa propia de la conexión a Internet del usuario.

- CA-RNF-02. El sistema deberá soportar una carga normal inicial de 500 transacciones de negocio por hora.

- CA-RNF-03. Durante periodos de alta demanda, el sistema deberá soportar un pico inmediato de hasta 1.000 transacciones de negocio por hora.

## Escalabilidad

- CA-RNF-04. El diseño del sistema deberá permitir evolucionar desde una carga normal inicial de 500 transacciones por hora hasta un escenario futuro de 5.000 transacciones por hora.

Este escenario representa un crecimiento de 10 veces la carga inicial.

El crecimiento no deberá requerir modificaciones de:

- los requerimientos funcionales;
- las reglas principales del negocio;
- el comportamiento funcional esperado.

Podrán realizarse cambios técnicos de infraestructura, configuración, optimización, índices, replicación o escalamiento.

## Seguridad

- CA-RNF-05. Un usuario no autenticado no deberá poder acceder a funciones protegidas del sistema.

- CA-RNF-06. Un usuario autenticado deberá acceder únicamente a las funciones permitidas por su rol y permisos asignados.

- CA-RNF-07. Las credenciales de los usuarios no deberán almacenarse en texto plano.

- CA-RNF-08. La información transmitida entre el usuario y el sistema deberá utilizar mecanismos de comunicación segura.

## Sesiones

- CA-RNF-09. Cuando una sesión autenticada alcance **15 minutos consecutivos de inactividad**, el sistema deberá:

  - finalizar automáticamente la sesión;
  - impedir el acceso posterior a funciones protegidas con la sesión expirada;
  - solicitar nuevamente autenticación al usuario.

El tiempo deberá mantenerse configurable para permitir modificaciones posteriores de la política de seguridad.

## Disponibilidad y recuperación

- CA-RNF-10. La disponibilidad mensual del sistema deberá ser igual o superior al 99 %.

Los mantenimientos previamente programados y comunicados podrán excluirse del cálculo.

Las interrupciones no programadas deberán contabilizarse como indisponibilidad.

- CA-RNF-11. Los mecanismos de respaldo y recuperación deberán permitir que, ante una falla con pérdida de información, el punto recuperable no sea anterior a 60 minutos respecto al momento del incidente.

Por tanto, deberá cumplirse un:

**RPO máximo de 60 minutos.**

- CA-RNF-12. Ante una falla que requiera recuperación, las operaciones principales del servicio deberán poder restablecerse dentro de un:

**RTO máximo de 120 minutos.**

El procedimiento de recuperación deberá poder ser comprobado mediante una prueba de restauración.

## Integridad

- CA-RNF-13. No deberá existir más de una cita confirmada para el mismo médico, fecha y horario cuando las reservas sean incompatibles.

Cuando existan solicitudes concurrentes sobre el mismo horario, solamente una deberá confirmarse correctamente.

- CA-RNF-14. El sistema deberá rechazar registros que:

  - no contengan los datos obligatorios;
  - presenten formatos inválidos;
  - incumplan las reglas de validación definidas para el dato correspondiente.

Las reglas específicas de formato que todavía no hayan sido definidas deberán establecerse antes de la implementación definitiva.

## Usabilidad

- CA-RNF-15. Las funciones principales deberán poder utilizarse desde:

  - computadora;
  - tableta;
  - teléfono móvil;

sin pérdida de las funciones que correspondan al rol del usuario.

Las funciones principales comprenden, como mínimo:

- consulta de disponibilidad;
- reserva de citas;
- reprogramación;
- cancelación;
- consulta de citas.

- CA-RNF-16. En una prueba de usabilidad, al menos el 80 % de los participantes deberá completar sin ayuda externa las tareas principales de:

  - consultar especialidades;
  - consultar médicos;
  - consultar horarios disponibles;
  - seleccionar fecha y horario;
  - completar la reserva de una cita.

La prueba deberá registrar como mínimo:

- perfil del participante;
- dispositivo utilizado;
- navegador;
- tareas realizadas;
- resultado;
- necesidad o no de asistencia.

## Observabilidad y auditoría

- CA-RNF-17. El sistema deberá registrar eventos operativos relacionados como mínimo con:

  - errores de aplicación;
  - intentos fallidos de autenticación;
  - fallas en operaciones críticas;
  - errores de integración con servicios externos;
  - cambios relevantes de estado necesarios para diagnóstico.

Los registros deberán permitir identificar como mínimo:

- fecha y hora;
- tipo o nivel del evento;
- componente relacionado;
- descripción suficiente para diagnóstico.

- CA-RNF-18. Cada modificación realizada sobre una cita deberá registrar como mínimo:

  - fecha;
  - hora;
  - usuario responsable;
  - acción realizada;
  - valor anterior;
  - valor actualizado.

Los registros de auditoría no deberán eliminarse automáticamente cuando la cita sea cancelada o finalizada.

## Compatibilidad

- CA-RNF-19. Las funciones principales deberán ejecutarse correctamente en las dos últimas versiones estables disponibles, al momento de realizar las pruebas de aceptación, de:

  - Google Chrome;
  - Microsoft Edge;
  - Mozilla Firefox.

Las versiones utilizadas durante la prueba deberán quedar registradas como evidencia.

## Accesibilidad

- CA-RNF-20. Los principales componentes y flujos de la interfaz deberán aplicar como referencia mínima **WCAG 2.1 nivel AA**.

La validación deberá considerar como mínimo:

- navegación mediante teclado;
- contraste adecuado entre texto y fondo;
- asociación de etiquetas con campos de formularios;
- textos alternativos en elementos visuales relevantes;
- mensajes de error comprensibles;
- estructura semántica de encabezados;
- indicador visible de foco;
- utilización de formularios mediante tecnologías de asistencia.

La evaluación deberá realizarse sobre los principales flujos correspondientes a:

- pacientes;
- médicos;
- personal de admisión o recepción;
- administradores.