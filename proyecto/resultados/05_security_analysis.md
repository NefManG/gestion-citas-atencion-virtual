# Análisis Especializado de Seguridad
## Sistema de Gestión de Citas y Atención Virtual

## 1. Objetivo

Realizar una revisión especializada de seguridad del Sistema de Gestión de Citas y Atención Virtual antes de la decisión arquitectónica final.

El análisis considera:

- autenticación;
- autorización;
- gestión de sesiones;
- protección de credenciales;
- protección de datos personales;
- seguridad de la integración externa;
- validación de entradas;
- amenazas web;
- auditoría;
- observabilidad;
- respaldo;
- recuperación;
- integridad;
- disponibilidad.

No se selecciona todavía una tecnología, proveedor o producto específico.

Las recomendaciones se mantienen a nivel arquitectónico y deberán validarse durante el diseño e implementación.

---

## 2. Fuentes analizadas

Se utilizaron como fuentes principales:

- `proyecto/contexto/descripcion.md`
- `proyecto/contexto/alcance.md`
- `proyecto/contexto/reglas_negocio.md` — RN-01 a RN-35
- `proyecto/contexto/restricciones.md`
- `proyecto/requisitos/RF.md` — RF-01 a RF-22
- `proyecto/requisitos/RNF.md` — RNF-01 a RNF-19
- `proyecto/requisitos/criterios_aceptacion.md` — CA-001 a CA-043 y CA-RNF-01 a CA-RNF-20
- `proyecto/resultados/01_requirements_analysis.md`
- `proyecto/resultados/02_decision_scope.md` — D-01 a D-23
- `proyecto/resultados/03_architecture_options.md`
- `proyecto/resultados/04_database_analysis.md`

Se consideran especialmente las decisiones:

- D-01 — integración con servicio externo;
- D-06 — separación entre Médico y Usuario;
- D-10 — permisos de admisión;
- D-12 — información mínima de auditoría;
- D-13 — sesión de 15 minutos de inactividad;
- D-14 — reglas detalladas de validación;
- D-19 — contingencia del servicio virtual;
- D-20 — conservación mínima académica de 5 años;
- D-21 — operación atómica para la reserva;
- D-22 — disponibilidad, RPO y RTO.

---

## 3. Activos y datos sensibles

### 3.1 Datos personales de pacientes

Incluyen:

- nombres;
- apellidos;
- documento de identidad;
- fecha de nacimiento;
- teléfono;
- correo electrónico.

Se consideran información que requiere protección contra acceso, modificación o divulgación no autorizada.

### 3.2 Información de citas

Incluye:

- paciente;
- médico;
- fecha;
- hora;
- modalidad;
- estado;
- historial de modificaciones.

La integridad de esta información es especialmente importante porque afecta directamente la atención y disponibilidad de horarios.

### 3.3 Credenciales

Las credenciales de los usuarios constituyen información crítica.

Nunca deberán almacenarse en texto plano.

### 3.4 Auditoría

Los registros definidos por D-12 contienen:

- fecha;
- hora;
- usuario responsable;
- acción realizada;
- valor anterior;
- valor actualizado.

Estos registros deben protegerse contra modificaciones o eliminaciones no autorizadas.

### 3.5 Información de atención virtual

Los enlaces, identificadores, tokens u otros elementos utilizados para acceder a una atención virtual deberán tratarse como información sensible.

### 3.6 Configuración de roles y permisos

Una modificación no autorizada de roles podría permitir elevación de privilegios y acceso a información restringida.

---

## 4. Roles y superficies de acceso

Los principales actores son:

### Paciente

Puede acceder a las funciones autorizadas relacionadas con:

- sus propias citas;
- historial propio;
- reserva;
- reprogramación;
- cancelación cuando corresponda.

Las funciones públicas definidas expresamente, como determinadas consultas de disponibilidad, podrán ser accesibles sin autenticación de acuerdo con RNF-04.

### Médico

Puede acceder a:

- su propia agenda;
- citas correspondientes a su atención;
- funciones autorizadas para inicio y finalización de atención.

La identidad profesional Médico debe mantenerse separada de la identidad de acceso Usuario.

### Personal de admisión

Podrá realizar las acciones autorizadas relacionadas con:

- registro de citas;
- reprogramación;
- cancelación;
- consulta de disponibilidad.

El alcance exacto continúa pendiente en D-10.

### Administrador

Puede gestionar, según los permisos definidos:

- pacientes;
- médicos;
- especialidades;
- usuarios;
- roles;
- configuraciones.

Estas capacidades deben estar sujetas a autenticación, autorización y auditoría.

### Usuario no autenticado

Solo podrá acceder a funciones declaradas explícitamente como públicas.

---

# 5. Autenticación

## 5.1 Requisitos

RNF-04 establece que las funciones protegidas requieren autenticación.

RNF-06 establece controles relacionados con credenciales y protección de información.

D-13 establece cierre automático de sesión después de:

**15 minutos de inactividad**, de forma configurable.

---

## SEC-01 — Autenticación obligatoria

**Severidad:** BLOCKER

**Relacionado con:** RNF-04, CA-RNF-05.

### Descripción

Toda operación protegida deberá comprobar la identidad del usuario antes de ejecutarse.

### Riesgo

Acceso no autorizado a información o funciones del sistema.

### Recomendación

La autenticación debe validarse en el servidor para cada operación protegida.

Las funciones públicas deberán estar expresamente identificadas.

### Estado

Requiere implementación.

---

## SEC-02 — Protección de contraseñas

**Severidad:** BLOCKER

**Relacionado con:** RNF-06, CA-RNF-07.

### Descripción

Las contraseñas no deberán almacenarse en texto plano.

### Riesgo

Una filtración de la base de datos podría comprometer inmediatamente las cuentas.

### Recomendación

Utilizar un mecanismo de hash adaptativo para contraseñas, con sal apropiada.

El algoritmo concreto se seleccionará posteriormente.

### Estado

Pendiente de decisión técnica.

---

## SEC-03 — Intentos repetidos de autenticación

**Severidad:** HIGH

**Relacionado con:** RNF-04, RNF-17.

### Descripción

No existe todavía un mecanismo definitivo para reducir intentos automatizados o repetidos de autenticación.

### Riesgo

Ataques de fuerza bruta o diccionario.

### Recomendación

Considerar:

- limitación de solicitudes;
- retrasos progresivos;
- bloqueo temporal configurable;
- registro de intentos fallidos.

### Estado

Requiere definición técnica.

---

## SEC-04 — Autenticación multifactor

**Severidad:** LOW

### Descripción

Los requisitos actuales no establecen MFA como obligatorio.

### Recomendación

Podrá evaluarse para cuentas de privilegios elevados si la institución lo considera necesario.

### Estado

Opcional.

---

# 6. Autorización y control de acceso

## SEC-05 — Acceso del paciente a sus propios datos

**Severidad:** BLOCKER

**Relacionado con:** RN-14, RNF-05, CA-022, CA-023, CA-024.

### Descripción

Un paciente autenticado solo deberá consultar o modificar las citas para las cuales tenga autorización.

Esto no impide que consulte información expresamente definida como pública, como especialidades, médicos o disponibilidad pública.

### Riesgo

Acceso a citas o información privada de otros pacientes.

### Recomendación

Validar la propiedad o autorización sobre el recurso en el servidor.

No confiar en identificadores enviados por el navegador como única comprobación.

### Estado

Requiere implementación.

---

## SEC-06 — Acceso del médico a su agenda

**Severidad:** BLOCKER

**Relacionado con:** RN-15, RNF-05, CA-025, CA-026.

### Descripción

Un médico deberá acceder únicamente a las citas y funciones correspondientes a su identidad profesional y permisos.

### Riesgo

Acceso no autorizado a agendas o citas de otros profesionales.

### Recomendación

La autorización deberá verificar la relación entre el Usuario autenticado y el Médico asociado.

### Estado

Requiere implementación.

---

## SEC-07 — Separación entre Médico y Usuario

**Severidad:** HIGH

**Relacionado con:** D-06, RN-33 a RN-35.

### Descripción

Médico y Usuario son conceptos diferentes.

Un Médico puede existir sin una cuenta de Usuario.

Un Usuario con rol Médico deberá estar asociado con un único Médico para ejecutar funciones clínicas.

### Riesgo

Una asociación incorrecta podría permitir acceso a la agenda de otro profesional.

### Recomendación

Mantener separadas:

- identidad profesional;
- identidad de autenticación.

La relación deberá garantizar que un Médico tenga como máximo una cuenta de Usuario asociada.

### Estado

Cubierto arquitectónicamente; requiere implementación.

---

## SEC-08 — Permisos de admisión

**Severidad:** HIGH

**Relacionado con:** D-10, RN-16.

### Descripción

El alcance exacto de permisos del personal de admisión continúa pendiente.

### Riesgo

Concesión excesiva de permisos.

### Recomendación

Definir expresamente qué puede:

- consultar;
- registrar;
- reprogramar;
- cancelar;
- modificar.

Todas las acciones deberán estar sujetas a las mismas reglas de negocio correspondientes.

### Estado

Pendiente de decisión D-10.

---

## SEC-09 — Permisos administrativos

**Severidad:** HIGH

**Relacionado con:** RN-17, RF-21, RNF-05.

### Descripción

Las funciones administrativas poseen capacidad elevada para modificar información del sistema.

### Riesgo

Una cuenta administrativa comprometida podría provocar modificaciones importantes.

### Recomendación

Aplicar:

- mínimo privilegio;
- autorización por operación;
- auditoría de acciones críticas.

### Estado

Requiere implementación.

---

## SEC-10 — Principio de mínimo privilegio

**Severidad:** MEDIUM

**Relacionado con:** RNF-05.

### Descripción

Cada Usuario deberá recibir únicamente los permisos necesarios para desarrollar sus funciones.

### Recomendación

Crear durante el diseño una matriz explícita de roles y permisos.

### Estado

Requiere definición detallada.

---

## SEC-37 — Separación de funciones administrativas

**Severidad:** LOW

### Descripción

Determinadas operaciones administrativas críticas podrían requerir controles adicionales.

### Recomendación

Evaluar, de acuerdo con la política institucional, si algunas acciones necesitan revisión o separación de responsabilidades.

No constituye un requisito obligatorio de la versión inicial.

### Estado

Pendiente de decisión institucional.

---

# 7. Gestión de sesiones

## SEC-11 — Cierre automático por inactividad

**Severidad:** HIGH

**Relacionado con:** D-13, RNF-07, CA-RNF-09.

### Descripción

Una sesión deberá finalizar después de 15 minutos consecutivos de inactividad.

El valor deberá ser configurable.

### Riesgo

Una sesión abandonada podría ser utilizada por otra persona.

### Recomendación

El servidor deberá hacer cumplir la expiración y requerir nueva autenticación después del vencimiento.

### Estado

Requiere implementación.

---

## SEC-12 — Protección de la sesión

**Severidad:** HIGH

**Relacionado con:** RNF-06, RNF-07.

### Riesgo

Robo o reutilización de credenciales de sesión.

### Recomendación

El mecanismo definitivo deberá contemplar, según la tecnología seleccionada:

- transporte seguro;
- protección frente a acceso desde scripts cuando corresponda;
- protección frente a solicitudes cruzadas cuando corresponda;
- expiración;
- renovación segura;
- revocación.

No se selecciona todavía JWT, cookies de sesión u otro mecanismo.

### Estado

Pendiente de decisión técnica.

---

## SEC-13 — Revocación de sesiones

**Severidad:** MEDIUM

### Descripción

La arquitectura deberá contemplar cómo invalidar una sesión cuando:

- el Usuario cierre sesión;
- la cuenta sea desactivada;
- exista una situación de seguridad que requiera revocarla.

### Estado

Requiere definición técnica.

---

# 8. Protección de datos

## SEC-14 — Cifrado de información en tránsito

**Severidad:** BLOCKER

**Relacionado con:** RNF-06, CA-RNF-08.

### Descripción

Las comunicaciones protegidas deberán utilizar transporte cifrado.

### Riesgo

Intercepción de:

- credenciales;
- información personal;
- citas;
- información de atención virtual.

### Recomendación

Utilizar TLS para las comunicaciones protegidas.

La configuración concreta se establecerá posteriormente.

### Estado

Requiere implementación.

---

## SEC-15 — Protección de datos almacenados

**Severidad:** HIGH

**Relacionado con:** RNF-06.

### Descripción

La información personal almacenada deberá protegerse contra acceso no autorizado.

### Recomendación

Evaluar durante el diseño:

- cifrado del almacenamiento;
- cifrado selectivo de información especialmente sensible;
- control de acceso;
- gestión segura de claves.

La necesidad de cifrado por columna deberá determinarse posteriormente.

### Estado

Pendiente de decisión técnica.

---

## SEC-16 — Credenciales almacenadas

**Severidad:** BLOCKER

**Relacionado con:** RNF-06, CA-RNF-07.

### Descripción

Las contraseñas nunca deberán almacenarse como texto plano ni escribirse en logs.

### Estado

Requiere implementación.

---

## SEC-17 — Información de acceso a atención virtual

**Severidad:** HIGH

**Relacionado con:** D-01, D-19, RN-18, RN-19.

### Descripción

Los enlaces, identificadores o credenciales asociados a una sesión virtual deberán tratarse como información sensible.

### Recomendación

Restringir su acceso a usuarios autorizados relacionados con la cita y evitar su exposición innecesaria en registros técnicos.

### Estado

Requiere implementación.

---

# 9. Seguridad de la integración externa

## SEC-18 — Desacoplamiento del proveedor

**Severidad:** HIGH

**Relacionado con:** D-01.

### Descripción

La lógica central del sistema no deberá depender directamente de un proveedor concreto.

### Recomendación

Utilizar una abstracción o adaptador para la integración.

### Estado

Cubierto arquitectónicamente; requiere implementación.

---

## SEC-19 — Credenciales del servicio externo

**Severidad:** HIGH

**Relacionado con:** D-01, RNF-06.

### Riesgo

La exposición de credenciales del proveedor permitiría uso no autorizado del servicio.

### Recomendación

Las credenciales no deberán almacenarse en:

- código fuente;
- repositorio Git;
- archivos públicos;
- logs.

Deberá utilizarse un mecanismo seguro de gestión de secretos.

### Estado

Pendiente de selección técnica.

---

## SEC-20 — Validación de respuestas externas

**Severidad:** HIGH

**Relacionado con:** D-01, D-19.

### Descripción

La información recibida desde el proveedor externo deberá considerarse entrada externa y validarse.

### Riesgo

Una respuesta incorrecta o manipulada podría provocar comportamiento no esperado.

### Recomendación

Validar:

- formato;
- autenticidad cuando corresponda;
- identificadores;
- relación con la cita;
- estados técnicos de integración.

**La respuesta del proveedor no deberá cambiar automáticamente el estado clínico de la cita.**

El cambio:

**Confirmada → En atención**

continúa siendo responsabilidad del médico de acuerdo con las reglas definidas.

### Estado

Requiere implementación.

---

## SEC-21 — Fallo del servicio de atención virtual

**Severidad:** HIGH

**Relacionado con:** D-19, RN-29 a RN-32, CA-041 a CA-043.

### Descripción

Un fallo del servicio externo no deberá:

- eliminar la cita;
- cancelarla automáticamente;
- marcarla automáticamente como no asistida;
- cambiar automáticamente su modalidad.

### Recomendación

Mantener el estado válido actual de la cita.

La incidencia técnica deberá registrarse **separadamente** de los estados oficiales de la cita.

No se crea un nuevo estado denominado `"Pendiente de resolución"` porque ese estado no forma parte del modelo actual.

El sistema deberá permitir posteriormente las acciones autorizadas definidas en D-19.

### Estado

Cubierto arquitectónicamente; requiere implementación.

---

## SEC-22 — Comunicación con el servicio externo

**Severidad:** HIGH

**Relacionado con:** RNF-06, D-01.

### Recomendación

Utilizar comunicación cifrada, validar certificados y evitar exposición innecesaria de información sensible.

### Estado

Requiere implementación.

---

# 10. Validación de entradas y amenazas web

## SEC-23 — Validación del lado del servidor

**Severidad:** BLOCKER

**Relacionado con:** RNF-12, D-14, CA-RNF-14.

### Descripción

Todas las entradas deberán validarse en el servidor.

La validación del navegador constituye únicamente una ayuda para el usuario y no una barrera de seguridad suficiente.

### Estado

Requiere implementación.

---

## SEC-24 — Inyección

**Severidad:** BLOCKER

**Relacionado con:** RNF-06, RNF-12.

### Riesgo

Manipulación de consultas o comandos mediante entradas del usuario.

### Recomendación

Utilizar mecanismos seguros de acceso a datos, como:

- consultas parametrizadas;
- parámetros tipados;
- APIs seguras del mecanismo de persistencia.

Nunca construir consultas concatenando directamente entradas no confiables.

### Estado

Requiere implementación.

---

## SEC-25 — Cross-Site Scripting (XSS)

**Severidad:** BLOCKER

**Relacionado con:** RNF-06, RNF-12.

### Riesgo

Ejecución de contenido activo introducido por un atacante en el navegador de otro usuario.

### Recomendación

Aplicar:

- codificación segura de salida;
- mecanismos de renderizado que escapen contenido no confiable;
- validación;
- políticas de seguridad del navegador cuando corresponda.

### Estado

Requiere implementación.

---

## SEC-26 — Cross-Site Request Forgery (CSRF)

**Severidad:** HIGH

**Relacionado con:** RNF-04, RNF-05.

### Descripción

El riesgo depende del mecanismo de autenticación finalmente seleccionado.

Es especialmente relevante cuando el navegador envía automáticamente credenciales de sesión.

### Recomendación

Si la solución utiliza autenticación basada en cookies, deberán evaluarse controles como:

- tokens anti-CSRF;
- SameSite;
- comprobación de origen;
- otras defensas equivalentes.

### Estado

Pendiente del mecanismo de autenticación.

---

## SEC-27 — Gestión segura de errores

**Severidad:** MEDIUM

**Relacionado con:** RNF-17.

### Descripción

Los mensajes enviados al usuario no deberán exponer:

- rutas internas;
- consultas;
- credenciales;
- configuraciones;
- detalles innecesarios de infraestructura.

### Recomendación

Mostrar mensajes comprensibles al usuario y mantener la información técnica necesaria únicamente en registros autorizados.

### Estado

Requiere implementación.

---

## SEC-28 — Formatos de datos

**Severidad:** MEDIUM

**Relacionado con:** D-14, RNF-12, CA-RNF-14.

### Descripción

Las reglas detalladas para documento, teléfono, correo electrónico y otros campos todavía no están totalmente definidas.

### Recomendación

Definir para cada dato:

- obligatoriedad;
- longitud;
- formato;
- rango;
- valores permitidos.

### Estado

Pendiente D-14.

---

# 11. Auditoría y trazabilidad

## SEC-29 — Auditoría con seis campos

**Severidad:** BLOCKER

**Relacionado con:** RF-22, RNF-18, D-12.

Cada modificación relevante deberá almacenar:

1. fecha;
2. hora;
3. usuario responsable;
4. acción realizada;
5. valor anterior;
6. valor actualizado.

### Riesgo

Sin trazabilidad no será posible reconstruir modificaciones relevantes.

### Estado

Requiere implementación.

---

## SEC-30 — Protección de auditoría

**Severidad:** HIGH

**Relacionado con:** RNF-18, D-20, CA-039.

### Descripción

Los usuarios normales del sistema no deberán poder modificar o eliminar libremente registros históricos de auditoría.

### Recomendación

Diseñar la auditoría con comportamiento equivalente a append-only para las operaciones normales.

Cualquier eliminación posterior al periodo mínimo de conservación deberá realizarse únicamente mediante procedimiento autorizado.

### Estado

Requiere implementación.

---

## SEC-31 — Auditoría y observabilidad

**Severidad:** MEDIUM

**Relacionado con:** RNF-17, RNF-18.

### Descripción

Son conceptos diferentes.

### Auditoría

Responde principalmente:

**Quién hizo qué, cuándo y qué cambió.**

### Observabilidad

Permite diagnosticar:

- errores;
- fallos técnicos;
- intentos fallidos;
- problemas de integración;
- comportamiento operativo.

### Estado

Cubierto arquitectónicamente; requiere implementación.

---

## SEC-32 — Registro de eventos de seguridad

**Severidad:** MEDIUM

**Relacionado con:** RNF-17, CA-RNF-17.

Deberán registrarse eventos relevantes como:

- errores;
- intentos fallidos de autenticación;
- fallos en operaciones críticas;
- errores de integración;
- incidentes técnicos relevantes.

Los logs no deberán contener contraseñas, tokens secretos u otra información sensible innecesaria.

### Estado

Requiere implementación.

---

# 12. Disponibilidad, respaldo e integridad

Los objetivos establecidos son:

- disponibilidad mensual ≥ 99 %;
- RPO ≤ 60 minutos;
- RTO ≤ 120 minutos.

Estos objetivos están definidos arquitectónicamente, pero todavía requieren infraestructura y pruebas.

---

## SEC-33 — Protección frente a abuso de recursos y DoS

**Severidad:** MEDIUM

**Relacionado con:** RNF-08, D-22.

### Recomendación

Evaluar:

- rate limiting;
- límites de solicitudes;
- timeouts;
- límites de tamaño;
- controles en infraestructura;
- protección adicional según el entorno de despliegue.

### Estado

Pendiente de decisión técnica.

---

## SEC-34 — Integridad de respaldos

**Severidad:** HIGH

**Relacionado con:** RNF-09, RNF-10, D-22.

### Descripción

Crear copias de respaldo no garantiza por sí mismo la recuperación.

### Recomendación

Realizar pruebas de restauración para comprobar que los respaldos son utilizables.

### Estado

Requiere implementación y validación.

---

## SEC-35 — Protección de respaldos

**Severidad:** HIGH

**Relacionado con:** RNF-06, RNF-09.

### Descripción

Los respaldos pueden contener una copia completa de la información sensible del sistema.

### Recomendación

Protegerlos mediante:

- cifrado cuando corresponda;
- control de acceso;
- almacenamiento separado;
- monitoreo;
- procedimientos autorizados.

La política de conservación de **backups** deberá definirse separadamente.

D-20 establece la conservación mínima de citas históricas y auditoría, pero no debe interpretarse automáticamente como una política completa de retención de archivos de respaldo.

### Estado

Requiere definición técnica.

---

## SEC-36 — Modificación o eliminación no autorizada de citas

**Severidad:** HIGH

**Relacionado con:** RN-09, RN-25, CA-021.

### Descripción

Las transiciones de estado deberán respetar las reglas de negocio.

Las citas finalizadas o canceladas no podrán volver arbitrariamente a estados anteriores.

### Recomendación

Validar toda transición en la lógica de negocio y registrar los cambios mediante auditoría.

### Estado

Requiere implementación.

---

# 13. Resumen de hallazgos

## 13.1 BLOCKER

Se identificaron **10 hallazgos BLOCKER**:

- SEC-01 — Autenticación obligatoria.
- SEC-02 — Protección de contraseñas.
- SEC-05 — Acceso del paciente a sus propios datos.
- SEC-06 — Acceso del médico a su propia agenda.
- SEC-14 — Cifrado en tránsito.
- SEC-16 — Protección de credenciales almacenadas.
- SEC-23 — Validación del lado del servidor.
- SEC-24 — Prevención de inyección.
- SEC-25 — Prevención de XSS.
- SEC-29 — Auditoría con los seis campos.

## 13.2 HIGH

Se identificaron **18 hallazgos HIGH**:

- SEC-03
- SEC-07
- SEC-08
- SEC-09
- SEC-11
- SEC-12
- SEC-15
- SEC-17
- SEC-18
- SEC-19
- SEC-20
- SEC-21
- SEC-22
- SEC-26
- SEC-30
- SEC-34
- SEC-35
- SEC-36

## 13.3 MEDIUM

Se identificaron **7 hallazgos MEDIUM**:

- SEC-10
- SEC-13
- SEC-27
- SEC-28
- SEC-31
- SEC-32
- SEC-33

## 13.4 LOW

Se identificaron **2 hallazgos LOW**:

- SEC-04 — MFA opcional.
- SEC-37 — Separación adicional de funciones administrativas.

### Total

**37 hallazgos de seguridad.**

---

# 14. Controles arquitectónicos recomendados

Los controles principales a mantener durante las siguientes fases son:

- autenticación obligatoria para funciones protegidas;
- autorización por rol y por recurso;
- mínimo privilegio;
- separación Médico–Usuario;
- contraseñas protegidas mediante hash;
- sesiones con expiración por inactividad;
- cifrado de comunicaciones;
- gestión segura de secretos;
- validación del lado del servidor;
- prevención de inyección;
- prevención de XSS;
- protección CSRF cuando sea aplicable;
- auditoría independiente;
- logs operativos separados;
- protección de respaldos;
- pruebas de restauración;
- validación de la integración externa;
- no permitir que fallos del proveedor alteren automáticamente el estado de una cita.

---

# 15. Decisiones pendientes

Continúan pendientes:

### SEC-D-01
Algoritmo concreto para protección de contraseñas.

### SEC-D-02
Mecanismo concreto de protección frente a intentos repetidos.

### SEC-D-03
Mecanismo de sesión y autenticación.

### SEC-D-04
Alcance del cifrado de información en reposo.

### SEC-D-05
Configuración técnica de TLS.

### SEC-D-06
Mecanismo concreto de almacenamiento de secretos del servicio externo.

### SEC-D-07
Permisos exactos del personal de admisión según D-10.

### SEC-D-08
Necesidad institucional de separación adicional de funciones administrativas.

### SEC-D-09
Reglas detalladas de validación según D-14.

### SEC-D-10
Mecanismos concretos de protección frente a abuso de recursos y DoS.

### SEC-D-11
Configuración de políticas adicionales del navegador, incluida CSP.

### SEC-D-12
Procedimiento definitivo de respaldo, restauración y prueba de recuperación.

---

# 16. Conclusión

El análisis identifica **37 hallazgos**:

- 10 BLOCKER;
- 18 HIGH;
- 7 MEDIUM;
- 2 LOW.

Los hallazgos BLOCKER representan controles que deberán estar presentes antes de considerar una implementación preparada para uso real.

La documentación actual permite establecer una arquitectura compatible con controles adecuados de:

- autenticación;
- autorización;
- protección de credenciales;
- gestión de sesiones;
- cifrado;
- validación;
- auditoría;
- integración externa;
- recuperación.

Sin embargo, todavía existen decisiones pendientes, principalmente:

- permisos exactos del personal de admisión;
- reglas detalladas de validación;
- mecanismo de autenticación y sesión;
- mecanismos concretos de cifrado;
- gestión de secretos;
- procedimientos de respaldo y recuperación.

No se ha seleccionado una tecnología, proveedor o producto específico.

Los controles descritos constituyen requisitos y recomendaciones arquitectónicas que deberán verificarse durante implementación y pruebas.

La revisión de seguridad no sustituye la revisión arquitectónica final.

**STATUS: REVIEWED — CONTROLES DE SEGURIDAD IDENTIFICADOS; PENDIENTE DE IMPLEMENTACIÓN, DECISIONES TÉCNICAS Y REVISIÓN ARQUITECTÓNICA FINAL.**