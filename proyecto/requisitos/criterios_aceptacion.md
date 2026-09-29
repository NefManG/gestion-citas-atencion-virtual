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

- CA-012. Si dos usuarios intentan reservar simultáneamente el mismo horario para el mismo médico, únicamente una reserva deberá confirmarse.

- CA-013. Una vez confirmada una cita, el horario correspondiente deberá dejar de mostrarse como disponible.

- CA-014. El personal de admisión deberá poder registrar una cita en representación de un paciente cuando tenga los permisos correspondientes.

## Reprogramación y cancelación

- CA-015. Al reprogramar una cita, el sistema deberá reservar el nuevo horario y liberar el horario anterior.

- CA-016. La reprogramación únicamente deberá realizarse hacia un horario disponible.

- CA-017. Al cancelar una cita, el sistema deberá actualizar su estado a cancelada y liberar el horario reservado.

- CA-018. El sistema deberá impedir la reprogramación o cancelación cuando no se cumplan las políticas establecidas por la institución.

## Estados de las citas

- CA-019. El sistema deberá permitir las siguientes transiciones:

  - Programada → Confirmada.
  - Programada → Cancelada.
  - Confirmada → En atención.
  - Confirmada → Cancelada.
  - Confirmada → No asistida.
  - En atención → Finalizada.

- CA-020. El sistema deberá impedir transiciones de estado que no se encuentren autorizadas.

- CA-021. Una cita cancelada o finalizada no deberá volver a un estado anterior.

## Consulta e historial

- CA-022. El paciente autenticado deberá poder consultar únicamente sus propias citas futuras programadas o confirmadas.

- CA-023. El paciente deberá poder consultar su historial de citas finalizadas, canceladas y no asistidas.

- CA-024. El paciente deberá poder filtrar su historial por fecha y estado.

- CA-025. El médico deberá poder consultar únicamente las citas correspondientes a su agenda.

- CA-026. El médico deberá poder filtrar su agenda por fecha y estado.

- CA-027. El personal de admisión deberá poder consultar la disponibilidad de médicos y horarios dentro de las especialidades habilitadas.

## Atención presencial y virtual

- CA-028. Durante la creación de una cita, el sistema deberá permitir seleccionar la modalidad presencial o virtual cuando ambas estén disponibles.

- CA-029. Para una cita virtual, el sistema deberá mostrar al paciente y al médico el enlace o información necesaria para acceder al servicio externo de atención virtual.

- CA-030. Una cita presencial no deberá requerir información de acceso virtual.

## Registro de atención

- CA-031. Al iniciar una atención, el médico deberá poder registrar la fecha y hora de inicio.

- CA-032. Al finalizar la atención, el médico deberá poder registrar la fecha y hora de finalización.

- CA-033. Una cita únicamente deberá poder marcarse como finalizada después de haber pasado por el estado "En atención".

## Usuarios y roles

- CA-034. El administrador deberá poder registrar, consultar, modificar, activar y desactivar usuarios.

- CA-035. El administrador deberá poder asignar y revocar roles a los usuarios.

- CA-036. Un usuario no deberá poder acceder a funciones que no correspondan a su rol.

## Historial y auditoría

- CA-037. Cada modificación de una cita deberá registrar como mínimo fecha, hora, usuario responsable y acción realizada.

- CA-038. Cuando se modifique información de una cita, el historial deberá conservar el valor anterior y el valor actualizado.

- CA-039. El historial de modificaciones no deberá eliminarse cuando una cita sea cancelada o finalizada.

# Criterios de aceptación de requisitos no funcionales

## Rendimiento y capacidad

- CA-RNF-01. Bajo una carga de hasta 100 usuarios concurrentes, las operaciones principales de consulta, registro, reprogramación y cancelación deberán responder en un tiempo máximo de 3 segundos.

- CA-RNF-02. El sistema deberá soportar una carga objetivo de 500 transacciones de negocio por hora.

- CA-RNF-03. Durante periodos de alta demanda, el sistema deberá soportar hasta 1.000 transacciones de negocio por hora.

## Escalabilidad

- CA-RNF-04. El diseño del sistema deberá permitir evaluar un escenario de crecimiento de hasta 10 veces la carga inicial sin modificar las reglas funcionales del sistema.

## Seguridad

- CA-RNF-05. Un usuario no autenticado no deberá poder acceder a funciones protegidas.

- CA-RNF-06. Un usuario autenticado deberá acceder únicamente a las funciones permitidas por su rol.

- CA-RNF-07. Las credenciales de los usuarios no deberán almacenarse en texto plano.

- CA-RNF-08. La información transmitida entre el usuario y el sistema deberá utilizar mecanismos de comunicación segura.

## Sesiones

- CA-RNF-09. Cuando una sesión supere el periodo de inactividad configurado, el sistema deberá cerrarla automáticamente y solicitar nuevamente autenticación.

## Disponibilidad y recuperación

- CA-RNF-10. La disponibilidad mensual del sistema deberá ser igual o superior al 99 %, excluyendo mantenimientos programados.

- CA-RNF-11. Los mecanismos de respaldo y recuperación deberán permitir una pérdida máxima de información equivalente a un RPO de 60 minutos.

- CA-RNF-12. Ante una falla que requiera recuperación, el servicio deberá poder restablecerse dentro de un RTO máximo de 120 minutos.

## Integridad

- CA-RNF-13. No deberá existir más de una cita confirmada para el mismo médico, fecha y horario.

- CA-RNF-14. El sistema deberá rechazar registros que no contengan los datos obligatorios o que presenten formatos inválidos.

## Usabilidad

- CA-RNF-15. Las funciones principales deberán poder utilizarse desde computadora, tableta y teléfono móvil sin pérdida de funcionalidad.

- CA-RNF-16. En una prueba de usabilidad, al menos el 80 % de los participantes deberá poder consultar disponibilidad y reservar una cita sin ayuda externa.

## Observabilidad y auditoría

- CA-RNF-17. El sistema deberá registrar errores de aplicación, intentos fallidos de autenticación, cambios de estado de citas y fallas en operaciones críticas.

- CA-RNF-18. Cada modificación de una cita deberá registrar fecha, hora y usuario responsable.

## Compatibilidad

- CA-RNF-19. Las funciones principales deberán ejecutarse correctamente en las dos últimas versiones estables de Google Chrome, Microsoft Edge y Mozilla Firefox.