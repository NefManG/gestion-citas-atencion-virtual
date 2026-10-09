# Reglas obligatorias — Landing

## Propósito

Landing pública del Sistema de Gestión de Citas y Atención Virtual
del Hospital Boliviano Español.

Su objetivo es presentar los servicios médicos disponibles,
informar sobre especialidades y médicos, y orientar al usuario
hacia la atención médica.

La Landing NO es el sistema administrativo.

---

## Tecnología permitida

- PHP 8.x puro
- PostgreSQL
- PDO
- HTML5
- CSS3
- JavaScript ES6+
- Fetch API cuando sea necesario

---

## Prohibido sin decisión explícita

- Laravel
- Symfony
- React
- Vue
- Angular
- Bootstrap
- Tailwind
- jQuery
- Node
- Vite
- Webpack
- GSAP
- librerías externas de animación

No incorporar nuevas tecnologías automáticamente.

---

## Arquitectura

La Landing deberá mantener separación de responsabilidades.

Flujo permitido:

```text
Usuario
→ Landing
→ View
→ Service
→ Repository
→ PDO
→ PostgreSQL