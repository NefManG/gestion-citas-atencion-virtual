# Política de imágenes — Landing Hospital Boliviano Español

## Objetivo

Garantizar que las imágenes utilizadas en la Landing
del Sistema de Gestión de Citas y Atención Virtual
del Hospital Boliviano Español tengan procedencia verificable,
sean adecuadas para un contexto de salud
y no generen riesgos legales o técnicos.

---

## Fuentes preferidas

Se podrán utilizar imágenes provenientes de:

- Pexels
- Unsplash
- Pixabay
- Wikimedia Commons

También podrán utilizarse otros repositorios
si su licencia y procedencia pueden verificarse.

---

## Fuentes no permitidas

No utilizar:

- Google Images;
- Pinterest;
- imágenes copiadas directamente de redes sociales;
- imágenes con procedencia desconocida;
- material con marcas visibles que puedan sugerir afiliación;
- imágenes protegidas sin autorización verificable.

---

## Uso de inteligencia artificial

No generar imágenes mediante IA
para la Landing del proyecto.

Las imágenes utilizadas deberán provenir
de fuentes verificables.

---

## Contexto hospitalario

Las imágenes deberán ser apropiadas para un entorno de salud.

Preferir imágenes relacionadas con:

- atención médica;
- profesionales de salud;
- consulta médica;
- hospital o clínica;
- bienestar;
- tecnología aplicada a salud;
- comunicación médico-paciente.

Evitar:

- procedimientos gráficos o invasivos;
- imágenes médicas sensibles;
- fotografías que puedan generar rechazo;
- imágenes que aparenten pertenecer al Hospital Boliviano Español
  cuando realmente sean fotografías genéricas.

---

## No simular identidad institucional

Las imágenes de médicos o instalaciones obtenidas
desde bancos de imágenes serán consideradas únicamente
recursos visuales demostrativos.

No se debe afirmar que una persona fotografiada
es un médico real del Hospital Boliviano Español.

No se debe afirmar que una instalación genérica
corresponde físicamente al hospital.

---

## Registro obligatorio

Toda imagen externa utilizada deberá registrarse en:

`proyecto/07_landing/docs/fuentes_imagenes.md`

Para cada recurso registrar:

- nombre o descripción;
- URL directa del recurso;
- página de origen;
- plataforma o fuente;
- autor, cuando esté disponible;
- licencia o condición de uso;
- fecha de verificación;
- sección de la Landing donde se utiliza.

---

## Regla de verificación

Si no se puede verificar la procedencia
o la condición de uso de una imagen:

NO UTILIZARLA.

---

## Fallback

Toda imagen externa deberá contar con un comportamiento
de respaldo cuando no pueda cargarse.

Ejemplos:

- fondo neutro;
- icono;
- placeholder local;
- ocultar el recurso sin romper el diseño.

La falla de una imagen no debe romper la Landing.

---

## Hero

La imagen principal del Hero podrá cargarse
sin `loading="lazy"` por tratarse de un recurso
visible inmediatamente.

Debe:

- tener tamaño adecuado;
- evitar archivos excesivamente pesados;
- mantener proporción;
- no provocar desplazamientos bruscos del contenido.

---

## Imágenes fuera del Hero

Las imágenes que no sean críticas deberán utilizar:

`loading="lazy"`

cuando corresponda.

---

## Performance

Obligatorio:

- evitar resoluciones mayores a las necesarias;
- reservar dimensiones o `aspect-ratio`;
- usar formatos web adecuados;
- reducir el peso de archivos;
- no cargar imágenes innecesarias;
- evitar múltiples recursos pesados simultáneamente.

---

## Accesibilidad

Toda imagen informativa debe incluir texto alternativo:

`alt`

El texto alternativo deberá describir
el propósito real de la imagen.

Las imágenes puramente decorativas podrán utilizar:

`alt=""`

---

## Médicos

Cuando la Landing muestre datos reales de médicos
provenientes de PostgreSQL:

- no asociar automáticamente fotografías genéricas
  a un médico real;
- no afirmar que una fotografía externa corresponde
  al profesional registrado;
- utilizar imagen genérica o avatar cuando no exista
  fotografía institucional aprobada.

---

## Evidencia

La política de imágenes será revisada
durante los checkpoints correspondientes.

No declarar una imagen como verificada
si no se comprobó realmente su procedencia.

La validación final deberá confirmar
que las imágenes utilizadas cumplen esta política.