# Requerimientos No Funcionales

## Rendimiento

- RNF-01. El sistema deberá responder a las operaciones de consulta de especialidades, consulta de médicos, consulta de horarios, registro de citas, reprogramación y cancelación en un tiempo máximo de 3 segundos, considerando una carga de hasta 100 usuarios concurrentes.

## Capacidad

- RNF-02. El sistema deberá soportar inicialmente hasta 100 usuarios concurrentes y una carga objetivo de 500 transacciones de negocio por hora, considerando un pico de hasta 1.000 transacciones por hora.

## Escalabilidad

- RNF-03. El sistema deberá permitir incrementar hasta 10 veces la carga inicial de transacciones sin requerir modificaciones del modelo funcional del sistema.

## Seguridad

- RNF-04. El sistema deberá requerir autenticación para acceder a las funciones correspondientes a pacientes, médicos, personal de admisión y administradores.

- RNF-05. El sistema deberá restringir el acceso a la información y funciones de acuerdo con el rol asignado a cada usuario.

- RNF-06. El sistema deberá proteger la información personal de pacientes y usuarios mediante controles de acceso, transmisión segura de información y almacenamiento protegido de credenciales.

## Gestión de sesiones

- RNF-07. El sistema deberá finalizar automáticamente la sesión de un usuario después de un periodo prolongado de inactividad definido por la configuración de seguridad.

## Disponibilidad

- RNF-08. El sistema deberá mantener una disponibilidad mensual mínima del 99 %, excluyendo los periodos de mantenimiento previamente programados.

## Recuperación y respaldo

- RNF-09. El sistema deberá implementar mecanismos de respaldo y recuperación que permitan cumplir con un RPO máximo de 60 minutos.

- RNF-10. El sistema deberá permitir restablecer el servicio dentro de un RTO máximo de 120 minutos ante una falla que requiera recuperación.

## Integridad y concurrencia

- RNF-11. El sistema deberá garantizar que un mismo horario no pueda ser reservado simultáneamente por más de un paciente para el mismo médico.

- RNF-12. Antes de registrar pacientes, médicos, horarios o citas, el sistema deberá validar la presencia y formato de los datos obligatorios correspondientes.

## Usabilidad

- RNF-13. La interfaz deberá permitir utilizar las funciones principales desde computadoras, tabletas y teléfonos móviles sin pérdida de funcionalidad.

- RNF-14. En una prueba de usabilidad, al menos el 80 % de los usuarios deberá poder consultar disponibilidad y reservar una cita sin asistencia externa.

## Accesibilidad

- RNF-15. La interfaz deberá considerar criterios básicos de accesibilidad para facilitar su utilización por personas con diferentes capacidades.

## Mantenibilidad

- RNF-16. Los componentes del sistema deberán mantenerse organizados de forma modular, permitiendo realizar cambios en una funcionalidad sin requerir modificaciones innecesarias en funcionalidades no relacionadas.

## Observabilidad

- RNF-17. El sistema deberá registrar como mínimo errores de aplicación, intentos fallidos de autenticación, cambios de estado de citas y fallas en operaciones críticas.

## Auditoría

- RNF-18. El sistema deberá registrar la fecha, hora y usuario responsable de las modificaciones realizadas sobre las citas.

## Portabilidad y compatibilidad

- RNF-19. El sistema deberá funcionar mediante navegador web en las dos últimas versiones estables de Google Chrome, Microsoft Edge y Mozilla Firefox.