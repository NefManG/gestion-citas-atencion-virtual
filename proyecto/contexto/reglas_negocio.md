# Reglas de negocio

- RN-01. Cada cita deberá estar asociada a un paciente y a un médico.

- RN-02. Cada médico deberá estar asociado al menos a una especialidad médica.

- RN-03. Una cita solo podrá reservarse en un horario que se encuentre disponible para el médico seleccionado.

- RN-04. Un mismo médico no podrá tener más de una cita programada en el mismo horario.

- RN-05. Un paciente no podrá registrar dos citas en el mismo horario.

- RN-06. Cuando una cita sea confirmada, el horario correspondiente dejará de estar disponible para nuevas reservas.

- RN-07. Una cita podrá tener los estados: programada, confirmada, en atención, finalizada, cancelada o no asistida.

- RN-08. Una cita cancelada no podrá cambiar posteriormente al estado de finalizada.

- RN-09. La reprogramación de una cita solo podrá realizarse hacia un horario disponible del médico seleccionado.

- RN-10. Cuando una cita sea reprogramada, el horario anteriormente reservado deberá quedar disponible nuevamente.

- RN-11. La cancelación de una cita deberá liberar el horario reservado para que pueda ser utilizado por otro paciente.

- RN-12. El paciente solo podrá consultar y gestionar las citas asociadas a su cuenta.

- RN-13. El médico solo podrá consultar las citas asignadas a su agenda.

- RN-14. El personal de admisión o recepción podrá registrar, reprogramar y cancelar citas en representación de un paciente autorizado.

- RN-15. El administrador podrá gestionar pacientes, médicos, especialidades, usuarios y configuraciones relacionadas con la gestión de citas.

- RN-16. Una cita destinada a atención virtual deberá identificarse como tal y disponer de la información necesaria para que el paciente y el médico puedan acceder a la atención.

- RN-17. Toda modificación relevante realizada sobre una cita deberá conservar la fecha, hora y usuario responsable del cambio.

- RN-18. Una cita solo podrá marcarse como finalizada cuando haya sido previamente registrada como en atención.

- RN-19. Las citas no asistidas deberán conservarse en el historial del paciente.

- RN-20. El sistema deberá conservar el historial de cambios realizados sobre las citas para permitir su seguimiento.