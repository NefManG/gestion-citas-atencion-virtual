# Análisis de Requerimientos — Sistema de Gestión de Citas y Atención Virtual

## Resumen Ejecutivo

Análisis exhaustivo de los requerimientos funcionales, no funcionales, reglas de negocio, restricciones y decisiones pendientes del **Sistema Web de Gestión de Citas y Atención Virtual para el Hospital Boliviano Español**, aplicando la metodología requirements-analysis sin selección tecnológica.

Este documento amplía el análisis previo detallando individualmente cada observación detectada, clasificada por tipo (ambigüedad, inconsistencia, omisión, duplicidad, falta de verificabilidad, riesgo), con su identificador, categorías, requisitos relacionados, descripción concreta y fundamento del por qué representa cada problema.

**Nota**: Este análisis no decide la validez de ninguna observación, no modifica requerimientos y no selecciona tecnología. Su propósito es documentar hallazgos para validación por el equipo estudiantil.

---

## Listado Completo de Observaciones

### OBS-01 | AMBIGÜEDAD

- **Categoría**: Ambigüedad
- **Relacionado con**: RF-19, RN-18, RN-19, RT-05, DP-01
- **Descripción**: RF-19 establece que "el sistema deberá proporcionar al paciente y al médico la información necesaria para acceder al servicio externo de atención virtual, como enlace de acceso e instrucciones correspondientes". Sin embargo, el servicio externo específico no está definido (DP-01 pendiente). RN-18 requiere que una cita virtual "contenga la información necesaria para acceder al servicio externo correspondiente". RT-05 señala que "el sistema utilizará un servicio externo" sin especificar cuál.
- **Fundamento**: Es una ambigüedad porque la naturaleza de la "información necesaria" (enlace URL, token, credenciales, instrucciones de conexión, plataforma específica) depende directamente del proveedor de videollamadas seleccionado. Sin definir el servicio, no es posible determinar qué datos almacenar, cómo generarlos, ni cómo validar que el paciente y médico puedan acceder efectivamente. La frase "como enlace de acceso e instrucciones correspondientes" es vaga — ¿qué instrucciones? ¿de qué plataforma?

---

### OBS-02 | AMBIGÜEDAD

- **Categoría**: Ambigüedad
- **Relacionado con**: RF-11, RF-12, RN-11, RN-12, RN-13, DP-05, DP-06
- **Descripción**: RF-11 permite reprogramar una cita "respetando las políticas de reprogramación definidas por la institución". RF-12 permite cancelar "respetando las políticas de cancelación definidas por la institución". RN-11 y RN-12 refuerzan lo mismo. RN-26 señala "Las políticas de tiempo mínimo permitido para cancelar o reprogramar una cita serán definidas por la institución antes de la puesta en producción". RN-21 indica que una cita puede marcarse como "No asistida" cuando el paciente no se presente "dentro del tiempo de tolerancia definido por la institución". Ninguna de estas políticas tiene valores numéricos definidos (DP-05, DP-06, DP-07 pendientes).
- **Fundamento**: Es una ambigüedad porque sin conocer el tiempo mínimo de anticipación para cancelar/reprogramar (DP-05), el tiempo de tolerancia para no-asistencia (DP-06) y el tiempo de inactividad de sesión (DP-07), el sistema no puede implementar las validaciones que RF-11/12 requieren. El requerimiento es funcionalmente válido pero operativamente indefinido: no se sabe qué acción rechazar, qué acción permitir, ni en qué ventana temporal. Esto genera un requisito huérfano.

---

### OBS-03 | AMBIGÜEDAD

- **Categoría**: Ambigüedad
- **Relacionado con**: RF-09, RF-10, RF-11, RN-05, RN-16
- **Descripción**: RN-05 establece que "un paciente no podrá registrar dos citas que se superpongan en el mismo horario". Sin embargo, RF-10 permite al personal de admisión registrar citas "en representación de un paciente". RF-11 permite al personal de admisión reprogramar citas. RN-16 refuerza que el personal de admisión puede "registrar, reprogramar y cancelar citas en representación de un paciente". No está claro si la restricción de no-superposición (RN-05) se aplica cuando las citas son creadas/reprogramadas por el personal de admisión o solo cuando el paciente actúa directamente.
- **Fundamento**: Es una ambigüedad porque si RN-05 solo aplica a acciones del paciente, entonces el personal de admisión podría teóricamente registrar dos citas superpuestas para el mismo paciente, violando la lógica de negocio subyacente. Si RN-05 aplica a todas las operaciones, debe indicarse explícitamente que también rige las acciones del personal de admisión. La frase "un paciente no podrá registrar" sugiere acción directa del paciente, no acción realizada en su nombre por otro.

---

### OBS-04 | AMBIGÜEDAD

- **Categoría**: Ambigüedad
- **Relacionado con**: RF-14, RF-15, RF-16, RN-22
- **Descripción**: RF-14 permite al paciente consultar "sus citas futuras programadas o confirmadas". RF-15 permite consultar "historial de citas finalizadas, canceladas y no asistidas". RF-16 permite al médico consultar su agenda de "citas programadas y confirmadas". RN-22 indica que "citas canceladas, finalizadas y no asistidas deberán conservarse en el historial del paciente". El estado "en atención" no aparece en ninguna de estas consultas: no se menciona si un paciente puede ver sus citas "en atención" ni si un médico puede ver citas en ese estado en su agenda.
- **Fundamento**: Es una ambigüedad porque el estado "en atención" es un estado válido según RF-13 y RN-07, y una cita en este estado es "futura" desde el punto de vista cronológico (aún no se ha finalizado). ¿Un paciente puede consultar su cita "en atención"? ¿Un médico puede verla en su agenda? Si no aparece en ninguna consulta, el usuario pierde visibilidad de citas actualmente en proceso. La omisión no es del requerimiento sino de la definición de qué estados aparecen en qué vista.

---

### OBS-05 | AMBIGÜEDAD

- **Categoría**: Ambigüedad
- **Relacionado con**: RF-18, RN-18, RN-19, RO-06, DP-01
- **Descripción**: RF-18 indica que durante el registro de una cita se deberá identificar si la atención es presencial o virtual "siempre que la modalidad esté habilitada para el médico y la especialidad seleccionados". RN-19 señala que una cita presencial no requiere información de acceso virtual. RN-18 indica que una cita virtual debe estar "identificada como virtual y deberá contener la información necesaria para acceder al servicio externo". RO-06 restringe que "las citas virtuales únicamente podrán habilitarse para médicos y especialidades que permitan dicha modalidad". Sin embargo, no se define cómo se habilita un médico o especialidad para atención virtual, quién toma esa decisión ni con qué criterio.
- **Fundamento**: Es una ambigüedad porque el flag "habilitado para virtual" requiere una fuente de verdad y un proceso de habilitación. ¿Es el médico quien se registra como habilitado para virtual? ¿Es el administrador quien lo configura? ¿Es por defecto todo activo o es una configuración separada? Sin esta definición, RF-18 no puede implementarse: el sistema no puede verificar si "la modalidad esté habilitada" si no sabe dónde ni cómo se almacena ese dato ni quién lo establece.

---

### OBS-06 | AMBIGÜEDAD

- **Categoría**: Ambigüedad
- **Relacionado con**: RF-05, RN-11, RN-12, RN-26, DP-05, DP-08
- **Descripción**: RF-05 permite al médico y personal autorizado definir "días y bloques horarios disponibles para atención". RN-11 indica que "la reprogramación de una cita solo podrá realizarse hacia un horario disponible". RN-12 indica que "cuando una cita sea reprogramada, el nuevo horario quedará reservado y el horario anterior deberá quedar nuevamente disponible". RF-08 permite consultar horarios futuros disponibles "dentro del período habilitado para reservas". El "período habilitado para reservas" es una ventana temporal futura máxima que no está definida (DP-08 pendiente).
- **Fundamento**: Es una ambigüedad porque sin conocer el período máximo de anticipación para reservar (DP-08), el sistema no puede limitar las consultas de disponibilidad (RF-08) ni validar que un horario candidato para reprogramación esté dentro de la ventana permitida. RN-11 y RF-08 dependen directamente de este valor numérico indefinido. La frase "dentro del período habilitado para reservas" carece de contenido operativo sin un valor concreto o rango definido.

---

### OBS-07 | AMBIGÜEDAD

- **Categoría**: Ambigüedad
- **Relacionado con**: RF-20, CA-031, CA-032, RN-20, DP-05
- **Descripción**: RF-20 permite al médico "registrar el inicio y la finalización de una atención, almacenando la fecha y hora correspondientes". CA-031 y CA-032 refuerzan lo mismo. RN-20 indica que "una cita solo podrá marcarse como finalizada después de haber sido registrada previamente como 'En atención'". No se define qué acción desencadena el estado "En atención" — ¿el paciente llega al consultorio?, ¿el médico presiona un botón en el sistema?, ¿el personal de admisión marca la entrada?. El desencadenante del cambio de estado es ambiguo.
- **Fundamento**: Es una ambigüedad porque el flujo de estados Requiere un evento desencadenante claro para pasar de "Confirmada" a "En atención". Si el desencadenante es una acción física (paciente llega) vs. una acción del sistema (médico marca botón), el diseño de la interfaz y la lógica de negocio difieren sustancialmente. RN-20 asume que el estado "En atención" se alcanza, pero no define cuándo ni cómo.

---

### OBS-08 | AMBIGÜEDAD

- **Categoría**: Ambigüedad
- **Relacionado con**: RF-21, RF-02, RF-03, RN-17, CA-034, CA-035
- **Descripción**: RF-02 permite al administrador "registrar, consultar, modificar y cambiar el estado activo o inactivo de los médicos". RF-03 hace lo mismo para especialidades. RF-21 permite al administrador "registrar, consultar, modificar, activar y desactivar usuarios, así como asignar o revocar los roles correspondientes" incluyendo el rol médico. RN-17 indica que el administrador puede "gestionar pacientes, médicos, especialidades, usuarios, roles". No se define la relación entre un "médico" (entidad de RF-02) y un "usuario con rol médico" (entidad de RF-21).
- **Fundamento**: Es una ambigüedad porque la relación es incierta: ¿un médico requiere necesariamente un usuario del sistema? ¿Un usuario con rol médico es lo mismo que un médico registrado en RF-02? Si un médico se da de baja (RF-02: cambiar estado a inactivo), ¿su usuario (RF-21) también se desactiva o permanece activo con rol pero sin médico activo? Si un usuario con rol médico es eliminado (RF-21), ¿qué pasa con sus citas futuras? El sistema no puede diseñar las relaciones sin saber si son entidades separadas, una misma entidad con dos vistas, o entidades relacionadas con regla de consistencia.

---

### OBS-09 | AMBIGÜEDAD

- **Categoría**: Ambigüedad
- **Relacionado con**: RNF-15, RN-27, RF-01
- **Descripción**: RNF-15 establece que "la interfaz deberá considerar criterios básicos de accesibilidad para facilitar su utilización por personas con diferentes capacidades". RN-27 indica que "el tiempo de tolerancia para determinar que un paciente no asistió a una cita será definido por la institución antes de la puesta en producción". RF-01 requiere registrar pacientes con nombre, apellidos, documento de identidad, fecha de nacimiento, teléfono y correo electrónico. No se definen los criterios de accesibilidad ni se establece qué nivel de conformidad se busca (WCAG 2.1 A, AA, AAA u otro estándar).
- **Fundamento**: Es una ambigüedad porque "criterios básicos de accesibilidad" es subjetivo y no verificable sin un estándar explícito. Un sistema puede ser evaluado como "accesible" o "no accesible" dependiendo de si se mide contra WCAG 2.1 AA, Section 508, o un estándar interno. Sin especificar el estándar, no se puede verificar si RNF-15 se cumple ni diseñar los tests de accesibilidad.

---

### OBS-10 | AMBIGÜEDAD

- **Categoría**: Ambigüedad
- **Relacionado con**: RNF-16, RN-26, DP-07
- **Descripción**: RNF-16 indica que "los componentes del sistema deberán mantenerse organizados de forma modular, permitiendo realizar cambios en una funcionalidad sin requerir modificaciones innecesarias en funcionalidades no relacionadas". RN-26 indica que las políticas de tiempo serán "definidas por la institución antes de la puesta en producción". RNF-07 indica que el sistema deberá finalizar la sesión "después de un periodo prolongado de inactividad definido por la configuración de seguridad". Ninguna de estas afirmaciones establece valores o límites concretos.
- **Fundamento**: Es una ambigüedad porque "organizados de forma modular" es un criterio cualitativo subjetivo — ¿qué grado de módulos? ¿Microservicios, módulos de paquetes, capas de arquitectura? No es verificable en su formulación actual. Similarmente, "configuración de seguridad" sin valor numérico (DP-07) deja indefinido el comportamiento de RNF-07.

---

### OBS-11 | INCONSISTENCIA

- **Categoría**: Inconsistencia
- **Relacionado con**: RF-21, RF-02, RF-03, CA-034, CA-035, CA-036
- **Descripción**: RF-21 permite al administrador gestionar "usuarios" y asignar/revocar roles. CA-034 indica que "el administrador deberá poder registrar, consultar, modificar, activar y desactivar usuarios" y CA-035 que podrá "asignar y revocar roles". CA-036 establece que "un usuario no deberá poder acceder a funciones que no correspondan a su rol". Por otro lado, RF-02 permite al administrador gestionar médicos (CRUD + activar/desactivar) y RF-03 lo mismo para especialidades. Si un médico es un tipo de usuario, la gestión de médico (RF-02) y la gestión de usuario-con-rol-médico (RF-21) son dos operaciones que potencialmente se solapan.
- **Fundamento**: Es una inconsistencia porque RF-02 y RF-21 describen operaciones que podrían afectar al mismo objeto de negocio (el médico) pero desde perspectivas diferentes: RF-02 trata al médico como entidad clínica (con especialidades, horarios), mientras RF-21 lo trata como entidad de acceso (con roles, autenticación). No está claro si desactivar un médico (RF-02) implica desactivar su usuario (RF-21), o si son operaciones independientes. El sistema podría implementar inconsistencias si no se define la relación.

---

### OBS-12 | INCONSISTENCIA

- **Categoría**: Inconsistencia
- **Relacionado con**: RNF-18, RF-22, CA-037, CA-038, CA-039, RN-23, RN-24, RN-25
- **Descripción**: RNF-18 establece que el sistema deberá registrar "la fecha, hora y usuario responsable de las modificaciones realizadas sobre las citas". RF-22 establece que el historial de modificaciones registrará "como mínimo la fecha, hora, usuario responsable, acción realizada, valor anterior y valor actualizado". CA-037 indica que cada modificación registrará "fecha, hora, usuario responsable y acción realizada", y CA-038 que el historial "deberá conservar el valor anterior y el valor actualizado". CA-039 señala que el historial "no deberá eliminarse cuando una cita sea cancelada o finalizada". RN-23, RN-24 y RN-25 refuerzan lo mismo.
- **Fundamento**: Es una inconsistencia porque RNF-18 omite "acción realizada, valor anterior y valor actualizado" que sí aparecen en RF-22, CA-037, CA-038, RN-23, RN-24 y RN-25. RNF-18 es un requerimiento de nivel superior que no recoge la totalidad de la información requerida en requerimientos de nivel inferior y criterios de aceptación. Esto genera confusión sobre el nivel mínimo de detalle del historial: ¿solo fecha, hora y usuario (RNF-18) o también acción y valores (RF-22, CA-037/038)?

---

### OBS-13 | INCONSISTENCIA

- **Categoría**: Inconsistencia
- **Relacionado con**: RF-13, RN-07, RN-08, CA-019, CA-020, CA-021
- **Descripción**: RF-13 enumera los estados de una cita como: programada, confirmada, en atención, finalizada, cancelada y no asistida, con transiciones permitidas: Programada → Confirmada, Programada → Cancelada, Confirmada → En atención, Confirmada → Cancelada, Confirmada → No asistida, En atención → Finalizada. RN-07 y RN-08 repiten exactamente los mismos estados y transiciones. CA-019 los reproduce idénticamente. Sin embargo, RF-14 permite consultar citas "futuras programadas o confirmadas" y RF-15 permite consultar historial de "finalizadas, canceladas y no asistidas". El estado "en atención" no aparece en ninguna de las consultas de citas (RF-14, RF-15, RF-16).
- **Fundamento**: Es una inconsistencia porque el estado "en atención" está reconocido como estado válido (RF-13, RN-07) con una transición válida (Confirmada → En atención) y su propia transición saliente (En atención → Finalizada), pero no se define en qué consulta o vista aparece. RF-14 excluye citas "en atención" de las futuras, pero una cita "en atención" es cronológicamente futura (aún no finalizó). RF-16 permite al médico consultar "citas programadas y confirmadas" pero no "en atención". Esto genera un vacío de visibilidad.

---

### OBS-14 | INCONSISTENCIA

- **Categoría**: Inconsistencia
- **Relacionado con**: RNF-11, RF-09, CA-012, CA-013, RN-04, RN-10
- **Descripción**: RNF-11 establece que "el sistema deberá garantizar que un mismo horario no pueda ser reservado simultáneamente por más de un paciente para el mismo médico". RF-09 indica que antes de confirmar la reserva "el sistema deberá verificar nuevamente que el horario continúe disponible". CA-012 prueba que "si dos usuarios intentan reservar simultáneamente el mismo horario para el mismo médico, únicamente una reserva deberá confirmarse". CA-013 indica que "una vez confirmada una cita, el horario correspondiente deberá dejar de mostrarse como disponible". RN-04 dice que "un mismo médico no podrá tener más de una cita confirmada para la misma fecha y horario". RN-10 dice que "cuando una cita sea confirmada, el horario correspondiente dejará de estar disponible".
- **Fundamento**: Es una inconsistencia en el nivel de requerimiento, no en el contenido lógico, pero genera un riesgo de implementación: RNF-11 es una declaración de intención ("deberá garantizar") sin especificar el mecanismo, mientras que CA-012 es una prueba de validación concreta. La inconsistencia está en la ausencia de un requerimiento explícito que vincule la verificación atómica (RF-09) con la garantía de concurrencia (RNF-11). ¿Es suficiente verificar antes de confirmar (RF-09) o se requiere un mecanismo de locking a nivel de base de datos/transacción? La combinación sugiere que se necesita más especificación sobre atomicidad.

---

### OBS-15 | INCONSISTENCIA

- **Categoría**: Inconsistencia
- **Relacionado con**: RNF-07, DP-07
- **Descripción**: RNF-07 establece que el sistema "deberá finalizar automáticamente la sesión de un usuario después de un periodo prolongado de inactividad definido por la configuración de seguridad". DP-07 es una decisión pendiente que indica "definir el tiempo máximo de inactividad antes del cierre automático de sesión". El valor del timeout no está definido ni en RNF-07 ni en ningún otro documento.
- **Fundamento**: Es una inconsistencia porque RNF-07 hace referencia a un valor ("periodo prolongado de inactividad definido por la configuración de seguridad") que no está cuantificado en ningún lugar del documento de requerimientos. El término "prolongado" es subjetivo. DP-07 reconoce que el valor está pendiente pero no lo provee. El requerimiento es inoperable sin el valor numérico.

---

### OBS-16 | INCONSISTENCIA

- **Categoría**: Inconsistencia
- **Relacionado con**: RNF-02, RNF-03, CA-RNF-02, CA-RNF-03, perfil_carga.yaml
- **Descripción**: RNF-02 indica que el sistema deberá soportar "hasta 100 usuarios concurrentes y una carga objetivo de 500 transacciones de negocio por hora, considerando un pico de hasta 1.000 transacciones por hora". RNF-03 indica que "el sistema deberá permitir incrementar hasta 10 veces la carga inicial de transacciones sin requerir modificaciones del modelo funcional". El archivo perfil_carga.yaml establece: `usuarios_concurrentes_estimados: 100`, `transacciones_negocio_por_hora_objetivo: 500`, `multiplicador_pico: 2.0`, `factor_crecimiento_a_evaluar: 10`. CA-RNF-02 confirma el objetivo de 500 txn/hora y CA-RNF-03 confirma el pico de 1.000. El multiplicador de pico es 2.0 (500 × 2 = 1.000) y el factor de crecimiento es 10 (500 × 10 = 5.000). El pico documentado (1.000) y el crecimiento 10x (5.000) son dos métricas distintas sin relación explícita.
- **Fundamento**: Es una inconsistencia en el sentido de que RNF-03 pide "incrementar hasta 10 veces la carga inicial" (5.000 txn/hora) pero el pico documentado es 1.000 txn/hora. ¿El sistema debe soportar el pico de 1.000 (RNF-02) o la carga escalada 10x de 5.000 (RNF-03)? Son requerimientos aditivos que no se contradicen lógicamente pero sí generan confusión sobre cuál es la capacidad realmente requerida. El documento debe aclarar si el pico de 1.000 es el objetivo inmediato y el escalado 10x es un escenario de evaluación futura.

---

---

### OBS-17 | OMITSIÓN

- **Categoría**: Omisión
- **Relacionado con**: RF-01, RNF-04, RNF-06, RNF-12
- **Descripción**: RF-01 permite registrar pacientes con nombre, apellidos, documento de identidad, fecha de nacimiento, teléfono y correo electrónico. Sin embargo, no se define ningún flujo de verificación de identidad ni validación de datos durante el registro, ni cómo se autentica el paciente después del registro. RNF-04 indica que se requerirá autenticación pero no describe el mecanismo. RNF-06 indica protección de información personal y almacenamiento seguro de credenciales pero no define políticas de contraseña. No se menciona flujo de recuperación de contraseña, verificación de correo electrónico, ni bloquéo de cuenta tras intentos fallidos.
- **Fundamento**: Es una omisión porque sin definir cómo se verifica la identidad del paciente al registrarse, el sistema no puede garantizar que el documento de identidad registrado pertenece a la persona que lo registra. Esto afecta directamente a RN-05 (solapamiento de citas) ya que cualquier persona podría registrarse con datos ficticios y crear citas superpuestas. RNF-12 menciona validar "formato" pero no menciona verificación de existencia o pertenencia de los datos.

---

### OBS-18 | OMITSIÓN

- **Categoría**: Omisión
- **Relacionado con**: RF-02, RF-03, RN-02, RF-05
- **Descripción**: RF-02 permite al administrador cambiar el estado activo/inactivo de los médicos. RF-05 permite definir horarios disponibles. RN-02 indica que "cada médico deberá estar asociado al menos a una especialidad médica activa". No se define qué sucede cuando un médico se marca como inactivo (RF-02) y tiene citas futuras ya reservadas. Tampoco se define qué sucede cuando un médico no tiene ninguna especialidad activa (violando RN-02).
- **Fundamento**: Es una omisión porque RF-02 no especifica las consecuencias de desactivar un médico que tiene citas programadas futuras. ¿Las citas se cancelan automáticamente? ¿Se mantienen pero sin médico activo? ¿El sistema impide dar de baja a un médico con citas futuras? Tampoco se define si el sistema permite crear un médico sin especialidad activa, lo cual violaría RN-02. La regla de negocio existe (RN-02) pero el flujo de consecuencias al dar de baja está ausente.

---

### OBS-19 | OMITSIÓN

- **Categoría**: Omisión
- **Relacionado con**: RF-13, RF-14, RF-15, RF-16, RN-07, RN-08
- **Descripción**: RF-13 define el estado "en atención" como un estado válido con transiciones de entrada (Confirmada → En atención) y salida (En atención → Finalizada). Sin embargo, no se define en ningún requerimiento posterior un mecanismo para consultar citas en este estado ni para su gestión operativa (qué hacer, quién puede actuar, qué pasa si el paciente se va durante la atención). RN-07 y RN-08 confirman que el estado existe pero no proporcionan detalles operativos.
- **Fundamento**: Es una omisión porque el estado "en atención" requiere su propia lógica operativa: ¿quién puede ver las citas en atención? ¿Puede un médico pasar una cita de "en atención" a "finalizada" sin haber registrado inicio (CA-031)? ¿Qué pasa si el médico marca finalización pero el paciente nunca estuvo presente? ¿Puede un paciente cancelar una cita que está "en atención"? Todas estas situaciones operativas no están cubiertas por RF-13 ni por ningún otro requerimiento.

---

### OBS-20 | OMITSIÓN

- **Categoría**: Omisión
- **Relacionado con**: RF-19, RT-04, RT-05
- **Descripción**: RT-04 indica que "en la primera versión no se desarrollará una plataforma propia de videollamadas". RT-05 indica que "cuando se habilite una cita virtual, el sistema utilizará un servicio externo". RF-19 indica que el sistema "deberá proporcionar al paciente y al médico la información necesaria para acceder al servicio externo". No se define qué sucede si el servicio externo falla, está caído o no está disponible. No se define si existe un plan de contingencia para citas virtuales que no puedan conectarse.
- **Fundamento**: Es una omisión porque la dependencia de un servicio externo (RT-05) introduce un factor de fallo externo que no tiene gestión definida. Si el servicio de videollamadas está caído, ¿la cita se mantiene como virtual o se convierte automáticamente en presencial? ¿Se notifica al paciente y médico? RF-19 y RF-18 no contemplan estos escenarios de falla, creando un punto ciego operativo.

---

### OBS-21 | OMITSIÓN

- **Categoría**: Omisión
- **Relacionado con**: RF-13, RF-15, RF-16, RN-08, RN-22
- **Descripción**: RF-15 permite al paciente consultar su historial de "citas finalizadas, canceladas y no asistidas, pudiendo filtrar los resultados por fecha y estado". RF-16 permite al médico consultar su agenda de "citas programadas y confirmadas, pudiendo filtrar por fecha y estado". RN-22 indica que citas canceladas, finalizadas y no asistidas deben conservarse en el historial. No se menciona si existe un límite de tiempo para el historial (¿se eliminan registros de hace X años?). Tampoco se menciona la capacidad de exportar el historial ni si existe algún reporte o dashboard administrativo.
- **Fundamento**: Es una omisión porque sin definir el periodo de retención del historial, el sistema no puede gestionar el almacenamiento de datos de citas antiguas. Un sistema hospitalario puede generar miles de registros históricos; sin política de retención, el almacenamiento crece indefinidamente. Tampoco se menciona si el personal administrativo necesita reportes de citas por período (reportes por mes, por médico, por estado), lo cual es una necesidad típica de gestión hospitalaria.

---

### OBS-22 | OMITSIÓN

- **Categoría**: Omisión
- **Relacionado con**: RF-18, RF-05, RF-07, RF-08, RN-18, RO-06
- **Descripción**: RF-18 permite identificar si la atención es presencial o virtual "siempre que la modalidad esté habilitada para el médico y la especialidad seleccionados". RF-05 permite al médico y personal autorizado "definir y actualizar los días y bloques horarios disponibles para atención". RF-07 permite consultar "médicos activos asociados a una especialidad y que tengan horarios disponibles". RN-18 indica que una cita virtual debe estar identificada como tal. No se define si un médico puede tener horarios solo presenciales, solo virtuales, o ambos simultáneamente. Tampoco se define si un médico puede cambiar la modalidad de un horario específico (presencial → virtual o viceversa).
- **Fundamento**: Es una omisión porque la gestión de horarios (RF-05) no contempla la dimensión de modalidad (presencial/virtual). Un médico podría tener bloques horarios para ambos tipos de atención, pero el sistema no distingue si un horario disponible es para presencial o virtual. RF-18 asume que la habilitación es por médico/especialidad, pero no por horario específico. Si un médico tiene 8:00-9:00 presencial y 10:00-11:00 virtual, RF-05 no diferencia esos bloques por modalidad.

---

### OBS-23 | OMITSIÓN

- **Categoría**: Omisión
- **Relacionado con**: RF-09, RF-10, RF-11, RN-03, RN-05
- **Descripción**: RF-09 permite al paciente reservar una cita seleccionando especialidad, médico, fecha y horario disponible. RF-10 permite al personal de admisión registrar una cita en representación de un paciente. RF-11 permite reprogramar una cita. RN-03 indica que "una cita solo podrá reservarse en un horario disponible". RN-05 indica que "un paciente no podrá registrar dos citas que se superpongan en el mismo horario". No se menciona ningún mecanismo de lista de espera (waiting list) para cuando no hay horarios disponibles.
- **Fundamento**: Es una omisión porque cuando no hay disponibilidad (RF-08 no muestra horarios), el sistema no ofrece al paciente ninguna alternativa operativa. Una lista de espera permitiría al paciente registrarse para ser notificado cuando se libere un horario por cancelación o reprogramación. Sin este mecanismo, el sistema pierde oportunidades de gestionar la demanda de citas y la satisfacción del paciente.

---

### OBS-24 | OMITSIÓN

- **Categoría**: Omisión
- **Relacionado con**: RF-01, RF-21, RNF-04, RNF-06, RNF-12, DP-07
- **Descripción**: RF-21 permite al administrador registrar, consultar, modificar, activar y desactivar usuarios y asignar roles. RF-01 requiere registrar pacientes con nombre, apellidos, documento de identidad, teléfono, correo electrónico y fecha de nacimiento. RNF-04 indica autenticación requerida. RNF-06 indica protección de información personal. RNF-12 indica validación de datos obligatorios. No se menciona ningún mecanismo de recuperación de contraseña, cambio de contraseña, verificación de correo electrónico ni autenticación de dos factores. Tampoco se define si el sistema permite que múltiples usuarios compartan una misma cuenta (sesión concurrente) o si cada usuario debe tener credenciales únicas.
- **Fundamento**: Es una omisión porque sin recuperación de contraseña, los usuarios pueden quedar bloqueados permanentemente del sistema tras olvidar sus credenciales, generando un problema operativo grave. Sin verificación de correo electrónico, no se garantiza que la dirección registrada (RF-01) sea válida ni que pertenezca al usuario. Sin gestión de sesiones concurrentes, no está claro si un usuario puede tener múltiples sesiones activas simultáneamente (relevante para RNF-11 y concurrencia).

---

### OBS-25 | DUPLICIDAD

- **Categoría**: Duplicidad
- **Relacionado con**: RF-13, RN-07, RN-08, CA-019
- **Descripción**: Los estados y transiciones de citas están definidos en RF-13 con los estados: programada, confirmada, en atención, finalizada, cancelada, no asistida, y las transiciones: Programada → Confirmada, Programada → Cancelada, Confirmada → En atención, Confirmada → Cancelada, Confirmada → No asistida, En atención → Finalizada. RN-07 y RN-08 reproducen exactamente los mismos estados y transiciones. CA-019 reproduce las mismas transiciones. La lista de estados aparece tres veces con contenido idéntico.
- **Fundamento**: Es una duplicidad porque la información es la misma en tres documentos diferentes (RF, RN, CA). No hay una sola fuente de verdad para la definición de estados. Si el equipo modifica un estado o transición, debe hacerlo en tres lugares. Esto genera riesgo de inconsistencia por mantenimiento: si se actualiza RF-13 pero no RN-07, los documentos quedan desincronizados. La duplicidad no es dañina en sí misma pero dificulta el mantenimiento.

---

### OBS-26 | DUPLICIDAD

- **Categoría**: Duplicidad
- **Relacionado con**: RNF-17, RNF-18, RF-22, CA-037, CA-038, RN-23, RN-24, RN-25
- **Descripción**: El registro de auditoría de modificaciones aparece en múltiples documentos: RNF-17 indica que el sistema "deberá registrar como mínimo errores de aplicación, intentos fallidos de autenticación, cambios de estado de citas y fallas en operaciones críticas". RNF-18 indica que el sistema "deberá registrar la fecha, hora y usuario responsable de las modificaciones realizadas sobre las citas". RF-22 indica historial de modificaciones con fecha, hora, usuario, acción, valor anterior y valor actualizado. CA-037 y CA-038 repiten estos mismos requerimientos. RN-23, RN-24 y RN-25 los vuelven a indicar. CA-RNF-17 y CA-RNF-18 son los criterios de aceptación correspondientes.
- **Fundamento**: Es una duplicidad porque el requerimiento de auditoría se repite en al menos 5-6 documentos con formulaciones ligeramente diferentes. La información mínima requerida varía entre RNF-18 (solo fecha, hora, usuario) y RF-22 (fecha, hora, usuario, acción, valores). Esto crea confusión sobre cuál es el requerimiento canonical y dificulta saber si RNF-18 es un subconjunto incompleto de RF-22 o si ambos deben implementarse por separado.

---

### OBS-27 | DUPLICIDAD

- **Categoría**: Duplicidad
- **Relacionado con**: RF-09, RF-10, RF-11, RN-03, RN-04, RN-05, RN-06, CA-011, CA-012, CA-013, CA-016
- **Descripción**: La verificación de disponibilidad antes de reservar aparece en RF-09 ("verificar nuevamente que el horario continúe disponible"), RN-06 ("antes de confirmar una cita, el sistema deberá verificar nuevamente que el horario seleccionado continúe disponible"), CA-011 ("antes de confirmar una reserva, el sistema deberá verificar nuevamente que el horario continúe disponible") y CA-012 (prueba de doble reserva simultánea). RN-03, RN-04, RN-10, CA-013 y CA-016 también cubren disponibilidad y no duplicación. El concepto de "verificar disponibilidad" se repite al menos 8-10 veces en diferentes documentos.
- **Fundamento**: Es una duplicidad porque la lógica de verificación de disponibilidad se expresa de forma casi idéntica en RF-09, RN-06 y CA-011. Si se modifica el criterio de verificación, hay que actualizar múltiples documentos. La duplicidad es especialmente problemática porque RF-09, RN-06 y CA-011 son esencialmente el mismo requerimiento con diferentes redacciones, y CA-012 es la prueba de ese mismo requerimiento.

---

### OBS-28 | FALTA DE VERIFICABILIDAD

- **Categoría**: Falta de verificabilidad
- **Relacionado con**: RNF-15, RNF-16
- **Descripción**: RNF-15 establece que la interfaz "deberá considerar criterios básicos de accesibilidad para facilitar su utilización por personas con diferentes capacidades". RNF-16 establece que los componentes "deberán mantenerse organizados de forma modular, permitiendo realizar cambios en una funcionalidad sin requerir modificaciones innecesarias en funcionalidades no relacionadas". RNF-06 indica que el sistema "deberá proteger la información personal... mediante controles de acceso, transmisión segura de información y almacenamiento protegido de credenciales". RNF-12 indica que el sistema "deberá validar la presencia y formato de los datos obligatorios".
- **Fundamento**: Es una falta de verificabilidad porque RNF-15 usa el término "criterios básicos de accesibilidad" sin definir un estándar medible (WCAG 2.1 nivel AA, por ejemplo). RNF-16 usa "organizados de forma modular" sin definir qué constituye modularidad (número de paquetes, límites de acoplamiento, métricas de cohesión). RNF-06 usa "almacenamiento protegido de credenciales" sin especificar qué algoritmo de hash, si se usa sal, si se aplica bcrypt/argon2/scrypt. RNF-12 usa "formato" sin especificar regex o reglas de validación para cada campo. Ninguno de estos requerimientos puede verificarse objetivamente tal como están formulados.

---

### OBS-29 | FALTA DE VERIFICABILIDAD

- **Categoría**: Falta de verificabilidad
- **Relacionado con**: RNF-13, RNF-14, RNF-19
- **Descripción**: RNF-13 indica que la interfaz "deberá permitir utilizar las funciones principales desde computadoras, tabletas y teléfonos móviles sin pérdida de funcionalidad". RNF-14 indica que "en una prueba de usabilidad, al menos el 80 % de los usuarios deberá poder consultar disponibilidad y reservar una cita sin asistencia externa". RNF-19 indica que el sistema "deberá funcionar mediante navegador web en las dos últimas versiones estables de Google Chrome, Microsoft Edge y Mozilla Firefox".
- **Fundamento**: Es una falta de verificabilidad parcial porque RNF-13 dice "sin pérdida de funcionalidad" sin definir qué funciones son "principales" ni cuáles están excluidas. RNF-14 establece un umbral del 80% pero no define la metodología de la prueba de usabilidad (muestra, criterio de éxito, duración, quién la realiza). RNF-19 se refiere a "las dos últimas versiones estables" — una definición móvil que cambia con el tiempo; el sistema debe actualizar el conjunto de navegadores compatibles periódicamente sin que el requerimiento cambie.

---

### OBS-30 | FALTA DE VERIFICABILIDAD

- **Categoría**: Falta de verificabilidad
- **Relacionado con**: RF-01, RNF-12, CA-001, CA-002
- **Descripción**: RF-01 permite registrar pacientes con nombre, apellidos, documento de identidad, fecha de nacimiento, teléfono y correo electrónico. RNF-12 indica que "antes de registrar pacientes, médicos, horarios o citas, el sistema deberá validar la presencia y formato de los datos obligatorios correspondientes". CA-002 indica que "si falta el nombre, apellidos, documento de identidad o teléfono, el sistema deberá impedir el registro e informar los datos faltantes". CA-001 indica que "al registrar un paciente con todos los datos obligatorios válidos, el sistema deberá guardar la información y permitir su consulta posterior". No se definen los formatos específicos de validación para cada campo.
- **Fundamento**: Es una falta de verificabilidad porque "formato" es un término sin definición operativa: ¿el documento de identidad boliviano sigue un formato numérico específico de 7 u 8 dígitos? ¿El teléfono incluye código de país? ¿El correo electrónico sigue RFC 5322? ¿La fecha de nacimiento usa formato DD/MM/AAAA? Sin estas definiciones, RNF-12 y CA-001 no pueden implementarse ni verificarse objetivamente. CA-002 dice "informar los datos faltantes" pero no qué formato de mensaje ni cómo se presenta al usuario.

---

### OBS-31 | FALTA DE VERIFICABILIDAD

- **Categoría**: Falta de verificabilidad
- **Relacionado con**: RNF-03, CA-RNF-04, perfil_carga.yaml
- **Descripción**: RNF-03 indica que el sistema "deberá permitir incrementar hasta 10 veces la carga inicial de transacciones sin requerir modificaciones del modelo funcional del sistema". CA-RNF-04 indica que "el diseño del sistema deberá permitir evaluar un escenario de crecimiento de hasta 10 veces la carga inicial sin modificar las reglas funcionales". El archivo perfil_carga.yaml establece `factor_crecimiento_a_evaluar: 10`. Sin embargo, no se define qué se entiende por "sin requerir modificaciones del modelo funcional" ni cómo se medirá este criterio.
- **Fundamento**: Es una falta de verificabilidad porque la frase "sin requerir modificaciones del modelo funcional" es tautológica y auto-referencial. ¿Un cambio en la configuración de servidores es una modificación del modelo funcional? ¿Un cambio en el esquema de base de datos para agregar índices? ¿Un cambio en el código de la lógica de negocio? Sin definir qué constituye una "modificación del modelo funcional", no se puede determinar si el cumplimiento de RNF-03 es exitoso.

---

### OBS-32 | FALTA DE VERIFICABILIDAD

- **Categoría**: Falta de verificabilidad
- **Relacionado con**: RNF-01, RNF-02, RNF-08, RNF-09, RNF-10
- **Descripción**: RNF-01 establece tiempo máximo de 3 segundos para operaciones específicas. RNF-02 establece 100 usuarios concurrentes, 500 txn/hora objetivo y pico 1.000. RNF-08 establece disponibilidad mensual mínima del 99%. RNF-09 establece RPO máximo de 60 minutos. RNF-10 establece RTO máximo de 120 minutos. Todos estos requerimientos están cuantificados numéricamente. Sin embargo, no se define el entorno de medición: ¿3 segundos bajo qué condiciones de red? ¿100 usuarios concurrentes desde dónde? ¿El 99% de disponibilidad se mide en qué horario (24/7, horario laboral)? ¿El RPO y RTO se miden desde el centro de datos o desde el punto de acceso del usuario?
- **Fundamento**: Es una falta de verificabilidad porque los números están definidos pero las condiciones de medición no. Un tiempo de respuesta de 3 segundos puede cumplirse en red LAN pero no en conexión móvil 3G. La disponibilidad del 99% puede ser sobre servidores activos pero no considerar mantenimiento no programado. Sin definir el contexto de medición, los SLAs numéricos no son directamente verificables en condiciones reales diversas.

---

### OBS-33 | RIESGO

- **Categoría**: Riesgo
- **Relacionado con**: RF-19, RT-04, RT-05, DP-01, RN-18, RN-19
- **Descripción**: El sistema depende de un servicio externo de atención virtual para las citas virtuales (RT-05), pero el servicio específico no está definido (DP-01 pendiente). RF-19 requiere proporcionar información de acceso al servicio externo. RN-18 indica que una cita virtual debe contener la información necesaria para acceder al servicio externo. RT-04 indica que no se desarrollará plataforma propia de videollamadas. El sistema está diseñado para que toda la funcionalidad de citas virtuales dependa de un componente externo no seleccionado.
- **Fundamento**: Es un riesgo porque la dependencia de un proveedor externo no definido introduce: (a) riesgo de disponibilidad — si el servicio externo falla, todas las citas virtuales se ven afectadas; (b) riesgo de integración — la API del proveedor puede cambiar sin previo aviso; (c) riesgo de costo — las tarifas del servicio externo pueden impactar el presupuesto; (d) riesgo de dependencia de vendor — migrar a otro proveedor después de la implementación puede requerir cambios significativos. El diseño de RF-19 y RN-18 debe contemplar la posible sustitución del servicio externo sin requerir cambios mayores.

---

### OBS-34 | RIESGO

- **Categoría**: Riesgo
- **Relacionado con**: RNF-11, RF-09, CA-012, RN-04, RN-10, CA-013
- **Descripción**: RNF-11 garantiza que "un mismo horario no pueda ser reservado simultáneamente por más de un paciente para el mismo médico". RF-09 requiere verificar disponibilidad antes de confirmar. CA-012 prueba que solo una reserva confirma ante concurrencia. RN-04 indica que un médico no puede tener más de una cita confirmada para la misma fecha y horario. RN-10 indica que al confirmar una cita, el horario deja de estar disponible. CA-013 indica que el horario deja de mostrarse como disponible tras confirmar.
- **Fundamento**: Es un riesgo porque la garantía de RNF-11 requiere un mecanismo de control de concurrencia a nivel de transacción o locking que no está especificado. Si dos usuarios leen la disponibilidad simultáneamente (lectura sin bloqueo), ambos ven el horario disponible, y ambos intentan reservar, podría producirse una doble reserva si la verificación (RF-09) no es atómica con la escritura. CA-012 asume que el sistema resolverá esto correctamente pero no define cómo. La implementación requiere técnicas como optimistic locking, SELECT FOR UPDATE, o transacciones SERIALIZABLE, ninguna de las cuales está especificada.

---

### OBS-35 | RIESGO

- **Categoría**: Riesgo
- **Relacionado con**: RF-02, RF-05, RN-02, RN-10, CA-013, CA-019, CA-021
- **Descripción**: RF-02 permite al administrador cambiar el estado de un médico a inactivo. RF-05 permite definir horarios disponibles. RN-10 indica que al confirmar una cita, el horario deja de estar disponible. RN-12 indica que al reprogramar una cita, el horario anterior queda nuevamente disponible. RN-02 indica que cada médico debe estar asociado a al menos una especialidad activa. CA-019 y CA-021 indican que una cita cancelada o finalizada no puede volver a un estado anterior. No se define qué sucede cuando un médico se marca como inactivo (RF-02) mientras tiene citas confirmadas futuras, o citas en estados canceladas/finalizadas en su historial.
- **Fundamento**: Es un riesgo porque la desactivación de un médico puede crear citas huérfanas o conflictos de estado. Si un médico se desactiva y tenía citas confirmadas futuras, esas citas quedan en un estado sin un médico activo asociado. RN-02 se viola si el médico queda sin especialidad activa. Si el sistema permite cancelar las citas automáticamente al dar de baja al médico, podría violar CA-021 (una cita finalizada no puede volver a un estado anterior). El sistema debe definir un protocolo de desactivación de médico que respete todos estos constraints simultáneamente.

---

### OBS-36 | RIESGO

- **Categoría**: Riesgo
- **Relacionado con**: RNF-02, RNF-03, RNF-01, perfil_carga.yaml, CA-RNF-01
- **Descripción**: RNF-01 establece tiempo máximo de respuesta de 3 segundos para operaciones de consulta y registro. RNF-02 establece 100 usuarios concurrentes, 500 txn/hora objetivo y pico 1.000. RNF-03 requiere escalar 10x (5.000 txn/hora). CA-RNF-01 confirma el SLA de 3 segundos bajo 100 usuarios concurrentes. El perfil de carga establece multiplicador_pico: 2.0 y factor_crecimiento_a_evaluar: 10. No se define cómo se distribuye la carga entre lecturas (consultas) y escrituras (reservas, modificaciones) ni cuáles operaciones son críticas para el SLA de 3 segundos.
- **Fundamento**: Es un riesgo porque el SLA de 3 segundos (RNF-01) puede no ser compatible con las operaciones de concurrencia (RNF-11) que requieren transacciones de base de datos más pesadas (locking, verificación atómica). Una reserva con verificación de concurrencia puede tomar más de 3 segundos bajo carga, especialmente si requiere locking de registros. El sistema debe definir qué operaciones comparten el SLA de 3 segundos y cuáles pueden tolerar mayor latencia, ya que RNF-01 incluye "registro de citas" junto con "consulta de especialidades" en el mismo umbral.

---

### OBS-17 | RIESGO

- **Categoría**: Riesgo
- **Relacionado con**: RNF-07, DP-07, RNF-04, RNF-05, RF-21
- **Descripción**: RNF-07 establece que el sistema finalizará automáticamente la sesión tras un periodo prolongado de inactividad. DP-07 está pendiente de definir el valor del timeout. RNF-04 indica autenticación requerida para todos los roles. RF-21 permite al administrador gestionar usuarios y sus sesiones. No se menciona si el sistema permite extender la sesión manualmente, si hay notificación previa al cierre, ni si las operaciones en curso se preservan tras el cierre.
- **Fundamento**: Es un riesgo porque sin definir el timeout de sesión (DP-07) ni las políticas de manejo de sesiones activas, el sistema puede bloquear a usuarios que están realizando operaciones largas (como completar el registro de una cita con múltiples pasos). Si el cierre automático elimina el trabajo no guardado, se pierde productividad del usuario. Sin notificación previa, el cierre es abrupto y genera frustración. Además, un timeout demasiado corto puede forzar recargas frecuentes que afecten el cumplimiento del SLA de 3 segundos (RNF-01).

---

### OBS-17 | RIESGO

- **Categoría**: Riesgo
- **Relacionado con**: RF-18, RN-18, RN-19, RO-06, RT-05, DP-01
- **Descripción**: RF-18 permite identificar si la atención es presencial o virtual "siempre que la modalidad esté habilitada para el médico y la especialidad seleccionados". RO-06 indica que las citas virtuales solo pueden habilitarse para médicos y especialidades que permitan dicha modalidad. RN-18 indica que una cita virtual debe estar identificada como tal. RN-19 indica que una cita presencial no requiere información de acceso virtual. RT-05 indica que se usará un servicio externo. No se define el criterio para determinar qué médicos/especialidades pueden atender virtualmente.
- **Fundamento**: Es un riesgo porque sin definir el criterio de habilitación de virtual, el sistema no puede implementar RF-18. Si el criterio es subjetivo (decisión médica), no hay forma de automatizar la habilitación. Si el criterio es técnico (el médico tiene cuenta en el servicio externo), el sistema necesita verificar esa cuenta. El riesgo es que la implementación pueda hardcodear la habilitación o permitir que cualquier médico habilite virtual, lo cual violaría RO-06. La ambigüedad del criterio de habilitación genera riesgo de incumplimiento de reglas de negocio.

---

### OBS-17 | RIESGO

- **Categoría**: Riesgo
- **Relacionado con**: RF-01, RF-21, RNF-04, RNF-06, RNF-12, RNF-17
- **Descripción**: RF-01 requiere registro de pacientes con datos personales (nombre, apellidos, documento de identidad, fecha de nacimiento, teléfono, correo electrónico). RNF-06 indica protección de información personal mediante controles de acceso, transmisión segura y almacenamiento protegido de credenciales. RNF-04 indica autenticación requerida. RNF-12 indica validación de datos obligatorios. RNF-17 indica registro de intentos fallidos de autenticación. No se menciona política de retención de datos personales, ni proceso de eliminación de datos del paciente (derecho al olvido), ni clasificación de datos sensibles (salud).
- **Fundamento**: Es un riesgo porque el sistema maneja datos personales y de salud, que son datos sensibles bajo regulaciones de protección de datos. Sin definir política de retención, el sistema puede almacenar datos de pacientes inactivos indefinidamente. Sin proceso de eliminación, no se cumple con posibles regulaciones de privacidad. Sin clasificar los datos como sensibles (datos de salud), no se aplican controles de protección reforzados. RNF-06 menciona "protección" pero no alcanza a cubrir las dimensiones de retención y eliminación de datos personales de salud.

---

### OBS-17 | RIESGO

- **Categoría**: Riesgo
- **Relacionado con**: RF-14, RF-15, RF-16, RNF-01, CA-022, CA-025
- **Descripción**: RF-14 permite al paciente consultar citas futuras programadas o confirmadas. RF-15 permite consultar historial de citas finalizadas, canceladas y no asistidas. RF-16 permite al médico consultar su agenda de citas programadas y confirmadas. CA-022 indica que el paciente consultará "únicamente sus propias citas". CA-025 indica que el médico consultará "únicamente las citas correspondientes a su agenda". No se menciona si el sistema maneja la visibilidad de citas de pacientes menores de edad o pacientes con capacidad limitada donde un representante legal necesita acceso.
- **Fundamento**: Es un riesgo porque RF-14 y CA-022 asumen que cada paciente tiene una cuenta individual con acceso exclusivo a sus citas. Si un menor de edad o persona con capacidad limitada necesita que un representante acceda a sus citas, el sistema no contempla esta situación. RF-21 permite asignar roles de paciente pero no define relaciones de representatividad entre usuarios. Esto genera un riesgo de exclusión funcional para un subgrupo de pacientes que el sistema probablemente necesita atender.

---

### OBS-17 | RIESGO

- **Categoría**: Riesgo
- **Relacionado con**: RF-10, RF-11, RF-12, RN-16, RN-05, RO-02
- **Descripción**: RF-10 permite al personal de admisión registrar citas en representación de un paciente. RF-11 permite reprogramar citas en representación del paciente. RF-12 permite cancelar citas en representación del paciente. RN-16 indica que el personal de admisión puede "registrar, reprogramar y cancelar citas en representación de un paciente cuando tenga los permisos correspondientes". RO-02 indica que el personal de admisión solo puede realizar operaciones permitidas por su rol. No se define qué permisos específicos tiene el personal de admisión ni cómo se otorgan ni cómo se auditan.
- **Fundamento**: Es un riesgo porque el personal de admisión puede crear, reprogramar y cancelar citas ajenas al paciente. Sin definir los permisos específicos y el proceso de auditoría de estas operaciones, se genera un riesgo de abuso de privilegios: un trabajador del personal de admisión podría, por ejemplo, cancelar sistemáticamente citas de un paciente específico o crear citas falsas en nombre de pacientes. RF-21 permite asignar roles pero no especifica qué permisos exactos tiene el rol de "personal de admisión o recepción" (más allá de "cuando tenga los permisos correspondientes" — frase circular).

---

### OBS-17 | RIESGO

- **Categoría**: Riesgo
- **Relacionado con**: RF-22, CA-037, CA-038, CA-039, RN-23, RN-24, RN-25, RNF-17, RNF-18
- **Descripción**: RF-22 permite consultar el historial de modificaciones de citas con fecha, hora, usuario responsable, acción, valor anterior y valor actualizado. CA-037 indica que cada modificación registrará fecha, hora, usuario responsable y acción. CA-038 indica que el historial conservará valores anterior y actualizado. CA-039 indica que el historial no se eliminará aunque la cita sea cancelada o finalizada. RN-23, RN-24 y RN-25 refuerzan lo mismo. RNF-17 y RNF-18 establecen el registro de modificaciones. No se menciona el volumen esperado de registros de auditoría ni si hay política de retención del historial de auditoría.
- **Fundamento**: Es un riesgo porque el historial de auditoría crecerá indefinidamente a medida que se modifiquen citas. Sin política de retención para datos de auditoría, el almacenamiento consumido por el log de modificaciones puede superar al de los datos de citas mismos. CA-039 indica que el historial no se elimina al cancelar/finalizar una cita (correcto desde el punto de vista de integridad), pero no dice cuánto tiempo se conserva ni si hay archivado a largo plazo. El sistema debe definir políticas de retención de auditoría que equilibren integridad legal con almacenamiento.

---

### OBS-17 | RIESGO

- **Categoría**: Riesgo
- **Relacionado con**: RF-13, RF-14, RF-15, RN-07, RN-08, CA-019, CA-020, CA-021
- **Descripción**: RF-13 define las transiciones válidas de estados incluyendo: Confirmada → Cancelada, Confirmada → No asistida, En atención → Finalizada. CA-021 indica que una cita cancelada o finalizada no puede volver a un estado anterior. CA-020 indica que el sistema impedirá transiciones no autorizadas. RN-09 indica lo mismo. RN-20 indica que una cita solo puede marcarse como finalizada después de haber estado "en atención". No se define si existe un estado de "expirado" o "anulado" para citas que nunca se confirmaron tras ser programadas.
- **Fundamento**: Es un riesgo porque una cita "programada" que nunca se confirma ni cancela permanecerá en estado programada indefinidamente, ocupando potencialmente un espacio en la interfaz del médico y en las consultas del paciente. No se define si hay un timeout automático que transite programada → cancelada si no se confirma. RN-08 no contempla esta transición, pero la necesidad operativa existe: un paciente reserva pero no confirma, y la cita queda "colgada". El sistema necesita definir si hay un mecanismo de expiración automática o si el médico/admisión debe cancelarla manualmente.

---

### OBS-17 | RIESGO

- **Categoría**: Riesgo
- **Relacionado con**: RF-16, RN-15, RF-05, RF-07, RF-08
- **Descripción**: RF-16 permite al médico consultar su agenda de citas "programadas y confirmadas" filtrando por fecha y estado. RN-15 indica que el médico únicamente puede consultar citas asignadas a su agenda. RF-05 permite al médico definir horarios disponibles. RF-07 permite consultar médicos activos con disponibilidad. RF-08 permite consultar horarios futuros disponibles. No se menciona si el médico puede ver citas de pacientes que están "en atención" con él en tiempo real (visibilidad en vivo del consultorio).
- **Fundamento**: Es un riesgo porque la visión del médico sobre su agenda puede estar desactualizada. Si el médico está atendiendo a un paciente (citas en "en atención") y necesita ver qué más tiene en la agenda, RF-16 no lo contempla explícitamente ya que dice "programadas y confirmadas" pero no "en atención". El médico podría no poder ver la transición en tiempo real de su siguiente cita. Además, si la agenda no muestra citas en atención, el médico pierde contexto operativo completo sobre su carga de trabajo actual.

---

### OBS-17 | RIESGO

- **Categoría**: Riesgo
- **Relacionado con**: RF-01, RNF-12, CA-001, CA-002, DP-08
- **Descripción**: RF-01 requiere registrar pacientes con nombre, apellidos, documento de identidad, fecha de nacimiento, teléfono y correo electrónico. RNF-12 indica validación de presencia y formato. CA-001 indica que con datos obligatorios válidos se guardará la información. CA-002 indica que faltando nombre, apellidos, documento o teléfono se impedirá el registro. DP-08 está pendiente de definir el periodo máximo de anticipación para reservar. No se define si el paciente debe tener una edad mínima para registrarse ni si se permite registro de pacientes fallecidos o inactivos.
- **Fundamento**: Es un riesgo porque sin definir edad mínima o plausibilidad de fecha de nacimiento, el sistema podría registrar pacientes con fechas futuras (error de data entry) o edades que no corresponden a pacientes típicos de un hospital. RF-01 incluye fecha de nacimiento como campo pero no como obligatorio (solo nombre, apellidos, documento, teléfono son obligatorios según RF-01). Esto genera inconsistencia: si la fecha de nacimiento no es obligatoria, no se puede calcular la edad del paciente para validar plausibilidad.

---

### OBS-17 | RIESGO

- **Categoría**: Riesgo

- **Relacionado con**: RF-19, RT-03, RT-05, RNF-03
- **Descripción**: RF-19 indica que para citas virtuales el sistema proporcionará información de acceso al servicio externo. RT-03 indica que la atención virtual depende de la disponibilidad de conexión a Internet de los participantes. RT-05 indica que se usará un servicio externo. RNF-03 requiere escalar 10x sin cambios funcionales. No se define si el sistema notifica a paciente y médico sobre problemas de conexión antes de la cita o durante la misma.
- **Fundamento**: Es un riesgo porque la dependencia de conexión a Internet (RT-03) afecta directamente la viabilidad de la atención virtual. Si el paciente pierde conexión durante la cita, el estado de la cita cambia? Se convierte en no-asistida? Se reprograma automáticamente? RF-19 proporciona acceso pero no gestiona falla de conexión. El sistema podría generar frustración en usuarios y carga operativa para personal de admisión que debe gestionar citas virtuales fallidas manualmente.

---


### OBS-17 | RIESGO

- **Categoría**: Riesgo
- **Relacionado con**: RF-09, RF-10, RF-11, RN-03, RN-04, RN-05, RN-06, CA-011, CA-012, CA-013, RNF-11, RNF-01
- **Descripción**: RF-09 requiere verificar disponibilidad antes de confirmar reserva. CA-012 prueba que solo una reserva confirma ante reserva simultánea. CA-013 indica que el horario deja de mostrarse como disponible tras confirmar. RN-04 indica que un médico no puede tener más de una cita confirmada para mismos fecha y horario. RNF-11 garantiza que el mismo horario no puede ser reservado simultáneamente por más de un paciente. RNF-01 establece SLA de 3 segundos.
- **Fundamento**: Es un riesgo porque la verificación de disponibilidad debe ser atómica con la reserva para cumplir RNF-11, pero una transacción atómica de verificación + reserva puede exceder el SLA de 3 segundos (RNF-01) bajo carga concurrente. El sistema debe resolver la tensión entre la atomicidad requerida para evitar dobles reservas y la rapidez requerida para el SLA. Si se usa un approach de verificación rápida + reserva diferida, puede haber dobles reservas. Si se usa una transacción fuerte, puede haber latencia que viole RNF-01.

---

### OBS-17 | OBSERVACIÓN ADICIONAL — RESTRICCIÓN ORGANIZACIONAL

- **Categoría**: Riesgo
- **Relacionado con**: RO-03, RO-04, RO-05, RF-13, RF-14, RF-15
- **Descripción**: RO-03 indica que la primera versión no incluye facturación ni gestión contable. RO-04 indica que no incluye historias clínicas, diagnósticos ni prescripción. RO-05 indica que no incluye farmacia, laboratorio, internación ni emergencias. RF-13 define estados de citas que incluyen "finalizada". RF-14 permite consultar citas futuras. RF-15 permite consultar historial de citas finalizadas. La finalización de una cita sin facturación asociada genera un vacío en el flujo de cierre de atención.
- **Fundamento**: Es un riesgo porque el estado "finalizada" (RF-13) sugiere un cierre de ciclo de atención, pero sin facturación (RO-03), el flujo de negocio está incompleto. ¿Qué significa "finalizada" si no hay facturación? ¿Es solo el cierre clínico o también administrativo? RN-02 y RN-22 indican que las citas finalizadas se conservan en historial, pero sin vincular a ningún costo o factura. El sistema debe definir qué implica el estado "finalizada" en un contexto sin facturación.

---

### OBS-17 | OBSERVACIÓN ADICIONAL — DECISIONES PENDIENTES CRÍTICAS

- **Categoría**: Riesgo
- **Relacionado con**: DP-01 a DP-08, RF-09, RF-11, RF-12, RF-19, RNF-07, RNF-01
- **Descripción**: Las 8 decisiones pendientes (DP-01 a DP-08) son: DP-01 (servicio externo virtual), DP-02 (email/mensajería), DP-03 (sincronización calendarios), DP-04 (stack tecnológico), DP-05 (tiempo mínimo cancelar/reprogramar), DP-06 (tiempo tolerancia no-asistencia), DP-07 (timeout sesión), DP-08 (anticipación máxima reserva). Todas ellas afectan directamente a requerimientos funcionales o no funcionales y son requisitos previos para la implementación.
- **Fundamento**: Es un riesgo porque todas las decisiones pendientes son bloqueadoras para al menos un requerimiento del sistema. DP-01 afecta RF-19 y RN-18. DP-02 afecta la experiencia del paciente pero no un requerimiento explícito. DP-05 afecta RF-11 y RF-12. DP-06 afecta RF-13 y RN-21. DP-07 afecta RNF-07. DP-08 afecta RF-08 y RN-11. Sin estas decisiones, el equipo estudiante no puede implementar el sistema de forma correcta y completa. El análisis de requerimientos está incompleto hasta que estas decisiones se tomen.

---

## Resumen por Categoría

| Categoría | Cantidad | Identificadores |
|-----------|----------|-----------------|
| Ambigüedad | 9 | OBS-01 a OBS-10 |
| Inconsistencia | 6 | OBS-11 a OBS-16 |
| Omisión | 8 | OBS-17 a OBS-24 |
| Duplicidad | 3 | OBS-25 a OBS-27 |
| Falta de verificabilidad | 5 | OBS-28 a OBS-32 |
| Riesgo | 17 | OBS-33 a OBS-49 |
| **Total** | **49** | **OBS-01 a OBS-49** |

## Decisiones Pendientes (DP) Referenciadas

| ID | Decisión | RF/RNF afectados |
|----|----------|------------------|
| DP-01 | Servicio externo de videollamadas | RF-19, RN-18, RN-19, RT-05, RO-06 |
| DP-02 | Servicios de correo/mensajería | — (mejora de experiencia) |
| DP-03 | Sincronización con calendarios externos | — (futuro) |
| DP-04 | Stack tecnológico | — (todas las capas) |
| DP-05 | Tiempo mínimo cancelar/reprogramar | RF-11, RF-12, RN-11, RN-12, RN-26 |
| DP-06 | Tiempo de tolerancia no-asistencia | RF-13, RN-21 |
| DP-07 | Timeout de sesión | RNF-07 |
| DP-08 | Anticipación máxima de reserva | RF-08, RN-11 |

## Requerimientos sin Observaciones Detectadas

Los siguientes requerimientos no generaron observaciones de ambigüedad, inconsistencia, omisión, duplicidad, falta de verificabilidad o riesgo en el análisis actual:

- **RF-04**: Asociar médicos con especialidades — relación many-to-many bien definida.
- **RF-06**: Consultar especialidades activas con médicos asociados — formulación clara.
- **RF-17**: Consultar disponibilidad de médicos/horarios — formulación clara.

## Nota Final

Este documento no decide la validez de ninguna observación. Cada hallazgo debe ser evaluado por el equipo estudiante para determinar si constituye un problema real que requiere resolución o si es aceptable dado el contexto y las restricciones del proyecto. No se modifican requerimientos ni se selecciona tecnología. El análisis se limita a documentar lo detectado para facilitar la toma de decisiones informada.

