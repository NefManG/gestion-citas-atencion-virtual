# Requerimientos Funcionales

## Gestión de pacientes

- RF-01. El sistema deberá permitir registrar pacientes con nombre, apellidos, documento de identidad, fecha de nacimiento, teléfono y correo electrónico. El nombre, los apellidos, el documento de identidad y el teléfono serán obligatorios.

## Gestión de médicos y especialidades

- RF-02. El sistema deberá permitir al administrador registrar, consultar, modificar y cambiar el estado activo o inactivo de los médicos.

- RF-03. El sistema deberá permitir al administrador registrar, consultar, modificar y cambiar el estado activo o inactivo de las especialidades médicas.

- RF-04. El sistema deberá permitir asociar uno o más médicos con una o más especialidades médicas.

## Gestión de horarios

- RF-05. El sistema deberá permitir al médico y al personal autorizado definir y actualizar los días y bloques horarios disponibles para atención.

- RF-06. El sistema deberá permitir a los pacientes consultar las especialidades médicas que se encuentren activas y tengan médicos asociados.

- RF-07. El sistema deberá permitir a los pacientes consultar los médicos activos asociados a una especialidad y que tengan horarios disponibles para atención.

- RF-08. El sistema deberá permitir consultar los horarios futuros disponibles de un médico dentro del período habilitado para reservas.

## Gestión de citas

- RF-09. El sistema deberá permitir al paciente reservar una cita seleccionando especialidad, médico, fecha y horario disponible. Antes de confirmar la reserva, el sistema deberá verificar nuevamente que el horario continúe disponible.

- RF-10. El sistema deberá permitir al personal de admisión o recepción registrar una cita en representación de un paciente, de acuerdo con los permisos asignados a su rol.

- RF-11. El sistema deberá permitir al paciente y al personal de admisión autorizado reprogramar una cita hacia otro horario disponible del médico correspondiente, respetando las políticas de reprogramación definidas por la institución.

- RF-12. El sistema deberá permitir al paciente y al personal de admisión autorizado cancelar una cita, respetando las políticas de cancelación definidas por la institución.

## Estados de la cita

- RF-13. El sistema deberá permitir gestionar los estados de una cita: programada, confirmada, en atención, finalizada, cancelada y no asistida.

Las transiciones permitidas serán:

- Programada → Confirmada.
- Programada → Cancelada.
- Confirmada → En atención.
- Confirmada → Cancelada.
- Confirmada → No asistida.
- En atención → Finalizada.

Una cita cancelada o finalizada no podrá volver a un estado anterior.

## Consulta de citas

- RF-14. El sistema deberá permitir al paciente consultar sus citas futuras programadas o confirmadas.

- RF-15. El sistema deberá permitir al paciente consultar su historial de citas finalizadas, canceladas y no asistidas, pudiendo filtrar los resultados por fecha y estado.

- RF-16. El sistema deberá permitir al médico consultar su agenda de citas programadas y confirmadas, pudiendo filtrar las citas por fecha y estado.

- RF-17. El sistema deberá permitir al personal de admisión o recepción consultar la disponibilidad de médicos y horarios de las especialidades habilitadas para la gestión de citas.

## Atención presencial y virtual

- RF-18. Durante el registro de una cita, el sistema deberá permitir identificar si la atención será presencial o virtual, siempre que la modalidad esté habilitada para el médico y la especialidad seleccionados.

- RF-19. Para una cita virtual, el sistema deberá proporcionar al paciente y al médico la información necesaria para acceder al servicio externo de atención virtual, como enlace de acceso e instrucciones correspondientes.

## Registro de la atención

- RF-20. El sistema deberá permitir al médico registrar el inicio y la finalización de una atención, almacenando la fecha y hora correspondientes.

## Usuarios y roles

- RF-21. El sistema deberá permitir al administrador registrar, consultar, modificar, activar y desactivar usuarios, así como asignar o revocar los roles correspondientes.

El administrador podrá gestionar los roles de paciente, médico, personal de admisión o recepción y administrador.

## Historial de modificaciones

- RF-22. El sistema deberá permitir consultar el historial de modificaciones realizadas sobre una cita, registrando como mínimo la fecha, hora, usuario responsable, acción realizada, valor anterior y valor actualizado.