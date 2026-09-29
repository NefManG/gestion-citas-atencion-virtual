# Supuestos y restricciones

## Supuestos

- S-01. Los pacientes, médicos y personal autorizado contarán con acceso a Internet para utilizar el sistema.

- S-02. Los médicos tendrán horarios de atención previamente definidos para permitir la reserva de citas.

- S-03. Los pacientes deberán contar con una cuenta registrada para acceder a las funciones que requieran identificación.

- S-04. Los usuarios utilizarán un navegador web compatible para acceder al sistema.

- S-05. La información de médicos, especialidades y horarios será registrada y mantenida actualizada por personal autorizado.

- S-06. Para las citas virtuales, el paciente y el médico contarán con acceso a Internet y a un dispositivo compatible con el servicio externo utilizado.

## Restricciones técnicas

- RT-01. El sistema será accesible mediante navegador web.

- RT-02. El sistema deberá poder utilizarse desde computadoras, tabletas y teléfonos móviles.

- RT-03. La atención virtual dependerá de la disponibilidad de conexión a Internet de los participantes.

- RT-04. En la primera versión no se desarrollará una plataforma propia de videollamadas.

- RT-05. Cuando se habilite una cita virtual, el sistema utilizará un servicio externo para proporcionar el acceso a la atención.

- RT-06. El sistema deberá diferenciar los permisos de acceso de acuerdo con el rol asignado a cada usuario.

- RT-07. La primera versión del sistema funcionará mediante una aplicación web y no incluirá una aplicación móvil nativa.

## Restricciones organizacionales

- RO-01. La disponibilidad de una cita dependerá de los horarios definidos para cada médico.

- RO-02. El personal de admisión o recepción únicamente podrá realizar las operaciones permitidas por su rol.

- RO-03. La primera versión no incluirá facturación, pagos ni gestión contable.

- RO-04. La primera versión no incluirá historias clínicas, diagnósticos médicos ni prescripción de medicamentos.

- RO-05. La primera versión no incluirá gestión de farmacia, laboratorio, internación ni emergencias.

- RO-06. Las citas virtuales únicamente podrán habilitarse para médicos y especialidades que permitan dicha modalidad.

- RO-07. La primera versión no requerirá integración obligatoria con otros sistemas hospitalarios.

## Decisiones pendientes

- DP-01. Definir qué servicio externo se utilizará para las atenciones virtuales.

- DP-02. Definir si se incorporarán posteriormente servicios de correo electrónico o mensajería para confirmaciones y recordatorios de citas.

- DP-03. Definir si se permitirá la sincronización de citas con calendarios externos.

- DP-04. Definir las tecnologías que se utilizarán para backend, frontend, base de datos e infraestructura.

- DP-05. Definir el tiempo mínimo permitido para cancelar o reprogramar una cita.

- DP-06. Definir el tiempo de tolerancia antes de marcar una cita como no asistida.

- DP-07. Definir el tiempo máximo de inactividad antes del cierre automático de sesión.

- DP-08. Definir el periodo máximo de anticipación con el que un paciente podrá reservar una cita.