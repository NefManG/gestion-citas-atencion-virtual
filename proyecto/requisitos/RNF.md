# Requerimientos No Funcionales

## Rendimiento

- RNF-01. El sistema deberá responder a las operaciones habituales de consulta, registro, reprogramación y cancelación de citas en un tiempo máximo de 3 segundos bajo condiciones normales de operación.

## Capacidad

- RNF-02. El sistema deberá soportar múltiples usuarios concurrentes realizando consultas y reservas de citas sin afectar significativamente el tiempo de respuesta.

## Escalabilidad

- RNF-03. El sistema deberá permitir incrementar la cantidad de usuarios, médicos y citas gestionadas sin requerir cambios significativos en su funcionamiento.

## Seguridad

- RNF-04. El sistema deberá requerir autenticación para acceder a las funciones correspondientes a pacientes, médicos, personal de admisión y administradores.

- RNF-05. El sistema deberá restringir el acceso a la información y funciones de acuerdo con el rol y los permisos asignados a cada usuario.

- RNF-06. El sistema deberá proteger la información personal de los pacientes y evitar el acceso no autorizado a sus datos.

## Disponibilidad

- RNF-07. El sistema deberá mantener una disponibilidad mensual mínima del 99 %, excluyendo los periodos de mantenimiento programado.

## Recuperación

- RNF-08. El sistema deberá contar con mecanismos de respaldo de la información que permitan recuperar los datos ante una pérdida o falla del sistema.

## Integridad

- RNF-09. El sistema deberá garantizar que un mismo horario no pueda ser reservado simultáneamente por más de un paciente para el mismo médico.

- RNF-10. El sistema deberá validar la información obligatoria antes de registrar pacientes, médicos, horarios o citas.

## Usabilidad

- RNF-11. La interfaz del sistema deberá ser adaptable a computadoras, tabletas y teléfonos móviles.

- RNF-12. Las funciones principales de consulta y reserva de citas deberán presentarse de forma clara y comprensible para los usuarios.

## Mantenibilidad

- RNF-13. El sistema deberá organizar sus componentes de manera que permita realizar modificaciones o incorporar nuevas funcionalidades sin afectar innecesariamente otras partes del sistema.

## Observabilidad

- RNF-14. El sistema deberá registrar eventos relevantes y errores que permitan identificar problemas durante su funcionamiento.

## Auditoría

- RNF-15. El sistema deberá registrar la fecha, hora y usuario responsable de las modificaciones realizadas sobre las citas.

## Portabilidad

- RNF-16. El sistema deberá poder ser utilizado desde navegadores web modernos en diferentes sistemas operativos.