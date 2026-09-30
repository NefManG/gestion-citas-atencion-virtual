# Análisis de Estrategia de Persistencia
## Sistema de Gestión de Citas y Atención Virtual

## 1. Alcance y decisiones previas

El presente análisis evalúa estrategias de persistencia para el Sistema de Gestión de Citas y Atención Virtual.

Se consideran como fuentes normativas principales:

- `proyecto/requisitos/RF.md` — RF-01 a RF-22.
- `proyecto/requisitos/RNF.md` — RNF-01 a RNF-19.
- `proyecto/requisitos/criterios_aceptacion.md` — CA-001 a CA-043 y CA-RNF-01 a CA-RNF-20.
- `proyecto/contexto/reglas_negocio.md` — RN-01 a RN-35.
- `proyecto/resultados/02_decision_scope.md` — D-01 a D-23.

También se consideran como documentos derivados de análisis:

- `proyecto/resultados/01_requirements_analysis.md`.
- `proyecto/resultados/03_architecture_options.md`.
- `proyecto/resultados/07_architecture_review.md`.

De acuerdo con D-23, los documentos derivados no sustituyen a las fuentes normativas.

### 1.1 Decisiones relevantes ya resueltas

**D-12 — Información mínima de auditoría**

Cada modificación relevante de una cita deberá conservar como mínimo:

- fecha;
- hora;
- usuario responsable;
- acción realizada;
- valor anterior;
- valor actualizado.

**D-20 — Conservación de información**

Para el alcance académico del proyecto se establece una conservación mínima de 5 años para:

- citas finalizadas;
- citas canceladas;
- citas no asistidas;
- registros de auditoría.

Finalizado este periodo, la información podrá mantenerse, archivarse o eliminarse mediante un procedimiento autorizado de acuerdo con la política institucional aplicable.

No se establece eliminación automática obligatoria.

**D-21 — Control de concurrencia**

La confirmación de una cita deberá ejecutarse como una operación atómica.

La verificación final de disponibilidad y la confirmación deberán formar parte de la misma operación transaccional.

El mecanismo técnico concreto de concurrencia continúa pendiente de selección.

**D-22 — Disponibilidad y recuperación**

La solución deberá permitir diseñar una infraestructura capaz de alcanzar:

- disponibilidad mensual igual o superior al 99 %;
- RPO máximo de 60 minutos;
- RTO máximo de 120 minutos.

**D-06 — Médico y Usuario**

Médico y Usuario representan conceptos diferentes.

Un Médico puede existir sin cuenta de Usuario y podrá tener cero o una cuenta asociada para acceder a funciones protegidas del sistema.

### 1.2 Decisión que todavía no se ha tomado

En esta etapa todavía **no se selecciona un motor específico de base de datos**.

La selección deberá realizarse posteriormente utilizando:

- los requisitos;
- las decisiones arquitectónicas;
- el análisis especializado;
- la infraestructura disponible;
- los resultados de pruebas.

---

## 2. Características de los datos

La información principal del sistema presenta relaciones estructuradas y reglas fuertes de integridad.

Las entidades conceptuales principales son:

### Paciente

Relacionado con:

- citas;
- historial;
- información de contacto.

### Médico

Relacionado con:

- especialidades;
- horarios;
- disponibilidad;
- citas;
- agenda;
- Usuario cuando corresponda.

### Especialidad

Relacionada con:

- médicos;
- disponibilidad de atención.

### Horario o bloque de disponibilidad

Relacionado con:

- médico;
- fecha;
- franja horaria;
- modalidad;
- disponibilidad;
- citas.

### Cita

Representa una de las entidades transaccionales principales.

Debe mantener relaciones con:

- paciente;
- médico;
- especialidad cuando corresponda;
- horario;
- modalidad;
- estado;
- historial de modificaciones.

### Usuario

Relacionado con:

- autenticación;
- autorización;
- roles;
- auditoría;
- Médico cuando corresponda.

### Historial/Auditoría

Relacionado con:

- cita;
- usuario responsable;
- modificaciones realizadas.

Por la naturaleza de estas relaciones, la persistencia deberá ofrecer mecanismos sólidos para:

- integridad referencial;
- transacciones;
- concurrencia;
- restricciones;
- consultas estructuradas;
- auditoría.

---

## 3. Requisitos críticos para la persistencia

La estrategia seleccionada deberá facilitar el cumplimiento de los siguientes aspectos.

### Integridad

Debe garantizarse que:

- una cita corresponda a un paciente y a un médico;
- las relaciones entre médicos y especialidades sean válidas;
- los horarios pertenezcan al médico correspondiente;
- los estados y relaciones de las citas sean consistentes.

### Concurrencia

Debe evitarse que dos operaciones concurrentes confirmen el mismo horario incompatible.

### Atomicidad

Operaciones como:

- reserva;
- reprogramación;
- cancelación;

deben ejecutarse de manera consistente aunque ocurra un fallo durante el proceso.

### Auditoría

Debe mantenerse un historial independiente que contenga los seis campos mínimos definidos en D-12.

### Conservación

Los datos históricos y de auditoría deben poder conservarse durante el periodo establecido en D-20.

### Escalabilidad

La estrategia deberá permitir evolucionar desde:

- 500 transacciones por hora como carga normal;
- 1.000 transacciones por hora como pico inmediato;
- hasta 5.000 transacciones por hora como escenario futuro.

### Recuperación

La persistencia deberá ser compatible con una estrategia capaz de alcanzar:

- RPO ≤ 60 minutos;
- RTO ≤ 120 minutos.

---

## 4. Alternativas evaluadas

## 4.1 Alternativa A — Persistencia relacional transaccional

### Características

Utiliza un modelo relacional estructurado con:

- relaciones explícitas;
- restricciones;
- claves;
- índices;
- transacciones ACID;
- mecanismos de control de concurrencia.

### Ventajas

- Alta correspondencia con las relaciones del dominio.
- Integridad referencial declarativa.
- Buen soporte para operaciones transaccionales.
- Facilita la atomicidad de reservas y reprogramaciones.
- Permite utilizar restricciones como última línea de defensa ante inconsistencias.
- Facilita consultas combinadas entre pacientes, médicos, horarios y citas.
- Amplio soporte para índices y optimización.

### Desventajas

- Requiere diseño cuidadoso del modelo.
- La escalabilidad de escritura puede requerir optimización posterior.
- Los mecanismos de bloqueo mal configurados pueden incrementar tiempos de espera.
- El crecimiento histórico puede requerir archivado o particionamiento.

### Ajuste al proyecto

**Alto.**

La mayoría de los datos del sistema son estructurados y mantienen relaciones fuertes.

Ejemplos de motores que podrían implementar esta estrategia incluyen PostgreSQL, SQL Server, MySQL/MariaDB u otros motores relacionales con soporte transaccional.

La mención de estos motores no implica una selección.

---

## 4.2 Alternativa B — Persistencia documental / NoSQL

### Características

Utiliza documentos u otras estructuras no relacionales.

Puede proporcionar:

- esquemas flexibles;
- distribución de datos;
- escalabilidad horizontal;
- modelos optimizados para determinados patrones de acceso.

### Ventajas

- Flexibilidad de estructura.
- Puede ser adecuada para información poco estructurada.
- Algunos motores permiten distribución horizontal de manera natural.
- Puede ofrecer buen rendimiento en determinados patrones de lectura.

### Desventajas

Para este proyecto se requiere especial atención a:

- relaciones entre múltiples entidades;
- restricciones de horarios;
- atomicidad de reservas;
- reprogramaciones;
- auditoría;
- consistencia de información.

Algunos motores NoSQL poseen transacciones, pero sus garantías y mecanismos varían según el producto.

Por ello, no puede asumirse que una estrategia documental simplifique automáticamente las reglas de concurrencia del proyecto.

### Ajuste al proyecto

**Medio a bajo como persistencia principal.**

Podría utilizarse posteriormente para determinados casos auxiliares, pero las reglas actuales favorecen una estrategia con fuertes garantías transaccionales.

---

## 4.3 Alternativa C — Estrategia híbrida

### Características

Mantiene un almacenamiento transaccional principal y añade otro mecanismo para necesidades específicas.

Por ejemplo:

- base transaccional para citas;
- almacenamiento auxiliar para búsqueda;
- caché;
- consultas de lectura;
- analítica.

### Ventajas

- Permite optimizar cargas específicas.
- Separa determinadas lecturas de las escrituras críticas.
- Puede mejorar escalabilidad futura.
- Mantiene consistencia fuerte en la parte transaccional.

### Desventajas

- Incrementa la cantidad de componentes.
- Requiere sincronización.
- Puede introducir consistencia eventual en algunos modelos de lectura.
- Incrementa observabilidad y mantenimiento.
- Requiere resolver qué almacenamiento constituye la fuente de verdad.

### Ajuste al proyecto

**Viable como evolución.**

No existe evidencia actual suficiente que justifique asumir esta complejidad desde la primera versión.

---

## 4.4 Alternativa D — Persistencia relacional con evolución posterior

### Características

Parte de una persistencia relacional transaccional centralizada y permite introducir progresivamente:

- índices adicionales;
- réplicas de lectura;
- particionamiento;
- archivado;
- separación de cargas;
- optimización especializada.

### Ventajas

- Mantiene simplicidad inicial.
- Conserva fuertes garantías transaccionales.
- Permite evolucionar según carga real.
- Se adapta al escenario 10× definido en D-18.
- Evita añadir componentes distribuidos antes de necesitarlos.
- Permite preparar estrategias de archivado para D-20.

### Desventajas

- Requiere planificación para evitar que el diseño inicial bloquee la evolución.
- Las réplicas pueden introducir retrasos de lectura.
- El particionamiento agrega complejidad operativa.
- La escalabilidad de escritura sigue requiriendo análisis específico.

### Ajuste al proyecto

**Alto.**

Esta alternativa permite combinar:

- simplicidad inicial;
- consistencia fuerte;
- capacidad de crecimiento posterior.

---

## 5. Comparación de alternativas

| Aspecto | A. Relacional | B. Documental/NoSQL | C. Híbrida | D. Relacional evolutiva |
|---|---|---|---|---|
| Integridad referencial | Alta | Variable | Alta en almacenamiento principal | Alta |
| Transacciones críticas | Alta | Depende del motor | Alta en componente transaccional | Alta |
| Control de concurrencia | Alto | Depende del motor/diseño | Alto en escritura | Alto |
| Auditoría | Viable | Requiere diseño explícito | Viable | Viable |
| Complejidad inicial | Baja/Media | Media | Alta | Media |
| Escalabilidad de lectura | Media | Alta | Alta | Alta mediante evolución |
| Escalabilidad de escritura | Media | Variable | Variable | Requiere evolución |
| Mantenibilidad | Alta | Media | Media/Baja | Alta/Media |
| Adecuación al dominio | Alta | Media/Baja | Alta | Alta |

---

## 6. Concurrencia y prevención de doble reserva

Uno de los requisitos más importantes para persistencia es RNF-11 junto con D-21.

Cuando dos o más solicitudes intenten reservar simultáneamente el mismo horario:

- únicamente una deberá confirmarse;
- las demás deberán recibir una respuesta de conflicto;
- la persistencia deberá impedir que ambas operaciones terminen correctamente.

No es suficiente realizar únicamente:

1. consulta de disponibilidad;
2. validación desde la interfaz;
3. confirmación posterior.

Entre la consulta y la confirmación otro usuario podría reservar el horario.

Por ello, la operación final deberá ejecutarse dentro de una transacción.

La estrategia de persistencia deberá permitir posteriormente evaluar mecanismos como:

- bloqueo pesimista;
- control optimista;
- restricciones únicas;
- restricciones de exclusión cuando existan;
- niveles adecuados de aislamiento;
- mecanismos equivalentes.

No se selecciona todavía uno de estos mecanismos.

La restricción de persistencia deberá utilizarse como última línea de defensa para evitar inconsistencias incluso cuando exista validación en la aplicación.

---

## 7. Reprogramación y consistencia transaccional

La reprogramación requiere especial atención porque implica dos recursos:

- nuevo horario;
- horario anterior.

El comportamiento esperado es:

1. comprobar que el nuevo horario continúa disponible;
2. intentar reservarlo;
3. actualizar la cita;
4. liberar el horario anterior;
5. confirmar la operación completa.

Si cualquiera de estas acciones falla, la operación no deberá dejar una cita en un estado inconsistente.

Por ello, la reprogramación deberá tratarse como una operación transaccional.

La cancelación también deberá realizar de forma consistente:

- actualización del estado;
- liberación del horario;
- registro de auditoría correspondiente.

---

## 8. Auditoría, observabilidad y conservación

Estos conceptos deben mantenerse separados.

### 8.1 Auditoría funcional

Relacionada con RF-22, RNF-18 y D-12.

Cada modificación relevante de una cita deberá conservar:

- fecha;
- hora;
- usuario responsable;
- acción;
- valor anterior;
- valor actualizado.

La auditoría podrá implementarse posteriormente mediante:

- capa de aplicación;
- mecanismos del motor;
- combinación de ambos.

La elección todavía está pendiente.

### 8.2 Observabilidad

RNF-17 corresponde a información técnica utilizada para:

- diagnóstico;
- monitoreo;
- investigación de errores;
- fallas de integración;
- fallas operativas.

Los registros técnicos de observabilidad no sustituyen la auditoría funcional.

### 8.3 Respaldo

El respaldo protege contra pérdida de información y forma parte de la estrategia necesaria para cumplir RPO.

No sustituye:

- historial;
- auditoría;
- archivado.

### 8.4 Recuperación

La recuperación comprende los procedimientos utilizados para restaurar el servicio y la información después de una falla.

Debe diseñarse para posibilitar un:

**RTO máximo de 120 minutos.**

### 8.5 Conservación

D-20 establece para el alcance académico:

**5 años como periodo mínimo de conservación.**

Transcurrido ese periodo, la información podrá:

- mantenerse;
- archivarse;
- eliminarse mediante procedimiento autorizado.

No deberá existir una eliminación automática obligatoria simplemente por alcanzar los cinco años.

---

## 9. Volumen y crecimiento

Los escenarios definidos son:

| Escenario | Carga |
|---|---:|
| Normal inicial | 500 transacciones/hora |
| Pico inmediato | 1.000 transacciones/hora |
| Futuro 10× | 5.000 transacciones/hora |
| Usuarios concurrentes iniciales | Hasta 100 |

Estos valores no representan todavía pruebas realizadas.

Son objetivos que deberán comprobarse posteriormente.

### Alternativa A

Es compatible conceptualmente con la carga inicial, pero el comportamiento real dependerá de:

- índices;
- consultas;
- infraestructura;
- conexiones;
- modelo de datos.

El escenario 10× deberá validarse.

### Alternativa B

Puede ofrecer escalabilidad horizontal, pero deberá comprobarse que sus garantías de consistencia satisfagan las reglas críticas de reserva.

### Alternativa C

Permite separar determinadas cargas, aunque incrementa complejidad.

### Alternativa D

Permite comenzar con una arquitectura sencilla y evolucionar mediante:

- optimización;
- índices;
- réplicas;
- particionamiento;
- separación de cargas.

Por ello presenta una evolución compatible con D-18 sin exigir desde la primera versión toda la infraestructura futura.

---

## 10. Índices

El diseño definitivo deberá evaluar índices para las consultas frecuentes.

Entre los atributos candidatos se encuentran:

- médico;
- fecha;
- horario;
- estado;
- paciente;
- especialidad;
- modalidad;
- fecha de auditoría.

También deberán evaluarse índices compuestos para operaciones frecuentes como:

- disponibilidad de un médico por fecha;
- agenda de un médico;
- citas de un paciente;
- historial por paciente y fecha;
- búsqueda de horarios libres.

Los índices concretos deberán definirse utilizando el modelo físico y las consultas reales.

No corresponde establecerlos definitivamente en esta etapa.

---

## 11. Respaldo, recuperación y disponibilidad

Los objetivos definidos son:

### Disponibilidad

**≥ 99 % mensual**

La persistencia deberá poder integrarse con una infraestructura que evite depender de un único punto de fallo sin estrategia de recuperación.

### RPO

**≤ 60 minutos**

La estrategia de respaldo deberá permitir recuperar información con una pérdida máxima compatible con este objetivo.

### RTO

**≤ 120 minutos**

El procedimiento de recuperación deberá ser diseñado y probado.

Ninguna alternativa puede considerarse actualmente como "cumplidora" únicamente por disponer de mecanismos técnicos.

El cumplimiento real dependerá de:

- configuración;
- infraestructura;
- automatización;
- monitoreo;
- procedimientos;
- pruebas de recuperación.

Por tanto, los estados actuales son:

| Requisito | Estado |
|---|---|
| Disponibilidad ≥ 99 % | Requiere validación de infraestructura |
| RPO ≤ 60 minutos | Requiere diseño y prueba |
| RTO ≤ 120 minutos | Requiere diseño y prueba |
| RNF-01 ≤ 3 segundos | Requiere pruebas de rendimiento |
| RNF-11 concurrencia | Arquitectónicamente viable; mecanismo pendiente |

La definición concreta será profundizada durante el análisis DevOps.

---

## 12. Riesgos identificados

### R-DB-01 — Doble reserva

Si la garantía depende únicamente de la aplicación, dos solicitudes concurrentes podrían confirmar un mismo horario.

**Mitigación:** combinar transacción y mecanismo de integridad en persistencia.

### R-DB-02 — Bloqueos prolongados

Una estrategia de locking demasiado agresiva puede afectar RNF-01.

**Mitigación:** seleccionar el mecanismo después de pruebas de concurrencia.

### R-DB-03 — Crecimiento del historial

Cinco años de auditoría e historial incrementarán progresivamente el volumen.

**Mitigación:** evaluar archivado, particionamiento e índices.

### R-DB-04 — Réplicas con retraso

Las réplicas de lectura pueden presentar información temporalmente atrasada.

**Mitigación:** las validaciones críticas de disponibilidad deberán realizarse contra la fuente transaccional autorizada.

### R-DB-05 — Complejidad innecesaria

Introducir CQRS, varios almacenes o distribución desde la primera versión puede aumentar costo y mantenimiento sin necesidad comprobada.

**Mitigación:** evolución basada en evidencia de carga.

### R-DB-06 — Auditoría incompleta

Una implementación que registre únicamente fecha, hora y usuario incumpliría D-12.

**Mitigación:** validar siempre los seis campos mínimos.

### R-DB-07 — Confundir respaldo con historial

Un backup no sustituye una política de conservación accesible para consultas autorizadas.

**Mitigación:** manejar respaldo, auditoría y archivado como conceptos independientes.

---

## 13. Complejidad operativa

| Aspecto | Relacional | Documental/NoSQL | Híbrida | Relacional evolutiva |
|---|---|---|---|---|
| Administración inicial | Media | Media | Alta | Media |
| Control transaccional | Directo | Depende del motor | Directo en componente principal | Directo |
| Auditoría | Media | Media/Alta | Alta | Media |
| Respaldo | Estándar según motor | Específico según motor | Múltiples componentes | Estándar + evolución |
| Monitoreo | Medio | Medio | Alto | Medio/Alto |
| Evolución futura | Media | Alta | Alta | Alta |
| Cantidad de componentes iniciales | Baja | Baja | Alta | Baja |

---

## 14. Decisiones técnicas pendientes

El análisis de persistencia no debe resolver todavía las siguientes decisiones de implementación:

1. Motor específico de base de datos.

2. Nivel de aislamiento de las transacciones.

3. Mecanismo definitivo de concurrencia:
   - pesimista;
   - optimista;
   - restricciones declarativas;
   - combinación.

4. Implementación definitiva de auditoría:
   - capa de aplicación;
   - mecanismos de base de datos;
   - combinación.

5. Estrategia concreta de particionamiento.

6. Necesidad real y momento de introducir réplicas.

7. Procedimiento definitivo de backup.

8. Procedimiento de restauración.

9. Mecanismo de failover.

10. Infraestructura utilizada para alcanzar disponibilidad, RPO y RTO.

11. Política institucional definitiva posterior al periodo académico de conservación de 5 años.

---

## 15. Recomendación especializada provisional

### Estrategia provisionalmente más adecuada

**Alternativa D — Persistencia relacional transaccional con capacidad de evolución posterior mediante optimización, réplicas, particionamiento o separación de cargas.**

Esta recomendación se refiere únicamente a la **estrategia de persistencia**.

No constituye una selección de motor.

### Justificación

#### Consistencia

El dominio contiene numerosas relaciones e invariantes que favorecen un modelo con integridad referencial fuerte.

#### Concurrencia

La reserva de citas requiere una garantía transaccional sólida para impedir dobles reservas.

#### Atomicidad

Reserva, reprogramación y cancelación poseen operaciones relacionadas que deben confirmarse o revertirse de manera consistente.

#### Auditoría

El modelo relacional facilita mantener un historial asociado a cada cita y usuario responsable.

#### Evolución

Permite iniciar con una solución relativamente sencilla e introducir posteriormente:

- réplicas;
- particionamiento;
- archivado;
- optimizaciones;

solo cuando exista evidencia que lo justifique.

#### Complejidad

Presenta menor complejidad inicial que una estrategia híbrida o distribuida.

### Alternativas menos adecuadas actualmente

**Persistencia documental/NoSQL como almacenamiento principal**

No se descarta técnicamente, pero ofrece menos ventajas para este dominio frente a la cantidad de relaciones, restricciones y operaciones transaccionales existentes.

**Estrategia híbrida desde la primera versión**

Es técnicamente viable, pero añade componentes y sincronización que no están justificados por la carga inicial conocida.

**Persistencia relacional sin estrategia de evolución**

Puede funcionar inicialmente, pero debe contemplar desde el diseño la posibilidad de crecer hacia el escenario establecido en D-18.

---

## 16. Conclusión

El análisis especializado indica que el Sistema de Gestión de Citas y Atención Virtual necesita principalmente:

- integridad referencial;
- consistencia fuerte;
- transacciones;
- control de concurrencia;
- auditoría;
- conservación histórica;
- recuperación;
- posibilidad de crecimiento.

Por estas características, la estrategia provisionalmente más alineada con el proyecto es una:

**persistencia relacional transaccional con capacidad de evolución progresiva.**

La recomendación no implica seleccionar PostgreSQL, SQL Server, MySQL/MariaDB ni ningún otro producto específico.

La selección del motor deberá realizarse posteriormente considerando:

- características técnicas;
- costos;
- conocimientos disponibles;
- infraestructura;
- herramientas de administración;
- compatibilidad con backup y recuperación;
- pruebas de rendimiento;
- necesidades de mantenimiento.

En consecuencia, el resultado de esta etapa es:

**ESTRATEGIA DE PERSISTENCIA RECOMENDADA PROVISIONALMENTE: RELACIONAL TRANSACCIONAL CON EVOLUCIÓN POSTERIOR.**

**MOTOR DE BASE DE DATOS: PENDIENTE DE SELECCIÓN.**

La decisión final deberá mantenerse bajo validación humana y conservar trazabilidad con RF, RNF, RN, criterios de aceptación y decisiones arquitectónicas.