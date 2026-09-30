# Reglas de negocio

## Gestión de citas

- RN-01. Cada cita deberá estar asociada a un paciente y a un médico.

- RN-02. Cada médico deberá estar asociado al menos a una especialidad médica activa.

- RN-03. Una cita solo podrá reservarse en un horario disponible para el médico seleccionado.

- RN-04. No podrá confirmarse más de una cita para el mismo médico, fecha y horario cuando las reservas utilicen el mismo bloque de disponibilidad.

- RN-05. Un paciente no podrá tener dos citas que se superpongan en el mismo horario.

Esta regla deberá aplicarse independientemente de si la operación es realizada:

  - directamente por el paciente;
  - por personal de admisión o recepción en representación del paciente.

- RN-06. Antes de confirmar una cita, el sistema deberá verificar nuevamente que el horario seleccionado continúe disponible.

Cuando dos o más solicitudes intenten reservar simultáneamente el mismo horario para el mismo médico:

  - solamente una podrá confirmarse;
  - las demás deberán ser rechazadas indicando que el horario ya no se encuentra disponible.

- RN-07. Una cita podrá tener únicamente los siguientes estados:

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

- RN-10. Cuando una cita sea confirmada, el horario correspondiente deberá dejar de estar disponible para nuevas reservas incompatibles.

---

## Reprogramación y cancelación

- RN-11. La reprogramación de una cita solo podrá realizarse hacia un horario disponible del médico correspondiente.

Antes de confirmar la reprogramación deberá verificarse nuevamente que el nuevo horario continúe disponible.

- RN-12. Cuando una reprogramación sea confirmada correctamente:

  - el nuevo horario deberá quedar reservado;
  - el horario anterior deberá quedar nuevamente disponible.

El horario anterior no deberá liberarse antes de que la nueva reserva haya sido confirmada correctamente.

- RN-13. La cancelación de una cita deberá liberar el horario reservado para que pueda ser utilizado por otro paciente.

- RN-14. El paciente únicamente podrá consultar y gestionar las citas asociadas a su propia cuenta.

- RN-15. El médico únicamente podrá consultar las citas correspondientes a su propia agenda.

- RN-16. El personal de admisión o recepción podrá registrar, reprogramar y cancelar citas en representación de un paciente cuando tenga los permisos correspondientes.

Las operaciones realizadas por el personal de admisión deberán cumplir las mismas reglas de:

  - disponibilidad;
  - superposición;
  - concurrencia;
  - reprogramación;
  - cancelación;

aplicables a las operaciones realizadas directamente por un paciente.

---

## Administración del sistema

- RN-17. El administrador podrá gestionar:

  - pacientes;
  - médicos;
  - especialidades;
  - usuarios;
  - roles;
  - configuraciones relacionadas con la gestión de citas;

de acuerdo con los permisos establecidos para su rol.

---

## Modalidad presencial y virtual

- RN-18. Una cita destinada a atención virtual deberá estar identificada como virtual y deberá contener la información necesaria para acceder al servicio externo correspondiente.

- RN-19. Una cita presencial no deberá requerir información de acceso al servicio de atención virtual.

---

## Inicio y finalización de la atención

- RN-20. Una cita solo podrá marcarse como Finalizada después de haber sido registrada previamente en estado "En atención".

El médico responsable de la cita será quien registre el inicio de la atención.

Al registrar el inicio se realizará la transición:

**Confirmada → En atención**

El médico responsable será también quien registre la finalización de la atención.

Al registrar la finalización se realizará la transición:

**En atención → Finalizada**

El sistema deberá conservar la fecha y hora correspondientes al inicio y a la finalización de la atención.

---

## No asistencia

- RN-21. Una cita podrá marcarse como "No asistida" cuando el paciente no se presente dentro del tiempo de tolerancia definido para el sistema.

Una falla atribuible al servicio externo de atención virtual o a un problema técnico de conexión no deberá utilizarse por sí sola para marcar automáticamente al paciente como "No asistido".

---

## Historial de citas

- RN-22. Las citas:

  - Canceladas.
  - Finalizadas.
  - No asistidas.

deberán conservarse en el historial del paciente.

Para el alcance académico del proyecto se establece un periodo mínimo inicial de conservación de **5 años** desde la fecha correspondiente.

Este periodo deberá poder configurarse y podrá ser sustituido posteriormente por una política institucional formal.

---

## Auditoría de modificaciones

- RN-23. Toda modificación relevante realizada sobre una cita deberá registrar como mínimo:

  - fecha;
  - hora;
  - usuario responsable;
  - acción realizada;
  - valor anterior;
  - valor actualizado.

- RN-24. Cuando se modifique información de una cita, el historial deberá permitir identificar claramente:

  - la información existente antes del cambio;
  - la información registrada después del cambio.

- RN-25. El historial de modificaciones de las citas no deberá eliminarse automáticamente cuando una cita sea cancelada o finalizada.

Para el alcance académico del proyecto, los registros de auditoría deberán conservarse durante un periodo mínimo de **5 años** desde la fecha del evento registrado.

El periodo deberá ser configurable para permitir su adaptación posterior a una política institucional.

---

## Políticas temporales pendientes de definición

- RN-26. Las políticas de tiempo mínimo permitido para cancelar o reprogramar una cita deberán definirse antes de la puesta en producción.

Mientras estos valores no hayan sido formalmente definidos, deberán permanecer como parámetros pendientes de configuración y no deberán asumirse arbitrariamente durante la implementación.

- RN-27. El tiempo de tolerancia utilizado para determinar que un paciente no asistió a una cita deberá definirse antes de la puesta en producción.

Mientras no exista una definición institucional, el sistema deberá mantener este valor como parámetro pendiente de configuración.

- RN-28. El periodo máximo de anticipación permitido para reservar una cita futura deberá definirse antes de la puesta en producción.

El valor deberá poder configurarse sin modificar la lógica principal del sistema.

---

## Contingencia de atención virtual

- RN-29. Cuando una cita virtual no pueda desarrollarse debido a una falla del servicio externo o a un problema de conexión detectado, la cita no deberá:

  - eliminarse automáticamente;
  - cancelarse automáticamente;
  - cambiar automáticamente a modalidad presencial;
  - marcarse automáticamente como "No asistida".

- RN-30. Cuando se detecte una falla relacionada con una atención virtual, el sistema deberá conservar:

  - paciente;
  - médico;
  - fecha;
  - hora;
  - modalidad;
  - estado actual de la cita;
  - fecha y hora del incidente;
  - información disponible sobre la falla.

- RN-31. Ante una falla detectada durante una cita virtual, el sistema deberá mostrar información visible al usuario afectado indicando la existencia del problema.

Cuando paciente y médico se encuentren utilizando el sistema, ambos deberán poder conocer que se produjo el incidente.

- RN-32. Cuando la atención virtual no pueda continuar, el médico o el personal de admisión autorizado podrá, según corresponda:

  - reprogramar la cita hacia otro horario disponible;
  - cambiar la cita a modalidad presencial cuando dicha modalidad se encuentre habilitada y exista conformidad de las partes;
  - mantener la cita pendiente de resolución manual.

Cualquier reprogramación o cambio de modalidad deberá quedar registrado en el historial de modificaciones.

---

## Relación entre Médico y Usuario

- RN-33. Médico y Usuario representan conceptos diferentes dentro del sistema.

El registro de un Médico contendrá la información profesional requerida para:

  - especialidades;
  - horarios;
  - disponibilidad;
  - citas;
  - agenda.

La entidad Usuario será utilizada para:

  - autenticación;
  - autorización;
  - roles;
  - acceso al sistema;
  - auditoría.

- RN-34. Un Médico podrá existir sin una cuenta de Usuario asociada.

Cuando el médico necesite acceder a funciones protegidas del sistema, deberá disponer de una cuenta de Usuario con el rol correspondiente.

Un Médico podrá estar asociado como máximo a una cuenta de Usuario para realizar funciones clínicas.

Una cuenta de Usuario utilizada para realizar funciones de Médico deberá estar asociada a un único Médico.

- RN-35. La desactivación de una cuenta de Usuario asociada a un Médico no deberá eliminar:

  - el registro profesional del Médico;
  - las citas históricas;
  - la información de auditoría asociada.

La desactivación del Médico tampoco deberá eliminar automáticamente su información histórica.

El tratamiento de las citas futuras existentes al momento de desactivar un Médico continuará sujeto a la decisión pendiente correspondiente.

---

## Reglas pendientes de definición institucional o funcional

Las siguientes reglas todavía requieren una decisión posterior:

- tiempo mínimo para cancelar una cita;
- tiempo mínimo para reprogramar una cita;
- tiempo de tolerancia para declarar una cita como No asistida;
- anticipación máxima permitida para reservar;
- comportamiento de las citas futuras al desactivar un Médico;
- nivel exacto en el que se habilitará la modalidad virtual;
- permisos definitivos del personal de admisión.

Estas condiciones deberán resolverse antes de la implementación definitiva y mantenerse trazables con los requerimientos y decisiones del proyecto.