# Reglas de negocio

- RN-01. Cada cita deberá estar asociada a un paciente y a un médico.

- RN-02. Cada médico deberá estar asociado al menos a una especialidad médica activa.

- RN-03. Una cita solo podrá reservarse en un horario disponible para el médico seleccionado.

- RN-04. Un mismo médico no podrá tener más de una cita confirmada para la misma fecha y horario.

- RN-05. Un paciente no podrá registrar dos citas que se superpongan en el mismo horario.

- RN-06. Antes de confirmar una cita, el sistema deberá verificar nuevamente que el horario seleccionado continúe disponible.

- RN-07. Una cita podrá tener los estados:
  - Programada.
  - Confirmada.
  - En atención.
  - Finalizada.
  - Cancelada.
  - No asistida.

- RN-08. Las transiciones válidas de estado serán:
  - Programada → Confirmada.
  - Programada → Cancelada.
  - Confirmada → En atención.
  - Confirmada → Cancelada.
  - Confirmada → No asistida.
  - En atención → Finalizada.

- RN-09. Una cita cancelada o finalizada no podrá volver a un estado anterior.

- RN-10. Cuando una cita sea confirmada, el horario correspondiente dejará de estar disponible para nuevas reservas.

- RN-11. La reprogramación de una cita solo podrá realizarse hacia un horario disponible del médico correspondiente.

- RN-12. Cuando una cita sea reprogramada, el nuevo horario quedará reservado y el horario anterior deberá quedar nuevamente disponible.

- RN-13. La cancelación de una cita deberá liberar el horario reservado para que pueda ser utilizado por otro paciente.

- RN-14. El paciente únicamente podrá consultar y gestionar las citas asociadas a su propia cuenta.

- RN-15. El médico únicamente podrá consultar las citas asignadas a su agenda.

- RN-16. El personal de admisión o recepción podrá registrar, reprogramar y cancelar citas en representación de un paciente cuando tenga los permisos correspondientes.

- RN-17. El administrador podrá gestionar pacientes, médicos, especialidades, usuarios, roles y configuraciones relacionadas con la gestión de citas.

- RN-18. Una cita destinada a atención virtual deberá estar identificada como virtual y deberá contener la información necesaria para acceder al servicio externo correspondiente.

- RN-19. Una cita presencial no deberá requerir información de acceso virtual.

- RN-20. Una cita solo podrá marcarse como finalizada después de haber sido registrada previamente como "En atención".

- RN-21. Una cita podrá marcarse como "No asistida" cuando el paciente no se presente dentro del tiempo de tolerancia definido por la institución.

- RN-22. Las citas canceladas, finalizadas y no asistidas deberán conservarse en el historial del paciente.

- RN-23. Toda modificación relevante realizada sobre una cita deberá registrar como mínimo la fecha, hora, usuario responsable y acción realizada.

- RN-24. Cuando se modifique información de una cita, el historial deberá conservar el valor anterior y el valor actualizado.

- RN-25. El historial de modificaciones de las citas no deberá eliminarse cuando una cita sea cancelada o finalizada.

- RN-26. Las políticas de tiempo mínimo permitido para cancelar o reprogramar una cita serán definidas por la institución antes de la puesta en producción.

- RN-27. El tiempo de tolerancia para determinar que un paciente no asistió a una cita será definido por la institución antes de la puesta en producción.