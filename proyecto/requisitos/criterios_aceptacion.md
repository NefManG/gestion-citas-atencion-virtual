# Criterios de aceptación

- CA-001. Al registrar un paciente con todos los datos obligatorios válidos, el sistema deberá guardar la información y permitir su consulta posterior.

- CA-002. Al seleccionar una especialidad médica, el sistema deberá mostrar los médicos asociados a dicha especialidad que tengan disponibilidad registrada.

- CA-003. Al seleccionar un médico, el sistema deberá mostrar únicamente los horarios disponibles para reserva.

- CA-004. Cuando un paciente reserve una cita en un horario disponible, el sistema deberá registrar la cita y evitar que ese mismo horario pueda ser reservado nuevamente para el mismo médico.

- CA-005. Cuando una cita sea reprogramada, el nuevo horario deberá quedar reservado y el horario anterior deberá quedar nuevamente disponible.

- CA-006. Cuando una cita sea cancelada, el sistema deberá actualizar su estado a cancelada y liberar el horario correspondiente.

- CA-007. El paciente autenticado deberá poder consultar únicamente sus propias citas programadas y su historial de citas.

- CA-008. El médico autenticado deberá poder consultar las citas correspondientes a su agenda.

- CA-009. El personal de admisión o recepción deberá poder registrar, reprogramar y cancelar citas en representación de un paciente, de acuerdo con los permisos asignados.

- CA-010. El sistema no deberá permitir registrar dos citas para el mismo médico en la misma fecha y horario.

- CA-011. Cuando una cita cambie de estado, el sistema deberá conservar la fecha, hora y usuario responsable de la modificación.

- CA-012. Una cita marcada como cancelada no deberá poder cambiar posteriormente al estado finalizada.

- CA-013. Para una cita identificada como virtual, el sistema deberá mostrar al paciente y al médico la información necesaria para acceder a la atención virtual.

- CA-014. Cuando una atención finalice, el médico deberá poder registrar la cita como finalizada y esta deberá permanecer disponible en el historial correspondiente.

- CA-015. El sistema deberá impedir que un usuario acceda a funciones que no correspondan al rol o permisos que tiene asignados.