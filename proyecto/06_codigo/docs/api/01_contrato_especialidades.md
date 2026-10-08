# Contrato de API - Especialidades

**Fase:** API piloto (Paso 18 — Verificación post-despliegue, Fase de base de datos completada y APROBADA)
**Estado del contrato:** CP-API-01 — Diseñado / Pendiente de validación humana
**Tabla piloto:** `especialidad` (base de datos `hospital_citas_dev`, PostgreSQL 18.6)
**Skill utilizado:** `api-design-principles`
**Stack definido (decisiones API aprobadas):** PHP 8.x puro (sin framework), PostgreSQL 18.6, PDO con driver PostgreSQL, REST + JSON, prefijo `/api/v1/`.

---

## 1. Alcance del contrato

Este documento define el contrato de la API piloto para la gestión de especialidades médicas
dentro del Sistema Web de Gestión de Citas y Atención Virtual del Hospital Boliviano Español.

**Cobertura:**

- Operaciones CRUD sobre la entidad `especialidad` (lectura, creación, actualización e inactivación).
- Validaciones server-side sobre los datos recibidos.
- Reglas de negocio aplicables a la entidad `especialidad`.

**Fuera de alcance de este contrato:**

- Autenticación y autorización (detallado en seguridad; la API piloto asume que la capa de seguridad
  identifica al `id_usuario` y al `rol` del solicitante antes de llamar al controlador).
- Operaciones sobre otras entidades (pacientes, médicos, citas, etc.).

---

## 2. Convenciones generales

### 2.1. Versionado y rutas

- Versión de API: `v1`.
- Prefijo base: `/api/v1/`.
- Todos los endpoints de especialidades se anclan en `/api/v1/especialidades`.
- Identificadores únicos (UUID en futuras entidades) — en la tabla piloto el identificador es
  `id_especialidad` (bigint autoincremental), por tanto la ruta usa `{id}` numérico.

### 2.2. Formato de intercambio

- Tipo de contenido de petición: `application/json`.
- Tipo de contenido de respuesta: `application/json`.
- Codificación de caracteres: UTF-8.
- Las fechas y horas se representan en formato ISO 8601 con zona horaria UTC (`YYYY-MM-DDTHH:MM:SS+00:00`),
  coincidiendo con el tipo `timestamp with time zone` de la base de datos.

### 2.3. Estructura de respuestas de éxito

| Campo      | Tipo   | Descripción                                                  |
|------------|--------|--------------------------------------------------------------|
| `data`     | object | Objeto con el recurso solicitado o un mensaje de confirmación. |
| `success`  | bool   | `true` cuando la operación se completó satisfactoriamente.    |
| `message`  | string | Mensaje descriptivo legible por el cliente.                   |

### 2.4. Estructura de respuestas de error

| Campo      | Tipo   | Descripción                                                                 |
|------------|--------|-----------------------------------------------------------------------------|
| `success`  | bool   | `false` cuando la operación falló.                                          |
| `error`    | object | Objeto con detalles del error (ver § 2.5).                                  |
| `message`  | string | Mensaje descriptivo legible por el cliente.                                 |

### 2.5. Estructura del objeto `error`

| Campo        | Tipo   | Descripción                                                                 |
|--------------|--------|-----------------------------------------------------------------------------|
| `code`       | string | Identificador único del tipo de error (para trazabilidad y localización).   |
| `message`    | string | Descripción legible del error.                                              |
| `field`      | string | Nombre del campo asociado al error (cuando aplica; `null` si no aplica).    |
| `details`    | array  | Detalles adicionales del error (validaciones específicas, etc.).            |

### 2.6. Codificación de errores (códigos HTTP)

| Código | Significado                       | Uso                                               |
|--------|-----------------------------------|---------------------------------------------------|
| 200    | OK                                | Operación completada correctamente.               |
| 201    | Created                           | Recurso creado exitosamente (POST).               |
| 400    | Bad Request                       | Solicitud malformada, parámetro inválido o dato no válido. |
| 401    | Unauthorized                      | Falta credencial de autenticación.                |
| 403    | Forbidden                         | Operación no autorizada para el rol del usuario.  |
| 404    | Not Found                         | Recurso no encontrado (especialidad inexistente). |
| 500    | Internal Server Error             | Error interno no controlado.                      |

### 2.7. Principios de seguridad (alineados a decisiones API)

- Todas las consultas a la base de datos utilizan **prepared statements** a través de PDO.
- La validación de datos se realiza **server-side**; ninguna entrada del cliente es confiable.
- Las credenciales de conexión se leen exclusivamente de variables de entorno.
- No hay secretos configurados en el repositorio.
- En producción no se exponen **stack traces** al cliente; se sustituyen por mensajes genéricos.

---

## 3. Reglas de negocio aplicables

| ID   | Regla de negocio                                                                 | Impacto en la API                                                                 |
|------|----------------------------------------------------------------------------------|-----------------------------------------------------------------------------------|
| RN-02 | Cada médico deberá estar asociado al menos a una especialidad médica activa.    | La API permite crear una especialidad inactiva temporalmente si no hay médicos asociados; sin embargo, antes de asociar una especialidad a un médico se debe verificar que la especialidad esté `activo = true`. |
| RN-17 | El administrador podrá gestionar: pacientes, médicos, **especialidades**, usuarios, roles. | Solo usuarios con rol de **administrador** pueden realizar POST, PUT y DELETE sobre la entidad `especialidad`. |

> **Observación:** las reglas RN-26, RN-27, RN-28, RN-01, RN-03..RN-16 corresponden a la entidad `cita` y no se aplican directamente a esta API piloto; se incluyen para evitar confusiones.

---

## 4. Mapeo entidad-base de datos

Este contrato se mapea directamente sobre la tabla `especialidad` de la base de datos `hospital_citas_dev`:

| Campo del contrato | Columna de base de datos | Tipo SQL                  | Regla de integridad |
|--------------------|--------------------------|---------------------------|---------------------|
| `id`               | `id_especialidad`        | `bigint`                  | PK (autoincremental)|
| `nombre`           | `nombre`                 | `text`                    | NOT NULL            |
| `descripcion`      | `descripcion`            | `text`                    | NULLABLE            |
| `activo`           | `activo`                 | `boolean`                 | NOT NULL, DEFAULT `true`|
| `fecha_creacion`   | `created_at`             | `timestamp with time zone`| NOT NULL, `now()`   |
| `fecha_actualizacion` | `updated_at`           | `timestamp with time zone`| NOT NULL, `now()`   |

> **Nota:** los campos `created_at` y `updated_at` son manejados automáticamente por la base de datos mediante `DEFAULT now()` y no se reciben por la API. El cliente puede leer `fecha_actualizacion` pero no debe enviarlo en el cuerpo de la petición.

---

## 5. Contratos de endpoints

### 5.1. LISTAR todas las especialidades activas

**GET** `/api/v1/especialidades`

**Propósito:** Obtener la lista de todas las especialidades médicas con `activo = true`.

**Parámetros de consulta (opcionales):**

| Parámetro   | Tipo   | Requerido | Descripción                                  |
|-------------|--------|-----------|----------------------------------------------|
| `pagina`    | int    | No        | Número de página (mínimo 1).                 |
| `limite`    | int    | No        | Cantidad de registros por página (mínimo 1, máximo 100). |
| `ordenar_por` | string | No    | Campos ordenables: `id`, `nombre`, `fecha_creacion` (con dirección `asc`/`desc`). |
| `buscar`    | string | No        | Búsqueda parcial por nombre (LIKE).          |

**Comportamiento:**

- Filtra automáticamente `activo = true`; las especialidades inactivas nunca aparecen en la lista.
- Si `pagina` o `limite` están fuera del rango permitido, la API devuelve el primer par por defecto (`pagina = 1`, `limite = 10`).
- Si `buscar` está vacío o no se envía, no se aplica filtro de búsqueda.

**Respuesta de éxito (200):**

```json
{
  "data": [
    {
      "id": 1,
      "nombre": "Cardiología",
      "descripcion": "Atención de enfermedades del corazón y sistema cardiovascular.",
      "activo": true,
      "fecha_creacion": "2026-09-15T08:30:00+00:00",
      "fecha_actualizacion": "2026-09-15T08:30:00+00:00"
    }
  ],
  "success": true,
  "message": "Especialidades obtenidas correctamente."
}
```

**Respuestas de error (400):**

```json
{
  "success": false,
  "error": {
    "code": "INVALID_PARAMETER",
    "message": "El parámetro 'limite' debe estar entre 1 y 100.",
    "field": "limite",
    "details": []
  },
  "message": "La solicitud contiene parámetros no válidos."
}
```

**Códigos de estado:**

| Código | Significado                                                  |
|--------|--------------------------------------------------------------|
| 200    | Lista devuelta correctamente.                                |
| 400    | Parámetro de consulta inválido (ej. `limite` > 100, `pagina` < 1). |

**RNF/RN de trazabilidad:** RNF-01 (formato JSON), RN-17 (solo administrador puede gestionar la lista).

---

### 5.2. OBTENER una especialidad por identificador

**GET** `/api/v1/especialidades/{id}`

**Propósito:** Obtener los detalles de una única especialidad.

**Parámetros de ruta:**

| Parámetro | Tipo | Requerido | Descripción                                  |
|-----------|------|-----------|----------------------------------------------|
| `id`      | int  | Sí        | Identificador único de la especialidad (`id_especialidad`). |

**Validaciones:**

- `id` debe ser un entero positivo.
- Si el `id` no corresponde a una especialidad **activa**, la API responde `404 Not Found`.

**Respuesta de éxito (200):**

```json
{
  "data": {
    "id": 1,
    "nombre": "Cardiología",
    "descripcion": "Atención de enfermedades del corazón y sistema cardiovascular.",
    "activo": true,
    "fecha_creacion": "2026-09-15T08:30:00+00:00",
    "fecha_actualizacion": "2026-09-15T08:30:00+00:00"
  },
  "success": true,
  "message": "Especialidad obtenida correctamente."
}
```

**Respuesta de error por recurso no encontrado (404):**

```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "No se encontró una especialidad activa con el identificador 999.",
    "field": null,
    "details": []
  },
  "message": "El recurso solicitado no existe."
}
```

**Códigos de estado:**

| Código | Significado                                                    |
|--------|----------------------------------------------------------------|
| 200    | Especialidad obtenida correctamente.                           |
| 400    | El identificador no es un entero positivo (ej. `-1`, `abc`).   |
| 404    | No existe una especialidad activa con el identificador indicado.|

**RNF/RN de trazabilidad:** RNF-01 (formato JSON), RNF-05 (manejo seguro de errores).

---

### 5.3. CREAR una especialidad

**POST** `/api/v1/especialidades`

**Propósito:** Registrar una nueva especialidad médica.

**Autorización:** Solo usuarios con rol de **administrador** (según RN-17).

**Cuerpo de la petición (JSON, requerido):**

| Campo        | Tipo   | Requerido | Descripción                                                                 |
|--------------|--------|-----------|-----------------------------------------------------------------------------|
| `nombre`     | string | Sí        | Nombre de la especialidad. Máximo 255 caracteres. Debe ser único (ver nota). |
| `descripcion`| string | No        | Descripción de la especialidad. Máximo 1024 caracteres. Puede ser `null` o vacío. |

**Validaciones:**

| Validación            | Código de error           | Mensaje                                                    |
|-----------------------|---------------------------|------------------------------------------------------------|
| Falta `nombre`        | `FIELD_REQUIRED`          | "El campo 'nombre' es requerido."                          |
| `nombre` > 255 chars  | `FIELD_TOO_LONG`          | "El campo 'nombre' no puede exceder los 255 caracteres."   |
| `nombre` vacío        | `FIELD_EMPTY`             | "El campo 'nombre' no puede estar vacío."                  |
| Falta `descripcion`   | (permitido)               | Se usa `null` automáticamente.                             |
| `descripcion` > 1024 chars | `FIELD_TOO_LONG`    | "El campo 'descripcion' no puede exceder los 1024 caracteres." |

> **Nota sobre unicidad:** la base de datos `especialidad.nombre` no tiene constraint UNIQUE. Por tanto, la API **no** rechaza duplicados en este checkpoint; la validación de unicidad queda como mejora pendiente para CP-API-05 (se recomienda agregar un `UNIQUE` en la columna `nombre` en una migración futura).

**Respuesta de éxito (201):**

```json
{
  "data": {
    "id": 5,
    "nombre": "Dermatología",
    "descripcion": "Atención de la piel, cabello y uñas.",
    "activo": true,
    "fecha_creacion": "2026-10-05T14:20:00+00:00",
    "fecha_actualizacion": "2026-10-05T14:20:00+00:00"
  },
  "success": true,
  "message": "Especialidad creada correctamente. id=5"
}
```

**Respuestas de error:**

```json
// 400 - Bad Request (validación fallida)
{
  "success": false,
  "error": {
    "code": "FIELD_REQUIRED",
    "message": "El campo 'nombre' es requerido.",
    "field": "nombre",
    "details": []
  },
  "message": "La solicitud no contiene los datos requeridos."
}
```

```json
// 401 - Unauthorized (sin credencial válida)
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Debe autenticarse para realizar esta operación.",
    "field": null,
    "details": []
  },
  "message": "Credenciales no proporcionadas o inválidas."
}
```

```json
// 403 - Forbidden (sin rol administrador)
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "No tiene permisos para crear especialidades.",
    "field": null,
    "details": []
  },
  "message": "La operación no está autorizada para este usuario."
}
```

```json
// 500 - Internal Server Error (ej. conexión a base de datos caída)
{
  "success": false,
  "error": {
    "code": "DATABASE_ERROR",
    "message": "No se pudo conectar a la base de datos.",
    "field": null,
    "details": []
  },
  "message": "Ocurrió un error interno al procesar la solicitud."
}
```

**Códigos de estado:**

| Código | Significado                                                                 |
|--------|-----------------------------------------------------------------------------|
| 201    | Especialidad creada correctamente.                                          |
| 400    | Uno o más campos del cuerpo de la petición no son válidos.                  |
| 401    | El solicitante no está autenticado.                                         |
| 403    | El solicitante está autenticado pero no tiene permiso (rol != administrador). |
| 500    | Error interno en el servidor (sin stack trace expuesto).                    |

**RNF/RN de trazabilidad:** RNF-01 (JSON), RNF-05 (manejo seguro de errores), RN-17 (rol administrador), RNF-06 (roles).

---

### 5.4. ACTUALIZAR una especialidad existente

**PUT** `/api/v1/especialidades/{id}`

**Propósito:** Actualizar el `nombre` y/o la `descripcion` de una especialidad existente.

**Autorización:** Solo usuarios con rol de **administrador**.

**Parámetros de ruta:**

| Parámetro | Tipo | Requerido | Descripción                                  |
|-----------|------|-----------|----------------------------------------------|
| `id`      | int  | Sí        | Identificador único de la especialidad a actualizar. |

**Cuerpo de la petición (JSON):**

| Campo        | Tipo   | Requerido | Descripción                                                                 |
|--------------|--------|-----------|-----------------------------------------------------------------------------|
| `nombre`     | string | No        | Nuevo nombre de la especialidad. Máximo 255 caracteres.                     |
| `descripcion`| string | No        | Nueva descripción. Máximo 1024 caracteres. Puede ser `null` o vacío.        |

**Validaciones:**

| Validación              | Código de error           | Mensaje                                                    |
|-------------------------|---------------------------|------------------------------------------------------------|
| Falta `id`              | `FIELD_REQUIRED`          | "El campo 'id' es requerido."                              |
| `id` no es entero       | `INVALID_PARAMETER`       | "El identificador debe ser un entero."                     |
| `id` <= 0               | `INVALID_PARAMETER`       | "El identificador debe ser un número positivo."            |
| `id` no existe          | `RESOURCE_NOT_FOUND`      | "No existe una especialidad activa con id={id}."           |
| `nombre` > 255 chars    | `FIELD_TOO_LONG`          | "El campo 'nombre' no puede exceder los 255 caracteres."   |
| `descripcion` > 1024 chars | `FIELD_TOO_LONG`      | "El campo 'descripcion' no puede exceder los 1024 caracteres." |

**Nota de diseño:** Se requiere al menos uno de los campos `nombre` o `descripcion`. Si ninguno se envía, la API responde `400 Bad Request` con el código `NO_FIELDS_PROVIDED`.

**Respuesta de éxito (200):**

```json
{
  "data": {
    "id": 3,
    "nombre": "Cardiología Pediátrica",
    "descripcion": "Atención cardiovascular de pacientes pediátricos.",
    "activo": true,
    "fecha_creacion": "2026-09-15T08:30:00+00:00",
    "fecha_actualizacion": "2026-10-05T15:10:00+00:00"
  },
  "success": true,
  "message": "Especialidad actualizada correctamente. id=3"
}
```

**Códigos de estado:**

| Código | Significado                                                                 |
|--------|-----------------------------------------------------------------------------|
| 200    | Especialidad actualizada correctamente.                                     |
| 400    | Datos inválidos (validación fallida o sin campos para actualizar).          |
| 401    | No autenticado.                                                             |
| 403    | Sin permiso (rol != administrador).                                         |
| 404    | No existe una especialidad activa con el identificador indicado.            |
| 500    | Error interno en el servidor.                                               |

**RNF/RN de trazabilidad:** RNF-01, RNF-05, RN-17, RNF-06.

> **Nota sobre `updated_at`:** La columna `updated_at` se actualiza automáticamente mediante `DEFAULT now()` en la base de datos. La API no recibe ni modifica este campo explícitamente; el valor reflejado en la respuesta corresponde al instante en que el trigger/fallback de la base de datos regrabó la fila.

---

### 5.5. INACTIVAR una especialidad (elimación lógica)

**DELETE** `/api/v1/especialidades/{id}`

**Propósito:** Inactivar una especialidad médica, estableciendo `activo = false`.

> **Regla operativa estricta:** `DELETE` en este endpoint **significa inactivar**, nunca eliminar físicamente. **Nunca** se debe ejecutar `DELETE FROM especialidad`.

**Autorización:** Solo usuarios con rol de **administrador**.

**Parámetros de ruta:**

| Parámetro | Tipo | Requerido | Descripción                                  |
|-----------|------|-----------|----------------------------------------------|
| `id`      | int  | Sí        | Identificador único de la especialidad a inactivar. |

**Validaciones:**

| Validación               | Código de error           | Mensaje                                                    |
|--------------------------|---------------------------|------------------------------------------------------------|
| `id` no es entero        | `INVALID_PARAMETER`       | "El identificador debe ser un entero."                     |
| `id` <= 0                | `INVALID_PARAMETER`       | "El identificador debe ser un número positivo."            |
| `id` no existe           | `RESOURCE_NOT_FOUND`      | "No existe una especialidad con id={id}."                  |
| `id` ya está inactiva    | `RESOURCE_ALREADY_INACTIVE` | "La especialidad con id={id} ya se encuentra inactiva."  |

**Respuesta de éxito (200):**

```json
{
  "data": {
    "id": 4,
    "nombre": "Endocrinología",
    "activo": false,
    "fecha_actualizacion": "2026-10-05T16:00:00+00:00"
  },
  "success": true,
  "message": "Especialidad inactivada correctamente. id=4 (activo=false)."
}
```

**Códigos de estado:**

| Código | Significado                                                                 |
|--------|-----------------------------------------------------------------------------|
| 200    | Especialidad inactivada (`activo = false`).                                 |
| 400    | Parámetro de ruta inválido.                                                 |
| 401    | No autenticado.                                                             |
| 403    | Sin permiso (rol != administrador).                                         |
| 404    | No existe una especialidad con el identificador indicado.                   |

**RNF/RN de trazabilidad:** RNF-01, RNF-05, decisiones API ("Eliminación lógica"), RN-17.

---

## 6. Matriz de errores y códigos de aplicación

| Código                | HTTP | Endpoint           | Causa                                                            |
|-----------------------|------|--------------------|------------------------------------------------------------------|
| `FIELD_REQUIRED`      | 400  | POST, PUT, GET{id} | Campo obligatorio (`nombre`, `id`) no provisto.                  |
| `FIELD_EMPTY`         | 400  | POST               | Campo `nombre` vacío.                                            |
| `FIELD_TOO_LONG`      | 400  | POST, PUT          | `nombre` > 255 o `descripcion` > 1024 caracteres.                |
| `INVALID_PARAMETER`   | 400  | GET, GET{id}, PUT, DELETE | Parámetro (`pagina`, `limite`, `id`) malformado o fuera de rango. |
| `NO_FIELDS_PROVIDED`  | 400  | PUT                | PUT sin `nombre` ni `descripcion`.                               |
| `RESOURCE_NOT_FOUND`  | 404  | GET, PUT, DELETE   | Recurso inexistente o inactivo (GET exige activo).               |
| `RESOURCE_ALREADY_INACTIVE` | 400  | DELETE       | DELETE sobre especialidad ya inactiva.                           |
| `UNAUTHORIZED`        | 401  | POST, PUT, DELETE  | Credencial faltante o inválida.                                  |
| `FORBIDDEN`           | 403  | POST, PUT, DELETE  | Rol del usuario no autorizado (debe ser administrador).          |
| `DATABASE_ERROR`      | 500  | Todos            | Fallo de conexión o ejecución SQL.                               |
| `INTERNAL_ERROR`      | 500  | Todos            | Excepción no controlada (sin stack trace en producción).         |

---

## 7. Rastreabilidad con requerimientos y decisiones

| Elemento de diseño          | Referencia en este contrato      | Origen                                                                 |
|-----------------------------|----------------------------------|------------------------------------------------------------------------|
| Prefijo de API `/api/v1/`   | §1, §5                           | decisiones_api.md                                                      |
| Stack: PHP 8.x puro, PDO    | Encabezado del documento         | decisiones_api.md                                                      |
| Formato JSON                | §2.2                             | decisiones_api.md, RNF-01                                            |
| GET lista                   | §5.1                             | decisiones_api.md                                                      |
| GET por id                  | §5.2                             | decisiones_api.md                                                      |
| POST crear                  | §5.3                             | decisiones_api.md                                                      |
| PUT actualizar              | §5.4                             | decisiones_api.md                                                      |
| DELETE inactivar (lógico)   | §5.5                             | decisiones_api.md ("eliminación lógica"), CP-API-07                    |
| Solo administrador escribe   | §5.3, §5.4, §5.5                 | RN-17, RNF-06                                                          |
| Campos `created_at`/`updated_at` | §4                         | Tabla real `especialidad` (DB introspección)                           |
| `descripcion` opcional      | §5.3                             | Columna `descripcion` NULLABLE en DB                                   |
| `activo` por defecto `true` | §5.3                             | Columna `activo` DEFAULT `true` en DB                                  |
| Manejo seguro de errores    | §2.7                             | decisiones_api.md ("sin stack traces en producción"), RNF-05         |

---

## 8. Cierre del checkpoint CP-API-01

- [x] Se diseñó el contrato de la API de especialidades conforme a la tabla piloto `especialidad`.
- [x] No se generó código PHP ni se escribió ninguna consulta directa en este checkpoint.
- [x] Se mantuvo trazabilidad con RF/RNF/RN y con el esquema real de la base de datos `hospital_citas_dev`.
- [x] El artefacto se almacenó en `proyecto/06_codigo/docs/api/01_contrato_especialidades.md`.
- [ ] **Validación humana requerida:** el contrato está pendiente de revisión y aprobación.

**Siguiente checkpoint:** CP-API-02 (Bootstrap PHP + `.env` + PDO PostgreSQL + `/api/v1/health`).

**No se inicia el backend** ni se crea ninguna implementación PHP en este checkpoint.
